@extends('admin.layout')
@section('title', 'Detail Presensi Siswa')

@section('content')
    @php
        $isMapelDetail = $isMapelDetail ?? false;

        $hadir = (int) ($sesi->rekap->hadir ?? 0);
        $alfa = (int) ($sesi->rekap->alfa ?? 0);
        $sakit = (int) ($sesi->rekap->sakit ?? 0);
        $izin = (int) ($sesi->rekap->izin ?? 0);

        $total = max(1, $hadir + $alfa + $sakit + $izin);

        $pct = [
            'hadir' => $hadir / $total * 100,
            'alfa' => $alfa / $total * 100,
            'sakit' => $sakit / $total * 100,
            'izin' => $izin / $total * 100,
        ];
    @endphp

    <div x-data="{
                        sortTanggal: 'desc',
                        sortJam: 'desc',
                        rows: @js(
                            collect($presensi ?? [])->map(function ($row) {
                                return [
                                    'tanggal' => $row->tanggal ?? '-',
                                    'waktu_mulai' => $row->waktu_mulai ?? '-',
                                    'waktu_tutup' => $row->waktu_tutup ?? '-',
                                    'status' => $row->status ?? '-',
                                    'dipindai_pada' => $row->dipindai_pada ?? '-',
                                    'nis' => $row->nis ?? '-',
                                    'nama' => $row->nama ?? '-',
                                ];
                            })->values()->all()
                        ),
                        get sortedRows() {
                            if (!@js($isMapelDetail)) return this.rows;

                            return [...this.rows].sort((a, b) => {
                                const dateA = new Date((a.tanggal ?? '1970-01-01') + ' ' + (a.waktu_mulai ?? '00:00')).getTime();
                                const dateB = new Date((b.tanggal ?? '1970-01-01') + ' ' + (b.waktu_mulai ?? '00:00')).getTime();

                                if (dateA !== dateB) {
                                    return this.sortTanggal === 'desc' ? dateB - dateA : dateA - dateB;
                                }

                                const jamA = (a.waktu_mulai ?? '00:00').toString();
                                const jamB = (b.waktu_mulai ?? '00:00').toString();

                                return this.sortJam === 'desc'
                                    ? jamB.localeCompare(jamA)
                                    : jamA.localeCompare(jamB);
                            });
                        }
                    }" class="space-y-6">

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
                                    d="M9 5h6m-3-2.25A1.75 1.75 0 0 1 13.75 4.5h.75A2.75 2.75 0 0 1 17.25 7.25v9.5A2.75 2.75 0 0 1 14.5 19.5h-5A2.75 2.75 0 0 1 6.75 16.75v-9.5A2.75 2.75 0 0 1 9.5 4.5h.75A1.75 1.75 0 0 1 12 2.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 13 1.5 1.5L15 10" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $isMapelDetail ? 'Detail Presensi per Mapel' : 'Detail Presensi Sesi' }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                @if($isMapelDetail)
                                    Riwayat seluruh sesi presensi siswa pada mata pelajaran yang dipilih.
                                @else
                                    Rincian kehadiran siswa pada satu sesi presensi berdasarkan data SIA.
                                @endif
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.sia-master.presensi.index', array_filter(['q' => $q ?? null])) }}"
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

                {{-- HERO CARD --}}
                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    {{ $isMapelDetail ? 'Informasi Presensi Siswa' : 'Informasi Sesi Presensi' }}
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                                    {{ $sesi->mapel->nama_mapel ?? '-' }}
                                </h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Guru {{ $sesi->guru->nama ?? '-' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-2 self-start rounded-full border border-sky-200 bg-sky-50 px-3.5 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                {{ ucfirst($sesi->status ?? '-') }}
                            </span>
                        </div>

                        @if($isMapelDetail)
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div
                                    class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                                        Siswa
                                    </p>
                                    <p class="mt-2 text-lg font-semibold text-slate-800">
                                        {{ $sesi->siswa->nama ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        NIS: {{ $sesi->siswa->nis ?? '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                                        Rombel
                                    </p>
                                    <p class="mt-2 text-lg font-semibold text-slate-800">
                                        {{ $sesi->rombel->nama_rombel ?? '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                                        Total Sesi
                                    </p>
                                    <p class="mt-2 text-lg font-semibold text-slate-800">
                                        {{ $presensi->count() }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
                                <div
                                    class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                                        Mulai
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800">
                                        {{ $sesi->mulai_pada ? \Illuminate\Support\Carbon::parse($sesi->mulai_pada)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-500">
                                        Ditutup
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800">
                                        {{ $sesi->ditutup_pada ? \Illuminate\Support\Carbon::parse($sesi->ditutup_pada)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                                        Guru
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800">
                                        {{ $sesi->guru->nama ?? '-' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                                        Status
                                    </p>
                                    <p class="mt-2 text-sm font-semibold capitalize text-slate-800">
                                        {{ $sesi->status ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- REKAP --}}
                <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">
                                    Rekap Presensi
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $isMapelDetail ? 'Ringkasan seluruh sesi presensi pada mapel ini.' : 'Ringkasan kehadiran siswa pada sesi ini.' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                                {{ $hadir + $alfa + $sakit + $izin }} data
                            </span>
                        </div>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 text-sm">
                            <div
                                class="group rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                <div
                                    class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-600">
                                    <span
                                        class="inline-flex h-4 w-4 rounded-full bg-emerald-500 transition duration-300 group-hover:scale-110"></span>
                                    Hadir
                                </div>
                                <p class="mt-4 flex items-end gap-2">
                                    <span class="text-3xl font-bold tracking-tight text-emerald-700">{{ $hadir }}</span>
                                    <span class="pb-0.5 text-sm text-slate-500">
                                        ({{ number_format($pct['hadir'], 0) }}%)
                                    </span>
                                </p>
                            </div>

                            <div
                                class="group rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-[0_12px_24px_rgba(244,63,94,0.10)]">
                                <div
                                    class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-rose-600">
                                    <span
                                        class="inline-flex h-4 w-4 rounded-full bg-rose-500 transition duration-300 group-hover:scale-110"></span>
                                    Alfa
                                </div>
                                <p class="mt-4 flex items-end gap-2">
                                    <span class="text-3xl font-bold tracking-tight text-rose-700">{{ $alfa }}</span>
                                    <span class="pb-0.5 text-sm text-slate-500">
                                        ({{ number_format($pct['alfa'], 0) }}%)
                                    </span>
                                </p>
                            </div>

                            <div
                                class="group rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                <div
                                    class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-600">
                                    <span
                                        class="inline-flex h-4 w-4 rounded-full bg-amber-500 transition duration-300 group-hover:scale-110"></span>
                                    Sakit
                                </div>
                                <p class="mt-4 flex items-end gap-2">
                                    <span class="text-3xl font-bold tracking-tight text-amber-700">{{ $sakit }}</span>
                                    <span class="pb-0.5 text-sm text-slate-500">
                                        ({{ number_format($pct['sakit'], 0) }}%)
                                    </span>
                                </p>
                            </div>

                            <div
                                class="group rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-[0_12px_24px_rgba(14,165,233,0.10)]">
                                <div
                                    class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-600">
                                    <span
                                        class="inline-flex h-4 w-4 rounded-full bg-sky-500 transition duration-300 group-hover:scale-110"></span>
                                    Izin
                                </div>
                                <p class="mt-4 flex items-end gap-2">
                                    <span class="text-3xl font-bold tracking-tight text-sky-700">{{ $izin }}</span>
                                    <span class="pb-0.5 text-sm text-slate-500">
                                        ({{ number_format($pct['izin'], 0) }}%)
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="flex h-full w-full">
                                    @if($hadir > 0)
                                        <div class="h-full bg-emerald-400" style="width: {{ $pct['hadir'] }}%"></div>
                                    @endif
                                    @if($alfa > 0)
                                        <div class="h-full bg-rose-400" style="width: {{ $pct['alfa'] }}%"></div>
                                    @endif
                                    @if($sakit > 0)
                                        <div class="h-full bg-amber-400" style="width: {{ $pct['sakit'] }}%"></div>
                                    @endif
                                    @if($izin > 0)
                                        <div class="h-full bg-sky-400" style="width: {{ $pct['izin'] }}%"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-3 text-[11px] text-slate-500">
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Hadir
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-rose-400"></span> Alfa
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-amber-400"></span> Sakit
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-sky-400"></span> Izin
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- TABEL --}}
                <section>
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">
                                        {{ $isMapelDetail ? 'Riwayat Sesi Presensi' : 'Rincian Presensi Siswa' }}
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if($isMapelDetail)
                                            Daftar seluruh sesi presensi siswa pada mata pelajaran ini.
                                        @else
                                            Status kehadiran seluruh anggota rombel pada sesi ini.
                                        @endif
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 ring-1 ring-slate-200">
                                    {{ $presensi->count() }} data
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            @if($isMapelDetail)
                                <table class="min-w-full table-auto text-sm">
                                    <thead class="bg-slate-50">
                                        <tr
                                            class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                            <th class="w-14 px-4 py-3 text-center">No</th>
                                            <th class="px-4 py-3">
                                                <button type="button"
                                                    @click="sortTanggal = sortTanggal === 'desc' ? 'asc' : 'desc'"
                                                    class="inline-flex items-center gap-1.5 transition hover:text-blue-600">
                                                    <span>Tanggal</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                                    </svg>
                                                </button>
                                            </th>
                                            <th class="px-4 py-3">
                                                <button type="button" @click="sortJam = sortJam === 'desc' ? 'asc' : 'desc'"
                                                    class="inline-flex items-center gap-1.5 transition hover:text-blue-600">
                                                    <span>Jam Mulai</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                                    </svg>
                                                </button>
                                            </th>
                                            <th class="px-4 py-3">Jam Tutup</th>
                                            <th class="px-4 py-3 text-center">Status</th>
                                            <th class="px-4 py-3">Dipindai pada</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <template x-for="(row, index) in sortedRows" :key="index">
                                            <tr class="transition duration-300 hover:bg-blue-50/30">
                                                <td class="px-4 py-3 text-center font-semibold text-slate-500"
                                                    x-text="index + 1"></td>
                                                <td class="px-4 py-3 font-medium text-slate-800" x-text="row.tanggal ?? '-'">
                                                </td>
                                                <td class="px-4 py-3 text-slate-700" x-text="row.waktu_mulai ?? '-'"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="row.waktu_tutup ?? '-'"></td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold capitalize ring-1"
                                                        :class="{
                                                                            'bg-emerald-50 text-emerald-700 ring-emerald-200': row.status === 'hadir',
                                                                            'bg-amber-50 text-amber-700 ring-amber-200': row.status === 'terlambat',
                                                                            'bg-sky-50 text-sky-700 ring-sky-200': row.status === 'izin',
                                                                            'bg-orange-50 text-orange-700 ring-orange-200': row.status === 'sakit',
                                                                            'bg-rose-50 text-rose-700 ring-rose-200': row.status === 'alfa',
                                                                            'bg-slate-50 text-slate-700 ring-slate-200': !['hadir', 'terlambat', 'izin', 'sakit', 'alfa'].includes(row.status)
                                                                        }">
                                                        <span class="h-1.5 w-1.5 rounded-full" :class="{
                                                                            'bg-emerald-500': row.status === 'hadir',
                                                                            'bg-amber-500': row.status === 'terlambat',
                                                                            'bg-sky-500': row.status === 'izin',
                                                                            'bg-orange-500': row.status === 'sakit',
                                                                            'bg-rose-500': row.status === 'alfa',
                                                                            'bg-slate-400': !['hadir', 'terlambat', 'izin', 'sakit', 'alfa'].includes(row.status)
                                                                        }"></span>
                                                        <span x-text="row.status"></span>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-700" x-text="row.dipindai_pada ?? '-'"></td>
                                            </tr>
                                        </template>

                                        @if(collect($presensi)->isEmpty())
                                            <tr>
                                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                                    Belum ada data presensi untuk mapel ini.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            @else
                                <table class="min-w-full table-auto text-sm">
                                    <thead class="bg-slate-50">
                                        <tr
                                            class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                            <th class="w-14 px-4 py-3 text-center">No</th>
                                            <th class="px-4 py-3">NIS</th>
                                            <th class="px-4 py-3">Nama</th>
                                            <th class="px-4 py-3 text-center">Status</th>
                                            <th class="px-4 py-3">Dipindai pada</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($presensi as $i => $row)
                                            @php
                                                $status = strtolower((string) ($row->status ?? '-'));

                                                $badgeClass = match ($status) {
                                                    'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                    'terlambat' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                    'izin' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                                    'sakit' => 'bg-orange-50 text-orange-700 ring-orange-200',
                                                    'alfa' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                                    default => 'bg-slate-50 text-slate-700 ring-slate-200',
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

                                            <tr class="transition duration-300 hover:bg-blue-50/30">
                                                <td class="px-4 py-3 text-center font-semibold text-slate-500">
                                                    {{ $i + 1 }}
                                                </td>

                                                <td class="px-4 py-3 font-medium text-slate-800">
                                                    {{ $row->nis ?? '-' }}
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $row->nama ?? '-' }}
                                                </td>

                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold capitalize ring-1 {{ $badgeClass }}">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                        {{ $status }}
                                                    </span>
                                                </td>

                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ $row->dipindai_pada ? \Illuminate\Support\Carbon::parse($row->dipindai_pada)->format('d/m/Y H:i') : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                                    Belum ada data presensi untuk sesi ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection