<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail; // Import Facade Mail
use Carbon\Carbon;
// --- MODELS ---
use App\Models\User;
use App\Models\Employee; // Pastikan model Employee ada
use App\Models\Engineering\Plant;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Mail\WorkOrderNotification; // Import Mailable yang baru Anda buat

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

        // =================================================================
        // MAPPING HAK AKSES
        // =================================================================
        $roleMap = [
            'eng.admin' => ['Engineering', 'engineering', 'ENGINEERING', 'PE'],
            'fh.admin'  => ['Facility', 'FH', 'FACILITY'],
            'mt.admin'  => ['Maintenance', 'maintenance', 'MT'],
            'lv.admin'  => ['Low Voltage', 'LOW VOLTAGE', 'low voltage', 'LV', 'lv'],
            'mv.admin'  => ['Medium Voltage', 'medium voltage', 'MV', 'mv'],
            'qr.admin'  => ['QR', 'qr'],
            'sc.admin'  => ['SC', 'sc'],
            'fo.admin'  => ['FO', 'fo'],
            'ss.admin'  => ['SS', 'ss'],
            'fa.admin'  => ['FA', 'fa'],
            'it.admin'  => ['IT', 'it'],
            'hc.admin'  => ['HC', 'hc'],
            'sales.admin'     => ['Sales', 'sales'],
            'marketing.admin' => ['Marketing', 'marketing'], // Pastikan ini ada
        ];

        // =================================================================
        // A. LOGIKA HAK AKSES
        // =================================================================
        if ($user) {

            // 1. GA ADMIN 
            // PERBAIKAN: Hapus 'waiting_approval' agar GA tidak melihat tiket mentah
            if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
                $query->where(function ($q) {
                    // A. Tiket yang sudah diproses (Pending/Open/Completed) - Tampilkan Semua
                    $q->whereIn('status', ['pending', 'approved', 'in_progress', 'completed', 'OPEN']);

                    // B. Tiket BARU (Waiting Approval) - HANYA TAMPILKAN JIKA TUJUANNYA KE GA
                    // Agar Admin GA bisa Approve/Reject tiket yang masuk ke departemennya
                    $q->orWhere(function ($sub) {
                        $sub->where('status', 'waiting_approval')
                            ->whereIn('department', ['GA', 'General Affair']); // Sesuaikan nama dept GA di DB Anda
                    });
                });
            }

            // 2. ADMIN TEKNIS (Sesuai Dept Tujuan)
            elseif (array_key_exists($user->role, $roleMap)) {
                $allowedDepts = $roleMap[$user->role];
                $query->where(function ($q) use ($user, $allowedDepts) {
                    // Hanya lihat tiket yang TUJUANNYA ke departemen dia
                    $q->whereIn('department', $allowedDepts)
                        // ATAU tiket yang dia BUAT SENDIRI (history pribadi)
                        ->orWhere('requester_id', $user->id);
                });
            }

            // 3. USER BIASA
            else {
                $query->where('requester_id', $user->id);
            }
        }

        // =================================================================
        // B. FILTERING (Search & Dropdown)
        // =================================================================
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('ticket_num', 'LIKE', "%{$request->search}%")
                    ->orWhere('requester_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        });

        $query->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status));
        $query->when($request->category && $request->category !== 'all', fn($q) => $q->where('category', $request->category));
        $query->when($request->parameter && $request->parameter !== 'all', fn($q) => $q->where('parameter_permintaan', $request->parameter));
        $query->when($request->plant_id && $request->plant_id !== 'all', fn($q) => $q->where('plant', $request->plant_id));

        // =================================================================
        // C. GET DATA
        // =================================================================
        $workOrders = $query->with(['user', 'histories.user', 'plantInfo'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transformasi Data Approver
        $workOrders->getCollection()->transform(function ($ticket) {
            $ticket->approver_divisi = null;
            if ($ticket->processed_by_name) {
                $approver = \App\Models\User::where('name', $ticket->processed_by_name)->first();
                $ticket->approver_divisi = $approver ? $approver->divisi : null;
            }
            return $ticket;
        });

        $pageIds = $workOrders->pluck('id')->toArray();
        $plants = \App\Models\Engineering\Plant::whereNotIn('name', ['QC', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH'])->get();

        // =================================================================
        // D. STATISTIK (LOGIKA SINKRON)
        // =================================================================
        $statsQuery = WorkOrderGeneralAffair::query();

        if ($user) {
            if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
                // PERBAIKAN: Statistik GA juga tidak menghitung waiting_approval
                $statsQuery->whereIn('status', ['pending', 'approved', 'in_progress', 'completed', 'OPEN']);
            } elseif (array_key_exists($user->role, $roleMap)) {
                $allowedDepts = $roleMap[$user->role];
                $statsQuery->where(function ($q) use ($user, $allowedDepts) {
                    $q->whereIn('department', $allowedDepts)
                        ->orWhere('requester_id', $user->id);
                });
            } else {
                $statsQuery->where('requester_id', $user->id);
            }
        }

        $countTotal           = (clone $statsQuery)->count();
        $countPending         = (clone $statsQuery)->where('status', 'pending')->count();
        $countWaitingApproval = (clone $statsQuery)->where('status', 'waiting_approval')->count();
        $countInProgress      = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted       = (clone $statsQuery)->where('status', 'completed')->count();

        return view('Division.GeneralAffair.GeneralAffair', compact(
            'workOrders',
            'plants',
            'pageIds',
            'countTotal',
            'countPending',
            'countWaitingApproval',
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

        // Ambil Data List (Urutkan berdasarkan tanggal terbaru)
        if (!$request->filled('start_date')) {
            // Default: ambil 100 data terbaru jika tidak ada filter
            $query->orderBy('created_at', 'desc')->take(100);
        } else {
            // Jika ada filter: tampilkan semua, tapi urutkan terbaru dulu
            $query->orderBy('created_at', 'desc');
        }
        $workOrders = $query->get();

        // Counter Dashboard
        $countTotal      = (clone $statsQuery)->count();
        $countPending    = (clone $statsQuery)->where('status', 'pending')->count();
        $countInProgress = (clone $statsQuery)->where('status', 'in_progress')->count();
        $countCompleted  = (clone $statsQuery)->where('status', 'completed')->count();

        // --- CHART DATA PREPARATION ---

        // 1. Chart Detail (Timeline) - DIURUTKAN BERDASARKAN PRIORITAS
        $detailLabels = [];
        $detailData   = [];
        $detailColors = [];

        // Sortir: Critical (merah) > In Progress (biru) > Completed (hijau)
        $sortedOrders = $workOrders->sortBy(function ($wo) {
            if ($wo->status == 'completed') return 3;
            $end = $wo->target_completion_date ? Carbon::parse($wo->target_completion_date) : now();
            if ($end < now()) return 1; // Critical (delayed)
            return 2; // In Progress
        });

        foreach ($sortedOrders as $wo) {
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
            if ($wo->status == 'completed') {
                $detailColors[] = '#10b981'; // Hijau
            } elseif ($end < now()) {
                $detailColors[] = '#ef4444'; // Merah (Telat/Critical)
            } else {
                $detailColors[] = '#3b82f6'; // Biru (In Progress)
            }
        }

        // 2. Chart Phase (Beban Kerja per Dept)
        $groupedDept = $workOrders->groupBy('department');
        $phaseLabels = [];
        $phaseData   = [];
        $phaseColors = [];

        foreach ($groupedDept as $deptName => $tickets) {
            $phaseLabels[] = $deptName ?? 'Unassigned';
            $phaseData[]   = $tickets->count();
            $phaseColors[] = '#eab308'; // Yellow for all departments
        }

        $chartDataDetail = [
            'labels' => $detailLabels,
            'data' => $detailData,
            'colors' => $detailColors
        ];

        $chartDataPhase = [
            'labels' => $phaseLabels,
            'data' => $phaseData,
            'colors' => $phaseColors
        ];

        // 3. Chart Lokasi (dengan Join ke tabel plants)
        $locData = WorkOrderGeneralAffair::where('work_order_general_affairs.status', '!=', 'cancelled')
            ->join('plants', 'work_order_general_affairs.plant', '=', 'plants.id')
            ->selectRaw('plants.name as label, count(*) as total');

        // Terapkan filter tanggal jika ada
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $locData->whereDate('work_order_general_affairs.created_at', '>=', $request->start_date)
                ->whereDate('work_order_general_affairs.created_at', '<=', $request->end_date);
        }

        $locData = $locData->groupBy('plants.name')
            ->orderByDesc('total')
            ->get();

        // 4. Helper untuk Chart Sederhana
        $getChartData = function ($col, $q) {
            return $q->selectRaw("$col as label, count(*) as total")
                ->whereNotNull($col)
                ->groupBy($col)
                ->orderByDesc('total')
                ->get();
        };

        $deptData  = $getChartData('department', clone $statsQuery);
        $paramData = $getChartData('parameter_permintaan', clone $statsQuery);

        // 5. Bobot Chart
        $bobotData = (clone $statsQuery)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $chartBobotLabels = ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'];
        $chartBobotValues = [
            $bobotData['HIGH'] ?? $bobotData['BERAT'] ?? 0,
            $bobotData['MEDIUM'] ?? $bobotData['SEDANG'] ?? 0,
            $bobotData['LOW'] ?? $bobotData['RINGAN'] ?? 0
        ];

        // 6. Performance Calculation
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

        $perfTotal      = $perfQuery->count();
        $perfCompleted  = (clone $perfQuery)->where('status', 'completed')->count();
        $perfPercentage = $perfTotal > 0 ? round(($perfCompleted / $perfTotal) * 100) : 0;

        // 7. Prepare chart arrays untuk JS
        $chartLocLabels = $locData->pluck('label')->toArray();
        $chartLocValues = $locData->pluck('total')->toArray();

        $chartDeptLabels = $deptData->pluck('label')->toArray();
        $chartDeptValues = $deptData->pluck('total')->toArray();

        $chartParamLabels = $paramData->pluck('label')->toArray();
        $chartParamValues = $paramData->pluck('total')->toArray();

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
            'chartLocLabels',
            'chartLocValues',
            'chartDeptLabels',
            'chartDeptValues',
            'chartParamLabels',
            'chartParamValues',
            'locData',
            'deptData',
            'paramData'
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
            'department'    => 'required',
            'description'   => 'required',
            'category'      => 'required',
        ]);

        try {
            DB::beginTransaction();

            // 1. Cari Data Employee (Master Data)
            $employee = \App\Models\User::where('nik', $request->requester_nik)->first();

            // 2. Tentukan Data Nama & Dept Pelapor
            $fixName = $employee?->name ?? $request->requester_name ?? Auth::user()->name;
            $fixDept = $employee?->divisi ?? $request->requester_department ?? Auth::user()->divisi;

            // --- [LOGIKA BYPASS APPROVAL] ---
            $loggedInUser = Auth::user();
            $isAdminGA = $loggedInUser->divisi === 'General Affair' || $loggedInUser->role === 'admin_ga';

            if ($isAdminGA) {
                $statusAwal = 'approved';
                $pesanSukses = 'Permintaan Berhasil Dibuat (Auto-Approved by GA).';
            } else {
                $statusAwal = 'waiting_approval';
                $pesanSukses = 'Permintaan Berhasil Dibuat! Silahkan hubungi SPV/Manager Dept Anda untuk Approve report ini.';
            }
            // ---------------------------------------

            // 3. Simpan & TANGKAP VARIABELNYA ($wo)
            $wo = WorkOrderGeneralAffair::create([
                'ticket_num'           => $this->generateTicketNum(),
                'requester_id'         => Auth::id(),

                'requester_nik'        => $request->requester_nik,
                'requester_name'       => $fixName,
                'requester_department' => $fixDept,

                'plant'                => $request->plant_id,
                'department'           => $request->department, // Target Approval

                'category'             => $request->category,
                'description'          => $request->description,
                'parameter_permintaan' => $request->parameter_permintaan,
                'status_permintaan'    => 'OPEN',
                'target_completion_date' => $request->target_completion_date,

                'status'               => $statusAwal,

                'photo_path'           => $request->hasFile('photo')
                    ? $request->file('photo')->store('wo_ga', 'public')
                    : null,
            ]);

            // =========================================================================
            // 4. LOGIKA PENGIRIMAN EMAIL NOTIFIKASI
            // =========================================================================

            // A. Kirim Notifikasi ke PELAPOR (Tipe: 'created_info')
            // -------------------------------------------------------------------------
            $pelaporEmail = $employee?->email ?? Auth::user()->email;
            if ($pelaporEmail) {
                try {
                    \Mail::to($pelaporEmail)->send(new WorkOrderNotification($wo, 'created_info'));
                } catch (\Exception $e) {
                    \Log::error('Gagal kirim email Pelapor: ' . $e->getMessage());
                }
            }

            // B. Kirim Notifikasi ke APPROVER (MENGGUNAKAN MAPPING ROLE)
            // -------------------------------------------------------------------------
            if ($statusAwal === 'waiting_approval') {

                // 1. Definisikan Mapping Role (Sesuai Database Anda)
                $roleMap = [
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

                // 2. Cari Role yang cocok berdasarkan Department Tujuan ($request->department)
                $targetRole = null;
                $targetDept = $request->department;

                foreach ($roleMap as $role => $departments) {
                    // Cek apakah Dept Tujuan ada di dalam array departemen milik role ini
                    if (in_array($targetDept, $departments)) {
                        $targetRole = $role;
                        break; // Ketemu!
                    }
                }

                // Log Debugging agar mudah dilacak
                \Log::info("DEBUG EMAIL STORE: Dept '$targetDept' -> Target Role '$targetRole'");

                // 3. Ambil User dengan Role tersebut dan Kirim Email
                if ($targetRole) {
                    $approvers = \App\Models\User::where('role', $targetRole)->get();

                    if ($approvers->count() > 0) {
                        foreach ($approvers as $approver) {
                            if ($approver->email) {
                                try {
                                    \Mail::to($approver->email)->send(new WorkOrderNotification($wo, 'need_approval'));
                                    \Log::info("DEBUG EMAIL STORE: Terkirim ke Approver " . $approver->email);
                                } catch (\Exception $e) {
                                    \Log::error('DEBUG EMAIL STORE ERROR: ' . $e->getMessage());
                                }
                            }
                        }
                    } else {
                        \Log::warning("DEBUG EMAIL STORE: Role '$targetRole' ditemukan mappingnya, tapi TIDAK ADA USER di database dengan role itu.");
                    }
                } else {
                    // Fallback: Jika departemen tidak ada di mapping, coba cari Manager umum
                    \Log::warning("DEBUG EMAIL STORE: Mapping tidak ketemu. Mencoba fallback ke pencarian divisi manual.");

                    $approvers = \App\Models\User::where('divisi', $targetDept)
                        ->whereIn('role', ['manager', 'spv', 'supervisor', 'dept_head'])
                        ->get();

                    foreach ($approvers as $approver) {
                        if ($approver->email) \Mail::to($approver->email)->send(new WorkOrderNotification($wo, 'need_approval'));
                    }
                }
            }
            // =========================================================================

            DB::commit();
            return redirect()->back()->with('success', $pesanSukses);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function getDepartmentsByPlant($plant_id)
    {
        try {
            // 1. Cari Data Plant
            $plant = Plant::find($plant_id);

            // Jika plant tidak ditemukan, kembalikan array kosong (jangan error)
            if (!$plant) {
                return response()->json([]);
            }

            // 2. Bersihkan nama plant (hapus spasi depan/belakang)
            $name = trim($plant->name);

            // 3. Tentukan Departemen Spesifik (Berdasarkan Logic JS Anda sebelumnya)
            $specificDept = '';

            // Logic Mapping (Switch Case)
            switch ($name) {
                // Group Low Voltage
                case 'Plant A':
                case 'Plant C':
                case 'Plant F':
                case 'MC Cable':
                case 'Autowire':
                    $specificDept = 'Low Voltage';
                    break;

                // Group Medium Voltage
                case 'Plant B':
                case 'Plant D':
                    $specificDept = 'Medium Voltage';
                    break;

                // Group Fiber Optic
                case 'Plant E':
                case 'FO':
                    $specificDept = 'FO';
                    break;

                // Group Support Components (SC) / RM
                case 'RM 1':
                case 'RM 2':
                case 'RM 3':
                case 'RM 5':
                case 'RM Office':
                    $specificDept = 'SC';
                    break;

                // Group Quality (QR)
                case 'QC FO':
                case 'QC LAB':
                case 'QC LV':
                case 'QC MV':
                case 'QR':
                    $specificDept = 'QR';
                    break;

                // Group Lainnya
                case 'Konstruksi':
                    $specificDept = 'FH';
                    break;
                case 'Workshop Electric':
                case 'MT':
                    $specificDept = 'MT';
                    break;
                case 'Gudang Jadi':
                case 'SS':
                    $specificDept = 'SS';
                    break;
                case 'Plant Tools':
                case 'PE':
                    $specificDept = 'PE';
                    break;
                case 'Planning':
                    $specificDept = 'Planning';
                    break;
                case 'IT':
                    $specificDept = 'IT';
                    break;
                case 'GA':
                    $specificDept = 'GA';
                    break;
                case 'FA':
                    $specificDept = 'FA';
                    break;
                case 'Marketing':
                    $specificDept = 'Marketing';
                    break;
                case 'HC':
                    $specificDept = 'HC';
                    break;
                case 'Sales':
                    $specificDept = 'Sales';
                    break;

                default:
                    $specificDept = 'General'; // Default jika nama plant tidak dikenali
                    break;
            }

            // 4. Buat Daftar Akhir Departemen
            // Kita gabungkan departemen spesifik tadi dengan departemen umum (GA, IT, dll)
            // agar user tetap punya pilihan departemen pendukung.

            $departments = [
                $specificDept, // Dept Utama (Hasil mapping di atas)
                'GA',
                'IT',
                'HC',
                'Safety',
                'Planning',
                'Maintenance'
            ];

            // Hapus duplikat (misal specificDept = 'GA', maka 'GA' jangan muncul 2x)
            $departments = array_unique($departments);

            // Re-index array supaya rapi di JSON (0, 1, 2...)
            $departments = array_values($departments);

            return response()->json($departments);
        } catch (\Exception $e) {
            // Log error untuk developer (cek di storage/logs/laravel.log)
            \Log::error('Error getDepartmentsByPlant: ' . $e->getMessage());

            // Return array kosong atau pesan error format JSON (jangan 500 crash)
            return response()->json(['General'], 200);
        }
    }

    public function approveByTechnical(Request $request, $id)
    {
        try {
            $ticket = WorkOrderGeneralAffair::findOrFail($id);
            $user = Auth::user();

            // 1. AMBIL DEPT TUJUAN
            $targetDept = $ticket->department;

            $isAuthorized = false;

            // --- A. LOGIKA UTAMA: Manager/SPV dari Dept Tujuan ---
            if (in_array($user->role, ['manager', 'spv', 'supervisor', 'dept_head']) && $user->divisi == $targetDept) {
                $isAuthorized = true;
            }

            // --- B. LOGIKA ADMIN GA ---
            if (($user->role == 'admin_ga' || $user->divisi == 'General Affair') && in_array($targetDept, ['GA', 'General Affair'])) {
                $isAuthorized = true;
            }

            // --- C. LOGIKA ADMIN TEKNIS ---
            if ($user->role == 'eng.admin' && (
                str_contains($targetDept, 'Engineering') || str_contains($targetDept, 'SC') ||
                str_contains($targetDept, 'ENG') || in_array($targetDept, ['Low Voltage', 'Medium Voltage', 'PE', 'FO', 'QR', 'SS'])
            )) {
                $isAuthorized = true;
            }
            if ($user->role == 'mt.admin' && (str_contains($targetDept, 'Maintenance') || $targetDept == 'MT')) {
                $isAuthorized = true;
            }
            if ($user->role == 'fh.admin' && (str_contains($targetDept, 'Facility') || $targetDept == 'FH')) {
                $isAuthorized = true;
            }

            // --- EKSEKUSI ---
            if (!$isAuthorized) {
                return back()->with('error', 'Anda tidak memiliki otoritas untuk menyetujui tiket Departemen: ' . $targetDept);
            }

            // =========================================================
            // SKENARIO 1: JIKA DITOLAK (DECLINE)
            // =========================================================
            if ($request->action === 'decline') {
                // 1. Update Database
                $ticket->update([
                    'status' => 'rejected',
                    'rejection_reason' => $request->reason
                ]);

                // 2. Kirim Email ke Pelapor
                $pelapor = \App\Models\User::find($ticket->requester_id);
                if ($pelapor && $pelapor->email) {
                    try {
                        \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($ticket, 'rejected'));
                    } catch (\Exception $e) {
                        \Log::error('Gagal kirim email reject: ' . $e->getMessage());
                    }
                }

                // 3. Catat History
                \App\Models\GeneralAffair\WorkOrderGaHistory::create([
                    'work_order_id' => $ticket->id,
                    'user_id'       => $user->id,
                    'action'        => 'Rejected',
                    'description'   => 'Tiket ditolak oleh ' . $user->name . '. Alasan: ' . $request->reason
                ]);

                return back()->with('success', 'Tiket berhasil ditolak.');
            }

            // =========================================================
            // SKENARIO 2: JIKA DISETUJUI (APPROVE)
            // =========================================================

            // 1. Update Database (PENTING!)
            $ticket->update(['status' => 'pending']);

            // 2. Kirim Email ke GA Admin (Tipe: 'ga_new')
            try {
                // Pastikan email GA Admin benar
                \Mail::to('ga_admin@company.com')->send(new \App\Mail\WorkOrderNotification($ticket, 'ga_new'));
                \Log::info('DEBUG: Email notifikasi ke GA Admin terkirim.');
            } catch (\Exception $e) {
                \Log::error('Gagal kirim email ke GA: ' . $e->getMessage());
            }

            // 3. Catat History
            \App\Models\GeneralAffair\WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => $user->id,
                'action'        => 'Approved',
                'description'   => 'Tiket disetujui oleh Manager/Admin: ' . $user->name
            ]);

            return back()->with('success', 'Tiket disetujui dan diteruskan ke GA.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function approveByGA(Request $request, $id)
    {
        dd("BERHASIL MASUK KE FUNGSI INI"); // <--- Tambahkan ini
        // 1. Validasi Role GA
        if (Auth::user()->role !== 'ga.admin') {
            abort(403, 'Hanya GA Admin yang bisa akses.');
        }

        $ticket = WorkOrderGeneralAffair::findOrFail($id);

        // 2. Decline
        if ($request->action === 'decline') {
            $ticket->update(['status' => 'rejected_by_ga', 'note' => $request->reason]);

            // --- EMAIL REJECT ---
            $pelapor = \App\Models\User::find($ticket->requester_id);
            if ($pelapor && $pelapor->email) {
                try {
                    \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($ticket, 'rejected'));
                } catch (\Exception $e) {
                    \Log::error('Email Reject Gagal: ' . $e->getMessage());
                }
            }
            return back()->with('error', 'Tiket ditolak GA.');
        }

        // 3. Approve -> On Process
        $ticket->update([
            'status' => 'on_process',
            'department'             => $request->department,
            'target_completion_date' => $request->target_completion_date,
            'approved_ga_by' => Auth::id(),
            'approved_ga_at' => now(),
        ]);

        // ====================================================================
        // 4. LOGIKA EMAIL DENGAN DEBUGGING
        // ====================================================================

        // Tulis ke log bahwa proses update DB selesai
        \Log::info('DEBUG EMAIL: Update DB Berhasil. Mencari pelapor ID: ' . $ticket->requester_id);

        $pelapor = \App\Models\User::find($ticket->requester_id);

        if ($pelapor) {
            // Cek apakah pelapor punya email
            \Log::info('DEBUG EMAIL: Pelapor ditemukan -> ' . $pelapor->name . ' (' . $pelapor->email . ')');

            if (!empty($pelapor->email)) {
                try {
                    \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($ticket, 'approved'));
                    \Log::info('DEBUG EMAIL: Fungsi Mail::send telah dieksekusi.');
                } catch (\Exception $e) {
                    \Log::error('DEBUG EMAIL: Gagal kirim email (Exception): ' . $e->getMessage());
                }
            } else {
                \Log::warning('DEBUG EMAIL: Field email pelapor KOSONG.');
            }
        } else {
            \Log::error('DEBUG EMAIL: Data User Pelapor TIDAK DITEMUKAN di database.');
        }
        // ====================================================================

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
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject',
        ]);

        try {
            DB::beginTransaction();

            // 2. Ambil Data Work Order
            $wo = WorkOrderGeneralAffair::findOrFail($id);

            // 3. Tentukan Status Baru
            $newStatus = ($request->action === 'approve') ? 'in_progress' : 'rejected';

            // 4. Update Tabel Utama
            $wo->update([
                'status' => $newStatus,
                'rejection_reason' => ($request->action === 'reject') ? $request->reason : null,
                'processed_by' => Auth::id(),
                'processed_by_name' => Auth::user()->name,
                'updated_at' => Carbon::now(),
            ]);

            // 5. Simpan ke History (Audit Trail)
            WorkOrderGaHistory::create([
                'work_order_id' => $wo->id,
                'user_id' => Auth::id(),
                'action' => ucfirst($newStatus),
                'description' => $request->action === 'reject'
                    ? "Permintaan ditolak. Alasan: " . $request->reason
                    : "Permintaan disetujui oleh GA dan sedang dikerjakan.",
                'created_at' => Carbon::now(),
            ]);

            // =================================================================
            // 6. [NEW] EMAIL NOTIFIKASI KE PELAPOR
            // =================================================================

            // Debug Log: Mulai proses email
            \Log::info('DEBUG EMAIL GA PROCESS: Mulai mencari pelapor ID ' . $wo->requester_id);

            $pelapor = \App\Models\User::find($wo->requester_id);

            if ($pelapor && $pelapor->email) {
                try {
                    if ($request->action === 'approve') {
                        // Kirim notifikasi APPROVED (Bahwa tiket sudah diproses/in_progress)
                        \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($wo, 'approved'));
                        \Log::info('DEBUG EMAIL GA PROCESS: Email Approved terkirim ke ' . $pelapor->email);
                    } elseif ($request->action === 'reject') {
                        // Kirim notifikasi REJECTED
                        \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($wo, 'rejected'));
                        \Log::info('DEBUG EMAIL GA PROCESS: Email Rejected terkirim ke ' . $pelapor->email);
                    }
                } catch (\Exception $e) {
                    // Catch error agar transaksi database TIDAK rollback cuma gara-gara email gagal
                    \Log::error('DEBUG EMAIL GA PROCESS ERROR: ' . $e->getMessage());
                }
            } else {
                \Log::warning('DEBUG EMAIL GA PROCESS: Pelapor tidak ditemukan atau tidak punya email.');
            }
            // =================================================================

            DB::commit();

            // 7. Redirect kembali dengan pesan sukses
            $message = ($request->action === 'approve')
                ? 'Work Order berhasil disetujui dan diproses.'
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
        try {
            $ticket = WorkOrderGeneralAffair::findOrFail($id);

            // 1. Validasi Input
            // Kita buat validation rules dinamis. 
            // Kalau status 'cancelled', 'cancellation_note' wajib diisi.
            $rules = [
                'status'            => 'required',
                'processed_by_name' => 'required|string',
                'category'          => 'required',
            ];

            if ($request->status === 'cancelled') {
                $rules['cancellation_note'] = 'required|string|min:5';
            }

            $request->validate($rules);

            // 2. Siapkan Array Data Dasar
            $dataToUpdate = [
                'status'            => $request->status,
                'processed_by_name' => $request->processed_by_name,
                'category'          => $request->category,
            ];

            // --- SKENARIO 1: ON PROGRESS ---
            if ($request->status === 'on_progress') {
                $dataToUpdate['actual_start_date'] = $request->start_date ?? $ticket->actual_start_date ?? now();
            }

            // --- SKENARIO 2: COMPLETED (SELESAI) ---
            if ($request->status === 'completed') {
                // Upload Foto Selesai (Jika ada)
                if ($request->hasFile('completion_photo')) {
                    $dataToUpdate['photo_completed_path'] = $request->file('completion_photo')->store('wo_ga_completed', 'public');
                }

                // Simpan Tanggal & Catatan
                $dataToUpdate['actual_completion_date'] = $request->actual_completion_date ?? now();
                $dataToUpdate['completion_note'] = $request->completion_note;

                // Bersihkan data cancel (jaga-jaga)
                $dataToUpdate['cancellation_note'] = null;
            }

            // --- SKENARIO 3: CANCELLED (DIBATALKAN) ---
            if ($request->status === 'cancelled') {
                // Simpan Alasan Pembatalan
                $dataToUpdate['cancellation_note'] = $request->cancellation_note;

                // HAPUS data penyelesaian (Karena batal, berarti tidak selesai)
                $dataToUpdate['actual_completion_date'] = null;
                $dataToUpdate['completion_note'] = null;
                $dataToUpdate['photo_completed_path'] = null;
            }

            // --- UPDATE DEPT & TARGET DATE (Jika ada perubahan) ---
            if ($request->filled('department')) {
                $dataToUpdate['department'] = $request->department;
            }
            if ($request->filled('target_date')) {
                $dataToUpdate['target_completion_date'] = $request->target_date;
            }

            // 3. Eksekusi Update ke Database
            $ticket->update($dataToUpdate);

            // 4. Kirim Email Notifikasi
            $pelapor = \App\Models\User::find($ticket->requester_id);
            if ($pelapor && $pelapor->email) {
                try {
                    if ($request->status === 'completed') {
                        \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($ticket, 'completed'));
                    } elseif ($request->status === 'cancelled') {
                        // Gunakan tipe 'rejected' untuk notifikasi pembatalan
                        \Mail::to($pelapor->email)->send(new \App\Mail\WorkOrderNotification($ticket, 'rejected'));
                    }
                } catch (\Exception $e) {
                    \Log::error('Gagal kirim email update status: ' . $e->getMessage());
                }
            }

            // 5. Simpan History
            \App\Models\GeneralAffair\WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => \Auth::id(),
                'action'        => 'Status Update',
                'description'   => "Status diubah menjadi: " . ucfirst($request->status)
            ]);

            return redirect()->back()->with('success', 'Status berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi Kesalahan: ' . $e->getMessage());
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
