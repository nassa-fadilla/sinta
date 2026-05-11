@extends('guru.layout')
@section('title', 'Monitoring Siswa')

@section('content')
    @php
        $siswaList = collect($siswaList ?? []);
        $rombel = $rombel ?? null;
        $guru = $guru ?? null;
        $role = $role ?? 'guru';

        $totalSiswa = $siswaList->count();
        $laki = $siswaList->where('jk', 'L')->count();
        $perempuan = $siswaList->where('jk', 'P')->count();

        $tahunAjaran = $siswaList->pluck('tahun_ajaran')->filter(fn($v) => filled($v) && $v !== '-')->first()
            ?? ($rombel->tahun_ajaran ?? '-');

        $namaRombel = $rombel->rombel_label ?? ($rombel->nama_rombel ?? '-');

        $roleLabel = match ($role) {
            'walkel' => 'Wali Kelas',
            'guru' => 'Guru',
            default => ucfirst($role ?: 'Guru'),
        };

        $namaGuru = $guru->nama ?? auth()->user()->name ?? '-';
        $nuptkGuru = $guru->nuptk ?? null;
        $nipGuru = $guru->nip ?? null;

        $resolveSiswaFoto = function ($siswa) {
            $candidateValues = [
                data_get($siswa, 'foto_src'),
                data_get($siswa, 'foto_url'),
                data_get($siswa, 'photo_url'),
                data_get($siswa, 'avatar'),
                data_get($siswa, 'foto'),
                data_get($siswa, 'foto_siswa'),
                data_get($siswa, 'photo'),
                data_get($siswa, 'gambar'),
                data_get($siswa, 'image'),
            ];

            foreach ($candidateValues as $value) {
                if (!is_scalar($value)) {
                    continue;
                }

                $foto = trim((string) $value);

                if ($foto === '' || $foto === '-') {
                    continue;
                }

                if (preg_match('/^https?:\/\//i', $foto)) {
                    return $foto;
                }

                $foto = str_replace('\\', '/', $foto);
                $foto = preg_replace('#/+#', '/', $foto);
                $foto = ltrim($foto, '/');

                $basename = basename($foto);

                $localCandidates = [
                    $foto,
                    'sia/' . $foto,
                    'foto_siswa/' . $basename,
                    'sia/foto_siswa/' . $basename,
                    'storage/' . $foto,
                    'storage/foto_siswa/' . $basename,
                    'storage/sia/foto_siswa/' . $basename,
                ];

                foreach (array_unique(array_filter($localCandidates)) as $relativePath) {
                    if (is_file(public_path($relativePath))) {
                        return asset($relativePath);
                    }
                }

                $siaPublicUrl = rtrim((string) (config('services.sia.public_url') ?: config('services.sia.base_url')), '/');

                if ($siaPublicUrl !== '') {
                    if (str_starts_with($foto, 'storage/')) {
                        return $siaPublicUrl . '/' . $foto;
                    }

                    if (str_starts_with($foto, 'foto_siswa/')) {
                        return $siaPublicUrl . '/storage/' . $foto;
                    }

                    return $siaPublicUrl . '/storage/foto_siswa/' . $basename;
                }
            }

            return null;
        };
    @endphp

    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5V4H2v16h5m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0H7" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Monitoring Siswa
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Pantau data siswa rombel binaan melalui integrasi data SIA.
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Login sebagai
                                <span class="font-medium text-slate-700">{{ $roleLabel }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 lg:pt-1">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Total Siswa: {{ $totalSiswa }}
                        </span>
                    </div>
                </div>
            </div>

            @if (!$rombel)
                <div class="px-5 py-14 text-center md:px-6">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v10H4zM8 20h8" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-slate-800">
                        Rombel binaan belum ditemukan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Data rombel untuk akun guru ini belum berhasil dihubungkan dari SIA.
                    </p>
                </div>
            @else
                {{-- INFO ROMBEL --}}
                <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                        <div
                            class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">
                                Wali Kelas
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $namaGuru }}</div>
                            @if ($nuptkGuru || $nipGuru)
                                <div class="mt-1 text-[11px] text-slate-500">
                                    @if ($nuptkGuru)
                                        NUPTK: {{ $nuptkGuru }}
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div
                            class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">
                                Rombel
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $namaRombel }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                {{ $roleLabel }}
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-cyan-700">
                                Tingkat
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $rombel->tingkat ?? '-' }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                Struktur kelas
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">
                                Ruang
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $rombel->ruang_kelas ?? '-' }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                Lokasi belajar
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
                                Tahun Ajaran
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $tahunAjaran }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                Periode aktif
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">
                                Kapasitas
                            </div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $rombel->kapasitas ?? '-' }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">
                                Daya tampung rombel
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STATISTIK --}}
                <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div
                            class="group relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/70 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="absolute -right-6 -top-6 h-16 w-16 rounded-full bg-white/40 opacity-60"></div>
                            <div class="relative z-10 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
                                        Jumlah Siswa
                                    </div>
                                    <div class="mt-1 text-3xl font-bold leading-none text-slate-800">
                                        {{ $totalSiswa }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500">
                                        Siswa aktif di rombel ini
                                    </div>
                                </div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-100 bg-white/80 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path d="M12 12a4 4 0 100-8 4 4 0 000 8z" />
                                        <path
                                            d="M2 20a7 7 0 0114 0v1H2v-1zM20 9a2 2 0 11-4 0 2 2 0 014 0zM22 20h-4v-1a4 4 0 013-3.87A5 5 0 0122 20z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            class="group relative overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-sky-100/70 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="absolute -right-6 -top-6 h-16 w-16 rounded-full bg-white/40 opacity-60"></div>
                            <div class="relative z-10 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                                        Siswa Laki-laki
                                    </div>
                                    <div class="mt-1 text-3xl font-bold leading-none text-slate-800">
                                        {{ $laki }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500">
                                        Kode gender: L
                                    </div>
                                </div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-sky-100 bg-white/80 text-sky-600 shadow-sm transition duration-300 group-hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path d="M16 3h5v5h-2V6.414l-3.293 3.293-1.414-1.414L17.586 5H16V3z" />
                                        <path d="M10 5a5 5 0 100 10 5 5 0 000-10zM4 20a6 6 0 1112 0v1H4v-1z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            class="group relative overflow-hidden rounded-2xl border border-pink-200 bg-gradient-to-br from-pink-50 to-pink-100/70 px-4 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="absolute -right-6 -top-6 h-16 w-16 rounded-full bg-white/40 opacity-60"></div>
                            <div class="relative z-10 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-pink-700">
                                        Siswa Perempuan
                                    </div>
                                    <div class="mt-1 text-3xl font-bold leading-none text-slate-800">
                                        {{ $perempuan }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-500">
                                        Kode gender: P
                                    </div>
                                </div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-pink-100 bg-white/80 text-pink-600 shadow-sm transition duration-300 group-hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path d="M12 4a5 5 0 100 10 5 5 0 000-10zM11 15v2H9v2h2v2h2v-2h2v-2h-2v-2h-2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLE HEADER --}}
                <div class="border-b border-slate-200 px-5 py-4 md:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-lg font-semibold text-slate-800">
                                Daftar Siswa Rombel {{ $namaRombel }}
                            </div>
                            <div class="mt-0.5 text-sm text-slate-500">
                                Total {{ $totalSiswa }} siswa terdaftar aktif
                                @if ($tahunAjaran && $tahunAjaran !== '-')
                                    • Tahun ajaran {{ $tahunAjaran }}
                                @endif
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Klik detail siswa untuk melihat profil, nilai, kehadiran, dan ekskul.
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                @if ($siswaList->isEmpty())
                    <div class="px-5 py-14 text-center md:px-6">
                        <div class="flex flex-col items-center justify-center gap-3 text-slate-500">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-6 4h6M5 5h14v14H5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Tidak ada data siswa.</p>
                                <p class="mt-1 text-xs text-slate-500">Belum ada siswa yang terdaftar pada rombel ini.</p>
                            </div>
                        </div>
                    </div>
                @else
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
                                        <th class="px-5 py-4">Tahun Ajaran</th>
                                        <th
                                            class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.18)]">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100/90 text-slate-700">
                                    @foreach ($siswaList as $index => $s)
                                        @php
                                            $nis = $s->nis ?? '-';
                                            $nisn = $s->nisn ?? '-';
                                            $nama = $s->nama ?? '-';
                                            $jkRaw = strtoupper((string) ($s->jk ?? $s->jenis_kelamin ?? ''));

                                            $labelJk = match ($jkRaw) {
                                                'L' => 'L',
                                                'P' => 'P',
                                                'LAKI-LAKI', 'LAKI', 'MALE', 'M' => 'L',
                                                'PEREMPUAN', 'WANITA', 'FEMALE', 'F' => 'P',
                                                default => '-',
                                            };

                                            $jkClass = match ($labelJk) {
                                                'L' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/80',
                                                'P' => 'bg-pink-50 text-pink-700 ring-1 ring-pink-200/80',
                                                default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                                            };

                                            $inisial = \Illuminate\Support\Str::of($nama)
                                                ->trim()
                                                ->explode(' ')
                                                ->map(fn($p) => mb_substr($p, 0, 1))
                                                ->take(2)
                                                ->implode('');

                                            if (trim((string) $inisial) === '') {
                                                $inisial = 'S';
                                            }

                                            $tahunRow = filled($s->tahun_ajaran ?? null) && ($s->tahun_ajaran ?? '-') !== '-'
                                                ? $s->tahun_ajaran
                                                : ($tahunAjaran ?? '-');

                                            $fotoThumb = $resolveSiswaFoto($s);
                                        @endphp

                                        <tr
                                            class="group transition duration-300 hover:bg-blue-50/40 hover:shadow-[inset_0_0_0_1px_rgba(191,219,254,0.45)]">
                                            <td class="px-4 py-4 text-center text-xs font-medium text-slate-500">
                                                {{ $index + 1 }}
                                            </td>

                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 group-hover:scale-[1.04] group-hover:border-blue-200 group-hover:shadow-md">
                                                        @if($fotoThumb)
                                                            <img src="{{ $fotoThumb }}" alt="Foto {{ $nama }}"
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
                                                            {{ $nama }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-5 py-4 font-medium text-slate-700">
                                                {{ $nis }}
                                            </td>

                                            <td class="px-5 py-4 text-slate-700">
                                                {{ $nisn }}
                                            </td>

                                            <td class="px-5 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $jkClass }}">
                                                    {{ $labelJk }}
                                                </span>
                                            </td>

                                            <td class="px-5 py-4 text-slate-700">
                                                {{ $tahunRow }}
                                            </td>

                                            <td
                                                class="sticky right-0 z-10 border-l border-slate-200 bg-white px-5 py-4 text-center shadow-[-8px_0_16px_-12px_rgba(15,23,42,0.14)] group-hover:bg-blue-50">
                                                <a href="{{ route('guru.monitoring.siswa.show', ['rombel' => $rombel->id, 'nis' => $nis]) }}"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                                                    <span>Detail</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection