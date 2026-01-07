@props(['workOrders'])
<div class="bg-white shadow-xl rounded-sm overflow-hidden border border-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900">
                <tr>
                    <th class="px-6 py-4 w-10">
                        <input type="checkbox" @change="toggleSelectAll()"
                            :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(id))"
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

                        {{-- Lokasi --}}
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

                                @if (Auth::user()->role === 'ga.admin' || Auth::user()->role === 'admin_ga')
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
                                    // PERBAIKAN 1: Gunakan 'department' (Target), BUKAN 'requester_department'
                                    $deptTiket = $item->department;

                                    $statusTiket = $item->status;
                                    $isAuthorized = false;

                                    // Cek apakah status masih menunggu approval
                                    if ($statusTiket == 'waiting_spv' || $statusTiket == 'waiting_approval') {
                                        // 1. Logika Maintenance
                                        if (
                                            ($deptTiket == 'MT' || stripos($deptTiket, 'Maintenance') !== false) &&
                                            $userRole == 'mt.admin'
                                        ) {
                                            $isAuthorized = true;
                                        }
                                        // 2. Logika Facility
                                        elseif (
                                            ($deptTiket == 'FH' || stripos($deptTiket, 'Facility') !== false) &&
                                            $userRole == 'fh.admin'
                                        ) {
                                            $isAuthorized = true;
                                        }
                                        // 3. Logika Engineering
                                        elseif (
                                            ($deptTiket == 'ENG' ||
                                                $deptTiket == 'SC' ||
                                                stripos($deptTiket, 'Engineering') !== false ||
                                                in_array($deptTiket, [
                                                    'PE',
                                                    'Low Voltage',
                                                    'Medium Voltage',
                                                    'FO',
                                                    'QR',
                                                    'SS',
                                                ])) &&
                                            $userRole == 'eng.admin'
                                        ) {
                                            $isAuthorized = true;
                                        }
                                        // 4. PERBAIKAN 2: Logika GA ADMIN (Agar tombol muncul untuk tiket GA)
                                        elseif (
                                            in_array($deptTiket, ['GA', 'General Affair']) &&
                                            ($userRole == 'ga.admin' || $userRole == 'admin_ga')
                                        ) {
                                            $isAuthorized = true;
                                        }
                                    }
                                @endphp

                                {{-- TAMPILKAN TOMBOL APPROVE/REJECT --}}
                                @if ($isAuthorized)
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
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7">
                                                </path>
                                            </svg>
                                            Approve
                                        </button>

                                        <button type="button"
                                            onclick="confirmTechnicalAction('{{ $item->id }}', 'decline')"
                                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-1 px-3 rounded text-[10px] shadow-sm flex items-center transition-colors"
                                            title="Reject Tiket">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12">
                                                </path>
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                @endif

                                {{-- Logika Tombol PROSES (Setelah Approved) --}}
                                @if (
                                    ($item->status == 'waiting_ga_approval' || $item->status == 'pending') &&
                                        ($userRole == 'ga.admin' || $userRole == 'admin_ga'))
                                    <button
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
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
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

{{-- Modal Process GA --}}
<div id="modal-process-ga" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
    role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="closeProcessModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-process-ga" action="" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-ga">
                                Proses Tiket
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Tentukan status untuk tiket <span id="modal-ticket-num"
                                        class="font-bold text-gray-800"></span>
                                    dari <span id="modal-requester-name" class="font-bold text-gray-800"></span>.
                                </p>

                                <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                                    <label class="block text-gray-700 text-xs font-bold mb-2 uppercase tracking-wide">
                                        Tindakan:
                                    </label>
                                    <div class="flex items-center space-x-6">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="action" value="approve" checked
                                                onclick="toggleReasonGa(false)"
                                                class="form-radio text-green-600 h-4 w-4 focus:ring-green-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">
                                                Approve (Setujui)
                                            </span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="action" value="reject"
                                                onclick="toggleReasonGa(true)"
                                                class="form-radio text-red-600 h-4 w-4 focus:ring-red-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">
                                                Reject (Tolak)
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div id="reason-container-ga" class="hidden transition-all duration-300">
                                    <label for="reason" class="block text-gray-700 text-sm font-bold mb-1">
                                        Alasan Penolakan <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="reason" id="reason-input-ga" rows="3"
                                        class="shadow-sm focus:ring-red-500 focus:border-red-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-2"
                                        placeholder="Jelaskan alasan penolakan secara singkat..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan Keputusan
                    </button>
                    <button type="button" onclick="closeProcessModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- <script>
    // Fungsi konfirmasi SweetAlert
    function confirmSubmit(event, title, text, icon = 'warning', confirmColor = '#3085d6') {
        event.preventDefault();
        const form = event.target;

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    // Fungsi konfirmasi tolak dengan alasan
    function confirmRejectWithReason(event) {
        event.preventDefault();
        const form = event.target;

        if (typeof Swal === 'undefined') {
            alert('Error: SweetAlert belum dimuat. Silakan refresh halaman.');
            return;
        }

        Swal.fire({
            title: 'Tolak Tiket?',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan (Opsional)',
            inputPlaceholder: 'Tulis alasan kenapa ditolak (boleh dikosongkan)...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                reasonInput.value = result.value ? result.value : '-';
                form.appendChild(reasonInput);
                form.submit();
            }
        });
    }

    // Fungsi buka modal proses
    function openProcessModal(id, ticketNum, requesterName) {
        let url = "{{ route('work-order-ga.process', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('form-process-ga').action = url;

        document.getElementById('modal-ticket-num').innerText = ticketNum;
        document.getElementById('modal-requester-name').innerText = requesterName;

        document.querySelector('input[name="action"][value="approve"]').checked = true;
        toggleReasonGa(false);

        document.getElementById('modal-process-ga').classList.remove('hidden');
    }

    // Fungsi tutup modal
    function closeProcessModal() {
        document.getElementById('modal-process-ga').classList.add('hidden');
    }

    // Fungsi toggle input alasan
    function toggleReasonGa(isReject) {
        const container = document.getElementById('reason-container-ga');
        const input = document.getElementById('reason-input-ga');

        if (isReject) {
            container.classList.remove('hidden');
            input.setAttribute('required', 'required');
            input.focus();
        } else {
            container.classList.add('hidden');
            input.removeAttribute('required');
            input.value = '';
        }
    }

    // Fungsi konfirmasi admin teknis
    function confirmTechnicalAction(id, type) {
        let titleText = '';
        let bodyText = '';
        let iconType = '';
        let confirmColor = '';
        let btnText = '';

        if (type === 'approve') {
            titleText = 'Setujui Tiket?';
            bodyText = 'Tiket akan diteruskan ke tim GA.';
            iconType = 'question';
            confirmColor = '#059669';
            btnText = 'Ya, Approve!';
        } else {
            titleText = 'Tolak Tiket?';
            bodyText = 'Tiket akan dikembalikan ke user.';
            iconType = 'warning';
            confirmColor = '#e11d48';
            btnText = 'Ya, Tolak!';
        }

        Swal.fire({
            title: titleText,
            text: bodyText,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#64748b',
            confirmButtonText: btnText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('input-action-' + id).value = type;
                document.getElementById('form-tech-' + id).submit();
            }
        });
    }
</script> --}}
