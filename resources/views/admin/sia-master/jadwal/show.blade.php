@extends('admin.layout')
@section('title', 'Detail Jadwal')

@section('content')
    @php
        $mapel = $jadwal->mapel ?? '-';
        $hari = strtolower((string) ($jadwal->hari ?? ''));

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
                                    d="M8 3v3m8-3v3M4 9h16M5 6h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Detail Jadwal
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap jadwal pelajaran berdasarkan data SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.sia-master.jadwal.index') }}"
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
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Jadwal Pelajaran
                                </p>
                                <h2
                                    class="mt-2 break-words text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                                    {{ $mapel }}
                                </h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Rombel {{ $jadwal->rombel ?? '-' }} • Guru {{ $jadwal->guru ?? '-' }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-2 self-start rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $hariBadgeClass }}">
                                <span class="h-2 w-2 rounded-full bg-current opacity-80"></span>
                                <span>{{ $jadwal->hari ?? '-' }}</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div
                                class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                                    Rombel
                                </p>
                                <p class="mt-2 break-words text-sm font-semibold text-slate-800">
                                    {{ $jadwal->rombel ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                                    Jam
                                </p>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-800">
                                    {{ $jadwal->jam_mulai ?? '-' }}<br>
                                    <span class="text-xs font-medium text-slate-500">s.d.</span><br>
                                    {{ $jadwal->jam_selesai ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-500">
                                    Durasi
                                </p>
                                <p class="mt-2 break-words text-sm font-semibold text-slate-800">
                                    {{ $jadwal->durasi_jp ?? '-' }} JP
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                                    Tingkat
                                </p>
                                <p class="mt-2 break-words text-sm font-semibold text-slate-800">
                                    {{ $jadwal->tingkat ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DETAIL TABLE --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Rincian Jadwal</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Informasi lengkap rombel, mapel, guru, hari, jam, ruang, dan tahun ajaran.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] table-auto text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="w-1/3 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Rombel
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->rombel ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Mata Pelajaran
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->mapel ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Guru Pengajar
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->guru ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Hari
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->hari ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Jam Pelajaran
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->jam_mulai ?? '-' }} - {{ $jadwal->jam_selesai ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Durasi (JP)
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->durasi_jp ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Kelompok
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->kelompok ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        KKM
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->kkm ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        NIP
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->nip ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        NUPTK
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->nuptk ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Ruang Kelas
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->ruang_kelas ?? '-' }}
                                    </td>
                                </tr>

                                <tr class="transition hover:bg-blue-50/30">
                                    <td class="bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                        Tahun Ajaran
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $jadwal->tahun_ajaran ?? '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>
    </div>
@endsection