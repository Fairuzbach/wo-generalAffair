<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// --- IMPORT LIBRARY EXCEL ---
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WorkOrderExport;
// ----------------------------
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Engineering\Plant;

class GeneralAffairController extends Controller
{

    public function checkEmployee(Request $request)
    {
        $request->validate(['nik' => 'required|string']);

        // PERBAIKAN: Cari di tabel 'employees', bukan 'users'
        // Sesuaikan nama kolom jika beda (misal: 'employee_id' atau 'nik')
        $employee = DB::table('employees')->where('nik', $request->nik)->first();

        if ($employee) {
            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $employee->name, // Pastikan kolom 'name' ada di tabel employees
                    'division' => $employee->department ?? '-', // Pastikan kolom 'department' ada
                    'nik' => $employee->nik
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Data karyawan tidak ditemukan.'], 404);
    }

    // --- 1. HELPER QUERY (Untuk Filter) ---
    private function buildQuery(Request $request)
    {
        $query = WorkOrderGeneralAffair::query();
        $user = Auth::user();
        if ($user) {
            if ($user->role !== 'ga.admin') {
                $query->where('requester_id', $user->id);
            }
        }

        // Filter Search
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

        // Filter Status & Kategori
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('parameter')) {
            $query->where('parameter_permintaan', $request->parameter);
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

    // --- 2. HALAMAN UTAMA (INDEX) - YANG TADI HILANG ---
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. INITIALIZE QUERY
        $query = WorkOrderGeneralAffair::query();

        // ---------------------------------------------------------
        // A. LOGIKA HAK AKSES (ROLE SCOPE) - "Siapa boleh lihat apa"
        // ---------------------------------------------------------
        if ($user) {
            // 1. GA ADMIN: Lihat semua KECUALI yang masih waiting_spv
            if ($user->role === 'ga.admin') {
                $query->where('status', '!=', 'waiting_spv');
            }
            // 2. ENGINEER ADMIN: Lihat semua tiket Dept Engineering (Approved & Waiting)
            elseif ($user->role === 'eng.admin') {
                $query->where(function ($q) {
                    $q->where('requester_department', 'LIKE', '%Engineering%')
                        ->orWhereNull('requester_department');
                });
            }
            // 3. USER BIASA: Hanya lihat tiket sendiri
            else {
                $query->where('requester_id', $user->id);
            }
        }

        // ---------------------------------------------------------
        // B. LOGIKA FILTER CONTROL PANEL (Search, Status, Category)
        // ---------------------------------------------------------

        // 1. Search Global (Tiket, Nama, Deskripsi)
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        });

        // 2. Filter Status (Dropdown)
        $query->when($request->status, function ($q) use ($request) {
            if ($request->status !== 'all') { // Pastikan value 'all' tidak di-filter
                $q->where('status', $request->status);
            }
        });

        // 3. Filter Category (Bobot)
        $query->when($request->category, function ($q) use ($request) {
            if ($request->category !== 'all') {
                $q->where('category', $request->category);
            }
        });

        // 4. Filter Parameter (Jenis)
        $query->when($request->parameter, function ($q) use ($request) { // Sesuaikan nama input di blade (parameter atau parameter_permintaan)
            if ($request->parameter !== 'all') {
                $q->where('parameter_permintaan', $request->parameter);
            }
        });

        // 5. Filter Plant/Lokasi
        $query->when($request->plant_id, function ($q) use ($request) {
            if ($request->plant_id !== 'all') {
                $q->where('plant', $request->plant_id); // Atau plant_id tergantung kolom DB
            }
        });


