@extends('guru.layout')
@section('title', 'Dashboard Guru')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $user = auth()->user();

        $isWalikelas = $isWalikelas ?? false;
        $rombel = $rombel ?? null;

        $jadwalHariIni = collect($jadwalHariIni ?? []);
        $jadwalSeminggu = collect($jadwalSeminggu ?? []);

        /*
        |--------------------------------------------------------------------------
        | PENTING:
        | Pengumuman di halaman dashboard hanya memakai data yang sudah dikirim
        | dari controller. Jangan query ulang di blade agar filter tingkat/rombel
        | dari controller tetap konsisten.
        |--------------------------------------------------------------------------
        */
        $pengumuman = collect($pengumuman ?? [])->values();

        $totalSiswaRombel = $totalSiswaRombel ?? 0;
        $totalMapelRombel = $totalMapelRombel ?? 0;
        $totalGuruRombel = $totalGuruRombel ?? 0;
        $totalJpMingguan = $totalJpMingguan ?? 0;
        $totalLakiRombel = $totalLakiRombel ?? 0;
        $totalPerempuanRombel = $totalPerempuanRombel ?? 0;

        $aktif = $pengumuman->count();

        $chartHariLabels = $chartHariLabels ?? [];
        $chartHariJp = $chartHariJp ?? [];

        $namaRombel = data_get($rombel, 'nama_rombel') ?? data_get($rombel, 'nama') ?? null;
        $waliKelas = data_get($rombel, 'wali_kelas') ?? ($user->name ?? '-');
        $tahunAjaran = data_get($rombel, 'tahun_ajaran') ?? '-';
        $ruangKelas = data_get($rombel, 'ruang_kelas') ?? '-';

        $tanggalHariIni = \Illuminate\Support\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y');

        $latestPengumuman = $pengumuman
            ->sortByDesc(function ($item) {
                if (empty($item->publish_at)) {
                    return 0;
                }

                try {
                    return \Carbon\Carbon::parse($item->publish_at)->timestamp;
                } catch (\Throwable $e) {
                    return 0;
                }
            })
            ->take(3)
            ->values();

        $hariTabs = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariAktifDefault = \Illuminate\Support\Carbon::now('Asia/Jakarta')->translatedFormat('l');
        $hariAktifDefault = ucfirst($hariAktifDefault);

        if (!in_array($hariAktifDefault, $hariTabs, true)) {
            $hariAktifDefault = 'Senin';
        }
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
                            <span>Dashboard Guru</span>
                        </h1>

                        <p class="mt-2 text-sm text-slate-500">
                            Ringkasan akademik, jadwal pembelajaran, komposisi rombel, dan informasi sekolah yang terhubung
                            dari SIA.
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Login sebagai:
                            <span class="font-semibold text-slate-600">
                                {{ $user->name ?? 'Guru' }}
                            </span>
                            <span class="mx-1">•</span>
                            <span class="font-semibold text-blue-600">
                                {{ $isWalikelas ? 'Wali Kelas' : 'Guru' }}
                            </span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="min-w-[250px] rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-[0_10px_28px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(59,130,246,0.12)]">
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
                                        Tahun Ajaran & Rombel
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-800">
                                        {{ $tahunAjaran !== '-' ? $tahunAjaran : ($activeTahunAjaran ?? 'Belum tersedia') }}
                                        @if(!empty($activeSemester))
                                            • Semester {{ $activeSemester }}
                                        @endif
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500">
                                        {{ $namaRombel ? 'Rombel ' . $namaRombel : 'Belum terhubung ke rombel' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- QUICK STATS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Pengumuman Aktif --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(16,185,129,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-100/70 via-white/20 to-green-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-emerald-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
                            Pengumuman Aktif
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($aktif) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Informasi aktif sesuai rombel
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-emerald-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 3.75L4.5 7.5v9l6.75 3.75 6.75-3.75v-9L11.25 3.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 12v8.25M18 7.5l-6.75 4.5L4.5 7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Siswa Laki-laki --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(14,165,233,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-sky-100/70 via-white/20 to-cyan-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-sky-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">
                            Siswa Laki-laki
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($totalLakiRombel) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Komposisi siswa rombel
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-sky-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 3h5v5h-2V6.414l-3.293 3.293-1.414-1.414L17.586 5H16V3z" />
                            <path d="M10 5a5 5 0 100 10 5 5 0 000-10zM4 20a6 6 0 1112 0v1H4v-1z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Siswa Perempuan --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(236,72,153,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-pink-100/70 via-white/20 to-rose-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-pink-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-pink-700">
                            Siswa Perempuan
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($totalPerempuanRombel) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Komposisi siswa rombel
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-pink-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4a5 5 0 100 10 5 5 0 000-10zM11 15v2H9v2h2v2h2v-2h2v-2h-2v-2h-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- JP per Minggu --}}
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(245,158,11,0.16)]">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-100/70 via-white/20 to-yellow-100/70"></div>
                <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-amber-300/25 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">
                            JP per Minggu
                        </div>
                        <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
                            {{ number_format($totalJpMingguan) }}
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Total jam pelajaran rombel
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-amber-600 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHART + PANEL SAMPING --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Grafik JP --}}
            <div
                class="lg:col-span-2 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Distribusi Jam Pelajaran
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Akumulasi jam pelajaran per hari dalam satu minggu.
                        </p>
                    </div>
                </div>

                @if(!$isWalikelas || !$rombel || empty($chartHariLabels))
                    <div
                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Belum ada data distribusi jam pelajaran.
                    </div>
                @else
                    <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="h-[290px] w-full">
                            <canvas id="chartJpHari"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Ringkasan Rombel --}}
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Statistik Rombel
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Ringkasan data rombel yang sedang dibina.
                        </p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/90 px-3 py-3 text-blue-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Total Siswa</span>
                            <span class="text-lg font-semibold">{{ $totalSiswaRombel }}</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-sky-100 bg-sky-50/90 px-3 py-3 text-sky-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Total Mapel</span>
                            <span class="text-lg font-semibold">{{ $totalMapelRombel }}</span>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-3 py-3 text-emerald-700 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Total Guru</span>
                            <span class="text-lg font-semibold">{{ $totalGuruRombel }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        Rombel Aktif
                    </div>
                    <div class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $namaRombel ?: 'Belum terhubung' }}
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500">
                        Wali Kelas: {{ $waliKelas ?: '-' }}
                    </div>
                    <div class="mt-0.5 text-[11px] text-slate-500">
                        Ruang: {{ $ruangKelas ?: '-' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- JADWAL + INFO ROMBEL --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Jadwal Rombel --}}
            <div x-data="{ hariAktif: '{{ $hariAktifDefault }}' }"
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">

                <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Jadwal Rombel
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Jadwal pembelajaran mingguan untuk rombel yang dibina.
                        </p>
                    </div>

                    <span
                        class="inline-flex items-center rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700 shadow-sm">
                        {{ $namaRombel ? 'Rombel ' . $namaRombel : 'Rombel belum tersedia' }}
                    </span>
                </div>

                @if(!$rombel)
                    <div
                        class="mt-3 rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Jadwal belum bisa ditampilkan karena rombel belum terhubung.
                    </div>
                @else
                    {{-- TAB HARI --}}
                    <div class="mt-4 overflow-x-auto pb-1">
                        <div class="flex min-w-max gap-2">
                            @foreach($hariTabs as $hari)
                                @php
                                    $jumlahJadwalHari = collect($jadwalSeminggu->get($hari, []))->count();
                                @endphp

                                <button type="button" x-on:click="hariAktif = '{{ $hari }}'"
                                    class="inline-flex items-center gap-2 rounded-2xl border px-3.5 py-2 text-xs font-semibold transition duration-300"
                                    x-bind:class="hariAktif === '{{ $hari }}'
                                                                                        ? 'border-blue-200 bg-blue-50 text-blue-700 shadow-sm'
                                                                                        : 'border-slate-200 bg-white text-slate-500 hover:border-blue-100 hover:bg-blue-50/60 hover:text-blue-700'">
                                    <span>{{ $hari }}</span>

                                    <span
                                        class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[10px]"
                                        x-bind:class="hariAktif === '{{ $hari }}'
                                                                                            ? 'bg-blue-500 text-white'
                                                                                            : 'bg-slate-100 text-slate-500'">
                                        {{ $jumlahJadwalHari }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ISI JADWAL PER HARI --}}
                    <div class="mt-4">
                        @foreach($hariTabs as $hari)
                            @php
                                $jadwalPerHari = collect($jadwalSeminggu->get($hari, []));
                            @endphp

                            <div x-show="hariAktif === '{{ $hari }}'" x-cloak>
                                @if($jadwalPerHari->isEmpty())
                                    <div
                                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                                        Tidak ada jadwal pelajaran pada hari {{ $hari }}.
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach($jadwalPerHari as $j)
                                            @php
                                                $mulai = substr((string) data_get($j, 'jam_mulai', ''), 0, 5);
                                                $selesai = substr((string) data_get($j, 'jam_selesai', ''), 0, 5);
                                            @endphp

                                            <div
                                                class="rounded-[1.6rem] border border-slate-200 bg-white px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-800">
                                                            {{ data_get($j, 'mapel', '-') }}
                                                        </div>

                                                        <div class="mt-1 text-xs text-slate-500">
                                                            {{ $mulai ?: '-' }}
                                                            @if($selesai)
                                                                <span class="mx-1 text-slate-400">s.d.</span>
                                                                {{ $selesai }}
                                                            @endif
                                                        </div>

                                                        <div class="mt-1 text-[11px] text-slate-400">
                                                            Guru: {{ data_get($j, 'guru', '-') }}
                                                        </div>
                                                    </div>

                                                    <span
                                                        class="inline-flex shrink-0 items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700">
                                                        {{ data_get($j, 'durasi_jp', 0) }} JP
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Info Rombel --}}
            <div
                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
                <div class="mb-3">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Informasi Rombel
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Informasi dasar rombel yang terhubung pada akun ini.
                    </p>
                </div>

                @if($rombel)
                    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <tr class="transition duration-300 hover:bg-blue-50/30">
                                    <td class="w-[42%] bg-slate-50 px-4 py-3 font-medium text-slate-600">Nama Rombel</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $namaRombel ?: '-' }}</td>
                                </tr>
                                <tr class="transition duration-300 hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 font-medium text-slate-600">Wali Kelas</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $waliKelas ?: '-' }}</td>
                                </tr>
                                <tr class="transition duration-300 hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 font-medium text-slate-600">Tahun Ajaran</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $tahunAjaran ?: '-' }}</td>
                                </tr>
                                <tr class="transition duration-300 hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 font-medium text-slate-600">Ruang Kelas</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $ruangKelas ?: '-' }}</td>
                                </tr>
                                <tr class="transition duration-300 hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 font-medium text-slate-600">Total Siswa</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $totalSiswaRombel }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div
                        class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                        Akun ini belum terhubung ke rombel wali kelas di SIA.
                    </div>
                @endif
            </div>
        </div>

        {{-- PENGUMUMAN --}}
        <div
            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <span
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-500 text-white shadow-md shadow-blue-200/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 4l2-2 2 2m-2-2v9m-7 4a7 7 0 1114 0v3H5v-3z" />
                            </svg>
                        </span>
                        <span>Pengumuman Sekolah</span>
                    </h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Pengumuman yang sesuai dengan guru dan rombel binaan.
                    </p>
                </div>

                <a href="{{ route('guru.pengumuman.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition duration-300 hover:bg-blue-100">
                    Lihat semua
                </a>
            </div>

            @if($latestPengumuman->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
                    Belum ada pengumuman aktif yang sesuai untuk Anda.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($latestPengumuman as $p)
                        @php
                            $jenis = strtolower((string) ($p->jenis ?? 'lainnya'));

                            [$strip, $badge] = match ($jenis) {
                                'akademik' => ['from-blue-500 to-sky-400', 'bg-blue-50 text-blue-700 border-blue-100'],
                                'kegiatan' => ['from-sky-500 to-cyan-400', 'bg-sky-50 text-sky-700 border-sky-100'],
                                'prestasi' => ['from-indigo-500 to-violet-400', 'bg-indigo-50 text-indigo-700 border-indigo-100'],
                                'umum' => ['from-violet-500 to-fuchsia-400', 'bg-violet-50 text-violet-700 border-violet-100'],
                                default => ['from-slate-400 to-slate-300', 'bg-slate-50 text-slate-700 border-slate-100'],
                            };

                            $tanggalPublish = '-';

                            if (!empty($p->publish_at)) {
                                try {
                                    $tanggalPublish = \Carbon\Carbon::parse($p->publish_at)->translatedFormat('d M Y');
                                } catch (\Throwable $e) {
                                    $tanggalPublish = '-';
                                }
                            }
                        @endphp

                        <a href="{{ route('guru.pengumuman.show', $p) }}"
                            class="group block overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex min-w-0">
                                <div class="w-1.5 shrink-0 rounded-l-[1.6rem] bg-gradient-to-b {{ $strip }}"></div>

                                <div class="min-w-0 flex-1 px-4 py-3">
                                    <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold leading-6 text-slate-800 sm:pr-3">
                                                {{ $p->judul }}
                                            </div>

                                            <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                                {{ $tanggalPublish }}
                                            </div>

                                            <p class="mt-2 text-xs leading-5 text-slate-600">
                                                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($p->isi ?? '')), 150) }}
                                            </p>
                                        </div>

                                        <div class="shrink-0">
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $badge }}">
                                                {{ ucfirst($jenis) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    @if($isWalikelas && $rombel && !empty($chartHariLabels) && !empty($chartHariJp))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('chartJpHari');
                if (!el) return;

                new Chart(el, {
                    type: 'bar',
                    data: {
                        labels: @json($chartHariLabels),
                        datasets: [{
                            data: @json($chartHariJp),
                            borderRadius: 10,
                            borderSkipped: false,
                            maxBarThickness: 34,
                            backgroundColor: 'rgba(37, 99, 235, 0.85)'
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
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#64748b',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    color: '#64748b',
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: 'rgba(148,163,184,0.14)',
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