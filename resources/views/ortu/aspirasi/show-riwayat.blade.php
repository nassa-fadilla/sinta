@extends('ortu.layout')
@section('title', 'Detail Jawaban Survei')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $tanggalIsi = $respon?->created_at
            ? \Carbon\Carbon::parse($respon->created_at)->timezone('Asia/Jakarta')
            : null;

        $totalPertanyaan = $survei->pertanyaan->count();

        $totalTerjawab = collect($survei->pertanyaan)->filter(function ($pertanyaan) use ($jawaban) {
            $nilai = $jawaban[$pertanyaan->id] ?? null;

            if (is_array($nilai)) {
                return count(array_filter($nilai, fn($v) => filled($v))) > 0;
            }

            return filled($nilai);
        })->count();

        $completionPercent = $totalPertanyaan > 0
            ? round(($totalTerjawab / $totalPertanyaan) * 100)
            : 0;
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_14px_30px_rgba(59,130,246,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Detail Jawaban
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Detail Jawaban Survei
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Lihat kembali jawaban survei yang telah Anda kirim sebagai dokumentasi aspirasi orang tua.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('ortu.aspirasi.riwayat') }}"
                        class="inline-flex w-fit items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>

                {{-- INFO CARDS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                                    Tanggal Isi
                                </div>
                                <div class="mt-3 text-sm font-bold leading-6 text-slate-900">
                                    {{ $tanggalIsi ? $tanggalIsi->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Waktu jawaban dikirim.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
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
                                    Total pertanyaan survei.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
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
                                    Status
                                </div>
                                <div
                                    class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Sudah dikirim
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Jawaban tersimpan.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- INFO SURVEI --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div
                    class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_16px_46px_rgba(15,23,42,0.06)] transition duration-300 hover:border-blue-200 hover:shadow-[0_20px_52px_rgba(59,130,246,0.10)] md:p-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                            Judul Survei
                        </p>

                        <h2 class="mt-2 text-xl font-semibold leading-snug text-slate-900 md:text-2xl">
                            {{ $survei->judul }}
                        </h2>

                        @if ($survei->deskripsi)
                            <p class="mt-3 max-w-4xl text-sm leading-7 text-slate-600">
                                {{ $survei->deskripsi }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-5 rounded-[1.4rem] border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">
                                    Kelengkapan Jawaban
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $totalTerjawab }} dari {{ $totalPertanyaan }} pertanyaan memiliki jawaban.
                                </p>
                            </div>

                            <p class="text-sm font-semibold text-blue-700">
                                {{ $completionPercent }}%
                            </p>
                        </div>

                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-500 transition-all duration-700"
                                style="width: {{ $completionPercent }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LIST JAWABAN --}}
            <div class="p-5 md:p-6 lg:p-7">
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Daftar Jawaban
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Jawaban ditampilkan sesuai urutan pertanyaan pada survei.
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-medium text-blue-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                        {{ $totalPertanyaan }} pertanyaan
                    </span>
                </div>

                <div class="space-y-4">
                    @forelse ($survei->pertanyaan as $index => $pertanyaan)
                        @php
                            $nilaiJawaban = $jawaban[$pertanyaan->id] ?? null;

                            $hasAnswer = is_array($nilaiJawaban)
                                ? count(array_filter($nilaiJawaban, fn($v) => filled($v))) > 0
                                : filled($nilaiJawaban);

                            $tipeLabel = match ($pertanyaan->tipe ?? 'text') {
                                'text' => 'Jawaban Singkat',
                                'textarea' => 'Paragraf',
                                'radio' => 'Pilihan Tunggal',
                                'checkbox' => 'Pilihan Ganda',
                                'dropdown' => 'Dropdown',
                                'skala' => 'Skala',
                                default => ucfirst($pertanyaan->tipe ?? 'Text'),
                            };
                        @endphp

                        <article
                            class="group relative overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_56px_rgba(59,130,246,0.12)] md:p-6">
                            <div class="absolute inset-x-0 top-0 h-1 bg-blue-500"></div>

                            <div class="flex flex-col gap-4 md:flex-row md:items-start">
                                <div
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-sm font-semibold text-blue-700 ring-1 ring-blue-100 transition duration-300 group-hover:bg-blue-500 group-hover:text-white">
                                    {{ $index + 1 }}
                                </div>

                                <div class="min-w-0 flex-1 space-y-4">
                                    <div>
                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-500">
                                                {{ $tipeLabel }}
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-medium {{ $hasAnswer ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $hasAnswer ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                                {{ $hasAnswer ? 'Terjawab' : 'Kosong' }}
                                            </span>
                                        </div>

                                        <h3 class="text-sm font-semibold leading-7 text-slate-900 md:text-base">
                                            {{ $pertanyaan->pertanyaan }}
                                        </h3>
                                    </div>

                                    <div class="rounded-[1.4rem] border border-slate-200 bg-slate-50/80 px-4 py-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                                            Jawaban Anda
                                        </p>

                                        @if (is_array($nilaiJawaban))
                                            @if (count($nilaiJawaban))
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @foreach ($nilaiJawaban as $item)
                                                        @if(filled($item))
                                                            <span
                                                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                                                {{ $item }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="mt-3 text-sm italic text-slate-400">
                                                    Tidak ada jawaban.
                                                </p>
                                            @endif
                                        @else
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                                                {{ filled($nilaiJawaban) ? $nilaiJawaban : '-' }}
                                            </p>
                                        @endif
                                    </div>

                                    @if (in_array($pertanyaan->tipe, ['radio', 'checkbox', 'dropdown']) && $pertanyaan->opsi->count())
                                        <div class="rounded-[1.4rem] border border-slate-200 bg-white px-4 py-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                                                Opsi Pertanyaan
                                            </p>

                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($pertanyaan->opsi as $opsi)
                                                    @php
                                                        $isSelected = is_array($nilaiJawaban)
                                                            ? in_array($opsi->opsi, $nilaiJawaban, true)
                                                            : (string) $nilaiJawaban === (string) $opsi->opsi;
                                                    @endphp

                                                    <span
                                                        class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium {{ $isSelected ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }}">
                                                        {{ $opsi->opsi }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div
                            class="rounded-[1.75rem] border border-dashed border-slate-200 bg-white px-5 py-14 text-center shadow-inner">
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-slate-100 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
                                </svg>
                            </div>

                            <h2 class="mt-4 text-base font-semibold text-slate-800">
                                Pertanyaan survei tidak ditemukan
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Tidak ada detail jawaban yang dapat ditampilkan.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection