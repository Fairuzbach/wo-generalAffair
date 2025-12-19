<div class="bg-white p-4 rounded-sm shadow-md border-l-4 border-slate-900 mb-6">
    <form action="{{ route('ga.dashboard') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">

        {{-- Input Tanggal Mulai --}}
        <div class="w-full md:w-auto">
            <label for="start_date" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                Tanggal Mulai
            </label>
            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                class="w-full md:w-48 border-slate-300 rounded-sm shadow-sm focus:border-yellow-400 focus:ring focus:ring-yellow-200 focus:ring-opacity-50 text-sm">
        </div>

        {{-- Input Tanggal Akhir --}}
        <div class="w-full md:w-auto">
            <label for="end_date" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                Tanggal Akhir
            </label>
            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                class="w-full md:w-48 border-slate-300 rounded-sm shadow-sm focus:border-yellow-400 focus:ring focus:ring-yellow-200 focus:ring-opacity-50 text-sm">
        </div>

        {{-- Tombol Filter --}}
        <div class="flex gap-2">
            <button type="submit"
                class="bg-slate-900 text-white hover:bg-slate-800 font-bold py-2 px-6 rounded-sm text-sm uppercase tracking-wide transition shadow-md">
                Filter Data
            </button>

            {{-- Tombol Reset (Jika sedang ada filter) --}}
            @if (request('start_date'))
                <a href="{{ route('ga.dashboard') }}"
                    class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-bold py-2 px-4 rounded-sm text-sm uppercase tracking-wide transition">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>
