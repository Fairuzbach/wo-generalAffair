@section('browser_title', 'General Affair Work Order')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2
                class="font-black text-3xl text-slate-900 leading-tight uppercase tracking-wider flex items-center gap-4">
                {{-- Industrial Accent: Striped Bar --}}
                <div
                    class="w-2 h-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmFjYzE1Ii8+CjxwYXRoIGQ9Ik0wIDBMOCA4Wk04IDBMMCA4WiIgc3Ryb2tlPSIjMTExIiBzdHJva2Utd2lkdGg9IjEiLz4KPC9zdmc+')] shadow-sm border border-slate-900">
                </div>
                {{ __('General Affair Request Order') }}
            </h2>
        </div>
    </x-slot>

    {{-- LOAD LIBRARY --}}
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @vite(['resources/css/general-affair.css', 'resources/js/general-affair.js'])
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- DATA CONFIGURATION --}}
    <script>
        window.gaConfig = {
            pageIds: @json($pageIds ?? []),
            startDate: "{{ request('start_date') }}",
            endDate: "{{ request('end_date') }}"
        };
    </script>

    {{-- CUSTOM PATTERN BACKGROUND --}}
    <div class="py-12 min-h-screen font-sans bg-slate-100 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNjYmQ1ZTEiIGZpbGwtb3BhY2l0eT0iMC4zIi8+PC9zdmc+')] bg-fixed"
        x-data="gaData">

        @if ($errors->any())
            <div x-init="setTimeout(() => showCreateModal = true, 500)"></div>
        @endif

        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">

            {{-- STATISTIK CARDS (Industrial & Clean) --}}
            <x-index.stats-card :countTotal="$countTotal" :countPending="$countPending" :countInProgress="$countInProgress" :countCompleted="$countCompleted" />

            {{-- CONTROL PANEL (Search & Filter) --}}
            <x-index.control-panel :filterOptions="[
                'status' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'category' => ['BERAT', 'SEDANG', 'RINGAN'],
                'parameter' => ['KEBERSIHAN', 'PEMELIHARAAN', 'PERBAIKAN', 'PEMBUATAN BARU', 'PERIZINAN', 'RESERVASI'],
            ]" />

            {{-- DATA TABLE --}}
            <x-index.table-data :workOrders="$workOrders" />

            {{-- MODAL 1: CREATE TICKET --}}
            <x-index.modal-create :plants="$plants" />

            {{-- MODAL 2: CONFIRMATION --}}
            <x-index.modal-confirm />

            {{-- MODAL 3: DETAIL TICKET (Simplified for this response, keeping functionality) --}}
            <x-index.modal-detail />

            {{-- MODAL 4: EDIT (Copy logic from original but use style above) --}}
            <x-index.modal-edit />

        </div>
        {{-- SCRIPT DATE RANGE DAN ALERT (Sama seperti original) --}}
        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: "{{ session('success') }}",
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Gagal!',
                        text: "{{ session('error') }}",
                        icon: 'error',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Tutup'
                    });
                });
            </script>
        @endif
    </div>
</x-app-layout>
