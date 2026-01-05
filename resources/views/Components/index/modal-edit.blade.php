<template x-teleport="body">
    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="showEditModal = false">
        </div>

        <div class="flex min-h-full items-center justify-center p-4 relative z-10">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-100 transform transition-all">

                {{-- Header --}}
                <div
                    class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 flex justify-between items-center border-b border-slate-600">
                    <h3 class="text-lg font-black text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Update Progress
                    </h3>
                    <div class="text-xs font-mono text-yellow-400 bg-slate-900/50 px-3 py-1 rounded border border-slate-600"
                        x-text="editForm.ticket_num"></div>
                </div>

                {{-- Form mengarah ke update-status --}}
                <form :action="'/ga/' + editForm.id + '/update-status'" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="p-6 space-y-6">

                        {{-- 1. Nama PIC (Wajib diisi saat update apapun) --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                                Update Oleh (PIC) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="processed_by_name"
                                class="w-full border-2 border-slate-200 focus:border-slate-800 focus:ring-0 rounded-lg text-sm font-bold py-2 px-3 transition-colors"
                                placeholder="Nama Anda..." required :value="editForm.processed_by_name">
                            {{-- Auto fill jika sudah ada --}}
                        </div>

                        {{-- 2. Pilihan Status Baru --}}
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Status
                                Pekerjaan</label>
                            <select name="status" x-model="editForm.status"
                                class="w-full border-2 border-slate-300 rounded-lg text-sm font-bold h-10 focus:border-blue-500 focus:ring-0">
                                <option value="in_progress">IN PROGRESS (Sedang Dikerjakan)</option>
                                <option value="completed">COMPLETED (Selesai)</option>
                                <option value="cancelled">CANCELLED (Dibatalkan)</option>
                            </select>
                        </div>

                        {{-- 3. Update Departemen (Opsional) --}}
                        {{-- 3. Update Departemen (Opsional) --}} <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">
                                Pindahkan Dept <span
                                    class="text-slate-400 font-normal italic lowercase">(opsional)</span>
                            </label>
                            <select name="department" x-model="editForm.department"
                                class="w-full border border-slate-300 rounded-lg text-xs font-semibold h-9 bg-white text-slate-600">

                                <option value="">-- Tetap di Dept Saat Ini --</option>

                                {{-- DAFTAR DEPARTMENT SESUAI PERMINTAAN --}}
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

                        {{-- 4. Kondisional: Jika Status COMPLETED --}}
                        <div x-show="editForm.status == 'completed'" x-transition
                            class="bg-emerald-50 p-4 rounded-lg border border-emerald-200">
                            <label class="block text-xs font-bold text-emerald-800 uppercase mb-2">
                                Upload Foto Bukti Selesai <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="completion_photo" accept="image/*"
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:uppercase file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer bg-white border border-emerald-100 rounded-lg">
                            <p class="text-[10px] text-emerald-600 mt-1 italic">*Wajib upload foto hasil pekerjaan.</p>
                        </div>

                        {{-- 5. Kondisional: Jika Status IN PROGRESS (Revisi Target) --}}
                        <div x-show="editForm.status == 'in_progress'" x-transition>
                            <label class="block text-xs font-bold text-blue-700 uppercase mb-1">
                                Revisi Target Penyelesaian <span
                                    class="text-slate-400 font-normal italic lowercase">(jika
                                    mundur)</span>
                            </label>
                            <input type="text" name="target_date" x-model="editForm.target_date"
                                class="w-full border border-blue-200 focus:border-blue-500 rounded-lg date-picker text-sm font-medium h-10 px-3"
                                placeholder="Pilih Tanggal Baru..." x-init="flatpickr($el, { dateFormat: 'Y-m-d', minDate: 'today' })">
                        </div>
                    </div>

                    {{-- Footer Buttons --}}
                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl border-t border-slate-100">
                        <button type="button" @click="showEditModal = false"
                            class="px-5 py-2 bg-white text-slate-600 font-bold rounded-lg uppercase text-xs border border-slate-300 hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-slate-900 text-yellow-400 font-black rounded-lg uppercase text-xs shadow-lg hover:bg-slate-800 transition-transform transform hover:-translate-y-0.5">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>
