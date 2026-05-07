@extends('ortu.layout')
@section('title', 'Kegiatan & Ekstrakurikuler')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $today = $today ?? now('Asia/Jakarta');
        $todayDate = ucfirst($today->locale('id')->translatedFormat('l, d F Y'));

        $hariOrder = [
            'senin' => ['label' => 'Senin', 'short' => 'Sen'],
            'selasa' => ['label' => 'Selasa', 'short' => 'Sel'],
            'rabu' => ['label' => 'Rabu', 'short' => 'Rab'],
            'kamis' => ['label' => 'Kamis', 'short' => 'Kam'],
            'jumat' => ['label' => 'Jumat', 'short' => 'Jum'],
            'sabtu' => ['label' => 'Sabtu', 'short' => 'Sab'],
        ];

        $hariToKey = function ($value) {
            $value = strtolower(trim((string) $value));

            return match ($value) {
                'senin' => 'senin',
                'selasa' => 'selasa',
                'rabu' => 'rabu',
                'kamis' => 'kamis',
                'jumat', 'jum\'at', 'jum at' => 'jumat',
                'sabtu' => 'sabtu',
                default => null,
            };
        };

        $groupedEkskul = [];
        foreach (array_keys($hariOrder) as $key) {
            $groupedEkskul[$key] = [];
        }

        foreach (($ekskulList ?? []) as $item) {
            $hariKey = $hariToKey($item['hari'] ?? null);
            if ($hariKey && isset($groupedEkskul[$hariKey])) {
                $groupedEkskul[$hariKey][] = $item;
            }
        }

        foreach ($groupedEkskul as $key => $items) {
            usort($groupedEkskul[$key], function ($a, $b) {
                return strcmp((string) ($a['jam_mulai'] ?? '99:99'), (string) ($b['jam_mulai'] ?? '99:99'));
            });
        }

        $daySummaries = [];
        foreach ($hariOrder as $key => $meta) {
            $items = $groupedEkskul[$key] ?? [];
            $count = count($items);

            $timeLabel = '-';
            if ($count === 1) {
                $timeLabel = $items[0]['jam'] ?? '-';
            } elseif ($count > 1) {
                $timeLabel = $count . ' kegiatan';
            }

            $daySummaries[$key] = [
                'count' => $count,
                'time_label' => $timeLabel,
                'items' => $items,
            ];
        }

        $rombelName = is_string($rombelName ?? null) && trim($rombelName) !== '' ? $rombelName : '-';

        $waliKelasName = $waliKelasName
            ?? $waliKelasNama
            ?? data_get($rombelDetail ?? null, 'wali_kelas.nama')
            ?? data_get($rombelDetail ?? null, 'guru.nama')
            ?? data_get($rombelAktif ?? null, 'wali_kelas.nama')
            ?? data_get($siswaApi ?? null, 'rombel_aktif.wali_kelas.nama')
            ?? data_get($siswaApi ?? null, 'rombel.wali_kelas.nama')
            ?? null;

        $waliKelasName = is_string($waliKelasName ?? null) && trim($waliKelasName) !== ''
            ? $waliKelasName
            : '-';

        $infoTahunAjaran = is_string($infoTahunAjaran ?? null) && trim($infoTahunAjaran) !== '' ? $infoTahunAjaran : '—';
        $infoSemester = is_string($infoSemester ?? null) && trim($infoSemester) !== '' ? $infoSemester : null;

        $totalEkskul = $totalEkskul ?? count($ekskulList ?? []);
        $aktifCount = $aktifCount ?? collect($ekskulList ?? [])->filter(fn($item) => strtolower((string) ($item['status'] ?? 'aktif')) === 'aktif')->count();
        $nonaktifCount = $nonaktifCount ?? max($totalEkskul - $aktifCount, 0);

        $hariAktifSekarang = strtolower($today->locale('id')->translatedFormat('l'));
        $hariAktifSekarang = match ($hariAktifSekarang) {
            'senin' => 'senin',
            'selasa' => 'selasa',
            'rabu' => 'rabu',
            'kamis' => 'kamis',
            'jumat' => 'jumat',
            'sabtu' => 'sabtu',
            default => null,
        };

        $ekskulPalettes = [
            [
                'outer' => 'border-blue-100 bg-white hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]',
                'iconWrap' => 'border-blue-200 bg-blue-50 text-blue-600',
                'chip' => 'border-blue-200 bg-blue-50 text-blue-700',
                'line' => 'bg-blue-500',
            ],
            [
                'outer' => 'border-emerald-100 bg-white hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]',
                'iconWrap' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
                'chip' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'line' => 'bg-emerald-500',
            ],
            [
                'outer' => 'border-fuchsia-100 bg-white hover:border-fuchsia-200 hover:shadow-[0_20px_48px_rgba(217,70,239,0.14)]',
                'iconWrap' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-600',
                'chip' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
                'line' => 'bg-fuchsia-500',
            ],
            [
                'outer' => 'border-amber-100 bg-white hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]',
                'iconWrap' => 'border-amber-200 bg-amber-50 text-amber-600',
                'chip' => 'border-amber-200 bg-amber-50 text-amber-700',
                'line' => 'bg-amber-500',
            ],
        ];
    @endphp

    <div class="space-y-6" x-data='{
                    modalOpen: false,
                    modalTitle: "",
                    modalItems: [],
                    openDay(dayLabel, items) {
                        this.modalTitle = dayLabel;
                        this.modalItems = items;
                        this.modalOpen = true;
                    }
                }'>

        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-[0_14px_30px_rgba(244,63,94,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6l3 7H9l3-7zm0 0V3m0 10v8" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-rose-100 bg-rose-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-rose-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Aktivitas Siswa
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Kegiatan & Ekstrakurikuler
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Lihat daftar kegiatan dan ekstrakurikuler yang diikuti putra/putri Anda berdasarkan jadwal
                                mingguan.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    {{ $todayDate }}
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
                            class="min-w-[250px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_22px_50px_rgba(244,63,94,0.13)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-500 text-white shadow-[0_12px_26px_rgba(244,63,94,0.28)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                        Ringkasan Ekskul
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                        {{ $totalEkskul }} kegiatan
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
                                        d="M12 6a3 3 0 110 6 3 3 0 010-6zm0 6c-3.314 0-6 1.79-6 4v1h12v-1c0-2.21-2.686-4-6-4z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Tahun ajaran --}}
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
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Total kegiatan --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Total Ekskul
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $totalEkskul }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Kegiatan yang terdaftar
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6l3 7H9l3-7zm0 0V3m0 10v8" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Ringkasan status --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-rose-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(244,63,94,0.08)] transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_20px_48px_rgba(244,63,94,0.14)]">
                        <div class="flex h-full items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-700">
                                    Ringkasan
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span
                                        class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif: {{ $aktifCount }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        Nonaktif: {{ $nonaktifCount }}
                                    </span>
                                </div>
                                <div class="mt-3 text-xs leading-5 text-slate-500">
                                    Tersinkron dari data SIA
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PANEL UTAMA --}}
            <div class="p-5 md:p-6 lg:p-7">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Kalender Kegiatan Mingguan
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Klik salah satu hari untuk melihat detail kegiatan ekstrakurikuler.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-medium text-emerald-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Ada kegiatan
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-500">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                            Tidak ada kegiatan
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($hariOrder as $key => $meta)
                        @php
                            $summary = $daySummaries[$key];
                            $items = $summary['items'];
                            $count = $summary['count'];
                            $hasItems = $count > 0;
                            $isToday = $hariAktifSekarang === $key;
                            $palette = $ekskulPalettes[$loop->index % count($ekskulPalettes)];
                        @endphp

                        <button type="button" @click='openDay(@json($meta["label"]), @json($items))' class="group relative min-h-[160px] overflow-hidden rounded-[1.7rem] border px-4 py-4 text-left shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1
                                                    {{ $hasItems ? $palette['outer'] : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.09)]' }}
                                                    {{ $isToday ? 'ring-2 ring-blue-200/80' : '' }}">

                            @if($hasItems)
                                <div class="absolute inset-x-0 top-0 h-1 {{ $palette['line'] }}"></div>
                            @else
                                <div class="absolute inset-x-0 top-0 h-1 bg-slate-200"></div>
                            @endif

                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $hasItems ? $palette['chip'] : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                            {{ $meta['label'] }}
                                        </span>

                                        @if($isToday)
                                            <span
                                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-medium text-blue-700">
                                                Hari Ini
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-4 text-3xl font-semibold leading-none text-slate-900">
                                        {{ $count }}
                                    </div>

                                    <div class="mt-2 text-sm font-normal {{ $hasItems ? 'text-slate-700' : 'text-slate-500' }}">
                                        {{ $hasItems ? $summary['time_label'] : 'Tidak ada kegiatan' }}
                                    </div>
                                </div>

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-hover:scale-110 {{ $hasItems ? $palette['iconWrap'] : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                                    @if($hasItems)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8v4m0 4h.01M4.93 4.93l14.14 14.14M5 13a7 7 0 1114 0 7 7 0 01-14 0z" />
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-1.5">
                                @if($hasItems)
                                    @foreach(collect($items)->take(3) as $item)
                                        <span
                                            class="inline-flex max-w-full items-center truncate rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-normal text-slate-600">
                                            {{ $item['nama'] ?? '-' }}
                                        </span>
                                    @endforeach

                                    @if($count > 3)
                                        <span
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-medium text-slate-700">
                                            +{{ $count - 3 }} lainnya
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[11px] text-slate-400">
                                        Tidak ada kegiatan
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                @if(empty($ekskulList))
                    <div
                        class="mt-5 rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-4 py-14 text-center shadow-inner">
                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 13h6m-3-3v6m9-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Belum ada kegiatan atau ekstrakurikuler yang tercatat.
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Jika seharusnya ada, silakan hubungi admin atau wali kelas.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- MODAL --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 px-4 backdrop-blur-sm"
            x-transition.opacity>
            <div @click.away="modalOpen = false"
                class="w-full max-w-2xl overflow-hidden rounded-[1.9rem] border border-white/80 bg-white shadow-[0_30px_90px_rgba(15,23,42,0.28)]"
                x-transition.scale>

                <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-5 py-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">
                            Detail Hari
                        </div>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900" x-text="modalTitle"></h3>
                        <p class="mt-0.5 text-xs leading-5 text-slate-500">
                            Informasi kegiatan ekstrakurikuler pada hari yang dipilih.
                        </p>
                    </div>

                    <button type="button" @click="modalOpen = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto bg-slate-50/80 p-5">
                    <template x-if="modalItems.length === 0">
                        <div
                            class="flex min-h-[180px] flex-col items-center justify-center rounded-[1.6rem] border border-dashed border-slate-200 bg-white px-6 text-center shadow-inner">
                            <div
                                class="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M4.93 4.93l14.14 14.14M5 13a7 7 0 1114 0 7 7 0 01-14 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">
                                Tidak ada kegiatan pada hari ini.
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Pilih hari lain untuk melihat jadwal yang tersedia.
                            </p>
                        </div>
                    </template>

                    <template x-if="modalItems.length > 0">
                        <div class="space-y-3">
                            <template x-for="(item, index) in modalItems" :key="index">
                                <div
                                    class="rounded-[1.6rem] border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-base font-semibold text-slate-900" x-text="item.nama ?? '-'">
                                            </div>
                                            <div class="mt-1 text-sm text-slate-500" x-text="item.jam ?? '-'"></div>
                                        </div>

                                        <span
                                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium"
                                            :class="(item.status ?? 'aktif') === 'aktif'
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                            : 'border-amber-200 bg-amber-50 text-amber-700'"
                                            x-text="(item.status ?? 'aktif') === 'aktif' ? 'Aktif' : 'Nonaktif'">
                                        </span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-3">
                                            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                                Pembina</div>
                                            <div class="mt-0.5 text-slate-700" x-text="item.pembina ?? '-'"></div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-3">
                                            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                                Lokasi</div>
                                            <div class="mt-0.5 text-slate-700" x-text="item.tempat ?? '-'"></div>
                                        </div>
                                    </div>

                                    <template x-if="item.catatan">
                                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                                            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                                Catatan</div>
                                            <div class="mt-0.5 text-sm text-slate-600" x-text="item.catatan"></div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection