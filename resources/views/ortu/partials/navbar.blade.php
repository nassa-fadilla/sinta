@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $user = auth()->user();

    $defaultFoto = file_exists(public_path('images/avatar-default.png'))
        ? asset('images/avatar-default.png')
        : asset('images/logo-sma2.png');

    /*
    |--------------------------------------------------------------------------
    | Foto user/orang tua
    |--------------------------------------------------------------------------
    | Tetap lewat route ortu.profil.photo.
    | Query versi ditambahkan untuk mencegah cache foto lama.
    */
    $nis = trim((string) ($user?->sia_user_id ?? '-'));
    $fotoVersion = md5((string) $nis . '|' . (string) ($user?->id ?? ''));

    $fotoUser = RouteFacade::has('ortu.profil.photo')
        ? route('ortu.profil.photo', ['v' => $fotoVersion, 'nis' => $nis])
        : $defaultFoto;
@endphp

<header
    class="fixed top-0 inset-x-0 z-40 h-16 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl shadow-[0_8px_30px_rgba(15,23,42,0.06)]">
    <div class="max-w-screen-2xl mx-auto flex h-full items-center justify-between px-4 md:px-6">
        {{-- Kiri --}}
        <div class="flex min-w-0 items-center gap-3">
            <button id="btn-toggle-sidebar"
                class="md:hidden -ml-2 inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-sky-200"
                aria-label="Toggle sidebar" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div
                class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-500 via-sky-500 to-cyan-400 p-[2px] shadow-[0_8px_20px_rgba(59,130,246,0.22)]">
                <div class="flex h-full w-full items-center justify-center rounded-[14px] bg-white">
                    <img src="{{ asset('images/logo-sma2.png') }}" alt="SMAN 2"
                        class="h-7 w-7 rounded-full object-contain" />
                </div>
            </div>

            <div class="min-w-0 leading-tight">
                <div class="flex items-center gap-2">
                    <span class="truncate text-sm font-semibold tracking-[0.22em] text-slate-800">
                        SINTA
                    </span>
                    <span
                        class="hidden sm:inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-blue-700">
                        Ortu
                    </span>
                </div>
                <div class="truncate text-[11px] text-slate-500">
                    Portal Orang Tua
                </div>
            </div>
        </div>

        {{-- Kanan --}}
        <div class="flex items-center gap-2">

            {{-- ═══ NOTIFIKASI ORTU — Ikon Lonceng ═══ --}}
            <div x-data="notifikasiOrtu()" x-init="init()" @click.away="tutup()" class="relative">
                <button type="button" @click="toggle()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    aria-label="Notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
                {{-- Titik merah pulse --}}
                <span x-show="total > 0"
                    style="position:absolute;top:-4px;right:-4px;width:12px;height:12px;display:flex;pointer-events:none;z-index:10;">
                    <span
                        style="position:absolute;display:inline-flex;height:100%;width:100%;border-radius:9999px;background-color:#f87171;opacity:0.75;animation:ping 1s cubic-bezier(0,0,0.2,1) infinite;"></span>
                    <span
                        style="position:relative;display:inline-flex;height:12px;width:12px;border-radius:9999px;background-color:#ef4444;"></span>
                </span>

                {{-- Dropdown panel --}}
                <div x-show="buka" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" x-cloak
                    class="absolute right-0 top-full mt-3 w-80 origin-top-right overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.14)] z-50">

                    {{-- Header panel --}}
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Notifikasi</p>
                            <p class="text-[11px] text-slate-500"
                                x-text="total > 0 ? total + ' notifikasi baru' : 'Semua sudah terpantau'"></p>
                        </div>
                        <div x-show="loading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
                        </div>
                    </div>

                    {{-- List --}}
                    <div class="max-h-[22rem] overflow-y-auto">
                        {{-- Kosong --}}
                        <div x-show="!loading && items.length === 0"
                            class="flex flex-col items-center justify-center px-4 py-10 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-slate-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="text-sm font-medium text-slate-500">Tidak ada notifikasi baru</p>
                            <p class="mt-1 text-xs text-slate-400">Semua aktivitas sudah terpantau</p>
                        </div>

                        {{-- Item --}}
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url" @click.prevent="klikItem(item)"
                                class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-blue-50/60">
                                <div
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <template x-if="item.icon === 'chat'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 10h8m-8 4h5M4 6h16v12H4z" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'megaphone'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'academic-cap'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 14l6.16-3.422A12.083 12.083 0 0121 17.25H3a12.083 12.083 0 012.84-6.672L12 14z" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'clipboard-check'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'star'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'clock'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="item.icon === 'clipboard'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-800" x-text="item.judul"></p>
                                    <p class="mt-0.5 text-[12px] leading-snug text-slate-500" x-text="item.pesan"></p>
                                    <p x-show="item.waktu" class="mt-1 text-[11px] text-slate-400" x-text="item.waktu">
                                    </p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-1 h-4 w-4 shrink-0 text-slate-300"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center">
                        <p class="text-[11px] text-slate-400">
                            Diperbarui otomatis tiap 30 detik
                            <span x-show="terakhirUpdate" x-text="'• ' + terakhirUpdate" x-cloak></span>
                        </p>
                    </div>
                </div>
            </div>
            {{-- end notifikasi --}}

            {{-- Profile dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.away="open = false"
                    class="group flex items-center gap-2 rounded-2xl px-2 py-1.5 transition hover:bg-slate-50">

                    <div class="hidden md:block text-right leading-tight">
                        <div class="text-sm font-semibold text-slate-800">
                            {{ $user->name ?? 'Orang Tua' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Orang Tua
                        </div>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50 via-sky-50 to-cyan-50 text-blue-700 shadow-sm transition group-hover:border-blue-200 group-hover:bg-blue-50">
                        <img src="{{ $fotoUser }}" alt="Foto {{ $user->name ?? 'Orang Tua' }}"
                            class="h-full w-full object-cover"
                            onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="hidden h-4 w-4 text-slate-400 transition duration-200 group-hover:text-blue-600 md:block"
                        :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="open" x-transition x-cloak
                    class="absolute right-0 mt-3 w-64 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.14)]">
                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-100 via-sky-100 to-cyan-100 text-blue-700 shadow-sm">
                                <img src="{{ $fotoUser }}" alt="Foto {{ $user->name ?? 'Orang Tua' }}"
                                    class="h-full w-full object-cover"
                                    onerror="this.onerror=null;this.src='{{ $defaultFoto }}';">
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">
                                    {{ $user->name ?? 'Orang Tua' }}
                                </div>
                                <div class="truncate text-xs text-slate-500">
                                    Orang Tua
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-2.5">
                        <a href="{{ route('ortu.profil') }}"
                            class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm text-slate-700 transition hover:bg-blue-50">
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19a7 7 0 10-6 0m6 0a9 9 0 11-6 0m6 0a8.962 8.962 0 01-6 0" />
                                </svg>
                            </span>
                            <div class="text-left">
                                <div class="font-medium">Profil</div>
                                <div class="text-[11px] text-slate-400">Data dari SIA</div>
                            </div>
                        </a>

                        <form method="POST" action="{{ route('ortu.logout') }}" class="mt-2">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm text-red-600 transition hover:bg-red-50">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-red-100 bg-red-50 text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 12H9m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                </span>
                                <div class="text-left">
                                    <div class="font-medium">Keluar</div>
                                    <div class="text-[11px] text-red-400">Akhiri sesi login</div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            {{-- end profile dropdown --}}

        </div>
        {{-- end kanan --}}
    </div>
</header>