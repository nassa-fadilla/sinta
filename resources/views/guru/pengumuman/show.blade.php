@extends('guru.layout')
@section('title', 'Detail Pengumuman')

@section('content')
    @php
        $jenis = strtolower($item->jenis ?? 'lainnya');

        $badgeClass = match ($jenis) {
            'akademik' => 'bg-blue-50 text-blue-700 border-blue-200',
            'kegiatan' => 'bg-sky-50 text-sky-700 border-sky-200',
            'prestasi' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'umum' => 'bg-blue-50 text-blue-700 border-blue-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };

        $iconWrapClass = match ($jenis) {
            'akademik' => 'bg-blue-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]',
            'kegiatan' => 'bg-sky-500 text-white shadow-[0_10px_24px_rgba(14,165,233,0.25)]',
            'prestasi' => 'bg-cyan-500 text-white shadow-[0_10px_24px_rgba(6,182,212,0.25)]',
            'umum' => 'bg-blue-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]',
            default => 'bg-slate-500 text-white shadow-[0_10px_24px_rgba(100,116,139,0.20)]',
        };

        $panelSoftClass = match ($jenis) {
            'akademik' => 'bg-blue-50/60 border-blue-100',
            'kegiatan' => 'bg-sky-50/60 border-sky-100',
            'prestasi' => 'bg-cyan-50/60 border-cyan-100',
            'umum' => 'bg-blue-50/60 border-blue-100',
            default => 'bg-slate-50/70 border-slate-100',
        };

        $buttonClass = match ($jenis) {
            'akademik' => 'bg-blue-600 hover:bg-blue-700',
            'kegiatan' => 'bg-sky-600 hover:bg-sky-700',
            'prestasi' => 'bg-cyan-600 hover:bg-cyan-700',
            'umum' => 'bg-blue-600 hover:bg-blue-700',
            default => 'bg-slate-600 hover:bg-slate-700',
        };
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.6rem] border border-slate-200/80 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $iconWrapClass }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h10.5A2.25 2.25 0 0019.5 18V14.25z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 9h7.5M8.25 12.75h7.5M8.25 16.5h4.5" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Detail Pengumuman
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $item->judul }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap pengumuman sekolah untuk guru.
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                                    {{ ucfirst($jenis) }}
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                    Dipublikasikan:
                                    <span class="ml-1 font-semibold text-slate-700">
                                        {{ $item->publish_at?->format('d M Y H:i') ?: '-' }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('guru.pengumuman.index') }}"
                        class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            {{-- BODY --}}
            <div class="p-5 md:p-6 space-y-6">

                {{-- INFO RINGKAS --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        class="rounded-[1.25rem] border px-4 py-4 transition duration-300 hover:-translate-y-[1px] hover:shadow-sm {{ $panelSoftClass }}">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            Jenis Pengumuman
                        </div>
                        <div class="mt-2 text-sm font-semibold capitalize text-slate-800">
                            {{ $item->jenis ?? '-' }}
                        </div>
                    </div>

                    <div
                        class="rounded-[1.25rem] border px-4 py-4 transition duration-300 hover:-translate-y-[1px] hover:shadow-sm {{ $panelSoftClass }}">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            Waktu Publikasi
                        </div>
                        <div class="mt-2 text-sm font-semibold text-slate-800">
                            {{ $item->publish_at?->format('d M Y H:i') ?: '-' }}
                        </div>
                    </div>
                </div>

                {{-- ISI PENGUMUMAN --}}
                <div>
                    <div class="mb-3">
                        <h2 class="text-sm font-semibold text-slate-800">
                            Isi Pengumuman
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Konten pengumuman yang telah dipublikasikan.
                        </p>
                    </div>

                    <div
                        class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] transition duration-300 hover:shadow-[0_14px_34px_rgba(15,23,42,0.07)]">
                        <div
                            class="prose prose-sm max-w-none text-slate-700 prose-p:leading-7 prose-headings:text-slate-800">
                            {!! nl2br(e($item->isi)) !!}
                        </div>
                    </div>
                </div>

                {{-- FILE PDF --}}
                @if($item->pdf_path)
                    <div
                        class="rounded-[1.35rem] border border-blue-100 bg-blue-50/60 px-4 py-4 shadow-sm transition duration-300 hover:shadow-md">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-800">
                                    Dokumen Resmi
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    File PDF resmi pengumuman tersedia untuk dilihat atau diunduh.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('guru.pengumuman.pdf.view', $item) }}" target="_blank"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-[1px] hover:bg-blue-50 hover:shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12H9m12 0A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span>Lihat PDF</span>
                                </a>

                                <a href="{{ route('guru.pengumuman.pdf.download', $item) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl {{ $buttonClass }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-[1px] hover:shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16" />
                                    </svg>
                                    <span>Unduh PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </section>
    </div>
@endsection