@extends('ortu.layout')
@section('title', 'Pengumuman Sekolah')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $pengumuman = $pengumuman ?? collect();

        $badgeMap = [
            'akademik' => [
                'badge' => 'bg-sky-50/90 text-sky-700 border-sky-200',
                'dot' => 'bg-sky-500',
                'icon_bg' => 'bg-sky-50 text-sky-600 border-sky-200',
                'line' => 'bg-sky-500',
                'outer' => 'border-sky-100 bg-white hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]',
            ],
            'kegiatan' => [
                'badge' => 'bg-emerald-50/90 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
                'icon_bg' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                'line' => 'bg-emerald-500',
                'outer' => 'border-emerald-100 bg-white hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]',
            ],
            'prestasi' => [
                'badge' => 'bg-amber-50/90 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
                'icon_bg' => 'bg-amber-50 text-amber-600 border-amber-200',
                'line' => 'bg-amber-500',
                'outer' => 'border-amber-100 bg-white hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]',
            ],
            'lainnya' => [
                'badge' => 'bg-slate-50/90 text-slate-700 border-slate-200',
                'dot' => 'bg-slate-400',
                'icon_bg' => 'bg-slate-50 text-slate-600 border-slate-200',
                'line' => 'bg-slate-400',
                'outer' => 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.09)]',
            ],
            'umum' => [
                'badge' => 'bg-slate-50/90 text-slate-700 border-slate-200',
                'dot' => 'bg-slate-400',
                'icon_bg' => 'bg-slate-50 text-slate-600 border-slate-200',
                'line' => 'bg-slate-400',
                'outer' => 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.09)]',
            ],
        ];

        $jumlahPengumuman = $pengumuman->count();
        $terbaru = $pengumuman->sortByDesc('publish_at')->first();

        $jumlahAkademik = $pengumuman->filter(fn($item) => strtolower((string) ($item->jenis ?? '')) === 'akademik')->count();
        $jumlahKegiatan = $pengumuman->filter(fn($item) => strtolower((string) ($item->jenis ?? '')) === 'kegiatan')->count();
        $jumlahPrestasi = $pengumuman->filter(fn($item) => strtolower((string) ($item->jenis ?? '')) === 'prestasi')->count();

        $tanggalTerbaru = ($terbaru && $terbaru->publish_at)
            ? $terbaru->publish_at->translatedFormat('d F Y')
            : 'Belum ada pengumuman';
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 10v4m-2 0h6l4 3V7l-4 3H6a2 2 0 0 0 0 4z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Informasi Sekolah
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                Pengumuman Sekolah
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Informasi terbaru terkait kegiatan, akademik, prestasi, dan pemberitahuan penting lainnya.
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <div
                            class="min-w-[250px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_50px_rgba(59,130,246,0.13)]">
                            <div class="flex items-center gap-3">
                                <div
                                    class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.28)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                                        Ringkasan
                                    </div>
                                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                                        {{ $jumlahPengumuman }} pengumuman aktif
                                    </div>
                                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                        Terbaru:
                                        <span class="font-medium text-slate-700">{{ $tanggalTerbaru }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- QUICK CARDS --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {{-- Total --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                                    Total
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jumlahPengumuman }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Pengumuman aktif
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 10v4m-2 0h6l4 3V7l-4 3H6a2 2 0 0 0 0 4z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Akademik --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                    Akademik
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jumlahAkademik }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Informasi akademik
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6l4 2M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Kegiatan --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Kegiatan
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jumlahKegiatan }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Aktivitas sekolah
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Prestasi --}}
                    <div
                        class="group h-full rounded-[1.6rem] border border-amber-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(245,158,11,0.08)] transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700">
                                    Prestasi
                                </div>
                                <div class="mt-3 text-2xl font-bold leading-none text-slate-900">
                                    {{ $jumlahPrestasi }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Kabar prestasi
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 21h8m-4-4v4m-5-16h10l-1 5a4 4 0 0 1-4 3H11a4 4 0 0 1-4-3L6 5z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="p-5 md:p-6 lg:p-7">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Daftar Pengumuman
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Baca informasi terbaru yang diterbitkan oleh pihak sekolah.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 font-medium text-blue-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            Aktif
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-500">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                            Terpublikasi
                        </span>
                    </div>
                </div>

                @if ($pengumuman->isEmpty())
                    <div
                        class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-4 py-14 text-center shadow-inner">
                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 17h5l-1.405-4.216A2 2 0 0 0 16.683 11H15m0 6H6a2 2 0 0 1-1.789-1.106L3 13m12 4V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v7m0 0H3" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    Belum ada pengumuman yang aktif.
                                </p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Jika sekolah menerbitkan informasi baru, pengumuman akan muncul di halaman ini.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        @foreach ($pengumuman as $p)
                            @php
                                $jenis = strtolower($p->jenis ?? 'umum');
                                $config = $badgeMap[$jenis] ?? $badgeMap['umum'];
                            @endphp

                            <article
                                class="group relative min-h-[210px] overflow-hidden rounded-[1.7rem] border px-5 py-5 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 {{ $config['outer'] }}">

                                <div class="absolute inset-x-0 top-0 h-1 {{ $config['line'] }}"></div>

                                <div class="flex h-full items-start gap-3">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-hover:scale-110 {{ $config['icon_bg'] }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5 10v4a2 2 0 0 0 2 2h2l4 3V7l-4 3H7a2 2 0 0 0-2 2z" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="mb-3 flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-medium {{ $config['badge'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $config['dot'] }}"></span>
                                                {{ ucfirst($p->jenis ?? 'Umum') }}
                                            </span>

                                            <span
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-500">
                                                {{ $p->publish_at?->translatedFormat('d M Y') ?? '-' }}
                                            </span>
                                        </div>

                                        <h2
                                            class="line-clamp-2 text-[15px] font-semibold leading-snug text-slate-900 transition duration-300 group-hover:text-blue-700 md:text-base">
                                            {{ $p->judul }}
                                        </h2>

                                        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">
                                            {{ \Illuminate\Support\Str::limit(strip_tags((string) ($p->isi ?? '')), 170) }}
                                        </p>

                                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <a href="{{ route('ortu.pengumuman.show', $p) }}"
                                                class="inline-flex w-fit items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                                <span>Baca Detail</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>

                                            @if (!empty($p->pdf_path))
                                                <a href="{{ route('ortu.pengumuman.pdf.view', $p) }}" target="_blank"
                                                    class="inline-flex w-fit items-center gap-1.5 rounded-2xl border border-violet-200 bg-violet-50 px-3.5 py-2 text-xs font-medium text-violet-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-violet-100 hover:shadow-md">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 10v6m0 0l-3-3m3 3l3-3M5 19h14M7 4h10a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                                    </svg>
                                                    <span>Unduh PDF</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection