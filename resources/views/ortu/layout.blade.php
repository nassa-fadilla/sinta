<!doctype html>
<html lang="id" class="h-full">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Portal Orang Tua – SINTA')</title>
  <link rel="icon" href="{{ asset('images/logo-sma2.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- PENTING: notifikasiOrtu() HARUS sebelum
  <script defer> Alpine--}}
    <script>
      function notifikasiOrtu() {
      return {
        buka: false,
      loading: false,
      total: 0,
      items: [],
      terakhirUpdate: '',
      intervalId: null,
      resetSiaUrl: '{{ route('ortu.notifikasi.reset-sia') }}',
      csrfToken: '{{ csrf_token() }}',

      init() {
        this.ambilNotifikasi();
          this.intervalId = setInterval(() => this.ambilNotifikasi(), 30000);
        },

      getDismissed() {
          try { return JSON.parse(sessionStorage.getItem('sinta_notif_ortu_dismissed') || '[]'); } catch(e) { return []; }
        },

      saveDismissed(ids) {
          try {sessionStorage.setItem('sinta_notif_ortu_dismissed', JSON.stringify(ids)); } catch(e) { }
        },

      async ambilNotifikasi() {
        this.loading = true;
      try {
            const res = await fetch('{{ route('ortu.notifikasi') }}', {
        headers: {'X-Requested-With': 'XMLHttpRequest' }
            });
      if (!res.ok) return;
      const data = await res.json();
      const dismissed = this.getDismissed();
      const allItems = data.items ?? [];
            this.items = allItems.filter(i => !dismissed.includes(i.id));
      this.total = this.items.length;
      const now = new Date();
      this.terakhirUpdate = now.getHours().toString().padStart(2,'0')
      + ':' + now.getMinutes().toString().padStart(2,'0');
          } catch(e) {
        // abaikan error jaringan sementara
      } finally {
        this.loading = false;
          }
        },

      toggle() {
        this.buka = !this.buka;
      if (this.buka) this.ambilNotifikasi();
        },

      tutup() {
        this.buka = false;
        },

      klikItem(item) {
          const dismissed = this.getDismissed();
      if (!dismissed.includes(item.id)) dismissed.push(item.id);
      this.saveDismissed(dismissed);
          this.items = this.items.filter(i => i.id !== item.id);
      this.total = this.items.length;
      this.tutup();
      if (['sia_nilai','sia_presensi','sia_ekskul','sia_jadwal'].includes(item.id)) {
        fetch(this.resetSiaUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(() => { });
          }
          if (item.url) setTimeout(() => {window.location.href = item.url; }, 100);
        },
      };
    }
  </script>

  {{-- Alpine Collapse plugin HARUS sebelum Alpine core --}}
  <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <style>
    [x-cloak] {
      display: none !important;
    }

    @keyframes ping {

      75%,
      100% {
        transform: scale(2);
        opacity: 0;
      }
    }
  </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">
  @include('ortu.partials.navbar')
  @include('ortu.partials.sidebar')

  {{-- overlay mobile --}}
  <div id="sidebar-overlay" class="fixed inset-0 z-20 hidden bg-slate-900/30 backdrop-blur-[1px] md:hidden"></div>

  <div id="app-shell" class="min-h-screen transition-all duration-300 md:ml-72">
    <main class="px-4 pb-8 pt-20 md:px-6">
      <div class="max-w-none">
        @yield('content')
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('btn-toggle-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const appShell = document.getElementById('app-shell');

    if (!sidebar || !btn || !overlay || !appShell) return;

    const STORAGE_KEY = 'ortu_sidebar_collapsed';

      const isMobile = () => window.innerWidth < 768;

    function setDesktopExpanded() {
      sidebar.classList.remove('md:w-20');
    sidebar.classList.add('md:w-72');

    appShell.classList.remove('md:ml-20');
    appShell.classList.add('md:ml-72');

    sidebar.classList.remove('sidebar-collapsed');
    localStorage.setItem(STORAGE_KEY, '0');
      }

    function setDesktopCollapsed() {
      sidebar.classList.remove('md:w-72');
    sidebar.classList.add('md:w-20');

    appShell.classList.remove('md:ml-72');
    appShell.classList.add('md:ml-20');

    sidebar.classList.add('sidebar-collapsed');
    localStorage.setItem(STORAGE_KEY, '1');
      }

    function openMobileSidebar() {
      sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
      }

    function closeMobileSidebar() {
      sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
      }

    function syncSidebarMode() {
        if (isMobile()) {
      sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    sidebar.classList.remove('md:w-20');
    sidebar.classList.add('md:w-72');

    appShell.classList.remove('md:ml-20');
    appShell.classList.add('md:ml-72');
        } else {
      sidebar.classList.remove('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    const collapsed = localStorage.getItem(STORAGE_KEY) === '1';
    if (collapsed) {
      setDesktopCollapsed();
          } else {
      setDesktopExpanded();
          }
        }
      }

    btn.addEventListener('click', function () {
        if (isMobile()) {
          if (sidebar.classList.contains('-translate-x-full')) {
      openMobileSidebar();
          } else {
      closeMobileSidebar();
          }
        } else {
          const collapsed = sidebar.classList.contains('sidebar-collapsed');
    if (collapsed) {
      setDesktopExpanded();
          } else {
      setDesktopCollapsed();
          }
        }
      });

    overlay.addEventListener('click', closeMobileSidebar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isMobile()) {
      closeMobileSidebar();
        }
      });

    window.addEventListener('resize', syncSidebarMode);

    syncSidebarMode();
    });
  </script>

  @stack('scripts')
</body>

</html>