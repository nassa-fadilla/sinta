@extends('admin.layout')
@section('title', 'Tahun Ajaran – SIA')

@section('content')
    <div class="space-y-6">

        @php
            $tahunAjaran = collect($tahunAjaran ?? []);

            $selectedTahun = request('tahun');
            $selectedSemester = request('semester');
            $selectedStatus = request('status');

            $optionsTahun = $tahunAjaran
                ->pluck('nama_tahun')
                ->filter()
                ->unique()
                ->values();

            if ($selectedTahun) {
                $tahunAjaran = $tahunAjaran->filter(function ($ta) use ($selectedTahun) {
                    return (string) ($ta->nama_tahun ?? '') === (string) $selectedTahun;
                })->values();
            }

            if ($selectedSemester) {
                $tahunAjaran = $tahunAjaran->filter(function ($ta) use ($selectedSemester) {
                    return strtolower((string) ($ta->semester ?? '')) === strtolower((string) $selectedSemester);
                })->values();
            }

            if ($selectedStatus) {
                $tahunAjaran = $tahunAjaran->filter(function ($ta) use ($selectedStatus) {
                    return strtolower((string) ($ta->status ?? '')) === strtolower((string) $selectedStatus);
                })->values();
            }

            $total = $tahunAjaran->count();
            $no = 1;
        @endphp

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
                                        d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                </svg>
                            </div>

                            <div>
                                <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                    Daftar Tahun Ajaran
                                </h1>
                                <p class="mt-1 text-sm text-slate-500">
                                    Data tahun ajaran terintegrasi dari sistem akademik SIA.
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

                    {{-- FILTER --}}
                    <div class="mt-5">
                        <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                            <div class="grid w-full grid-cols-1 gap-3 md:grid-cols-3 lg:max-w-4xl">
                                <select name="tahun"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach($optionsTahun as $tahun)
                                        <option value="{{ $tahun }}" @selected($selectedTahun == $tahun)>
                                            {{ $tahun }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="semester"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Semester</option>
                                    <option value="ganjil" @selected(strtolower((string) $selectedSemester) === 'ganjil')>
                                        Ganjil</option>
                                    <option value="genap" @selected(strtolower((string) $selectedSemester) === 'genap')>Genap
                                    </option>
                                </select>

                                <select name="status"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" @selected(strtolower((string) $selectedStatus) === 'aktif')>Aktif
                                    </option>
                                    <option value="nonaktif" @selected(strtolower((string) $selectedStatus) === 'nonaktif')>
                                        Nonaktif</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                                    Cari
                                </button>

                                @if(request()->filled('tahun') || request()->filled('semester') || request()->filled('status'))
                                    <a href="{{ route('admin.sia-master.tahun-ajaran.index') }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto border-collapse text-sm">
                            <thead class="bg-slate-50">
                                <tr
                                    class="border-b border-slate-200/80 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="w-14 px-4 py-4 text-center">No</th>
                                    <th class="px-5 py-4">Tahun Ajaran</th>
                                    <th class="px-5 py-4">Semester</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Mulai</th>
                                    <th class="px-5 py-4">Selesai</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100/90 text-slate-700">
                                @forelse ($tahunAjaran as $ta)
                                    @php
                                        $statusValue = strtolower((string) ($ta->status ?? ''));

                                        $statusClass = $statusValue === 'aktif'
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80'
                                            : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200';

                                        $dotClass = $statusValue === 'aktif'
                                            ? 'bg-emerald-500'
                                            : 'bg-slate-400';
                                    @endphp

                                    <tr
                                        class="group transition duration-300 hover:bg-blue-50/50 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]">
                                        <td class="px-4 py-4 text-center text-xs font-medium text-slate-500">
                                            {{ $no++ }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <div
                                                class="font-semibold text-slate-800 transition duration-300 group-hover:text-blue-700">
                                                {{ $ta->nama_tahun ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 transition duration-300 group-hover:bg-slate-200">
                                                {{ ucfirst($ta->semester ?? '-') }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                <span>{{ ucfirst($ta->status ?? 'nonaktif') }}</span>
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $ta->tanggal_mulai ?? '-' }}
                                        </td>

                                        <td class="px-5 py-4 text-slate-700">
                                            {{ $ta->tanggal_selesai ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-14 text-center">
                                            <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                                                <div
                                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">Tidak ada data tahun ajaran.
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Coba ubah filter tahun ajaran atau semester yang digunakan.
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
            </div>
        </section>
    </div>
@endsection