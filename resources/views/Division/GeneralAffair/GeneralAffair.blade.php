@section('browser_title', 'General Affair Work Order')

<x-app-layout>
    {{-- HEADER DENGAN TEMA MERAH PUTIH --}}
    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2 class="font-black text-3xl leading-tight uppercase tracking-wider flex items-center gap-4">
                {{-- Industrial Accent: Striped Bar (Red & Slate Theme) --}}
                <div class="w-2 h-10 bg-red-600 shadow-sm border border-red-800"></div>

                {{-- Text dengan Kombinasi Warna --}}
                <span class="flex gap-2">
                    <span class="text-red-600">GENERAL</span>
                    <span class="text-slate-800">AFFAIR</span>
                    <span class="text-slate-400 font-light">|</span>
                    <span class="text-slate-600 text-lg self-center tracking-normal normal-case font-bold">Request
                        Order</span>
                </span>
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

        {{-- Error Handler --}}
        @if ($errors->any())
            <div x-init="setTimeout(() => showCreateModal = true, 500)"></div>
        @endif

        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 
                STATISTIK CARDS (Industrial & Clean) 
                Update: Pastikan komponen ini menerima props warna jika ingin dikustomisasi, 
                tapi defaultnya biasanya sudah bagus.
            --}}
            <x-index.stats-card :countTotal="$countTotal" :countPending="$countPending" :countInProgress="$countInProgress" :countCompleted="$countCompleted" />

            {{-- CONTROL PANEL (Search & Filter) --}}
            <x-index.control-panel :filterOptions="[
                'status' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'category' => ['BERAT', 'SEDANG', 'RINGAN'],
                'parameter' => ['KEBERSIHAN', 'PEMELIHARAAN', 'PERBAIKAN', 'PEMBUATAN BARU', 'PERIZINAN', 'RESERVASI'],
            ]" />

            {{-- DATA TABLE --}}
            <x-index.table-data :workOrders="$workOrders" />

            {{-- 
                MODAL 1: CREATE TICKET 
                PENTING: Kita perlu menyuntikkan logika "Input NIK" ke dalam modal ini.
                Jika modal ini adalah file terpisah (resources/views/Components/index/modal-create.blade.php),
                ANDA HARUS MENGUPDATE FILE TERSEBUT JUGA.
                
                Di bawah ini saya asumsikan komponen modal create dipanggil.
            --}}
            <x-index.modal-create :plants="$plants" />

            {{-- MODAL LAINNYA --}}
            <x-index.modal-confirm />
            <x-index.modal-detail />
            <x-index.modal-edit />
            {{-- MODAL ACCEPT (TERIMA) --}}
            <div x-data>
                <div x-init="$watch('showAcceptModal', value => { if (value) $nextTick(() => $refs.picInput.focus()) })"></div>
                <template x-teleport="body">
                    <div x-show="showAcceptModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">
                        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"
                            @click="showAcceptModal = false"></div>
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div
                                class="relative w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden border border-slate-100 p-6">
                                <h3 class="text-lg font-black text-slate-800 uppercase mb-2">Terima Tiket</h3>
                                <p class="text-sm text-slate-500 mb-6">Siapa PIC yang akan mengerjakan tiket ini?</p>
                                <form :action="'/ga/approve/' + acceptId" method="POST">
                                    @csrf
                                    <div class="mb-6">
                                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Nama PIC /
                                            Teknisi</label>
                                        <input type="text" name="processed_by_name" x-ref="picInput" required
                                            class="w-full border-2 border-slate-300 focus:border-emerald-500 rounded-lg text-sm font-bold h-11 px-3"
                                            placeholder="Contoh: Budi Santoso">
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="showAcceptModal = false"
                                            class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs hover:bg-slate-200">Batal</button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg uppercase text-xs hover:bg-emerald-600 shadow-lg shadow-emerald-500/30">Simpan
                                            & Terima</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- MODAL REJECT (TOLAK) --}}
            <div x-data>
                <div x-init="$watch('showRejectModal', value => { if (value) $nextTick(() => $refs.reasonInput.focus()) })"></div>
                <template x-teleport="body">
                    <div x-show="showRejectModal" style="display: none;" class="fixed inset-0 z-[70] overflow-y-auto">
                        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"
                            @click="showRejectModal = false"></div>
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div
                                class="relative w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden border-t-4 border-red-500 p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="bg-red-100 p-2 rounded-full text-red-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-black text-slate-800 uppercase">Tolak Tiket</h3>
                                </div>
                                <p class="text-sm text-slate-500 mb-6">Apakah Anda yakin ingin menolak tiket ini?
                                    Silakan berikan alasannya.</p>
                                <form :action="'/ga/reject/' + rejectId" method="POST">
                                    @csrf
                                    <div class="mb-6">
                                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Alasan
                                            Penolakan <span class="text-red-500">*</span></label>
                                        <textarea name="reason" rows="3" x-ref="reasonInput" required
                                            class="w-full border-2 border-slate-300 focus:border-red-500 rounded-lg text-sm font-medium p-3"
                                            placeholder="Contoh: Permintaan tidak sesuai prosedur..."></textarea>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="showRejectModal = false"
                                            class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs hover:bg-slate-200">Batal</button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg uppercase text-xs hover:bg-red-600 shadow-lg shadow-red-500/30">Tolak
                                            Tiket</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @if (session('auto_edit_ticket'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    // Tunggu sebentar (500ms) untuk memastikan AlpineJS sudah ter-load sepenuhnya
                    setTimeout(() => {
                        // 1. Ambil elemen root Alpine (x-data="gaData")
                        const rootElement = document.querySelector('[x-data="gaData"]');

                        if (rootElement) {
                            // 2. Ambil data tiket dari Session PHP
                            const ticketData = @json(session('auto_edit_ticket'));

                            // Patch nama user jika relasi user tidak terbawa di json session
                            if (!ticketData.user_name) {
                                ticketData.user_name = "{{ session('auto_edit_ticket')->user->name ?? 'User' }}";
                            }

                            console.log('Auto opening edit modal for ticket:', ticketData.ticket_num);

                            // 3. Akses scope AlpineJS dan panggil fungsi openEditModal
                            // Pastikan fungsi openEditModal ada di resources/js/general-affair.js Anda
                            // (Gunakan Alpine.$data untuk mengakses scope dari luar)
                            const scope = Alpine.$data(rootElement);

                            if (typeof scope.openEditModal === 'function') {
                                scope.openEditModal(ticketData);

                                // (Opsional) Tampilkan notifikasi kecil
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'info',
                                    title: 'Silakan lengkapi detail (Dept/Target)',
                                    showConfirmButton: false,
                                    timer: 4000,
                                    timerProgressBar: true
                                });
                            } else {
                                console.error('Fungsi openEditModal tidak ditemukan di gaData');
                            }
                        }
                    }, 500);
                });
            </script>
        @endif

        {{-- SCRIPT SWEETALERT (Success/Error Messages) --}}
        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: "{{ session('success') }}",
                        icon: 'success',
                        confirmButtonColor: '#dc2626', // Red-600
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
