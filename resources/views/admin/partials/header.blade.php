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
    </div>
  </div>
</header>