@extends('ortu.layout')
@section('title', 'Rekap Nilai')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $todayLabel = isset($today)
            ? $today->copy()->locale('id')->translatedFormat('l, d F Y')
            : now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y');

        $tahunAjaranList = is_iterable($tahunAjaranList ?? null)
            ? collect($tahunAjaranList)
                ->filter(fn($v) => is_string($v) && trim($v) !== '')
                ->map(fn($v) => trim((string) $v))
                ->unique()
                ->values()
                ->all()
            : [];

        $semesterList = ['Ganjil', 'Genap'];

        $tahunAjaranAktif = is_string($tahunAjaranAktif ?? null) && trim($tahunAjaranAktif) !== ''
            ? trim($tahunAjaranAktif)
            : null;

        $semesterAktif = is_string($semesterAktif ?? null) && trim($semesterAktif) !== ''
            ? trim($semesterAktif)
            : null;

        if ($tahunAjaranAktif && !in_array($tahunAjaranAktif, $tahunAjaranList, true)) {
            array_unshift($tahunAjaranList, $tahunAjaranAktif);
        }

        if (!$semesterAktif || !in_array($semesterAktif, $semesterList, true)) {
            $semesterAktif = 'Ganjil';
        }

        $periodeAktifTahunLabel = is_string($infoTahunAjaran ?? null) && trim($infoTahunAjaran) !== ''
            ? trim($infoTahunAjaran)
            : '—';

        $periodeAktifSemesterLabel = is_string($infoSemester ?? null) && trim($infoSemester) !== ''
            ? trim($infoSemester)
            : '—';

        $tahunAjaranLabel = $tahunAjaranAktif ?: '—';
        $semesterLabel = $semesterAktif ?: '—';

        $trendChartLabels = is_iterable($trendChartLabels ?? null)
            ? collect($trendChartLabels)->values()->all()
            : [];

        $trendChartDatasets = is_iterable($trendChartDatasets ?? null)
            ? collect($trendChartDatasets)->values()->all()
            : [];

        $rombelLabel = is_string($rombelName ?? null) && trim($rombelName) !== ''
            ? trim($rombelName)
            : '-';

        $waliKelasLabel = is_string($waliKelasName ?? null) && trim($waliKelasName) !== ''
            ? trim($waliKelasName)
            : '-';

        $scoreClass = function ($v) {
            if (!is_numeric($v)) {
                return 'bg-white text-slate-500 border-slate-200';
            }

            if ($v >= 90) {
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            }

            if ($v >= 80) {
                return 'bg-blue-50 text-blue-700 border-blue-200';
            }

            if ($v >= 70) {
                return 'bg-amber-50 text-amber-700 border-amber-200';
            }

            return 'bg-rose-50 text-rose-700 border-rose-200';
        };

        $lmGroups = [
            [
                'label' => 'LM 1',
                'nilai' => 'lm1',
                'tp' => ['lm1_tp1', 'lm1_tp2', 'lm1_tp3', 'lm1_tp4'],
            ],
            [
                'label' => 'LM 2',
                'nilai' => 'lm2',
                'tp' => ['lm2_tp1', 'lm2_tp2', 'lm2_tp3', 'lm2_tp4'],
            ],
            [
                'label' => 'LM 3',
                'nilai' => 'lm3',
                'tp' => ['lm3_tp1', 'lm3_tp2', 'lm3_tp3', 'lm3_tp4'],
            ],
            [
                'label' => 'LM 4',
                'nilai' => 'lm4',
                'tp' => ['lm4_tp1', 'lm4_tp2', 'lm4_tp3', 'lm4_tp4'],
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M8 17V9m4 8V5m4 12v-6" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <div
                                    class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                    Akademik Siswa
                                </div>

                                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                    Rekap Nilai
                                </h1>

                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    Pantau riwayat nilai putra/putri Anda berdasarkan data akademik yang tersinkron dari SIA.
                                </p>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                        {{ $todayLabel }}
                                    </span>

                                    <span
                                        class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
                                        {{ $rombelLabel }}
                                    </span>

                                    <span
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                        Wali Kelas: {{ $waliKelasLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 lg:pt-1">
                            <div
                                class="min-w-[250px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_50px_rgba(59,130,246,0.13)]">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.28)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                            Periode Aktif
                                        </div>
                                        <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                            {{ $periodeAktifTahunLabel }}
                                        </div>
                                        <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                            Semester {{ $periodeAktifSemesterLabel }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FLASH INFO --}}
                @if (session('info'))
                    <div class="border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-800 md:px-6">
                        {{ session('info') }}
                    </div>
                @endif

                {{-- QUICK STATS --}}
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
                                        {{ $rombelLabel }}
                                    </div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500 line-clamp-2">
                                        Wali Kelas: <span class="font-medium text-slate-700">{{ $waliKelasLabel }}</span>
                                    </div>
                                </div>

                                <div
                                    class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 19v-1a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v1m10-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 10v-1a3 3 0 0 0-2-2.83M16 6.17a3 3 0 0 1 0 5.66" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Tahun Ajaran Filter --}}
                        <div
                            class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
                            <div class="flex h-full items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                        Tahun Ajaran
                                    </div>
                                    <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                        {{ $tahunAjaranLabel }}
                                    </div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        Semester {{ $semesterLabel }}
                                    </div>
                                </div>

                                <div
                                    class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Rata-rata --}}
                        <div
                            class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                            <div class="flex h-full items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                        Rata-rata
                                    </div>
                                    <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                        {{ $globalAverage !== null ? $globalAverage : '—' }}
                                    </div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        Rata-rata nilai global.
                                    </div>
                                </div>

                                <div
                                    class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 19h16M8 17V9m4 8V5m4 12v-6" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Ketuntasan --}}
                        <div
                            class="group h-full rounded-[1.6rem] border border-rose-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(244,63,94,0.08)] transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_20px_48px_rgba(244,63,94,0.14)]">
                            <div class="flex h-full items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-700">
                                        Ketuntasan
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <span
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Tuntas: {{ $jumlahTuntas ?? 0 }}
                                        </span>
                                        <span
                                            class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                            Belum: {{ $jumlahBelum ?? 0 }}
                                        </span>
                                    </div>

                                    <div class="mt-3 text-xs leading-5 text-slate-500">
                                        Total {{ $totalMapel ?? 0 }} mata pelajaran.
                                    </div>
                                </div>

                                <div
                                    class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition duration-300 group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
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
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">
                                    Filter Nilai
                                </h2>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Pilih tahun ajaran dan semester untuk melihat riwayat nilai siswa.
                                </p>
                            </div>

                            <form action="{{ route('ortu.nilai.index') }}" method="GET"
                                class="flex w-full flex-col gap-3 sm:flex-row sm:items-center xl:w-auto">

                                <div class="flex items-center gap-2">
                                    <label for="tahun_ajaran"
                                        class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                        TA
                                    </label>

                                    <select id="tahun_ajaran" name="tahun_ajaran"
                                        class="min-w-[170px] rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                        @forelse($tahunAjaranList as $ta)
                                            <option value="{{ $ta }}" {{ (string) $tahunAjaranAktif === (string) $ta ? 'selected' : '' }}>
                                                {{ $ta }}
                                            </option>
                                        @empty
                                            <option value="">—</option>
                                        @endforelse
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <label for="semester"
                                        class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                        Semester
                                    </label>

                                    <select id="semester" name="semester"
                                        class="min-w-[145px] rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100">
                                        @foreach($semesterList as $sem)
                                            <option value="{{ $sem }}" {{ (string) $semesterAktif === (string) $sem ? 'selected' : '' }}>
                                                {{ $sem }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                    Lihat
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- CONTENT --}}
                <div class="p-5 md:p-6 lg:p-7">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1.8fr,1fr]">
                        {{-- TABEL NILAI --}}
                        <div
                            class="overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                            <div class="border-b border-slate-200 bg-white px-5 py-5 md:px-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold text-slate-900">
                                            Rekap Nilai per Mata Pelajaran
                                        </h2>
                                        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">
                                            Menampilkan nilai KKM, detail TP, LM 1 sampai LM 4, nilai akhir, dan status
                                            ketuntasan pada periode yang dipilih.
                                        </p>
                                    </div>

                                    <div class="flex flex-col items-start gap-1 sm:items-end">
                                        @if($canExportPdf ?? false)
                                                                                <a href="{{ route('ortu.nilai.exportPdf', array_filter([
                                                'tahun_ajaran' => $tahunAjaranAktif,
                                                'semester' => $semesterAktif,
                                            ])) }}"
                                                                                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-emerald-600 hover:shadow-md">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                                            d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                                                                                    </svg>
                                                                                    Unduh PDF
                                                                                </a>
                                        @else
                                            <button type="button" disabled title="{{ $exportDisabledReason ?? '' }}"
                                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                                                </svg>
                                                Export PDF
                                            </button>
                                        @endif

                                        @if(!($canExportPdf ?? false) && !empty($exportDisabledReason))
                                            <p class="max-w-[240px] text-[11px] leading-5 text-amber-600 sm:text-right">
                                                {{ $exportDisabledReason }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(empty($nilaiList))
                                <div class="bg-slate-50/60 px-5 py-14 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                        <div
                                            class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 8v4m0 4h.01M4.93 4.93l14.14 14.14M5 13a7 7 0 1 1 14 0 7 7 0 0 1-14 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">
                                                Belum ada data nilai yang tersedia.
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Jika seharusnya sudah ada nilai, silakan konfirmasi ke wali kelas atau admin
                                                sekolah.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- MOBILE / ACCORDION --}}
                                <div class="space-y-3 p-4 sm:p-5 xl:hidden">
                                    @foreach($nilaiList as $index => $row)
                                        @php
                                            $isTuntas = $row['is_tuntas'] ?? null;
                                            $nilaiAkhir = $row['nilai_akhir'] ?? null;

                                            $badgeClass = 'bg-white text-slate-700 border-slate-200';
                                            $badgeText = 'Belum Lengkap';

                                            if ($isTuntas === true) {
                                                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                $badgeText = 'Tuntas';
                                            } elseif ($isTuntas === false) {
                                                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                                $badgeText = 'Belum Tuntas';
                                            }

                                            $palette = match ($index % 4) {
                                                0 => [
                                                    'border' => 'border-blue-100',
                                                    'line' => 'bg-blue-500',
                                                    'soft' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                    'icon' => 'bg-blue-50 text-blue-600 border-blue-200',
                                                ],
                                                1 => [
                                                    'border' => 'border-emerald-100',
                                                    'line' => 'bg-emerald-500',
                                                    'soft' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'icon' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                                                ],
                                                2 => [
                                                    'border' => 'border-amber-100',
                                                    'line' => 'bg-amber-500',
                                                    'soft' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    'icon' => 'bg-amber-50 text-amber-600 border-amber-200',
                                                ],
                                                default => [
                                                    'border' => 'border-fuchsia-100',
                                                    'line' => 'bg-fuchsia-500',
                                                    'soft' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                                                    'icon' => 'bg-fuchsia-50 text-fuchsia-600 border-fuchsia-200',
                                                ],
                                            };
                                        @endphp

                                        <details
                                            class="group overflow-hidden rounded-[1.7rem] border {{ $palette['border'] }} bg-white shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition duration-300 open:shadow-[0_20px_48px_rgba(59,130,246,0.12)]">

                                            <summary
                                                class="relative cursor-pointer list-none px-4 py-4 transition hover:bg-slate-50/70 [&::-webkit-details-marker]:hidden">
                                                <div class="absolute inset-x-0 top-0 h-1 {{ $palette['line'] }}"></div>

                                                <div class="flex items-start justify-between gap-3 pt-1">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span
                                                                class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $palette['soft'] }}">
                                                                Mapel {{ $index + 1 }}
                                                            </span>

                                                            <span
                                                                class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $badgeClass }}">
                                                                {{ $badgeText }}
                                                            </span>
                                                        </div>

                                                        <h3 class="mt-3 text-base font-semibold leading-snug text-slate-900">
                                                            {{ $row['mapel'] ?? '-' }}
                                                        </h3>

                                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                                                    KKM
                                                                </div>
                                                                <div class="mt-1">
                                                                    <span
                                                                        class="inline-flex min-w-[54px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $scoreClass($row['kkm'] ?? null) }}">
                                                                        {{ $row['kkm'] ?? '-' }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                                                                <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                                                    Nilai Akhir
                                                                </div>
                                                                <div class="mt-1">
                                                                    <span
                                                                        class="inline-flex min-w-[54px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $scoreClass($nilaiAkhir) }}">
                                                                        {{ $nilaiAkhir ?? '-' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-open:rotate-180 {{ $palette['icon'] }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </summary>

                                            <div class="border-t border-slate-100 bg-slate-50/40 px-4 pb-4 pt-4">
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <div>
                                                        <div class="text-sm font-semibold text-slate-800">
                                                            Detail Nilai
                                                        </div>
                                                        <div class="mt-0.5 text-xs text-slate-500">
                                                            Rincian TP dan LM pada mata pelajaran ini.
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-3">
                                                    @foreach($lmGroups as $group)
                                                        <div class="rounded-[1.35rem] border border-slate-200 bg-white p-3 shadow-sm">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                    {{ $group['label'] }}
                                                                </div>

                                                                <span
                                                                    class="inline-flex min-w-[58px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $scoreClass($row[$group['nilai']] ?? null) }}">
                                                                    {{ $row[$group['nilai']] ?? '-' }}
                                                                </span>
                                                            </div>

                                                            <div class="mt-3 grid grid-cols-2 gap-2">
                                                                @foreach($group['tp'] as $tpIndex => $tpKey)
                                                                    <div
                                                                        class="flex items-center justify-between gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2">
                                                                        <span class="text-[11px] font-medium text-slate-500">
                                                                            TP {{ $tpIndex + 1 }}
                                                                        </span>

                                                                        <span
                                                                            class="inline-flex min-w-[44px] justify-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $scoreClass($row[$tpKey] ?? null) }}">
                                                                            {{ $row[$tpKey] ?? '-' }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </details>
                                    @endforeach
                                </div>

                                {{-- DESKTOP / TABLE --}}
                                <div class="hidden overflow-x-auto xl:block">
                                    <table class="min-w-[1780px] w-full border-collapse text-xs sm:text-sm">
                                        <thead>
                                            <tr
                                                class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                <th rowspan="2"
                                                    class="sticky left-0 z-20 min-w-[190px] max-w-[190px] bg-slate-50 px-4 py-4 text-left whitespace-nowrap">
                                                    Mata Pelajaran
                                                </th>
                                                <th rowspan="2"
                                                    class="sticky left-[190px] z-20 min-w-[78px] bg-slate-50 px-3 py-4 text-center whitespace-nowrap">
                                                    KKM
                                                </th>
                                                <th colspan="5" class="px-3 py-4 text-center whitespace-nowrap">LM 1</th>
                                                <th colspan="5" class="px-3 py-4 text-center whitespace-nowrap">LM 2</th>
                                                <th colspan="5" class="px-3 py-4 text-center whitespace-nowrap">LM 3</th>
                                                <th colspan="5" class="px-3 py-4 text-center whitespace-nowrap">LM 4</th>
                                                <th rowspan="2" class="min-w-[100px] px-3 py-4 text-center whitespace-nowrap">
                                                    Nilai Akhir
                                                </th>
                                                <th rowspan="2" class="min-w-[120px] px-3 py-4 text-center whitespace-nowrap">
                                                    Status
                                                </th>
                                            </tr>
                                            <tr
                                                class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                @for($i = 1; $i <= 4; $i++)
                                                    <th class="min-w-[66px] px-3 py-3 text-center whitespace-nowrap">TP 1</th>
                                                    <th class="min-w-[66px] px-3 py-3 text-center whitespace-nowrap">TP 2</th>
                                                    <th class="min-w-[66px] px-3 py-3 text-center whitespace-nowrap">TP 3</th>
                                                    <th class="min-w-[66px] px-3 py-3 text-center whitespace-nowrap">TP 4</th>
                                                    <th class="min-w-[72px] px-3 py-3 text-center whitespace-nowrap">Nilai</th>
                                                @endfor
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100 text-slate-700">
                                            @foreach($nilaiList as $row)
                                                @php
                                                    $isTuntas = $row['is_tuntas'] ?? null;
                                                    $nilaiAkhir = $row['nilai_akhir'] ?? null;

                                                    $badgeClass = 'bg-white text-slate-700 border-slate-200';
                                                    $badgeText = 'Belum Lengkap';

                                                    if ($isTuntas === true) {
                                                        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                        $badgeText = 'Tuntas';
                                                    } elseif ($isTuntas === false) {
                                                        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                                        $badgeText = 'Belum Tuntas';
                                                    }
                                                @endphp

                                                <tr
                                                    class="group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]">
                                                    <td
                                                        class="sticky left-0 z-10 min-w-[190px] max-w-[190px] bg-white px-4 py-3 font-semibold text-slate-900 transition duration-300 group-hover:text-blue-700">
                                                        <div class="break-words leading-6">
                                                            {{ $row['mapel'] ?? '-' }}
                                                        </div>
                                                    </td>

                                                    <td class="sticky left-[190px] z-10 bg-white px-3 py-3 text-center">
                                                        <span
                                                            class="inline-flex min-w-[56px] justify-center rounded-full border px-2.5 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['kkm'] ?? null) }}">
                                                            {{ $row['kkm'] ?? '-' }}
                                                        </span>
                                                    </td>

                                                    {{-- LM 1 --}}
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm1_tp1'] ?? null) }}">{{ $row['lm1_tp1'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm1_tp2'] ?? null) }}">{{ $row['lm1_tp2'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm1_tp3'] ?? null) }}">{{ $row['lm1_tp3'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm1_tp4'] ?? null) }}">{{ $row['lm1_tp4'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[60px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition hover:scale-105 {{ $scoreClass($row['lm1'] ?? null) }}">{{ $row['lm1'] ?? '-' }}</span></td>

                                                    {{-- LM 2 --}}
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm2_tp1'] ?? null) }}">{{ $row['lm2_tp1'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm2_tp2'] ?? null) }}">{{ $row['lm2_tp2'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm2_tp3'] ?? null) }}">{{ $row['lm2_tp3'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm2_tp4'] ?? null) }}">{{ $row['lm2_tp4'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[60px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition hover:scale-105 {{ $scoreClass($row['lm2'] ?? null) }}">{{ $row['lm2'] ?? '-' }}</span></td>

                                                    {{-- LM 3 --}}
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm3_tp1'] ?? null) }}">{{ $row['lm3_tp1'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm3_tp2'] ?? null) }}">{{ $row['lm3_tp2'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm3_tp3'] ?? null) }}">{{ $row['lm3_tp3'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm3_tp4'] ?? null) }}">{{ $row['lm3_tp4'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[60px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition hover:scale-105 {{ $scoreClass($row['lm3'] ?? null) }}">{{ $row['lm3'] ?? '-' }}</span></td>

                                                    {{-- LM 4 --}}
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm4_tp1'] ?? null) }}">{{ $row['lm4_tp1'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm4_tp2'] ?? null) }}">{{ $row['lm4_tp2'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm4_tp3'] ?? null) }}">{{ $row['lm4_tp3'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[56px] justify-center rounded-full border px-2 py-1 text-xs font-medium transition hover:scale-105 {{ $scoreClass($row['lm4_tp4'] ?? null) }}">{{ $row['lm4_tp4'] ?? '-' }}</span></td>
                                                    <td class="px-3 py-3 text-center"><span class="inline-flex min-w-[60px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition hover:scale-105 {{ $scoreClass($row['lm4'] ?? null) }}">{{ $row['lm4'] ?? '-' }}</span></td>

                                                    <td class="px-3 py-3 text-center">
                                                        <span
                                                            class="inline-flex min-w-[72px] justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition hover:scale-105 {{ $scoreClass($nilaiAkhir) }}">
                                                            {{ $nilaiAkhir ?? '-' }}
                                                        </span>
                                                    </td>

                                                    <td class="px-3 py-3 text-center">
                                                        <span
                                                            class="inline-flex items-center justify-center rounded-full border px-2.5 py-1 text-[11px] font-medium transition hover:scale-105 {{ $badgeClass }}">
                                                            {{ $badgeText }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- GRAFIK --}}
                        <div
                            class="flex min-h-[470px] flex-col overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                            <div class="border-b border-slate-200 bg-white px-5 py-5 md:px-6">
                                <h2 class="text-base font-semibold text-slate-900">
                                    Grafik Perkembangan Nilai
                                </h2>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Perbandingan rata-rata nilai antar semester pada tiap tahun ajaran untuk melihat
                                    perkembangan akademik siswa.
                                </p>
                            </div>

                            <div class="flex-1 p-5 md:p-6">
                                @if(empty($trendChartLabels) || empty($trendChartDatasets))
                                    <div
                                        class="flex h-full min-h-[340px] items-center justify-center rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50/70 text-center">
                                        <div class="px-5">
                                            <div
                                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 19h16M8 17V9m4 8V5m4 12v-6" />
                                                </svg>
                                            </div>
                                            <p class="mt-3 text-sm font-medium text-slate-700">
                                                Grafik belum dapat ditampilkan.
                                            </p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Data tren nilai per tahun ajaran atau semester belum tersedia.
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full min-h-[340px] rounded-[1.5rem] border border-slate-100 bg-slate-50/40 p-3">
                                        <canvas id="chartTrendNilai" class="h-full w-full"></canvas>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @if(!empty($trendChartLabels) && !empty($trendChartDatasets))
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const ctx = document.getElementById('chartTrendNilai');
                    if (!ctx) return;

                    const labels = @json($trendChartLabels);
                    const rawDatasets = @json($trendChartDatasets);

                    const palette = [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#8B5CF6',
                        '#EC4899',
                        '#06B6D4',
                        '#EF4444',
                        '#14B8A6'
                    ];

                    const datasets = rawDatasets.map((item, index) => {
                        const color = item.borderColor || palette[index % palette.length];

                        return {
                            label: item.label || ('Seri ' + (index + 1)),
                            data: Array.isArray(item.data) ? item.data : [],
                            borderColor: color,
                            backgroundColor: (item.backgroundColor || color) + '20',
                            pointBackgroundColor: color,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35
                        };
                    });

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        padding: 14,
                                        color: '#475569',
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            return `${context.dataset.label}: ${context.parsed.y ?? 0}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    suggestedMax: 100,
                                    ticks: {
                                        stepSize: 10,
                                        color: '#64748b'
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.18)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#64748b'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        @endif
@endsection