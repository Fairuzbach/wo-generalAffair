<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// --- IMPORT LIBRARY EXCEL ---
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WorkOrderExport;
// ----------------------------
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Engineering\Plant;

class GeneralAffairController extends Controller
{
    // --- 1. HELPER QUERY (Untuk Filter) ---
    private function buildQuery(Request $request)
    {
        $query = WorkOrderGeneralAffair::query();
        $user = Auth::user();

        // Jika bukan Admin GA, hanya lihat tiket sendiri
        if ($user->role !== 'ga.admin') {
            $query->where('requester_id', $user->id);
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
        $query = $this->buildQuery($request);
        $workOrders = $query->with(['user', 'histories.user'])->paginate(10)->withQueryString();
        $pageIds = $workOrders->pluck('id')->toArray();
        $plants = Plant::whereNotIn('name', ['QC', 'GA', 'FO', 'PE', 'QR', 'SS', 'MT', 'FH'])->get();

        // Counter Sederhana untuk Index
        $user = Auth::user();
        $statsQuery = WorkOrderGeneralAffair::query();
        if ($user->role !== 'ga.admin') {
            $statsQuery->where('requester_id', $user->id);
        }

        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('status', 'pending')->count();
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

    // --- 3. HALAMAN DASHBOARD (STATISTIK) ---
    public function dashboard(Request $request)
    {
        if (Auth::user()->role !== 'ga.admin') {
            abort(403, 'Akses Ditolak.');
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
        // Kita hitung jumlah tiket per departemen
        $groupedDept = $workOrders->groupBy('department');
        $phaseLabels = [];
        $phaseData   = [];

        foreach ($groupedDept as $deptName => $tickets) {
            $phaseLabels[] = $deptName ?? 'Unassigned';
            $phaseData[]   = $tickets->count(); // Jumlah tiket sebagai 'beban kerja'
        }

        // Bungkus ke Array Asosiatif untuk dikirim ke Blade/JS
        $chartDataDetail = [
            'labels' => $detailLabels,
            'data'   => $detailData,
            'colors' => $detailColors
        ];

        $chartDataPhase = [
            'labels' => $phaseLabels,
            'data'   => $phaseData,
            'colors' => '#eab308' // Kuning (Satu warna solid untuk grouping)
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
            // Stats
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted',
            // Performance
            'perfTotal',
            'perfCompleted',
            'perfPercentage',
            'filterMonth',
            // Charts Statistik
            'chartLocLabels',
            'chartLocValues',
            'chartDeptLabels',
            'chartDeptValues',
            'chartParamLabels',
            'chartParamValues',
            'chartBobotLabels',
            'chartBobotValues',
            // DATA GANTT BARU (Detail & Phase)
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
        $request->validate([
            // Validasi Input Nama Baru
            'manual_requester_name' => 'required|string|max:255',

            'plant_id' => 'required',
            'department' => 'required',
            'description' => 'required',
            'category' => 'required',
            'parameter_permintaan' => 'required',
            'photo' => 'nullable|image|max:5120'
        ]);

        $plantData = Plant::find($request->plant_id);
        $plantName = $plantData ? $plantData->name : 'Unknown Plant';

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('wo_ga', 'public');
        }

        // Generate Nomor Tiket
        $dateCode = date('Ymd');
        $prefix = 'woGA-' . $dateCode . '-';
        $lastTicket = WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $newSequence  = $lastTicket ? ((int) substr($lastTicket->ticket_num, -3) + 1) : 1;
        $ticketNum = $prefix . sprintf('%03d', $newSequence);

        // --- SIMPAN DATA ---
        $ticket = WorkOrderGeneralAffair::create([
            'ticket_num' => $ticketNum,

            // 1. Requester ID tetap ambil dari akun yang login (untuk trace akun mana yang pakai)
            'requester_id' => Auth::id(),

            // 2. Requester Name DIUBAH: Ambil dari Input Manual
            'requester_name' => $request->manual_requester_name,

            'plant' => $plantName,
            'department' => $request->department,
            'description' => $request->description,
            'category' => $request->category,
            'parameter_permintaan' => $request->parameter_permintaan,
            'status' => 'pending',
            'status_permintaan' => $request->status_permintaan,
            'photo_path' => $photoPath,
        ]);

        WorkOrderGaHistory::create([
            'work_order_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'Created',
            // Update deskripsi history agar mencatat nama inputan juga
            'description' => 'Tiket dibuat atas nama: ' . $request->manual_requester_name
        ]);

        return redirect()->route('ga.index')->with('success', 'Permintaan berhasil dibuat!');
    }

    // --- 6. UPDATE STATUS ---
    public function updateStatus(Request $request, $id)
    {
        if (!in_array(auth()->user()->role, ['ga.admin'])) {
            abort(403, 'Anda tidak memiliki akses.');
        }
        $user = auth()->user();
        $ticket = WorkOrderGeneralAffair::findOrFail($id);
        $oldStatus = $ticket->status;

        $request->validate([
            'admin_name' => 'required|string|max:255'
        ]);

        if ($ticket->status == 'pending') {
            if ($request->action == 'decline') {
                $ticket->status = 'cancelled';
                $ticket->processed_by = $user->id;
                $ticket->processed_by_name = $request->admin_name;
                $ticket->save();

                WorkOrderGaHistory::create([
                    'work_order_id' => $ticket->id,
                    'user_id' => $user->id,
                    'action' => 'Declined',
                    'description' => 'Permintaan ditolak oleh ' . $request->admin_name . '.',
                ]);

                return redirect()->route('ga.index')->with('error', 'Permintaan telah di tolak.');
            }
            if ($request->action == 'accept') {
                $request->validate([
                    'category' => 'required',
                    'target_date' => 'required|date',
                ]);
                $ticket->status = 'in_progress';
                $ticket->category = $request->category;
                $ticket->target_completion_date = $request->target_date;
                $ticket->processed_by = $user->id;
                $ticket->processed_by_name = $request->admin_name;
                $ticket->save();

                WorkOrderGaHistory::create([
                    'work_order_id' => $ticket->id,
                    'user_id' => $user->id,
                    'action' => 'Accepted',
                    'description' => "Permintaan diterima. Target: {$request->target_date}. Kategori: {$request->category}.",
                ]);
                return redirect()->route('ga.index')->with('success', 'Permintaan berhasil diterima dan akan di proses.');
            }
        }

        $request->validate([
            'status' => 'required',
            'completion_photo' => 'nullable|image|max:5120'
        ]);

        $ticket->status = $request->status;

        if ($request->filled('department')) {
            $ticket->department = $request->department;
        }

        if ($request->status === 'completed') {
            $ticket->actual_completion_date = now()->toDateString();
            if ($request->hasFile('completion_photo')) {
                $photoPath = $request->file('completion_photo')->store('wo_ga_completed', 'public');
                $ticket->photo_completed_path = $photoPath;
            }
        } elseif ($request->filled('target_date')) {
            $ticket->target_completion_date = $request->target_date;
        }

        $ticket->processed_by = $user->id;
        $ticket->processed_by_name = $request->admin_name;
        $ticket->save();

        WorkOrderGaHistory::create([
            'work_order_id' => $ticket->id,
            'user_id' => $user->id,
            'action' => 'Status Updated',
            'description' => 'Status diubah dari ' . $oldStatus . ' menjadi ' . $request->status . '.',
        ]);

        return redirect()->route('ga.index')->with('success', 'Status tiket berhasil diperbarui!');
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
