@props(['workOrders'])
<div class="bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-900">
                <tr>
                    <th class="px-6 py-4 w-10"><input type="checkbox" @change="toggleSelectAll()"
                            :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(id))"
                            class="rounded-sm border-slate-600 bg-slate-700 text-yellow-400 focus:ring-offset-slate-900 focus:ring-yellow-400 cursor-pointer">
                    </th>
                    @foreach (['Tiket', 'Pelapor', 'Lokasi / Dept', 'Parameter', 'Bobot', 'Uraian', 'Diterima Oleh', 'Status', 'Aksi'] as $head)
                        <th
                            class="px-6 py-4 text-left text-[11px] font-black text-white uppercase tracking-widest {{ $head == 'Tiket' ? 'text-yellow-400' : '' }}">
                            {{ $head }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                @forelse ($workOrders as $item)
                    <tr class="hover:bg-yellow-50/50 transition-colors duration-150 group">
                        <td class="px-6 py-4"><input type="checkbox" value="{{ (string) $item->id }}" x-model="selected"
                                class="rounded-sm border-slate-300 text-slate-900 focus:ring-yellow-400 cursor-pointer" />
                        </td>

                        {{-- Tiket --}}
                        <td class="px-6 py-4">
                            <div
                                class="text-sm font-black text-slate-900 font-mono group-hover:text-blue-600 transition-colors">
                                {{ $item->ticket_num }}</div>
                            <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase">
                                {{ $item->created_at->format('d M Y') }}</div>
                        </td>

                        <td class="px-6 py-4 text-xs font-bold text-slate-700">{{ $item->requester_name }}
                        </td>

                        {{-- Lokasi --}}
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1 items-start">
                                @if ($item->plant)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-tight">LOC:
                                        {{ $item->plant }}</span>
                                @endif
                                @if ($item->department)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-800 text-white uppercase tracking-tight">DEPT:
                                        {{ $item->department }}</span>
                                @endif
                                @if (!$item->plant && !$item->department)
                                    <span class="text-xs text-slate-300 italic">-</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-xs font-bold text-slate-600 uppercase">
                            {{ $item->parameter_permintaan }}</td>

                        {{-- Bobot --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $catDisplay = match ($item->category) {
                                    'HIGH' => 'BERAT',
                                    'MEDIUM' => 'SEDANG',
                                    'LOW' => 'RINGAN',
                                    default => $item->category,
                                };
                                $catColor = match ($catDisplay) {
                                    'BERAT' => 'text-red-700 bg-red-50 border-red-200',
                                    'SEDANG' => 'text-yellow-700 bg-yellow-50 border-yellow-200',
                                    default => 'text-green-700 bg-green-50 border-green-200',
                                };
                            @endphp
                            <span
                                class="px-2 py-1 text-[10px] font-black rounded-sm border {{ $catColor }} uppercase tracking-wide">{{ $catDisplay }}</span>
                        </td>

                        <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate font-medium">
                            {{ Str::limit($item->description, 35) }}</td>

                        {{-- PIC --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($item->processed_by_name)
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-[10px] font-black border border-slate-600">
                                        {{ substr($item->processed_by_name, 0, 1) }}</div>
                                    <span
                                        class="text-xs font-bold text-slate-700 uppercase">{{ $item->processed_by_name }}</span>
                                </div>
                            @else
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide border border-dashed border-slate-300 px-2 py-1 rounded-sm">Menunggu</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClass = match ($item->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <span
                                class="px-3 py-1 text-[10px] font-black uppercase rounded-sm border {{ $statusClass }} tracking-wider">{{ str_replace('_', ' ', $item->status) }}</span>
                        </td>

                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <button
                                @click='ticket = @json($item); ticket.user_name = "{{ $item->user->name ?? 'User' }}"; showDetailModal=true'
                                class="text-slate-900 hover:text-yellow-600 font-bold mr-3 underline decoration-2 decoration-yellow-400 underline-offset-4 hover:decoration-slate-900 transition-all text-xs uppercase tracking-wide">Detail</button>
                            @if (in_array(auth()->user()->role, ['ga.admin']))
                                <button @click='openEditModal(@json($item))'
                                    class="text-slate-400 hover:text-slate-900 font-bold transition text-xs uppercase tracking-wide">Update</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span class="text-slate-500 font-bold uppercase tracking-wide">Tidak ada
                                    data ditemukan</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Pagination with custom styling --}}
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        {{ $workOrders->appends(request()->all())->links() }}
    </div>
</div>
