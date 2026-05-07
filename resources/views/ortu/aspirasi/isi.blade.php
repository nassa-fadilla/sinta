@extends('ortu.layout')
@section('title', 'Isi Survei')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $mulaiAt = $survei->mulai_at_parsed ?? null;
        $akhirAt = $survei->akhir_at_parsed ?? null;

        $totalPertanyaan = $survei->pertanyaan->count();

        $periodeText = $mulaiAt && $akhirAt
            ? $mulaiAt->translatedFormat('d M Y H:i') . ' WIB - ' . $akhirAt->translatedFormat('d M Y H:i') . ' WIB'
            : ($mulaiAt
                ? 'Mulai ' . $mulaiAt->translatedFormat('d M Y H:i') . ' WIB'
                : ($akhirAt
                    ? 'Berakhir ' . $akhirAt->translatedFormat('d M Y H:i') . ' WIB'
                    : 'Periode tidak dibatasi'));
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5h9m-9 4h9m-9 4h5M7 5h.01M7 9h.01M7 13h.01M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Form Survei
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                {{ $survei->judul }}
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Isi survei berikut untuk menyampaikan penilaian dan masukan Anda kepada sekolah.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    {{ $totalPertanyaan }} pertanyaan
                                </span>

                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Status: Aktif
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('ortu.aspirasi.index') }}"
                        class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>

                {{-- MINI CARDS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">
                                    Total Pertanyaan
                                </div>
                                <div class="mt-3 text-2xl font-semibold leading-none text-slate-900">
                                    {{ $totalPertanyaan }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Pertanyaan yang perlu dijawab.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
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
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
                                    Status
                                </div>
                                <div class="mt-3 text-lg font-semibold leading-none text-slate-900">
                                    Belum Dikirim
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Jawaban tersimpan setelah dikirim.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-amber-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(245,158,11,0.08)] transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">
                                    Mulai
                                </div>
                                <div class="mt-3 text-sm font-semibold leading-6 text-slate-900">
                                    {{ $mulaiAt ? $mulaiAt->translatedFormat('d M Y H:i') . ' WIB' : 'Tanpa batas awal' }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Waktu mulai pengisian.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-600 shadow-sm transition duration-300 group-hover:scale-110">
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
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-700">
                                    Berakhir
                                </div>
                                <div class="mt-3 text-sm font-semibold leading-6 text-slate-900">
                                    {{ $akhirAt ? $akhirAt->translatedFormat('d M Y H:i') . ' WIB' : 'Tanpa batas akhir' }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Batas waktu pengisian.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('info'))
                <div
                    class="auto-dismiss-alert border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-800 opacity-100 shadow-sm transition-all duration-500 md:px-7">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                @if(!empty($survei->deskripsi))
                    <div
                        class="mb-5 rounded-[1.6rem] border border-slate-200 bg-slate-50/70 px-5 py-4 shadow-inner">
                        <div class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">
                            Deskripsi Survei
                        </div>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            {{ $survei->deskripsi }}
                        </p>
                    </div>
                @endif

                <div class="mb-5 rounded-[1.6rem] border border-blue-100 bg-blue-50/70 px-5 py-4 text-sm leading-7 text-blue-800">
                    Silakan isi jawaban sesuai kondisi dan pengalaman Anda. Jawaban yang dikirim akan menjadi bahan masukan
                    bagi sekolah dalam meningkatkan layanan informasi dan monitoring aktivitas siswa.
                </div>

                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Daftar Pertanyaan
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Lengkapi jawaban pada pertanyaan yang tersedia, kemudian tekan tombol kirim.
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-medium text-blue-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                        {{ $periodeText }}
                    </span>
                </div>

                <form method="POST" action="{{ route('ortu.aspirasi.kirim', $survei->id) }}" class="space-y-5">
                    @csrf

                    <div class="space-y-4">
                        @foreach ($survei->pertanyaan as $q)
                            <div
                                class="group relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white px-5 py-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_44px_rgba(59,130,246,0.12)] md:px-6">
                                <div class="absolute inset-x-0 top-0 h-1 bg-blue-500"></div>

                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-sm font-semibold text-blue-700 ring-1 ring-blue-100 transition duration-300 group-hover:bg-blue-500 group-hover:text-white">
                                        {{ $loop->iteration }}
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold leading-7 text-slate-800">
                                                {{ $q->pertanyaan }}
                                            </label>
                                            <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                Tipe: {{ ucfirst($q->tipe ?? 'text') }}
                                            </p>
                                        </div>

                                        @if ($q->tipe === 'text')
                                            <input type="text" name="jawaban[{{ $q->id }}]"
                                                value="{{ old('jawaban.' . $q->id) }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100">

                                        @elseif ($q->tipe === 'textarea')
                                            <textarea name="jawaban[{{ $q->id }}]" rows="4"
                                                class="w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100">{{ old('jawaban.' . $q->id) }}</textarea>

                                        @elseif ($q->tipe === 'radio')
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @foreach ($q->opsi as $opt)
                                                    <label
                                                        class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50/50 hover:shadow-sm">
                                                        <input type="radio" name="jawaban[{{ $q->id }}]"
                                                            value="{{ $opt->opsi }}"
                                                            {{ old('jawaban.' . $q->id) == $opt->opsi ? 'checked' : '' }}
                                                            class="text-sky-600 focus:ring-sky-500">
                                                        <span>{{ $opt->opsi }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @elseif ($q->tipe === 'checkbox')
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @foreach ($q->opsi as $opt)
                                                    <label
                                                        class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50/50 hover:shadow-sm">
                                                        <input type="checkbox" name="jawaban[{{ $q->id }}][]"
                                                            value="{{ $opt->opsi }}"
                                                            {{ in_array($opt->opsi, old('jawaban.' . $q->id, [])) ? 'checked' : '' }}
                                                            class="text-sky-600 focus:ring-sky-500">
                                                        <span>{{ $opt->opsi }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @elseif ($q->tipe === 'dropdown')
                                            <select name="jawaban[{{ $q->id }}]"
                                                class="w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100">
                                                <option value="">-- Pilih Jawaban --</option>
                                                @foreach ($q->opsi as $opt)
                                                    <option value="{{ $opt->opsi }}"
                                                        {{ old('jawaban.' . $q->id) == $opt->opsi ? 'selected' : '' }}>
                                                        {{ $opt->opsi }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        @elseif ($q->tipe === 'skala')
                                            <div
                                                class="space-y-4 rounded-[1.35rem] border border-slate-200 bg-slate-50/70 px-4 py-4">
                                                <div class="grid grid-cols-5 gap-2">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <label
                                                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white px-2 py-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50/50">
                                                            <input type="radio" name="jawaban[{{ $q->id }}]"
                                                                value="{{ $i }}"
                                                                {{ old('jawaban.' . $q->id) == (string) $i ? 'checked' : '' }}
                                                                class="scale-110 accent-sky-600 text-sky-600 focus:ring-sky-500">
                                                            <span class="mt-2 text-xs font-medium text-slate-700">
                                                                {{ $i }}
                                                            </span>
                                                        </label>
                                                    @endfor
                                                </div>

                                                <div class="flex justify-between gap-3 text-[11px] text-slate-500">
                                                    <span>Sangat Tidak Setuju</span>
                                                    <span>Sangat Setuju</span>
                                                </div>
                                            </div>
                                        @else
                                            <input type="text" name="jawaban[{{ $q->id }}]"
                                                value="{{ old('jawaban.' . $q->id) }}"
                                                class="w-full rounded-2xl border border-slate-300 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-sky-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-end">
                        <a href="{{ route('ortu.aspirasi.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                            Batal
                        </a>

                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-[0_14px_30px_rgba(59,130,246,0.25)] transition duration-300 hover:-translate-y-1 hover:bg-blue-600 hover:shadow-[0_20px_45px_rgba(59,130,246,0.35)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Kirim Jawaban
                        </button>
                    </div>
                </form>
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