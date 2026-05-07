@extends('kepsek.layout')
@section('title', 'Detail Ekstrakurikuler')

@section('content')
    @php
        $pembina = is_array($ekskul->pembina ?? null)
            ? ($ekskul->pembina['nama'] ?? '-')
            : (is_object($ekskul->pembina ?? null)
                ? ($ekskul->pembina->nama ?? '-')
                : '-');

        $anggota = collect($ekskul->anggota ?? [])->map(function ($row) {
            return is_array($row) ? (object) $row : $row;
        });

        $presensi = collect($ekskul->presensi ?? [])->map(function ($row) {
            return is_array($row) ? (object) $row : $row;
        });

        $hari = strtolower((string) ($ekskul->hari ?? ''));

        $hariBadgeClass = match ($hari) {
            'senin' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
            'selasa' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'rabu' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            'kamis' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
            'jumat' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'sabtu' => 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200',
            'minggu' => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
            default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
        };
    @endphp

    <div class="space-y-6">
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
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 13a4 4 0 10-8 0m12-5a3 3 0 11-6 0 3 3 0 016 0zM10 8a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 18a4 4 0 017.465-1.544M18 21v-1a4 4 0 00-3-3.873" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Detail Ekstrakurikuler
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi ekskul, anggota, dan presensi berdasarkan data SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('kepsek.sia-master.ekskul.index') }}"
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

                {{-- HERO --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Informasi Ekstrakurikuler
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                                    {{ $ekskul->nama ?? '-' }}
                                </h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Pembina {{ $pembina ?: '-' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-2 self-start rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $hariBadgeClass }}">
                                <span class="h-2 w-2 rounded-full bg-current opacity-80"></span>
                                <span>{{ $ekskul->hari ?? 'Belum diatur' }}</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div
                                class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                                    Hari
                                </p>
                                <p class="mt-2 truncate text-sm font-semibold text-slate-800">
                                    {{ $ekskul->hari ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                                    Jadwal
                                </p>
                                <p class="mt-2 truncate text-sm font-semibold text-slate-800">
                                    @if(!empty($ekskul->jam_mulai) || !empty($ekskul->jam_selesai))
                                        {{ $ekskul->jam_mulai ?? '-' }} - {{ $ekskul->jam_selesai ?? '-' }}
                                    @else
                                        Belum diatur
                                    @endif
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-500">
                                    Lokasi
                                </p>
                                <p class="mt-2 truncate text-sm font-semibold text-slate-800">
                                    {{ $ekskul->lokasi ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                                    Anggota Aktif
                                </p>
                                <p class="mt-2 truncate text-sm font-semibold text-slate-800">
                                    {{ $anggota->count() }} siswa
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ANGGOTA --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Anggota Ekstrakurikuler</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Daftar siswa yang terdaftar pada ekstrakurikuler ini.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                                {{ $anggota->count() }} siswa
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">NIS</th>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Tahun Ajaran</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($anggota as $row)
                                    <tr class="transition duration-300 hover:bg-blue-50/40">
                                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-500">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-900">
                                            {{ $row->siswa_nis ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->siswa_nama ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->tahun_ajaran ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if(($row->status ?? '') === 'aktif')
                                                <span
                                                    class="inline-flex items-center justify-center gap-1 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-emerald-200 bg-emerald-50 text-emerald-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center justify-center gap-1 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-slate-200 bg-slate-50 text-slate-600">
                                                    {{ ucfirst($row->status ?? 'nonaktif') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16 13a4 4 0 10-8 0m12-5a3 3 0 11-6 0 3 3 0 016 0zM10 8a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18a4 4 0 017.465-1.544M18 21v-1a4 4 0 00-3-3.873" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        Belum ada anggota terdaftar.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Data anggota ekstrakurikuler belum tersedia.
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

                {{-- PRESENSI --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Presensi Ekstrakurikuler</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Riwayat kehadiran siswa dalam kegiatan ekstrakurikuler ini.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-medium text-sky-700 ring-1 ring-sky-200">
                                {{ $presensi->count() }} data
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">NIS</th>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($presensi as $row)
                                    @php
                                        $status = strtoupper((string) ($row->status ?? 'H'));
                                        $label = match ($status) {
                                            'H' => 'Hadir',
                                            'I' => 'Izin',
                                            'S' => 'Sakit',
                                            'A' => 'Alfa',
                                            default => $status,
                                        };
                                    @endphp

                                    <tr class="transition duration-300 hover:bg-blue-50/40">
                                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-500">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->tanggal ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->siswa_nis ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->siswa_nama ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold ring-1
                                                @switch($status)
                                                    @case('H') bg-emerald-50 text-emerald-700 ring-emerald-200 @break
                                                    @case('I') bg-sky-50 text-sky-700 ring-sky-200 @break
                                                    @case('S') bg-amber-50 text-amber-700 ring-amber-200 @break
                                                    @case('A') bg-rose-50 text-rose-700 ring-rose-200 @break
                                                    @default bg-slate-50 text-slate-600 ring-slate-200
                                                @endswitch">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $row->keterangan ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16 13a4 4 0 10-8 0m12-5a3 3 0 11-6 0 3 3 0 016 0zM10 8a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18a4 4 0 017.465-1.544M18 21v-1a4 4 0 00-3-3.873" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        Belum ada data presensi.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Riwayat presensi ekstrakurikuler belum tersedia.
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