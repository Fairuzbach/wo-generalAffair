@props(['workOrders'])

<div class="bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
    {{-- ALERT BANNER (Sudah Benar) --}}
    @if (session('alert-action'))
        <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r shadow-md" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-orange-700 font-bold">
                        {{ session('alert-action')['message'] }}
                    </p>
                    <p class="text-sm text-orange-700 mt-1">
                        {{ session('alert-action')['instruction'] }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900">
                <tr>
                    <th class="px-6 py-4 w-10">
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

                        {{-- Checkbox --}}
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

                        {{-- Diterima Oleh --}}
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

                        {{-- Status (YANG DIPERBAIKI) --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClass = match ($item->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    // Pending kita buat orange agar admin notice
                                    'pending' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'waiting_spv' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'waiting_ga_approval' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'cancelled', 'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-800',
                                };

                                $statusLabel = str_replace('_', ' ', $item->status);

                                // Penyesuaian Label
                                if ($item->status == 'waiting_spv') {
                                    $statusLabel = 'WAITING DEPT APPROVAL';
                                } elseif ($item->status == 'waiting_ga_approval') {
                                    $statusLabel = 'WAITING GA APPROVAL';
                                } elseif ($item->status == 'pending') {
                                    // Label khusus untuk pending (antrian)
                                    $statusLabel = 'PENDING (ANTRIAN)';
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

                                {{-- Detail --}}
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

                                {{-- Edit (Diperbaiki: pending lowercase) --}}
                                @if (
                                    (Auth::user()->role === 'ga.admin' || Auth::user()->role === 'admin_ga') &&
                                        in_array($item->status, ['in_progress', 'completed', 'pending']))
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

                                {{-- Tombol Approval Teknis (Logic tetap sama) --}}
                                {{-- LOGIKA OTORITAS APPROVAL (Admin Teknis & GA) --}}
                                @php
                                    $userRole = Auth::user()->role;
                                    $deptTiket = $item->department;
                                    $statusTiket = $item->status;
                                    $isAuthorized = false;

                                    // Cek apakah status tiket butuh approval
                                    if (in_array($statusTiket, ['waiting_spv', 'waiting_approval'])) {
                                        // 1. Definisikan Mapping Lengkap (Copy dari Controller/Service)
                                        $roleMap = [
                                            'eng.admin' => ['Engineering', 'engineering', 'ENGINEERING', 'PE'],
                                            'fh.admin' => ['Facility', 'FH', 'FACILITY', 'Konstruksi'],
                                            'mt.admin' => ['Maintenance', 'maintenance', 'MT', 'Workshop', 'Mechanic'],
                                            'lv.admin' => [
                                                'Low Voltage',
                                                'LOW VOLTAGE',
                                                'low voltage',
                                                'LV',
                                                'lv',
                                                'Plant A',
                                                'Plant C',
                                                'Plant F',
                                                'Autowire',
                                            ],
                                            'mv.admin' => [
                                                'Medium Voltage',
                                                'medium voltage',
                                                'MV',
                                                'mv',
                                                'Plant B',
                                                'Plant D',
                                            ],
                                            'qr.admin' => ['QR', 'qr', 'QC', 'Quality'],
                                            'sc.admin' => ['SC', 'sc', 'Support', 'RM'],
                                            'fo.admin' => ['FO', 'fo', 'Plant E'],
                                            'ss.admin' => ['SS', 'ss', 'Gudang'],
                                            'fa.admin' => ['FA', 'fa'],
                                            'it.admin' => ['IT', 'it'],
                                            'hc.admin' => ['HC', 'hc'],
                                            'sales1.admin' => ['Sales 1', 'sales 1'],
                                            'sales2.admin' => ['Sales 2', 'sales 2'],
                                            'marketing.admin' => ['Marketing', 'marketing'],
                                            // Tambahkan GA juga jika perlu approve level 1
                                            'ga.admin' => ['GA', 'General Affair'],
                                            'admin_ga' => ['GA', 'General Affair'],
                                        ];

                                        // 2. Logika Pengecekan Baru (Support Array)
                                        // Cek apakah role user saat ini ada di daftar map?
                                        if (array_key_exists($userRole, $roleMap)) {
                                            // Ambil array keyword milik user ini (contoh: ['MT', 'Maintenance'])
                                            $allowedKeywords = $roleMap[$userRole];

                                            // Loop setiap keyword untuk dicocokkan dengan dept tiket
                                            foreach ($allowedKeywords as $keyword) {
                                                // stripos = case insensitive (tidak peduli huruf besar/kecil)
                                                if (stripos($deptTiket, $keyword) !== false) {
                                                    $isAuthorized = true;
                                                    break; // Jika sudah ketemu cocok, stop loop
                                                }
                                            }
                                        }
                                    }
                                @endphp

                                @if ($isAuthorized)
                                    <form id="form-tech-{{ $item->id }}"
                                        action="{{ route('wo.approve_technical', $item->id) }}" method="POST"
                                        class="hidden">
                                        @csrf <input type="hidden" name="action"
                                            id="input-action-{{ $item->id }}">
                                    </form>
                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'approve')"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1 px-3 rounded text-[10px]">Approve</button>
                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'decline')"
                                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-1 px-3 rounded text-[10px]">Reject</button>
                                    </div>
                                @endif

                                {{-- Tombol Proses GA --}}
                                @if (
                                    $item->status == 'waiting_ga_approval' && // Hapus "|| $item->status == 'pending'"
                                        ($userRole == 'ga.admin' || $userRole == 'admin_ga'))
                                    <button type="button"
                                        onclick="openProcessModal('{{ $item->id }}', '{{ $item->ticket_num }}', '{{ $item->requester_name }}')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm flex items-center gap-1 transition-colors">
                                        Proses
                                    </button>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center">
                            <span class="text-slate-500 font-bold uppercase tracking-wide">Tidak ada data
                                ditemukan</span>
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
