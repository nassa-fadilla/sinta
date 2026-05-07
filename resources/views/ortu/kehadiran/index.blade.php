@extends('ortu.layout')
@section('title', 'Rekap Kehadiran')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $weekGroups = collect($weekdayColumns ?? [])->groupBy('week_no');
        $tableRows = is_iterable($tableRows ?? null) ? $tableRows : [];

        $rekap = $rekap ?? [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'total' => 0,
            'persen' => null,
        ];

        $todayLabel = \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y');

        $rombelName = is_string($rombelName ?? null) && trim($rombelName) !== '' ? $rombelName : '-';
        $waliKelasName = is_string($waliKelasName ?? null) && trim($waliKelasName) !== '' ? $waliKelasName : '-';
        $infoTahunAjaran = is_string($infoTahunAjaran ?? null) && trim($infoTahunAjaran) !== '' ? $infoTahunAjaran : '—';
        $infoSemester = is_string($infoSemester ?? null) && trim($infoSemester) !== '' ? $infoSemester : null;

        $bulanLabel = is_string($bulanLabel ?? null) && trim($bulanLabel) !== ''
            ? \Illuminate\Support\Str::title($bulanLabel)
            : \Carbon\Carbon::createFromFormat('Y-m', $bulanKey, 'Asia/Jakarta')->locale('id')->translatedFormat('F Y');

        $persentaseKehadiran = $rekap['persen'] !== null ? $rekap['persen'] : null;

        $statusClass = function ($status) {
            return match ($status) {
                'hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'izin' => 'bg-amber-50 text-amber-700 border-amber-200',
                'sakit' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                'alpa' => 'bg-rose-50 text-rose-700 border-rose-200',
                default => 'bg-slate-50 text-slate-400 border-slate-200',
            };
        };
    @endphp

    <div class="space-y-6">

        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-[0_14px_30px_rgba(16,185,129,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Presensi Siswa
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Rekap Kehadiran
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Pantau kehadiran putra/putri Anda berdasarkan data presensi yang tersinkron dari SIA.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    {{ $todayLabel }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
                                    {{ $rombelName }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-medium text-violet-700">
                                    {{ $infoTahunAjaran }}{{ $infoSemester ? ' • Semester ' . $infoSemester : '' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <div
                            class="min-w-[250px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_22px_50px_rgba(16,185,129,0.13)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-500 text-white shadow-[0_12px_26px_rgba(16,185,129,0.28)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                        Periode Presensi
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                        {{ $bulanLabel }}
                                    </div>
                                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                        Wali Kelas:
                                        <span class="font-medium text-slate-700">{{ $waliKelasName }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUICK STATS + FILTER --}}
            <div class="border-b border-slate-200/80 px-5 py-5 md:px-6 lg:px-7">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {{-- Kelas --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                                    Kelas
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $rombelName }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500 line-clamp-2">
                                    Wali Kelas: <span class="font-medium text-slate-700">{{ $waliKelasName }}</span>
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 6c-3.314 0-6 1.79-6 4v1h12v-1c0-2.21-2.686-4-6-4z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Tahun Ajaran --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-violet-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(139,92,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_20px_48px_rgba(139,92,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-700">
                                    Tahun Ajaran
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $infoTahunAjaran }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $infoSemester ? 'Semester ' . $infoSemester : 'Semester —' }}
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Persentase Kehadiran --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Kehadiran
                                </div>

                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $persentaseKehadiran !== null ? $persentaseKehadiran . '%' : '—' }}
                                </div>

                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Total {{ $rekap['total'] ?? 0 }} presensi.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Ringkasan Status --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-rose-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(244,63,94,0.08)] transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_20px_48px_rgba(244,63,94,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-700">
                                    Ringkasan
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        H: {{ $rekap['hadir'] }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        I: {{ $rekap['izin'] }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-yellow-200 bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700">
                                        S: {{ $rekap['sakit'] }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                        A: {{ $rekap['alpa'] }}
                                    </span>
                                </div>

                                <div class="mt-3 text-xs leading-5 text-slate-500">
                                    Rekap berdasarkan periode aktif.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FILTER SEBARIS --}}
                <div
                    class="mt-5 rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">
                                Filter Periode
                            </h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Pilih bulan untuk melihat rekap kehadiran siswa.
                            </p>
                        </div>

                        <form method="GET" action="{{ route('ortu.kehadiran.index') }}"
                            class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2">
                                <label for="bulan" class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                    Periode
                                </label>

                                <input type="month" name="bulan" id="bulan" value="{{ $bulanKey }}"
                                    class="min-w-[180px] rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-emerald-300 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                            </div>

                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-emerald-600 hover:shadow-md">
                                Lihat
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="p-5 md:p-6 lg:p-7">
                @if(empty($tableRows))
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-14 text-center shadow-inner">
                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M4.93 4.93l14.14 14.14M5 13a7 7 0 1 1 14 0 7 7 0 0 1-14 0z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Belum ada data presensi pada periode ini.
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Jika seharusnya sudah ada, silakan hubungi admin atau wali kelas.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">
                                Detail Kehadiran per Mata Pelajaran
                            </h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Data presensi ditampilkan berdasarkan mata pelajaran dan tanggal pada periode
                                {{ $bulanLabel }}.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-[11px]">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-medium text-emerald-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                Hadir
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 font-medium text-amber-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                Izin
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-yellow-200 bg-yellow-50 px-3 py-1.5 font-medium text-yellow-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
                                Sakit
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                Alpa
                            </span>
                        </div>
                    </div>

                    {{-- MOBILE CARDS --}}
                    <div class="grid grid-cols-1 gap-4 xl:hidden">
                        @foreach($tableRows as $index => $row)
                            @php
                                $cardPalette = match ($index % 4) {
                                    0 => [
                                        'outer' => 'border-blue-100 bg-white hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]',
                                        'iconWrap' => 'border-blue-200 bg-blue-50 text-blue-600',
                                        'chip' => 'border-blue-200 bg-blue-50 text-blue-700',
                                        'line' => 'bg-blue-500',
                                    ],
                                    1 => [
                                        'outer' => 'border-emerald-100 bg-white hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]',
                                        'iconWrap' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
                                        'chip' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                        'line' => 'bg-emerald-500',
                                    ],
                                    2 => [
                                        'outer' => 'border-amber-100 bg-white hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]',
                                        'iconWrap' => 'border-amber-200 bg-amber-50 text-amber-600',
                                        'chip' => 'border-amber-200 bg-amber-50 text-amber-700',
                                        'line' => 'bg-amber-500',
                                    ],
                                    default => [
                                        'outer' => 'border-fuchsia-100 bg-white hover:border-fuchsia-200 hover:shadow-[0_20px_48px_rgba(217,70,239,0.14)]',
                                        'iconWrap' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-600',
                                        'chip' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
                                        'line' => 'bg-fuchsia-500',
                                    ],
                                };
                            @endphp

                            <article
                                class="group relative overflow-hidden rounded-[1.7rem] border px-4 py-4 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 {{ $cardPalette['outer'] }}">
                                <div class="absolute inset-x-0 top-0 h-1 {{ $cardPalette['line'] }}"></div>

                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <span
                                            class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $cardPalette['chip'] }}">
                                            Mata Pelajaran
                                        </span>

                                        <h3 class="mt-3 text-base font-semibold leading-snug text-slate-900">
                                            {{ $row['mapel'] }}
                                        </h3>
                                    </div>

                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-hover:scale-110 {{ $cardPalette['iconWrap'] }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-3 py-2">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-emerald-600">
                                            Hadir
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">
                                            {{ $row['totals']['hadir'] ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-amber-100 bg-amber-50/70 px-3 py-2">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-amber-600">
                                            Izin
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">
                                            {{ $row['totals']['izin'] ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-yellow-100 bg-yellow-50/70 px-3 py-2">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-yellow-600">
                                            Sakit
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">
                                            {{ $row['totals']['sakit'] ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-rose-100 bg-rose-50/70 px-3 py-2">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-rose-600">
                                            Alpa
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">
                                            {{ $row['totals']['alpa'] ?? 0 }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-3">
                                    <div class="mb-2 text-xs font-medium text-slate-600">
                                        Status Harian
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach($weekdayColumns as $col)
                                            @php
                                                $cell = $row['cells'][$col['date_key']] ?? null;
                                                $kode = $cell['kode'] ?? '';
                                                $status = $cell['status'] ?? null;
                                            @endphp

                                            <span title="{{ $cell['tooltip'] ?? 'Tidak ada presensi' }}"
                                                class="inline-flex min-w-[42px] items-center justify-center rounded-full border px-2 py-1 text-xs font-semibold transition hover:scale-105 {{ $statusClass($status) }}">
                                                {{ $kode !== '' ? $kode : '•' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- DESKTOP TABLE --}}
                    <div
                        class="hidden overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)] xl:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-[1500px] w-full border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        <th rowspan="2"
                                            class="sticky left-0 z-20 min-w-[220px] bg-slate-50 px-4 py-4 text-left whitespace-nowrap">
                                            Mata Pelajaran
                                        </th>

                                        @foreach($weekGroups as $weekNo => $cols)
                                            <th colspan="{{ count($cols) }}" class="px-3 py-4 text-center whitespace-nowrap">
                                                M{{ $weekNo }}
                                            </th>
                                        @endforeach

                                        <th colspan="4" class="min-w-[220px] px-3 py-4 text-center whitespace-nowrap">
                                            Total
                                        </th>
                                    </tr>

                                    <tr
                                        class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        @foreach($weekGroups as $cols)
                                            @foreach($cols as $col)
                                                <th class="min-w-[62px] px-2 py-3 text-center whitespace-nowrap"
                                                    title="{{ $col['tooltip'] }}">
                                                    <div class="leading-tight">
                                                        <div class="text-slate-700">{{ $col['tanggal'] }}</div>
                                                        <div class="mt-0.5 text-slate-400">{{ $col['hari_singkat'] }}</div>
                                                    </div>
                                                </th>
                                            @endforeach
                                        @endforeach

                                        <th class="min-w-[54px] px-2 py-3 text-center text-emerald-700 whitespace-nowrap">
                                            H
                                        </th>
                                        <th class="min-w-[54px] px-2 py-3 text-center text-amber-700 whitespace-nowrap">
                                            I
                                        </th>
                                        <th class="min-w-[54px] px-2 py-3 text-center text-yellow-700 whitespace-nowrap">
                                            S
                                        </th>
                                        <th class="min-w-[54px] px-2 py-3 text-center text-rose-700 whitespace-nowrap">
                                            A
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach($tableRows as $row)
                                        <tr
                                            class="group transition duration-300 hover:bg-emerald-50/35 hover:shadow-[inset_0_0_0_1px_rgba(167,243,208,0.45)]">
                                            <td
                                                class="sticky left-0 z-10 min-w-[220px] bg-white px-4 py-3 font-semibold text-slate-900 transition duration-300 group-hover:text-emerald-700">
                                                <div class="break-words leading-6">
                                                    {{ $row['mapel'] }}
                                                </div>
                                            </td>

                                            @foreach($weekdayColumns as $col)
                                                @php
                                                    $cell = $row['cells'][$col['date_key']] ?? null;
                                                    $kode = $cell['kode'] ?? '';
                                                    $status = $cell['status'] ?? null;
                                                @endphp

                                                <td class="px-2 py-3 text-center">
                                                    <span title="{{ $cell['tooltip'] ?? 'Tidak ada presensi' }}"
                                                        class="inline-flex min-w-[40px] justify-center rounded-full border px-2 py-1 text-xs font-semibold transition hover:scale-105 {{ $statusClass($status) }}">
                                                        {{ $kode !== '' ? $kode : '•' }}
                                                    </span>
                                                </td>
                                            @endforeach

                                            <td class="px-2 py-3 text-center">
                                                <span
                                                    class="inline-flex min-w-[44px] justify-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 transition hover:scale-105">
                                                    {{ $row['totals']['hadir'] ?? 0 }}
                                                </span>
                                            </td>

                                            <td class="px-2 py-3 text-center">
                                                <span
                                                    class="inline-flex min-w-[44px] justify-center rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 transition hover:scale-105">
                                                    {{ $row['totals']['izin'] ?? 0 }}
                                                </span>
                                            </td>

                                            <td class="px-2 py-3 text-center">
                                                <span
                                                    class="inline-flex min-w-[44px] justify-center rounded-full border border-yellow-200 bg-yellow-50 px-2 py-1 text-xs font-semibold text-yellow-700 transition hover:scale-105">
                                                    {{ $row['totals']['sakit'] ?? 0 }}
                                                </span>
                                            </td>

                                            <td class="px-2 py-3 text-center">
                                                <span
                                                    class="inline-flex min-w-[44px] justify-center rounded-full border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 transition hover:scale-105">
                                                    {{ $row['totals']['alpa'] ?? 0 }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- LEGENDA --}}
                    <div class="mt-5 rounded-[1.7rem] border border-slate-200 bg-slate-50/70 px-4 py-3">
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 font-semibold text-emerald-700">H</span>
                                Hadir
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-amber-200 bg-amber-50 font-semibold text-amber-700">I</span>
                                Izin
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-yellow-200 bg-yellow-50 font-semibold text-yellow-700">S</span>
                                Sakit
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-rose-200 bg-rose-50 font-semibold text-rose-700">A</span>
                                Alpa
                            </span>

                            <span class="text-slate-400">
                                Arahkan kursor ke sel untuk melihat tanggal dan waktu presensi.
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection