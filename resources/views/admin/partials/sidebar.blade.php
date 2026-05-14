@php
  use Illuminate\Support\Facades\Route as RouteFacade;

  $isActive = fn($pattern) => request()->routeIs($pattern);

  $hrefOf = function ($name) {
    return ($name && RouteFacade::has($name)) ? route($name) : '#';
  };

  $groups = [
    [
      'key' => 'master',
      'label' => 'Data Master',
      'icon' => 'M12 6l-2 1-2-1-2 1v10l2-1 2 1 2-1 2 1 2-1V7l-2-1-2 1z',
      'items' => [
        ['label' => 'Data Siswa', 'route' => 'admin.sia-master.siswa.index', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14v7'],
        ['label' => 'Data Guru', 'route' => 'admin.sia-master.guru.index', 'icon' => 'M5.5 17a3.5 3.5 0 017 0v1H5.5v-1z M9 7a3 3 0 110 6 3 3 0 010-6z'],
        ['label' => 'Data Tahun Ajaran', 'route' => 'admin.sia-master.tahun-ajaran.index', 'icon' => 'M4 6h16v10H4z M8 6v10 M16 6v10'],
        ['label' => 'Data Mapel', 'route' => 'admin.sia-master.mapel.index', 'icon' => 'M4 4h16v16H4z M8 8h8v8H8z'],
        ['label' => 'Data Rombel', 'route' => 'admin.sia-master.rombel.index', 'icon' => 'M3 8h18M3 12h18M3 16h18'],
      ],
    ],
    [
      'key' => 'akademik',
      'label' => 'Akademik',
      'icon' => 'M8 7V3m8 4V3M3 9h18M5 12h14M5 16h10',
      'items' => [
        ['label' => 'Data Jadwal', 'route' => 'admin.sia-master.jadwal.index', 'icon' => 'M3 4h18v16H3z M8 2v4 M16 2v4'],
        ['label' => 'Data Presensi', 'route' => 'admin.sia-master.presensi.index', 'icon' => 'M5 13l4 4L19 7'],
        ['label' => 'Data Penilaian', 'route' => 'admin.sia-master.nilai.index', 'icon' => 'M11 3h2a1 1 0 011 1v3h3a1 1 0 011 1v2H3V8a1 1 0 011-1h3V4a1 1 0 011-1h3z'],
      ],
    ],
    [
      'key' => 'kegiatan',
      'label' => 'Kegiatan & Info',
      'icon' => 'M13 7h8m-8 4h8m-8 4h5M3 7h8v12H3z',
      'items' => [
        ['label' => 'Ekstrakurikuler', 'route' => 'admin.sia-master.ekskul.index', 'icon' => 'M12 6v12m6-6H6'],
        ['label' => 'Pengumuman', 'route' => 'admin.pengumuman.index', 'icon' => 'M3 5h18v14H3z M3 10h18'],
      ],
    ],
    [
      'key' => 'interaksi',
      'label' => 'Interaksi',
      'icon' => 'M16 11a4 4 0 10-8 0 4 4 0 008 0z M12 15c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z',
      'items' => [
        ['label' => 'Chat Ortu', 'route' => 'admin.chat.index', 'icon' => 'M8 10h8m-8 4h5M4 6h16v12H4z M12 18l4 4 4-4'],
        ['label' => 'Survei Ortu', 'route' => 'admin.survei.index', 'icon' => 'M5 13l4 4L19 7'],
      ],
    ],
  ];

  $singleItems = [
    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'M3 12h18M3 6h18M3 18h18'],
  ];

  $isGroupActive = function ($group) {
    foreach ($group['items'] as $it) {
      $pattern = $it['route'];
      if (request()->routeIs($pattern) || request()->routeIs(str_replace('.index', '.*', $pattern))) {
        return true;
      }
    }
    return false;
  };
@endphp

<aside id="sidebar"
  class="fixed top-16 left-0 z-30 h-[calc(100vh-4rem)] w-64 border-r border-slate-200 bg-white shadow-sm transition-transform duration-300 -translate-x-full md:translate-x-0">
  <div class="flex h-full flex-col bg-white">

    {{-- Brand --}}
    <div class="px-4 py-4 border-b border-slate-100 bg-white">
      <div class="flex items-center gap-3">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-sky-500 bg-white shadow-sm shrink-0">
          <img src="{{ asset('images/logo-sma2.png') }}" alt="SMAN 2" class="h-9 w-9 rounded-full object-contain">
        </div>

        <div class="min-w-0">
          <div class="truncate text-[11px] font-semibold tracking-[0.28em] text-slate-800">
            ADMIN SINTA
          </div>
          <div class="truncate text-xs text-slate-500">
            SMA Negeri 2 Temanggung
          </div>
        </div>
      </div>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 overflow-y-auto px-3 py-3 bg-white">
      <ul class="space-y-1">

        {{-- Single menu --}}
        @foreach($singleItems as $it)
          @php $active = $isActive($it['route']); @endphp
          <li>
            <a href="{{ $hrefOf($it['route']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition
                              {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl
                                {{ $active ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.9">
                  <path d="{{ $it['icon'] }}" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
              <span class="text-sm font-medium">{{ $it['label'] }}</span>
            </a>
          </li>
        @endforeach

        {{-- Group menu --}}
        @foreach($groups as $g)
          @php
            $groupActive = $isGroupActive($g);
            $persistKey = 'admin_sidebar_open_' . $g['key'];
          @endphp

          <li x-data="{
                              open: {{ $groupActive ? 'true' : 'false' }},
                              toggle() {
                                this.open = !this.open;
                                try { localStorage.setItem('{{ $persistKey }}', this.open ? '1' : '0'); } catch(e){}
                              }
                            }" x-init="(() => {
                              try {
                                const v = localStorage.getItem('{{ $persistKey }}');
                                if (v === '1' || v === '0') open = (v === '1');
                                @if($groupActive) open = true; @endif
                              } catch(e){}
                            })()">
            <button type="button" @click="toggle()"
              class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-slate-700 transition hover:bg-slate-50">
              <div class="flex items-center gap-3 min-w-0">
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

            <ul class="mt-1 ml-11 space-y-1" x-show="open" x-collapse>
              @foreach($g['items'] as $it)
                @php
                  $active = $isActive($it['route']) || $isActive(str_replace('.index', '.*', $it['route']));
                @endphp
                <li>
                  <a href="{{ $hrefOf($it['route']) }}"
                    class="group flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] transition
                                                    {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="{{ $active ? 'text-blue-600' : 'text-slate-400' }}">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8">
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
    <div class="border-t border-slate-100 px-4 py-4 bg-white">
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