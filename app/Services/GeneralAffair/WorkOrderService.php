<?php

namespace App\Services\GeneralAffair;

use App\Models\User;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Mail\WorkOrderNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class WorkOrderService
{
    /**
     * Membuat Tiket Work Order Baru
     */
    public function createWorkOrder(array $data, ?UploadedFile $filePhoto = null): array
    {
        return DB::transaction(function () use ($data, $filePhoto) {
            $user = Auth::user();
            $employee = User::where('nik', $data['requester_nik'])->first();


            $isAdminGA = $user->divisi === 'General Affair' || $user->role === 'admin_ga';
            $statusAwal = $isAdminGA ? 'approved' : 'waiting_approval';


            $fixName = $employee?->name ?? $data['requester_name'] ?? $user->name;
            $fixDept = $employee?->divisi ?? $data['requester_department'] ?? $user->divisi;


            $photoPath = $filePhoto ? $filePhoto->store('wo_ga', 'public') : null;


            $wo = WorkOrderGeneralAffair::create([
                'ticket_num'           => $this->generateTicketNum(),
                'requester_id'         => $user->id,
                'requester_nik'        => $data['requester_nik'],
                'requester_name'       => $fixName,
                'requester_department' => $fixDept,
                'plant'                => $data['plant_id'],
                'department'           => $data['department'],
                'category'             => $data['category'],
                'description'          => $data['description'],
                'parameter_permintaan' => $data['parameter_permintaan'] ?? '-',
                'status_permintaan'    => 'OPEN',
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'status'               => $statusAwal,
                'photo_path'           => $photoPath,
            ]);


            $this->sendNotifications($wo, $employee, $user, $statusAwal, $data['department']);

            return [
                'ticket' => $wo,
                'message' => $isAdminGA
                    ? 'Permintaan Berhasil Dibuat (Auto-Approved by GA).'
                    : 'Permintaan Berhasil Dibuat! Silahkan hubungi Manager Dept Anda untuk Approve tiket ini!.'
            ];
        });
    }
    public function updateStatus($id, array $data, ?UploadedFile $completionPhoto = null): void
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);

        $updateData = [
            'status' => $data['status'],
            'processed_by_name' => $data['processed_by_name'],
            'category' => $data['category']
        ];

        if (!empty($data['start_date'])) {
            $updateData['actual_start_date'] = $data['start_date'];
        } else if ($data['status'] === 'in_progress' && is_null($ticket->actual_start_date)) {
            $updateData['actual_start_date'] = now();
        }

        if ($data['status'] === 'completed') {
            if ($completionPhoto) {
                $updateData['photo_completion_path'] = $completionPhoto->store('wo_ga_completed', 'public');
            }
            $updateData['actual_completion_date'] = $data['actual_completion_date'];
            $updateData['completion_note'] = $data['completion_note'] ?? null;
            $updateData['cancellation_note'] = null;
        }

        if ($data['status'] === 'cancelled') {
            $updateData['cancellation_note'] = $data['cancellation_note'] ?? null;
            $updateData['actual_completion_date'] = null;
            $updateData['completion_note'] = null;
            $updateData['photo_completion_path'] = null;
        }

        if (!empty($data['department'])) $updateData['department'] = $data['department'];
        if (!empty($data['target_date'])) $updateData['target_completion_date'] = $data['target_date'];

        $ticket->update($updateData);

        $this->sendStatusChangeEmail($ticket, $data['status']);
        $this->logHistory($ticket->id, 'Status Update', 'Status diubah menjadi: ' . ucfirst($data['status']));
    }

    public function processTicket($id, string $action, ?string $reason): array
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $user = Auth::user();

        // 1. BERSIHKAN ROLE (Penting: Hapus spasi & lowercase)
        $cleanRole = strtolower(trim($user->role));

        // HAPUS DD DI SINI AGAR SCRIPT TIDAK MATI SEBELUM UPDATE

        $alertData = null;

        if ($action == 'reject') {
            $newStatus = 'rejected';
            $desc = "Ditolak. Alasan: $reason";
        } else {
            // 2. LOGIKA ADMIN GA (Gunakan $cleanRole & in_array agar aman)
            // Kita cek variasi penulisan role admin
            $adminRoles = ['ga.admin', 'admin_ga', 'ga_admin'];

            if (in_array($cleanRole, $adminRoles)) {
                // --- AREA ADMIN GA ---
                $newStatus = 'pending';
                $desc = "Tiket diterima oleh General Affair dan masuk antrian pending.";

                $alertData = [
                    'type' => 'warning',
                    'message' => 'Tiket berhasil disetujui (Status: Pending).',
                    'instruction' => 'Harap segera kerjakan tiket yang baru anda Approve dan ubah status menjadi In Progress!'
                ];
            } else {
                // --- AREA ADMIN DIVISI LAIN ---
                $newStatus = 'waiting_ga_approval';
                $desc = "Disetujui oleh Admin Divisi ({$user->divisi}). Menunggu tindak lanjut General Affair.";
            }
        }

        // 3. UPDATE DATABASE
        // update() sekarang PASTI jalan karena dd() sudah dihapus
        $ticket->update([
            'status' => $newStatus,
            'rejection_reason' => ($action === 'reject') ? $reason : null,
            'processed_by' => $user->id,
            'processed_by_name' => $user->name,
            'updated_at' => now()
        ]);

        $this->logHistory($ticket->id, ucfirst($newStatus), $desc);

        return [
            'status' => 'success',
            'message' => ($action === 'approve' ? 'Tiket Disetujui' : 'Tiket Ditolak'),
            'alert' => $alertData
        ];
    }

    public function getWorkOrders($request, $user)
    {
        $query = WorkOrderGeneralAffair::query();

        $this->applyAccessControl($query, $user);
        $this->applyFilters($query, $request);

        $data = $query->with(['user', 'histories.user', 'plantInfo'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $data->getCollection()->transform(function ($ticket) {
            $ticket->approver_divisi = null;
            if ($ticket->processed_by_name) {
                $approver = User::where('name', $ticket->processed_by_name)->first();
                $ticket->approver_divisi = $approver ? $approver->divisi : null;
            }
            return $ticket;
        });
        return $data;
    }

    public function getIndexStats($user)
    {
        $query = WorkOrderGeneralAffair::query();
        $this->applyAccessControl($query, $user);

        // Cloning query dasar agar filter user/role tetap berlaku
        $baseQuery = clone $query;

        // 1. Hitung Delayed (Logika: Belum Selesai DAN Target Date sudah lewat)
        $countDelayed = (clone $baseQuery)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->whereNotNull('target_completion_date') // Pastikan ada target date
            ->where('target_completion_date', '<', now()) // Tanggal target lebih kecil dari sekarang
            ->count();

        return [
            'countTotal'           => (clone $baseQuery)->count(),
            // Pending = Tiket yang sudah di-approve GA tapi belum dikerjakan
            'countPending'         => (clone $baseQuery)->where('status', 'pending')->count(),
            // Waiting Approval = Menunggu approval Dept Head/GA
            'countWaitingApproval' => (clone $baseQuery)->whereIn('status', ['waiting_approval', 'waiting_ga_approval'])->count(),
            'countInProgress'      => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'countCompleted'       => (clone $baseQuery)->where('status', 'completed')->count(),
            'countDelayed'         => $countDelayed, // Tambahkan ini ke return
        ];
    }


    public function applyAccessControl(Builder $query, $user)
    {
        if (!$user) return;

        // 1. LOGIKA ADMIN GA (Melihat semua tiket untuk GA)
        if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
            $query->where(function ($q) {
                // Admin GA melihat tiket yang STATUSNYA relevan
                $q->whereIn('status', [
                    'pending',
                    'approved',
                    'in_progress',
                    'completed',
                    'OPEN',
                    'waiting_ga_approval',
                    'rejected'
                ]);

                // ATAU tiket yang TUJUANNYA ke departemen GA (walau status masih waiting_approval)
                $q->orWhere(function ($sub) {
                    $sub->where('status', 'waiting_approval')
                        ->whereIn('department', ['GA', 'General Affair']);
                });
            });
        } else {
            // 2. LOGIKA ADMIN DIVISI LAIN (MT, ENG, LV, dll)
            $roleMap = $this->getRoleMapping();

            if (array_key_exists($user->role, $roleMap)) {
                // Ambil daftar divisi yang DIKELOLA oleh admin ini
                // Contoh: lv.admin mengelola ['PLANT A', 'PLANT C', 'Low Voltage']
                $managedDepts = $roleMap[$user->role];

                $query->where(function ($q) use ($user, $managedDepts) {

                    // A. ADMIN MELIHAT TIKET YANG "DITUJUKAN" KE DIVISINYA
                    // (User memilih 'PLANT A', maka lv.admin harus bisa lihat)
                    $q->whereIn('department', $managedDepts)

                        // B. ATAU TIKET YANG DIA BUAT SENDIRI (Sebagai Requester)
                        ->orWhere('requester_id', $user->id);
                });
            } else {
                // 3. LOGIKA USER BIASA
                // Hanya bisa melihat tiket yang dia buat sendiri
                $query->where('requester_department', $user->divisi);
            }
        }
    }
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
    private function applyFilters(Builder $query, $request)
    {

        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%")
                    ->orWhere('category', 'like', "%{$request->search}%")
                    ->orWhere('processed_by_name', 'like', "%{$request->search}%");
            });
        });


        $query->when($request->filled('status') && $request->status !== 'all', fn($q) => $q->where('status', $request->status));
        $query->when($request->filled('category') && $request->category !== 'all', fn($q) => $q->where('category', $request->category));
        $query->when($request->filled('parameter') && $request->parameter !== 'all', fn($q) => $q->where('parameter_permintaan', $request->parameter));
        $query->when($request->filled('plant_id') && $request->plant_id !== 'all', fn($q) => $q->where('plant', $request->plant_id));


        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
    }
    /**
     * Generate Nomor Tiket Otomatis (GA-YYYYMMDD-XXXX)
     
     */
    private function generateTicketNum(): string
    {
        $prefix = 'GA-' . date('Ymd');
        $lastTicket = WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')
            ->latest('id')->first();

        $number = $lastTicket ? sprintf('%04d', intval(substr($lastTicket->ticket_num, -4)) + 1) : '0001';
        return $prefix . '-' . $number;
    }

    private function logHistory($woId, $action, $desc)
    {
        WorkOrderGaHistory::create([
            'work_order_id' => $woId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $desc
        ]);
    }

    private function sendStatusChangeEmail($ticket, $status)
    {
        $pelapor = User::find($ticket->requester_id);
        if (!$pelapor || empty($pelapor->email)) return;

        $type = match ($status) {
            'completed' => 'completed',
            'cancelled', 'rejected' => 'rejected',
            'approved', 'in_progress' => 'approved',
            default => null
        };

        if ($type) {
            $this->safeMail($pelapor->email, new WorkOrderNotification($ticket, $type));
        }
    }


    /**
     * Mengatur Pengiriman Notifikasi
     */
    private function sendNotifications($wo, $employee, $user, string $statusAwal, string $targetDept): void
    {
        // 1. Email ke Pelapor [cite: 126]
        $pelaporEmail = $employee?->email ?? $user->email;
        if ($pelaporEmail) {
            $this->safeMail($pelaporEmail, new WorkOrderNotification($wo, 'created_info'));
        }

        // 2. Email ke Approver (Jika butuh approval)
        if ($statusAwal === 'waiting_approval') {
            $approvers = $this->getApproversForDept($targetDept);

            if ($approvers->isEmpty()) {
                Log::warning("WO GA: Tidak ada approver ditemukan untuk dept: $targetDept");
            }

            foreach ($approvers as $approver) {
                $this->safeMail($approver->email, new WorkOrderNotification($wo, 'need_approval'));
            }
        }
    }

    /**
     * Mencari User Approver berdasarkan Departemen Tujuan
     * Logic dari 
     */
    private function getApproversForDept(string $targetDept): Collection
    {
        $roleMap = $this->getRoleMapping();
        $targetRole = null;

        // 1. Cari Role berdasarkan Mapping
        foreach ($roleMap as $role => $departments) {
            if (in_array($targetDept, $departments)) {
                $targetRole = $role;
                break;
            }
        }

        // 2. Ambil User dengan Role tersebut
        if ($targetRole) {
            $approvers = User::where('role', $targetRole)->get();
            if ($approvers->isNotEmpty()) {
                return $approvers;
            }
        }

        // 3. Fallback: Cari Manager/SPV di Divisi tersebut jika Mapping tidak ketemu 
        return User::where('divisi', $targetDept)
            ->whereIn('role', ['manager', 'spv', 'supervisor', 'dept_head'])
            ->get();
    }

    /**
     * Wrapper aman untuk kirim email agar tidak crash jika SMTP error
     */
    private function safeMail(?string $to, $mailable): void
    {
        if (empty($to)) return;

        try {
            Mail::to($to)->send($mailable);
        } catch (\Exception $e) {
            Log::error('Mail Error (WorkOrderService): ' . $e->getMessage());
        }
    }

    /**
     * Definisi Mapping Role ke Departemen

     */
    private function getRoleMapping(): array
    {
        return [
            'eng.admin'       => ['Engineering', 'engineering', 'ENGINEERING', 'PE'],
            'fh.admin'        => ['Facility', 'FH', 'FACILITY'],
            'mt.admin'        => ['Maintenance', 'maintenance', 'MT', 'MAINTENANCE', 'mt'],
            'lv.admin'        => ['Low Voltage', 'LOW VOLTAGE', 'low voltage', 'LV', 'lv'],
            'mv.admin'        => ['Medium Voltage', 'medium voltage', 'MV', 'mv'],
            'qr.admin'        => ['QR', 'qr'],
            'sc.admin'        => ['SC', 'sc'],
            'fo.admin'        => ['FO', 'fo'],
            'ss.admin'        => ['SS', 'ss'],
            'fa.admin'        => ['FA', 'fa'],
            'it.admin'        => ['IT', 'it'],
            'hc.admin'        => ['HC', 'hc'],
            'sales.admin'     => ['Sales', 'sales'],
            'marketing.admin' => ['Marketing', 'marketing'],
            'ga.admin'        => ['GA', 'General Affair']
        ];
    }
}
