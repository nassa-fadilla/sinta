@extends('kepsek.layout')
@section('title', 'Dashboard Kepala Sekolah')

@section('content')
    @php
        $presensiToday = $presensiToday ?? ['persen' => null, 'hadir' => 0, 'total' => 0];
        $presensiTrend7 = $presensiTrend7 ?? [];
        $pending = $pending ?? 0;
        $approved = $approved ?? 0;
        $rejected = $rejected ?? 0;
        $rekapNilaiTingkat = $rekapNilaiTingkat ?? [];
        $topSiswa = $topSiswa ?? [];
        $hasil = $hasil ?? [];
        $guruSia = $guruSia ?? null;
        $tahunAjaranAktif = $tahunAjaranAktif ?? '—';

        $safePresensiPercent = min(max((float) ($presensiToday['persen'] ?? 0), 0), 100);
        $rekapNilaiCollection = collect($rekapNilaiTingkat ?? []);
        $topSiswaCollection = collect($topSiswa ?? []);
    @endphp

    <div class="space-y-6">

        {{-- HEADER --}}
        <div
            class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_12px_36px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_18px_48px_rgba(15,23,42,0.10)]">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.08),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.06),transparent_24%)]">
            </div>
            <div class="absolute -top-16 -right-16 h-44 w-44 rounded-full bg-blue-300/15 blur-3xl"></div>
            <div class="absolute -bottom-14 -left-10 h-36 w-36 rounded-full bg-cyan-300/10 blur-3xl"></div>

            <div class="relative px-5 py-5 md:px-7 md:py-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="flex items-center gap-3 text-2xl font-semibold text-slate-800">
                            <span
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-200/50 transition duration-300 hover:scale-[1.03]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                                </svg>
                            </span>
                            <span>Dashboard Kepala Sekolah</span>
                        </h1>

                        <p class="mt-2 text-sm text-slate-500">
                            Ringkasan akademik dan aktivitas sekolah untuk mendukung pemantauan serta pengambilan keputusan.
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Login sebagai:
                            <span class="font-semibold text-slate-600">
                                {{ $guruSia->nama ?? auth()->user()->name ?? 'Kepala Sekolah' }}
                            </span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="min-w-[240px] rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-[0_10px_28px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(59,130,246,0.12)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 text-white shadow-md shadow-blue-200/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                        Tahun Ajaran Aktif
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-800">
                                        {{ $tahunAjaranAktif ?? '—' }}
                                    </div>
                                    @if ($guruSia)
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            NUPTK: {{ $guruSia->nuptk ?? '-' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- QUICK STATS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Presensi --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(14,165,233,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-sky-100/70 via-white/20 to-cyan-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-sky-300/25 blur-2xl"></div>

                <div class="relative flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">
                            Presensi Hari Ini
                        </div>

                        <div class="mt-2 flex flex-wrap items-baseline gap-2">
                            <span class="text-2xl font-bold leading-none text-slate-800">
                                {{ $presensiToday['hadir'] ?? 0 }} / {{ $presensiToday['total'] ?? 0 }}
                            </span>
                            <span class="text-xs font-semibold text-sky-700">
                                {{ $presensiToday['persen'] !== null ? number_format((float) $presensiToday['persen'], 1) . '%' : '—' }}
                            </span>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/80 shadow-inner">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-500 via-cyan-500 to-blue-500"
                                style="width: {{ $safePresensiPercent }}%"></div>
                        </div>

                        <div class="mt-1 text-[11px] text-slate-500">
                            Rekap kehadiran siswa hari ini
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-sky-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Pending --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(245,158,11,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-100/70 via-white/20 to-orange-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-amber-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">
                            Menunggu Persetujuan
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($pending ?? 0) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Pengumuman menunggu review
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-amber-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 8a1 1 0 011 1v3.382l2.447 1.224a1 1 0 11-.894 1.788l-3-1.5A1 1 0 0111 14V9a1 1 0 011-1z" />
                            <path fill-rule="evenodd"
                                d="M12 2a10 10 0 100 20 10 10 0 000-20zm8 10A8 8 0 114 12a8 8 0 0116 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Approved --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(16,185,129,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-100/70 via-white/20 to-teal-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-emerald-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
                            Telah Disetujui
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($approved ?? 0) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Pengumuman yang disetujui
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-emerald-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M2.25 12a9.75 9.75 0 1119.5 0 9.75 9.75 0 01-19.5 0zm13.28-2.53a.75.75 0 10-1.06-1.06l-3.72 3.72-1.22-1.22a.75.75 0 00-1.06 1.06l1.75 1.75a.75.75 0 001.06 0l4.25-4.25z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Rejected --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(244,63,94,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-100/70 via-white/20 to-pink-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-rose-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-700">
                            Ditolak
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($rejected ?? 0) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Pengumuman yang ditolak
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-rose-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M12 2.25A9.75 9.75 0 1021.75 12 9.75 9.75 0 0012 2.25zm3.53 12.22a.75.75 0 11-1.06 1.06L12 13.06l-2.47 2.47a.75.75 0 11-1.06-1.06L10.94 12 8.47 9.53a.75.75 0 111.06-1.06L12 10.94l2.47-2.47a.75.75 0 111.06 1.06L13.06 12l2.47 2.47z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHARTS --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Tren Presensi --}}
            <div
                class="lg:col-span-2 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Tren Presensi 7 Hari Terakhir
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Persentase kehadiran siswa selama tujuh hari terakhir.
                        </p>
                    </div>
                </div>

                @if (!empty($presensiTrend7))
                    <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="h-[290px] w-full">
                            <canvas id="chartPresensi7"></canvas>
                        </div>
                    </div>
                @else
                    <div
                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Belum ada data tren presensi.
                    </div>
                @endif
            </div>

            {{-- Ringkasan Pengumuman --}}
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Status Pengumuman
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Ringkasan alur persetujuan pengumuman sekolah.
                        </p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/90 px-3 py-3 text-amber-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Menunggu Persetujuan</span>
                            <span class="text-lg font-semibold">{{ $pending ?? 0 }}</span>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-3 py-3 text-emerald-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Telah Disetujui</span>
                            <span class="text-lg font-semibold">{{ $approved ?? 0 }}</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-rose-100 bg-rose-50/90 px-3 py-3 text-rose-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Ditolak</span>
                            <span class="text-lg font-semibold">{{ $rejected ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('kepsek.pengumuman.index') }}"
                        class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[11px] font-medium text-blue-700 shadow-sm transition hover:bg-blue-100">
                        Kelola Pengumuman
                    </a>
                </div>
            </div>
        </div>

        {{-- REKAP NILAI & TOP SISWA --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Rata-rata per tingkat --}}
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Rata-rata Nilai per Tingkat
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Rekap rata-rata nilai siswa berdasarkan tingkat kelas.
                        </p>
                    </div>
                </div>

                @if ($rekapNilaiCollection->isEmpty())
                    <div
                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Belum ada data rekap nilai per tingkat dari SIA.
                    </div>
                @else
                    <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm h-[380px]">
                        <div class="h-full w-full">
                            <canvas id="chartNilaiTingkat"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Top siswa --}}
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-3">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Top Siswa (Prestasi Akademik)
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Daftar siswa dengan capaian rata-rata nilai tertinggi.
                    </p>
                </div>

                @if ($topSiswaCollection->isEmpty())
                    <div
                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Belum ada data top siswa dari SIA.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($topSiswaCollection as $index => $row)
                            @php
                                $rank = $index + 1;
                                $badgeClass = match ($rank) {
                                    1 => 'from-amber-400 to-yellow-300 text-amber-900',
                                    2 => 'from-slate-300 to-slate-200 text-slate-700',
                                    3 => 'from-orange-300 to-amber-200 text-orange-900',
                                    default => 'from-blue-100 to-sky-100 text-blue-700',
                                };

                                $kelasTop = $row['rombel'] ?? $row['kelas'] ?? $row['rombel_nama'] ?? '-';
                            @endphp

                            <div
                                class="rounded-[1.6rem] border border-slate-200 bg-white px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $badgeClass }} text-sm font-bold shadow-sm">
                                            {{ $rank }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-800">
                                                {{ $row['nama'] ?? '-' }}
                                            </div>
                                            <div class="mt-1 truncate text-xs text-slate-500">
                                                Kelas {{ $kelasTop }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                            Nilai
                                        </div>
                                        <div
                                            class="bg-gradient-to-r from-blue-600 to-sky-500 bg-clip-text text-xl font-bold text-transparent">
                                            {{ $row['rata_rata'] !== null ? $row['rata_rata'] : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- HASIL SURVEI ORTU --}}
        @if (!empty($hasil))
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Insight Survei Orang Tua
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Gambaran ringkas hasil survei orang tua berdasarkan setiap pertanyaan.
                        </p>
                    </div>
                </div>

                @php $chartIndex = 0; @endphp

                <div class="space-y-5">
                    @foreach ($hasil as $s)
                        <div x-data="{ open: true }"
                            class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                            <button type="button" @click="open = !open"
                                class="flex w-full items-center justify-between gap-3 bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100/80">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">
                                        {{ $s['judul'] }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Ringkasan jawaban per pertanyaan
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    @php
                                        $totalRespon = array_sum(
                                            collect($s['ringkasan'] ?? [])->map(fn($opsi) => array_sum($opsi))->toArray(),
                                        );
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700">
                                        {{ $totalRespon }} respon
                                    </span>

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-slate-400 transition-transform duration-150"
                                        :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" x-collapse class="px-4 pb-4 pt-3">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($s['ringkasan'] as $pertanyaan => $opsi)
                                        @php
                                            $chartId = 'chart_' . $chartIndex++;
                                            $labels = array_keys($opsi);
                                            $data = array_values($opsi);
                                            $isPie = count($labels) <= 5;
                                        @endphp

                                        <div
                                            class="rounded-[1.4rem] border border-slate-200 bg-white p-3 shadow-sm transition hover:shadow-md">
                                            <p class="mb-2 line-clamp-2 text-xs font-semibold leading-snug text-slate-800">
                                                {{ $pertanyaan }}
                                            </p>

                                            @if (!empty($data))
                                                <div class="relative h-48 w-full">
                                                    <canvas id="{{ $chartId }}"></canvas>
                                                </div>
                                            @else
                                                <p class="mt-2 text-xs italic text-slate-500">Belum ada data jawaban.</p>
                                            @endif
                                        </div>

                                        @push('scripts')
                                                    <script>
                                                        document.addEventListener("DOMContentLoaded", function () {
                                                            const ctx = document.getElementById('{{ $chartId }}');
                                                            if (!ctx) return;

                                                            new Chart(ctx, {
                                                                type: '{{ $isPie ? 'pie' : 'bar' }}',
                                                                data: {
                                                                    labels: {!! json_encode($labels) !!},
                                                                    datasets: [{
                                                                        label: 'Jumlah Jawaban',
                                                                        data: {!! json_encode($data) !!},
                                                                        backgroundColor: [
                                                                            '#3b82f6', '#22c55e', '#eab308', '#ef4444',
                                                                            '#8b5cf6', '#14b8a6', '#f97316', '#ec4899'
                                                                        ],
                                                                        borderWidth: 1,
                                                                        borderRadius: 6
                                                                    }]
                                                                },
                                                                options: {
                                                                    maintainAspectRatio: false,
                                                                    responsive: true,
                                                                    plugins: {
                                                                        legend: {
                                                                            display: {{ $isPie ? 'true' : 'false' }},
                                                                            position: 'bottom',
                                                                            labels: {
                                                                                boxWidth: 10,
                                                                                padding: 8,
                                                                                font: {
                                                                                    size: 10
                                                                                }
                                                                            }
                                                                        },
                                                                        tooltip: {
                                                                            backgroundColor: '#0f172a',
                                                                            titleFont: {
                                                                                size: 12
                                                                            },
                                                                            bodyFont: {
                                                                                size: 11
                                                                            },
                                                                            padding: 8
                                                                        }
                                                                    },
                                                                    scales: {!! $isPie
                                            ? '{}'
                                            : '{ y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } }' !!}
                                                                }
                                                            });
                                                        });
                                                    </script>
                                        @endpush
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        @if (!empty($presensiTrend7))
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const ctx = document.getElementById('chartPresensi7');
                    if (!ctx) return;

                    const src = @json($presensiTrend7 ?? []);
                    const labels = src.map(r => r.tanggal ?? r.label ?? '');
                    const data = src.map(r => r.persen ?? r.percentage ?? 0);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Persentase Hadir (%)',
                                data: data,
                                borderColor: '#0ea5e9',
                                backgroundColor: 'rgba(14,165,233,0.10)',
                                fill: true,
                                tension: 0.38,
                                pointRadius: 4,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#38bdf8',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 900
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.88)',
                                    titleColor: '#fff',
                                    bodyColor: '#e2e8f0',
                                    padding: 10,
                                    displayColors: false,
                                    callbacks: {
                                        label: function (context) {
                                            return ' ' + context.raw + '%';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    suggestedMax: 100,
                                    ticks: {
                                        color: '#64748b',
                                        callback: value => value + '%',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(148,163,184,0.14)',
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        @endif

        @if (!$rekapNilaiCollection->isEmpty())
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const ctx = document.getElementById('chartNilaiTingkat');
                    if (!ctx) return;

                    const src = @json($rekapNilaiTingkat ?? []);
                    const labels = src.map(r => r.tingkat ?? r.label ?? '');
                    const data = src.map(r => r.rata_rata ?? r.rata ?? 0);

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Rata-rata Nilai',
                                data: data,
                                backgroundColor: 'rgba(59, 130, 246, 0.85)',
                                borderRadius: 10,
                                borderSkipped: false,
                                maxBarThickness: 48
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 900
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.88)',
                                    titleColor: '#fff',
                                    bodyColor: '#e2e8f0',
                                    padding: 10,
                                    displayColors: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(148,163,184,0.14)',
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });
                });
                        </script>
        @endif
    @endpush
@endsection