<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// --- MODELS ---
use App\Models\User;
use App\Models\Engineering\Plant;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Http\Requests\GA\StoreWorkOrderRequest;
use App\Http\Requests\GA\ProcessTicketRequest;
use App\Http\Requests\GA\UpdateStatusRequest;
use App\Services\GeneralAffair\WorkOrderService;
use App\Services\GeneralAffair\DashboardService;

// --- EXPORT ---
use App\Exports\WorkOrderExport;
use Maatwebsite\Excel\Facades\Excel;

class GeneralAffairController extends Controller
{
    // Constructor injection 
    public function __construct(
        protected WorkOrderService $gaService, // PHP 8 Constructor Promotion (Otomatis define property)
        protected DashboardService $dashboardService
    ) {}



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

    // =========================================================================
    // 2. MAIN PAGES (INDEX & DASHBOARD)
    // =========================================================================

    public function index(Request $request)
    {
        $workOrders = $this->gaService->getWorkOrders(
            $request,
            Auth::user()
        );

        $stats = $this->gaService->getIndexStats(Auth::user());

        $plants = Plant::whereNotIn('name', ['QC', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH', 'RM', 'Plant F'])->get();
        $pageIds = $workOrders->pluck('id')->toArray();

        return view('Division.GeneralAffair.GeneralAffair', array_merge(
            [
                'workOrders' => $workOrders,
                'plants' => $plants,
                'pageIds' => $pageIds
            ],
            $stats
        ));
    }

    public function dashboard(Request $request)
    {
        $data = $this->dashboardService->getDashboardData($request);

        return view('Division.GeneralAffair.Dashboard', $data);
    }

    // =========================================================================
    // 3. CRUD ACTIONS (STORE, UPDATE, APPROVE, REJECT)
    // =========================================================================

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        try {
            // Kita panggil service. 
            // $request->validated() otomatis hanya mengambil data yg lolos rules()
            $result = $this->gaService->createWorkOrder(
                $request->validated(),
                $request->file('photo')
            );

            return redirect()->back()->with('success', $result['message']);
        } catch (\Exception $e) {
            // Logging error standar Laravel 11
            \Log::error('Gagal Store GA: ' . $e->getMessage());
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
                case 'Sales 1':
                    $specificDept = 'Sales 1';
                    break;
                case 'Sales 2':
                    $specificDept = 'Sales 2';
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
                'FA',
                'FH',
                'FO',
                'GA',
                'HC',
                'IT',
                'Low Voltage',
                'MT',
                'Marketing',
                'Medium Voltage',
                'PE',
                'Planning',
                'QR',
                'Sales 1',
                'Sales 2',
                'SC',
                'SS',
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

    public function processTicket(ProcessTicketRequest $request, $id)
    {
        try {
            $statusMsg = $this->gaService->processTicket(
                $id,
                $request->action,
                $request->reason
            );
            return redirect()->back()->with('success', "Tiket berhasil $statusMsg.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses tiket: ', $e->getMessage());
        }
    }



    public function approveByTechnical(Request $request, $id)
    {
        // Mapping: Jika request lama kirim 'decline', kita anggap 'reject'. Sisanya 'approve'.
        $action = ($request->action === 'decline') ? 'reject' : 'approve';

        try {
            // Panggil Service processTicket yang baru
            $statusMsg = $this->gaService->processTicket(
                $id,
                $action,
                $request->reason
            );
            return redirect()->back()->with('success', "Tiket berhasil diproses ($statusMsg).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // --- UPDATE STATUS PROGRESS (OLEH GA ADMIN) ---
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        try {
            $this->gaService->updateStatus(
                $id,
                $request->validated(),
                $request->file('completion_photo')
            );
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
        $user = Auth::user();
        $query = WorkOrderGeneralAffair::query();

        // =================================================================
        // 1. LOGIKA HAK AKSES (SAMA PERSIS DENGAN INDEX)
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
            'sales1.admin'     => ['Sales 1', 'sales 1'],
            'sales2.admin'     => ['Sales 2', 'sales 2'],
            'marketing.admin' => ['Marketing', 'marketing'],
        ];

        if ($user) {
            // A. GA ADMIN (Logika Khusus GA)
            if ($user->role === User::ROLE_GA_ADMIN || $user->role === 'admin_ga') {
                $query->where(function ($q) {
                    // Tampilkan tiket yang sudah diproses
                    $q->whereIn('status', ['pending', 'approved', 'in_progress', 'completed', 'OPEN']);

                    // Tampilkan tiket Waiting Approval HANYA jika tujuannya ke GA
                    $q->orWhere(function ($sub) {
                        $sub->where('status', 'waiting_approval')
                            ->whereIn('department', ['GA', 'General Affair']);
                    });
                });
            }
            // B. ADMIN TEKNIS
            elseif (array_key_exists($user->role, $roleMap)) {
                $allowedDepts = $roleMap[$user->role];
                $query->where(function ($q) use ($user, $allowedDepts) {
                    $q->whereIn('department', $allowedDepts)
                        ->orWhere('requester_id', $user->id);
                });
            }
            // C. USER BIASA
            else {
                $query->where('requester_id', $user->id);
            }
        }

        // =================================================================
        // 2. LOGIKA FILTER & PENCARIAN (SAMA PERSIS DENGAN INDEX)
        // =================================================================

        // A. Handle Checklist (Jika user mencentang beberapa baris saja)
        if ($request->filled('selected_ids')) {
            $ids = explode(',', $request->selected_ids);
            $query->whereIn('id', $ids);
        }
        // B. Jika tidak ada checklist, gunakan filter global
        else {
            // Search
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

            // Filter Tanggal
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereDate('created_at', '>=', $request->start_date)
                    ->whereDate('created_at', '<=', $request->end_date);
            }
        }

        // =================================================================
        // 3. SORTING & EKSEKUSI
        // =================================================================
        $query->orderBy('created_at', 'desc');

        // Kirim Query Builder ke Export Class (bukan ->get() agar hemat memori)
        return Excel::download(new WorkOrderExport($query), 'Laporan-GA-' . date('d-m-Y-H-i') . '.xlsx');
    }
}
