@extends('kepsek.layout')
@section('title', 'Detail Pengumuman')

@section('content')
    <div x-data="{ openApprove: false, openReject: false }" class="space-y-6">

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @php
            $status = $item->status;

            $badgeClass =
                $status === 'approved'
                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                : ($status === 'rejected'
                    ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                    : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200');

            $statusText =
                $status === 'approved'
                ? 'Disetujui'
                : ($status === 'rejected'
                    ? 'Ditolak'
                    : 'Menunggu');

            $targetLabel = match ($item->target_scope) {
                'all' => 'Semua Orang Tua',
                'tingkat' => 'Tingkat ' . ($item->target_tingkat ?: '-'),
                'kelas' => 'Per Kelas',
                default => 'Tidak ditentukan',
            };
        @endphp

        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 3.75L4.5 7.5v9l6.75 3.75 6.75-3.75v-9L11.25 3.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 12v8.25M18 7.5l-6.75 4.5L4.5 7.5" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Detail Pengumuman
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Tinjau isi, target, dan periode tayang pengumuman sebelum memberikan persetujuan.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('kepsek.pengumuman.index') }}"
                            class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">

                {{-- HERO --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Informasi Pengumuman
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                                    {{ $item->judul }}
                                </h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    {{ ucfirst($item->jenis) }}
                                    @if($tahunAjaran)
                                        • {{ $tahunAjaran->nama_tahun ?? '—' }}
                                        @if($tahunAjaran->semester)
                                            (Semester {{ $tahunAjaran->semester }})
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div
                                class="rounded-[1.5rem] border border-blue-200 bg-blue-50 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-600">
                                    Jenis
                                </p>
                                <p class="mt-2 text-sm font-semibold capitalize text-slate-800">
                                    {{ $item->jenis }}
                                </p>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-600">
                                    Target
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $targetLabel }}
                                </p>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-600">
                                    Mulai Tayang
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $item->publish_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </div>

                            <div
                                class="rounded-[1.5rem] border border-violet-200 bg-violet-50 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-600">
                                    Akhir Tayang
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ $item->expire_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DETAIL INFO --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-800">Informasi Utama</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Ringkasan data utama pengumuman.
                            </p>
                        </div>

                        <div class="space-y-4 px-5 py-5 text-sm md:px-6 md:py-6">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Dibuat Oleh
                                </div>
                                <div class="mt-1 font-medium text-slate-800">
                                    {{ $item->author?->name ?? '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Tahun Ajaran
                                </div>
                                <div class="mt-1 font-medium text-slate-800">
                                    {{ $tahunAjaran?->nama_tahun ?? '—' }}
                                    @if($tahunAjaran?->semester)
                                        <span class="text-[11px] text-slate-500">
                                            (Semester {{ $tahunAjaran->semester }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Dibuat
                                </div>
                                <div class="mt-1 font-medium text-slate-800">
                                    {{ $item->created_at?->format('d M Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-800">Verifikasi</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Informasi proses persetujuan pengumuman.
                            </p>
                        </div>

                        <div class="space-y-4 px-5 py-5 text-sm md:px-6 md:py-6">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </div>
                                <div class="mt-1">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $statusText }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Diproses
                                </div>
                                <div class="mt-1 font-medium text-slate-800">
                                    {{ $item->approved_at?->format('d M Y H:i') ?? '—' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Catatan Penolakan
                                </div>
                                <div class="mt-1 font-medium text-slate-800">
                                    {{ $item->reject_note ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ISI PENGUMUMAN --}}
                <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Isi Pengumuman</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Konten pengumuman yang akan ditampilkan kepada pengguna.
                        </p>
                    </div>

                    <div class="px-5 py-5 md:px-6 md:py-6">
                        <div class="text-[15px] leading-[2] text-slate-800">
                            {!! nl2br(e($item->isi)) !!}
                        </div>
                    </div>
                </section>

                {{-- ACTIONS --}}
                <div class="flex flex-wrap items-center justify-end gap-3">
                    @if($item->status === 'draft')
                        <button type="button" @click="openReject = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-rose-700 hover:shadow-md">
                            Tolak
                        </button>

                        <button type="button" @click="openApprove = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md">
                            Setujui
                        </button>
                    @elseif($item->status === 'approved' && $item->pdf_path)
                        <a href="{{ route('kepsek.pengumuman.pdf.view', $item) }}" target="_blank"
                            class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-medium text-sky-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-sky-50 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12H9m12 0A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                            </svg>
                            Lihat PDF
                        </a>

                        <a href="{{ route('kepsek.pengumuman.pdf.download', $item) }}"
                            class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-sky-700 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M6 20h12" />
                            </svg>
                            Unduh PDF
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- MODAL APPROVE --}}
        <div x-show="openApprove" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @keydown.escape.window="openApprove = false" @click.self="openApprove = false">

            <div x-show="openApprove" x-transition
                class="w-full max-w-md overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Setujui Pengumuman?</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pengumuman akan disetujui dengan periode tayang sesuai data yang telah diisi admin.
                    </p>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4">
                    <button type="button" @click="openApprove = false"
                        class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                        Batal
                    </button>

                    <form method="POST" action="{{ route('kepsek.pengumuman.approve', $item) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Ya, Setujui
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL REJECT --}}
        <div x-show="openReject" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @keydown.escape.window="openReject = false" @click.self="openReject = false">

            <div x-show="openReject" x-transition
                class="w-full max-w-md overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Tolak Pengumuman?</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Berikan alasan penolakan agar admin dapat memperbaiki pengumuman dengan jelas.
                    </p>
                </div>

                <form method="POST" action="{{ route('kepsek.pengumuman.reject', $item) }}">
                    @csrf
                    @method('PUT')

                    <div class="px-6 py-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Alasan Penolakan
                        </label>
                        <textarea name="reject_note" rows="4" required
                            class="w-full rounded-xl border border-slate-300 p-3 text-sm text-slate-700 placeholder-slate-400 transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                            placeholder="Tuliskan alasan penolakan agar admin dapat memperbaiki pengumuman."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="openReject = false"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-700">
                            Ya, Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection