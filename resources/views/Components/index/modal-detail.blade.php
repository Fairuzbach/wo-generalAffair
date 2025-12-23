<template x-teleport="body">
    <div x-show="showDetailModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm" @click="showDetailModal = false">
        </div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white rounded-sm shadow-2xl border-t-8 border-yellow-400">
                {{-- Header --}}
                <div class="bg-slate-100 px-6 py-4 flex justify-between items-center border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-wider">Detail Tiket
                    </h3>
                    <button @click="showDetailModal = false"
                        class="text-slate-400 hover:text-red-500 text-2xl font-bold">&times;</button>
                </div>
                <div class="p-8 max-h-[80vh] overflow-y-auto">
                    <template x-if="ticket">
                        <div>
                            {{-- Ticket Info --}}
                            <div class="flex justify-between items-end border-b-2 border-slate-100 pb-6 mb-6">
                                <div>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">NO
                                        TIKET</span>
                                    <p class="text-4xl font-black text-slate-900 font-mono tracking-tighter mt-1"
                                        x-text="ticket.ticket_num"></p>
                                </div>
                                <span
                                    class="px-4 py-2 bg-yellow-400 text-slate-900 font-black rounded-sm uppercase tracking-wide border-2 border-slate-900 text-sm"
                                    x-text="ticket.status.replace('_',' ')"></span>
                            </div>
                            {{-- Grid --}}
                            <div class="grid grid-cols-2 gap-x-8 gap-y-6 mb-8 text-sm">
                                <div><span
                                        class="text-[10px] font-black text-slate-400 uppercase block">Lokasi</span><span
                                        class="font-bold text-slate-800 text-base" x-text="ticket.plant"></span></div>
                                <div><span
                                        class="text-[10px] font-black text-slate-400 uppercase block">Dept</span><span
                                        class="font-bold text-slate-800 text-base" x-text="ticket.department"></span>
                                </div>
                                <div><span
                                        class="text-[10px] font-black text-slate-400 uppercase block">Bobot</span><span
                                        class="font-bold text-slate-800 text-base" x-text="ticket.category"></span>
                                </div>
                                <div><span
                                        class="text-[10px] font-black text-slate-400 uppercase block">Jenis</span><span
                                        class="font-bold text-slate-800 text-base"
                                        x-text="ticket.parameter_permintaan"></span></div>
                                <div class="col-span-2"><span
                                        class="text-[10px] font-black text-slate-400 uppercase block mb-1">Uraian</span>
                                    <p class="bg-slate-50 p-4 border border-slate-200 rounded-sm font-medium text-slate-700 whitespace-pre-wrap"
                                        x-text="ticket.description"></p>
                                </div>
                            </div>
                            {{-- Photos Logic (Same as original) --}}
                            <div class="grid grid-cols-2 gap-4">
                                <template x-if="ticket.photo_path">
                                    <div>
                                        <span class="text-xs font-bold text-slate-500 uppercase mb-2 block">Foto
                                            Awal</span>
                                        <a :href="'/storage/' + ticket.photo_path" target="_blank"><img
                                                :src="'/storage/' + ticket.photo_path"
                                                class="w-full h-32 object-cover border rounded-sm hover:opacity-90"></a>
                                    </div>
                                </template>
                                <template x-if="ticket.photo_completed_path">
                                    <div>
                                        <span class="text-xs font-bold text-green-600 uppercase mb-2 block">Foto
                                            Selesai</span>
                                        <a :href="'/storage/' + ticket.photo_completed_path" target="_blank"><img
                                                :src="'/storage/' + ticket.photo_completed_path"
                                                class="w-full h-32 object-cover border-2 border-green-400 rounded-sm hover:opacity-90"></a>
                                    </div>
                                </template>
                            </div>
                            {{-- History --}}
                            <div class="mt-6 pt-4 border-t border-slate-200">
                                <h4 class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-3">
                                    Log Aktivitas</h4>
                                <div class="space-y-2">
                                    <template x-for="h in ticket.histories">
                                        <div class="flex gap-3 text-xs">
                                            <div class="font-mono font-bold text-slate-400"
                                                x-text="new Date(h.created_at).toLocaleDateString('id-ID')">
                                            </div>
                                            <div><span class="font-bold text-slate-900" x-text="h.action"></span> <span
                                                    class="text-slate-500" x-text="'- ' + h.description"></span></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
