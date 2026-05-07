@extends('ortu.layout')
@section('title', 'Profil Siswa')

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $u = auth()->user();
        $siswa = isset($siswaApi) && is_array($siswaApi) ? $siswaApi : [];

        $pickText = function (...$values) {
            foreach ($values as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }

                if (is_numeric($value)) {
                    return (string) $value;
                }
            }

            return '-';
        };

        $extractText = function ($value, array $keys = ['nama', 'nama_rombel', 'label', 'status', 'keterangan']) {
            if (is_string($value)) {
                return trim($value) !== '' ? trim($value) : '-';
            }

            if (is_array($value)) {
                foreach ($keys as $key) {
                    if (!empty($value[$key]) && (is_string($value[$key]) || is_numeric($value[$key]))) {
                        return trim((string) $value[$key]);
                    }
                }
            }

            if (is_object($value)) {
                foreach ($keys as $key) {
                    if (!empty($value->{$key}) && (is_string($value->{$key}) || is_numeric($value->{$key}))) {
                        return trim((string) $value->{$key});
                    }
                }
            }

            return '-';
        };

        $namaSiswa = $pickText($siswa['nama'] ?? null, $u->name ?? null);
        $nis = $pickText($siswa['nis'] ?? null, $u->sia_user_id ?? null);
        $nisn = $pickText($siswa['nisn'] ?? null);
        $tempatLahir = $pickText($siswa['tempat_lahir'] ?? null);

        $tanggalLahirRaw = $siswa['tanggal_lahir'] ?? null;
        $tanggalLahir = '-';

        if (is_string($tanggalLahirRaw) && trim($tanggalLahirRaw) !== '') {
            try {
                $tanggalLahir = \Carbon\Carbon::parse($tanggalLahirRaw)
                    ->timezone('Asia/Jakarta')
                    ->translatedFormat('d F Y');
            } catch (\Throwable $e) {
                $tanggalLahir = $tanggalLahirRaw;
            }
        }

        $jkRaw = strtoupper(trim((string) ($siswa['jenis_kelamin'] ?? $siswa['jk'] ?? '')));
        $jenisKelamin = match ($jkRaw) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => $pickText($siswa['jenis_kelamin'] ?? $siswa['jk'] ?? null),
        };

        $agama = $pickText($siswa['agama'] ?? null);
        $statusSiswa = $extractText($siswa['status'] ?? null, ['status', 'label', 'keterangan']);

        $tahunMasuk = $pickText(
            isset($siswa['tahun_masuk']) ? (string) $siswa['tahun_masuk'] : null,
            isset($siswa['tahun_ajaran_masuk']) ? (string) $siswa['tahun_ajaran_masuk'] : null
        );

        $jalurPenerimaan = $pickText($siswa['jalur_penerimaan'] ?? null);
        $kebutuhanKhusus = $pickText($siswa['kebutuhan_khusus'] ?? null);
        $emailSiswa = $pickText($siswa['email'] ?? null);
        $noHpSiswa = $pickText($siswa['no_hp'] ?? null);
        $alamat = $pickText($siswa['alamat'] ?? null);

        $rombelAktif = '-';
        if (!empty($siswa['rombel_aktif'])) {
            $rombelAktif = $extractText($siswa['rombel_aktif'], ['nama_rombel', 'nama', 'label']);
            $tingkatRombel = $pickText($siswa['rombel_aktif']['tingkat'] ?? null, '');

            if (
                $tingkatRombel !== '' &&
                $tingkatRombel !== '-' &&
                $rombelAktif !== '-' &&
                !str_starts_with(strtoupper($rombelAktif), strtoupper($tingkatRombel))
            ) {
                $rombelAktif = $tingkatRombel . $rombelAktif;
            }
        } elseif (!empty($siswa['rombel'])) {
            $rombelAktif = $extractText($siswa['rombel'], ['nama_rombel', 'nama', 'label']);
        }

        $namaAyah = $extractText($siswa['nama_ayah'] ?? null, ['nama', 'value']);
        $nikAyah = $pickText($siswa['nik_ayah'] ?? null);
        $statusAyah = $pickText($siswa['status_ayah'] ?? null);
        $pekerjaanAyah = $pickText($siswa['pekerjaan_ayah'] ?? null);
        $pendidikanAyah = $pickText($siswa['pendidikan_ayah'] ?? null);
        $noHpAyah = $pickText($siswa['no_hp_ayah'] ?? null, $siswa['hp_ayah'] ?? null);
        $alamatAyah = $pickText($siswa['alamat_ayah'] ?? null);

        $namaIbu = $extractText($siswa['nama_ibu'] ?? null, ['nama', 'value']);
        $nikIbu = $pickText($siswa['nik_ibu'] ?? null);
        $statusIbu = $pickText($siswa['status_ibu'] ?? null);
        $pekerjaanIbu = $pickText($siswa['pekerjaan_ibu'] ?? null);
        $pendidikanIbu = $pickText($siswa['pendidikan_ibu'] ?? null);
        $noHpIbu = $pickText($siswa['no_hp_ibu'] ?? null, $siswa['hp_ibu'] ?? null);
        $alamatIbu = $pickText($siswa['alamat_ibu'] ?? null);

        $fotoVersion = md5((string) $nis . '|' . (string) ($siswa['foto'] ?? $siswa['foto_url'] ?? ''));
        $fotoSrc = route('ortu.profil.photo', ['v' => $fotoVersion]);
        $fotoTersedia = !empty($previewFoto);

        $defaultFoto = file_exists(public_path('images/avatar-default.png'))
            ? asset('images/avatar-default.png')
            : asset('images/logo-sma2.png');

        $infoTempatTanggalLahir = trim(($tempatLahir !== '-' ? $tempatLahir : '') . ($tanggalLahir !== '-' ? ', ' . $tanggalLahir : ''));
        $infoTempatTanggalLahir = $infoTempatTanggalLahir !== '' ? ltrim($infoTempatTanggalLahir, ', ') : '-';
    @endphp

    <div x-data="{ openPreview: false, activeTab: 'siswa' }" class="space-y-6">

        <section
            class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_14px_30px_rgba(59,130,246,0.28)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 6c-3.314 0-6 1.79-6 4v1h12v-1c0-2.21-2.686-4-6-4z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Profil Siswa
                            </div>

                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                                {{ $namaSiswa }}
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                Informasi identitas siswa dan data orang tua ditampilkan berdasarkan data yang tersinkron
                                dari SIA.
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    NIS {{ $nis }}
                                </span>

                                @if($nisn !== '-')
                                    <span
                                        class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
                                        NISN {{ $nisn }}
                                    </span>
                                @endif

                                <span
                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                    {{ $rombelAktif }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('ortu.dashboard') }}"
                        class="inline-flex w-fit items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>

            {{-- RINGKASAN --}}
            <div class="border-b border-slate-200/80 px-5 py-5 md:px-6 lg:px-7">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                                    Rombel Aktif
                                </div>
                                <div class="mt-3 text-2xl font-semibold leading-none text-slate-900">
                                    {{ $rombelAktif }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Kelas siswa saat ini.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 6c-3.314 0-6 1.79-6 4v1h12v-1c0-2.21-2.686-4-6-4z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                                    Jenis Kelamin
                                </div>
                                <div class="mt-3 text-2xl font-semibold leading-none text-slate-900">
                                    {{ $jenisKelamin }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Data identitas siswa.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19a7 7 0 1 0-6 0m6 0a9 9 0 1 1-6 0m6 0a8.962 8.962 0 0 1-6 0" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                                    Status
                                </div>
                                <div class="mt-3 text-2xl font-semibold leading-none text-slate-900">
                                    {{ ucfirst($statusSiswa) }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Status siswa di SIA.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group h-full rounded-[1.6rem] border border-violet-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(139,92,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_20px_48px_rgba(139,92,246,0.14)]">
                        <div class="flex h-full items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-700">
                                    Tahun Masuk
                                </div>
                                <div class="mt-3 text-2xl font-semibold leading-none text-slate-900">
                                    {{ $tahunMasuk }}
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    Awal terdaftar sebagai siswa.
                                </div>
                            </div>

                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-600 shadow-sm transition duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="p-5 md:p-6 lg:p-7">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-stretch">

                    {{-- FOTO --}}
                    <aside class="md:col-span-4 xl:col-span-3">
                        <div
                            class="group rounded-[1.5rem] border border-slate-200/80 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-[2px] hover:border-blue-100 hover:shadow-[0_16px_36px_rgba(59,130,246,0.10)]">

                            <div class="flex flex-col items-center">
                                <div
                                    class="aspect-[3/4] w-full max-w-[250px] overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50 shadow-sm transition duration-300 group-hover:shadow-md">
                                    <img src="{{ $fotoSrc }}" alt="Foto {{ $namaSiswa }}"
                                        class="h-full w-full object-cover object-top transition duration-300 group-hover:scale-[1.03]"
                                        loading="lazy" onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                                </div>

                                <div class="mt-5 w-full text-center">
                                    <h3 class="text-base font-semibold leading-tight text-slate-900">
                                        {{ $namaSiswa }}
                                    </h3>

                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        {{ $rombelAktif }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-medium text-blue-700">
                                            NIS: {{ $nis }}
                                        </span>

                                        @if($nisn !== '-')
                                            <span
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-600">
                                                NISN: {{ $nisn }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 px-5 py-4">
                                @if ($fotoTersedia)
                                    <button type="button" @click="openPreview = true"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-100 hover:shadow-md">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 10l4.553-4.553a1.5 1.5 0 0 1 2.121 2.121L17.12 12.12M15 10v8.25A2.25 2.25 0 0 1 12.75 20.5h-8.5A2.25 2.25 0 0 1 2 18.25v-8.5A2.25 2.25 0 0 1 4.25 7.5H13" />
                                        </svg>
                                        <span>Preview Foto</span>
                                    </button>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-medium text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5V8.25A2.25 2.25 0 0 1 5.25 6h3.379a1.5 1.5 0 0 0 1.06-.44l.621-.62A1.5 1.5 0 0 1 11.371 4.5h3.258a1.5 1.5 0 0 1 1.06.44l.621.62a1.5 1.5 0 0 0 1.06.44h3.38A2.25 2.25 0 0 1 23 8.25v8.25A2.25 2.25 0 0 1 20.75 18.75H5.25A2.25 2.25 0 0 1 3 16.5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
                                        </svg>
                                        <span>Foto Tidak Tersedia</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </aside>

                    {{-- DATA PROFIL --}}
                    <section class="lg:col-span-8 xl:col-span-9">
                        <div
                            class="h-full overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">

                            <div class="border-b border-slate-200 px-5 py-5">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold tracking-tight text-slate-900">
                                            Informasi Profil
                                        </h2>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Data dipisahkan antara identitas siswa dan informasi orang tua.
                                        </p>
                                    </div>

                                    <div
                                        class="inline-flex w-fit max-w-full flex-wrap items-center gap-1 rounded-full border border-slate-200 bg-slate-50 p-1 shadow-sm">
                                        <button type="button" @click="activeTab = 'siswa'"
                                            :class="activeTab === 'siswa'
                                                                                                ? 'bg-blue-600 text-white shadow-sm'
                                                                                                : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
                                            class="rounded-full px-4 py-2 text-sm font-medium transition">
                                            Data Siswa
                                        </button>

                                        <button type="button" @click="activeTab = 'ortu'"
                                            :class="activeTab === 'ortu'
                                                                                                ? 'bg-blue-600 text-white shadow-sm'
                                                                                                : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
                                            class="rounded-full px-4 py-2 text-sm font-medium transition">
                                            Data Orang Tua
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB DATA SISWA --}}
                            <div x-show="activeTab === 'siswa'" x-cloak>
                                <div class="overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full table-auto text-sm text-slate-800">
                                            <tbody class="divide-y divide-slate-100">
                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="w-[34%] bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Nama
                                                    </td>
                                                    <td class="px-5 py-3.5 font-medium text-slate-900">
                                                        {{ $namaSiswa }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        NIS
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $nis }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        NISN
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $nisn }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Jenis Kelamin
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $jenisKelamin }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Tempat, Tanggal Lahir
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $infoTempatTanggalLahir }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Agama
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $agama }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Tahun Masuk
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $tahunMasuk }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Rombel Aktif
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $rombelAktif }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Jalur Penerimaan
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $jalurPenerimaan }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Kebutuhan Khusus
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $kebutuhanKhusus }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Email
                                                    </td>
                                                    <td class="break-all px-5 py-3.5 text-slate-700">
                                                        {{ $emailSiswa }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        No. HP
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ $noHpSiswa }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Status
                                                    </td>
                                                    <td class="px-5 py-3.5 text-slate-700">
                                                        {{ ucfirst($statusSiswa) }}
                                                    </td>
                                                </tr>

                                                <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                    <td
                                                        class="bg-slate-50/80 px-5 py-3.5 align-top font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                        Alamat
                                                    </td>
                                                    <td class="px-5 py-3.5 leading-7 text-slate-700">
                                                        {{ $alamat }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB DATA ORANG TUA --}}
                            <div x-show="activeTab === 'ortu'" x-cloak>
                                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        Data Ayah
                                    </span>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full table-auto text-sm text-slate-800">
                                        <tbody class="divide-y divide-slate-100">
                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="w-[34%] bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Nama Ayah
                                                </td>
                                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                                    {{ $namaAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    NIK Ayah
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $nikAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Status Ayah
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $statusAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Pekerjaan Ayah
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $pekerjaanAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Pendidikan Ayah
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $pendidikanAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    No. HP Ayah
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $noHpAyah }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 align-top font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Alamat Ayah
                                                </td>
                                                <td class="px-5 py-3.5 leading-7 text-slate-700">
                                                    {{ $alamatAyah }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-y border-slate-100 bg-slate-50/70 px-5 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Data Ibu
                                    </span>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full table-auto text-sm text-slate-800">
                                        <tbody class="divide-y divide-slate-100">
                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="w-[34%] bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Nama Ibu
                                                </td>
                                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                                    {{ $namaIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    NIK Ibu
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $nikIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Status Ibu
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $statusIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Pekerjaan Ibu
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $pekerjaanIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Pendidikan Ibu
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $pendidikanIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    No. HP Ibu
                                                </td>
                                                <td class="px-5 py-3.5 text-slate-700">
                                                    {{ $noHpIbu }}
                                                </td>
                                            </tr>

                                            <tr class="group transition duration-200 hover:bg-blue-50/30">
                                                <td
                                                    class="bg-slate-50/80 px-5 py-3.5 align-top font-medium text-slate-600 transition group-hover:bg-blue-50/70 group-hover:text-blue-700">
                                                    Alamat Ibu
                                                </td>
                                                <td class="px-5 py-3.5 leading-7 text-slate-700">
                                                    {{ $alamatIbu }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        {{-- POPUP FOTO --}}
        <div x-show="openPreview" x-cloak x-transition.opacity
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 px-4 py-6 backdrop-blur-sm"
            @keydown.escape.window="openPreview = false">

            <div @click.away="openPreview = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="relative w-full max-w-md overflow-hidden rounded-[1.9rem] border border-white/80 bg-white shadow-[0_30px_90px_rgba(15,23,42,0.28)]">

                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <div class="text-[11px] font-medium uppercase tracking-[0.16em] text-blue-600">
                            Preview Foto
                        </div>
                        <h3 class="mt-1 truncate text-base font-semibold text-slate-900">
                            {{ $namaSiswa }}
                        </h3>
                        <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ $rombelAktif }}
                        </p>
                    </div>

                    <button type="button" @click="openPreview = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition duration-300 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                        title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center justify-center bg-slate-50 px-5 py-5">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ $fotoSrc }}" alt="Foto {{ $namaSiswa }}"
                            class="max-h-[65vh] w-auto rounded-[1.3rem] object-contain shadow-sm transition duration-300 hover:scale-[1.02]"
                            onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection