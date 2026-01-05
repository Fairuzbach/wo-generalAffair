@section('browser_title', 'General Affair Work Order')

<x-app-layout>
    {{-- CSS CUSTOM --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>

    <x-slot name="header">
        <div class="flex justify-between items-center -my-2">
            <h2 class="font-black text-3xl leading-tight uppercase tracking-wider flex items-center gap-4">
                <div class="relative">
                    <div class="w-2 h-10 bg-gradient-to-b from-red-600 to-red-700 shadow-lg border border-red-800"></div>
                    <div class="absolute -right-1 top-0 w-2 h-10 bg-gradient-to-b from-white to-gray-100 opacity-30">
                    </div>
                </div>
                <span class="flex gap-2 items-center">
                    <span class="text-red-600 drop-shadow-sm">GENERAL</span>
                    <span class="text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)]"
                        style="text-shadow: 2px 2px 0 #dc2626, -1px -1px 0 #dc2626, 1px -1px 0 #dc2626, -1px 1px 0 #dc2626;">AFFAIR</span>
                    <span class="text-red-300 font-light">|</span>
                    <span class="text-slate-700 text-lg self-center tracking-normal normal-case font-bold">Request
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

    <script>
        window.gaConfig = {
            pageIds: @json($pageIds ?? []),
            startDate: "{{ request('start_date') }}",
            endDate: "{{ request('end_date') }}",
            // TAMBAHKAN INI:
            userNik: "{{ Auth::user()->nik }}",
            userName: "{{ Auth::user()->name }}",
            userDept: "{{ Auth::user()->divisi }}"
        };
    </script>

    {{-- MULAI SCOPE ALPINE JS --}}
    <div class="py-12 min-h-screen font-sans bg-slate-100 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNjYmQ1ZTEiIGZpbGwtb3BhY2l0eT0iMC4zIi8+PC9zdmc+')] bg-fixed"
        x-data="gaData" @buka-detail.window="openDetail($event.detail)" x-cloak>

        <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 1. STATISTIK --}}
            <x-index.stats-card :countTotal="$countTotal" :countPending="$countPending" :countInProgress="$countInProgress" :countCompleted="$countCompleted" />

            {{-- 2. CONTROL PANEL --}}
            <x-index.control-panel :filterOptions="[
                'status' => ['pending', 'in_progress', 'completed', 'cancelled'],
                'category' => ['BERAT', 'SEDANG', 'RINGAN'],
                'parameter' => ['KEBERSIHAN', 'PEMELIHARAAN', 'PERBAIKAN', 'PEMBUATAN BARU', 'PERIZINAN', 'RESERVASI'],
            ]" />

            {{-- 3. DATA TABLE (Pusat Tombol Mata) --}}
            <x-index.table-data :workOrders="$workOrders" />

            {{-- 4. MODAL-MODAL (Semua ada di dalam scope x-data) --}}
            <x-index.modal-create :plants="$plants" />
            <x-index.modal-detail />
            <x-index.modal-edit />
            <x-index.modal-confirm />

            {{-- MODAL ACCEPT GA --}}
            <template x-teleport="body">
                <div x-show="showAcceptModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">
                    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showAcceptModal = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-6">
                            <h3 class="text-lg font-black text-slate-800 uppercase mb-2">Terima Tiket</h3>
                            <p class="text-sm text-slate-500 mb-6">Siapa PIC yang akan mengerjakan tiket ini?</p>
                            <form :action="'/ga/approve/' + acceptId" method="POST">
                                @csrf
                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Nama PIC /
                                        Teknisi</label>
                                    <input type="text" name="processed_by_name" required
                                        class="w-full border-2 border-slate-300 rounded-lg text-sm font-bold h-11 px-3">
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="showAcceptModal = false"
                                        class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs">Batal</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg uppercase text-xs">Simpan
                                        & Terima</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

            {{-- MODAL REJECT GA --}}
            <template x-teleport="body">
                <div x-show="showRejectModal" style="display: none;" class="fixed inset-0 z-[70] overflow-y-auto">
                    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showRejectModal = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="relative w-full max-w-md bg-white rounded-xl shadow-2xl border-t-4 border-red-500 p-6">
                            <h3 class="text-lg font-black text-slate-800 uppercase mb-4">Tolak Tiket</h3>
                            <form :action="'/ga/reject/' + rejectId" method="POST">
                                @csrf
                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Alasan
                                        Penolakan <span class="text-red-500">*</span></label>
                                    <textarea name="reason" rows="3" required class="w-full border-2 border-slate-300 rounded-lg text-sm p-3"></textarea>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="showRejectModal = false"
                                        class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs">Batal</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg uppercase text-xs">Tolak
                                        Tiket</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

        </div> {{-- End max-w container --}}
    </div> {{-- AKHIR DIV x-data="gaData" --}}

    {{-- SCRIPTS --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gaData', () => ({
                // DATA USER LOGIN
                currentUser: {
                    nik: "{{ Auth::user()->nik ?? '' }}",
                    name: "{{ Auth::user()->name ?? '' }}",
                    department: "{{ Auth::user()->divisi ?? '' }}"
                },

                // STATE FORM CREATE
                formData: {
                    nik: "{{ Auth::user()->nik ?? '' }}",
                    manual_requester_name: "{{ Auth::user()->name ?? '' }}",
                    plant_id: '',
                    department: "{{ Auth::user()->divisi ?? '' }}",
                    category: 'RINGAN',
                    parameter_permintaan: '',
                    status_permintaan: 'OPEN',
                    target_completion_date: '',
                    description: ''
                },

                plantsData: @json($plants),
                isChecking: false,
                showCreateModal: false,
                showDetailModal: false,
                ticket: null, // Variabel penting untuk modal detail
                showAcceptModal: false,
                acceptId: null,
                showRejectModal: false,
                rejectId: null,

                get displayDept() {
                    return this.formData.department || '-';
                },

                init() {
                    this.resetToMe();
                },

                // FUNGSI UTAMA: BUKA DETAIL (Gunakan Base64 untuk keamanan data)
                openDetail(encodedData) {
                    if (!encodedData) return;
                    try {
                        // Decode dan Parse
                        const ticketData = JSON.parse(atob(encodedData));

                        // Masukkan ke state
                        this.ticket = ticketData;
                        this.showDetailModal = true;

                        console.log('Detail Modal Terbuka:', this.ticket);
                    } catch (error) {
                        console.error('Error parsing detail:', error);
                    }
                },

                resetToMe() {
                    this.formData.nik = this.currentUser.nik;
                    this.formData.manual_requester_name = this.currentUser.name;
                    this.formData.department = this.currentUser.department;
                },

                async checkNik() {
                    if (!this.formData.nik || this.formData.nik === this.currentUser.nik) {
                        this.resetToMe();
                        return;
                    }

                    this.isChecking = true;
                    try {
                        const response = await fetch(`/ga/check-employee?nik=${this.formData.nik}`);
                        const result = await response.json();

                        if (result.status === 'success') {
                            this.formData.manual_requester_name = result.data.name;
                            this.formData.department = result.data.department;
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Data Ditemukan',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            this.formData.manual_requester_name = '';
                            this.formData.department = '';
                            Swal.fire({
                                icon: 'error',
                                title: 'NIK Tidak Ditemukan!',
                                html: `<div class="text-slate-600">Maaf, NIK <b>${this.formData.nik}</b> tidak terdaftar.</div>`,
                                confirmButtonColor: '#0f172a'
                            });
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isChecking = false;
                    }
                },

                // Modal Helpers
                openAcceptModal(id) {
                    this.acceptId = id;
                    this.showAcceptModal = true;
                },
                openRejectModal(id) {
                    this.rejectId = id;
                    this.showRejectModal = true;
                }
            }));
        });

        // SweetAlert Handler untuk tombol Engineer di Tabel
        function confirmTechnicalAction(id, type) {
            const isApprove = type === 'approve';
            Swal.fire({
                title: isApprove ? 'Setujui Tiket?' : 'Tolak Tiket?',
                text: isApprove ? 'Tiket akan diteruskan ke tim GA.' : 'Tiket akan dikembalikan ke user.',
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#059669' : '#e11d48',
                confirmButtonText: isApprove ? 'Ya, Approve!' : 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('input-action-' + id).value = type;
                    document.getElementById('form-tech-' + id).submit();
                }
            });
        }
    </script>

    {{-- SESSION MESSAGES --}}
    @if (session('success'))
        <script>
            Swal.fire({
                title: 'Berhasil!',
                text: "{{ session('success') }}",
                icon: 'success',
                confirmButtonColor: '#dc2626'
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                title: 'Gagal!',
                text: "{{ session('error') }}",
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        </script>
    @endif
</x-app-layout>
