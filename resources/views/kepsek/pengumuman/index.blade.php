@extends('kepsek.layout')
@section('title', 'Persetujuan Pengumuman')

@section('content')
    <div x-data="kepsekPengumumanIndex()" class="space-y-6">
        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

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

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Pengumuman
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Daftar pengumuman yang diajukan admin untuk ditinjau dan disetujui oleh Kepala Sekolah.
                            </p>
                        </div>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 self-start rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        Total Data: {{ $items->total() }}
                    </div>
                </div>

                {{-- FILTER --}}
                <div class="mt-6">
                    <form method="GET" x-ref="searchForm"
                        class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex w-full flex-col gap-3 xl:flex-row xl:items-center">
                            <div class="w-full xl:max-w-[760px]">
                                <div
                                    class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                                    <span class="text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>

                                    <input type="text" name="q" value="{{ request('q') }}"
                                        placeholder="Cari judul pengumuman..."
                                        class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-0"
                                        autocomplete="off" @input="handleSearchInput">

                                    <div x-show="isLoading" x-cloak class="shrink-0">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                            <div class="w-full sm:w-[230px]">
                                <select name="status"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="pending" @selected(request('status', 'pending') === 'pending')>
                                        Menunggu Persetujuan
                                    </option>
                                    <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                    Filter
                                </button>

                                @if(request()->filled('q') || request()->filled('status'))
                                    <a href="{{ route('kepsek.pengumuman.index') }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ALERT --}}
            @if(session('ok'))
                <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-3 text-sm text-emerald-700 md:px-6">
                    {{ session('ok') }}
                </div>
            @endif

            @if(session('no'))
                <div class="border-b border-rose-100 bg-rose-50 px-5 py-3 text-sm text-rose-700 md:px-6">
                    {{ session('no') }}
                </div>
            @endif

            {{-- TABLE --}}
            <div class="overflow-x-auto">
                <table class="min-w-[1080px] w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <th class="px-5 py-4 text-center w-16">No</th>
                            <th class="px-5 py-4 text-left">Judul</th>
                            <th class="px-5 py-4 text-left">Jenis</th>
                            <th class="px-5 py-4 text-left">Target</th>
                            <th class="px-5 py-4 text-left">Tahun Ajaran</th>
                            <th class="px-5 py-4 text-left">Periode Tayang</th>
                            <th class="px-5 py-4 text-left">Status</th>
                            <th
                                class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($items as $row)
                            @php
                                $tahunAjaran = $tahunAjaranMap[$row->tahun_ajaran_id] ?? null;

                                $statusMeta = match ($row->status) {
                                    'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
                                    'rejected' => ['Ditolak', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
                                    default => ['Menunggu', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
                                };
                            @endphp

                            <tr class="transition duration-300 hover:bg-blue-50/30">
                                <td class="px-5 py-5 text-center text-base text-slate-500">
                                    {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                                </td>

                                <td class="px-5 py-5">
                                    <div class="font-semibold leading-snug text-slate-900">
                                        {{ $row->judul }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500">
                                        Dibuat {{ $row->created_at?->format('d M Y') ?? '-' }}
                                    </div>
                                </td>

                                <td class="px-5 py-5">
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold capitalize text-slate-700">
                                        {{ $row->jenis }}
                                    </span>
                                </td>

                                <td class="px-5 py-5">
                                    @if($row->target_scope === 'all')
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                            Semua
                                        </span>
                                    @elseif($row->target_scope === 'tingkat')
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                            Tingkat {{ $row->target_tingkat ?: '-' }}
                                        </span>
                                    @elseif($row->target_scope === 'kelas')
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                            Per Kelas
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            Tidak ditentukan
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-5">
                                    @if($tahunAjaran)
                                        <div class="font-medium text-slate-800">
                                            {{ $tahunAjaran->nama_tahun ?? '—' }}
                                        </div>
                                        @if($tahunAjaran->semester)
                                            <div class="mt-0.5 text-[11px] text-slate-500">
                                                Semester {{ $tahunAjaran->semester }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>

                                <td class="px-5 py-5">
                                    <div class="text-sm text-slate-800">
                                        {{ $row->publish_at?->format('d M Y H:i') ?? '—' }}
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-slate-500">
                                        s.d. {{ $row->expire_at?->format('d M Y H:i') ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-5 py-5">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusMeta[1] }}">
                                        {{ $statusMeta[0] }}
                                    </span>
                                </td>

                                <td
                                    class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-5 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)]">
                                    <a href="{{ route('kepsek.pengumuman.show', $row) }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition duration-300 hover:bg-blue-100 hover:shadow-md">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span
                                            class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h6" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.5 19.5l2.25-2.25m0 0L21 15m-2.25 2.25L16.5 15" />
                                            </svg>
                                        </span>
                                        <h3 class="mt-3 text-sm font-semibold text-slate-700">Tidak ada pengumuman</h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Belum ada data pengumuman yang sesuai dengan filter saat ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION MENYATU DI DALAM CARD --}}
            @if($items->hasPages())
                @php
                    $currentPage = $items->currentPage();
                    $lastPage = $items->lastPage();

                    $start = max(1, $currentPage - 2);
                    $end = min($lastPage, $currentPage + 2);

                    if ($currentPage <= 3) {
                        $start = 1;
                        $end = min($lastPage, 5);
                    } elseif ($currentPage >= $lastPage - 2) {
                        $end = $lastPage;
                        $start = max(1, $lastPage - 4);
                    }
                @endphp

                <div class="border-t border-slate-200 px-5 py-4 md:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs text-slate-500">
                            Menampilkan
                            <span class="font-medium text-slate-700">{{ $items->firstItem() ?? 0 }}</span>
                            –
                            <span class="font-medium text-slate-700">{{ $items->lastItem() ?? 0 }}</span>
                            dari
                            <span class="font-medium text-slate-700">{{ $items->total() }}</span>
                            data
                        </div>

                        <nav class="flex flex-wrap items-center gap-1">
                            @if($items->onFirstPage())
                                <span
                                    class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-400">
                                    Prev
                                </span>
                            @else
                                <a href="{{ $items->appends(request()->all())->previousPageUrl() }}"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                    Prev
                                </a>
                            @endif

                            @if($start > 1)
                                <a href="{{ $items->appends(request()->all())->url(1) }}"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                    1
                                </a>

                                @if($start > 2)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif
                            @endif

                            @for($page = $start; $page <= $end; $page++)
                                @if($page == $currentPage)
                                    <span
                                        class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $items->appends(request()->all())->url($page) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endfor

                            @if($end < $lastPage)
                                @if($end < $lastPage - 1)
                                    <span class="px-2 text-slate-400">…</span>
                                @endif

                                <a href="{{ $items->appends(request()->all())->url($lastPage) }}"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                    {{ $lastPage }}
                                </a>
                            @endif

                            @if($items->hasMorePages())
                                <a href="{{ $items->appends(request()->all())->nextPageUrl() }}"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
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
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function kepsekPengumumanIndex() {
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