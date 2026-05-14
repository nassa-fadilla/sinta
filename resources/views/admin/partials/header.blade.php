<header
  class="fixed top-0 inset-x-0 z-40 h-16 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl shadow-[0_8px_30px_rgba(15,23,42,0.06)]">
  <div class="max-w-screen-2xl mx-auto flex h-full items-center justify-between px-4 md:px-6">
    {{-- Kiri --}}
    <div class="flex min-w-0 items-center gap-3">
      <button id="btn-toggle-sidebar"
        class="md:hidden -ml-2 inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-sky-200"
        aria-label="Toggle sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
          stroke-width="1.9">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div
        class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-500 via-sky-500 to-cyan-400 p-[2px] shadow-[0_8px_20px_rgba(59,130,246,0.22)]">
        <div class="flex h-full w-full items-center justify-center rounded-[14px] bg-white">
          <img src="{{ asset('images/logo-sma2.png') }}" alt="SMAN 2" class="h-7 w-7 rounded-full object-contain" />
        </div>
      </div>

      <div class="min-w-0 leading-tight">
        <div class="flex items-center gap-2">
          <span class="truncate text-sm font-semibold tracking-[0.22em] text-slate-800">
            SINTA
          </span>
          <span
            class="hidden sm:inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-blue-700">
            Admin
          </span>
        </div>
        <div class="truncate text-[11px] text-slate-500">
          Sistem Informasi Monitoring Aktivitas Siswa
        </div>
      </div>
    </div>

    {{-- Kanan --}}
    <div class="flex items-center gap-2">

      {{-- ═══════════════════════════════════════════════
      NOTIFIKASI ADMIN — Ikon Lonceng
      Polling tiap 30 detik ke GET /admin/notifikasi
      ═══════════════════════════════════════════════ --}}
      <div x-data="notifikasiAdmin()" x-init="init()" @click.away="tutup()" class="relative">
        {{-- Tombol lonceng --}}
        <button type="button" @click="toggle()"
          class="relative inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-sky-200"
          aria-label="Notifikasi">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          {{-- Titik merah (pulse) — muncul saat ada notif --}}
          <span x-show="total > 0" x-cloak class="absolute -top-1 -right-1 flex h-3 w-3">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
            <span class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
          </span>
        </button>

        {{-- Dropdown panel notifikasi --}}
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
            {{-- Indikator loading --}}
            <div x-show="loading"
              class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></div>
          </div>

          {{-- List notifikasi --}}
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

            {{-- Item notifikasi --}}
            <template x-for="item in items" :key="item.id">
              <a :href="item.url" @click="klikItem(item)"
                class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-blue-50/60">
                {{-- Ikon per tipe --}}
                <div
                  class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                  {{-- chat --}}
                  <template x-if="item.icon === 'chat'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5M4 6h16v12H4z" />
                    </svg>
                  </template>
                  {{-- megaphone / pengumuman --}}
                  <template x-if="item.icon === 'megaphone'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                  </template>
                  {{-- check / disetujui --}}
                  <template x-if="item.icon === 'check'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-green-600" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </template>
                  {{-- x / ditolak --}}
                  <template x-if="item.icon === 'x'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-red-500" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </template>
                  {{-- clipboard / survei --}}
                  <template x-if="item.icon === 'clipboard'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                  </template>
                  {{-- users / siswa baru --}}
                  <template x-if="item.icon === 'users'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </template>
                  {{-- user-check / guru baru --}}
                  <template x-if="item.icon === 'user-check'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h11.5M16.5 19.5l1.5 1.5 3-3" />
                    </svg>
                  </template>
                </div>

                {{-- Teks --}}
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-slate-800" x-text="item.judul"></p>
                  <p class="mt-0.5 text-[12px] leading-snug text-slate-500" x-text="item.pesan"></p>
                  <p x-show="item.waktu" class="mt-1 text-[11px] text-slate-400" x-text="item.waktu"></p>
                </div>

                {{-- Panah --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-1 h-4 w-4 shrink-0 text-slate-300" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </a>
            </template>
          </div>

          {{-- Footer panel --}}
          <div class="border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center">
            <p class="text-[11px] text-slate-400">
              Diperbarui otomatis tiap 30 detik
              <span x-show="terakhirUpdate" x-text="'• ' + terakhirUpdate" x-cloak></span>
            </p>
          </div>
        </div>
      </div>

      {{-- Profile dropdown (existing) --}}
      <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = !open" @click.away="open = false"
          class="group flex items-center gap-2 rounded-2xl px-2 py-1.5 transition hover:bg-slate-50">

          <div class="hidden sm:block text-right leading-tight">
            <div class="text-sm font-semibold text-slate-800">
              {{ auth()->user()->name ?? 'Admin SINTA' }}
            </div>
            <div class="text-[11px] text-slate-500">
              Administrator
            </div>
          </div>

          <div
            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50 via-sky-50 to-cyan-50 text-blue-700 shadow-sm transition group-hover:border-blue-200 group-hover:bg-blue-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.9 0-12 2-12 6v2h24v-2c0-4-8.1-6-12-6z" />
            </svg>
          </div>

          <svg xmlns="http://www.w3.org/2000/svg"
            class="hidden h-4 w-4 text-slate-400 transition duration-200 group-hover:text-blue-600 sm:block"
            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
              clip-rule="evenodd" />
          </svg>
        </button>

        <div x-show="open" x-transition x-cloak
          class="absolute right-0 mt-3 w-64 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.14)]">
          <div class="border-b border-slate-100 bg-slate-50 px-4 py-4">
            <div class="flex items-center gap-3">
              <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-100 via-sky-100 to-cyan-100 text-blue-700 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.9 0-12 2-12 6v2h24v-2c0-4-8.1-6-12-6z" />
                </svg>
              </div>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-800">
                  {{ auth()->user()->name ?? 'Admin SINTA' }}
                </div>
                <div class="truncate text-xs text-slate-500">
                  Administrator Sistem
                </div>
              </div>
            </div>
          </div>

          <div class="p-2.5">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit"
                class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm text-red-600 transition hover:bg-red-50">
                <span
                  class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-red-100 bg-red-50 text-red-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m0 0l3-3m-3 3l3 3" />
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
      </div>{{-- end flex items-center gap-2 --}}
    </div>
</header>