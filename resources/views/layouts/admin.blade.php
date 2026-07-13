<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel — '.config('app.name'))</title>
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased{{ request()->is('maps*') ? ' maps-page' : '' }}">
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-900/40 lg:hidden" data-sidebar-close></div>

    <div class="flex min-h-screen">
        @include('partials.sidebar')

        <div class="flex min-h-screen flex-1 flex-col lg:min-w-0">
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
