@extends('ortu.layout')
@section('title', 'Riwayat Survei')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $riwayat = $riwayat ?? collect();
        $totalRiwayat = $riwayat->count();
        $totalPertanyaan = $riwayat->sum('pertanyaan_count');

        $terakhirDiisi = $riwayat
            ->map(fn($item) => optional($item->respon->first())->created_at)
            ->filter()
            ->sortDesc()
            ->first();

        $terakhirDiisiLabel = $terakhirDiisi
            ? \Carbon\Carbon::parse($terakhirDiisi)->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i')
            : '-';
    @endphp

    <div class="space-y-6">

        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_14px_30px_rgba(59,130,246,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l2 2m5-2a7 7 0 1 1-14 0 7 7 0 0 1 14 0z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Riwayat Pengisian
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Riwayat Survei
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Lihat daftar survei yang sudah pernah Anda isi beserta status dan detail jawaban yang
                                telah dikirim kepada pihak sekolah.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('ortu.aspirasi.index') }}"
                        class="inline-flex items-center justify-center gap-2 self-start rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-100 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5h9m-9 4h9m-9 4h5M7 5h.01M7 9h.01M7 13h.01M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" />
                        </svg>
                        Survei Aktif
                    </a>
                </div>

                {{-- MINI CARDS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                    Total Riwayat
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $totalRiwayat }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Survei yang sudah dikirim.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-violet-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(139,92,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_20px_48px_rgba(139,92,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-700">
                                    Pertanyaan
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $totalPertanyaan }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Total pertanyaan terjawab.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6h.01M8 10h8M8 14h5M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Terakhir Isi
                                </div>
                                <div class="mt-3 text-sm font-bold leading-6 text-slate-900">
                                    {{ $terakhirDiisiLabel }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Waktu pengisian terbaru.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FLASH MESSAGE --}}
            @if(session('ok'))
                <div
                    class="auto-dismiss-alert border-b border-emerald-100 bg-emerald-50 px-5 py-3 text-sm text-emerald-800 opacity-100 shadow-sm transition-all duration-500 md:px-7">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span>{{ session('ok') }}</span>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div
                    class="auto-dismiss-alert border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-800 opacity-100 shadow-sm transition-all duration-500 md:px-7">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
                            </svg>
                        </span>
                        <span>{{ session('info') }}</span>
                    </div>
                </div>
            @endif

            {{-- CONTENT --}}
            <div class="p-5 md:p-6 lg:p-7">
                @if ($riwayat->isEmpty())
                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-14 text-center shadow-inner">
                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Belum ada survei yang diisi.
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Survei yang sudah Anda isi akan tampil di halaman ini.
                                </p>
                            </div>

                            <a href="{{ route('ortu.aspirasi.index') }}"
                                class="mt-3 inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                Lihat Survei Aktif
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">
                                Daftar Riwayat Pengisian
                            </h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Total {{ $totalRiwayat }} survei sudah pernah Anda kirim.
                            </p>
                        </div>

                        <span
                            class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-medium text-emerald-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Semua jawaban tersimpan
                        </span>
                    </div>

                    {{-- MOBILE --}}
                    <div class="space-y-4 lg:hidden">
                        @foreach ($riwayat as $i => $s)
                            @php
                                $respon = $s->respon->first();
                                $tanggalIsi = optional($respon?->created_at)->translatedFormat('d M Y H:i') ?? '-';
                                $deskripsi = $s->deskripsi ?? null;
                            @endphp

                            <article
                                class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white px-4 py-4 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_44px_rgba(59,130,246,0.12)]">
                                <div class="absolute inset-x-0 top-0 h-1 bg-blue-500"></div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-sm font-semibold text-blue-700 ring-1 ring-blue-100 transition duration-300 group-hover:bg-blue-500 group-hover:text-white">
                                        {{ $i + 1 }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Terkirim
                                            </span>

                                            <span
                                                class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-[11px] font-medium text-violet-700">
                                                {{ $s->pertanyaan_count }} pertanyaan
                                            </span>
                                        </div>

                                        <h2 class="mt-3 text-sm font-semibold leading-6 text-slate-900">
                                            {{ $s->judul }}
                                        </h2>

                                        @if($deskripsi)
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                {{ \Illuminate\Support\Str::limit($deskripsi, 120) }}
                                            </p>
                                        @endif

                                        <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                                            <div class="flex items-start justify-between gap-3 text-xs">
                                                <span class="text-slate-500">Tanggal Isi</span>
                                                <span class="text-right font-medium text-slate-800">
                                                    {{ $tanggalIsi }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <a href="{{ route('ortu.aspirasi.showRiwayat', $s->id) }}"
                                                class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-xs font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                                Lihat Jawaban
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- DESKTOP --}}
                    <div
                        class="hidden overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_16px_46px_rgba(15,23,42,0.06)] lg:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-[920px] w-full border-collapse text-sm text-slate-800">
                                <thead>
                                    <tr
                                        class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">
                                        <th class="w-16 px-5 py-4 text-left">No</th>
                                        <th class="px-5 py-4 text-left">Judul Survei</th>
                                        <th class="px-5 py-4 text-left">Pertanyaan</th>
                                        <th class="px-5 py-4 text-left">Tanggal Isi</th>
                                        <th class="px-5 py-4 text-center">Status</th>
                                        <th class="px-5 py-4 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($riwayat as $i => $s)
                                        @php
                                            $respon = $s->respon->first();
                                            $tanggalIsi = optional($respon?->created_at)->translatedFormat('d M Y H:i') ?? '-';
                                            $deskripsi = $s->deskripsi ?? null;
                                        @endphp

                                        <tr class="transition duration-300 hover:bg-blue-50/40">
                                            <td class="px-5 py-5 align-top">
                                                <div
                                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-sm font-semibold text-blue-700 ring-1 ring-blue-100">
                                                    {{ $i + 1 }}
                                                </div>
                                            </td>

                                            <td class="px-5 py-5 align-top">
                                                <div class="max-w-[520px]">
                                                    <div class="font-semibold leading-6 text-slate-900">
                                                        {{ $s->judul }}
                                                    </div>

                                                    @if($deskripsi)
                                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                                            {{ \Illuminate\Support\Str::limit($deskripsi, 120) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-5 py-5 align-top">
                                                <span
                                                    class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700">
                                                    {{ $s->pertanyaan_count }} pertanyaan
                                                </span>
                                            </td>

                                            <td class="px-5 py-5 align-top text-slate-700">
                                                <div class="font-medium">
                                                    {{ $tanggalIsi }}
                                                </div>
                                            </td>

                                            <td class="px-5 py-5 align-top text-center">
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    Terkirim
                                                </span>
                                            </td>

                                            <td class="px-5 py-5 align-top text-center">
                                                <a href="{{ route('ortu.aspirasi.showRiwayat', $s->id) }}"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-xs font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                                    Jawaban
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.auto-dismiss-alert').forEach((alert) => {
                setTimeout(() => {
                    alert.classList.add('opacity-0', '-translate-y-1');
                    alert.classList.remove('opacity-100');

                    setTimeout(() => {
                        alert.remove();
                    }, 550);
                }, 5000);
            });
        });
    </script>
@endsection