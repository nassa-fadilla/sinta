@extends('admin.layout')
@section('title', 'Detail Guru')

@section('content')

    @php
        $defaultFoto = file_exists(public_path('images/default-user.png'))
            ? asset('images/default-user.png')
            : asset('images/default-siswa.png');

        $fotoRaw = $guru->foto ?? null;
        $fotoPath = null;

        if (!empty($fotoRaw)) {
            $rawFoto = trim((string) $fotoRaw);

            if (preg_match('/^https?:\/\//i', $rawFoto)) {
                $fotoPath = $rawFoto;
            } else {
                $rawFoto = str_replace('\\', '/', $rawFoto);
                $rawFoto = preg_replace('#/+#', '/', $rawFoto);
                $rawFoto = ltrim($rawFoto, '/');

                $basename = basename($rawFoto);

                $candidates = [
                    $rawFoto,
                    'foto_guru/' . $basename,
                    'sia/' . $rawFoto,
                    'sia/foto_guru/' . $basename,
                    'storage/foto_guru/' . $basename,
                    'storage/sia/foto_guru/' . $basename,
                ];

                $candidates = array_values(array_unique(array_filter($candidates)));

                foreach ($candidates as $relativePath) {
                    if (is_file(public_path($relativePath))) {
                        $fotoPath = asset($relativePath);
                        break;
                    }
                }
            }
        }

        if (!$fotoPath && !empty($guru->foto_url) && preg_match('/^https?:\/\//i', (string) $guru->foto_url)) {
            $fotoPath = $guru->foto_url;
        }

        $previewFoto = $fotoPath ?: $defaultFoto;

        $ttl = $guru->tanggal_lahir
            ? \Carbon\Carbon::parse($guru->tanggal_lahir)->translatedFormat('d-m-Y')
            : '-';
    @endphp

    <div x-data="{ tab: 'pribadi', openFoto: false }" class="space-y-6">

        {{-- CARD HEADER + PROFIL --}}
        <section
            class="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">

            <div class="border-b border-slate-100 bg-white px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-3">
                        <span
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 20a8 8 0 0116 0" />
                            </svg>
                        </span>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Detail Guru
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $guru->nama ?? '-' }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi lengkap guru dari SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('admin.sia-master.guru.index') }}"
                         class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2.2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
    </svg>
    <span>Kembali</span>
                    </a>
                </div>
            </div>

            {{-- TAB --}}
            <nav class="flex flex-wrap gap-2 border-b border-slate-100 bg-white px-4 py-3 md:px-5">
                @foreach ([
            'pribadi' => 'Data Pribadi',
            'kontak' => 'Kontak',
            'status' => 'Status',
        ] as $key => $label)
                    <button type="button" @click="tab='{{ $key }}'"
                        :class="tab==='{{ $key }}'
                            ? 'border-blue-200 bg-white/95 text-blue-700 shadow-[0_8px_20px_rgba(59,130,246,0.10)]'
                            : 'border-slate-200/70 bg-white/55 text-slate-600 hover:border-blue-100 hover:bg-white hover:text-blue-600 hover:shadow-sm hover:-translate-y-[1px]'"
                        class="rounded-2xl border px-4 py-2 text-xs font-medium backdrop-blur-md transition-all duration-200 md:text-sm whitespace-nowrap">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>

            <div class="space-y-6 p-5 text-sm md:p-6">

                {{-- TAB PRIBADI --}}
                <div x-show="tab==='pribadi'" x-cloak>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-12">

                        {{-- FOTO --}}
                        <aside class="md:col-span-3">
                            <div
                                class="group rounded-[1.5rem] border border-slate-200/80 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-[2px] hover:border-blue-100 hover:shadow-[0_16px_36px_rgba(59,130,246,0.10)]">
                                <div class="flex justify-center">
                                    <div
                                        class="aspect-[3/4] w-26 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                        <img src="{{ $previewFoto }}" alt="Foto {{ $guru->nama ?? 'Guru' }}"
                                            class="h-full w-full object-cover object-top transition duration-300 hover:scale-[1.03]"
                                            onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="button" @click="openFoto = true"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-700 transition-all duration-200 hover:-translate-y-[1px] hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 10l4.553-4.553a1.5 1.5 0 012.121 2.121L17.12 12.12M15 10v8.25A2.25 2.25 0 0112.75 20.5h-8.5A2.25 2.25 0 012 18.25v-8.5A2.25 2.25 0 014.25 7.5H13" />
                                        </svg>
                                        <span>Preview Foto</span>
                                    </button>
                                </div>
                            </div>
                        </aside>

                        {{-- DATA --}}
                        <section class="md:col-span-9">
                            <div
                                class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                                <table class="w-full table-auto text-slate-800">
                                    <tbody class="divide-y divide-slate-100">

                                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                            <td
                                                class="w-1/3 bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                                Nama
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                                {{ $guru->nama ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                            <td
                                                class="bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                                NIP
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                                {{ $guru->nip ?: '-' }}
                                            </td>
                                        </tr>

                                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                            <td
                                                class="bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                                NUPTK
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                                {{ $guru->nuptk ?: '-' }}
                                            </td>
                                        </tr>

                                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                            <td
                                                class="bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                                Jenis Kelamin
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                                {{ $guru->jk === 'L' ? 'Laki-laki' : ($guru->jk === 'P' ? 'Perempuan' : '-') }}
                                            </td>
                                        </tr>

                                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                            <td
                                                class="bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                                TTL
                                            </td>
                                            <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                                {{ $guru->tempat_lahir ?: '-' }}, {{ $ttl }}
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- TAB KONTAK --}}
                <div x-show="tab==='kontak'" x-cloak>
                    <div
                        class="rounded-[1.5rem] border border-slate-200/80 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)] transition-all duration-300 hover:border-blue-100 hover:shadow-[0_16px_36px_rgba(59,130,246,0.08)]">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 12A4 4 0 118 12a4 4 0 018 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 12a8.25 8.25 0 1116.5 0 8.25 8.25 0 01-16.5 0z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-semibold text-slate-800">
                                Kontak & Alamat
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 gap-6 text-xs md:grid-cols-2 md:text-sm">
                            <div class="space-y-3">
                                <div class="flex items-start gap-2 rounded-xl px-2 py-2 transition-all duration-200 hover:bg-slate-50">
                                    <span class="mt-0.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 12A4 4 0 118 12a4 4 0 018 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 12a8.25 8.25 0 1116.5 0 8.25 8.25 0 01-16.5 0z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-slate-500">Email</div>
                                        <div class="font-medium text-slate-800 break-all">
                                            {{ $guru->email ?: '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 rounded-xl px-2 py-2 transition-all duration-200 hover:bg-slate-50">
                                    <span class="mt-0.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 4.5l4.5-1.5a1 1 0 011.09.36l2.1 2.8a1 1 0 01-.08 1.3L9.5 9.5a11 11 0 004.99 4.99l1.04-1.86a1 1 0 011.3-.08l2.8 2.1a1 1 0 01.36 1.09l-1.5 4.5a1 1 0 01-.95.68A15.25 15.25 0 013.07 5.45a1 1 0 01.68-.95z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-slate-500">No HP</div>
                                        <div class="font-medium text-slate-800">
                                            {{ $guru->no_hp ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-start gap-2 rounded-xl px-2 py-2 transition-all duration-200 hover:bg-slate-50">
                                    <span class="mt-0.5 text-emerald-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 10.5c0 7.142-7.5 10.5-7.5 10.5S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-slate-500">Alamat</div>
                                        <div class="font-medium leading-relaxed text-slate-800">
                                            {{ $guru->alamat ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB STATUS --}}
                <div x-show="tab==='status'" x-cloak>
                    <div
                        class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <table class="w-full table-auto text-slate-800">
                            <tbody class="divide-y divide-slate-100">

                                <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                    <td
                                        class="w-1/3 bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                        Status Kepegawaian
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                        {{ $guru->status_kepegawaian ?: '-' }}
                                    </td>
                                </tr>

                                <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                    <td
                                        class="bg-slate-50/80 px-4 py-3 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                        Status Keaktifan
                                    </td>
                                    <td class="px-4 py-3 transition-all duration-200">
                                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs
                                            {{ ($guru->status ?? '') === 'aktif'
                                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                                : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' }}">
                                            <span class="h-1.5 w-1.5 rounded-full
                                                {{ ($guru->status ?? '') === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400' }}">
                                            </span>
                                            <span>{{ ucfirst($guru->status ?? '-') }}</span>
                                        </span>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        {{-- POPUP FOTO --}}
        <div x-show="openFoto" x-cloak x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/45 px-4 py-6 backdrop-blur-sm"
            @keydown.escape.window="openFoto = false">

            <div @click.away="openFoto = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="relative w-full max-w-md overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.20)]">

                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-slate-800">
                            Preview Foto Guru
                        </h3>
                        <p class="mt-0.5 truncate text-sm text-slate-500">
                            {{ $guru->nama ?? '-' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ $previewFoto }}" download
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:-translate-y-[1px] hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm"
                            title="Download Foto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                            </svg>
                        </a>

                        <button type="button" @click="openFoto = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:-translate-y-[1px] hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm"
                            title="Tutup">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-center bg-slate-50 px-4 py-5">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ $previewFoto }}" alt="Foto {{ $guru->nama ?? 'Guru' }}"
                            class="max-h-[65vh] w-auto rounded-2xl object-contain shadow-sm transition duration-300 ease-out hover:scale-[1.035] hover:shadow-[0_14px_34px_rgba(15,23,42,0.14)]"
                            onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection