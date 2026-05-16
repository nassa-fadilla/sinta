<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') – SINTA Kepsek</title>
    <link rel="icon" href="{{ asset('images/logo-sma2.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- PENTING: notifikasiKepsek() HARUS sebelum
    <script defer> Alpine--}}
        <script>
            function notifikasiKepsek() {
            return {
                buka: false,
            loading: false,
            total: 0,
            items: [],
            terakhirUpdate: '',
            intervalId: null,
            resetSiaUrl: '{{ route('kepsek.notifikasi.reset-sia') }}',
            csrfToken: '{{ csrf_token() }}',

            init() {
                this.ambilNotifikasi();
                    this.intervalId = setInterval(() => this.ambilNotifikasi(), 30000);
                },

            getDismissed() {
                    try { return JSON.parse(sessionStorage.getItem('sinta_notif_kepsek_dismissed') || '[]'); } catch(e) { return []; }
                },

            saveDismissed(ids) {
                    try {sessionStorage.setItem('sinta_notif_kepsek_dismissed', JSON.stringify(ids)); } catch(e) { }
                },

            async ambilNotifikasi() {
                this.loading = true;
            try {
                        const res = await fetch('{{ route('kepsek.notifikasi') }}', {
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
            if (item.id === 'sia_siswa' || item.id === 'sia_guru') {
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
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btn-toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function closeSidebar() {
                if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
            }

        function openSidebar() {
                if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
            }

        if (btn && sidebar) {
            btn.addEventListener('click', () => {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                if (isHidden) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });
            }

        overlay?.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
            overlay?.classList.add('hidden');
        sidebar?.classList.remove('-translate-x-full');
                } else {
            sidebar?.classList.add('-translate-x-full');
                }
            });
        });
    </script>
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-100 text-slate-900">
    {{-- Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.10),_transparent_28%),linear-gradient(to_bottom,_#f8fbff,_#eef5ff_42%,_#f8fafc_100%)]">
        </div>
        <div class="absolute left-[-90px] top-20 h-72 w-72 rounded-full bg-blue-200/20 blur-3xl"></div>
        <div class="absolute right-[-80px] top-40 h-80 w-80 rounded-full bg-sky-200/20 blur-3xl"></div>
        <div class="absolute bottom-[-100px] left-1/3 h-80 w-80 rounded-full bg-cyan-100/20 blur-3xl"></div>
    </div>

    {{-- Header --}}
    @include('kepsek.partials.navbar')

    {{-- Overlay mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-20 hidden bg-slate-900/20 backdrop-blur-[1px] md:hidden"></div>

    {{-- Sidebar --}}
    @include('kepsek.partials.sidebar')

    {{-- Main --}}
    <main class="min-h-screen px-4 pb-12 pt-20 md:ml-64 md:px-8 lg:px-10">
        <div class="mx-auto max-w-screen-2xl">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>

</html>