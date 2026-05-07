@extends('admin.layout')
@section('title', 'Presensi Siswa (SIA)')

@section('content')
    <div x-data="presensiIndex()" class="space-y-6">

        @php
            $presensiData = collect($data ?? [])->values();
            $selectedStatus = $statusFilter ?? request('status');
            $selectedMapel = $mapelFilter ?? request('mapel');

            $totalData = $presensiData->count();
            $hadirCount = $presensiData->where('status_terakhir', 'hadir')->count();
            $izinCount = $presensiData->where('status_terakhir', 'izin')->count();
            $sakitCount = $presensiData->where('status_terakhir', 'sakit')->count();
            $alfaCount = $presensiData->where('status_terakhir', 'alfa')->count();
        @endphp

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            <div class="relative">

                {{-- HEADER --}}
                <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 5h6m-3-2.25A1.75 1.75 0 0 1 13.75 4.5h.75A2.75 2.75 0 0 1 17.25 7.25v9.5A2.75 2.75 0 0 1 14.5 19.5h-5A2.75 2.75 0 0 1 6.75 16.75v-9.5A2.75 2.75 0 0 1 9.5 4.5h.75A1.75 1.75 0 0 1 12 2.75z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 13 1.5 1.5L15 10" />
                                </svg>
                            </div>

                            <div>
                                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                    Presensi Siswa
                                </h1>
                                <p class="mt-1 text-sm text-slate-500">
                                    Lihat riwayat presensi siswa berdasarkan NIS atau nama yang terintegrasi dari sistem
                                    akademik SIA.
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 pt-1">
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                Total Mapel: {{ $totalData }}
                            </span>
                        </div>
                    </div>

                    {{-- SEARCH --}}
                    <div class="mt-5">
                        <form method="GET" x-ref="searchForm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="w-full lg:flex-1 lg:max-w-3xl">
                                    <div
                                        class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                                        <span class="pl-1 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10 4a6 6 0 014.472 9.966l3.281 3.28a1 1 0 01-1.414 1.415l-3.28-3.281A6 6 0 1110 4z" />
                                            </svg>
                                        </span>

                                        <input type="text" name="q" value="{{ $q ?? '' }}"
                                            placeholder="Cari NIS atau nama siswa..."
                                            class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                                            autocomplete="off" @input="handleSearchInput">

                                        @if(request()->filled('status'))
                                            <input type="hidden" name="status" value="{{ request('status') }}">
                                        @endif

                                        @if(request()->filled('mapel'))
                                            <input type="hidden" name="mapel" value="{{ request('mapel') }}">
                                        @endif

                                        <div x-show="isLoading" x-cloak class="pr-1">
                                            <div
                                                class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                        Tampilkan Presensi
                                    </button>

                                    <a href="{{ route('admin.sia-master.presensi.index') }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="p-5 md:p-6 space-y-6">

                    {{-- INFO SISWA --}}
                    @if($siswa)
                        <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50/70 px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                            Siswa Ditemukan
                                        </p>
                                        <h3 class="mt-1 text-base font-semibold text-emerald-950">
                                            {{ $siswa->nama ?? '-' }}
                                        </h3>
                                        <p class="mt-1 text-sm text-emerald-800/90">
                                            NIS: {{ $siswa->nis ?? '-' }}
                                            <span class="mx-1">•</span>
                                            Rombel: {{ $siswa->rombel_nama ?? '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 shadow-sm">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Data siswa aktif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- SUMMARY CARD --}}
                    @if($q && $totalData > 0)
                        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                            <div
                                class="rounded-[1.5rem] border border-blue-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_40px_rgba(59,130,246,0.14)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                                    Total
                                </div>
                                <div class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ $totalData }}
                                </div>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_40px_rgba(16,185,129,0.14)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600">
                                    Hadir
                                </div>
                                <div class="mt-3 text-3xl font-bold tracking-tight text-emerald-700">
                                    {{ $hadirCount }}
                                </div>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-sky-100 bg-sky-50/60 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_18px_40px_rgba(14,165,233,0.14)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-600">
                                    Izin
                                </div>
                                <div class="mt-3 text-3xl font-bold tracking-tight text-sky-700">
                                    {{ $izinCount }}
                                </div>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_18px_40px_rgba(245,158,11,0.14)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-600">
                                    Sakit
                                </div>
                                <div class="mt-3 text-3xl font-bold tracking-tight text-amber-700">
                                    {{ $sakitCount }}
                                </div>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-rose-100 bg-rose-50/60 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_18px_40px_rgba(244,63,94,0.14)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-rose-600">
                                    Alfa
                                </div>
                                <div class="mt-3 text-3xl font-bold tracking-tight text-rose-700">
                                    {{ $alfaCount }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- TABLE --}}
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Riwayat Presensi per Mapel</h2>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Ringkasan presensi siswa berdasarkan mata pelajaran.
                                    </p>
                                </div>

                                <form method="GET" class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                                    @if(request()->filled('q'))
                                        <input type="hidden" name="q" value="{{ request('q') }}">
                                    @endif

                                    <select name="status"
                                        class="min-w-[180px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                        <option value="">Semua Status</option>
                                        @foreach(['hadir', 'izin', 'sakit', 'alfa', 'terlambat'] as $statusOption)
                                            <option value="{{ $statusOption }}" @selected($selectedStatus == $statusOption)>
                                                {{ ucfirst($statusOption) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="mapel"
                                        class="min-w-[220px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                        <option value="">Semua Mapel</option>
                                        @foreach(($mapelOptions ?? []) as $mapel)
                                            <option value="{{ $mapel }}" @selected($selectedMapel == $mapel)>
                                                {{ $mapel }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                        Filter
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[980px] table-auto text-sm">
                                <thead class="bg-slate-50">
                                    <tr
                                        class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-14 px-4 py-3 text-center">No</th>
                                        <th class="px-4 py-3">Mapel</th>
                                        <th class="px-4 py-3">Rombel</th>
                                        <th class="px-4 py-3">Pertemuan</th>
                                        <th class="px-4 py-3">Presensi Terakhir</th>
                                        <th class="px-4 py-3">Status Terakhir</th>
                                        <th
                                            class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @forelse($presensiData as $i => $p)
                                        @php
                                            $status = strtolower((string) ($p['status_terakhir'] ?? '-'));

                                            $badgeClass = match ($status) {
                                                'hadir' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                                'terlambat' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                                'izin' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
                                                'sakit' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                                                'alfa' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                                default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                                            };

                                            $dotClass = match ($status) {
                                                'hadir' => 'bg-emerald-500',
                                                'terlambat' => 'bg-amber-500',
                                                'izin' => 'bg-sky-500',
                                                'sakit' => 'bg-orange-500',
                                                'alfa' => 'bg-rose-500',
                                                default => 'bg-slate-400',
                                            };
                                        @endphp

                                        <tr class="transition duration-300 hover:bg-blue-50/40">
                                            <td class="px-4 py-3 text-center font-semibold text-slate-500">
                                                {{ $i + 1 }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-800">
                                                    {{ $p['mapel'] ?? '-' }}
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500">
                                                    Hadir: {{ $p['hadir_count'] ?? 0 }}
                                                    <span class="mx-0.5">•</span>
                                                    Izin: {{ $p['izin_count'] ?? 0 }}
                                                    <span class="mx-0.5">•</span>
                                                    Sakit: {{ $p['sakit_count'] ?? 0 }}
                                                    <span class="mx-0.5">•</span>
                                                    Alfa: {{ $p['alfa_count'] ?? 0 }}
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $p['rombel'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                                    {{ $p['total_pertemuan'] ?? 0 }} sesi
                                                </span>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="font-medium text-slate-800">
                                                    {{ $p['tanggal_terakhir'] ?? '-' }}
                                                </div>
                                                @if(!empty($p['waktu_mulai']) && $p['waktu_mulai'] !== '-')
                                                    <div class="mt-0.5 text-xs text-slate-500">
                                                        Jam mulai: {{ $p['waktu_mulai'] }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $badgeClass }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                    {{ $status }}
                                                </span>
                                            </td>

                                            <td
                                                class="sticky right-0 z-10 border-l border-slate-100 bg-white px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                                @if(!empty($p['sesi_id']))
                                                                                    <a href="{{ route('admin.sia-master.presensi.show', [
                                                        'id' => $p['sesi_id'],
                                                        'q' => $q,
                                                        'mapel' => $p['mapel'] ?? ''
                                                    ]) }}"
                                                                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                                                                                        Detail
                                                                                    </a>
                                                @else
                                                    <span
                                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-400">
                                                        -
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-14 text-center">
                                                <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                    <div
                                                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 5h6m-3-2.25A1.75 1.75 0 0 1 13.75 4.5h.75A2.75 2.75 0 0 1 17.25 7.25v9.5A2.75 2.75 0 0 1 14.5 19.5h-5A2.75 2.75 0 0 1 6.75 16.75v-9.5A2.75 2.75 0 0 1 9.5 4.5h.75A1.75 1.75 0 0 1 12 2.75z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m9 13 1.5 1.5L15 10" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-700">
                                                            @if($q)
                                                                Tidak ada data presensi ditemukan.
                                                            @else
                                                                Belum ada pencarian presensi.
                                                            @endif
                                                        </p>
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            @if($q)
                                                                Coba gunakan NIS, nama siswa, filter status, atau mapel yang lain.
                                                            @else
                                                                Masukkan NIS atau nama siswa untuk menampilkan riwayat presensi.
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function presensiIndex() {
            return {
                isLoading: false,
                typingTimer: null,

                handleSearchInput() {
                    this.isLoading = true;

                    if (this.typingTimer) {
                        clearTimeout(this.typingTimer);
                    }

                    this.typingTimer = setTimeout(() => {
                        if (this.$refs.searchForm) {
                            this.$refs.searchForm.submit();
                        }
                    }, 500);
                },
            }
        }
    </script>
@endpush