<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// --- MODELS ---
use App\Models\User;
use App\Models\Employee; // Pastikan model Employee ada
use App\Models\Engineering\Plant;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;

// --- EXPORT ---
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WorkOrderExport;

class GeneralAffairController extends Controller
{
    // =========================================================================
    // 1. HELPER & AJAX
    // =========================================================================

    // API untuk mengambil data karyawan berdasarkan NIK saat input form
    public function checkEmployee(Request $request)
    {
        // Cari user berdasarkan NIK
        $employee = \App\Models\User::where('nik', $request->nik)->first();

        if ($employee) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'name' => $employee->name,

                    // PERUBAHAN DI SINI:
                    // Kiri ('department') adalah nama Kunci untuk JavaScript
                    // Kanan ($employee->divisi) adalah nama Kolom di Database Anda
                    'department' => $employee->divisi
                ]
            ], 200);
        } else {
            // Return 200 dengan status error agar Console bersih
            return response()->json([
                'status' => 'error',
                'message' => 'NIK tidak ditemukan'
            ], 200);
        }
    }

    // Generate Nomor Tiket Otomatis (Format: GA-YYYYMMDD-XXXX)
    private function generateTicketNum()
    {
        $prefix = 'GA-' . date('Ymd');
        $lastTicket = WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastTicket) {
            $number = '0001';
        } else {
            $lastNumber = substr($lastTicket->ticket_num, -4);
            $number = sprintf('%04d', intval($lastNumber) + 1);
        }

        return $prefix . '-' . $number;
    }

    // Helper Query Builder untuk Filter (Digunakan di Index & Export)
    private function buildQuery(Request $request)
    {
        $query = WorkOrderGeneralAffair::query();
        $user = Auth::user();

        // LOGIKA AKSES DATA (Scope)
        if ($user) {
            // GA Admin lihat semua (kecuali waiting_spv di index, tapi di export mungkin butuh semua)
            if ($user->role !== User::ROLE_GA_ADMIN) {
                // User biasa hanya lihat tiket sendiri
                // (Kecuali Eng Admin/Boss yang dihandle di index khusus)
                $query->where('requester_id', $user->id);
            }
        }

        // Filter Search Global
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_num', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('plant', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('processed_by_name', 'like', "%{$search}%");
            });
        }

        // Filter Spesifik
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('parameter') && $request->parameter !== 'all') {
            $query->where('parameter_permintaan', $request->parameter);
        }
        if ($request->filled('plant_id') && $request->plant_id !== 'all') {
            $query->where('plant', $request->plant_id);
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query->with('user')->latest();
    }

    // =========================================================================
    // 2. MAIN PAGES (INDEX & DASHBOARD)
    // =========================================================================

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = WorkOrderGeneralAffair::query();

        // --- A. LOGIKA HAK AKSES (VIEW PERMISSION) ---
        if ($user) {
            // 1. GA ADMIN
            if ($user->role === User::ROLE_GA_ADMIN) {
                // GA Admin tidak perlu melihat tiket yang belum diapprove atasan (waiting_spv)
                $query->where('status', '!=', 'waiting_spv');
            }
            // 2. TEKNIS ADMIN (Eng, MT, FH)
            elseif ($user->isTeknisAdmin()) {
                // Melihat tiket dari departemen mereka (Engineering/Maintenance)
                // Agar bisa Approve/Monitor
                $query->where(function ($q) {
                    $q->where('requester_department', 'LIKE', '%Engineering%')
                        ->orWhere('requester_department', 'LIKE', '%Maintenance%')
                        ->orWhere('requester_department', 'LIKE', '%Facility%')
                        ->orWhereNull('requester_department'); // Fallback
                });
            }
            // 3. USER BIASA
            else {
                $query->where('requester_id', $user->id);
            }
        }

        // --- B. FILTERING ---
        // Search Global
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        });

        // Filter Dropdown
        $query->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status));
        $query->when($request->category && $request->category !== 'all', fn($q) => $q->where('category', $request->category));
        $query->when($request->parameter && $request->parameter !== 'all', fn($q) => $q->where('parameter_permintaan', $request->parameter));
        $query->when($request->plant_id && $request->plant_id !== 'all', fn($q) => $q->where('plant', $request->plant_id));

        // --- C. GET DATA ---
        $workOrders = $query->with(['user', 'histories.user', 'plantInfo'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $pageIds = $workOrders->pluck('id')->toArray();
        // Ambil list Plant untuk filter (kecuali dept office)
        $plants = Plant::whereNotIn('name', ['QC', 'GA', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH'])->get();

        // --- D. STATISTIK HEADER (Global Counter) ---
        $statsQuery = WorkOrderGeneralAffair::query();

        // Logic Statistik harus sama dengan Logic Hak Akses di atas
        if ($user) {
            if ($user->role === User::ROLE_GA_ADMIN) {
                $statsQuery->where('status', '!=', 'waiting_spv');
            } elseif ($user->isTeknisAdmin()) {
                $statsQuery->where(function ($q) {
                    $q->where('requester_department', 'LIKE', '%Engineering%')
                        ->orWhere('requester_department', 'LIKE', '%Maintenance%')
                        ->orWhereNull('requester_department');
                });
            } else {
                $statsQuery->where('requester_id', $user->id);
            }
        }

        // Hitung (Clone query agar tidak saling menimpa)
        $countTotal      = (clone $statsQuery)->count();
        $countPending    = (clone $statsQuery)->where('status', 'waiting_spv')->count();
        $countInProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted  = (clone $statsQuery)->where('status', 'completed')->count();

        return view('Division.GeneralAffair.GeneralAffair', compact(
            'workOrders',
            'plants',
            'pageIds',
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted'
        ));
    }

    public function dashboard(Request $request)
    {
        // Proteksi Dashboard
        if (Auth::check() && Auth::user()->role !== User::ROLE_GA_ADMIN) {
            abort(403, 'Akses Ditolak. Dashboard hanya untuk GA Admin.');
        }

        // Query Dasar
        $query = WorkOrderGeneralAffair::where('status', '!=', 'cancelled');

        // Filter Tanggal Dashboard
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        }

        $statsQuery = clone $query; // Simpan base query untuk statistik

        // Ambil Data List (Limit 100 agar ringan jika tanpa filter)
        if (!$request->filled('start_date')) {
            $query->orderBy('created_at', 'asc')->take(100);
        } else {
            $query->orderBy('created_at', 'asc');
        }
        $workOrders = $query->get();

        // Counter Dashboard
        $countTotal      = (clone $statsQuery)->count();
        $countPending    = (clone $statsQuery)->where('status', 'pending')->count();
        $countInProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted  = (clone $statsQuery)->where('status', 'completed')->count();

        // --- CHART DATA PREPARATION ---

        // 1. Chart Detail (Timeline)
        $detailLabels = [];
        $detailData   = [];
        $detailColors = [];

        foreach ($workOrders as $wo) {
            $detailLabels[] = "[$wo->department] $wo->ticket_num";

            // Hitung Durasi
            $start = Carbon::parse($wo->created_at);
            if ($wo->status == 'completed' && $wo->actual_completion_date) {
                $end = Carbon::parse($wo->actual_completion_date);
            } elseif ($wo->target_completion_date) {
                $end = Carbon::parse($wo->target_completion_date);
            } else {
                $end = now();
            }
            if ($end < $start) $end = $start;

            $diffDays = $start->diffInDays($end);
            $detailData[] = $diffDays < 1 ? 1 : $diffDays;

            // Warna Bar
            if ($wo->status == 'completed') $detailColors[] = '#10b981'; // Hijau
            elseif ($end < now()) $detailColors[] = '#ef4444'; // Merah (Telat)
            else $detailColors[] = '#3b82f6'; // Biru
        }

        // 2. Chart Phase (Beban Kerja per Dept)
        $groupedDept = $workOrders->groupBy('department');
        $phaseLabels = [];
        $phaseData   = [];
        foreach ($groupedDept as $deptName => $tickets) {
            $phaseLabels[] = $deptName ?? 'Unassigned';
            $phaseData[]   = $tickets->count();
        }

        $chartDataDetail = ['labels' => $detailLabels, 'data' => $detailData, 'colors' => $detailColors];
        $chartDataPhase  = ['labels' => $phaseLabels, 'data' => $phaseData, 'colors' => '#eab308'];

        // 3. Helper Chart Sederhana
        $getChartData = function ($col, $q) {
            return $q->selectRaw("$col as label, count(*) as total")
                ->whereNotNull($col)->groupBy($col)->orderByDesc('total')->get();
        };

        $locData   = $getChartData('plant', clone $statsQuery);
        $deptData  = $getChartData('department', clone $statsQuery);
        $paramData = $getChartData('parameter_permintaan', clone $statsQuery);

        // Bobot Chart
        $bobotData = (clone $statsQuery)->selectRaw('category, count(*) as total')
            ->groupBy('category')->pluck('total', 'category')->toArray();
        $chartBobotLabels = ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'];
        $chartBobotValues = [
            $bobotData['HIGH'] ?? $bobotData['BERAT'] ?? 0,
            $bobotData['MEDIUM'] ?? $bobotData['SEDANG'] ?? 0,
            $bobotData['LOW'] ?? $bobotData['RINGAN'] ?? 0
        ];

        // 4. Performance Calculation
        $filterMonth = $request->input('filter_month', date('Y-m'));
        $year = substr($filterMonth, 0, 4);
        $month = substr($filterMonth, 5, 2);

        $perfQuery = WorkOrderGeneralAffair::where('status', '!=', 'cancelled')
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('target_completion_date', $year)->whereMonth('target_completion_date', $month)
                    ->orWhere(function ($sub) use ($year, $month) {
                        $sub->whereNull('target_completion_date')->whereYear('created_at', $year)->whereMonth('created_at', $month);
                    });
            });

        $perfTotal      = $perfQuery->count();
        $perfCompleted  = (clone $perfQuery)->where('status', 'completed')->count();
        $perfPercentage = $perfTotal > 0 ? round(($perfCompleted / $perfTotal) * 100) : 0;

        return view('Division.GeneralAffair.Dashboard', compact(
            'workOrders',
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted',
            'perfTotal',
            'perfCompleted',
            'perfPercentage',
            'filterMonth',
            'chartDataDetail',
            'chartDataPhase',
            'chartBobotLabels',
            'chartBobotValues',
            'locData',
            'deptData',
            'paramData' // Kirim raw object agar mudah di-loop di blade
        ));
    }

    // =========================================================================
    // 3. CRUD ACTIONS (STORE, UPDATE, APPROVE, REJECT)
    // =========================================================================

    public function store(Request $request)
    {
        $request->validate([
            'requester_nik' => 'required',
            'plant_id'      => 'required',
            'department'    => 'required', // Ini Dept Tujuan (GA/IT/dll)
            'description'   => 'required',
            'category'      => 'required',
        ]);

        try {
            DB::beginTransaction();

            // 1. Cari Data Employee (Master Data)
            // PERBAIKAN: Gunakan Model User (sesuai tabel users)
            $employee = \App\Models\User::where('nik', $request->requester_nik)->first();

            // 2. Tentukan Data Nama & Dept
            // Priority: 
            // A. Data dari DB ($employee) -> Paling Akurat
            // B. Data dari Input Form Hidden ($request) -> Backup
            // C. Data Login (Auth) -> Backup terakhir

            $fixName = $employee?->name ?? $request->requester_name ?? Auth::user()->name;

            // PERBAIKAN PENTING: Ganti 'department' menjadi 'divisi'
            $fixDept = $employee?->divisi ?? $request->requester_department ?? Auth::user()->divisi;

            // 3. Simpan
            WorkOrderGeneralAffair::create([
                'ticket_num'           => $this->generateTicketNum(),
                'requester_id'         => Auth::id(), // Traceability (Siapa yang input)

                // Data Pelapor (Hasil Logic di atas)
                'requester_nik'        => $request->requester_nik,
                'requester_name'       => $fixName,
                'requester_department' => $fixDept, // <--- Data Dept Pelapor yang benar

                // Data Work Order
                'plant'                => $request->plant_id,
                'department'           => $request->department, // Dept Tujuan
                'category'             => $request->category,
                'description'          => $request->description,
                'parameter_permintaan' => $request->parameter_permintaan,
                'status_permintaan'    => 'OPEN',
                'target_completion_date' => $request->target_completion_date,
                'status'     => 'waiting_approval',
                'photo_path'           => $request->hasFile('photo')
                    ? $request->file('photo')->store('wo_ga', 'public')
                    : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Tiket berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            // Debugging: Tampilkan error spesifik biar tau salah dimana
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
    public function approveByTechnical(Request $request, $id)
    {
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $userRole = Auth::user()->role; // Misal: 'mt.admin', 'fh.admin'

        // 1. Validasi Hak Akses (Authorization)
        // Tiket untuk MT hanya boleh diapprove mt.admin, dst.
        $canApprove = false;

        if ($ticket->department == 'MT' && $userRole == 'mt.admin') $canApprove = true;
        if ($ticket->department == 'FH' && $userRole == 'fh.admin') $canApprove = true;
        if ($ticket->department == 'ENG' && $userRole == 'eng.admin') $canApprove = true;

        if (!$canApprove) {
            return back()->with('error', 'Anda tidak berhak meng-approve tiket departemen lain.');
        }

        // 2. Logic Decline
        if ($request->action === 'decline') {
            $ticket->update(['status' => 'rejected_by_technical', 'note' => $request->reason]);
            return back()->with('error', 'Tiket ditolak.');
        }

        // 3. Logic Approve -> Lempar ke GA
        $ticket->update([
            'status' => 'waiting_ga_approval', // Status 2: Menunggu GA
            'approved_tech_by' => Auth::id(),
            'approved_tech_at' => now(),
        ]);

        return back()->with('success', 'Disetujui. Menunggu persetujuan GA.');
    }
    public function approveByGA(Request $request, $id)
    {
        // 1. Validasi Role GA
        if (Auth::user()->role !== 'ga.admin') {
            abort(403, 'Hanya GA Admin yang bisa akses.');
        }

        $ticket = WorkOrderGeneralAffair::findOrFail($id);

        // 2. Decline
        if ($request->action === 'decline') {
            $ticket->update(['status' => 'rejected_by_ga', 'note' => $request->reason]);
            return back()->with('error', 'Tiket ditolak GA.');
        }

        // 3. Approve -> On Process (Bisa ubah target date/dept)
        $ticket->update([
            'status' => 'on_process', // Status 3: Final

            // GA Berhak mengubah/finalisasi data ini
            'department'             => $request->department,
            'target_completion_date' => $request->target_completion_date,

            'approved_ga_by' => Auth::id(),
            'approved_ga_at' => now(),
        ]);

        return back()->with('success', 'Tiket resmi diproses.');
    }

    // --- REJECT OLEH SPV/MANAGER (ENGINEERING/MT) ---
    // =========================================================================
    //  TAMBAHAN: LOGIKA UNIFIED APPROVAL/REJECT (Sesuai Alur Baru)
    // =========================================================================

    public function processTicket(Request $request, $id)
    {
        // 1. Validasi Input
        $request->validate([
            'action' => 'required|in:approve,reject', // Input dari tombol Approve/Reject
            'reason' => 'required_if:action,reject',  // Wajib isi alasan jika reject
        ]);

        try {
            DB::beginTransaction();

            // 2. Ambil Data Work Order
            $wo = WorkOrderGeneralAffair::findOrFail($id);

            // 3. Tentukan Status Baru
            // Jika Approve -> 'approved' (Bisa diganti 'on_process' jika alurnya langsung dikerjakan)
            // Jika Reject -> 'rejected'
            $newStatus = ($request->action === 'approve') ? 'approved' : 'rejected';

            // 4. Update Tabel Utama
            $wo->update([
                'status' => $newStatus,

                // Update kolom rejection_reason (yang dibuat di migration Langkah 1)
                'rejection_reason' => ($request->action === 'reject') ? $request->reason : null,

                // Mencatat siapa yang memproses
                'processed_by' => Auth::id(),
                'processed_by_name' => Auth::user()->name,
                'updated_at' => Carbon::now(),
            ]);

            // 5. Simpan ke History (Audit Trail)
            WorkOrderGaHistory::create([
                'work_order_id' => $wo->id,
                'user_id' => Auth::id(),
                'action' => ucfirst($newStatus), // 'Approved' atau 'Rejected'
                'description' => $request->action === 'reject'
                    ? "Permintaan ditolak. Alasan: " . $request->reason
                    : "Permintaan disetujui oleh GA.",
                'created_at' => Carbon::now(),
            ]);

            DB::commit();

            // 6. Redirect kembali dengan pesan sukses
            $message = ($request->action === 'approve')
                ? 'Work Order berhasil disetujui.'
                : 'Work Order berhasil ditolak.';

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal memproses tiket: ' . $e->getMessage());
        }
    }

    // --- REJECT OLEH GA ADMIN ---
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|min:5']);

        try {
            DB::beginTransaction();
            $ticket = WorkOrderGeneralAffair::findOrFail($id);

            $ticket->update(['status' => 'cancelled']);

            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => Auth::id(),
                'action'        => 'Rejected by GA',
                'description'   => 'Ditolak oleh GA Admin. Alasan: ' . $request->reason
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Tiket ditolak oleh GA.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // --- UPDATE STATUS PROGRESS (OLEH GA ADMIN) ---
    public function updateStatus(Request $request, $id)
    {
        // DEBUG DD SUDAH DIHAPUS. LANGSUNG EKSEKUSI.
        try {
            $ticket = WorkOrderGeneralAffair::findOrFail($id);

            $request->validate([
                'status'            => 'required',
                'processed_by_name' => 'required|string',
            ]);

            $dataToUpdate = [
                'status'            => $request->status,
                'processed_by_name' => $request->processed_by_name,
            ];

            // Update Dept jika diubah
            if ($request->filled('department')) {
                $dataToUpdate['department'] = $request->department;
            }

            // Jika Selesai (Completed) -> Wajib Foto
            if ($request->status === 'completed') {
                $request->validate(['completion_photo' => 'required|image|max:5120']);
                if ($request->hasFile('completion_photo')) {
                    $dataToUpdate['photo_completed_path'] = $request->file('completion_photo')->store('wo_ga_completed', 'public');
                    $dataToUpdate['completed_at'] = now();
                    $dataToUpdate['actual_completion_date'] = now(); // Isi actual date
                }
            }

            // Revisi Target Date
            if ($request->filled('target_date')) {
                $dataToUpdate['target_completion_date'] = $request->target_date;
            }

            $ticket->update($dataToUpdate);

            // History
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => Auth::id(),
                'action'        => 'Status Update',
                'description'   => 'Status: ' . $request->status . '. PIC: ' . $request->processed_by_name
            ]);

            return redirect()->back()->with('success', 'Progress tiket berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 4. EXPORT
    // =========================================================================

    public function export(Request $request)
    {
        // Jika user memilih checkbox (Selected IDs)
        if ($request->filled('selected_ids') && $request->selected_ids != '') {
            $idsRaw = is_array($request->selected_ids) ? end($request->selected_ids) : $request->selected_ids;
            $ids = explode(',', $idsRaw);

            $query = WorkOrderGeneralAffair::with('user')
                ->whereIn('id', $ids)
                ->latest();
        } else {
            // Jika tidak, export sesuai filter yang sedang aktif
            $query = $this->buildQuery($request);
            $query->with('user');
        }

        $data = $query->get();
        $filename = 'Laporan-GA-' . date('d-m-Y-H-i') . '.xlsx';

        return Excel::download(new WorkOrderExport($data), $filename);
    }
}
