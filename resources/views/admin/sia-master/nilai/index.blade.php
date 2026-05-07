@extends('admin.layout')
@section('title', 'Data Nilai (SIA)')

@section('content')
    @php
        $nilaiData = collect($data ?? [])->values();

        $selectedTahunAjaran = request('tahun_ajaran');
        $selectedSemester = request('semester');

        $tahunAjaranOptions = $nilaiData
            ->pluck('tahun_ajaran')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $semesterOptions = $nilaiData
            ->pluck('semester')
            ->filter()
            ->map(fn($s) => ucfirst(strtolower((string) $s)))
            ->unique()
            ->values();

        if ($selectedTahunAjaran) {
            $nilaiData = $nilaiData->filter(function ($item) use ($selectedTahunAjaran) {
                return (string) ($item['tahun_ajaran'] ?? '') === (string) $selectedTahunAjaran;
            })->values();
        }

        if ($selectedSemester) {
            $nilaiData = $nilaiData->filter(function ($item) use ($selectedSemester) {
                return strtolower((string) ($item['semester'] ?? '')) === strtolower((string) $selectedSemester);
            })->values();
        }

        $totalData = $nilaiData->count();
        $tuntasCount = $nilaiData->where('status', 'tuntas')->count();
        $tidakTuntasCount = $nilaiData->where('status', 'tidak_tuntas')->count();
        $finalCount = $nilaiData->where('status_penilaian', 'final')->count();
        $draftCount = $nilaiData->where('status_penilaian', 'draft')->count();
    @endphp

    <div x-data="nilaiIndex()" class="space-y-6">

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9.25A1.25 1.25 0 0 1 18 4.25V18a1 1 0 0 1-1 1H7.5A2.75 2.75 0 0 1 4.75 16.25V5.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 11.25h3.5M9 14.5h2" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Data Nilai Siswa
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Lihat rekap nilai siswa berdasarkan NIS atau nama yang terintegrasi dari sistem akademik
                                SIA.
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 pt-1">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Total Data: {{ $totalData }}
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

                                    @if(request()->filled('tahun_ajaran'))
                                        <input type="hidden" name="tahun_ajaran" value="{{ request('tahun_ajaran') }}">
                                    @endif

                                    @if(request()->filled('semester'))
                                        <input type="hidden" name="semester" value="{{ request('semester') }}">
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
                                    Tampilkan Nilai
                                </button>

                                <a href="{{ route('admin.sia-master.nilai.index') }}"
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
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div
                            class="rounded-[1.5rem] border border-blue-100 bg-blue-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_40px_rgba(59,130,246,0.14)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-600">
                                Total Mapel
                            </p>
                            <div class="mt-3 text-3xl font-bold tracking-tight text-blue-700">
                                {{ $totalData }}
                            </div>
                        </div>

                        <div
                            class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_40px_rgba(16,185,129,0.14)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600">
                                Tuntas
                            </p>
                            <div class="mt-3 text-3xl font-bold tracking-tight text-emerald-700">
                                {{ $tuntasCount }}
                            </div>
                        </div>

                        <div
                            class="rounded-[1.5rem] border border-rose-100 bg-rose-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_18px_40px_rgba(244,63,94,0.14)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-rose-600">
                                Tidak Tuntas
                            </p>
                            <div class="mt-3 text-3xl font-bold tracking-tight text-rose-700">
                                {{ $tidakTuntasCount }}
                            </div>
                        </div>

                        <div
                            class="rounded-[1.5rem] border border-violet-100 bg-violet-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_18px_40px_rgba(139,92,246,0.14)]">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-violet-600">
                                Final / Draft
                            </p>
                            <div class="mt-3 flex items-end gap-2">
                                <span class="text-3xl font-bold tracking-tight text-violet-700">{{ $finalCount }}</span>
                                <span class="pb-1 text-sm text-slate-500">/ {{ $draftCount }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- TABLE --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Rekap Nilai per Mapel</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Daftar rekap nilai siswa berdasarkan mata pelajaran hasil pencarian.
                                </p>
                            </div>

                            <form method="GET" class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                                @if(request()->filled('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif

                                <select name="tahun_ajaran"
                                    class="min-w-[220px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach($tahunAjaranOptions as $ta)
                                        <option value="{{ $ta }}" @selected($selectedTahunAjaran == $ta)>
                                            {{ $ta }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="semester"
                                    class="min-w-[180px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Semester</option>
                                    @foreach($semesterOptions as $semester)
                                        <option value="{{ strtolower($semester) }}" @selected(strtolower((string) $selectedSemester) == strtolower((string) $semester))>
                                            {{ $semester }}
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
                        <table class="w-full min-w-[1080px] table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Tahun Ajaran</th>
                                    <th class="px-4 py-3">Semester</th>
                                    <th class="px-4 py-3">Rombel</th>
                                    <th class="px-4 py-3">Mapel</th>
                                    <th class="px-4 py-3">Guru</th>
                                    <th class="px-4 py-3 text-center">Nilai Akhir</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Status Penilaian</th>
                                    <th
                                        class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($nilaiData as $i => $n)
                                    @php
                                        $status = strtolower((string) ($n['status'] ?? 'tidak_tuntas'));
                                        $statusPenilaian = strtolower((string) ($n['status_penilaian'] ?? 'draft'));

                                        $badgeStatusClass = $status === 'tuntas'
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';

                                        $badgePenilaianClass = $statusPenilaian === 'final'
                                            ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'
                                            : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
                                    @endphp

                                    <tr class="transition duration-300 hover:bg-blue-50/40">
                                        <td class="px-4 py-3 text-center font-semibold text-slate-500">
                                            {{ $i + 1 }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $n['tahun_ajaran'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                {{ ucfirst($n['semester'] ?? '-') }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ $n['rombel'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ $n['mapel'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $n['guru'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-center font-bold text-violet-700">
                                            {{ $n['nilai_akhir'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center gap-1 rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $badgeStatusClass }}">
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full {{ $status === 'tuntas' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                                {{ str_replace('_', ' ', $status) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center gap-1 rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $badgePenilaianClass }}">
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full {{ $statusPenilaian === 'final' ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                                {{ $statusPenilaian }}
                                            </span>
                                        </td>

                                        <td
                                            class="sticky right-0 z-10 border-l border-slate-200 bg-white px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)]">
                                            <a href="{{ route('admin.sia-master.nilai.show', ['nilai' => $n['id'], 'q' => $q]) }}"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9.25A1.25 1.25 0 0 1 18 4.25V18a1 1 0 0 1-1 1H7.5A2.75 2.75 0 0 1 4.75 16.25V5.75z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 8h6M9 11.25h3.5M9 14.5h2" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        @if($q)
                                                            Tidak ada data nilai ditemukan.
                                                        @else
                                                            Belum ada pencarian nilai.
                                                        @endif
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        @if($q)
                                                            Coba gunakan NIS, nama siswa, tahun ajaran, atau semester yang lain.
                                                        @else
                                                            Masukkan NIS atau nama siswa untuk menampilkan rekap nilai.
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
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function nilaiIndex() {
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