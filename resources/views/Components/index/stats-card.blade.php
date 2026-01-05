@props(['countTotal', 'countPending', 'countInProgress', 'countCompleted'])
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10" x-show="show"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0">

    @php
        $cards = [
            [
                'title' => 'Total Tiket',
                'value' => $countTotal,
                'color' => 'white',
                'bg' => 'bg-gradient-to-br from-gray-50 to-gray-100',
                'icon_bg' => 'bg-gray-200',
                'icon_color' => 'text-gray-700',
                'text_color' => 'text-gray-700',
                'border' => 'border-gray-200',
                'accent' => 'bg-gray-100',
                'icon_path' =>
                    'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
            ],
            [
                'title' => 'Pending',
                'value' => $countPending,
                'color' => 'yellow',
                'bg' => 'bg-gradient-to-br from-yellow-400 to-orange-500',
                'icon_bg' => 'bg-yellow-300/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-yellow-300',
                'accent' => 'bg-yellow-300/20',
                'icon_path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'title' => 'On Progress',
                'value' => $countInProgress,
                'color' => 'blue',
                'bg' => 'bg-gradient-to-br from-blue-500 to-blue-700',
                'icon_bg' => 'bg-blue-400/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-blue-400',
                'accent' => 'bg-blue-300/20',
                'icon_path' =>
                    'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
            ],
            [
                'title' => 'Selesai',
                'value' => $countCompleted,
                'color' => 'green',
                'bg' => 'bg-gradient-to-br from-red-500 to-red-700',
                'icon_bg' => 'bg-red-400/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-red-400',
                'accent' => 'bg-red-300/20',
                'icon_path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];
    @endphp

    @foreach ($cards as $card)
        <div
            class="status-card relative {{ $card['bg'] }} border-none rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden group hover:-translate-y-2 transition-all duration-300 border {{ $card['border'] }}">
            {{-- Gradient Background Accent --}}
            <div
                class="absolute top-0 right-0 w-32 h-32 {{ $card['accent'] }} rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500">
            </div>
            {{-- Icon Pattern Background --}}
            <div
                class="absolute top-0 right-0 p-5 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
                <svg class="w-28 h-28 {{ $card['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="{{ $card['icon_path'] }}" />
                </svg>
            </div>
            <div class="p-7 relative z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3
                        class="text-xs font-bold {{ $card['text_color'] }} uppercase tracking-widest pl-3 border-l-4 {{ $card['color'] === 'white' ? 'border-gray-400' : 'border-white/50' }}">
                        {{ $card['title'] }}
                    </h3>
                    <div
                        class="w-12 h-12 rounded-xl {{ $card['icon_bg'] }} flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 backdrop-blur-sm">
                        <svg class="w-6 h-6 {{ $card['icon_color'] }}" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $card['icon_path'] }}" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline">
                    <span
                        class="text-5xl font-black {{ $card['text_color'] }} tracking-tight drop-shadow-sm">{{ $card['value'] }}</span>
                    <span class="ml-3 text-sm font-semibold {{ $card['text_color'] }} opacity-80">Tiket</span>
                </div>
            </div>
            {{-- Shine effect on hover --}}
            <div
                class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none bg-gradient-to-tr from-transparent via-white/10 to-transparent transform -translate-x-full group-hover:translate-x-full transition-transform duration-1000">
            </div>
        </div>
    @endforeach
</div>
