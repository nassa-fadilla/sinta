@extends('kepsek.layout')
@section('title', 'Profil Pengguna')

@section('content')
    @php
        use Illuminate\Support\Facades\Route as RouteFacade;

        $u = auth()->user();
        $guru = isset($guruApi) && is_array($guruApi) ? (object) $guruApi : null;

        $jabatan = 'Kepala Sekolah';

        $nama = $guru->nama ?? ($u->name ?? '-');
        $nuptk = $guru->nuptk ?? ($u->sia_user_id ?? '-');
        $nip = $guru->nip ?? '-';
        $email = $guru->email ?? ($u->email ?? '-');
        $jk = $guru->jk ?? '-';
        $tempatLahir = $guru->tempat_lahir ?? '-';
        $tanggalLahir = !empty($guru?->tanggal_lahir)
            ? \Carbon\Carbon::parse($guru->tanggal_lahir)->timezone('Asia/Jakarta')->translatedFormat('d F Y')
            : '-';
        $alamat = $guru->alamat ?? '-';
        $noHp = $guru->no_hp ?? '-';

        $defaultFoto = file_exists(public_path('images/default-user.png'))
            ? asset('images/default-user.png')
            : asset('images/default-siswa.png');

        $resolveKepsekFoto = function ($guru = null) use ($defaultFoto, $u) {
            $candidateValues = [
                data_get($guru, 'foto_url'),
                data_get($guru, 'photo_url'),
                data_get($guru, 'avatar'),
                data_get($guru, 'foto'),
                data_get($guru, 'photo'),
                data_get($guru, 'gambar'),
                data_get($guru, 'image'),
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
                    $version = md5(
                        (string) ($u?->sia_user_id ?? '') . '|' .
                        (string) ($u?->id ?? '') . '|' .
                        (string) ($u?->updated_at ?? '') . '|' .
                        (string) data_get($guru, 'updated_at', '') . '|' .
                        $foto
                    );

                    return $foto . (str_contains($foto, '?') ? '&' : '?') . 'v=' . $version;
                }

                $foto = str_replace('\\', '/', $foto);
                $foto = preg_replace('#/+#', '/', $foto);
                $foto = ltrim($foto, '/');

                $basename = basename($foto);

                $localCandidates = [
                    $foto,
                    'foto_guru/' . $basename,
                    'sia/' . $foto,
                    'sia/foto_guru/' . $basename,
                    'storage/' . $foto,
                    'storage/foto_guru/' . $basename,
                    'storage/sia/foto_guru/' . $basename,
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

                    if (str_starts_with($foto, 'foto_guru/')) {
                        return $siaPublicUrl . '/storage/' . $foto;
                    }

                    return $siaPublicUrl . '/storage/foto_guru/' . $basename;
                }
            }

            if (RouteFacade::has('kepsek.profil.photo')) {
                $fotoVersion = md5(
                    (string) ($u?->sia_user_id ?? '') . '|' .
                    (string) ($u?->id ?? '') . '|' .
                    (string) ($u?->updated_at ?? '')
                );

                return route('kepsek.profil.photo', ['v' => $fotoVersion]);
            }

            return $defaultFoto;
        };

        $fotoSrc = $resolveKepsekFoto($guru);

        $previewFoto = !empty($guru?->foto_url)
            || !empty($guru?->photo_url)
            || !empty($guru?->avatar)
            || !empty($guru?->foto)
            || !empty($guru?->photo)
            || RouteFacade::has('kepsek.profil.photo');
    @endphp

    <div x-data="{ openFoto: false }" class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-white/70 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.07)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-100 bg-white px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-3">
                        <span
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-[0_10px_24px_rgba(59,130,246,0.25)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0114 0" />
                            </svg>
                        </span>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                Profil Pengguna
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-800">
                                {{ $nama }}
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Informasi profil kepala sekolah dari data SIA.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('kepsek.dashboard') }}"
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
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12 items-stretch">

                    {{-- FOTO --}}
                    <aside class="md:col-span-3 h-full">
                        <div
                            class="group h-full rounded-[1.5rem] border border-slate-200/80 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-[2px] hover:border-blue-100 hover:shadow-[0_16px_36px_rgba(59,130,246,0.10)] flex flex-col">

                            <div class="flex-1 flex flex-col items-center justify-center">
                                <div
                                    class="aspect-[3/4] w-60 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition-all duration-300 group-hover:shadow-md">
                                    <img src="{{ $fotoSrc }}" alt="Foto {{ $nama }}"
                                        class="h-full w-full object-cover object-top transition duration-300 hover:scale-[1.03]"
                                        loading="lazy" onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                                </div>

                                <div class="mt-4 text-center">
                                    <h3 class="text-sm font-semibold text-slate-800">{{ $nama }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $jabatan }}</p>
                                </div>
                            </div>

                            <div class="mt-5">
                                @if ($previewFoto)
                                    <button type="button" @click="openFoto = true"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-[1px] hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 10l4.553-4.553a1.5 1.5 0 012.121 2.121L17.12 12.12M15 10v8.25A2.25 2.25 0 0112.75 20.5h-8.5A2.25 2.25 0 012 18.25v-8.5A2.25 2.25 0 014.25 7.5H13" />
                                        </svg>
                                        <span>Preview Foto</span>
                                    </button>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-3 py-3 text-sm font-medium text-slate-400 cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
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
                    </aside>

                    {{-- DATA PROFIL --}}
                    <section class="md:col-span-9 h-full">
                        <div
                            class="h-full overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <table class="w-full table-auto text-slate-800">
                                <tbody class="divide-y divide-slate-100">

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="w-1/3 bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Nama
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $nama }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Jabatan
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $jabatan }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            NUPTK
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $nuptk }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            NIP
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $nip }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Email
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $email }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Jenis Kelamin
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $jk }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Tempat, Tanggal Lahir
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $tempatLahir }}, {{ $tanggalLahir }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            No. HP
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $noHp }}
                                        </td>
                                    </tr>

                                    <tr class="group transition-all duration-200 hover:bg-blue-50/30">
                                        <td
                                            class="bg-slate-50/80 px-4 py-4 font-medium text-slate-600 transition-all duration-200 group-hover:bg-blue-50/60 group-hover:text-blue-700">
                                            Alamat
                                        </td>
                                        <td
                                            class="px-4 py-4 text-slate-700 leading-7 transition-all duration-200 group-hover:text-slate-800">
                                            {{ $alamat }}
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </section>
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
                            Preview Foto Profil
                        </h3>
                        <p class="mt-0.5 truncate text-sm text-slate-500">
                            {{ $nama }}
                        </p>
                    </div>

                    <button type="button" @click="openFoto = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:-translate-y-[1px] hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm"
                        title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center justify-center bg-slate-50 px-4 py-5">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                        <img src="{{ $fotoSrc }}" alt="Foto {{ $nama }}"
                            class="max-h-[65vh] w-auto rounded-2xl object-contain shadow-sm transition duration-300 ease-out hover:scale-[1.035] hover:shadow-[0_14px_34px_rgba(15,23,42,0.14)]"
                            onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection