@extends('admin.layout')
@section('title', 'Pengumuman')

@section('content')
  @php
    $rows = method_exists($items, 'getCollection') ? $items->getCollection() : collect($items);
    $totalData = method_exists($items, 'total') ? $items->total() : $rows->count();

    $draftCount = $rows->where('status', 'draft')->count();
    $approvedCount = $rows->where('status', 'approved')->count();
    $rejectedCount = $rows->where('status', 'rejected')->count();

    $aktifTayangCount = $rows->filter(function ($row) {
      $publish = $row->publish_at ?? null;
      $expire = $row->expire_at ?? null;
      $now = now();

      return ($row->status ?? null) === 'approved'
        && (!$publish || $publish <= $now)
        && (!$expire || $expire >= $now);
    })->count();

    $currentPage = method_exists($items, 'currentPage') ? $items->currentPage() : 1;
    $lastPage = method_exists($items, 'lastPage') ? $items->lastPage() : 1;

    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);

    if ($currentPage <= 3) {
      $startPage = 1;
      $endPage = min($lastPage, 5);
    } elseif ($currentPage >= $lastPage - 2) {
      $endPage = $lastPage;
      $startPage = max(1, $lastPage - 4);
    }

    $pageUrl = function ($page) {
      return request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page]));
    };

    $flashMessage = null;
    $flashTitle = null;
    $flashStyle = null;
    $flashDot = null;

    if (session('created')) {
      $flashTitle = 'Pengumuman Dibuat';
      $flashMessage = session('created');
      $flashStyle = 'border-emerald-200 bg-emerald-50 text-emerald-800 ring-emerald-100';
      $flashDot = 'bg-emerald-500';
    } elseif (session('updated')) {
      $flashTitle = 'Pengumuman Diperbarui';
      $flashMessage = session('updated');
      $flashStyle = 'border-amber-200 bg-amber-50 text-amber-800 ring-amber-100';
      $flashDot = 'bg-amber-500';
    } elseif (session('deleted')) {
      $flashTitle = 'Pengumuman Dihapus';
      $flashMessage = session('deleted');
      $flashStyle = 'border-rose-200 bg-rose-50 text-rose-800 ring-rose-100';
      $flashDot = 'bg-rose-500';
    } elseif (session('ok')) {
      $flashTitle = 'Berhasil';
      $flashMessage = session('ok');
      $flashStyle = 'border-emerald-200 bg-emerald-50 text-emerald-800 ring-emerald-100';
      $flashDot = 'bg-emerald-500';
    } elseif (session('no')) {
      $flashTitle = 'Tidak Dapat Diproses';
      $flashMessage = session('no');
      $flashStyle = 'border-rose-200 bg-rose-50 text-rose-800 ring-rose-100';
      $flashDot = 'bg-rose-500';
    }
  @endphp

  <div x-data="pengumumanIndex()" class="space-y-6">
    <section
      class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

      {{-- HEADER --}}
      <div class="border-b border-slate-200 px-5 py-5 md:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.5 8.25 18 6m-2.5 2.25L18 9.75M15.5 8.25L9 12m6.5-3.75L9 4.5m0 7.5-3.5-2.25M9 12v7.5m0-15v7.5" />
              </svg>
            </div>

            <div>
              <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                Pengumuman
              </h1>
              <p class="mt-1 text-sm text-slate-500">
                Kelola pengumuman sekolah berdasarkan status, target penerima, dan periode tayang.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.pengumuman.create') }}"
              class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              Tambah
            </a>
          </div>
        </div>

        {{-- SEARCH + FILTER --}}
        <div class="mt-5">
          <form method="GET" x-ref="searchForm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div class="w-full lg:flex-1 lg:max-w-3xl">
                <div
                  class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                  <span class="pl-1 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10 4a6 6 0 014.472 9.966l3.281 3.28a1 1 0 01-1.414 1.415l-3.28-3.281A6 6 0 1110 4z" />
                    </svg>
                  </span>

                  <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul pengumuman..."
                    class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                    autocomplete="off" @input="handleSearchInput">

                  <div x-show="isLoading" x-cloak class="pr-1">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                <select name="jenis"
                  class="min-w-[180px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                  <option value="">Semua Jenis</option>
                  @foreach(['umum', 'akademik', 'kegiatan', 'prestasi', 'lainnya'] as $j)
                    <option value="{{ $j }}" @selected(request('jenis') === $j)>
                      {{ ucfirst($j) }}
                    </option>
                  @endforeach
                </select>

                <select name="status"
                  class="min-w-[180px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                  <option value="">Semua Status</option>
                  <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                  <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                  <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                </select>

                <button type="submit"
                  class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                  Terapkan
                </button>

                @if(request()->filled('q') || request()->filled('jenis') || request()->filled('status'))
                  <a href="{{ route('admin.pengumuman.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                    Reset
                  </a>
                @endif
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="p-5 md:p-6 space-y-6">

        {{-- REKAP --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <div
            class="rounded-[1.5rem] border border-blue-100 bg-blue-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_40px_rgba(59,130,246,0.14)]">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-600">
              Total
            </p>
            <div class="mt-3 text-3xl font-bold tracking-tight text-blue-700">
              {{ $totalData }}
            </div>
          </div>

          <div
            class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_40px_rgba(16,185,129,0.14)]">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600">
              Disetujui
            </p>
            <div class="mt-3 text-3xl font-bold tracking-tight text-emerald-700">
              {{ $approvedCount }}
            </div>
          </div>

          <div
            class="rounded-[1.5rem] border border-amber-100 bg-amber-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_18px_40px_rgba(245,158,11,0.14)]">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-600">
              Draft
            </p>
            <div class="mt-3 text-3xl font-bold tracking-tight text-amber-700">
              {{ $draftCount }}
            </div>
          </div>

          <div
            class="rounded-[1.5rem] border border-violet-100 bg-violet-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_18px_40px_rgba(139,92,246,0.14)]">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-violet-600">
              Aktif Tayang
            </p>
            <div class="mt-3 flex items-end gap-2">
              <span class="text-3xl font-bold tracking-tight text-violet-700">{{ $aktifTayangCount }}</span>
              <span class="pb-1 text-sm text-slate-500">/ {{ $rejectedCount }} ditolak</span>
            </div>
          </div>
        </div>

        {{-- FLASH NOTIFIKASI --}}
        @if($flashMessage)
          <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
            class="rounded-[1.5rem] border px-4 py-3 text-sm shadow-sm ring-1 {{ $flashStyle }}">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3">
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $flashDot }}"></span>

                <div>
                  <p class="font-semibold">
                    {{ $flashTitle }}
                  </p>
                  <p class="mt-0.5 leading-6">
                    {{ $flashMessage }}
                  </p>
                </div>
              </div>

              <button type="button" @click="show = false"
                class="rounded-full px-2 py-1 text-xs font-semibold opacity-70 transition hover:bg-white/70 hover:opacity-100">
                Tutup
              </button>
            </div>
          </div>
        @endif

        {{-- DAFTAR PENGUMUMAN --}}
        <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h2 class="text-sm font-semibold text-slate-800">Daftar Pengumuman</h2>
                <p class="mt-1 text-xs text-slate-500">
                  Data judul, target penerima, tahun ajaran, jadwal tayang, dan status pengumuman.
                </p>
              </div>

              <span
                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                {{ $totalData }} data
              </span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] table-auto text-sm">
              <thead class="bg-slate-50">
                <tr
                  class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                  <th class="w-14 px-4 py-3 text-center">No</th>
                  <th class="px-5 py-3">Judul</th>
                  <th class="px-5 py-3">Jenis</th>
                  <th class="px-5 py-3">Target</th>
                  <th class="px-5 py-3">Tahun Ajaran</th>
                  <th class="px-5 py-3">Tayang</th>
                  <th class="px-5 py-3">Status</th>
                  <th
                    class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                    Aksi
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($items as $row)
                  @php
                    $tahunAjaran = $tahunAjaranMap[$row->tahun_ajaran_id] ?? null;

                    $statusMeta = match ($row->status) {
                      'draft' => ['Draft', 'bg-slate-50 text-slate-700 ring-1 ring-slate-200', 'bg-slate-400'],
                      'rejected' => ['Ditolak', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'bg-rose-500'],
                      'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'bg-emerald-500'],
                      default => ['Tidak dikenal', 'bg-slate-50 text-slate-700 ring-1 ring-slate-200', 'bg-slate-400'],
                    };
                  @endphp

                  <tr class="transition duration-300 hover:bg-blue-50/40">
                    <td class="px-4 py-3 text-center text-xs font-semibold text-slate-500">
                      {{ ($items->firstItem() ?? 1) + $loop->index }}
                    </td>

                    <td class="px-5 py-3">
                      <div class="font-semibold text-slate-800">
                        {{ $row->judul }}
                      </div>
                      <div class="mt-1 text-xs text-slate-500">
                        {{ \Illuminate\Support\Str::limit(strip_tags($row->isi), 90) }}
                      </div>
                    </td>

                    <td class="px-5 py-3">
                      <span
                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-700">
                        {{ $row->jenis }}
                      </span>
                    </td>

                    <td class="px-5 py-3">
                      @if($row->target_scope === 'all')
                        Semua
                      @elseif($row->target_scope === 'tingkat')
                        Tingkat {{ $row->target_tingkat ?: '-' }}
                      @else
                        <span class="text-slate-400">—</span>
                      @endif
                    </td>

                    <td class="px-5 py-3">
                      @if($tahunAjaran)
                        <div>{{ $tahunAjaran->nama_tahun }}</div>
                        <div class="text-[11px] text-slate-500">
                          Semester {{ $tahunAjaran->semester ?? '-' }}
                        </div>
                      @else
                        <span class="text-slate-400">—</span>
                      @endif
                    </td>

                    <td class="px-5 py-3">
                      <div>{{ $row->publish_at?->format('d M Y H:i') ?: '-' }}</div>
                      @if($row->expire_at)
                        <div class="text-[11px] text-slate-500">
                          s.d. {{ $row->expire_at->format('d M Y H:i') }}
                        </div>
                      @endif
                    </td>

                    <td class="px-5 py-3">
                      <span
                        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta[1] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta[2] }}"></span>
                        {{ $statusMeta[0] }}
                      </span>
                    </td>

                    <td
                      class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)]">
                      <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.pengumuman.show', $row) }}"
                          class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                          Detail
                        </a>

                        @if(in_array($row->status, ['draft', 'rejected']))
                          <a href="{{ route('admin.pengumuman.edit', $row) }}"
                            class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-semibold text-blue-700 shadow-sm transition duration-300 hover:bg-blue-100 hover:shadow-md">
                            Ubah
                          </a>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="px-5 py-14 text-center">
                      <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                        <div
                          class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.5 8.25 18 6m-2.5 2.25L18 9.75M15.5 8.25L9 12m6.5-3.75L9 4.5m0 7.5-3.5-2.25M9 12v7.5m0-15v7.5" />
                          </svg>
                        </div>
                        <div>
                          <p class="text-sm font-semibold text-slate-700">
                            Belum ada pengumuman.
                          </p>
                          <p class="mt-1 text-xs text-slate-500">
                            Coba ubah kata kunci pencarian atau filter yang digunakan.
                          </p>
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- PAGINATION --}}
          @if($lastPage > 1)
            <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                  Menampilkan
                  <span class="font-semibold text-slate-700">{{ $items->firstItem() ?? 0 }}</span>
                  –
                  <span class="font-semibold text-slate-700">{{ $items->lastItem() ?? 0 }}</span>
                  dari
                  <span class="font-semibold text-slate-700">{{ $items->total() }}</span> data
                </div>

                <nav class="flex flex-wrap items-center gap-1.5">
                  @if ($currentPage <= 1)
                    <span
                      class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-400">
                      Prev
                    </span>
                  @else
                    <a href="{{ $pageUrl($currentPage - 1) }}"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                      Prev
                    </a>
                  @endif

                  @if ($startPage > 1)
                    <a href="{{ $pageUrl(1) }}"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                      1
                    </a>
                    @if ($startPage > 2)
                      <span class="px-2 text-slate-400">…</span>
                    @endif
                  @endif

                  @for ($p = $startPage; $p <= $endPage; $p++)
                    @if ($p == $currentPage)
                      <span
                        class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm">
                        {{ $p }}
                      </span>
                    @else
                      <a href="{{ $pageUrl($p) }}"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                        {{ $p }}
                      </a>
                    @endif
                  @endfor

                  @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                      <span class="px-2 text-slate-400">…</span>
                    @endif
                    <a href="{{ $pageUrl($lastPage) }}"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                      {{ $lastPage }}
                    </a>
                  @endif

                  @if ($currentPage < $lastPage)
                    <a href="{{ $pageUrl($currentPage + 1) }}"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                      Next
                    </a>
                  @else
                    <span
                      class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-400">
                      Next
                    </span>
                  @endif
                </nav>
              </div>
            </div>
          @endif
        </div>

      </div>
    </section>
  </div>
@endsection

@push('scripts')
  <script>
    function pengumumanIndex() {
      return {
        isLoading: false,
        typingTimer: null,

        handleSearchInput() {
          this.isLoading = true;

          if (this.typingTimer) {
            clearTimeout(this.typingTimer);
          }

          this.typingTimer = setTimeout(() => {
            if (this.$refs.searchForm) {
              this.$refs.searchForm.submit();
            }
          }, 600);
        },
      }
    }
  </script>
@endpush