<?php

namespace App\Services\GeneralAffair;

use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData($request)
    {
        // 1. Base Query
        $query = WorkOrderGeneralAffair::query()
            ->whereIn('status', ['in_progress', 'completed', 'approved']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        }

        // Ambil semua data sekaligus (Eager Loading) agar hemat query
        $allTickets = $query->with(['user', 'plantInfo'])->orderBy('created_at', 'desc')->get();

        // Clone query dasar untuk statistik counter global (jika diperlukan terpisah)
        // Namun sebenarnya bisa hitung langsung dari collection $allTickets

        // 2. Prepare Chart Data (Gantt Chart Timeline)
        $chartData = $this->prepareGanttChart($allTickets);

        // 3. Grouping Stats (Loc, Dept, Param, Bobot/Category)
        $groupedStats = $this->prepareGroupedStats($allTickets);

        // 4. Performance Stats (Berdasarkan Target Date vs Bulan Filter)
        $perfStats = $this->calculatePerformance($request->input('filter_month', date('Y-m')));

        return array_merge([
            'workOrders'      => $allTickets,
            'countTotal'      => $allTickets->count(),
            'countInProgress' => $allTickets->where('status', 'in_progress')->count(),
            'countCompleted'  => $allTickets->where('status', 'completed')->count(),
            // Pending dihitung terpisah karena tidak masuk filter status di atas
            'countPending'    => WorkOrderGeneralAffair::whereIn('status', ['pending', 'waiting_ga_approval'])->count(),
            'filterMonth'     => $request->input('filter_month', date('Y-m')),
        ], $chartData, $groupedStats, $perfStats);
    }

    private function prepareGanttChart($tickets)
    {
        // Logic sorting: Completed bawah, No Target tengah, Critical atas [cite: 67-68]
        $sorted = $tickets->sortBy(function ($wo) {
            if ($wo->status == 'completed') return 3;
            if (!$wo->target_completion_date) return 2.5;
            return Carbon::parse($wo->target_completion_date) < now() ? 1 : 2;
        });

        $labels = [];
        $data = [];
        $colors = [];
        $metadata = [];

        foreach ($sorted as $wo) {
            $labels[] = "[$wo->department] $wo->ticket_num";
            $start = Carbon::parse($wo->created_at);
            $hasTarget = !is_null($wo->target_completion_date);
            $isOverdue = false;

            // Logic Warna & End Date [cite: 72-78]
            if ($wo->status == 'completed') {
                $end = $wo->actual_completion_date ? Carbon::parse($wo->actual_completion_date) : ($hasTarget ? Carbon::parse($wo->target_completion_date) : now());
                $color = '#10b981'; // Hijau
            } elseif ($hasTarget) {
                $end = Carbon::parse($wo->target_completion_date);
                $isOverdue = $end < now();
                $color = $isOverdue ? '#ef4444' : '#3b82f6'; // Merah (Critical) / Biru
            } else {
                $end = now();
                $color = '#3b82f6'; // Biru Default
            }

            // Pastikan end date tidak sebelum start date
            if ($end < $start) $end = $start;

            $data[] = max(1, $start->diffInDays($end));
            $colors[] = $color;

            // Metadata lengkap untuk popup di View [cite: 83-85]
            $metadata[] = [
                'ticket_num'             => $wo->ticket_num,
                'status'                 => $wo->status,
                'status_type'            => $wo->status,
                'has_target'             => $hasTarget,
                'target_completion_date' => $wo->target_completion_date,
                'actual_completion_date' => $wo->actual_completion_date,
                'is_overdue'             => $isOverdue,
                'department'             => $wo->department,
                'created_at'             => $wo->created_at->format('Y-m-d'),
            ];
        }

        return [
            'chartDataDetail' => [
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors,
                'metadata' => $metadata
            ]
        ];
    }

    private function prepareGroupedStats($tickets)
    {
        // Helper function untuk format data tabel (Label + Total)
        $formatForTable = function ($grouped) {
            return $grouped->map(fn($list, $key) => (object)['label' => $key, 'total' => $list->count()])
                ->sortByDesc('total')->values();
        };

        // 1. Chart Phase (Beban Kerja per Dept) [cite: 86-90]
        $deptGroup = $tickets->groupBy(fn($i) => $i->department ?? 'Unassigned');
        $chartDataPhase = [
            'labels' => $deptGroup->keys()->toArray(),
            'data'   => $deptGroup->map->count()->values()->toArray(),
            'colors' => array_fill(0, $deptGroup->count(), '#eab308')
        ];

        // 2. Chart Lokasi (Plant) [cite: 91-92]
        $locGroup = $tickets->groupBy(fn($i) => $i->plantInfo->name ?? 'Unknown');
        $locData = $formatForTable($locGroup);

        // 3. Chart Department (Tabel Statistik) [cite: 92]
        // (Sama dengan Phase tapi format beda untuk view tabel)
        $deptData = $formatForTable($deptGroup);

        // 4. Chart Parameter [cite: 93]
        $paramGroup = $tickets->groupBy(fn($i) => $i->parameter_permintaan ?? 'Lainnya');
        $paramData = $formatForTable($paramGroup);

        // 5. Chart Bobot (Category) [cite: 94-98]
        $catGroup = $tickets->groupBy('category')->map->count();
        $chartBobotValues = [
            $catGroup['HIGH'] ?? $catGroup['BERAT'] ?? 0,
            $catGroup['MEDIUM'] ?? $catGroup['SEDANG'] ?? 0,
            $catGroup['LOW'] ?? $catGroup['RINGAN'] ?? 0
        ];

        return [
            'chartDataPhase'   => $chartDataPhase,

            // Data Tabel Lengkap (Object Collection)
            'locData'          => $locData,
            'deptData'         => $deptData,
            'paramData'        => $paramData,

            // Data Array untuk Chart.js
            'chartLocLabels'   => $locData->pluck('label')->toArray(),
            'chartLocValues'   => $locData->pluck('total')->toArray(),

            'chartDeptLabels'  => $deptData->pluck('label')->toArray(),
            'chartDeptValues'  => $deptData->pluck('total')->toArray(),

            'chartParamLabels' => $paramData->pluck('label')->toArray(),
            'chartParamValues' => $paramData->pluck('total')->toArray(),

            'chartBobotLabels' => ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'],
            'chartBobotValues' => $chartBobotValues,
        ];
    }

    private function calculatePerformance($filterMonth)
    {
        // Logika Performa: Total tiket vs Completed pada bulan target tertentu [cite: 100-104]
        $year  = substr($filterMonth, 0, 4);
        $month = substr($filterMonth, 5, 2);

        $query = WorkOrderGeneralAffair::whereIn('status', ['in_progress', 'completed', 'approved'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('target_completion_date', $year)
                    ->whereMonth('target_completion_date', $month)
                    ->orWhere(function ($sub) use ($year, $month) {
                        $sub->whereNull('target_completion_date')
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month);
                    });
            });

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();

        return [
            'perfTotal'      => $total,
            'perfCompleted'  => $completed,
            'perfPercentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }
}
