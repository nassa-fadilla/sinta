@extends('ortu.layout')
@section('title', $pengumuman->judul)

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $badgeMap = [
            'akademik' => [
                'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',
                'icon' => 'bg-sky-50 text-sky-600 border-sky-200',
                'accent' => 'from-sky-500 to-blue-500',
            ],
            'kegiatan' => [
                'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'icon' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                'accent' => 'from-emerald-500 to-teal-500',
            ],
            'prestasi' => [
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'icon' => 'bg-amber-50 text-amber-600 border-amber-200',
                'accent' => 'from-amber-500 to-orange-500',
            ],
            'lainnya' => [
                'badge' => 'bg-slate-50 text-slate-700 ring-slate-200',
                'icon' => 'bg-slate-50 text-slate-600 border-slate-200',
                'accent' => 'from-slate-500 to-slate-600',
            ],
            'umum' => [
                'badge' => 'bg-slate-50 text-slate-700 ring-slate-200',
                'icon' => 'bg-slate-50 text-slate-600 border-slate-200',
                'accent' => 'from-slate-500 to-slate-600',
            ],
        ];

        $jenis = strtolower($pengumuman->jenis ?? 'umum');
        $config = $badgeMap[$jenis] ?? $badgeMap['umum'];
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_22px_60px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5 10v4a2 2 0 0 0 2 2h2l4 3V7l-4 3H7a2 2 0 0 0-2 2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 {{ $config['badge'] }}">
                                    {{ ucfirst($pengumuman->jenis ?? 'Umum') }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    Dipublikasikan {{ $pengumuman->publish_at?->translatedFormat('d F Y') ?? '-' }}
                                </span>
                            </div>

                            <h1 class="mt-3 text-2xl font-semibold tracking-tight leading-snug text-slate-800 md:text-3xl">
                                {{ $pengumuman->judul }}
                            </h1>

                            <p class="mt-2 text-sm text-slate-500">
                                Informasi resmi sekolah yang dapat dibaca langsung oleh orang tua siswa.
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <a href="{{ route('ortu.pengumuman.index') }}"
                            class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali</span>
                        </a>

                        @if (!empty($pengumuman->pdf_path))
                            <a href="{{ route('ortu.pengumuman.pdf.download', $pengumuman) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-[1px] hover:bg-blue-600 hover:shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16" />
                                </svg>
                                <span>Unduh PDF</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="p-5 md:p-6">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr,0.9fr]">
                    {{-- KONTEN UTAMA --}}
                    <div class="space-y-5">
                        <div
                            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <div class="h-1 bg-gradient-to-r {{ $config['accent'] }}"></div>

                            <div class="p-5">
                                <div class="mb-4 flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl border {{ $config['icon'] }} shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5 10v4a2 2 0 0 0 2 2h2l4 3V7l-4 3H7a2 2 0 0 0-2 2z" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h2 class="text-base font-semibold text-slate-800">Isi Pengumuman</h2>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Detail informasi pengumuman resmi sekolah.
                                        </p>
                                    </div>
                                </div>

                                <div class="prose prose-slate max-w-none prose-p:leading-7 prose-p:text-slate-700">
                                    @foreach (preg_split('/\r\n|\r|\n/', trim((string) $pengumuman->isi)) as $paragraph)
                                        @if (trim($paragraph) !== '')
                                            <p>{{ $paragraph }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PDF PREVIEW --}}
                @if (!empty($pengumuman->pdf_path))
                    <div class="mt-6 space-y-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Dokumen Resmi</h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                Dokumen pengumuman resmi sekolah dapat dibaca langsung pada panel berikut.
                            </p>
                        </div>

                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50 shadow-sm">
                            <iframe src="{{ route('ortu.pengumuman.pdf.view', $pengumuman) }}"
                                class="h-[72vh] w-full border-none"></iframe>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection