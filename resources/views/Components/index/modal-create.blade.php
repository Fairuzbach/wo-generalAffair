@props(['plants'])

<template x-teleport="body">
    <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="showCreateModal = false">
        </div>

        <div class="flex min-h-full items-center justify-center p-4">

            {{-- Wrapper Utama --}}
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all border border-slate-100"
                x-data="{
                    formData: {
                        nik: '',
                        manual_requester_name: '',
                        plant_id: '',
                        department: '',
                        category: 'RINGAN',
                        parameter_permintaan: '',
                        status_permintaan: 'OPEN',
                        target_completion_date: '',
                        description: ''
                    },
                    plantsData: {{ Js::from($plants) }},
                    isChecking: false,
                    isSubmitting: false,
                    displayDept: '',
                
                    updateDepartment() {
                        const selectedPlant = this.plantsData.find(p => p.id == this.formData.plant_id);
                        if (selectedPlant) {
                            const map = {
                                'Plant A': 'Low Voltage',
                                'Plant B': 'Medium Voltage',
                                'Plant C': 'Low Voltage',
                                'Plant D': 'Medium Voltage',
                                'Plant E': 'FO',
                                'RM 1': 'SC',
                                'RM 2': 'SC',
                                'RM 3': 'SC',
                                'RM 5': 'SC',
                                'RM Office': 'SC',
                                'QC FO': 'QR',
                                'QC LAB': 'QR',
                                'QC LV': 'QR',
                                'QC MV': 'QR',
                                'Konstruksi': 'FH',
                                'MC Cable': 'Low Voltage',
                                'Autowire': 'Low Voltage',
                                'Workshop Electric': 'MT',
                                'Gudang Jadi': 'SS',
                                'Plant Tools': 'PE',
                                'Planning': 'Planning'
                            };
                            if (map[selectedPlant.name]) this.formData.department = map[selectedPlant.name];
                        }
                    },
                
                    async checkNik() {
                        if (!this.formData.nik) return;
                        this.isChecking = true;
                        try {
                            const response = await axios.post('{{ route('ga.check-employee') }}', { nik: this.formData.nik });
                            if (response.data.success) {
                                this.formData.manual_requester_name = response.data.data.name;
                                this.displayDept = response.data.data.division;
                                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
                                    .fire({ icon: 'success', title: 'Ditemukan: ' + this.formData.manual_requester_name });
                            }
                        } catch (e) {
                            Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'NIK Tidak Ditemukan' });
                            this.formData.manual_requester_name = '';
                            this.displayDept = '';
                        } finally { this.isChecking = false; }
                    },
                
                    openConfirm() {
                        window.gaFormData = JSON.parse(JSON.stringify(this.formData));
                        $dispatch('open-confirm-modal');
                        this.showConfirmModal = true;
                    },
                    handleFile(e) {}
                }">

                {{-- [FIX] LOADING OVERLAY --}}
                <div x-show="isSubmitting" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    class="absolute inset-0 z-[100] bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center">

                    <div class="flex space-x-3 mb-6">
                        <div class="w-5 h-5 bg-[#1E3A5F] rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                        <div class="w-5 h-5 bg-yellow-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                        <div class="w-5 h-5 bg-[#1E3A5F] rounded-full animate-bounce"></div>
                    </div>

                    <h3 class="text-xl font-bold text-slate-800">Mohon Tunggu</h3>
                    <p class="text-slate-500 text-sm mt-1">Sedang membuat tiket Anda...</p>
                </div>

                {{-- Header --}}
                <div
                    class="bg-gradient-to-r from-[#1E3A5F] to-slate-700 px-8 py-7 flex justify-between items-center relative z-10">
                    <h3 class="text-xl font-bold text-white uppercase tracking-wide flex items-center gap-3">
                        <span class="bg-yellow-400 text-slate-900 px-3 py-1.5 text-xs font-black rounded-lg">NEW</span>
                        Create Work Order
                    </h3>
                    <button @click="showCreateModal = false"
                        class="text-white/60 hover:text-white rounded-full p-2.5 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Body Form --}}
                {{-- [FIX] setTimeout 500ms agar Loading sempat muncul sebelum browser reload --}}
                <form x-ref="createForm"
                    @submit-confirmed.window="isSubmitting = true; setTimeout(() => $refs.createForm.submit(), 500)"
                    action="{{ route('ga.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="p-8 space-y-6">
                        {{-- SECTION 1 --}}
                        <div class="bg-slate-50 p-5 rounded-sm border border-slate-200">
                            <label
                                class="block text-xs font-black text-slate-400 uppercase mb-4 tracking-widest">IDENTITAS
                                PELAPOR</label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Input NIK --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-700 uppercase mb-1">NIK <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        {{-- [FIX] name="requester_nik" agar terbaca di Controller --}}
                                        <input type="text" name="requester_nik" x-model="formData.nik"
                                            @keydown.enter.prevent="checkNik()" @blur="checkNik()"
                                            class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11 placeholder-slate-300"
                                            placeholder="Ketik NIK..." required>

                                        {{-- Loading Spinner --}}
                                        <div x-show="isChecking" class="absolute right-3 top-3">
                                            <svg class="animate-spin h-5 w-5 text-slate-900" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Tekan Enter untuk cari data</p>
                                </div>

                                {{-- Input Nama --}}
                                <div>
                                    <label class="text-xs font-bold text-slate-700 uppercase mb-1">Nama & Dept</label>

                                    {{-- Visual Display (Gabungan Nama - Dept) --}}
                                    <input type="text"
                                        :value="formData.manual_requester_name ? (formData.manual_requester_name + ' - ' +
                                            displayDept) : '-'"
                                        readonly
                                        class="w-full bg-slate-200 border-2 border-slate-200 text-slate-500 font-bold text-sm h-11 cursor-not-allowed mb-2">

                                    {{-- [FIX] Hidden Input yang BENAR untuk dikirim ke Controller --}}
                                    {{-- name="requester_name" (bukan manual_requester_name) --}}
                                    <input type="hidden" name="requester_name" :value="formData.manual_requester_name">

                                    {{-- [FIX] Hidden Input untuk Departemen Pelapor --}}
                                    {{-- Agar kolom requester_department di database terisi otomatis --}}
                                    <input type="hidden" name="requester_department" :value="displayDept">
                                </div>
                            </div>
                        </div>

                        {{-- SECTION 2 --}}
                        <div class="bg-slate-50 p-5 rounded-sm border border-slate-200 mt-6">
                            <label class="block text-xs font-black text-slate-400 uppercase mb-4 tracking-widest">Target
                                Area Kerja</label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">Lokasi</label>
                                    <select name="plant_id" x-model="formData.plant_id" @change="updateDepartment()"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11"
                                        required>
                                        <option value="">-- PILIH LOKASI --</option>
                                        @foreach ($plants as $plant)
                                            <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">Department</label>
                                    <select name="department" x-model="formData.department"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold bg-white h-11"
                                        required>
                                        <option value="">-- PILIH DEPT --</option>
                                        <option value="Low Voltage">Low Voltage</option>
                                        <option value="Medium Voltage">Medium Voltage</option>
                                        <option value="IT">IT</option>
                                        <option value="FH">FH</option>
                                        <option value="PE">PE</option>
                                        <option value="MT">MT</option>
                                        <option value="GA">GA</option>
                                        <option value="FO">FO</option>
                                        <option value="SS">SS</option>
                                        <option value="SC">SC</option>
                                        <option value="RM">RM</option>
                                        <option value="QR">QR</option>
                                        <option value="FA">FA</option>
                                        <option value="HC">HC</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Planning">Planning</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- SECTION 3 --}}
                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Target Selesai
                                    (Opsional)</label>
                                <input type="date" name="target_completion_date"
                                    x-model="formData.target_completion_date"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11 text-slate-600">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Jenis Permintaan <span
                                        class="text-red-500">*</span></label>
                                <select name="parameter_permintaan" x-model="formData.parameter_permintaan"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11"
                                    required>
                                    <option value="">-- PILIH --</option>
                                    <option value="KEBERSIHAN">Kebersihan</option>
                                    <option value="PEMELIHARAAN">Pemeliharaan</option>
                                    <option value="PERBAIKAN">Perbaikan</option>
                                    <option value="PEMBUATAN BARU">Pembuatan Baru</option>
                                    <option value="PERIZINAN">Perizinan</option>
                                    <option value="RESERVASI">Reservasi</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Kategori Bobot</label>
                                <select name="category" x-model="formData.category"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11">
                                    <option value="RINGAN">Ringan</option>
                                    <option value="SEDANG">Sedang</option>
                                    <option value="BERAT">Berat</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Status
                                    Permintaan</label>
                                <select name="status_permintaan" x-model="formData.status_permintaan"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-bold h-11">
                                    <option value="OPEN">Open</option>
                                    <option value="SUDAH DIRENCANAKAN">Sudah Direncanakan</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Uraian Pekerjaan <span
                                    class="text-red-500">*</span></label>
                            <textarea name="description" x-model="formData.description" rows="3"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 rounded-sm text-sm font-medium"
                                placeholder="Deskripsi..." required></textarea>
                        </div>

                        <div class="mt-4">
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Foto Bukti
                                (Opsional)</label>
                            <input type="file" name="photo"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-black file:uppercase file:bg-slate-900 file:text-white hover:file:bg-slate-700 cursor-pointer border border-slate-300 rounded-sm">
                        </div>

                        {{-- Footer --}}
                        <div class="px-8 py-5 bg-slate-50 flex flex-row-reverse gap-3 border-t border-slate-200 mt-6">
                            <button type="button" @click="openConfirm()"
                                class="bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-500 text-slate-900 hover:from-yellow-500 px-8 py-3.5 rounded-xl font-bold uppercase tracking-wider shadow-lg hover:scale-105 active:scale-95 transition-all">Kirim
                                Tiket</button>
                            <button type="button" @click="showCreateModal = false"
                                class="bg-white border-2 border-slate-200 text-slate-600 hover:border-slate-400 px-7 py-3.5 rounded-xl font-bold uppercase tracking-wide shadow-sm hover:shadow-md transition-all">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
