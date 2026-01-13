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

                {{-- Select Filter --}}
                <select id="ganttStatusFilter"
                    class="text-xs font-bold border-slate-300 rounded-sm focus:ring-yellow-400 focus:border-yellow-400 cursor-pointer">
                    <option value="all">Status: All</option>
                    <option value="completed">✓ Completed</option>
                    <option value="in_progress">◷ In Progress</option>
                    <option value="delayed">⚠ Critical</option>
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

                {{-- Input Limit Data --}}
                <div class="flex items-center gap-2 border border-slate-300 rounded-sm px-2 py-1">
                    <label for="dataLimitInput" class="text-xs font-bold text-slate-600">Tampilkan:</label>
                    <input type="number" id="dataLimitInput" min="5" max="100" value="20"
                        class="w-16 text-xs font-bold border-0 focus:ring-0 p-0 text-center">
                    <span class="text-xs text-slate-500">data</span>
                </div>
            </div>
        </div>

        {{-- LEGEND & STATUS --}}
        {{-- LEGEND & STATUS --}}
        <div class="flex flex-wrap justify-between items-center gap-4 mt-2">
            <div class="text-xs flex flex-wrap gap-3 font-bold uppercase text-slate-600">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-red-500 rounded-sm animate-pulse"></span> Critical
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-blue-500 rounded-sm"></span> In Progress
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 bg-emerald-500 rounded-sm"></span> Completed
                </span>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-[10px] font-mono text-slate-400">Today: {{ now()->format('d M Y') }}</div>
                <div id="dataCountInfo" class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded-sm">
                    Menampilkan: <span id="displayedCount">0</span> dari <span id="totalCount">0</span>
                </div>
            </div>
        </div>

        {{-- Warning untuk data banyak --}}
        <div id="dataWarning" class="hidden bg-yellow-50 border border-yellow-200 rounded-sm p-3">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-xs font-bold text-yellow-800">Data terlalu banyak!</p>
                    <p class="text-xs text-yellow-700 mt-1">
                        Grafik menampilkan <span id="warningLimit"></span> data teratas.
                        Gunakan filter tanggal, status, atau view mode untuk melihat data lebih spesifik.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- CHART CONTAINER --}}
    <div class="relative w-full" id="chartWrapper">
        <canvas id="ganttChart"></canvas>

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
        const rawDetail = {!! json_encode($chartDataDetail ?? ['labels' => [], 'data' => [], 'colors' => [], 'metadata' => []]) !!};
        const rawPhase = {!! json_encode($chartDataPhase ?? ['labels' => [], 'data' => [], 'colors' => []]) !!};

        const canvasId = 'ganttChart';
        const ctx = document.getElementById(canvasId).getContext('2d');

        // Destroy existing chart
        const existingChart = Chart.getChart(canvasId);
        if (existingChart) existingChart.destroy();

        const toggleBtn = document.getElementById('togglePhase');
        const viewModeSelect = document.getElementById('viewModeSelect');
        const timeRangeSelect = document.getElementById('timeRangeSelect');
        const statusFilter = document.getElementById('ganttStatusFilter');
        const dataLimitInput = document.getElementById('dataLimitInput');
        const chartWrapper = document.getElementById('chartWrapper');
        const dataWarning = document.getElementById('dataWarning');

        let isPhaseView = false;
        let currentLimit = 20;

        // PERBAIKAN: Sanitize data untuk handle no target date
        let fullDetailData = sanitizeChartData({
            labels: [...rawDetail.labels],
            data: [...rawDetail.data],
            colors: [...rawDetail.colors],
            metadata: rawDetail.metadata ? [...rawDetail.metadata] : []
        });

        const colorMap = {
            'completed': '#10b981',
            'delayed': '#ef4444',
            'in_progress': '#3b82f6'
            // ✅ HAPUS 'no-target' dari colorMap karena bukan filter status
        };

        if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

        /**
         * FUNGSI BARU: Sanitize data untuk menangani report tanpa target completion
         */
        function sanitizeChartData(chartData) {
            const sanitized = {
                labels: [],
                data: [],
                colors: [],
                metadata: []
            };

            chartData.labels.forEach((label, index) => {
                const metadata = chartData.metadata && chartData.metadata[index] ? chartData.metadata[
                    index] : {};
                const hasTargetDate = metadata.target_completion_date || metadata.has_target;
                const color = chartData.colors[index];
                const status = metadata.status;

                // ✅ PERBAIKAN: Hanya ubah warna jika TIDAK ADA target date DAN status bukan completed
                // Report dengan target date tetap dengan warna aslinya (merah/biru/hijau)
                if (!hasTargetDate && status !== 'completed' && color && color.toLowerCase().includes(
                        '#ef4444')) {
                    sanitized.labels.push(label);
                    sanitized.data.push(chartData.data[index]);
                    sanitized.colors.push('#94a3b8'); // Abu-abu untuk no target
                    sanitized.metadata.push({
                        ...metadata,
                        has_target: false
                    });
                } else {
                    // Pertahankan warna asli untuk semua report dengan target date
                    sanitized.labels.push(label);
                    sanitized.data.push(chartData.data[index]);
                    sanitized.colors.push(color);
                    sanitized.metadata.push(metadata);
                }
            });

            return sanitized;
        }

        // ✅ PERBAIKAN: Update info counter dengan pengecekan phase view
        function updateDataInfo(displayed, total, isPhase = false) {
            document.getElementById('displayedCount').textContent = displayed;
            document.getElementById('totalCount').textContent = total;

            // ✅ FIX: Jangan tampilkan warning di phase view
            // Dan hanya tampilkan jika memang ada data yang tidak ditampilkan
            if (!isPhase && displayed < total) {
                dataWarning.classList.remove('hidden');
                document.getElementById('warningLimit').textContent = displayed;
            } else {
                dataWarning.classList.add('hidden');
            }
        }

        // Dynamic height calculation
        function calculateChartHeight(dataCount) {
            const minHeight = 400;
            const itemHeight = 35;
            const calculatedHeight = Math.max(minHeight, dataCount * itemHeight);
            return Math.min(calculatedHeight, 1200);
        }

        // Limit data function
        function limitData(labels, data, colors, metadata, limit) {
            if (labels.length <= limit) {
                return {
                    labels,
                    data,
                    colors,
                    metadata
                };
            }
            return {
                labels: labels.slice(0, limit),
                data: data.slice(0, limit),
                colors: colors.slice(0, limit),
                metadata: metadata ? metadata.slice(0, limit) : []
            };
        }

        const chartConfig = {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Durasi',
                    data: [],
                    backgroundColor: [],
                    borderRadius: 4,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9,
                    datalabels: {
                        color: 'white',
                        anchor: 'end',
                        align: 'start',
                        offset: 4,
                        font: {
                            weight: 'bold',
                            size: 10
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
                        right: 30,
                        left: 5
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            stepSize: 1,
                            font: {
                                size: 10,
                                weight: '500'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Durasi (Hari)',
                            font: {
                                size: 11,
                                weight: 'bold'
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
                                size: 10,
                                weight: '600',
                                family: 'monospace'
                            },
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                return label.length > 35 ? label.substring(0, 32) + '...' : label;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        padding: 15,
                        titleFont: {
                            size: 12,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 11
                        },
                        bodySpacing: 6,
                        displayColors: true,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const val = Math.round(context.raw);
                                const color = context.dataset.backgroundColor[context.dataIndex];
                                const metadata = fullDetailData.metadata ? fullDetailData.metadata[
                                    context.dataIndex] : {};

                                if (isPhaseView) {
                                    return `Total Tiket: ${val} pekerjaan`;
                                }

                                // ✅ PERBAIKAN: Status hanya 3 jenis
                                let status = '';
                                if (color.includes('#10b981')) {
                                    status = '✓ Completed';
                                } else if (color.includes('#ef4444')) {
                                    status = '⚠ Critical';
                                } else {
                                    status = '◷ In Progress';
                                }

                                // ✅ PERBAIKAN: Deadline sebagai informasi terpisah
                                let deadline = '';
                                if (metadata.target_completion_date) {
                                    const targetDate = new Date(metadata.target_completion_date);
                                    const formattedDate = targetDate.toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    });
                                    deadline = `Deadline: ${formattedDate}`;
                                } else {
                                    deadline = 'Deadline: Tanpa Target Waktu';
                                }

                                return [
                                    `Status: ${status}`,
                                    deadline,
                                    `Durasi: ${val} hari`,
                                    `─────────────────`
                                ];
                            },
                            afterLabel: function(context) {
                                if (isPhaseView) return '';

                                const color = context.dataset.backgroundColor[context.dataIndex];
                                const val = Math.round(context.raw);
                                const metadata = fullDetailData.metadata ? fullDetailData.metadata[
                                    context.dataIndex] : {};

                                let info = [];

                                // ✅ PERBAIKAN: Info berdasarkan warna dan status
                                if (color.includes('#ef4444')) {
                                    // Merah = Critical/Delayed
                                    info.push('⚡ Perlu Perhatian Segera');
                                } else if (color.includes('#3b82f6')) {
                                    // Biru = In Progress
                                    if (metadata.target_completion_date) {
                                        const targetDate = new Date(metadata.target_completion_date);
                                        const today = new Date();
                                        const diffTime = targetDate - today;
                                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                                        if (diffDays > 0) {
                                            info.push(`📅 ${diffDays} hari tersisa`);
                                        }
                                    }
                                } else if (color.includes('#10b981')) {
                                    // Hijau = Completed
                                    info.push('✅ Pekerjaan telah tuntas');
                                    if (metadata.actual_completion_date) {
                                        const actualDate = new Date(metadata.actual_completion_date);
                                        const formattedActual = actualDate.toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric'
                                        });
                                        info.push(`Selesai: ${formattedActual}`);
                                    }
                                } else if (color.includes('#94a3b8')) {
                                    // Abu-abu = No Target
                                    info.push('📌 Belum ada target penyelesaian');
                                }

                                // Kategori durasi (untuk semua kecuali yang abu-abu tanpa target)
                                if (!color.includes('#94a3b8')) {
                                    if (val <= 3) {
                                        info.push('🔵 Prioritas: Cepat');
                                    } else if (val <= 7) {
                                        info.push('🟡 Prioritas: Normal');
                                    } else {
                                        info.push('🔴 Prioritas: Lama');
                                    }
                                }

                                return info;
                            },
                            footer: function(context) {
                                if (isPhaseView) {
                                    return '💡 Klik untuk detail';
                                }
                                return '💡 Hover untuk info lengkap';
                            }
                        }
                    }
                }
            }
        };

        let myGanttChart = new Chart(ctx, chartConfig);

        // ✅ PERBAIKAN: Update chart dengan parameter isPhase
        function updateChartData(labels, data, colors, metadata = [], isPhase = false) {
            const totalData = labels.length;
            let displayData;

            if (isPhase) {
                // Phase view: tampilkan semua
                displayData = {
                    labels,
                    data,
                    colors,
                    metadata
                };
                currentLimit = totalData;
            } else {
                // Task view: limit data
                displayData = limitData(labels, data, colors, metadata, currentLimit);
            }

            const newHeight = calculateChartHeight(displayData.labels.length);
            chartWrapper.style.height = newHeight + 'px';

            myGanttChart.data.labels = displayData.labels;
            myGanttChart.data.datasets[0].data = displayData.data;
            myGanttChart.data.datasets[0].backgroundColor = displayData.colors;

            if (displayData.labels.length > 30) {
                myGanttChart.options.scales.y.ticks.font.size = 9;
                myGanttChart.options.scales.x.ticks.font.size = 9;
            } else {
                myGanttChart.options.scales.y.ticks.font.size = 10;
                myGanttChart.options.scales.x.ticks.font.size = 10;
            }

            myGanttChart.update();

            // ✅ FIX: Pass isPhase parameter to updateDataInfo
            updateDataInfo(displayData.labels.length, totalData, isPhase);
        }

        // Initial render
        updateChartData(
            fullDetailData.labels,
            fullDetailData.data,
            fullDetailData.colors,
            fullDetailData.metadata,
            false // Task view by default
        );

        // --- EVENT LISTENERS ---

        // 1. View Mode
        if (viewModeSelect) {
            viewModeSelect.addEventListener('change', function(e) {
                const mode = e.target.value;
                if (mode === 'phase') {
                    isPhaseView = true;
                    updateChartData(
                        rawPhase.labels,
                        rawPhase.data,
                        Array(rawPhase.labels.length).fill('#eab308'),
                        [],
                        true // ✅ FIX: Pass true for phase view
                    );
                    if (statusFilter) {
                        statusFilter.value = 'all';
                        statusFilter.disabled = true;
                    }
                    dataLimitInput.disabled = true;
                    myGanttChart.options.scales.x.title.text = 'Jumlah Tiket';
                } else {
                    isPhaseView = false;
                    fullDetailData = sanitizeChartData({
                        labels: [...rawDetail.labels],
                        data: [...rawDetail.data],
                        colors: [...rawDetail.colors],
                        metadata: rawDetail.metadata ? [...rawDetail.metadata] : []
                    });
                    updateChartData(
                        fullDetailData.labels,
                        fullDetailData.data,
                        fullDetailData.colors,
                        fullDetailData.metadata,
                        false // ✅ FIX: Pass false for task view
                    );
                    if (statusFilter) statusFilter.disabled = false;
                    dataLimitInput.disabled = false;
                    myGanttChart.options.scales.x.title.text = 'Durasi (Hari)';
                }
            });
        }

        // 2. Toggle Button
        if (toggleBtn && viewModeSelect) {
            toggleBtn.addEventListener('click', function() {
                viewModeSelect.value = (viewModeSelect.value === 'task') ? 'phase' : 'task';
                viewModeSelect.dispatchEvent(new Event('change'));

                if (viewModeSelect.value === 'phase') {
                    toggleBtn.innerText = "View: Detail Tasks";
                    toggleBtn.className =
                        "px-3 py-1.5 text-xs font-bold bg-yellow-100 text-yellow-800 rounded-sm border border-yellow-400 transition-colors";
                } else {
                    toggleBtn.innerText = "Toggle Phase";
                    toggleBtn.className =
                        "px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-sm border border-slate-300 transition-colors";
                }
            });
        }

        // 3. Data Limit Input
        if (dataLimitInput) {
            dataLimitInput.addEventListener('change', function(e) {
                let newLimit = parseInt(e.target.value);
                if (newLimit < 5) newLimit = 5;
                if (newLimit > 100) newLimit = 100;
                e.target.value = newLimit;
                currentLimit = newLimit;

                if (!isPhaseView) {
                    updateChartData(
                        fullDetailData.labels,
                        fullDetailData.data,
                        fullDetailData.colors,
                        fullDetailData.metadata,
                        false
                    );
                }
            });
        }

        // 4. Time Range
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

        // 5. Status Filter
        if (statusFilter) {
            statusFilter.addEventListener('change', function(e) {
                const status = e.target.value;

                if (isPhaseView) {
                    viewModeSelect.value = 'task';
                    viewModeSelect.dispatchEvent(new Event('change'));
                }

                if (status === 'all') {
                    fullDetailData = sanitizeChartData({
                        labels: [...rawDetail.labels],
                        data: [...rawDetail.data],
                        colors: [...rawDetail.colors],
                        metadata: rawDetail.metadata ? [...rawDetail.metadata] : []
                    });
                } else {
                    const targetColor = colorMap[status];

                    // ✅ PERBAIKAN: Sanitize data mentah dulu
                    const sanitizedSource = sanitizeChartData({
                        labels: [...rawDetail.labels],
                        data: [...rawDetail.data],
                        colors: [...rawDetail.colors],
                        metadata: rawDetail.metadata ? [...rawDetail.metadata] : []
                    });

                    let fLabels = [],
                        fData = [],
                        fColors = [],
                        fMetadata = [];

                    // Filter berdasarkan warna yang sudah benar
                    sanitizedSource.colors.forEach((color, index) => {
                        const colorLower = color.toLowerCase();
                        const targetColorLower = targetColor.toLowerCase();

                        if (colorLower.includes(targetColorLower) || colorLower ===
                            targetColorLower) {
                            fLabels.push(sanitizedSource.labels[index]);
                            fData.push(sanitizedSource.data[index]);
                            fColors.push(sanitizedSource.colors[index]);
                            if (sanitizedSource.metadata && sanitizedSource.metadata[index]) {
                                fMetadata.push(sanitizedSource.metadata[index]);
                            }
                        }
                    });

                    fullDetailData = {
                        labels: fLabels,
                        data: fData,
                        colors: fColors,
                        metadata: fMetadata
                    };
                }

                updateChartData(
                    fullDetailData.labels,
                    fullDetailData.data,
                    fullDetailData.colors,
                    fullDetailData.metadata,
                    false
                );
            });
        }
    });
</script>
