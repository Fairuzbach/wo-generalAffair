@section('browser_title', 'GA Dashboard')
<x-app-layout>
    {{-- Header dengan Tema Caterpillar (Hitam/Kuning) --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight uppercase flex items-center gap-3">
                <span class="w-3 h-8 bg-yellow-400 rounded-sm inline-block"></span>
                {{ __('Dashboard Statistik') }}
            </h2>
            <div class="flex gap-2">
                {{-- TOMBOL DOWNLOAD PDF BARU --}}
                <button onclick="exportToPdf()"
                    class="bg-red-600 text-white hover:bg-red-700 font-bold py-2 px-4 rounded text-sm uppercase tracking-wide transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export PDF
                </button>

                <a href="{{ route('ga.index') }}"
                    class="bg-slate-900 text-white hover:bg-slate-800 font-bold py-2 px-4 rounded text-sm uppercase tracking-wide transition">
                    &larr; Kembali ke Data
                </a>
            </div>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    {{-- <script>
            window.gaDashboardData = @json($dashboardData);
        </script> --}}
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard/index.js'])

    {{-- DATA INJECTION (PHP ke JS) --}}
    <script>
        window.gaDashboardData = {
            performance: {
                percentage: {{ $perfPercentage }},
                total: {{ $perfTotal }},
                completed: {{ $perfCompleted }}
            },
            loc: {
                labels: @json($chartLocLabels),
                values: @json($chartLocValues)
            },
            dept: {
                labels: @json($chartDeptLabels),
                values: @json($chartDeptValues)
            },
            param: {
                labels: @json($chartParamLabels),
                values: @json($chartParamValues)
            },
            bobot: {
                labels: @json($chartBobotLabels),
                values: @json($chartBobotValues)
            },
            gantt: {
                labels: @json($ganttLabels ?? []),
                data: @json($ganttData ?? []),
                colors: @json($ganttColors ?? []),
                raw: @json($ganttRawData ?? [])
            },
            meta: {
                filterMonth: "{{ $filterMonth }}",
                defaultStartDateFilename: "{{ $workOrders->min('created_at')?->format('Y-m-d') ?? date('Y-m-d') }}",
                defaultStartDateHeader: "{{ $workOrders->min('created_at')?->translatedFormat('d F Y') ?? date('d F Y') }}"
            }
        };
    </script>

    <div class="py-12 bg-slate-50">
        <div id="dashboard-content" class="max-w-8xl mx-auto sm:px-6 lg:px-8 p-4 bg-slate-50">
            {{-- B. STATISTIK CARDS (Interactive Industrial Style) --}}
            {{-- B. STATISTIK CARDS (High Interactivity) --}}
            <x-dashboard.stats-card :countTotal="$countTotal" :countPending="$countPending" :countInProgress="$countInProgress" :countCompleted="$countCompleted" />

            {{-- Grid Grafik --}}
            {{-- GRID GRAFIK (INDUSTRIAL STYLE) --}}
            <x-dashboard.graph-grid :filterMonth="$filterMonth" :perfPercentage="$perfPercentage" :perfTotal="$perfTotal" :perfCompleted="$perfCompleted" />

            {{-- Pie Chart --}}
            <x-dashboard.pie-chart />

            {{-- Date Range --}}
            <x-dashboard.date-range />

            {{-- Gantt Chart (Container Kuning) --}}
            <x-dashboard.gantt-chart :chartDataDetail="$chartDataDetail" :chartDataPhase="$chartDataPhase" />
</x-app-layout>
