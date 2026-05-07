@extends('admin.layout')
@section('title', 'Detail Nilai Siswa')

@section('content')
    @php
        $status = strtolower((string) ($nilai->status ?? 'tidak_tuntas'));
        $statusPenilaian = strtolower((string) ($nilai->status_penilaian ?? 'draft'));

        $badgeStatusClass = $status === 'tuntas'
            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';

        $badgePenilaianClass = $statusPenilaian === 'final'
            ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'
            : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';

        $lingkupMateri = [
            [
                'label' => 'LM 1',
                'tp1' => $nilai->lm1_tp1 ?? null,
                'tp2' => $nilai->lm1_tp2 ?? null,
                'tp3' => $nilai->lm1_tp3 ?? null,
                'tp4' => $nilai->lm1_tp4 ?? null,
                'nilai' => $nilai->lm1_nilai ?? null,
                'theme' => 'blue',
            ],
            [
                'label' => 'LM 2',
                'tp1' => $nilai->lm2_tp1 ?? null,
                'tp2' => $nilai->lm2_tp2 ?? null,
                'tp3' => $nilai->lm2_tp3 ?? null,
                'tp4' => $nilai->lm2_tp4 ?? null,
                'nilai' => $nilai->lm2_nilai ?? null,
                'theme' => 'emerald',
            ],
            [
                'label' => 'LM 3',
                'tp1' => $nilai->lm3_tp1 ?? null,
                'tp2' => $nilai->lm3_tp2 ?? null,
                'tp3' => $nilai->lm3_tp3 ?? null,
                'tp4' => $nilai->lm3_tp4 ?? null,
                'nilai' => $nilai->lm3_nilai ?? null,
                'theme' => 'amber',
            ],
            [
                'label' => 'LM 4',
                'tp1' => $nilai->lm4_tp1 ?? null,
                'tp2' => $nilai->lm4_tp2 ?? null,
                'tp3' => $nilai->lm4_tp3 ?? null,
                'tp4' => $nilai->lm4_tp4 ?? null,
                'nilai' => $nilai->lm4_nilai ?? null,
                'theme' => 'violet',
            ],
        ];
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
                                    d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h9.25A1.25 1.25 0 0 1 18 4.25V18a1 1 0 0 1-1 1H7.5A2.75 2.75 0 0 1 4.75 16.25V5.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 11.25h3.5M9 14.5h2" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Detail Nilai Siswa
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Rincian penilaian per mata pelajaran berdasarkan data akademik SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.sia-master.nilai.index', ['q' => $q]) }}"
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
                                    Detail Penilaian
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                                    {{ $nilai->mapel ?? '-' }}
                                </h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    {{ $siswa->nama ?? '-' }} • NIS {{ $siswa->nis ?? '-' }} •
                                    {{ $siswa->rombel_nama ?? '-' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $badgeStatusClass }}">
                                    <span
                                        class="h-2 w-2 rounded-full {{ $status === 'tuntas' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ str_replace('_', ' ', $status) }}
                                </span>

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $badgePenilaianClass }}">
                                    <span
                                        class="h-2 w-2 rounded-full {{ $statusPenilaian === 'final' ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                    {{ $statusPenilaian }}
                                </span>
                            </div>
                        </div>

                        {{-- 4 MINI CARD SEJAJAR --}}
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div
                                class="min-w-0 rounded-2xl border border-blue-200 bg-blue-50 px-3.5 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                                    Rombel
                                </p>
                                <p class="mt-2 truncate text-[15px] font-semibold text-slate-800">
                                    {{ $nilai->rombel ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="min-w-0 rounded-2xl border border-emerald-200 bg-emerald-50 px-3.5 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                                    Guru
                                </p>
                                <p class="mt-2 truncate text-[15px] font-semibold text-slate-800">
                                    {{ $nilai->guru ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="min-w-0 rounded-2xl border border-amber-200 bg-amber-50 px-3.5 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-500">
                                    Tahun Ajaran
                                </p>
                                <p class="mt-2 truncate text-[15px] font-semibold text-slate-800">
                                    {{ $nilai->tahun_ajaran ?? '-' }}
                                </p>
                            </div>

                            <div
                                class="min-w-0 rounded-2xl border border-violet-200 bg-violet-50 px-3.5 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                                    Semester / Nilai
                                </p>
                                <p class="mt-2 truncate text-[15px] font-semibold text-slate-800">
                                    {{ ucfirst($nilai->semester ?? '-') }} • {{ $nilai->nilai_akhir ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RINCIAN LM --}}
                <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Rincian Nilai per Lingkup Materi</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Setiap lingkup materi terdiri dari TP1 sampai TP4 dan menghasilkan nilai LM.
                        </p>
                    </div>

                    <div class="p-5 md:p-6">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            @foreach($lingkupMateri as $lm)
                                @php
                                    $theme = $lm['theme'];

                                    $cardClass = match ($theme) {
                                        'blue' => 'border-blue-200 bg-blue-50/70 hover:shadow-[0_14px_28px_rgba(59,130,246,0.12)]',
                                        'emerald' => 'border-emerald-200 bg-emerald-50/70 hover:shadow-[0_14px_28px_rgba(16,185,129,0.12)]',
                                        'amber' => 'border-amber-200 bg-amber-50/70 hover:shadow-[0_14px_28px_rgba(245,158,11,0.12)]',
                                        default => 'border-violet-200 bg-violet-50/70 hover:shadow-[0_14px_28px_rgba(139,92,246,0.12)]',
                                    };

                                    $labelClass = match ($theme) {
                                        'blue' => 'text-blue-600',
                                        'emerald' => 'text-emerald-600',
                                        'amber' => 'text-amber-600',
                                        default => 'text-violet-600',
                                    };

                                    $nilaiClass = match ($theme) {
                                        'blue' => 'text-blue-700',
                                        'emerald' => 'text-emerald-700',
                                        'amber' => 'text-amber-700',
                                        default => 'text-violet-700',
                                    };
                                @endphp

                                <div
                                    class="rounded-[1.5rem] border p-4 shadow-sm transition duration-300 hover:-translate-y-1 {{ $cardClass }}">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] {{ $labelClass }}">
                                                {{ $lm['label'] }}
                                            </p>
                                            <p class="mt-2 text-2xl font-bold {{ $nilaiClass }}">
                                                {{ $lm['nilai'] ?? '-' }}
                                            </p>
                                        </div>

                                        <div
                                            class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">
                                            Nilai LM
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-4 gap-2">
                                        <div class="rounded-xl bg-white px-3 py-3 text-center shadow-sm ring-1 ring-slate-100">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">TP1
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $lm['tp1'] ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-xl bg-white px-3 py-3 text-center shadow-sm ring-1 ring-slate-100">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">TP2
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $lm['tp2'] ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-xl bg-white px-3 py-3 text-center shadow-sm ring-1 ring-slate-100">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">TP3
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $lm['tp3'] ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-xl bg-white px-3 py-3 text-center shadow-sm ring-1 ring-slate-100">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">TP4
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $lm['tp4'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

            </div>
        </section>
    </div>
@endsection