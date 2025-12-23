<template x-teleport="body">
    <div x-show="showConfirmModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm transition-opacity" @click="showConfirmModal = false">
        </div>
        <div class="flex min-h-full items-center justify-center p-4 relative z-10">
            <div class="bg-white rounded-sm shadow-2xl max-w-sm w-full p-0 overflow-hidden transform transition-all">
                <div class="h-2 bg-yellow-400 w-full"></div>
                <div class="p-6">
                    <h3 class="text-xl font-black text-slate-900 uppercase mb-6 tracking-wide text-center">
                        Konfirmasi Data</h3>
                    <div class="bg-slate-50 p-4 mb-6 border border-slate-200 text-sm space-y-3">
                        <div class="flex justify-between border-b border-slate-200 pb-2"><span
                                class="font-bold text-slate-400 text-xs uppercase">Lokasi</span><span
                                class="font-bold text-slate-900 text-right" x-text="form.plant_name"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2"><span
                                class="font-bold text-slate-400 text-xs uppercase">Dept</span><span
                                class="font-bold text-slate-900 text-right" x-text="form.department"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2"><span
                                class="font-bold text-slate-400 text-xs uppercase">Bobot</span><span
                                class="font-bold text-slate-900 text-right" x-text="form.category"></span>
                        </div>
                        <div class="pt-1"><span
                                class="font-bold text-slate-400 text-xs uppercase block mb-1">Uraian</span><span
                                class="font-medium text-slate-800 leading-snug" x-text="form.description"></span></div>
                    </div>
                    <div class="flex flex-col gap-3">
                        <button @click="submitForm()"
                            class="w-full bg-slate-900 text-white py-3.5 rounded-sm font-black uppercase tracking-wider hover:bg-slate-800 transition shadow-lg">Ya,
                            Proses</button>
                        <button @click="showConfirmModal = false"
                            class="w-full bg-white border-2 border-slate-200 text-slate-500 py-3.5 rounded-sm font-bold uppercase tracking-wider hover:bg-slate-50 transition">Periksa
                            Lagi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
