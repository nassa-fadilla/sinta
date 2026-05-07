@extends('ortu.layout')
@section('title', 'Survei Orang Tua')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $survei = $survei ?? collect();
        $today = $today ?? now('Asia/Jakarta');
        $todayLabel = ucfirst($today->translatedFormat('l, d F Y'));

        $totalSurvei = $survei->count();
        $sudahDiisi = $survei->filter(fn($item) => (bool) ($item->sudah_isi ?? false))->count();
        $belumDiisi = max($totalSurvei - $sudahDiisi, 0);
        $totalPertanyaan = $survei->sum('pertanyaan_count');

        $completionPercent = $totalSurvei > 0
            ? round(($sudahDiisi / $totalSurvei) * 100)
            : 0;
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
                                    d="M9 5h9m-9 4h9m-9 4h5M7 5h.01M7 9h.01M7 13h.01M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Aspirasi Orang Tua
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Survei Orang Tua
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Sampaikan penilaian, masukan, dan aspirasi Anda melalui survei yang tersedia sebagai
                                bahan evaluasi sekolah.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    {{ $todayLabel }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                    {{ $totalSurvei }} survei tersedia
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <div
                            class="min-w-[250px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_50px_rgba(59,130,246,0.13)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.28)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                        Ringkasan Survei
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                        {{ $sudahDiisi }} dari {{ $totalSurvei }} survei
                                    </div>
                                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                        Progres:
                                        <span class="font-medium text-slate-700">{{ $completionPercent }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY CARDS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                                    Total Survei
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $totalSurvei }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Survei aktif yang dapat diakses.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 5h9m-9 4h9m-9 4h5M7 5h.01M7 9h.01M7 13h.01M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Sudah Diisi
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $sudahDiisi }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Survei yang telah dikirim.
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

                    <div
                        class="group h-full rounded-[1.6rem] border border-amber-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(245,158,11,0.08)] transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">
                                    Belum Diisi
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $belumDiisi }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Masih menunggu respon.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M12 5a7 7 0 1 0 0 14 7 7 0 0 0 0-14z" />
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
                                    Total pertanyaan tersedia.
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
                </div>

                {{-- PROGRESS --}}
                <div
                    class="mt-5 rounded-[1.5rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_14px_34px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Progres Pengisian Survei
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $sudahDiisi }} dari {{ $totalSurvei }} survei telah diisi.
                            </p>
                        </div>

                        <div class="text-sm font-semibold text-blue-700">
                            {{ $completionPercent }}%
                        </div>
                    </div>

                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-blue-500 transition-all duration-700"
                            style="width: {{ $completionPercent }}%">
                        </div>
                    </div>
                </div>
            </div>

            {{-- FLASH --}}
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
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Daftar Survei
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Pilih survei yang tersedia untuk memberikan penilaian dan masukan kepada sekolah.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 font-medium text-blue-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            Aktif
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-medium text-emerald-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Sudah diisi
                        </span>
                    </div>
                </div>

                @if($survei->isEmpty())
                    <div
                        class="rounded-[1.75rem] border border-dashed border-slate-200 bg-white px-5 py-16 text-center shadow-inner">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-slate-100 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                            </svg>
                        </div>

                        <h2 class="mt-4 text-base font-semibold text-slate-800">
                            Belum ada survei aktif
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Survei yang tersedia akan muncul di halaman ini.
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5">
                        @foreach($survei as $item)
                            @php
                                $sudahIsi = (bool) ($item->sudah_isi ?? false);

                                $mulai = $item->mulai_at
                                    ? \Carbon\Carbon::parse($item->mulai_at)->timezone('Asia/Jakarta')
                                    : null;

                                $akhir = $item->akhir_at
                                    ? \Carbon\Carbon::parse($item->akhir_at)->timezone('Asia/Jakarta')
                                    : null;

                                $periodeText = $mulai && $akhir
                                    ? $mulai->translatedFormat('d M Y') . ' - ' . $akhir->translatedFormat('d M Y')
                                    : ($mulai
                                        ? 'Mulai ' . $mulai->translatedFormat('d M Y')
                                        : ($akhir
                                            ? 'Berakhir ' . $akhir->translatedFormat('d M Y')
                                            : 'Periode tidak dibatasi'));

                                $deadlineText = $akhir
                                    ? $akhir->translatedFormat('d M Y')
                                    : '-';

                                $isExpiredSoon = $akhir
                                    ? now('Asia/Jakarta')->diffInDays($akhir, false) <= 3 && now('Asia/Jakarta')->lte($akhir)
                                    : false;

                                $lineColor = $sudahIsi ? 'bg-emerald-500' : 'bg-blue-500';
                                $outerClass = $sudahIsi
                                    ? 'border-emerald-100 bg-white hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]'
                                    : 'border-blue-100 bg-white hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]';
                            @endphp

                            <article
                                class="group relative overflow-hidden rounded-[1.8rem] border p-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 md:p-6 {{ $outerClass }}">

                                <div class="absolute inset-x-0 top-0 h-1 {{ $lineColor }}"></div>

                                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="mb-3 flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-medium {{ $sudahIsi ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-700' }}">
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full {{ $sudahIsi ? 'bg-emerald-500' : 'bg-blue-500' }}">
                                                </span>
                                                {{ $sudahIsi ? 'Sudah Diisi' : 'Aktif' }}
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-medium text-slate-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6h.01M8 10h8M8 14h5M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                                </svg>
                                                {{ $item->pertanyaan_count }} pertanyaan
                                            </span>

                                            @if($isExpiredSoon && !$sudahIsi)
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-medium text-amber-700">
                                                    Segera berakhir
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-lg font-semibold leading-snug text-slate-900 md:text-xl">
                                            {{ $item->judul }}
                                        </h3>

                                        @if(!empty($item->deskripsi))
                                            <p class="mt-2 max-w-4xl text-sm leading-7 text-slate-600">
                                                {{ \Illuminate\Support\Str::limit($item->deskripsi, 210) }}
                                            </p>
                                        @endif

                                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                    Periode
                                                </p>
                                                <p class="mt-1 text-sm font-medium text-slate-700">
                                                    {{ $periodeText }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                    Berakhir
                                                </p>
                                                <p class="mt-1 text-sm font-medium text-slate-700">
                                                    {{ $deadlineText }}
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                    Jumlah Pertanyaan
                                                </p>
                                                <p class="mt-1 text-sm font-medium text-slate-700">
                                                    {{ $item->pertanyaan_count }} pertanyaan
                                                </p>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                                                    Status Pengisian
                                                </p>
                                                <p
                                                    class="mt-1 text-sm font-medium {{ $sudahIsi ? 'text-emerald-700' : 'text-blue-700' }}">
                                                    {{ $sudahIsi ? 'Terkirim' : 'Belum diisi' }}
                                                </p>
                                            </div>
                                        </div>

                                        @if($sudahIsi)
                                            <div
                                                class="mt-4 inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-medium text-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Diisi pada
                                                <span class="font-medium">
                                                    {{ optional($item->tanggal_isi)->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') ?? '-' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 items-center lg:pt-1">
                                        @if($sudahIsi)
                                            <a href="{{ route('ortu.aspirasi.showRiwayat', $item->id) }}"
                                                class="group/btn inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-500 px-5 py-3 text-sm font-medium text-white shadow-[0_14px_30px_rgba(59,130,246,0.25)] transition duration-300 hover:-translate-y-1 hover:bg-blue-600 hover:shadow-[0_20px_45px_rgba(59,130,246,0.35)] lg:w-auto">
                                                <span>Lihat Jawaban</span>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 transition duration-300 group-hover/btn:translate-x-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        @else
                                            <a href="{{ route('ortu.aspirasi.isi', $item->id) }}"
                                                class="group/btn inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-sky-500 px-5 py-3 text-sm font-medium text-white shadow-[0_14px_30px_rgba(14,165,233,0.25)] transition duration-300 hover:-translate-y-1 hover:bg-sky-600 hover:shadow-[0_20px_45px_rgba(14,165,233,0.35)] lg:w-auto">
                                                <span>Isi Survei</span>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 transition duration-300 group-hover/btn:translate-x-1" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
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