<template x-teleport="body">
    <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 relative z-10">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-100 transform transition-all">
                {{-- Header --}}
                <div
                    class="bg-gradient-to-r from-[#1E3A5F] to-slate-700 px-8 py-7 flex justify-between items-center border-b border-slate-200/50">
                    <h3 class="text-lg font-bold text-white uppercase tracking-wide flex items-center gap-2">
                        <span class="text-yellow-400">⚙</span> <span
                            x-text="editForm.status == 'pending' ? 'Approval Form' : 'Update Status'"></span>
                    </h3>
                    <div class="text-xs font-mono text-yellow-400 bg-slate-800/50 px-3 py-1.5 rounded-lg"
                        x-text="editForm.ticket_num"></div>
                </div>
                <form :action="'/ga/' + editForm.id + '/update-status'" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Admin
                                / PIC <span class="text-red-500">*</span></label>
                            <input type="text" name="admin_name"
                                class="w-full border-2 border-slate-300 focus:border-slate-900 focus:ring-0 rounded-sm text-sm font-bold py-2.5"
                                placeholder="Nama Anda..." required>
                        </div>

                        <template x-if="editForm.status == 'pending'">
                            <div x-data="{ decision: null }">
                                <div x-show="!decision" class="grid grid-cols-2 gap-4">
                                    <button type="button" @click="decision = 'accept'"
                                        class="bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-500 text-slate-900 hover:from-yellow-500 hover:to-amber-600 py-4 px-4 rounded-xl font-bold uppercase tracking-wider shadow-lg hover:shadow-xl transition-all hover:scale-105 active:scale-95">Accept</button>
                                    <button type="button" @click="decision = 'decline'"
                                        class="bg-white text-slate-600 hover:text-red-600 hover:border-red-400 hover:bg-red-50 py-4 px-4 rounded-xl font-bold uppercase tracking-wider border-2 border-slate-200 transition-all shadow-sm hover:shadow-md">Decline</button>
                                </div>
                                {{-- Decline Form --}}
                                <div x-show="decision == 'decline'"
                                    class="bg-red-50 p-5 rounded-xl border-l-4 border-red-500 mt-4">
                                    <h4 class="font-bold text-red-800 uppercase mb-4 text-xs">Konfirmasi
                                        Penolakan</h4>
                                    <div class="flex gap-3">
                                        <button type="submit" name="action" value="decline"
                                            class="bg-gradient-to-br from-red-600 to-red-700 text-white px-5 py-2.5 rounded-xl hover:from-red-700 hover:to-red-800 text-xs font-bold uppercase shadow-md hover:shadow-lg transition-all">Tolak
                                            Tiket</button>
                                        <button type="button" @click="decision = null"
                                            class="text-slate-500 hover:text-slate-800 px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Batal</button>
                                    </div>
                                </div>
                                {{-- Accept Form --}}
                                <div x-show="decision == 'accept'"
                                    class="space-y-4 bg-yellow-50 p-6 rounded-xl border-l-4 border-yellow-400 mt-4">
                                    <h4
                                        class="font-black text-slate-900 uppercase border-b border-yellow-200 pb-2 mb-2 text-xs">
                                        Parameter Pengerjaan</h4>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Bobot</label>
                                        <select name="category" x-model="editForm.category"
                                            class="w-full border border-slate-300 rounded-sm text-xs font-bold h-9">
                                            <option value="RINGAN">Ringan</option>
                                            <option value="SEDANG">Sedang</option>
                                            <option value="BERAT">Berat</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Target
                                            Penyelesaian</label>
                                        <input type="text" name="target_date"
                                            class="w-full border border-slate-300 rounded-sm date-picker text-xs font-bold h-9"
                                            placeholder="Pilih Tanggal..." x-init="flatpickr($el, { dateFormat: 'Y-m-d', minDate: 'today' })">
                                    </div>
                                    <div class="flex gap-2 pt-2">
                                        <button type="submit" name="action" value="accept"
                                            class="bg-slate-900 text-white px-4 py-2 rounded-sm hover:bg-slate-800 text-xs font-black uppercase shadow-md">Simpan</button>
                                        <button type="button" @click="decision = null"
                                            class="bg-white border border-slate-300 text-slate-600 px-3 py-2 rounded-sm text-xs font-bold uppercase">Batal</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Logic Update (Ongoing) --}}
                        <template x-if="editForm.status != 'pending'">
                            <div class="space-y-4 bg-slate-50 p-5 border border-slate-200">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Status
                                        Baru</label>
                                    <select name="status" x-model="editForm.status"
                                        class="w-full border-2 border-slate-300 rounded-sm text-sm font-bold">
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                {{-- Dept Update --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Update
                                        Dept <span class="text-slate-400 font-normal italic">(Opsional)</span></label>
                                    <select name="department" x-model="editForm.department"
                                        class="w-full border-2 border-slate-300 rounded-sm text-sm font-semibold">
                                        <option value="">-- Tidak Berubah --</option>
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

                                <div x-show="editForm.status == 'completed'">
                                    <label class="block text-xs font-bold text-emerald-700 uppercase mb-1">Foto
                                        Bukti</label>
                                    <input type="file" name="completion_photo"
                                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-bold file:uppercase file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 border border-emerald-200 bg-white">
                                </div>

                                <div x-show="editForm.status == 'in_progress'">
                                    <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Revisi
                                        Target</label>
                                    <input type="text" name="target_date" x-model="editForm.target_date"
                                        class="w-full border-2 border-blue-200 rounded-sm date-picker text-sm"
                                        placeholder="Update Tanggal..." x-init="flatpickr($el, { dateFormat: 'Y-m-d', minDate: 'today' })">
                                </div>
                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="submit"
                                        class="bg-yellow-400 text-slate-900 px-5 py-2 rounded-sm font-black uppercase text-xs shadow-sm">Simpan</button>
                                    <button type="button" @click="showEditModal = false"
                                        class="bg-white border border-slate-300 text-slate-600 px-4 py-2 rounded-sm font-bold uppercase text-xs">Batal</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
