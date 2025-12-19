@props(['plants'])
<template x-teleport="body">
    <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="showCreateModal = false">
        </div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all border border-slate-100">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-[#1E3A5F] to-slate-700 px-8 py-7 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white uppercase tracking-wide flex items-center gap-3">
                        <span class="bg-yellow-400 text-slate-900 px-3 py-1.5 text-xs font-black rounded-lg">NEW</span>
                        Create Work Order
                    </h3>
                    <button @click="showCreateModal = false"
                        class="text-white/60 hover:text-white hover:bg-white/10 rounded-full p-2.5 transition-all duration-200"><svg
                            class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg></button>
                </div>
                {{-- Body (Keep logic same) --}}
                <form x-ref="createForm" action="{{ route('ga.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-8 space-y-6">
                        {{-- DATE & TIME DISPLAY --}}
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase mb-1 tracking-wider">Tanggal</label>
                                <input type="text" x-model="currentDate" readonly
                                    class="w-full bg-slate-100 border-0 border-b-2 border-slate-300 font-mono text-sm font-bold text-slate-800 focus:ring-0">
                            </div>
                            <div>
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase mb-1 tracking-wider">Jam</label>
                                <input type="text" x-model="currentTime" readonly
                                    class="w-full bg-slate-100 border-0 border-b-2 border-slate-300 font-mono text-sm font-bold text-slate-800 focus:ring-0">
                            </div>
                        </div>

                        {{-- REQUESTOR NAME --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Nama
                                Requestor <span class="text-red-500">*</span></label>
                            <input type="text" name="manual_requester_name"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold placeholder-slate-300 py-3"
                                placeholder="Masukkan Nama Lengkap..." required>
                        </div>

                        {{-- LOCATION GROUP --}}
                        <div class="bg-slate-50 p-5 rounded-sm border border-slate-200">
                            <label class="block text-xs font-black text-slate-400 uppercase mb-4 tracking-widest">Area
                                Kerja</label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">Lokasi</label>
                                    <select name="plant_id" id="plantSelect" x-model="form.plant"
                                        @change="updateDepartment()"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold h-11"
                                        required>
                                        <option value="">-- PILIH LOKASI --</option>
                                        @foreach ($plants as $plant)
                                            <option value="{{ $plant->id }}">{{ $plant->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-600 uppercase mb-1">Department</label>
                                    <select name="department" x-model="form.department"
                                        class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold bg-white h-11"
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
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- PARAMETERS --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Kategori
                                    Bobot</label>
                                <select name="category" x-model="form.category"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold h-11">
                                    <option value="RINGAN">Ringan</option>
                                    <option value="SEDANG">Sedang</option>
                                    <option value="BERAT">Berat</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700 uppercase mb-1">Jenis
                                    Permintaan <span class="text-red-500">*</span></label>
                                <select name="parameter_permintaan" x-model="form.parameter_permintaan"
                                    class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold h-11"
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

                        <div>
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Status
                                Permintaan</label>
                            <select name="status_permintaan" x-model="form.status_permintaan"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold h-11">
                                <option value="">-- Pilih --</option>
                                <option value="OPEN">Open</option>
                                <option value="SUDAH DIRENCANAKAN">Sudah Direncanakan</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Uraian Pekerjaan
                                <span class="text-red-500">*</span></label>
                            <textarea name="description" x-model="form.description" rows="3"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-medium"
                                placeholder="Deskripsikan detail pekerjaan secara lengkap..." required></textarea>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-700 uppercase mb-1">Foto Bukti
                                (Opsional)</label>
                            <input type="file" name="photo" @change="handleFile"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-black file:uppercase file:bg-slate-900 file:text-white hover:file:bg-slate-700 cursor-pointer border border-slate-300 rounded-sm">
                        </div>
                    </div>
                    <div class="px-8 py-5 bg-slate-50 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="button" @click="showConfirmModal = true"
                            class="bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-500 text-slate-900 hover:from-yellow-500 hover:to-amber-600 px-8 py-3.5 rounded-xl font-bold uppercase tracking-wider shadow-lg hover:shadow-xl transition-all hover:scale-105 active:scale-95">Kirim
                            Tiket</button>
                        <button type="button" @click="showCreateModal = false"
                            class="bg-white border-2 border-slate-200 text-slate-600 hover:border-slate-400 hover:text-slate-800 hover:bg-slate-50 px-7 py-3.5 rounded-xl font-bold uppercase tracking-wide transition-all shadow-sm hover:shadow-md">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
