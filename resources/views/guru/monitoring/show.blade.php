@extends('guru.layout')
@section('title', 'Detail Monitoring Siswa')

@section('content')
        @php
            use Illuminate\Support\Str;

            $s = is_array($siswa ?? null) ? $siswa : (array) ($siswa ?? []);
            $nilaiApi = collect($nilaiApi ?? []);
            $ekskulApi = collect($ekskulApi ?? []);
            $presensiGrouped = collect($presensiGrouped ?? []);

            $namaSiswa = $s['nama'] ?? ($s['nama_siswa'] ?? '-');
            $nis = $s['nis'] ?? '-';
            $nisn = $s['nisn'] ?? '-';
            $jk = $s['jk'] ?? ($s['jenis_kelamin'] ?? '-');
            $tempatLahir = $s['tempat_lahir'] ?? '-';

            $tanggalLahir = !empty($s['tanggal_lahir'] ?? null)
                ? \Carbon\Carbon::parse($s['tanggal_lahir'])->translatedFormat('d F Y')
                : '-';

            $alamat = $s['alamat'] ?? '-';
            $rombelNama = $s['rombel']
                ?? ($s['nama_rombel'] ?? ($rombel->nama_rombel ?? '-'));

            $tahunAjaran = $s['tahun_ajaran']
                ?? ($s['nama_tahun_ajaran'] ?? ($rombel->tahun_ajaran ?? '-'));

            $waliKelas = $s['wali_kelas']
                ?? ($s['nama_wali_kelas'] ?? ($rombel->wali_kelas ?? ($guru->nama ?? auth()->user()->name ?? '-')));

            // FIX ERROR: pastikan variabel foto selalu ada
            $defaultFoto = !empty($s['default_foto'] ?? null)
                ? $s['default_foto']
                : (file_exists(public_path('images/default-siswa.png'))
                    ? asset('images/default-siswa.png')
                    : asset('images/default-user.png'));

            $fotoSrc = $s['foto_src']
                ?? $s['preview_foto']
                ?? $defaultFoto;

            $previewFoto = !empty($fotoSrc) && $fotoSrc !== $defaultFoto;

            $nilaiPalettes = [
                [
                    'wrap' => 'border-blue-200',
                    'head' => 'from-blue-50 to-sky-50',
                    'icon' => 'bg-blue-500 text-white',
                    'badge' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'lm' => 'border-blue-100 bg-blue-50/70',
                    'lmBadge' => 'bg-white text-blue-700 border-blue-200',
                    'tpBadge' => 'bg-blue-100 text-blue-700',
                    'dot' => 'bg-blue-500',
                    'progress' => 'from-blue-500 to-sky-400',
                ],
                [
                    'wrap' => 'border-emerald-200',
                    'head' => 'from-emerald-50 to-teal-50',
                    'icon' => 'bg-emerald-500 text-white',
                    'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'lm' => 'border-emerald-100 bg-emerald-50/70',
                    'lmBadge' => 'bg-white text-emerald-700 border-emerald-200',
                    'tpBadge' => 'bg-emerald-100 text-emerald-700',
                    'dot' => 'bg-emerald-500',
                    'progress' => 'from-emerald-500 to-teal-400',
                ],
                [
                    'wrap' => 'border-fuchsia-200',
                    'head' => 'from-fuchsia-50 to-pink-50',
                    'icon' => 'bg-fuchsia-500 text-white',
                    'badge' => 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200',
                    'lm' => 'border-fuchsia-100 bg-fuchsia-50/70',
                    'lmBadge' => 'bg-white text-fuchsia-700 border-fuchsia-200',
                    'tpBadge' => 'bg-fuchsia-100 text-fuchsia-700',
                    'dot' => 'bg-fuchsia-500',
                    'progress' => 'from-fuchsia-500 to-pink-400',
                ],
                [
                    'wrap' => 'border-amber-200',
                    'head' => 'from-amber-50 to-yellow-50',
                    'icon' => 'bg-amber-500 text-white',
                    'badge' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'lm' => 'border-amber-100 bg-amber-50/70',
                    'lmBadge' => 'bg-white text-amber-700 border-amber-200',
                    'tpBadge' => 'bg-amber-100 text-amber-700',
                    'dot' => 'bg-amber-500',
                    'progress' => 'from-amber-500 to-yellow-400',
                ],
            ];

            $presensiPalettes = [
                [
                    'wrap' => 'border-blue-200',
                    'head' => 'from-blue-50 to-sky-50',
                    'icon' => 'bg-blue-500 text-white',
                ],
                [
                    'wrap' => 'border-emerald-200',
                    'head' => 'from-emerald-50 to-teal-50',
                    'icon' => 'bg-emerald-500 text-white',
                ],
                [
                    'wrap' => 'border-fuchsia-200',
                    'head' => 'from-fuchsia-50 to-pink-50',
                    'icon' => 'bg-fuchsia-500 text-white',
                ],
                [
                    'wrap' => 'border-amber-200',
                    'head' => 'from-amber-50 to-yellow-50',
                    'icon' => 'bg-amber-500 text-white',
                ],
            ];

            $ekskulPalettes = [
                [
                    'outer' => 'border-blue-200 bg-gradient-to-br from-blue-50 to-sky-100/70',
                    'iconWrap' => 'border-blue-100 bg-white/85 text-blue-600',
                    'chip' => 'border-blue-200 bg-blue-100 text-blue-700',
                ],
                [
                    'outer' => 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-100/70',
                    'iconWrap' => 'border-emerald-100 bg-white/85 text-emerald-600',
                    'chip' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
                ],
                [
                    'outer' => 'border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-pink-100/70',
                    'iconWrap' => 'border-fuchsia-100 bg-white/85 text-fuchsia-600',
                    'chip' => 'border-fuchsia-200 bg-fuchsia-100 text-fuchsia-700',
                ],
                [
                    'outer' => 'border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-100/70',
                    'iconWrap' => 'border-amber-100 bg-white/85 text-amber-600',
                    'chip' => 'border-amber-200 bg-amber-100 text-amber-700',
                ],
            ];

            $getPredikatNilai = function ($nilai) {
                if (!is_numeric($nilai)) {
                    return '-';
                }

                $nilai = (float) $nilai;

                return match (true) {
                    $nilai >= 90 => 'Sangat Baik',
                    $nilai >= 80 => 'Baik',
                    $nilai >= 70 => 'Cukup',
                    $nilai >= 60 => 'Kurang',
                    default => 'Perlu Bimbingan',
                };
            };

            $getPredikatBadge = function ($nilai) {
                if (!is_numeric($nilai)) {
                    return 'bg-slate-100 text-slate-600 border-slate-200';
                }

                $nilai = (float) $nilai;

                return match (true) {
                    $nilai >= 90 => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    $nilai >= 80 => 'bg-blue-100 text-blue-700 border-blue-200',
                    $nilai >= 70 => 'bg-amber-100 text-amber-700 border-amber-200',
                    $nilai >= 60 => 'bg-orange-100 text-orange-700 border-orange-200',
                    default => 'bg-rose-100 text-rose-700 border-rose-200',
                };
            };

            $getEkskulIcon = function ($nama) {
                $nama = strtolower(trim((string) $nama));

                if (str_contains($nama, 'voli') || str_contains($nama, 'volley')) {
                    return 'voli';
                }

                if (str_contains($nama, 'batik')) {
                    return 'batik';
                }

                if (str_contains($nama, 'bulu tangkis') || str_contains($nama, 'badminton')) {
                    return 'badminton';
                }

                return 'default';
            };

            $statusSiswa = strtolower((string) ($s['status'] ?? 'aktif'));
            $statusBadge = match ($statusSiswa) {
                'aktif' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'lulus' => 'border-blue-200 bg-blue-50 text-blue-700',
                'pindah' => 'border-amber-200 bg-amber-50 text-amber-700',
                'keluar' => 'border-rose-200 bg-rose-50 text-rose-700',
                default => 'border-slate-200 bg-slate-50 text-slate-700',
            };
            $statusDot = match ($statusSiswa) {
                'aktif' => 'bg-emerald-500',
                'lulus' => 'bg-blue-500',
                'pindah' => 'bg-amber-500',
                'keluar' => 'bg-rose-500',
                default => 'bg-slate-400',
            };
        @endphp

    <div x-data="{ tab: 'nilai', profileTab: 'siswa', previewOpen: false }" class="space-y-6">
                <section class="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">
                <div class="border-b border-slate-100 bg-white px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-3">
                            <span
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0114 0" />
                                </svg>
                            </span>

                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                    Monitoring Siswa
                                </p>
                                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-800">
                                    {{ $namaSiswa }}
                                </h1>
                                <p class="mt-1 text-sm text-slate-500">
                                    Detail profil dan aktivitas akademik siswa terintegrasi dari SIA.
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('guru.monitoring.index') }}"
                            class="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.9">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Kembali</span>
                        </a>
                    </div>
                </div>

                <div class="p-5 md:p-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <aside class="lg:col-span-4">
                            <div
                                class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-[2px] hover:border-blue-100 hover:shadow-[0_16px_36px_rgba(59,130,246,0.10)]">
                                <div class="flex flex-col items-center text-center">
                                    <button type="button" @click="previewOpen = true"
                                        class="group relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50 shadow-sm transition-all duration-300 hover:shadow-lg focus:outline-none">
                                        <div class="aspect-[3/4] w-44 overflow-hidden md:w-48">
                                            <img src="{{ $fotoSrc }}" alt="Foto {{ $namaSiswa }}"
                                                class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-[1.045]"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.src='{{ $defaultFoto }}';" />
                                        </div>

                                        <div
                                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/60 via-slate-900/20 to-transparent px-4 py-3 text-left opacity-0 transition duration-300 group-hover:opacity-100">
                                            <span
                                                class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 10l4.553-4.553a1.5 1.5 0 012.121 2.121L17.12 12.12M15 10v8.25A2.25 2.25 0 0112.75 20.5h-8.5A2.25 2.25 0 012 18.25v-8.5A2.25 2.25 0 014.25 7.5H13" />
                                                </svg>
                                                Klik untuk preview
                                            </span>
                                        </div>
                                    </button>

                                    <h2 class="mt-4 text-xl font-semibold text-slate-800">{{ $namaSiswa }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">Siswa</p>

                                    <div class="mt-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold {{ $statusBadge }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                            <span>{{ ucfirst($s['status'] ?? 'aktif') }}</span>
                                        </span>
                                    </div>

                                    <div class="mt-4 space-y-1 text-sm text-slate-500">
                                        <div>NIS: {{ $nis }}</div>
                                        <div>NISN: {{ $nisn }}</div>
                                        <div>Rombel: {{ $rombelNama }}</div>
                                    </div>

                                    <div class="mt-5 w-full">
                                        @if($previewFoto)
                                            <button type="button" @click="previewOpen = true"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-[1px] hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 10l4.553-4.553a1.5 1.5 0 012.121 2.121L17.12 12.12M15 10v8.25A2.25 2.25 0 0112.75 20.5h-8.5A2.25 2.25 0 012 18.25v-8.5A2.25 2.25 0 014.25 7.5H13" />
                                                </svg>
                                                <span>Preview Foto</span>
                                            </button>
                                        @else
                                            <button type="button" disabled
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-3 py-3 text-sm font-medium text-slate-400 cursor-not-allowed">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 16.5V8.25A2.25 2.25 0 015.25 6h3.379a1.5 1.5 0 001.06-.44l.621-.62A1.5 1.5 0 0111.371 4.5h3.258a1.5 1.5 0 011.06.44l.621.62a1.5 1.5 0 001.06.44h3.38A2.25 2.25 0 0123 8.25v8.25A2.25 2.25 0 0120.75 18.75H5.25A2.25 2.25 0 013 16.5z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                                </svg>
                                                <span>Foto Tidak Tersedia</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <section class="lg:col-span-8">
        <div
            class="h-full overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- HEADER + TAB --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-800">Informasi Profil</h3>

                <div
                    class="inline-flex rounded-full border border-slate-200 bg-white p-0.5 text-xs font-medium text-slate-600 shadow-sm">
                    <button type="button" @click="profileTab = 'siswa'"
                        :class="profileTab === 'siswa' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600'"
                        class="rounded-full px-3 py-1.5 transition">
                        Data Siswa
                    </button>
                    <button type="button" @click="profileTab = 'ortu'"
                        :class="profileTab === 'ortu' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600'"
                        class="rounded-full px-3 py-1.5 transition">
                        Data Orang Tua
                    </button>
                </div>
            </div>

            {{-- TAB DATA SISWA --}}
            <div x-show="profileTab === 'siswa'">
                <table class="w-full table-auto text-slate-800">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="w-[38%] bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Nama
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $namaSiswa }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                NIS / NISN
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $nis }} / {{ $nisn }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Jenis Kelamin
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $jk }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Tempat, Tanggal Lahir
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $tempatLahir }}, {{ $tanggalLahir }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Rombel
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $rombelNama }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Tahun Ajaran
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $tahunAjaran }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Wali Kelas
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $waliKelas }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Alamat
                            </td>
                            <td class="px-4 py-4 leading-7 text-slate-700">{{ $alamat }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- TAB DATA ORANG TUA --}}
            <div x-show="profileTab === 'ortu'" x-cloak>
                <table class="w-full table-auto text-slate-800">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="w-[38%] bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Nama Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['nama_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                NIK Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['nik_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                No. HP Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['no_hp_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Pendidikan Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['pendidikan_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Pekerjaan Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['pekerjaan_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Status Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['status_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Alamat Ayah
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['alamat_ayah'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Nama Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['nama_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                NIK Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['nik_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                No. HP Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['no_hp_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Pendidikan Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['pendidikan_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Pekerjaan Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['pekerjaan_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Status Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['status_ibu'] ?? '-' }}</td>
                        </tr>

                        <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                            <td
                                class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                Alamat Ibu
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $s['alamat_ibu'] ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">
                <div class="border-b border-slate-100 bg-white px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Detail Akademik</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Data diambil dari sistem akademik (SIA) untuk siswa ini.
                            </p>
                        </div>

                        <div class="inline-flex rounded-full border border-slate-200 bg-slate-50 p-1 shadow-sm">
                            <button type="button" @click="tab = 'nilai'"
                                :class="tab === 'nilai' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-blue-700'"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition">
                                Nilai
                            </button>
                            <button type="button" @click="tab = 'presensi'"
                                :class="tab === 'presensi' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-blue-700'"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition">
                                Kehadiran
                            </button>
                            <button type="button" @click="tab = 'ekskul'"
                                :class="tab === 'ekskul' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-blue-700'"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition">
                                Ekskul
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'nilai'" x-cloak class="p-5">
                    @if($nilaiApi->isEmpty())
                        <p class="text-sm italic text-slate-500">Belum ada data nilai dari SIA.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($nilaiApi as $index => $n)
                                @php
                                    $palette = $nilaiPalettes[$index % count($nilaiPalettes)];
                                    $nilaiAkhir = $n['nilai_akhir'] ?? null;
                                    $predikat = $getPredikatNilai($nilaiAkhir);
                                    $predikatBadge = $getPredikatBadge($nilaiAkhir);
                                    $progressWidth = is_numeric($nilaiAkhir) ? min(max((float) $nilaiAkhir, 0), 100) : 0;
                                @endphp

                                <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }"
                                    class="overflow-hidden rounded-[1.35rem] border {{ $palette['wrap'] }} bg-white shadow-sm transition duration-300 hover:shadow-md">
                                    <button type="button" @click="open = !open"
                                        class="w-full bg-gradient-to-r {{ $palette['head'] }} px-4 py-4 text-left">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="flex items-start gap-3">
                                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $palette['icon'] }} shadow-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 17v-6m3 6V7m3 10v-4m3 4V5M4 19h16" />
                                                    </svg>
                                                </div>

                                                <div class="min-w-0">
                                                    <h3 class="text-base font-semibold text-slate-800">
                                                        {{ $n['mapel'] ?? '-' }}
                                                    </h3>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                        <span>Status: {{ ucfirst(str_replace('_', ' ', $n['status'] ?? '-')) }}</span>
                                                        <span>•</span>
                                                        <span>Penilaian: {{ ucfirst($n['status_penilaian'] ?? '-') }}</span>
                                                    </div>

                                                    <div class="mt-3 max-w-md">
                                                        <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                                            <span>Capaian nilai</span>
                                                            <span>{{ is_numeric($nilaiAkhir) ? $nilaiAkhir : '-' }}/100</span>
                                                        </div>
                                                        <div class="h-2.5 overflow-hidden rounded-full bg-white/80 shadow-inner">
                                                            <div class="h-full rounded-full bg-gradient-to-r {{ $palette['progress'] }}"
                                                                style="width: {{ $progressWidth }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <div class="text-right">
                                                    <div class="text-[11px] font-medium text-slate-500">Nilai Akhir</div>
                                                    <div class="mt-1 text-2xl font-bold leading-none text-slate-800">
                                                        {{ $nilaiAkhir ?? '-' }}
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-semibold {{ $predikatBadge }}">
                                                            {{ $predikat }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 text-slate-400 transition-transform duration-200"
                                                    :class="open ? 'rotate-180 text-slate-700' : ''" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </button>

                                    <div x-show="open" x-collapse class="p-4">
                                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                            @foreach([
                                                    ['label' => 'LM 1', 'nilai' => $n['lm1'] ?? '-', 'detail' => $n['lm1_detail'] ?? []],
                                                    ['label' => 'LM 2', 'nilai' => $n['lm2'] ?? '-', 'detail' => $n['lm2_detail'] ?? []],
                                                    ['label' => 'LM 3', 'nilai' => $n['lm3'] ?? '-', 'detail' => $n['lm3_detail'] ?? []],
                                                    ['label' => 'LM 4', 'nilai' => $n['lm4'] ?? '-', 'detail' => $n['lm4_detail'] ?? []],
                                                ] as $lm)
                                                    <div class="rounded-2xl border {{ $palette['lm'] }} p-4">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="h-2.5 w-2.5 rounded-full {{ $palette['dot'] }}"></span>
                                                                <h4 class="text-sm font-semibold text-slate-800">{{ $lm['label'] }}</h4>
                                                            </div>

                                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $palette['lmBadge'] }}">
                                                                {{ $lm['nilai'] }}
                                                            </span>
                                                        </div>

                                                        <div class="mt-3 space-y-2">
                                                            @forelse(($lm['detail'] ?? []) as $tp)
                                                                <div class="flex items-center justify-between rounded-xl border border-white/80 bg-white/90 px-3 py-2 shadow-sm">
                                                                    <div class="flex min-w-0 items-center gap-2">
                                                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $palette['tpBadge'] }} text-[11px] font-bold">
                                                                            TP
                                                                        </span>
                                                                        <span class="truncate text-sm text-slate-700">
                                                                            {{ $tp['label'] ?? '-' }}
                                                                        </span>
                                                                    </div>

                                                                    <span class="ml-3 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                                        {{ $tp['nilai'] ?? '-' }}
                                                                    </span>
                                                                </div>
                                                            @empty
                                                                <div class="rounded-xl border border-dashed border-slate-200 bg-white/80 px-3 py-3 text-center text-xs italic text-slate-400">
                                                                    Belum ada detail TP
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'presensi'" x-cloak class="p-5">
                    @if($presensiGrouped->isEmpty())
                        <p class="text-sm italic text-slate-500">Tidak ada data presensi yang tersedia dari SIA.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($presensiGrouped as $idx => $group)
                                @php
                                    $palette = $presensiPalettes[$idx % count($presensiPalettes)];
                                    $items = collect($group['items'] ?? []);
                                    $itemsAsc = $items->sortBy(fn($p) => strtotime((string) ($p['dipindai_pada'] ?? $p['tanggal'] ?? '')))->values();
                                    $itemsDesc = $itemsAsc->reverse()->values();
                                @endphp

                                <div x-data="{ open: {{ $idx === 0 ? 'true' : 'false' }}, sortAsc: false }"
                                    class="overflow-hidden rounded-[1.35rem] border {{ $palette['wrap'] }} bg-white shadow-sm transition duration-300 hover:shadow-md">
                                    <button type="button" @click="open = !open"
                                        class="w-full bg-gradient-to-r {{ $palette['head'] }} px-5 py-4 text-left">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-start gap-3">
                                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $palette['icon'] }} shadow-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                                    </svg>
                                                </div>

                                                <div>
                                                    <div class="text-base font-semibold text-slate-800">
                                                        {{ $group['mapel'] ?? 'Mapel tidak diketahui' }}
                                                    </div>
                                                    <div class="mt-1 text-sm text-slate-500">
                                                        {{ count($group['items'] ?? []) }} data presensi
                                                    </div>
                                                </div>
                                            </div>

                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5 text-slate-400 transition-transform duration-200"
                                                :class="open ? 'rotate-180 text-slate-700' : ''" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>

                                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3">
                                        <table class="min-w-full text-sm">
                                            <thead class="text-[11px] uppercase tracking-wide text-slate-500">
                                                <tr class="border-b border-slate-100">
                                                    <th class="px-4 py-3 text-left font-semibold">
                                                        <button type="button" @click.stop="sortAsc = !sortAsc"
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-1 py-1 transition hover:text-blue-600">
                                                            <span>Tanggal / Waktu</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-3.5 w-3.5 transition-transform"
                                                                :class="sortAsc ? 'rotate-180' : ''" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M8 7h8M6 12h12M10 17h4" />
                                                            </svg>
                                                        </button>
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-semibold">Mapel</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-slate-100" x-show="!sortAsc">
                                                @foreach($itemsDesc as $p)
                                                    @php
                                                        $status = strtolower((string) ($p['status'] ?? ''));
                                                        $badgeClass = 'bg-slate-50 text-slate-700 border-slate-200';

                                                        if ($status === 'hadir') {
                                                            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                        } elseif (in_array($status, ['izin', 'sakit'])) {
                                                            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                                        } elseif (in_array($status, ['alpa', 'alfa'])) {
                                                            $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                                        }
                                                    @endphp
                                                    <tr class="transition hover:bg-slate-50/70">
                                                        <td class="px-4 py-3 text-slate-700">
                                                            {{ $p['dipindai_pada'] ?? ($p['tanggal'] ?? '-') }}
                                                        </td>
                                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $p['mapel'] ?? '-' }}</td>
                                                        <td class="px-4 py-3">
                                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $badgeClass }}">
                                                                {{ strtoupper($p['status'] ?? '-') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>

                                            <tbody class="divide-y divide-slate-100" x-show="sortAsc" x-cloak>
                                                @foreach($itemsAsc as $p)
                                                    @php
                                                        $status = strtolower((string) ($p['status'] ?? ''));
                                                        $badgeClass = 'bg-slate-50 text-slate-700 border-slate-200';

                                                        if ($status === 'hadir') {
                                                            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                        } elseif (in_array($status, ['izin', 'sakit'])) {
                                                            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                                        } elseif (in_array($status, ['alpa', 'alfa'])) {
                                                            $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                                        }
                                                    @endphp
                                                    <tr class="transition hover:bg-slate-50/70">
                                                        <td class="px-4 py-3 text-slate-700">
                                                            {{ $p['dipindai_pada'] ?? ($p['tanggal'] ?? '-') }}
                                                        </td>
                                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $p['mapel'] ?? '-' }}</td>
                                                        <td class="px-4 py-3">
                                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $badgeClass }}">
                                                                {{ strtoupper($p['status'] ?? '-') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'ekskul'" x-cloak class="p-5">
                    @if($ekskulApi->isEmpty())
                        <p class="text-sm italic text-slate-500">Belum ada data ekstrakurikuler dari SIA.</p>
                    @else
                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                            @foreach($ekskulApi as $index => $e)
                                @php
                                    $palette = $ekskulPalettes[$index % count($ekskulPalettes)];
                                    $iconType = $getEkskulIcon($e['nama'] ?? '');
                                @endphp

                                <div class="group rounded-[1.5rem] border {{ $palette['outer'] }} p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-[2px] hover:shadow-[0_18px_36px_rgba(15,23,42,0.10)]">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-xl font-semibold text-slate-800">
                                                {{ $e['nama'] ?? '-' }}
                                            </h3>
                                            <p class="mt-2 text-sm text-slate-500">
                                                {{ $e['hari'] ?? '-' }} • {{ $e['jam'] ?? ($e['waktu'] ?? '-') }}
                                            </p>
                                        </div>

                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border shadow-sm {{ $palette['iconWrap'] }}">
                                            @if($iconType === 'voli')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <circle cx="12" cy="12" r="7.5"></circle>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M7 8.5c2 1 3.5 2.5 4.5 4.5M16.5 7c-.7 2.3-2.2 4.2-4.5 5.5M9 18c1.8-1.1 4-1.6 6.5-1.5" />
                                                </svg>
                                            @elseif($iconType === 'batik')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M7 16c1.5-1.5 3.5-2 5-1 1.5 1 3.5.5 5-1M6 8c1.2 1.2 2.8 1.8 4.2 1.5M11 6c.8 1.4 2.1 2.5 3.8 3" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 19l5-12 9 9" />
                                                </svg>
                                            @elseif($iconType === 'badminton')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l5 5M12 7l5 5M5 19l6-6" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 20l3-1 10-10-2-2L5 17l-1 3z" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M11 17a1 1 0 002 0v-5a1 1 0 00-.553-.894l-4-2A1 1 0 007 10v3.382a1 1 0 00.553.894l4 2z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 9.5V14a2 2 0 01-.553 1.382L13 19" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if(!empty($e['pembina']))
                                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $palette['chip'] }}">
                                                Pembina: {{ $e['pembina'] }}
                                            </span>
                                        @endif

                                        @if(!empty($e['lokasi']))
                                            <span class="inline-flex items-center rounded-full border border-white/80 bg-white/75 px-3 py-1 text-xs font-semibold text-slate-600">
                                                Lokasi: {{ $e['lokasi'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <div x-show="previewOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/45 px-4 py-6 backdrop-blur-sm"
                @keydown.escape.window="previewOpen = false">

                <div @click.away="previewOpen = false" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                    class="relative w-full max-w-md overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.20)]">

                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-semibold text-slate-800">
                                Preview Foto Siswa
                            </h3>
                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                {{ $namaSiswa }}
                            </p>
                        </div>

                        <button type="button" @click="previewOpen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:-translate-y-[1px] hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm"
                            title="Tutup">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-center bg-slate-50 px-4 py-5">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                            <img src="{{ $fotoSrc }}" alt="Foto {{ $namaSiswa }}"
                                class="max-h-[65vh] w-auto rounded-2xl object-contain shadow-sm transition duration-300 ease-out hover:scale-[1.035] hover:shadow-[0_14px_34px_rgba(15,23,42,0.14)]"
                                onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection