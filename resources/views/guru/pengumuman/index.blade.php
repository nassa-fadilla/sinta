@extends('guru.layout')
@section('title', 'Pengumuman')

@section('content')
    @php
        $activeJenis = request('jenis');
        $activeQuery = request('q');

        $filterBadge = match ($activeJenis) {
            'akademik' => 'bg-blue-50 text-blue-700 border-blue-200',
            'kegiatan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'prestasi' => 'bg-amber-50 text-amber-700 border-amber-200',
            'umum' => 'bg-violet-50 text-violet-700 border-violet-200',
            'lainnya' => 'bg-slate-50 text-slate-700 border-slate-200',
            default => 'bg-white text-slate-600 border-slate-200',
        };
    @endphp

    <div x-data="pengumumanIndex()" class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.6rem] border border-slate-200/80 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h10.5A2.25 2.25 0 0019.5 18V14.25z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 9h7.5M8.25 12.75h7.5M8.25 16.5h4.5" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Daftar Pengumuman
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi pengumuman sekolah terbaru untuk guru.
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if($activeQuery)
                                    <span
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-medium text-slate-600">
                                        Kata kunci: {{ $activeQuery }}
                                    </span>
                                @endif

                                @if($activeJenis)
                                    <span
                                        class="inline-flex items-center rounded-full border px-3.5 py-1.5 text-xs font-medium {{ $filterBadge }}">
                                        Jenis: {{ ucfirst($activeJenis) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- SEARCH + FILTER --}}
                    <form method="GET" x-ref="searchForm" class="w-full xl:w-auto xl:min-w-[560px]">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                            <div class="w-full lg:flex-1">
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
                                        placeholder="Cari judul pengumuman..."
                                        class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                                        autocomplete="off" @input="handleSearchInput">

                                    <div x-show="isLoading" x-cloak class="pr-1">
                                        <div
                                            class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full lg:w-[180px]">
                                <select name="jenis"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Jenis</option>
                                    <option value="akademik" @selected(request('jenis') === 'akademik')>Akademik</option>
                                    <option value="kegiatan" @selected(request('jenis') === 'kegiatan')>Kegiatan</option>
                                    <option value="prestasi" @selected(request('jenis') === 'prestasi')>Prestasi</option>
                                    <option value="lainnya" @selected(request('jenis') === 'lainnya')>Lainnya</option>
                                    <option value="umum" @selected(request('jenis') === 'umum')>Umum</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                                    Terapkan
                                </button>

                                @if(request()->filled('q') || request()->filled('jenis'))
                                    <a href="{{ route('guru.pengumuman.index') }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px] table-auto border-collapse text-sm">
                        <thead class="bg-slate-50">
                            <tr
                                class="border-b border-slate-200/80 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4 md:px-6">Judul</th>
                                <th class="px-5 py-4">Jenis</th>
                                <th class="px-5 py-4">Tanggal Tayang</th>
                                <th
                                    class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)] md:px-6">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100/90 text-slate-700">
                            @forelse ($items as $row)
                                @php
                                    $jenis = strtolower($row->jenis ?? 'lainnya');

                                    $badgeClass = match ($jenis) {
                                        'akademik' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/80',
                                        'kegiatan' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
                                        'prestasi' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
                                        'umum' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200/80',
                                        default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                                    };

                                    $iconClass = match ($jenis) {
                                        'akademik' => 'bg-blue-100 text-blue-700',
                                        'kegiatan' => 'bg-emerald-100 text-emerald-700',
                                        'prestasi' => 'bg-amber-100 text-amber-700',
                                        'umum' => 'bg-violet-100 text-violet-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };

                                    $buttonHover = match ($jenis) {
                                        'akademik' => 'hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700',
                                        'kegiatan' => 'hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700',
                                        'prestasi' => 'hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700',
                                        'umum' => 'hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700',
                                        default => 'hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700',
                                    };
                                @endphp

                                <tr
                                    class="group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.35)]">
                                    <td class="px-5 py-4 md:px-6">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $iconClass }} shadow-sm transition duration-300 group-hover:scale-[1.04]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M7.5 3.75h7.379a1.5 1.5 0 011.06.44l2.871 2.87a1.5 1.5 0 01.44 1.061V19.5A1.5 1.5 0 0117.75 21h-10.5a1.5 1.5 0 01-1.5-1.5v-14a1.75 1.75 0 011.75-1.75z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 9.75h6M9 13.5h6M9 17.25h3.75" />
                                                </svg>
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    class="truncate font-semibold text-slate-800 transition duration-300 group-hover:text-blue-700">
                                                    {{ $row->judul }}
                                                </div>
                                                <div class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($row->isi ?? ''), 120) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                                            {{ ucfirst($jenis) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        <div class="font-medium">
                                            {{ $row->publish_at?->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-400">
                                            {{ $row->publish_at?->format('H:i') ?? '-' }} WIB
                                        </div>
                                    </td>

                                    <td
                                        class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] transition duration-300 group-hover:bg-blue-50 md:px-6">
                                        <a href="{{ route('guru.pengumuman.show', $row) }}"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md {{ $buttonHover }}">
                                            <span>Lihat</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-14 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 14.25v-8.25A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25h6.75" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 21l2.25-2.25L21 21m-4.5-4.5a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-700">Belum ada pengumuman.</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    Data pengumuman akan tampil di sini saat tersedia.
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
            @if(method_exists($items, 'links'))
                <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
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
                    }, 500);
                },
            }
        }
    </script>
@endpush