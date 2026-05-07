@extends('admin.layout')
@section('title', 'Detail Pengumuman')

@section('content')
  @php
    $statusMeta = match ($item->status) {
      'draft' => ['Menunggu Persetujuan', 'bg-amber-50 text-amber-800 ring-1 ring-amber-200', 'bg-amber-500'],
      'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200', 'bg-emerald-500'],
      'rejected' => ['Ditolak', 'bg-rose-50 text-rose-800 ring-1 ring-rose-200', 'bg-rose-500'],
      default => ['-', 'bg-slate-50 text-slate-700 ring-1 ring-slate-200', 'bg-slate-400'],
    };

    $targetLabel = match ($item->target_scope) {
      'all' => 'Semua',
      'tingkat' => 'Tingkat ' . ($item->target_tingkat ?: '-'),
      default => '-',
    };
  @endphp

  <div class="space-y-6">
    <section
      class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

      {{-- HEADER --}}
      <div class="border-b border-slate-200 px-5 py-5 md:px-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 8.25L19.5 4.5m-3.75 3.75L19.5 12m-3.75-3.75H13.5L9 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5A2.25 2.25 0 0 0 6.75 19.5H9l4.5-3.75h2.25a2.25 2.25 0 0 0 2.25-2.25v-3A2.25 2.25 0 0 0 15.75 8.25z" />
              </svg>
            </div>

            <div>
              <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                Detail Pengumuman
              </h1>
              <p class="mt-1 text-sm text-slate-500">
                Ringkasan informasi, target, jadwal tayang, dan status persetujuan pengumuman.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.pengumuman.index') }}"
              class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
              </svg>
              <span>Kembali</span>
            </a>

            @if($item->status === 'approved' && $item->pdf_path)
              <a href="{{ route('admin.pengumuman.pdf.view', $item) }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                </svg>
                Lihat PDF
              </a>

              <a href="{{ route('admin.pengumuman.pdf.download', $item) }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 16v1.25A2.75 2.75 0 0 0 6.75 20h10.5A2.75 2.75 0 0 0 20 17.25V16M8.75 11.25 12 14.5m0 0 3.25-3.25M12 14.5v-9" />
                </svg>
                Unduh PDF
              </a>
            @endif

            @if(auth()->user()->role === 'admin' && in_array($item->status, ['draft', 'rejected']))
              <a href="{{ route('admin.pengumuman.edit', $item) }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.232 5.232l3.536 3.536M9 13l6.732-6.732a2 2 0 112.828 2.828L11.828 15.828H9v-2.828z" />
                </svg>
                Ubah
              </a>
            @endif
          </div>
        </div>
      </div>

      <div class="p-5 md:p-6 space-y-6">

        @if(session('ok'))
          <div
            class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            {{ session('ok') }}
          </div>
        @endif

        @if(session('no'))
          <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
            {{ session('no') }}
          </div>
        @endif

        {{-- HERO --}}
        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6 overflow-hidden">
          <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                  Informasi Pengumuman
                </p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-800 md:text-3xl">
                  {{ $item->judul }}
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                  {{ ucfirst($item->jenis) }}
                  @if($tahunAjaran)
                    • {{ $tahunAjaran->nama_tahun }} (Semester {{ $tahunAjaran->semester }})
                  @endif
                </p>
              </div>

              <span
                class="inline-flex items-center gap-2 self-start rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $statusMeta[1] }}">
                <span class="h-2 w-2 rounded-full {{ $statusMeta[2] }}"></span>
                {{ $statusMeta[0] }}
              </span>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
              {{-- VERIFIKASI --}}
              <div
                class="flex h-full min-w-0 flex-col justify-between rounded-[1.25rem] border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(59,130,246,0.10)]">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-500">
                    Diverifikasi
                  </p>
                  <p class="mt-2 text-[15px] font-semibold leading-6 text-slate-800">
                    {{ $item->approved_at?->format('d M Y H:i') ?: '-' }}
                  </p>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">
                  {{ $item->approved_at ? 'Oleh ' . ($item->approver?->name ?? '—') : 'Belum diverifikasi' }}
                </p>
              </div>

              {{-- TARGET --}}
              <div
                class="flex h-full min-w-0 flex-col justify-between rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(16,185,129,0.10)]">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-500">
                    Target
                  </p>
                  <p class="mt-2 text-[15px] font-semibold leading-6 text-slate-800">
                    {{ $targetLabel }}
                  </p>
                </div>
                <p class="mt-2 text-xs leading-5 text-emerald-700/80">
                  Sasaran penerima pengumuman
                </p>
              </div>

              {{-- MULAI TAYANG --}}
              <div
                class="flex h-full min-w-0 flex-col justify-between rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(245,158,11,0.10)]">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-amber-500">
                    Mulai Tayang
                  </p>
                  <p class="mt-2 text-[15px] font-semibold leading-6 text-slate-800">
                    {{ $item->publish_at?->format('d M Y H:i') ?: '—' }}
                  </p>
                </div>
                <p class="mt-2 text-xs leading-5 text-amber-700/80">
                  Waktu awal publikasi
                </p>
              </div>

              {{-- AKHIR TAYANG --}}
              <div
                class="flex h-full min-w-0 flex-col justify-between rounded-[1.25rem] border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(139,92,246,0.10)]">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-violet-500">
                    Akhir Tayang
                  </p>
                  <p class="mt-2 text-[15px] font-semibold leading-6 text-slate-800">
                    {{ $item->expire_at?->format('d M Y H:i') ?: '—' }}
                  </p>
                </div>
                <p class="mt-2 text-xs leading-5 text-violet-700/80">
                  Batas akhir penayangan
                </p>
              </div>
            </div>
          </div>
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

        {{-- CATATAN PENOLAKAN --}}
        @if($item->status === 'rejected' && !empty($item->reject_note))
          <section class="overflow-hidden rounded-[1.5rem] border border-rose-200 bg-white shadow-sm">
            <div class="border-b border-rose-100 px-5 py-4">
              <h2 class="text-sm font-semibold text-rose-700">Catatan Penolakan</h2>
              <p class="mt-1 text-xs text-slate-500">
                Alasan atau catatan perbaikan dari proses verifikasi pengumuman.
              </p>
            </div>

            <div class="px-5 py-5 md:px-6 md:py-6">
              <div class="text-sm leading-7 text-slate-800">
                {!! nl2br(e($item->reject_note)) !!}
              </div>
            </div>
          </section>
        @endif

      </div>
    </section>
  </div>
@endsection