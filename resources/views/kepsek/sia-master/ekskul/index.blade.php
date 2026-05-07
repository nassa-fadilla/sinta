@extends('kepsek.layout')
@section('title', 'Data Ekstrakurikuler (SIA)')

@section('content')
    @php
        use Illuminate\Pagination\LengthAwarePaginator;

        $items = is_array($data ?? null) ? $data : [];
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $currentItems = array_slice($items, ($currentPage - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $currentItems,
            count($items),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $pageUrl = function ($page) {
            return request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page]));
        };

        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);

        if ($currentPage <= 3) {
            $startPage = 1;
            $endPage = min($lastPage, 5);
        } elseif ($currentPage >= $lastPage - 2) {
            $endPage = $lastPage;
            $startPage = max(1, $lastPage - 4);
        }

        $totalEkskul = count($items);
        $totalAnggota = collect($items)->sum(fn($item) => (int) ($item['jumlah_anggota'] ?? 0));
        $totalDenganJadwal = collect($items)->filter(function ($item) {
            return !empty($item['hari']) || !empty($item['jam_mulai']) || !empty($item['jam_selesai']);
        })->count();
        $totalTanpaJadwal = max(0, $totalEkskul - $totalDenganJadwal);
    @endphp

    <div x-data="ekskulIndex()" class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 13a4 4 0 10-8 0m12-5a3 3 0 11-6 0 3 3 0 016 0zM10 8a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 18a4 4 0 017.465-1.544M18 21v-1a4 4 0 00-3-3.873" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Data Ekstrakurikuler
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Daftar kegiatan ekstrakurikuler yang tersinkron dari sistem akademik SIA dalam mode baca
                                saja.
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Total Data: {{ $totalEkskul }}
                        </span>
                    </div>
                </div>

                {{-- SEARCH --}}
                <div class="mt-5">
                    <form method="GET" x-ref="searchForm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="w-full lg:flex-1 lg:max-w-3xl">
                                <div
                                    class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                                    <span class="pl-1 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10 4a6 6 0 014.472 9.966l3.281 3.28a1 1 0 01-1.414 1.415l-3.28-3.281A6 6 0 1110 4z" />
                                        </svg>
                                    </span>

                                    <input type="text" name="q" value="{{ $q ?? request('q') }}"
                                        placeholder="Masukkan nama ekstrakurikuler..."
                                        class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                                        autocomplete="off" @input="handleSearchInput">

                                    <div x-show="isLoading" x-cloak class="pr-1">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                    Filter
                                </button>

                                @if(request()->filled('q'))
                                    <a href="{{ route('kepsek.sia-master.ekskul.index') }}"
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
                            Total Ekskul
                        </p>
                        <div class="mt-3 text-3xl font-bold tracking-tight text-blue-700">
                            {{ $totalEkskul }}
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_40px_rgba(16,185,129,0.14)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600">
                            Total Anggota
                        </p>
                        <div class="mt-3 text-3xl font-bold tracking-tight text-emerald-700">
                            {{ $totalAnggota }}
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-amber-100 bg-amber-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_18px_40px_rgba(245,158,11,0.14)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-600">
                            Dengan Jadwal
                        </p>
                        <div class="mt-3 text-3xl font-bold tracking-tight text-amber-700">
                            {{ $totalDenganJadwal }}
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border border-violet-100 bg-violet-50/70 p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_18px_40px_rgba(139,92,246,0.14)]">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-violet-600">
                            Belum Dijadwalkan
                        </p>
                        <div class="mt-3 text-3xl font-bold tracking-tight text-violet-700">
                            {{ $totalTanpaJadwal }}
                        </div>
                    </div>
                </div>

                {{-- TABEL --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Daftar Ekstrakurikuler</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Data ekskul, pembina, jadwal, lokasi, dan jumlah anggota aktif.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                                {{ $paginator->total() }} data
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1120px] table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Nama Ekskul</th>
                                    <th class="px-4 py-3">Pembina</th>
                                    <th class="px-4 py-3">Hari</th>
                                    <th class="px-4 py-3">Jam</th>
                                    <th class="px-4 py-3">Lokasi</th>
                                    <th class="px-4 py-3 text-center">Anggota Aktif</th>
                                    <th
                                        class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($paginator as $e)
                                    @php
                                        $nomor = (($currentPage - 1) * $perPage) + $loop->iteration;
                                    @endphp

                                    <tr class="group transition duration-300 hover:bg-blue-50/40">
                                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-500">
                                            {{ $nomor }}
                                        </td>

                                        <td class="px-4 py-3 font-medium text-slate-900">
                                            {{ $e['nama'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $e['pembina'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $e['hari'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            @if(!empty($e['jam_mulai']) && !empty($e['jam_selesai']))
                                                {{ $e['jam_mulai'] }} - {{ $e['jam_selesai'] }}
                                            @else
                                                <span class="text-xs text-slate-400">Belum diatur</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $e['lokasi'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                {{ $e['jumlah_anggota'] ?? 0 }} siswa
                                            </span>
                                        </td>

                                        <td
                                            class="sticky right-0 z-10 border-l border-slate-200 bg-white px-4 py-3 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] group-hover:bg-blue-50">
                                            <a href="{{ route('kepsek.sia-master.ekskul.show', $e['id']) }}"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16 13a4 4 0 10-8 0m12-5a3 3 0 11-6 0 3 3 0 016 0zM10 8a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18a4 4 0 017.465-1.544M18 21v-1a4 4 0 00-3-3.873" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        Tidak ada data ekstrakurikuler ditemukan.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Coba gunakan kata kunci pencarian yang lain.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($paginator->lastPage() > 1)
                        <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-xs text-slate-500">
                                    Menampilkan
                                    <span class="font-semibold text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>
                                    –
                                    <span class="font-semibold text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
                                    dari
                                    <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
                                    data
                                </div>

                                <nav class="flex flex-wrap items-center gap-1.5">
                                    @if ($paginator->onFirstPage())
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

                                    @if ($paginator->hasMorePages())
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
        function ekskulIndex() {
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
                    }, 500);
                },
            }
        }
    </script>
@endpush