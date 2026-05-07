@php
    use Illuminate\Support\Facades\Route as RouteFacade;
    use Illuminate\Support\Str;

    $isActive = fn($pattern) => request()->routeIs($pattern);
    $hrefOf = fn($name) => (RouteFacade::has($name) ? route($name) : '#');

    $user = auth()->user();

    /*
    |--------------------------------------------------------------------------
    | 1. Data dari controller jika tersedia
    |--------------------------------------------------------------------------
    */
    $sidebarData = is_array($sidebarSiswa ?? null) ? $sidebarSiswa : null;

    /*
    |--------------------------------------------------------------------------
    | 2. Fallback: ambil langsung dari API SIA via NIS user login
    |--------------------------------------------------------------------------
    | Catatan:
    | - Ini tetap melalui SiaClient/API SIA.
    | - Tidak mengambil dari database SIA lokal.
    | - Data foto tidak dibaca langsung di blade, karena pencocokan path foto
    |   tetap ditangani oleh route ortu.profil.photo.
    */
    $siswaApi = null;

    if (!empty($user?->sia_user_id)) {
        try {
            /** @var \App\Services\SiaClient $sia */
            $sia = app(\App\Services\SiaClient::class);
            $resp = $sia->getSiswaByNis($user->sia_user_id);

            if (
                is_array($resp) &&
                (($resp['success'] ?? false) === true ||
                    ($resp['status'] ?? false) === true ||
                    ($resp['status'] ?? null) === 'success') &&
                !empty($resp['data']) &&
                is_array($resp['data'])
            ) {
                $siswaApi = $resp['data'];
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Normalisasi data siswa sidebar
    |--------------------------------------------------------------------------
    */
    $namaSiswa = $sidebarData['nama']
        ?? data_get($siswaApi, 'nama')
        ?? 'Siswa';

    $nis = $sidebarData['nis']
        ?? data_get($siswaApi, 'nis')
        ?? ($user->sia_user_id ?? '-');

    $nisn = $sidebarData['nisn']
        ?? data_get($siswaApi, 'nisn')
        ?? '-';

    $namaKelas = $sidebarData['kelas']
        ?? data_get($siswaApi, 'rombel_aktif.nama_rombel')
        ?? data_get($siswaApi, 'rombel.nama_rombel')
        ?? data_get($siswaApi, 'rombel_nama')
        ?? '-';

    /*
    |--------------------------------------------------------------------------
    | 4. Foto siswa
    |--------------------------------------------------------------------------
    | Foto tetap lewat route ortu.profil.photo.
    | Controller photo() yang bertugas mencocokkan nama file dari API/DB SIA
    | dengan file fisik di public/sia/foto_siswa.
    |
    | Query v/nis ditambahkan untuk mencegah browser menampilkan foto lama
    | dari cache saat akun/nis berganti.
    */
    $defaultFoto = file_exists(public_path('images/avatar-default.png'))
        ? asset('images/avatar-default.png')
        : asset('images/logo-sma2.png');

    $fotoVersion = md5((string) $nis . '|' . (string) ($user?->id ?? ''));

    $fotoPath = RouteFacade::has('ortu.profil.photo')
        ? route('ortu.profil.photo', ['v' => $fotoVersion, 'nis' => $nis])
        : $defaultFoto;

    /*
    |--------------------------------------------------------------------------
    | 5. State menu aktif
    |--------------------------------------------------------------------------
    */
    $dashboardActive = $isActive('ortu.dashboard');
    $akademikActive = request()->routeIs('ortu.jadwal.*', 'ortu.nilai.*', 'ortu.kehadiran.*');
    $ekskulActive = $isActive('ortu.ekskul.*');
    $chatActive = $isActive('ortu.chat.*');
    $pengumumanActive = $isActive('ortu.pengumuman.*');
    $aspirasiActive = request()->routeIs('ortu.aspirasi.*');
@endphp

<aside id="sidebar"
    class="fixed top-16 left-0 z-30 h-[calc(100vh-4rem)] w-64 -translate-x-full border-r border-slate-200 bg-white shadow-sm transition-transform duration-300 md:translate-x-0">
    <div class="flex h-full flex-col bg-white">

        {{-- Brand / Profil --}}
        <div class="border-b border-slate-100 bg-white px-4 py-5">
            <div class="flex flex-col items-center text-center">
                <div
                    class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-[1.75rem] border-2 border-sky-500 bg-white shadow-[0_10px_24px_rgba(59,130,246,0.12)]">
                    <img src="{{ $fotoPath }}" alt="Foto {{ $namaSiswa }}" class="h-full w-full object-cover"
                        loading="lazy" onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                </div>

                <div class="mt-3 min-w-0">
                    <div class="truncate text-[11px] font-semibold tracking-[0.28em] text-slate-800">
                        ORANG TUA
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        SMA Negeri 2 Temanggung
                    </div>
                    <div class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-800">
                        {{ $namaSiswa }}
                    </div>

                    <div class="mt-2 space-y-0.5">
                        <div class="text-[11px] text-slate-500">
                            NIS: {{ $nis }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            NISN: {{ $nisn }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Kelas: {{ $namaKelas }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 overflow-y-auto bg-white px-3 py-3">
            <ul class="space-y-1">

                {{-- Dashboard --}}
                <li>
                    <a href="{{ $hrefOf('ortu.dashboard') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $dashboardActive ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $dashboardActive ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10.5 12 3l9 7.5M5.25 9.75V20.25a.75.75 0 0 0 .75.75h4.5v-6h3v6H18a.75.75 0 0 0 .75-.75V9.75" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                </li>

                {{-- Akademik --}}
                @php $persistKeyAkademik = 'ortu_sidebar_open_akademik'; @endphp
                <li x-data="{
                        open: {{ $akademikActive ? 'true' : 'false' }},
                        toggle() {
                            this.open = !this.open;
                            try { localStorage.setItem('{{ $persistKeyAkademik }}', this.open ? '1' : '0'); } catch(e) {}
                        }
                    }" x-init="(() => {
                        try {
                            const v = localStorage.getItem('{{ $persistKeyAkademik }}');
                            if (v === '1' || v === '0') open = (v === '1');
                            @if ($akademikActive) open = true; @endif
                        } catch(e) {}
                    })()">
                    <button type="button" @click="toggle()"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-slate-700 transition hover:bg-slate-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $akademikActive ? 'bg-slate-100 text-blue-600' : 'text-slate-500' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 6.75h16M4 12h10M4 17.25h7" />
                                </svg>
                            </span>
                            <span class="truncate text-sm font-medium">Akademik</span>
                        </div>

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                            :class="open ? 'rotate-180 text-blue-600' : ''" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.855a.75.75 0 111.08 1.04l-4.24 4.405a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" />
                        </svg>
                    </button>

                    <ul class="ml-11 mt-1 space-y-1" x-show="open" x-collapse>
                        <li>
                            <a href="{{ $hrefOf('ortu.jadwal.index') }}"
                                class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ $isActive('ortu.jadwal.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="{{ $isActive('ortu.jadwal.*') ? 'text-blue-600' : 'text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 3v3m8-3v3M4.75 9.75h14.5M6 5.25h12A1.75 1.75 0 0 1 19.75 7v11A1.75 1.75 0 0 1 18 19.75H6A1.75 1.75 0 0 1 4.25 18V7A1.75 1.75 0 0 1 6 5.25Z" />
                                    </svg>
                                </span>
                                <span class="font-medium">Jadwal</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ $hrefOf('ortu.nilai.index') }}"
                                class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ $isActive('ortu.nilai.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="{{ $isActive('ortu.nilai.*') ? 'text-blue-600' : 'text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7 18V10m5 8V6m5 12v-4" />
                                    </svg>
                                </span>
                                <span class="font-medium">Nilai</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ $hrefOf('ortu.kehadiran.index') }}"
                                class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ $isActive('ortu.kehadiran.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="{{ $isActive('ortu.kehadiran.*') ? 'text-blue-600' : 'text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </span>
                                <span class="font-medium">Kehadiran</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Ekskul --}}
                <li>
                    <a href="{{ $hrefOf('ortu.ekskul.index') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $ekskulActive ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $ekskulActive ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3l2.2 4.45 4.9.71-3.55 3.46.84 4.88L12 14.77l-4.39 2.3.84-4.88L4.9 8.16l4.9-.71L12 3Z" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium">Ekskul</span>
                    </a>
                </li>

                {{-- Chat --}}
                <li>
                    <a href="{{ $hrefOf('ortu.chat.index') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $chatActive ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $chatActive ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 10h8M8 14h5M6.75 19.5l-2.25 1.5V6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v7.5A2.25 2.25 0 0 1 17.25 16.5H9.75L6.75 19.5Z" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium">Chat</span>
                    </a>
                </li>

                {{-- Pengumuman --}}
                <li>
                    <a href="{{ $hrefOf('ortu.pengumuman.index') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $pengumumanActive ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $pengumumanActive ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 8.25h9m-9 3.75h9m-9 3.75h6M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z" />
                            </svg>
                        </span>
                        <span class="text-sm font-medium">Pengumuman</span>
                    </a>
                </li>

                {{-- Survei --}}
                @php $persistKeySurvei = 'ortu_sidebar_open_survei'; @endphp
                <li x-data="{
                        open: {{ $aspirasiActive ? 'true' : 'false' }},
                        toggle() {
                            this.open = !this.open;
                            try { localStorage.setItem('{{ $persistKeySurvei }}', this.open ? '1' : '0'); } catch(e) {}
                        }
                    }" x-init="(() => {
                        try {
                            const v = localStorage.getItem('{{ $persistKeySurvei }}');
                            if (v === '1' || v === '0') open = (v === '1');
                            @if ($aspirasiActive) open = true; @endif
                        } catch(e) {}
                    })()">
                    <button type="button" @click="toggle()"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-slate-700 transition hover:bg-slate-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $aspirasiActive ? 'bg-slate-100 text-blue-600' : 'text-slate-500' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.9">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z" />
                                </svg>
                            </span>
                            <span class="truncate text-sm font-medium">Survei</span>
                        </div>

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                            :class="open ? 'rotate-180 text-blue-600' : ''" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.855a.75.75 0 111.08 1.04l-4.24 4.405a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" />
                        </svg>
                    </button>

                    <ul class="ml-11 mt-1 space-y-1" x-show="open" x-collapse>
                        <li>
                            <a href="{{ route('ortu.aspirasi.index') }}"
                                class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ request()->routeIs('ortu.aspirasi.index') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span
                                    class="{{ request()->routeIs('ortu.aspirasi.index') ? 'text-blue-600' : 'text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6m0 4h.01M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z" />
                                    </svg>
                                </span>
                                <span class="font-medium">Isi Survei</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ortu.aspirasi.riwayat') }}"
                                class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ request()->routeIs('ortu.aspirasi.riwayat') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span
                                    class="{{ request()->routeIs('ortu.aspirasi.riwayat') ? 'text-blue-600' : 'text-slate-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 6.75h7.5M8.25 10.5h7.5M8.25 14.25h4.5M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z" />
                                    </svg>
                                </span>
                                <span class="font-medium">Riwayat Survei</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        {{-- Footer --}}
        <div class="border-t border-slate-100 bg-white px-4 py-4">
            <div class="text-center">
                <p class="text-xs font-semibold tracking-[0.16em] text-blue-700">
                    SMAN 2 TEMANGGUNG
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    © {{ date('Y') }} SINTA
                </p>
            </div>
        </div>
    </div>
</aside>