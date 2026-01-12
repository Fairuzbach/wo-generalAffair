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
                    : 'Permintaan Berhasil Dibuat! Silahkan hubungi SPV/Manager Dept Anda.'
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

    public function processTicket($id, string $action, ?string $reason): string
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $newStatus = ($action === 'approve') ? 'in_progress' : 'rejected';

        $ticket->update([
            'status' => $newStatus,
            'rejection_reason' => ($action === 'reject') ? $reason : null,
            'processed_by' => Auth::id(),
            'processed_by_name' => Auth::user()->name,
            'updated_at' => now()
        ]);

        $desc = $action === 'reject' ? "Ditolak. Alasan: $reason" : "Disetujui dan sedang dikerjakan.";
        $this->logHistory($ticket->id, ucfirst($newStatus), $desc);

        return ($action === 'approve') ? 'Disetujui' : 'Ditolak';
    }

    public function getWorkOrders($request, $user)
    {
        $query = WorkOrderGeneralAffair::query();

        $this->applyAccessControl($query, $user);
        $this->applyFilters($query, $request);

        $data = $query->with(['user', 'histories.user', 'plantInfo'])
            ->orderBy('updated_at', 'desc')
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

        return [
            'countTotal' => (clone $query)->count(),
            'countPending' => (clone $query)->where('status', 'pending')->count(),
            'countWaitingApproval' => (clone $query)->where('status', 'waiting_approval')->count(),
            'countInProgress' => (clone $query)->where('status', 'in_progress')->count(),
            'countCompleted' => (clone $query)->where('status', 'completed')->count(),
        ];
    }

    public function applyAccessControl(Builder $query, $user)
    {
        if (!$user) return;
        if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
            $query->where(function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'in_progress', 'completed', 'OPEN']);

                $q->orWhere(function ($sub) {
                    $sub->where('status', 'waiting_approval')
                        ->whereIn('department', ['GA', 'General Affair']);
                });
            });
        } else {
            $roleMap = $this->getRoleMapping();
            if (array_key_exists($user->role, $roleMap)) {
                $allowedDepts = $roleMap[$user->role];
                $query->where(function ($q) use ($user, $allowedDepts) {
                    $q->whereIn('department', $allowedDepts)
                        ->orWhere('requester_id', $user->id);
                });
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
            'mt.admin'        => ['Maintenance', 'maintenance', 'MT'],
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
