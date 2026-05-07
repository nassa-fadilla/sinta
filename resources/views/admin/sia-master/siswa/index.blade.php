@extends('admin.layout')
@section('title', 'Master Siswa – SIA')

@section('content')
    <div x-data="siswaIndex()" class="space-y-6">

        @php
            $perPage = 10;
            $collection = collect($data ?? []);

            $status = request('status');
            if ($status) {
                $collection = $collection->filter(function ($row) use ($status) {
                    return strtolower((string) ($row['status'] ?? '')) === strtolower($status);
                })->values();
            }

            $page = max((int) request('page', 1), 1);
            $total = $collection->count();
            $lastPage = (int) ceil(max($total, 1) / $perPage);
            $page = min($page, max($lastPage, 1));

            $offset = ($page - 1) * $perPage;
            $pageItems = $collection->slice($offset, $perPage)->values();

            $i = $offset + 1;

            $queryBase = request()->except('page');
            $makeUrl = function ($p) use ($queryBase) {
                return request()->url() . '?' . http_build_query(array_merge($queryBase, ['page' => $p]));
            };
        @endphp

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            <div>
                {{-- HEADER --}}
                <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 5a2 2 0 012-2h10a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h5" />
                                </svg>
                            </div>

                            <div>
                                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                    Daftar Siswa
                                </h1>
                                <p class="mt-1 text-sm text-slate-500">
                                    Gunakan pencarian dan filter untuk mempersempit data siswa.
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
                                @if(request()->filled('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
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
                                        placeholder="Cari nama / NIS / NISN..."
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

                                <select name="status"
                                    class="min-w-[190px] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Status</option>
                                    @foreach(['aktif', 'lulus', 'pindah', 'keluar'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') == $st)>
                                            {{ ucfirst($st) }}
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
                        <table class="w-full min-w-[980px] table-auto border-collapse text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200/80 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-4 text-center">No</th>
                                    <th class="px-5 py-4">Nama</th>
                                    <th class="px-5 py-4">NIS</th>
                                    <th class="px-5 py-4">NISN</th>
                                    <th class="px-5 py-4">JK</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th
                                        class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100/90 text-slate-700">
                                @forelse($pageItems as $row)
                                    @php
                                        $statusValue = strtolower((string) ($row['status'] ?? ''));
                                        $jk = $row['jenis_kelamin'] ?? '-';

                                        $statusClass = match ($statusValue) {
                                            'aktif' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
                                            'lulus' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/80',
                                            'pindah' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
                                            'keluar' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
                                            default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                                        };

                                        $dotClass = match ($statusValue) {
                                            'aktif' => 'bg-emerald-500',
                                            'lulus' => 'bg-blue-500',
                                            'pindah' => 'bg-amber-500',
                                            'keluar' => 'bg-rose-500',
                                            default => 'bg-slate-400',
                                        };

                                        $fotoRaw = $row['foto'] ?? null;
                                        $fotoThumb = null;

                                        if (!empty($fotoRaw)) {
                                            $rawFoto = trim((string) $fotoRaw);

                                            if (preg_match('/^https?:\/\//i', $rawFoto)) {
                                                $fotoThumb = $rawFoto;
                                            } else {
                                                $rawFoto = str_replace('\\', '/', $rawFoto);
                                                $rawFoto = preg_replace('#/+#', '/', $rawFoto);
                                                $rawFoto = ltrim($rawFoto, '/');

                                                $basename = basename($rawFoto);

                                                $candidates = [
                                                    $rawFoto,
                                                    'foto_siswa/' . $basename,
                                                    'sia/' . $rawFoto,
                                                    'sia/foto_siswa/' . $basename,
                                                    'storage/foto_siswa/' . $basename,
                                                    'storage/sia/foto_siswa/' . $basename,
                                                ];

                                                $candidates = array_values(array_unique(array_filter($candidates)));

                                                foreach ($candidates as $relativePath) {
                                                    if (is_file(public_path($relativePath))) {
                                                        $fotoThumb = asset($relativePath);
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        $inisial = \Illuminate\Support\Str::of($row['nama'] ?? 'S')
                                            ->trim()
                                            ->explode(' ')
                                            ->map(fn($p) => mb_substr($p, 0, 1))
                                            ->take(2)
                                            ->implode('');
                                    @endphp

                                    <tr
                                        class="group transition duration-300 hover:bg-blue-50/50 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]">
                                        <td class="px-4 py-4 text-center text-xs font-medium text-slate-500">
                                            {{ $i++ }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 group-hover:scale-[1.04] group-hover:border-blue-200 group-hover:shadow-md">
                                                    @if($fotoThumb)
                                                        <img src="{{ $fotoThumb }}" alt="Foto {{ $row['nama'] ?? 'Siswa' }}"
                                                            class="h-full w-full object-cover object-top"
                                                            onerror="this.onerror=null; this.parentElement.innerHTML='<span class=&quot;text-xs font-semibold text-blue-700&quot;>{{ $inisial }}</span>'; ">
                                                    @else
                                                        <span class="text-xs font-semibold text-blue-700">
                                                            {{ $inisial }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="min-w-0">
                                                    <div
                                                        class="truncate font-semibold text-slate-800 transition duration-300 group-hover:text-blue-700">
                                                        {{ $row['nama'] ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 font-medium text-slate-700">
                                            {{ $row['nis'] ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $row['nisn'] ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 transition duration-300 group-hover:bg-slate-200">
                                                {{ $jk }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                <span>{{ ucfirst($row['status'] ?? '-') }}</span>
                                            </span>
                                        </td>

                                        <td
                                            class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] group-hover:bg-blue-50">
                                            <a href="{{ route('admin.sia-master.siswa.show', $row['id']) }}"
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
                                                            d="M9 13h6m-6 4h6M5 5h14v14H5z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">Tidak ada data siswa.</p>
                                                    <p class="mt-1 text-xs text-slate-500">Coba ubah kata kunci pencarian atau
                                                        filter yang digunakan.</p>
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
                @if($total > $perPage)
                    <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs text-slate-500">
                                Menampilkan
                                <span class="font-semibold text-slate-700">{{ $total ? ($offset + 1) : 0 }}</span>
                                –
                                <span class="font-semibold text-slate-700">{{ min($offset + $perPage, $total) }}</span>
                                dari
                                <span class="font-semibold text-slate-700">{{ $total }}</span> data
                            </div>

                            <nav class="flex flex-wrap items-center gap-1.5">
                                @if($page > 1)
                                    <a href="{{ $makeUrl($page - 1) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        Prev
                                    </a>
                                @else
                                    <span
                                        class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-400">
                                        Prev
                                    </span>
                                @endif

                                @php
                                    $start = max(1, $page - 2);
                                    $end = min($lastPage, $page + 2);

                                    if ($page <= 3) {
                                        $start = 1;
                                        $end = min($lastPage, 5);
                                    } elseif ($page >= $lastPage - 2) {
                                        $end = $lastPage;
                                        $start = max(1, $lastPage - 4);
                                    }
                                @endphp

                                @if($start > 1)
                                    <a href="{{ $makeUrl(1) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        1
                                    </a>
                                    @if($start > 2)
                                        <span class="px-2 text-slate-400">…</span>
                                    @endif
                                @endif

                                @for($p = $start; $p <= $end; $p++)
                                    @if($p == $page)
                                        <span
                                            class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm">
                                            {{ $p }}
                                        </span>
                                    @else
                                        <a href="{{ $makeUrl($p) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                            {{ $p }}
                                        </a>
                                    @endif
                                @endfor

                                @if($end < $lastPage)
                                    @if($end < $lastPage - 1)
                                        <span class="px-2 text-slate-400">…</span>
                                    @endif
                                    <a href="{{ $makeUrl($lastPage) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        {{ $lastPage }}
                                    </a>
                                @endif

                                @if($page < $lastPage)
                                    <a href="{{ $makeUrl($page + 1) }}"
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
        function siswaIndex() {
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