@extends('kepsek.layout')
@section('title', 'Master Jadwal SIA')

@section('content')
    @php
        use Illuminate\Pagination\LengthAwarePaginator;

        $items = is_array($data ?? null) ? $data : [];

        $selectedHari = request('hari');
        if ($selectedHari) {
            $items = array_values(array_filter($items, function ($item) use ($selectedHari) {
                return strtolower((string) ($item['hari'] ?? '')) === strtolower((string) $selectedHari);
            }));
        }

        $total = count($items);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;

        $currentItems = array_slice($items, ($currentPage - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $currentItems,
            $total,
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
    @endphp

    <div x-data="jadwalIndex()" class="space-y-6">

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            <div class="relative">
                {{-- HEADER --}}
                <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 3v3m8-3v3M4 9h16M5 6h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                                </svg>
                            </div>

                            <div>
                                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                    Data Jadwal
                                </h1>
                                <p class="mt-1 text-sm text-slate-500">
                                    Gunakan pencarian dan filter untuk mempersempit data jadwal pelajaran.
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 lg:pt-1">
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                Total Data: {{ $total }}
                            </span>
                        </div>
                    </div>

                    {{-- SEARCH + FILTER --}}
                    <div class="mt-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            {{-- Search --}}
                            <form method="GET" x-ref="searchForm" class="w-full lg:flex-1 lg:max-w-3xl">
                                @if(request()->filled('hari'))
                                    <input type="hidden" name="hari" value="{{ request('hari') }}">
                                @endif

                                <div
                                    class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                                    <span class="pl-1 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10 4a6 6 0 014.472 9.966l3.281 3.28a1 1 0 01-1.414 1.415l-3.28-3.281A6 6 0 1110 4z" />
                                        </svg>
                                    </span>

                                    <input type="text" name="q" value="{{ request('q') }}"
                                        placeholder="Cari rombel / mapel / guru..."
                                        class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                                        autocomplete="off" @input="handleSearchInput">

                                    <div x-show="isLoading" x-cloak class="pr-1">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                        </div>
                                    </div>
                                </div>
                            </form>

                            {{-- Filter --}}
                            <form method="GET" class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                                @if(request()->filled('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif

                                <select name="hari"
                                    class="min-w-[200px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Hari</option>
                                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                                        <option value="{{ $h }}" @selected(request('hari') == $h)>
                                            {{ $h }}
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                    Filter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1080px] table-auto border-collapse text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200/80 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-4 text-center">No</th>
                                    <th class="px-5 py-4">Rombel</th>
                                    <th class="px-5 py-4">Mapel</th>
                                    <th class="px-5 py-4">Guru</th>
                                    <th class="px-5 py-4">Hari</th>
                                    <th class="px-5 py-4">Jam</th>
                                    <th
                                        class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100/90 text-slate-700">
                                @forelse ($paginator as $j)
                                    @php
                                        $hari = strtolower((string) ($j['hari'] ?? ''));

                                        $hariBadge = match ($hari) {
                                            'senin' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                            'selasa' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                            'rabu' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                            'kamis' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
                                            'jumat' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                            'sabtu' => 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200',
                                            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
                                        };
                                    @endphp

                                    <tr
                                        class="group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]">
                                        <td class="px-4 py-4 text-center text-xs font-medium text-slate-500">
                                            {{ $paginator->firstItem() + $loop->index }}
                                        </td>

                                        <td
                                            class="px-5 py-4 font-semibold text-slate-800 transition duration-300 group-hover:text-blue-700">
                                            {{ $j['rombel'] ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $j['mapel'] ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $j['guru'] ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $hariBadge }}">
                                                {{ $j['hari'] ?? '-' }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $j['jam_mulai'] ?? '-' }}{{ !empty($j['jam_mulai']) && !empty($j['jam_selesai']) ? ' - ' : '' }}{{ $j['jam_selesai'] ?? '' }}
                                        </td>

                                        <td
                                            class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] group-hover:bg-blue-50">
                                            <a href="{{ route('kepsek.sia-master.jadwal.show', $j['id']) }}"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                                                <span>Detail</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 3v3m8-3v3M4 9h16M5 6h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        Tidak ada jadwal ditemukan.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Coba ubah kata kunci pencarian atau filter hari yang digunakan.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- PAGINATION --}}
                @if ($paginator->lastPage() > 1)
                    <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs text-slate-500">
                                Menampilkan
                                <span class="font-semibold text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>
                                –
                                <span class="font-semibold text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
                                dari
                                <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span> data
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
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function jadwalIndex() {
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