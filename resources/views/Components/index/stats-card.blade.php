@props(['countTotal', 'countPending', 'countInProgress', 'countCompleted'])
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10" x-show="show"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">

    @php
        $cards = [
            [
                'title' => 'Total Tiket',
                'value' => $countTotal,
                'color' => 'slate',
                'icon_path' =>
                    'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
            ],
            [
                'title' => 'Pending',
                'value' => $countPending,
                'color' => 'amber',
                'icon_path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'title' => 'On Progress',
                'value' => $countInProgress,
                'color' => 'blue',
                'icon_path' =>
                    'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
            ],
            [
                'title' => 'Selesai',
                'value' => $countCompleted,
                'color' => 'emerald',
                'icon_path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];
    @endphp

    @foreach ($cards as $card)
        <div
            class="status-card relative bg-white border-none rounded-2xl shadow-md hover:shadow-xl overflow-hidden group hover:-translate-y-2 transition-all duration-300 border border-{{ $card['color'] }}-100">
            {{-- Gradient Background Accent --}}
            <div
                class="absolute top-0 right-0 w-24 h-24 bg-{{ $card['color'] }}-50 rounded-full -mr-12 -mt-12 group-hover:scale-150 transition-transform duration-500 opacity-0 group-hover:opacity-100">
            </div>
            {{-- Icon Pattern --}}
            <div
                class="absolute top-0 right-0 p-5 opacity-5 group-hover:opacity-10 group-hover:scale-110 transition-transform duration-500">
                <svg class="w-24 h-24 text-{{ $card['color'] }}-900" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="{{ $card['icon_path'] }}" />
                </svg>
            </div>
            <div class="p-7 relative z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3
                        class="text-xs font-bold text-{{ $card['color'] }}-600 uppercase tracking-widest pl-3 border-l-3 border-{{ $card['color'] }}-500">
                        {{ $card['title'] }}
                    </h3>
                    <div
                        class="w-10 h-10 rounded-lg bg-{{ $card['color'] }}-100 flex items-center justify-center group-hover:bg-{{ $card['color'] }}-200 transition-colors">
                        <svg class="w-5 h-5 text-{{ $card['color'] }}-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $card['icon_path'] }}" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline">
                    <span class="text-4xl font-bold text-slate-800 tracking-tight">{{ $card['value'] }}</span>
                    <span class="ml-2 text-xs font-semibold text-slate-400">Tiket</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
