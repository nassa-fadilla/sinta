@extends('admin.layout')
@section('title', 'Detail Rombel')

@section('content')
    @php
        $aktif = (int) ($rombel->aktif ?? 0);
        $totalSiswa = collect($rombel->siswa ?? [])->count();
        $jadwalRombel = collect($rombel->jadwal ?? []);
        $totalJadwal = $jadwalRombel->count();

        $mapelUnik = $jadwalRombel
            ->pluck('mapel')
            ->filter()
            ->unique()
            ->values();

        $guruUnik = $jadwalRombel
            ->pluck('guru')
            ->filter()
            ->unique()
            ->values();

        $totalMapel = $mapelUnik->count();
        $totalGuru = $guruUnik->count();

        $hariOrder = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $hariLabelMap = [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
        ];

        $jadwalByHari = [];
        foreach ($hariOrder as $hariKey) {
            $jadwalByHari[$hariKey] = $jadwalRombel
                ->filter(function ($j) use ($hariKey) {
                    return strtolower(trim((string) ($j->hari ?? ''))) === $hariKey;
                })
                ->values();
        }

        $hariAktifDefault = collect($hariOrder)
            ->first(fn($hariKey) => ($jadwalByHari[$hariKey] ?? collect())->isNotEmpty()) ?? 'senin';
    @endphp

    <div x-data="{ activeHari: '{{ $hariAktifDefault }}' }" class="space-y-6">

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v10H4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20h4" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Detail Rombel
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap rombel, anggota siswa, mata pelajaran, dan guru pengajar.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.sia-master.rombel.index') }}"
                        class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">

                {{-- SUMMARY CARDS --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="group relative overflow-hidden rounded-[1.5rem] border border-blue-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_40px_rgba(59,130,246,0.14)]">
                        <div class="absolute inset-x-0 top-0 h-1 bg-blue-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Jumlah Siswa
                                </p>
                                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ $totalSiswa }}
                                </h3>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Total anggota siswa.
                                </p>
                            </div>
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition duration-300 group-hover:scale-105 group-hover:bg-blue-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-[1.5rem] border border-violet-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_18px_40px_rgba(139,92,246,0.14)]">
                        <div class="absolute inset-x-0 top-0 h-1 bg-violet-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Jumlah Mapel
                                </p>
                                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ $totalMapel }}
                                </h3>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Total mata pelajaran.
                                </p>
                            </div>
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 transition duration-300 group-hover:scale-105 group-hover:bg-violet-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.75v10.5m-7.5-9h15m-12-3h9a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0116.5 19.5H7.5A2.25 2.25 0 015.25 17.25V7.5A2.25 2.25 0 017.5 5.25z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-[1.5rem] border border-emerald-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_40px_rgba(16,185,129,0.14)]">
                        <div class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Jumlah Guru
                                </p>
                                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ $totalGuru }}
                                </h3>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Total guru mengajar.
                                </p>
                            </div>
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 transition duration-300 group-hover:scale-105 group-hover:bg-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group relative overflow-hidden rounded-[1.5rem] border border-amber-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_18px_40px_rgba(245,158,11,0.14)]">
                        <div class="absolute inset-x-0 top-0 h-1 bg-amber-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Jadwal
                                </p>
                                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ $totalJadwal }}
                                </h3>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Total jadwal.
                                </p>
                            </div>
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 transition duration-300 group-hover:scale-105 group-hover:bg-amber-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DETAIL + JADWAL --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

                    {{-- INFORMASI ROMBEL --}}
                    <section class="xl:col-span-5">
                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Informasi Rombel</h2>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Ringkasan data utama rombel.
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                    Detail
                                </span>
                            </div>

                            <table class="w-full table-auto text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="w-1/3 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Nama Rombel
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ $rombel->nama_rombel ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Tingkat
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->tingkat ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Wali Kelas
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->wali_kelas ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Kapasitas
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->kapasitas ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Ruang Kelas
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->ruang_kelas ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Tahun Ajaran
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->tahun_ajaran ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                            Status
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($aktif === 1)
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Aktif</span>
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Nonaktif</span>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- MAPEL & GURU BERDASARKAN TAB HARI --}}
                    <section class="xl:col-span-7">
                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-5 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h2 class="text-sm font-semibold text-slate-800">Mapel & Guru Pengajar</h2>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Jadwal pembelajaran berdasarkan hari yang dipilih.
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-violet-200 bg-white px-2.5 py-1 text-[11px] font-medium text-violet-700">
                                        {{ $totalJadwal }} Jadwal
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($hariOrder as $hariKey)
                                        @php
                                            $tabColor = match ($hariKey) {
                                                'senin' => 'blue',
                                                'selasa' => 'emerald',
                                                'rabu' => 'amber',
                                                'kamis' => 'violet',
                                                'jumat' => 'rose',
                                                default => 'slate',
                                            };

                                            $activeClass = match ($tabColor) {
                                                'blue' => 'border-blue-200 bg-blue-50 text-blue-700 shadow-sm',
                                                'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm',
                                                'amber' => 'border-amber-200 bg-amber-50 text-amber-700 shadow-sm',
                                                'violet' => 'border-violet-200 bg-violet-50 text-violet-700 shadow-sm',
                                                'rose' => 'border-rose-200 bg-rose-50 text-rose-700 shadow-sm',
                                                default => 'border-slate-200 bg-slate-50 text-slate-700 shadow-sm',
                                            };

                                            $countHari = ($jadwalByHari[$hariKey] ?? collect())->count();
                                        @endphp

                                        <button type="button" @click="activeHari = '{{ $hariKey }}'"
                                            :class="activeHari === '{{ $hariKey }}'
                                                                ? '{{ $activeClass }}'
                                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                            class="inline-flex items-center gap-2 rounded-2xl border px-3 py-2 text-xs font-semibold transition duration-200">
                                            <span>{{ $hariLabelMap[$hariKey] }}</span>
                                            <span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px]">
                                                {{ $countHari }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @foreach($hariOrder as $hariKey)
                                <div x-show="activeHari === '{{ $hariKey }}'" x-cloak class="overflow-x-auto">
                                    <table class="w-full table-auto text-sm">
                                        <thead class="bg-slate-50">
                                            <tr
                                                class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                <th class="w-14 px-4 py-3 text-center">No</th>
                                                <th class="px-4 py-3">Hari</th>
                                                <th class="px-4 py-3">Jam</th>
                                                <th class="px-4 py-3">Mapel</th>
                                                <th class="px-4 py-3">Guru</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100 text-slate-700">
                                            @forelse($jadwalByHari[$hariKey] as $j)
                                                @php
                                                    $hariBadgeClass = match ($hariKey) {
                                                        'senin' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                                        'selasa' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                                        'rabu' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                                        'kamis' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
                                                        'jumat' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                                        default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                                                    };
                                                @endphp

                                                <tr class="hover:bg-blue-50/30 transition">
                                                    <td class="px-4 py-3 text-center text-xs text-slate-500">
                                                        {{ $loop->iteration }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span
                                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $hariBadgeClass }}">
                                                            {{ $hariLabelMap[$hariKey] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700">
                                                        {{ $j->jam_mulai ?? '-' }}{{ $j->jam_mulai && $j->jam_selesai ? ' - ' : '' }}{{ $j->jam_selesai ?? '' }}
                                                    </td>
                                                    <td class="px-4 py-3 font-medium text-slate-800">
                                                        {{ $j->mapel ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700">
                                                        {{ $j->guru ?? '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-4 py-12 text-center">
                                                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                            <div
                                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M12 6.75v10.5m-7.5-9h15m-12-3h9a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0116.5 19.5H7.5A2.25 2.25 0 015.25 17.25V7.5A2.25 2.25 0 017.5 5.25z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <p class="text-sm font-semibold text-slate-700">
                                                                    Tidak ada jadwal hari {{ strtolower($hariLabelMap[$hariKey]) }}.
                                                                </p>
                                                                <p class="mt-1 text-xs text-slate-500">
                                                                    Belum ada mapel dan guru pengajar pada hari ini.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                {{-- ANGGOTA ROMBEL --}}
                <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Anggota Rombel</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Daftar siswa yang terdaftar pada rombel ini.
                            </p>
                        </div>

                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-[11px] font-medium text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ $totalSiswa }} siswa
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">NIS</th>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Jenis Kelamin</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Rombel</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($rombel->siswa ?? [] as $s)
                                    @php
                                        $jk = $s->jenis_kelamin ?? $s->jk ?? '-';
                                        $statusSiswa = strtolower((string) ($s->status ?? 'aktif'));

                                        $statusBadge = match ($statusSiswa) {
                                            'aktif' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                            'lulus' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                            'pindah' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                            'keluar' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                            default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                                        };

                                        $statusDot = match ($statusSiswa) {
                                            'aktif' => 'bg-emerald-500',
                                            'lulus' => 'bg-blue-500',
                                            'pindah' => 'bg-amber-500',
                                            'keluar' => 'bg-rose-500',
                                            default => 'bg-slate-400',
                                        };
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-4 py-3 text-center text-xs text-slate-500">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $s->nis ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ $s->nama ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                {{ $jk }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadge }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                                <span>{{ ucfirst($s->status ?? 'aktif') }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $rombel->nama_rombel ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4 20a8 8 0 0116 0" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">Belum ada anggota rombel.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Data siswa pada rombel ini belum tersedia.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </section>
    </div>
@endsection