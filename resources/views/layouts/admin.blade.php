<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-institution-id" content="{{ auth()->user()?->institution_id }}">
    <meta name="user-roles" content="{{ auth()->user()?->getRoleNames()?->implode(',') }}">
    <title>@yield('title', 'Panel — '.config('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.svg">
    @vite(['resources/css/app.css'])
    @stack('styles')
    <style>
        /* ═══ GLOBAL Z-INDEX HİYERARŞİSİ FIX ═══
           Leaflet harita panelleri header/navbar üzerine binmesin.
           Header/Nav üstte kalsın, modallar en üstte olsun. */
        .leaflet-container,
        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom { z-index: 10 !important; }

        header, nav,
        #admin-sidebar,
        .admin-navbar { z-index: 50 !important; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased{{ request()->is('maps*') ? ' maps-page' : '' }}">
    {{-- Sidebar Overlay (mobile) --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-900/40 lg:hidden" data-sidebar-close></div>

    {{-- Sidebar wrapper --}}
    <div class="flex min-h-screen">
        @include('partials.sidebar')

        {{-- Main content area --}}
        <div class="flex min-h-screen w-0 flex-1 flex-col">
            @include('partials.navbar')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @include('partials.flash-message')
                @yield('content')
            </main>

            @include('partials.footer')
        </div>
    </div>

    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
            const closeTargets = document.querySelectorAll('[data-sidebar-close]');

            if (!sidebar || !overlay) {
                return;
            }

            const openSidebar = () => {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            };

            const closeSidebar = () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            };

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                        return;
                    }

                    closeSidebar();
                });
            });

            closeTargets.forEach((target) => {
                target.addEventListener('click', closeSidebar);
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                }
            });
        })();
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    @auth
        @vite(['resources/js/echo.js'])
    @endauth
    @stack('scripts')
    @include('partials.scripts')
</body>
</html>
