<div x-show="showDetailModal" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm transition-opacity" @click="showDetailModal = false">
    </div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl border-t-8 border-yellow-400 overflow-hidden"
            @click.away="showDetailModal = false">

            {{-- Header --}}
            <div class="bg-slate-50 px-8 py-5 flex justify-between items-center border-b border-slate-200">
                <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Detail Tiket</h3>
                    <p class="text-xs text-slate-500 font-bold mt-1">Informasi lengkap permintaan pekerjaan</p>
                </div>
                <button @click="showDetailModal = false"
                    class="bg-white rounded-full p-2 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-all shadow-sm border border-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
                <template x-if="ticket">
                    <div class="space-y-8">
                        {{-- Nomor Tiket --}}
                        <div class="flex justify-between items-end border-b-2 pb-6">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase mb-1 block">NOMOR TIKET</span>
                                <p class="text-4xl font-black text-slate-900 font-mono" x-text="ticket.ticket_num"></p>
                            </div>
                            <div>
                                <span
                                    class="px-5 py-2 bg-yellow-400 text-slate-900 font-black rounded-lg uppercase text-sm"
                                    x-text="ticket.status.replace('_', ' ')"></span>
                            </div>
                        </div>

                        {{-- Info Detail --}}
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Pelapor</span>
                                <p class="font-bold text-slate-800" x-text="ticket.requester_name"></p>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Department</span>
                                <p class="font-bold text-slate-800" x-text="ticket.requester_department || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Lokasi</span>
                                <p class="font-bold text-slate-800"
                                    x-text="ticket.plant_info ? ticket.plant_info.name : (ticket.plant || '-')"></p>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Kategori</span>
                                <p class="font-bold text-slate-800" x-text="ticket.category"></p>
                            </div>
                            <div class="col-span-2">
                                <span class="text-xs font-bold text-slate-400 uppercase block mb-2">Deskripsi</span>
                                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                                    <p class="text-slate-700" x-text="ticket.description"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Tutup --}}
                        <div class="flex justify-end pt-4 border-t">
                            <button @click="showDetailModal = false"
                                class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg">
                                Tutup
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
