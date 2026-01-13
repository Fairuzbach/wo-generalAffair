@props(['workOrders'])

<div class="bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900">
                <tr>
                    <th class="px-6 py-4 w-10">
                        {{-- Checkbox Select All --}}
                        <input type="checkbox" @change="toggleSelectAll()"
                            :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(String(id)))"
                            class="rounded-sm border-slate-600 bg-slate-700 text-yellow-400 focus:ring-offset-slate-900 focus:ring-yellow-400 cursor-pointer">
                    </th>

                    @foreach (['Tiket', 'Pelapor', 'Lokasi / Dept', 'Parameter', 'Bobot', 'Uraian', 'Diterima Oleh', 'Status', 'Aksi'] as $head)
                        <th
                            class="px-6 py-4 text-left text-[11px] font-black text-white uppercase tracking-widest {{ $head == 'Tiket' ? 'text-yellow-400' : '' }}">
                            {{ $head }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-slate-100">
                @forelse ($workOrders as $index => $item)
                    <tr
                        class="hover:bg-yellow-50/50 transition-colors duration-150 group {{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50/30' }}">
                        {{-- Checkbox Row --}}
                        <td class="px-6 py-4">
                            <input type="checkbox" value="{{ (string) $item->id }}" x-model="selected"
                                class="rounded-sm border-slate-300 text-slate-900 focus:ring-yellow-400 cursor-pointer" />
                        </td>

                        {{-- Tiket --}}
                        <td class="px-6 py-4">
                            <div
                                class="text-sm font-black text-slate-900 font-mono group-hover:text-blue-600 transition-colors">
                                {{ $item->ticket_num }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase">
                                {{ $item->created_at->format('d M Y') }}
                            </div>
                        </td>

                        {{-- Pelapor --}}
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-slate-700">{{ $item->requester_name }}</div>
                            @if ($item->requester_department)
                                <div class="text-[10px] font-bold text-slate-400 mt-1">
                                    Dept: {{ $item->requester_department }}
                                </div>
                            @endif
                        </td>

                        {{-- Lokasi / Dept --}}
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1 items-start">
                                @if ($item->plant)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-tight">
                                        LOC: {{ $item->plantInfo->name ?? $item->plant }}
                                    </span>
                                @endif

                                @if ($item->department)
                                    <span
                                        class="px-2 py-0.5 rounded-sm text-[10px] font-black bg-slate-800 text-white uppercase tracking-tight">
                                        DEPT: {{ $item->department }}
                                    </span>
                                @endif

                                @if (!$item->plant && !$item->department)
                                    <span class="text-xs text-slate-300 italic">-</span>
                                @endif
                            </div>
                        </td>

                        {{-- Parameter --}}
                        <td class="px-6 py-4 text-xs font-bold text-slate-600 uppercase">
                            {{ $item->parameter_permintaan }}
                        </td>

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
                                class="px-2 py-1 text-[10px] font-black rounded-sm border {{ $catColor }} uppercase tracking-wide">
                                {{ $catDisplay }}
                            </span>
                        </td>

                        {{-- Uraian --}}
                        <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate font-medium">
                            {{ Str::limit($item->description, 35) }}
                        </td>

                        {{-- PIC --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($item->processed_by_name)
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center text-[10px] font-black border border-slate-600">
                                        {{ substr($item->processed_by_name, 0, 1) }}
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 uppercase">
                                        {{ $item->processed_by_name }}
                                    </span>
                                </div>
                            @else
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide border border-dashed border-slate-300 px-2 py-1 rounded-sm">
                                    Menunggu
                                </span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClass = match ($item->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'pending' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'waiting_spv' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                                $statusLabel = str_replace('_', ' ', $item->status);
                                if ($item->status == 'waiting_spv') {
                                    $statusLabel = 'WAITING APPROVAL';
                                }
                                if ($item->status == 'pending') {
                                    $statusLabel = 'PENDING GA';
                                }
                            @endphp
                            <span
                                class="px-3 py-1 text-[10px] font-black uppercase rounded-sm border {{ $statusClass }} tracking-wider">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 justify-end">

                                {{-- Tombol Detail --}}
                                <button type="button"
                                    @click="$dispatch('buka-detail', '{{ base64_encode(json_encode($item)) }}')"
                                    class="text-slate-400 hover:text-blue-600 transition-colors" title="Lihat Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                {{-- Tombol Edit --}}
                                @if (
                                    (Auth::user()->role === 'ga.admin' || Auth::user()->role === 'admin_ga') &&
                                        in_array($item->status, ['in_progress', 'completed', 'approved']))
                                    <button type="button" @click='openEditModal(@json($item))'
                                        class="text-slate-400 hover:text-yellow-500 transition-colors"
                                        title="Update / Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endif

                                {{-- LOGIKA OTORITAS APPROVAL (Admin Teknis & GA) --}}
                                @php
                                    $userRole = Auth::user()->role;
                                    $deptTiket = $item->department;
                                    $statusTiket = $item->status;
                                    $isAuthorized = false;

                                    if ($statusTiket == 'waiting_spv' || $statusTiket == 'waiting_approval') {
                                        if (stripos($deptTiket, 'MAINTENANCE') !== false && $userRole == 'mt.admin') {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'FACILITY') !== false &&
                                            $userRole == 'fh.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'PE') !== false && $userRole == 'eng.admin') {
                                            $isAuthorized = true;
                                        } elseif (
                                            in_array($deptTiket, ['GA', 'General Affair']) &&
                                            ($userRole == 'ga.admin' || $userRole == 'admin_ga')
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'Low voltage') !== false &&
                                            $userRole == 'lv.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'Medium voltage') !== false &&
                                            $userRole == 'mv.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'QR') !== false && $userRole == 'qr.admin') {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'hc') !== false && $userRole == 'hc.admin') {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'sc') !== false && $userRole == 'sc.admin') {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'it') !== false && $userRole == 'it.admin') {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'sales 1') !== false &&
                                            $userRole == 'sales1.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'sales 2') !== false &&
                                            $userRole == 'sales2.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (
                                            stripos($deptTiket, 'marketing') !== false &&
                                            $userRole == 'marketing.admin'
                                        ) {
                                            $isAuthorized = true;
                                        } elseif (stripos($deptTiket, 'ss') !== false && $userRole == 'ss.admin') {
                                            $isAuthorized = true;
                                        }
                                    }
                                @endphp

                                @if ($isAuthorized)
                                    {{-- FORM TERSEMBUNYI UNTUK APPROVAL TEKNIS --}}
                                    <form id="form-tech-{{ $item->id }}"
                                        action="{{ route('wo.approve_technical', $item->id) }}" method="POST"
                                        class="hidden">
                                        @csrf
                                        <input type="hidden" name="action" id="input-action-{{ $item->id }}">
                                    </form>

                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'approve')"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm flex items-center transition-colors"
                                            title="Approve Tiket">
                                            Approve
                                        </button>

                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'decline')"
                                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm flex items-center transition-colors"
                                            title="Reject Tiket">
                                            Reject
                                        </button>
                                    </div>
                                @endif

                                {{-- Tombol Proses GA --}}
                                @if (
                                    ($item->status == 'waiting_ga_approval' || $item->status == 'pending') &&
                                        ($userRole == 'ga.admin' || $userRole == 'admin_ga'))
                                    <button type="button"
                                        onclick="openProcessModal('{{ $item->id }}', '{{ $item->ticket_num }}', '{{ $item->requester_name }}')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm flex items-center gap-1 transition-colors"
                                        title="Proses Tiket">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Proses
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-slate-500 font-bold uppercase tracking-wide">
                                    Tidak ada data ditemukan
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        {{ $workOrders->appends(request()->all())->links() }}
    </div>
</div>
