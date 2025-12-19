@props(['chartDataDetail', 'chartDataPhase'])

<div class="bg-white p-6 rounded-sm shadow-md border-t-4 border-yellow-400 mb-8" x-data="{ openFilter: false }">

    {{-- HEADER & CONTROLS --}}
    <div class="flex flex-col gap-4 mb-6 border-b border-slate-200 pb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">

            {{-- Title --}}
            <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Timeline & Workload
            </h4>

            {{-- ACTION: Filter & View Mode --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Tombol Toggle Phase --}}
                <button id="togglePhase"
                    class="px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-sm border border-slate-300 transition-colors">
                    Toggle Phase
                </button>

                {{-- Select Filter (Dummy UI) --}}
                <select id="ganttStatusFilter"
                    class="text-xs font-bold border-slate-300 rounded-sm focus:ring-yellow-400 focus:border-yellow-400 cursor-pointer">
                    <option value="all">Status: All</option>
                    <option value="completed">Completed</option>
                    <option value="in-progress">In Progress</option>
                    <option value="delayed">Delayed</option>
                </select>

                <select id="viewModeSelect" name="view_mode"
                    class="text-xs font-bold border-slate-300 rounded-sm focus:ring-yellow-400 focus:border-yellow-400 cursor-pointer">
                    <option value="phase">View: By Dept</option>
                    <option value="task">View: All Tasks</option>
                </select>

                <select id="timeRangeSelect" name="time_range"
                    class="text-xs font-bold border-slate-300 rounded-sm focus:ring-yellow-400 focus:border-yellow-400 cursor-pointer">
                    <option value="month" {{ request('range_mode') != 'week' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="week" {{ request('range_mode') == 'week' ? 'selected' : '' }}>Minggu Ini</option>
                </select>
            </div>
        </div>

        {{-- LEGEND & STATUS --}}
        <div class="flex flex-wrap justify-between items-center gap-4 mt-2">
            <div class="text-xs flex flex-wrap gap-3 font-bold uppercase text-slate-600">
                <span class="flex items-center gap-1.5"><span
                        class="w-3 h-3 bg-red-500 rounded-sm animate-pulse"></span> Critical</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-blue-500 rounded-sm"></span> On
                    Progress</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-emerald-500 rounded-sm"></span>
                    Completed</span>
            </div>
            <div class="text-[10px] font-mono text-slate-400">Today: {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    {{-- CHART CONTAINER --}}
    <div class="relative w-full h-[400px]">
        <canvas id="ganttChart"></canvas>

        {{-- PERBAIKAN 1: Cek chartDataDetail['labels'] bukan ganttLabels --}}
        @if (empty($chartDataDetail['labels']))
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/90 z-10 backdrop-blur-sm">
                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                <p class="text-slate-500 font-bold text-sm">Tidak ada jadwal pada periode ini</p>
            </div>
        @endif
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rawDetail = {!! json_encode($chartDataDetail ?? ['labels' => [], 'data' => [], 'colors' => []]) !!};
        const rawPhase = {!! json_encode($chartDataPhase ?? ['labels' => [], 'data' => [], 'colors' => []]) !!};

        const canvasId = 'ganttChart';
        const ctx = document.getElementById(canvasId).getContext('2d');

        // Destroy existing chart if any (Mencegah error Canvas reused)
        const existingChart = Chart.getChart(canvasId);
        if (existingChart) existingChart.destroy();

        const toggleBtn = document.getElementById('togglePhase');
        const viewModeSelect = document.getElementById('viewModeSelect');
        const timeRangeSelect = document.getElementById('timeRangeSelect');
        const statusFilter = document.getElementById('ganttStatusFilter');

        let isPhaseView = false;
        const colorMap = {
            'completed': '#10b981',
            'delayed': '#ef4444',
            'in-progress': '#3b82f6'
        };

        if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

        const chartConfig = {
            type: 'bar',
            data: {
                labels: [...rawDetail.labels],
                datasets: [{
                    label: 'Durasi',
                    data: [...rawDetail.data],
                    backgroundColor: [...rawDetail.colors],
                    borderRadius: 4,
                    barPercentage: 0.7,
                    datalabels: {
                        color: 'white',
                        anchor: 'end',
                        align: 'start',
                        offset: 4,
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (val) => val > 0 ? Math.round(val) : ''
                    }
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 30
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            precision: 0,
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: 'Durasi (Hari)',
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            color: '#334155',
                            font: {
                                size: 11,
                                weight: '500',
                                family: 'monospace'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                let val = Math.round(context.raw);
                                return isPhaseView ? `Total Tiket: ${val}` : `Estimasi: ${val} Hari`;
                            }
                        }
                    }
                }
            }
        };

        let myGanttChart = new Chart(ctx, chartConfig);

        // --- EVENT LISTENERS ---
        function updateChartData(labels, data, colors) {
            myGanttChart.data.labels = labels;
            myGanttChart.data.datasets[0].data = data;
            myGanttChart.data.datasets[0].backgroundColor = colors;
            myGanttChart.update();
        }

        // 1. View Mode
        if (viewModeSelect) {
            viewModeSelect.addEventListener('change', function(e) {
                const mode = e.target.value;
                if (mode === 'phase') {
                    isPhaseView = true;
                    updateChartData(rawPhase.labels, rawPhase.data, rawPhase.colors);
                    if (statusFilter) {
                        statusFilter.value = 'all';
                        statusFilter.disabled = true;
                    }
                    myGanttChart.options.scales.x.title.text = 'Jumlah Tiket';
                } else {
                    isPhaseView = false;
                    updateChartData(rawDetail.labels, rawDetail.data, rawDetail.colors);
                    if (statusFilter) statusFilter.disabled = false;
                    myGanttChart.options.scales.x.title.text = 'Durasi (Hari)';
                }
            });
        }

        // 2. Toggle Sync
        if (toggleBtn && viewModeSelect) {
            toggleBtn.addEventListener('click', function() {
                viewModeSelect.value = (viewModeSelect.value === 'task') ? 'phase' : 'task';
                viewModeSelect.dispatchEvent(new Event('change'));

                if (viewModeSelect.value === 'phase') {
                    toggleBtn.innerText = "View: Detail Tasks";
                    toggleBtn.className =
                        "px-3 py-1.5 text-xs font-bold bg-yellow-100 text-yellow-800 rounded-sm border border-yellow-400";
                } else {
                    toggleBtn.innerText = "Toggle Phase";
                    toggleBtn.className =
                        "px-3 py-1.5 text-xs font-bold bg-slate-100 text-slate-700 rounded-sm border border-slate-300";
                }
            });
        }

        // 3. Time Range
        if (timeRangeSelect) {
            timeRangeSelect.addEventListener('change', function(e) {
                const range = e.target.value;
                const now = new Date();
                let startDate, endDate;
                const formatDate = (date) => date.toISOString().split('T')[0];

                if (range === 'week') {
                    const day = now.getDay();
                    const diff = now.getDate() - day + (day === 0 ? -6 : 1);
                    const monday = new Date(now.setDate(diff));
                    const sunday = new Date(now.setDate(monday.getDate() + 6));
                    startDate = formatDate(monday);
                    endDate = formatDate(sunday);
                } else {
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    startDate = formatDate(firstDay);
                    endDate = formatDate(lastDay);
                }

                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('start_date', startDate);
                currentUrl.searchParams.set('end_date', endDate);
                currentUrl.searchParams.set('range_mode', range);
                window.location.href = currentUrl.toString();
            });
        }

        // 4. Status Filter
        if (statusFilter) {
            statusFilter.addEventListener('change', function(e) {
                const status = e.target.value;
                if (isPhaseView) {
                    viewModeSelect.value = 'task';
                    viewModeSelect.dispatchEvent(new Event('change'));
                }
                if (status === 'all') {
                    updateChartData(rawDetail.labels, rawDetail.data, rawDetail.colors);
                } else {
                    const targetColor = colorMap[status];
                    let fLabels = [],
                        fData = [],
                        fColors = [];
                    rawDetail.colors.forEach((color, index) => {
                        if (color && color.toLowerCase().includes(targetColor)) {
                            fLabels.push(rawDetail.labels[index]);
                            fData.push(rawDetail.data[index]);
                            fColors.push(color);
                        }
                    });
                    updateChartData(fLabels, fData, fColors);
                }
            });
        }
    });
</script>
