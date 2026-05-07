<!doctype html>
<html lang="id" class="h-full">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Portal Orang Tua – SINTA')</title>
  <link rel="icon" href="{{ asset('images/logo-sma2.png') }}">
  <script src="https://cdn.tailwindcss.com"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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