        // ---------------------------------------------------------
        // C. EKSEKUSI DATA UTAMA
        // ---------------------------------------------------------
        $workOrders = $query->with(['user', 'histories.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $pageIds = $workOrders->pluck('id')->toArray();
        $plants = Plant::whereNotIn('name', ['QC', 'GA', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH'])->get();


        // ---------------------------------------------------------
        // D. LOGIKA COUNTER (STATISTIK)
        // ---------------------------------------------------------
        // Kita buat query baru khusus statistik agar angkanya tetap 
        // menunjukkan Total Global User tersebut (tidak terpengaruh filter table)

        $statsQuery = WorkOrderGeneralAffair::query();

        if ($user) {
            if ($user->role === 'ga.admin') {
                $statsQuery->where('status', '!=', 'waiting_spv');
            } elseif ($user->role === 'eng.admin') {
                $statsQuery->where(function ($q) {
                    $q->where('requester_department', 'LIKE', '%Engineering%')
                        ->orWhereNull('requester_department');
                });
            } else {
                $statsQuery->where('requester_id', $user->id);
            }
        }

        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('status', 'waiting_spv')->count();
        $countInProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted = (clone $statsQuery)->where('status', 'completed')->count();

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
    // Method Approve (Update Logika)
    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $ticket = WorkOrderGeneralAffair::findOrFail($id);
            $action = ''; // Inisialisasi variabel
            $desc = '';

            // SKENARIO 1: ENGINEER ADMIN APPROVE
            if ($user->role === 'eng.admin') {
                $ticket->update(['status' => 'pending']);
                $action = 'Approved by SPV';
                $desc = 'Disetujui Eng Admin, diteruskan ke GA (Status: Pending)';
            }
            // SKENARIO 2: GA ADMIN APPROVE (TERIMA TIKET)
            elseif ($user->role === 'ga.admin') {

                // Validasi Input Nama PIC
                $request->validate([
                    'processed_by_name' => 'required|string|max:255'
                ]);

                $ticket->update([
                    'status' => 'in_progress',
                    'processed_by_name' => $request->processed_by_name, // Simpan Nama PIC
                    'processed_at' => now() // Simpan waktu mulai
                ]);

                $action = 'Accepted by GA';
                $desc = 'Tiket diterima oleh GA Admin. PIC: ' . $request->processed_by_name;
            } else {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses approval.');
            }

            // Catat History (Dilakukan untuk kedua role)
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => $action,
                'description' => $desc
            ]);

            DB::commit();

            // --- [LOGIKA REDIRECT] ---

            // Jika GA Admin, kirim data tiket balik ke View agar Modal Edit terbuka Otomatis
            if ($user->role === 'ga.admin') {
                return redirect()->back()
                    ->with('success', 'Tiket diterima! Silakan lengkapi detail (Dept/Target/Foto) jika diperlukan.')
                    ->with('auto_edit_ticket', $ticket); // <--- INI KUNCINYA
            }

            // Jika Eng Admin, redirect biasa
            return redirect()->back()->with('success', 'Status tiket berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
    private function generateTicketNum()
    {
        $prefix = 'GA-' . date('Ymd');
        $lastTicket = \App\Models\GeneralAffair\WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')
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

    // Method Reject (Baru)
    public function reject(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $ticket = WorkOrderGeneralAffair::findOrFail($id);

            // [VALIDASI] Alasan Wajib Diisi
            $request->validate([
                'reason' => 'required|string|min:5'
            ]);

            // Update Status
            $ticket->update(['status' => 'cancelled']);

            // Catat History dengan Alasan
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'Rejected by GA',
                'description' => 'Tiket ditolak. Alasan: ' . $request->reason
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Tiket berhasil ditolak.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // --- 3. HALAMAN DASHBOARD (STATISTIK) ---
    public function dashboard(Request $request)
    {
        if (Auth::check() && Auth::user()->role !== 'ga.admin') {
            abort(403, 'Akses Ditolak. Dashboard hanya untuk Admin atau Tamu.');
        }

        // --- 1. QUERY UTAMA ---
        // Ambil Tiket (exclude cancelled)
        $query = WorkOrderGeneralAffair::where('status', '!=', 'cancelled');

        // Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        }

        // Clone query untuk statistik (agar filter tanggal tetap terbawa)
        $statsQuery = clone $query;

        // Ambil Data untuk List & Gantt
        // PENTING: Urutkan berdasarkan created_at ASC (kronologis) agar Gantt Chart rapi
        if (!$request->filled('start_date')) {
            $query->orderBy('created_at', 'asc')->take(100); // Limit 100 agar tidak terlalu berat jika tanpa filter
        } else {
            $query->orderBy('created_at', 'asc');
        }

        $workOrders = $query->get();

        // --- 2. HITUNG STATISTIK COUNTER ---
        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('status', 'pending')->count();
        $countInProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted = (clone $statsQuery)->where('status', 'completed')->count();

        // --- 3. [BARU] SIAPKAN DATA CHART (DETAIL & PHASE) ---

        // A. DATA MODE DETAIL (Per Tiket)
        $detailLabels = [];
        $detailData   = [];
        $detailColors = [];

        foreach ($workOrders as $wo) {
            // Label: [DEPT] No Tiket
            $deptCode = $wo->department ?? '-';
            $detailLabels[] = "[$deptCode] $wo->ticket_num";

            // Hitung Durasi (Dalam Hari)
            $start = Carbon::parse($wo->created_at);

            // Tentukan tanggal akhir
            if ($wo->status == 'completed' && $wo->actual_completion_date) {
                $end = Carbon::parse($wo->actual_completion_date);
            } elseif ($wo->target_completion_date) {
                $end = Carbon::parse($wo->target_completion_date);
            } else {
                $end = now(); // Jika belum selesai & belum ada target, pakai hari ini
            }

            // Validasi: End tidak boleh kurang dari Start
            if ($end < $start) $end = $start;

            // Hitung selisih hari (Minimal 1 hari agar bar terlihat)
            $diffDays = $start->diffInDays($end);
            $detailData[] = $diffDays < 1 ? 1 : $diffDays;

            // Warna berdasarkan Status
            if ($wo->status == 'completed') {
                $detailColors[] = '#10b981'; // Hijau (Completed)
            } elseif ($wo->status == 'delayed' || ($wo->status != 'completed' && $end < now())) {
                $detailColors[] = '#ef4444'; // Merah (Delayed/Overdue)
            } else {
                $detailColors[] = '#3b82f6'; // Biru (In Progress/Planned)
            }
        }

        // B. DATA MODE PHASE (Group by Department)

        $groupedDept = $workOrders->groupBy('department');
        $phaseLabels = [];
        $phaseData   = [];

        foreach ($groupedDept as $deptName => $tickets) {
            $phaseLabels[] = $deptName ?? 'Unassigned';
            $phaseData[]   = $tickets->count(); // Jumlah tiket sebagai 'beban kerja'
        }


        $chartDataDetail = [
            'labels' => $detailLabels,
            'data'   => $detailData,
            'colors' => $detailColors
        ];

        $chartDataPhase = [
            'labels' => $phaseLabels,
            'data'   => $phaseData,
            'colors' => '#eab308'
        ];

        // --- 4. CHART LAINNYA (LOKASI, DEPT, PARAMETER, BOBOT) ---
        // Helper function
        $getChartData = function ($col, $q) {
            return $q->selectRaw("$col as label, count(*) as total")
                ->whereNotNull($col)->groupBy($col)->orderByDesc('total')->get();
        };

        // Chart Lokasi
        $locData = $getChartData('plant', clone $statsQuery);
        $chartLocLabels = $locData->pluck('label')->toArray();
        $chartLocValues = $locData->pluck('total')->toArray();

        // Chart Dept (Pie Chart Statistik)
        $deptData = $getChartData('department', clone $statsQuery);
        $chartDeptLabels = $deptData->pluck('label')->toArray();
        $chartDeptValues = $deptData->pluck('total')->toArray();

        // Chart Parameter
        $paramData = $getChartData('parameter_permintaan', clone $statsQuery);
        $chartParamLabels = $paramData->pluck('label')->toArray();
        $chartParamValues = $paramData->pluck('total')->toArray();

        // Chart Bobot
        $bobotData = (clone $statsQuery)->selectRaw('category, count(*) as total')
            ->groupBy('category')->pluck('total', 'category')->toArray();

        $chartBobotLabels = ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'];
        $chartBobotValues = [
            $bobotData['BERAT'] ?? 0,
            $bobotData['SEDANG'] ?? 0,
            $bobotData['RINGAN'] ?? 0
        ];

        // --- 5. PERFORMANCES ---
        $filterMonth = $request->input('filter_month', date('Y-m'));
        $year = substr($filterMonth, 0, 4);
        $month = substr($filterMonth, 5, 2);

        $perfQuery = WorkOrderGeneralAffair::where('status', '!=', 'cancelled')
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('target_completion_date', $year)
                    ->whereMonth('target_completion_date', $month)
                    ->orWhere(function ($sub) use ($year, $month) {
                        $sub->whereNull('target_completion_date')
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month);
                    });
            });

        $perfTotal = $perfQuery->count();
        $perfCompleted = (clone $perfQuery)->where('status', 'completed')->count();
        $perfPercentage = $perfTotal > 0 ? round(($perfCompleted / $perfTotal) * 100) : 0;

        // --- 6. RETURN VIEW ---
        return view('Division.GeneralAffair.Dashboard', compact(
            'workOrders',
            // Status
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted',
            //Performance
            'perfTotal',
            'perfCompleted',
            'perfPercentage',
            //filtermonth
            'filterMonth',
            //chart
            'chartLocLabels',
            'chartLocValues',
            'chartDeptLabels',
            'chartDeptValues',
            'chartParamLabels',
            'chartParamValues',
            'chartBobotLabels',
            'chartBobotValues',
            'chartDataDetail',
            'chartDataPhase'
        ));
    }

    // --- 4. FORM CREATE ---
    public function create()
    {
        $plants = Plant::whereNotIn('name', ['QC', 'GA', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH'])->get();
        $workOrders = WorkOrderGeneralAffair::with('user')->latest()->paginate(10);
        return view('general-affair.index', compact('workOrders', 'plants'));
    }

    // --- 5. STORE DATA ---
    public function store(Request $request)
    {
        // 1. VALIDASI DATA MASUK
        // Pastikan nama field ini sama persis dengan name="" di HTML form Anda
        $request->validate([
            'requester_nik' => 'required',
            'plant_id'      => 'required', // Input form namanya plant_id
            'department'    => 'required', // Input form target dept
            'description'   => 'required',
            'category'      => 'required',
        ]);

        try {
            DB::beginTransaction();

            // 2. LOGIKA CARI DATA PELAPOR
            // Cari data di master employee berdasarkan NIK
            $employee = \App\Models\Employee::where('nik', $request->requester_nik)->first();

            // Tentukan Nama & Dept Pelapor (Prioritas: Master DB -> Input Form -> Default)
            $fixName = $employee?->name ?? $request->requester_name ?? 'Tanpa Nama';
            $fixDept = $employee?->department ?? $request->requester_department ?? '-';

            // 3. SIMPAN KE DATABASE
            WorkOrderGeneralAffair::create([
                // Generate Nomor Tiket
                'ticket_num' => $this->generateTicketNum(),

                // Data User Login
                'requester_id' => Auth::id(),

                // Data Pelapor (Hasil Logika Diatas)
                'requester_nik'        => $request->requester_nik,
                'requester_name'       => $fixName,
                'requester_department' => $fixDept,

                // Data Tiket (Mapping Input Form -> Kolom Database)
                'plant'                => $request->plant_id, // Form: plant_id -> DB: plant
                'department'           => $request->department, // Dept Tujuan (IT/GA/dll)
                'category'             => $request->category,
                'description'          => $request->description,
                'parameter_permintaan' => $request->parameter_permintaan,
                'status_permintaan'    => $request->status_permintaan ?? 'OPEN',
                'target_completion_date' => $request->target_completion_date,

                // Status Awal
                'status' => 'waiting_spv',

                // Upload Foto
                'photo_path' => $request->hasFile('photo')
                    ? $request->file('photo')->store('wo_ga', 'public')
                    : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Tiket berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            // Jika error, akan tampil dilayar agar ketahuan penyebabnya
            dd([
                'STATUS' => 'GAGAL MENYIMPAN',
                'ERROR' => $e->getMessage(),
                'LINE' => $e->getLine()
            ]);
        }
    }


    // --- 6. UPDATE STATUS ---
    public function updateStatus(Request $request, $id)
    {
        try {
            $ticket = WorkOrderGeneralAffair::findOrFail($id);

            // Validasi Dasar
            $request->validate([
                'status' => 'required',
                'processed_by_name' => 'required|string',
            ]);

            $dataToUpdate = [
                'status' => $request->status,
                'processed_by_name' => $request->processed_by_name, // Update nama PIC jika berubah
            ];

            // 1. Jika Ganti Department
            if ($request->filled('department')) {
                $dataToUpdate['department'] = $request->department;
            }

            // 2. Jika Status Completed (Wajib Foto)
            if ($request->status === 'completed') {
                $request->validate(['completion_photo' => 'required|image|max:5120']);
                if ($request->hasFile('completion_photo')) {
                    $dataToUpdate['photo_completed_path'] = $request->file('completion_photo')->store('wo_ga_completed', 'public');
                    $dataToUpdate['completed_at'] = now(); // Catat waktu selesai
                }
            }

            // 3. Jika Revisi Tanggal
            if ($request->filled('target_date')) {
                $dataToUpdate['target_completion_date'] = $request->target_date;
            }

            // Simpan
            $ticket->update($dataToUpdate);

            // Catat History
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'Status Update',
                'description' => 'Status diubah menjadi ' . $request->status . ' oleh ' . $request->processed_by_name
            ]);

            return redirect()->back()->with('success', 'Progress tiket berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // --- 7. EXPORT EXCEL ---
    public function export(Request $request)
    {
        // 1. LOGIKA QUERY
        if ($request->filled('selected_ids') && $request->selected_ids != '') {
            $idsRaw = is_array($request->selected_ids) ? end($request->selected_ids) : $request->selected_ids;
            $ids = explode(',', $idsRaw);

            $query = WorkOrderGeneralAffair::with('user')
                ->whereIn('id', $ids)
                ->latest();
        } else {
            // Gunakan logika filter dari index
            $query = $this->buildQuery($request);
            $query->with('user');
        }

        // 2. EKSEKUSI DATA
        $data = $query->get();

        // 3. GENERATE NAMA FILE
        $filename = 'Laporan-GA-' . date('d-m-Y-H-i') . '.xlsx';

        // 4. DOWNLOAD EXCEL
        return Excel::download(new WorkOrderExport($data), $filename);
    }
}
