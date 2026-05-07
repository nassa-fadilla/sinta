@extends('admin.layout')
@section('title', 'Survei')

@section('content')
    @php
        $totalData = method_exists($survei, 'total') ? $survei->total() : $survei->count();
        $currentPage = method_exists($survei, 'currentPage') ? $survei->currentPage() : 1;
        $lastPage = method_exists($survei, 'lastPage') ? $survei->lastPage() : 1;

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

        $flashType = session('flash_type', 'created');

        $flashMeta = match ($flashType) {
            'updated' => [
                'bg' => 'bg-amber-50',
                'border' => 'border-amber-200',
                'text' => 'text-amber-800',
                'iconBg' => 'bg-amber-100',
                'iconText' => 'text-amber-600',
                'title' => 'Data berhasil diperbarui',
            ],
            'deleted' => [
                'bg' => 'bg-rose-50',
                'border' => 'border-rose-200',
                'text' => 'text-rose-800',
                'iconBg' => 'bg-rose-100',
                'iconText' => 'text-rose-600',
                'title' => 'Data berhasil dihapus',
            ],
            default => [
                'bg' => 'bg-emerald-50',
                'border' => 'border-emerald-200',
                'text' => 'text-emerald-800',
                'iconBg' => 'bg-emerald-100',
                'iconText' => 'text-emerald-600',
                'title' => 'Data berhasil dibuat',
            ],
        };
    @endphp

    <div x-data="surveiIndex()" class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7h8M8 11h5M6 5a2 2 0 00-2 2v10a2 2 0 002 2h9.5a2.5 2.5 0 002.5-2.5V7a2 2 0 00-2-2H6z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Survei Orang Tua
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Kelola form survei untuk orang tua siswa.
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto lg:items-center">
                        {{-- SEARCH --}}
                        <form method="GET" x-ref="searchForm" class="w-full sm:w-[18rem]">
                            <div
                                class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-100">
                                <span class="text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 21l-5.2-5.2m0 0A7 7 0 1010 17a7 7 0 005.8-3.2z" />
                                    </svg>
                                </span>

                                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul survei..."
                                    class="w-full border-none bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:ring-0"
                                    autocomplete="off" @input="handleSearchInput">

                                <div x-show="isLoading" x-cloak class="pr-1">
                                    <div
                                        class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                                    </div>
                                </div>

                                @if(request('q'))
                                    <a href="{{ route('admin.survei.index') }}"
                                        class="text-[11px] font-medium text-slate-400 transition hover:text-slate-600">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>

                        {{-- BUTTON TAMBAH --}}
                        <a href="{{ route('admin.survei.create') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Buat Survei Baru</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">

                {{-- NOTIFIKASI --}}
                @if(session('ok'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                        class="rounded-[1.5rem] border {{ $flashMeta['border'] }} {{ $flashMeta['bg'] }} px-4 py-3 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl {{ $flashMeta['iconBg'] }} {{ $flashMeta['iconText'] }}">
                                @if($flashType === 'deleted')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 4v6m4-6v6m-7-10 1 13h10l1-13" />
                                    </svg>
                                @elseif($flashType === 'updated')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536M9 13l6.732-6.732a2 2 0 112.828 2.828L11.828 15.828H9v-2.828z" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold {{ $flashMeta['text'] }}">
                                    {{ $flashMeta['title'] }}
                                </p>
                                <p class="mt-0.5 text-sm {{ $flashMeta['text'] }}">
                                    {{ session('ok') }}
                                </p>
                            </div>

                            <button type="button" @click="show = false"
                                class="rounded-full p-1 text-slate-400 transition hover:bg-white/60 hover:text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- TABEL --}}
                <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Daftar Survei</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Data judul, periode, jumlah pertanyaan, dan status survei.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200">
                                {{ $totalData }} data
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] table-auto text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-16 px-5 py-4 text-center md:px-6">No</th>
                                    <th class="px-5 py-4 md:px-6">Judul</th>
                                    <th class="px-5 py-4 md:px-6">Periode</th>
                                    <th class="px-5 py-4 md:px-6">Pertanyaan</th>
                                    <th class="px-5 py-4 text-center md:px-6">Status</th>
                                    <th
                                        class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)] md:px-6">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($survei as $s)
                                    @php
                                        $no = $loop->iteration + ($survei->currentPage() - 1) * $survei->perPage();
                                        $mulai = $s->mulai_at ? date('d M Y', strtotime($s->mulai_at)) : '-';
                                        $akhir = $s->akhir_at ? date('d M Y', strtotime($s->akhir_at)) : '-';
                                    @endphp

                                    <tr class="transition duration-300 hover:bg-blue-50/30">
                                        <td class="px-5 py-5 text-center font-semibold text-slate-500 md:px-6">
                                            {{ $no }}
                                        </td>

                                        <td class="px-5 py-5 md:px-6">
                                            <div class="font-semibold leading-snug text-slate-900">
                                                {{ $s->judul }}
                                            </div>
                                            @if($s->deskripsi ?? false)
                                                <div class="mt-1 text-[11px] leading-5 text-slate-500">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($s->deskripsi), 95) }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-5 py-5 md:px-6">
                                            <div class="text-sm font-medium leading-6 text-slate-800">
                                                {{ $mulai }} – {{ $akhir }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-5 md:px-6">
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                {{ $s->pertanyaan_count }} soal
                                            </span>
                                        </td>

                                        <td class="px-5 py-5 text-center md:px-6">
                                            @if($s->is_active)
                                                <span
                                                    class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>

                                        <td
                                            class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-5 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] md:px-6">
                                            <div class="flex justify-center gap-2">
                                                <a href="{{ route('admin.survei.show', $s->id) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 shadow-sm transition duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Lihat
                                                </a>

                                                <a href="{{ route('admin.survei.edit', $s->id) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-500 px-3.5 py-2 text-xs font-medium text-white shadow-sm transition duration-300 hover:bg-blue-600 hover:shadow-md">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2 2 0 112.828 2.828L11.828 13.828a2 2 0 01-.707.464l-4.242 1.414a1 1 0 01-1.263-1.263l1.414-4.242a2 2 0 01.464-.707z" />
                                                    </svg>
                                                    Kelola
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-14 text-center md:px-6">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 7h8M8 11h5M6 5a2 2 0 00-2 2v10a2 2 0 002 2h9.5a2.5 2.5 0 002.5-2.5V7a2 2 0 00-2-2H6z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">
                                                        Belum ada survei.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Buat survei baru untuk mulai mengumpulkan aspirasi orang tua.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINASI --}}
                    @if($lastPage > 1)
                        <div class="border-t border-slate-200/70 px-5 py-4 md:px-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-xs text-slate-500">
                                    Menampilkan
                                    <span class="font-semibold text-slate-700">{{ $survei->firstItem() ?? 0 }}</span>
                                    –
                                    <span class="font-semibold text-slate-700">{{ $survei->lastItem() ?? 0 }}</span>
                                    dari
                                    <span class="font-semibold text-slate-700">{{ $survei->total() }}</span> data
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
        function surveiIndex() {
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