@extends('kepsek.layout')
@section('title', 'Detail Mata Pelajaran')

@section('content')

    @php
        $idMapel = data_get($mapel, 'id', '-');
        $namaMapel = data_get($mapel, 'nama_mapel', 'Mata Pelajaran');
        $statusMapel = strtolower((string) data_get($mapel, 'status', ''));
        $kelompok = data_get($mapel, 'kelompok', '-');
        $kkm = data_get($mapel, 'kkm', '-');

        $guruPengajarRaw = data_get($mapel, 'guru_pengajar', []);
        if (is_string($guruPengajarRaw)) {
            $decoded = json_decode($guruPengajarRaw, true);
            $guruPengajar = is_array($decoded) ? $decoded : [];
        } elseif (is_array($guruPengajarRaw)) {
            $guruPengajar = $guruPengajarRaw;
        } elseif ($guruPengajarRaw instanceof \Illuminate\Support\Collection) {
            $guruPengajar = $guruPengajarRaw->toArray();
        } else {
            $guruPengajar = [];
        }

        $jumlahGuru = is_array($guruPengajar) ? count($guruPengajar) : 0;
    @endphp

    <div class="space-y-6">

        <section
            class="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-100 bg-white px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-3">
                        <span
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75v10.5m-7.5-9h15m-12-3h9a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0116.5 19.5H7.5A2.25 2.25 0 015.25 17.25V7.5A2.25 2.25 0 017.5 5.25z" />
                            </svg>
                        </span>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Detail Mata Pelajaran
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $namaMapel }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap mata pelajaran dari SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('kepsek.sia-master.mapel.index') }}"
                        class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            {{-- BODY --}}
            <div class="space-y-6 p-5 text-sm md:p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12">

                    {{-- DETAIL MAPEL --}}
                    <section class="md:col-span-5">
                        <div
                            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <div class="border-b border-slate-100 bg-white px-4 py-4">
                                <h2 class="text-sm font-semibold text-slate-800">Informasi Mapel</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Informasi dasar mata pelajaran dan status aktifnya.
                                </p>
                            </div>

                            <table class="w-full table-auto text-slate-800">
                                <tbody class="divide-y divide-slate-100">
                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="w-1/3 bg-slate-50 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            ID Mapel
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $idMapel }}</td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Nama Mapel
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $namaMapel }}</td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Kelompok
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $kelompok }}</td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            KKM
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $kkm }}</td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Status
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($statusMapel === 'aktif')
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

                    {{-- GURU PENGAJAR --}}
                    <section class="md:col-span-7">
                        <div
                            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-center justify-between border-b border-slate-100 bg-white px-4 py-4">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">
                                        Guru Pengajar
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Daftar guru yang mengampu mata pelajaran ini.
                                    </p>
                                </div>

                                @if($jumlahGuru > 0)
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        {{ $jumlahGuru }} guru
                                    </span>
                                @endif
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full table-auto text-sm">
                                    <thead
                                        class="bg-slate-50 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <tr class="text-left">
                                            <th class="w-14 px-4 py-3 text-center">No</th>
                                            <th class="px-4 py-3">Nama</th>
                                            <th class="px-4 py-3">NIP</th>
                                            <th class="px-4 py-3">NUPTK</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        @forelse($guruPengajar as $g)
                                            <tr
                                                class="group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.35)]">
                                                <td class="px-4 py-3 text-center text-xs text-slate-500">
                                                    {{ $loop->iteration }}
                                                </td>
                                                <td class="px-4 py-3 font-medium text-slate-800">
                                                    {{ data_get($g, 'nama', '-') }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ data_get($g, 'nip', '-') }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    {{ data_get($g, 'nuptk', '-') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-10 text-center">
                                                    <div class="flex flex-col items-center justify-center gap-2 text-slate-500">
                                                        <div
                                                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M4 20a8 8 0 0116 0" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-700">Belum ada guru
                                                                pengajar.</p>
                                                            <p class="mt-1 text-xs text-slate-500">Data guru pengajar belum
                                                                tersedia untuk mata pelajaran ini.</p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </section>
    </div>

@endsection