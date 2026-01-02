<template x-teleport="body">
    <div x-show="showDetailModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm transition-opacity" @click="showDetailModal = false">
        </div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl border-t-8 border-yellow-400 overflow-hidden"
                @click.outside="showDetailModal = false">

                {{-- Header --}}
                <div class="bg-slate-50 px-8 py-5 flex justify-between items-center border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Detail Tiket</h3>
                        <p class="text-xs text-slate-500 font-bold mt-1">Informasi lengkap permintaan pekerjaan</p>
                    </div>
                    <button @click="showDetailModal = false"
                        class="bg-white rounded-full p-2 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-all shadow-sm border border-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <template x-if="ticket">
                        <div class="space-y-8">

                            {{-- Section 1: Header Info (No Tiket & Status) --}}
                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-end border-b-2 border-slate-100 pb-6 gap-4">
                                <div>
                                    <span
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1 block">NOMOR
                                        TIKET</span>
                                    <p class="text-4xl font-black text-slate-900 font-mono tracking-tighter"
                                        x-text="ticket.ticket_num"></p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span
                                        class="px-5 py-2 bg-yellow-400 text-slate-900 font-black rounded-lg uppercase tracking-wide border border-yellow-500 shadow-sm text-sm"
                                        x-text="ticket.status.replace('_', ' ')"></span>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-wide">
                                        Dibuat: <span
                                            x-text="new Date(ticket.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Section 2: Informasi Detail (Grid) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 text-sm">

                                {{-- [BARU] Info Pelapor Lengkap (Nama + NIK + Dept) --}}
                                <div
                                    class="col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-2">Pelapor
                                        (Requester)</span>

                                    <div class="flex items-center gap-3">
                                        {{-- Icon Profile --}}
                                        <div
                                            class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 shadow-sm shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                        </div>

                                        {{-- Info Nama, NIK, Dept --}}
                                        <div class="overflow-hidden">
                                            <span class="font-bold text-slate-800 text-base block truncate"
                                                x-text="ticket.requester_name"></span>

                                            <div class="flex items-center flex-wrap gap-2 text-xs mt-0.5">
                                                {{-- NIK --}}
                                                <span
                                                    class="font-mono text-slate-500 bg-slate-200/60 px-1.5 rounded-[2px]"
                                                    x-text="ticket.requester_nik || '-'"></span>

                                                {{-- Departemen (Hanya muncul jika ada datanya) --}}
                                                <template x-if="ticket.requester_department">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-slate-300">|</span>
                                                        <span class="font-bold text-slate-600 uppercase tracking-tight"
                                                            x-text="ticket.requester_department"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Baris 1 --}}
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Lokasi
                                        (Plant)</span>
                                    <span class="font-bold text-slate-800 text-base flex items-center gap-2">
                                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span x-text="ticket.plant"></span>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Departemen
                                        Tujuan</span>
                                    <span class="font-bold text-slate-800 text-base" x-text="ticket.department"></span>
                                </div>

                                {{-- Baris 2 --}}
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Kategori
                                        Bobot</span>
                                    <span
                                        class="font-bold text-slate-800 text-base px-2 py-1 bg-slate-100 rounded inline-block"
                                        x-text="ticket.category"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Jenis
                                        Permintaan</span>
                                    <span class="font-bold text-slate-800 text-base"
                                        x-text="ticket.parameter_permintaan"></span>
                                </div>

                                {{-- [BARU] Target Selesai --}}
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Target
                                        Selesai</span>
                                    <span class="font-bold text-slate-800 text-base flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span
                                            x-text="ticket.target_completion_date ? new Date(ticket.target_completion_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'"></span>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Status
                                        Permintaan</span>
                                    <span class="font-bold text-slate-800 text-base"
                                        x-text="ticket.status_permintaan || '-'"></span>
                                </div>

                                {{-- Uraian --}}
                                <div class="col-span-1 md:col-span-2">
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-2">Uraian
                                        Pekerjaan</span>
                                    <div class="bg-yellow-50/50 p-5 border border-yellow-100 rounded-lg">
                                        <p class="font-medium text-slate-700 whitespace-pre-wrap leading-relaxed"
                                            x-text="ticket.description"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 3: Foto --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Foto Awal --}}
                                <template x-if="ticket.photo_path">
                                    <div>
                                        <span
                                            class="text-[10px] font-black text-slate-500 uppercase mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Foto Kondisi Awal
                                        </span>
                                        <a :href="'/storage/' + ticket.photo_path" target="_blank"
                                            class="block group relative overflow-hidden rounded-lg shadow-sm">
                                            <div
                                                class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all z-10">
                                            </div>
                                            <img :src="'/storage/' + ticket.photo_path"
                                                class="w-full h-48 object-cover transform group-hover:scale-105 transition-all duration-500"
                                                alt="Bukti Foto">
                                        </a>
                                    </div>
                                </template>

                                {{-- Foto Selesai --}}
                                <template x-if="ticket.photo_completed_path">
                                    <div>
                                        <span
                                            class="text-[10px] font-black text-green-600 uppercase mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Foto Penyelesaian
                                        </span>
                                        <a :href="'/storage/' + ticket.photo_completed_path" target="_blank"
                                            class="block group relative overflow-hidden rounded-lg shadow-md ring-2 ring-green-100">
                                            <div
                                                class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all z-10">
                                            </div>
                                            <img :src="'/storage/' + ticket.photo_completed_path"
                                                class="w-full h-48 object-cover transform group-hover:scale-105 transition-all duration-500"
                                                alt="Foto Selesai">
                                        </a>
                                    </div>
                                </template>
                            </div>

                            {{-- Section 4: History / Log --}}
                            <div class="mt-8 pt-6 border-t border-slate-200">
                                <h4
                                    class="font-bold text-slate-900 uppercase text-xs tracking-wider mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Riwayat Aktivitas
                                </h4>
                                <div class="relative border-l-2 border-slate-200 ml-2 space-y-6">
                                    <template x-for="h in ticket.histories">
                                        <div class="ml-6 relative">
                                            {{-- Dot --}}
                                            <div
                                                class="absolute -left-[31px] top-1 w-4 h-4 bg-white border-2 border-slate-300 rounded-full">
                                            </div>

                                            <div
                                                class="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-1">
                                                <span class="font-bold text-slate-800 text-sm"
                                                    x-text="h.action"></span>
                                                <span
                                                    class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded"
                                                    x-text="new Date(h.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-1 italic" x-text="h.description"></p>
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
