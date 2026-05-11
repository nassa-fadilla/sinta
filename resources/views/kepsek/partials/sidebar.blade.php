@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $isActive = fn($pattern) => request()->routeIs($pattern);
    $hrefOf = fn($name) => (RouteFacade::has($name) ? route($name) : '#');

    $u = auth()->user();
    $kepsekNuptk = trim((string) env('KEPSEK_NUPTK', '43265128'));
    $authIdentifier = trim((string) ($u?->sia_user_id ?? ''));

    $kepsekApi = isset($kepsekApi) && is_array($kepsekApi) ? (object) $kepsekApi : ($kepsekApi ?? null);

    if (!$kepsekApi) {
        try {
            /** @var \App\Services\SiaClient $sia */
            $sia = app(\App\Services\SiaClient::class);

            $candidateKeys = collect([
                $authIdentifier !== '' ? $authIdentifier : null,
                $kepsekNuptk !== '' ? $kepsekNuptk : null,
            ])->filter()->unique()->values();

            foreach ($candidateKeys as $key) {
                $resp = $sia->getGuruByKey($key);

                if (
                    is_array($resp)
                    && (($resp['status'] ?? null) === true || ($resp['success'] ?? null) === true)
                    && !empty($resp['data'])
                    && is_array($resp['data'])
                ) {
                    $kepsekApi = (object) $resp['data'];
                    break;
                }
            }
        } catch (\Throwable $e) {
            $kepsekApi = null;
        }
    }

    $kepsek = $kepsekApi;
    $displayName = $kepsek->nama ?? ($u?->name ?? 'Kepala Sekolah');

    $defaultFoto = file_exists(public_path('images/default-user.png'))
        ? asset('images/default-user.png')
        : asset('images/logo-sma2.png');

    $resolveKepsekFoto = function ($kepsekApi = null) use ($defaultFoto, $u) {
        $candidateValues = [
            data_get($kepsekApi, 'foto_url'),
            data_get($kepsekApi, 'photo_url'),
            data_get($kepsekApi, 'avatar'),
            data_get($kepsekApi, 'foto'),
            data_get($kepsekApi, 'photo'),
            data_get($kepsekApi, 'gambar'),
            data_get($kepsekApi, 'image'),
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
                    (string) data_get($kepsekApi, 'updated_at', '') . '|' .
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

    $fotoPath = $resolveKepsekFoto($kepsekApi);

    $singleItems = [
        [
            'label' => 'Dashboard',
            'route' => 'kepsek.dashboard',
            'icon' => 'M3 12h18M3 6h18M3 18h18',
        ],
    ];

    $groups = [
        [
            'key' => 'master',
            'label' => 'Data Master',
            'icon' => 'M12 6l-2 1-2-1-2 1v10l2-1 2 1 2-1 2 1 2-1V7l-2-1-2 1z',
            'items' => [
                [
                    'label' => 'Data Siswa',
                    'route' => 'kepsek.sia-master.siswa.index',
                    'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14v7',
                ],
                [
                    'label' => 'Data Guru',
                    'route' => 'kepsek.sia-master.guru.index',
                    'icon' => 'M5.5 17a3.5 3.5 0 017 0v1H5.5v-1z M9 7a3 3 0 110 6 3 3 0 010-6z',
                ],
                [
                    'label' => 'Data Tahun Ajaran',
                    'route' => 'kepsek.sia-master.tahun-ajaran.index',
                    'icon' => 'M4 6h16v10H4z M8 6v10 M16 6v10',
                ],
                [
                    'label' => 'Data Mapel',
                    'route' => 'kepsek.sia-master.mapel.index',
                    'icon' => 'M4 4h16v16H4z M8 8h8v8H8z',
                ],
                [
                    'label' => 'Data Rombel',
                    'route' => 'kepsek.sia-master.rombel.index',
                    'icon' => 'M3 8h18M3 12h18M3 16h18',
                ],
            ],
        ],
        [
            'key' => 'akademik',
            'label' => 'Akademik',
            'icon' => 'M8 7V3m8 4V3M3 9h18M5 12h14M5 16h10',
            'items' => [
                [
                    'label' => 'Data Jadwal',
                    'route' => 'kepsek.sia-master.jadwal.index',
                    'icon' => 'M3 4h18v16H3z M8 2v4 M16 2v4',
                ],
                [
                    'label' => 'Data Presensi',
                    'route' => 'kepsek.sia-master.presensi.index',
                    'icon' => 'M5 13l4 4L19 7',
                ],
                [
                    'label' => 'Data Penilaian',
                    'route' => 'kepsek.sia-master.nilai.index',
                    'icon' => 'M11 3h2a1 1 0 011 1v3h3a1 1 0 011 1v2H3V8a1 1 0 011-1h3V4a1 1 0 011-1h3z',
                ],
            ],
        ],
        [
            'key' => 'kegiatan',
            'label' => 'Kegiatan',
            'icon' => 'M13 7h8m-8 4h8m-8 4h5M3 7h8v12H3z',
            'items' => [
                [
                    'label' => 'Ekstrakurikuler',
                    'route' => 'kepsek.sia-master.ekskul.index',
                    'icon' => 'M12 6v12m6-6H6',
                ],
            ],
        ],
        [
            'key' => 'pengumuman',
            'label' => 'Pengumuman',
            'icon' => 'M4 5h16v14H4z M4 10h16',
            'items' => [
                [
                    'label' => 'Menunggu Persetujuan',
                    'route' => 'kepsek.pengumuman.index',
                    'params' => ['status' => 'pending'],
                    'icon' => 'M8 12h8m-8 4h8M5 8h14v12H5z',
                ],
                [
                    'label' => 'Riwayat Pengumuman',
                    'route' => 'kepsek.pengumuman.index',
                    'params' => ['status' => 'approved'],
                    'icon' => 'M12 8v8m-4-4h8M5 12h14',
                ],
            ],
        ],
    ];

    $isGroupActive = function ($group) {
        foreach ($group['items'] as $it) {
            $r = $it['route'];
            if (request()->routeIs($r) || request()->routeIs(str_replace('.index', '.*', $r))) {
                return true;
            }
        }
        return false;
    };
@endphp

<aside id="sidebar"
    class="fixed top-16 left-0 z-30 h-[calc(100vh-4rem)] w-64 -translate-x-full border-r border-slate-200 bg-white shadow-sm transition-transform duration-300 md:translate-x-0">
    <div class="flex h-full flex-col bg-white">

        {{-- Brand / Profil --}}
        <div class="border-b border-slate-100 bg-white px-4 py-5">
            <div class="flex flex-col items-center text-center">
                <div
                    class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-[1.75rem] border-2 border-sky-500 bg-white shadow-[0_10px_24px_rgba(59,130,246,0.12)]">
                    <img src="{{ $fotoPath }}" alt="Foto Kepala Sekolah" class="h-full w-full object-cover object-top"
                        loading="lazy" onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                </div>

                <div class="mt-3 min-w-0">
                    <div class="truncate text-[11px] font-semibold tracking-[0.28em] text-slate-800">
                        KEPALA SEKOLAH
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        SMA Negeri 2 Temanggung
                    </div>
                    <div class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-800">
                        {{ $displayName }}
                    </div>

                    @if ($kepsek)
                        <div class="mt-2 space-y-0.5">
                            <div class="text-[11px] text-slate-500">
                                NUPTK: {{ $kepsek->nuptk ?? '-' }}
                            </div>
                            @if (!empty($kepsek->nip))
                                <div class="text-[11px] text-slate-500">
                                    NIP: {{ $kepsek->nip }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 overflow-y-auto bg-white px-3 py-3">
            <ul class="space-y-1">

                @foreach ($singleItems as $it)
                    @php $active = $isActive($it['route']); @endphp
                    <li>
                        <a href="{{ $hrefOf($it['route']) }}"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $active ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.9">
                                    <path d="{{ $it['icon'] }}" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium">{{ $it['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                @foreach ($groups as $g)
                    @php
                        $groupActive = $isGroupActive($g);
                        $persistKey = 'kepsek_sidebar_open_' . $g['key'];
                    @endphp

                    <li x-data="{
                                    open: {{ $groupActive ? 'true' : 'false' }},
                                    toggle() {
                                        this.open = !this.open;
                                        try { localStorage.setItem('{{ $persistKey }}', this.open ? '1' : '0'); } catch(e) {}
                                    }
                                }" x-init="(() => {
                                    try {
                                        const v = localStorage.getItem('{{ $persistKey }}');
                                        if (v === '1' || v === '0') open = (v === '1');
                                        @if ($groupActive) open = true; @endif
                                    } catch(e) {}
                                })()">
                        <button type="button" @click="toggle()"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-slate-700 transition hover:bg-slate-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $groupActive ? 'bg-slate-100 text-blue-600' : 'text-slate-500' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.9">
                                        <path d="{{ $g['icon'] }}" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="truncate text-sm font-medium">{{ $g['label'] }}</span>
                            </div>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                                :class="open ? 'rotate-180 text-blue-600' : ''" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.855a.75.75 0 111.08 1.04l-4.24 4.405a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" />
                            </svg>
                        </button>

                        <ul class="ml-11 mt-1 space-y-1" x-show="open" x-collapse>
                            @foreach ($g['items'] as $it)
                                @php
                                    $routeName = $it['route'];
                                    $params = $it['params'] ?? [];
                                    $url = route($routeName, $params);

                                    $active = false;
                                    if (!empty($params['status'])) {
                                        $active = request()->fullUrlIs('*status=' . $params['status'] . '*');
                                    } else {
                                        $active =
                                            request()->routeIs($routeName) ||
                                            request()->routeIs(str_replace('.index', '.*', $routeName));
                                    }
                                @endphp

                                <li>
                                    <a href="{{ $url }}"
                                        class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <span class="{{ $active ? 'text-blue-600' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="{{ $it['icon'] }}" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="font-medium">{{ $it['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
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