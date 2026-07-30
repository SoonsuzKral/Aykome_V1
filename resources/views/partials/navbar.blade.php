<header class="sticky top-0 z-[1050] flex h-14 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" data-sidebar-toggle aria-label="Menüyü aç">
            ☰
        </button>
        <div class="text-sm font-medium text-slate-600">
            @yield('page-heading', '')
        </div>
    </div>

    <div class="flex items-center gap-2 text-sm">
        {{-- Notification Bell --}}
        <div class="relative" id="notif-wrapper">
            <button type="button" id="notif-btn"
                class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100"
                aria-label="Bildirimler">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span id="notif-badge"
                    class="absolute -right-0.5 -top-0.5 hidden min-w-[18px] rounded-full bg-rose-500 px-1 py-px text-center text-[10px] font-bold leading-tight text-white shadow"></span>
            </button>

            <div id="notif-panel"
                class="absolute right-0 top-full z-50 mt-2 hidden w-80 max-w-[calc(100vw-1rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-800">Bildirimler</span>
                    <button type="button" id="notif-mark-all"
                        class="text-xs text-cyan-700 hover:underline">Tümünü oku</button>
                </div>
                <ul id="notif-list" class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                    <li class="px-4 py-6 text-center text-sm text-slate-400">Yükleniyor…</li>
                </ul>
                <div class="border-t border-slate-100 px-4 py-2 text-center">
                    <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium text-cyan-700 hover:underline">Panele dön</a>
                </div>
            </div>
        </div>

        {{-- Tarih / Saat / Hoş Geldin --}}
        <div class="hidden items-center gap-2 border-r border-slate-200 pr-3 md:flex">
            <span id="navbar-clock" class="font-mono text-xs font-semibold tabular-nums text-[#02AFC6]">--:--:--</span>
            <span class="text-xs text-slate-400">{{ now()->format('d.m.Y') }}</span>
        </div>
        <span class="hidden text-sm text-slate-600 lg:inline">Hoş geldin, <strong class="text-slate-800">{{ auth()->user()->name }}</strong></span>
        <a href="{{ route('admin.profile.edit') }}" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">Profil</a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="rounded-md px-2 py-1 text-red-600 hover:bg-red-50">Çıkış</button>
        </form>
    </div>
</header>

<script>
(function () {
    const btn = document.getElementById('notif-btn');
    const panel = document.getElementById('notif-panel');
    const badge = document.getElementById('notif-badge');
    const list = document.getElementById('notif-list');
    const markAllBtn = document.getElementById('notif-mark-all');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    if (!btn || !panel) return;

    let loaded = false;

    const fetchNotifications = () => {
        fetch('{{ route('admin.notifications.index') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            const count = data.unread_count ?? 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }

            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML = '<li class="px-4 py-6 text-center text-sm text-slate-400">Bildirim yok.</li>';
                return;
            }

            list.innerHTML = data.notifications.map(n => `
                <li class="flex cursor-pointer gap-3 px-4 py-3 transition hover:bg-slate-50 ${!n.read ? 'bg-cyan-50/50' : ''}"
                    data-notif-id="${n.id}" data-url="${n.url}" onclick="notifClick(this)">
                    <div class="mt-0.5 flex-shrink-0">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full ${n.type === 'receipt_uploaded' ? 'bg-amber-100 text-amber-600' : 'bg-cyan-100 text-cyan-600'} text-xs font-bold">
                            ${n.type === 'receipt_uploaded' ? '₺' : '✚'}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-slate-800">${n.title}</p>
                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">${n.message}</p>
                        <p class="mt-1 text-[10px] text-slate-400">${n.created_at}</p>
                    </div>
                </li>
            `).join('');

            loaded = true;
        })
        .catch(() => {
            list.innerHTML = '<li class="px-4 py-4 text-center text-xs text-rose-500">Bildirimler yüklenemedi.</li>';
        });
    };

    window.notifClick = function (el) {
        const id = el.dataset.notifId;
        const url = el.dataset.url;
        fetch('{{ route('admin.notifications.mark-read') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ id })
        });
        if (url && url !== '#') window.location.href = url;
    };

    markAllBtn?.addEventListener('click', () => {
        fetch('{{ route('admin.notifications.mark-read') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ id: null })
        }).then(() => fetchNotifications());
    });

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = panel.classList.contains('hidden');
        panel.classList.toggle('hidden');
        if (isHidden && !loaded) fetchNotifications();
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('notif-wrapper')?.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });

    // Initial badge count
    fetchNotifications();
})();

// Dinamik saat
(function () {
    const clockEl = document.getElementById('navbar-clock');
    if (!clockEl) return;
    const tick = () => {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        clockEl.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    };
    tick();
    setInterval(tick, 1000);
})();
</script>
