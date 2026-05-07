@extends('ortu.layout')
@section('title', 'Jadwal Pelajaran')

@section('content')
    @php
        use Carbon\Carbon;

        $rombelNama = $rombelNama ?: '-';
        $waliKelasNama = $waliKelasNama ?: '-';

        $hariAktifKey = strtolower(trim((string) ($hariAktifKey ?? 'senin')));
        $todayKeyNow = strtolower(trim((string) ($todayKey ?? '')));

        $labelHariAktif = $hariAktifLabel
            ?? ($hariMap[$hariAktifKey] ?? ucfirst($hariAktifKey));

        $listHariAktif = $jadwalByHari[$hariAktifKey] ?? [];

        $now = Carbon::now('Asia/Jakarta');

        $listHariAktif = collect($listHariAktif)->map(function ($row) use ($hariAktifKey, $todayKeyNow, $now) {
            $row = (array) $row;

            $jamMulaiRaw = trim((string) ($row['jam_mulai'] ?? ''));
            $jamSelesaiRaw = trim((string) ($row['jam_selesai'] ?? ''));

            $isTodaySelected = $hariAktifKey === $todayKeyNow;

            $isActive = false;
            $isUpcoming = false;
            $isPassed = false;
            $progressPercent = 0;

            if ($isTodaySelected && $jamMulaiRaw !== '' && $jamSelesaiRaw !== '') {
                try {
                    $start = Carbon::createFromFormat('H:i', substr($jamMulaiRaw, 0, 5), 'Asia/Jakarta')
                        ->setDate($now->year, $now->month, $now->day);

                    $end = Carbon::createFromFormat('H:i', substr($jamSelesaiRaw, 0, 5), 'Asia/Jakarta')
                        ->setDate($now->year, $now->month, $now->day);

                    if ($now->between($start, $end)) {
                        $isActive = true;

                        $totalMinutes = max($start->diffInMinutes($end), 1);
                        $passedMinutes = max($start->diffInMinutes($now), 0);
                        $progressPercent = min(100, max(0, round(($passedMinutes / $totalMinutes) * 100)));
                    } elseif ($now->lt($start)) {
                        $isUpcoming = true;
                    } elseif ($now->gt($end)) {
                        $isPassed = true;
                    }
                } catch (\Throwable $e) {
                    // Tampilan tetap aman jika format jam tidak valid.
                }
            }

            $row['_meta'] = [
                'is_active' => $isActive,
                'is_upcoming' => $isUpcoming,
                'is_passed' => $isPassed,
                'progress_percent' => $progressPercent,
            ];

            return $row;
        })->values()->all();

        $activeNowCount = collect($listHariAktif)
            ->filter(fn($row) => data_get($row, '_meta.is_active'))
            ->count();

        $totalSesi = count($listHariAktif);

        $jadwalSelesaiCount = collect($listHariAktif)
            ->filter(fn($row) => data_get($row, '_meta.is_passed'))
            ->count();

        $jadwalAkanDatangCount = collect($listHariAktif)
            ->filter(fn($row) => data_get($row, '_meta.is_upcoming'))
            ->count();

        $dayPalettes = [
            [
                'border' => 'border-blue-100',
                'hover' => 'hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]',
                'icon' => 'border-blue-200 bg-blue-50 text-blue-600',
                'badge' => 'border-blue-200 bg-blue-50 text-blue-700',
                'line' => 'bg-blue-500',
            ],
            [
                'border' => 'border-emerald-100',
                'hover' => 'hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]',
                'icon' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
                'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'line' => 'bg-emerald-500',
            ],
            [
                'border' => 'border-amber-100',
                'hover' => 'hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]',
                'icon' => 'border-amber-200 bg-amber-50 text-amber-600',
                'badge' => 'border-amber-200 bg-amber-50 text-amber-700',
                'line' => 'bg-amber-500',
            ],
            [
                'border' => 'border-fuchsia-100',
                'hover' => 'hover:border-fuchsia-200 hover:shadow-[0_20px_48px_rgba(217,70,239,0.14)]',
                'icon' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-600',
                'badge' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
                'line' => 'bg-fuchsia-500',
            ],
        ];
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_14px_30px_rgba(59,130,246,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Aktivitas Akademik
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Jadwal Pelajaran
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Pantau jadwal pelajaran harian siswa secara ringkas, jelas, dan mudah dibaca.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    Kelas {{ $rombelNama }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
                                    {{ $todayLabel }}, {{ $todayDate }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                    {{ $totalSesi }} sesi
                                </span>

                                @if($hariAktifKey === $todayKeyNow && $activeNowCount > 0)
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-500 px-3 py-1.5 text-xs font-medium text-white shadow-sm">
                                        <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                                        Sedang berlangsung
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <div
                            class="min-w-[270px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_50px_rgba(59,130,246,0.13)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.28)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                        Tahun Ajaran Aktif
                                    </div>

                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                        {{ $infoTahunAjaran ?? '—' }}
                                    </div>

                                    @if($infoSemester)
                                        <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                            Semester {{ $infoSemester }}
                                        </div>
                                    @endif

                                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                        Rombel:
                                        <span class="font-medium text-slate-700">{{ $rombelNama }}</span>
                                    </div>

                                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                        Wali Kelas:
                                        <span class="font-medium text-slate-700">{{ $waliKelasNama }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- QUICK STATS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                    Hari Aktif
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $labelHariAktif }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Jadwal yang sedang ditampilkan.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Total Sesi
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $totalSesi }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Sesi pelajaran pada hari aktif.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 5h9m-9 4h9m-9 4h5M7 5h.01M7 9h.01M7 13h.01M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-amber-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(245,158,11,0.08)] transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">
                                    Akan Datang
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jadwalAkanDatangCount }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Sesi belum dimulai.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4l2 2m6-2a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-slate-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-slate-200 hover:shadow-[0_20px_48px_rgba(15,23,42,0.10)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Selesai
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jadwalSelesaiCount }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Sesi telah selesai.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="p-5 md:p-6 lg:p-7">
                <div
                    class="overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">

                    {{-- FILTER HARI --}}
                    <div class="border-b border-slate-200 bg-white px-5 py-5 md:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="text-base font-semibold text-slate-900">
                                    Jadwal {{ $labelHariAktif }}
                                </div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">
                                    Susunan mata pelajaran siswa pada hari {{ strtolower($labelHariAktif) }}.
                                </div>
                            </div>

                            <div
                                class="inline-flex w-fit max-w-full flex-wrap items-center gap-1 rounded-full border border-slate-200 bg-slate-50 p-1 shadow-inner">
                                @foreach($hariMap as $key => $label)
                                    <a href="{{ route('ortu.jadwal.index', ['hari' => $key]) }}"
                                        class="rounded-full px-3 py-1.5 text-xs font-medium transition whitespace-nowrap {{ $hariAktifKey === $key
                                            ? 'bg-blue-500 text-white shadow-sm'
                                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if(empty($listHariAktif))
                        <div class="bg-slate-50/60 px-5 py-14 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-slate-700">
                                        Tidak ada jadwal pelajaran pada hari {{ strtolower($labelHariAktif) }}.
                                    </p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Jadwal hanya ditampilkan untuk hari Senin sampai Jumat sesuai data rombel aktif dari SIA.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- MOBILE / CARD --}}
                        <div class="grid grid-cols-1 gap-4 p-5 xl:hidden">
                            @foreach($listHariAktif as $index => $row)
                                @php
                                    $meta = $row['_meta'] ?? [];
                                    $palette = $dayPalettes[$index % count($dayPalettes)];

                                    $isActive = data_get($meta, 'is_active');
                                    $isUpcoming = data_get($meta, 'is_upcoming');
                                    $isPassed = data_get($meta, 'is_passed');

                                    $statusLabel = $isActive
                                        ? 'Sedang Berlangsung'
                                        : ($isUpcoming
                                            ? 'Akan Datang'
                                            : ($isPassed
                                                ? 'Selesai'
                                                : 'Terjadwal'));

                                    $statusClass = $isActive
                                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                                        : ($isUpcoming
                                            ? 'border-amber-200 bg-amber-50 text-amber-700'
                                            : ($isPassed
                                                ? 'border-slate-200 bg-slate-100 text-slate-500'
                                                : 'border-slate-200 bg-slate-50 text-slate-600'));
                                @endphp

                                <article
                                    class="group relative overflow-hidden rounded-[1.7rem] border bg-white p-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 {{ $isActive ? 'border-blue-300 ring-2 ring-blue-100 shadow-[0_20px_48px_rgba(59,130,246,0.14)]' : $palette['border'] . ' ' . $palette['hover'] }}">
                                    <div class="absolute inset-x-0 top-0 h-1 {{ $isActive ? 'bg-blue-500' : $palette['line'] }}">
                                    </div>

                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span
                                                    class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $isActive ? 'border-blue-200 bg-blue-50 text-blue-700' : $palette['badge'] }}">
                                                    Sesi {{ $index + 1 }}
                                                </span>

                                                <span
                                                    class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>

                                            <h3 class="mt-3 text-lg font-semibold leading-snug text-slate-900">
                                                {{ $row['mapel'] ?? '-' }}
                                            </h3>

                                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                                {{ $row['guru'] ?? '-' }}
                                            </p>
                                        </div>

                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-hover:scale-110 {{ $isActive ? 'border-blue-200 bg-blue-50 text-blue-600' : $palette['icon'] }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 8v4l2 2m6-2a8 8 0 1 1-16 0 8 8 0 0 1 16 0z" />
                                            </svg>
                                        </div>
                                    </div>

                                    @if($isActive)
                                        <div class="mt-4">
                                            <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-blue-700">
                                                <span>Progres sesi</span>
                                                <span>{{ data_get($meta, 'progress_percent', 0) }}%</span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                                <div class="h-full rounded-full bg-blue-500 transition-all duration-700"
                                                    style="width: {{ data_get($meta, 'progress_percent', 0) }}%">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                        <div class="text-xs text-slate-500">
                                            Jam pelajaran
                                        </div>
                                        <div
                                            class="whitespace-nowrap text-sm font-semibold {{ $isActive ? 'text-blue-700' : 'text-slate-800' }}">
                                            {{ $row['jam_mulai'] ?? '-' }} – {{ $row['jam_selesai'] ?? '-' }}
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        {{-- DESKTOP / TABLE --}}
                        <div class="hidden overflow-x-auto xl:block">
                            <table class="min-w-full table-auto border-collapse text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-20 px-6 py-4">Sesi</th>
                                        <th class="px-6 py-4">Mata Pelajaran</th>
                                        <th class="px-6 py-4">Guru</th>
                                        <th class="px-6 py-4">Jam Mulai</th>
                                        <th class="px-6 py-4">Jam Selesai</th>
                                        <th class="px-6 py-4">Status</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach($listHariAktif as $index => $row)
                                        @php
                                            $meta = $row['_meta'] ?? [];

                                            $isActive = data_get($meta, 'is_active');
                                            $isUpcoming = data_get($meta, 'is_upcoming');
                                            $isPassed = data_get($meta, 'is_passed');

                                            $statusClass = $isActive
                                                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'
                                                : ($isUpcoming
                                                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                                                    : ($isPassed
                                                        ? 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'
                                                        : match ($index % 4) {
                                                            0 => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                                            1 => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                                            2 => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                                            default => 'bg-fuchsia-50 text-fuchsia-700 ring-1 ring-fuchsia-200',
                                                        }));

                                            $statusLabel = $isActive
                                                ? 'Sedang Berlangsung'
                                                : ($isUpcoming
                                                    ? 'Akan Datang'
                                                    : ($isPassed
                                                        ? 'Selesai'
                                                        : 'Terjadwal'));

                                            $rowClass = $isActive
                                                ? 'bg-blue-50/70 shadow-[inset_0_0_0_1px_rgba(59,130,246,0.12)]'
                                                : 'group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]';
                                        @endphp

                                        <tr class="{{ $rowClass }}">
                                            <td class="px-6 py-5 align-top">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                                    {{ $index + 1 }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-5 align-top">
                                                <div
                                                    class="font-semibold text-slate-900 transition duration-300 {{ $isActive ? 'text-blue-700' : 'group-hover:text-blue-700' }}">
                                                    {{ $row['mapel'] ?? '-' }}
                                                </div>
                                            </td>

                                            <td class="px-6 py-5 align-top text-slate-700">
                                                {{ $row['guru'] ?? '-' }}
                                            </td>

                                            <td
                                                class="px-6 py-5 align-top {{ $isActive ? 'font-semibold text-blue-700' : 'text-slate-700' }}">
                                                {{ $row['jam_mulai'] ?? '-' }}
                                            </td>

                                            <td
                                                class="px-6 py-5 align-top {{ $isActive ? 'font-semibold text-blue-700' : 'text-slate-700' }}">
                                                {{ $row['jam_selesai'] ?? '-' }}
                                            </td>

                                            <td class="px-6 py-5 align-top">
                                                <div class="flex flex-col gap-2">
                                                    <span
                                                        class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-medium {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>

                                                    @if($isActive)
                                                        <div class="w-32">
                                                            <div class="h-1.5 overflow-hidden rounded-full bg-white shadow-inner">
                                                                <div class="h-full rounded-full bg-blue-500 transition-all duration-700"
                                                                    style="width: {{ data_get($meta, 'progress_percent', 0) }}%">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection