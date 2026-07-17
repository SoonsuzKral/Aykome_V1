@php
    $items = [
        ['label' => 'Dashboard',        'route' => 'admin.dashboard',          'match' => 'admin.dashboard',         'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>'],
        ['label' => 'Başvurular',       'route' => 'admin.applications.index', 'match' => 'admin.applications.*',   'perm' => 'applications.view', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Harita İzleme',    'route' => 'admin.map.index',          'match' => 'admin.map.*',             'perm' => 'applications.view', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zm10.586 13.414L18 14.586V4a1 1 0 00-1.707-.707L14 5.586v12.828l.293-.707z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Raporlar',          'route' => 'admin.reports.index',      'match' => 'admin.reports.index',     'perm' => 'applications.view', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>'],
        ['label' => 'Gelişmiş Rapor',   'route' => 'admin.reports.advanced',   'match' => 'admin.reports.advanced',  'perm' => 'pro.advanced_reports', 'badge' => 'PRO', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Zemin Tipleri',     'route' => 'admin.surface-types.index','match' => 'admin.surface-types.*',  'perm' => 'surface_types.manage', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h3a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Kurumlar',          'route' => 'admin.institutions.index', 'match' => 'admin.institutions.*',    'perm' => 'users.manage', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Kullanıcılar',     'route' => 'admin.users.index',        'match' => 'admin.users.*',           'perm' => 'users.manage', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>'],
        ['label' => 'Roller',           'route' => 'admin.roles.index',        'match' => 'admin.roles.*',           'perm' => 'users.manage', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Profil',           'route' => 'admin.profile.edit',       'match' => 'admin.profile.*', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>'],
        // Yalnızca Super Admin görür
        ['label' => 'Firmalar & Lisanslar', 'route' => 'admin.licenses.index',      'match' => 'admin.licenses.*',      'role' => 'super-admin', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Belge Ayarları',       'route' => 'admin.settings.permit',              'match' => 'admin.settings.permit',      'role' => 'super-admin', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Ön Kazı Belge Ayarları', 'route' => 'admin.settings.pre-excavation-permit', 'match' => 'admin.settings.pre-excavation*', 'role' => 'super-admin', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/></svg>'],
        ['label' => 'Sistem Logları',       'route' => 'admin.logs.index',         'match' => 'admin.logs.*',          'role' => 'super-admin', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h3a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>'],
    ];

    // PRO Modüller — Gerçek rotalar ile aktif (altyapısı hazır)
    $proModulesActive = [
        [
            'label' => 'Canlı Saha İzleme PRO',
            'route' => 'admin.live-map-pro.index',
            'match' => 'admin.live-map-pro.*',
            'pulse' => true,
            'perm'  => 'pro.live_map',
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>',
        ],
        [
            'label' => 'Görev Emri Yönetimi',
            'route' => 'admin.work-orders.index',
            'match' => 'admin.work-orders.*',
            'perm'  => 'pro.work_orders',
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>',
        ],
        [
            'label' => 'Gelişmiş Saha Raporlama',
            'route' => 'admin.field-reports-pro.index',
            'match' => 'admin.field-reports-pro.*',
            'perm'  => 'pro.field_reports',
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>',
        ],
        [
            'label' => 'Evrak ve Tevdi (E-Belge)',
            'route' => 'admin.e-document.index',
            'match' => 'admin.e-document.*',
            'perm'  => 'pro.evrak_tevdi',
            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>',
        ],
    ];

    // Yakında: PRO Sidebar önerileri (tıklanabilir değil, popup ile)
    $proComingSoon = [
        ['label' => 'Kazı Metraj Tahmin Motoru', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>'],
    ];

    // Yakında gelecek placeholder modüller
    $premiumModules = [
        ['label' => 'E-Tebligat Servisi', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>'],
    ];
@endphp

<aside
    id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-slate-700/80 bg-gradient-to-b from-slate-950 to-slate-900 text-slate-200 transition-transform duration-200 lg:static lg:z-auto lg:w-64 lg:max-w-none lg:translate-x-0"
>
    {{-- Brand --}}
    <div class="flex items-center justify-between border-b border-slate-700/60 px-4 py-4 lg:block lg:border-b-0">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#02E0FB] to-[#FA6001] text-xs font-black text-white shadow">H</span>
                <span class="font-bold tracking-tight text-white">{{ config('app.name') }}</span>
            </div>
            <span class="mt-2 inline-block rounded-md bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-300">Ultra SaaS</span>
        </div>
        <button type="button" class="rounded-md p-1 text-slate-300 hover:bg-slate-800 lg:hidden" data-sidebar-close aria-label="Menüyü kapat">
            ✕
        </button>
    </div>

    {{-- Main nav --}}
    <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3 py-3" aria-label="Ana menü">
        {{-- Vitrin Butonu — SADECE Super Admin görür --}}
        @hasrole('super-admin')
        <a
            href="{{ route('home') }}"
            target="_blank"
            rel="noopener"
            class="group mb-1.5 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 bg-gradient-to-r from-[#02E0FB]/15 via-[#FA6001]/10 to-transparent border border-[#02E0FB]/25 hover:border-[#02E0FB]/50 hover:from-[#02E0FB]/25 hover:shadow-[0_0_16px_rgba(2,224,251,0.2)] text-[#02E0FB] hover:text-white"
        >
            <span class="flex-shrink-0 text-base leading-none">🌍</span>
            <span class="flex-1 truncate">Ürünü İncele</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
        </a>
        @endhasrole

        {{-- Kullanım Kılavuzu — Saha ekibi GÖREMEZ, admin/belediye/kurum görür --}}
        @if(!auth()->user()->hasRole('field-team'))
        <a
            href="{{ route('docs.index') }}"
            target="_blank"
            rel="noopener"
            class="group mb-2 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-200 bg-gradient-to-r from-[#FA6001]/15 via-[#FA6001]/5 to-transparent border border-[#FA6001]/25 hover:border-[#FA6001]/50 hover:from-[#FA6001]/25 hover:shadow-[0_0_16px_rgba(250,96,1,0.2)] text-[#FA6001] hover:text-white"
        >
            <span class="flex-shrink-0 text-base leading-none">📖</span>
            <span class="flex-1 truncate">Kullanım Kılavuzu</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
        </a>
        @endif
        <div class="mb-1 border-t border-slate-700/40"></div>

        @foreach($items as $item)
            {{-- field-team: only Dashboard and Profil --}}
            @if(auth()->user()->hasRole('field-team') && !in_array($item['route'], ['admin.dashboard', 'admin.profile.edit']))
                @continue
            @endif
            @if(isset($item['perm']) && ! auth()->user()->can($item['perm']))
                @continue
            @endif
            @if(isset($item['role']) && ! auth()->user()->hasRole($item['role']))
                @continue
            @endif
            @php $active = request()->routeIs($item['match']); @endphp
            <a
                href="{{ route($item['route']) }}"
                data-sidebar-close
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ $active ? 'bg-gradient-to-r from-[#02E0FB]/20 to-transparent font-semibold text-white shadow-sm ring-1 ring-[#02E0FB]/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white' }}"
            >
                <span class="{{ $active ? 'text-[#02E0FB]' : 'opacity-60' }}">{!! $item['icon'] !!}</span>
                {{ $item['label'] }}
                @if(isset($item['role']))<span class="ms-1 rounded-md bg-gradient-to-r from-[#02E0FB]/25 to-[#FA6001]/20 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-[#02E0FB]">SA</span>@endif
                @if(isset($item['badge']))<span class="ms-1 rounded-full bg-gradient-to-r from-[#FA6001]/40 to-[#02E0FB]/30 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-orange-300">{{ $item['badge'] }}</span>@endif
                @if($active)<span class="ms-auto h-1.5 w-1.5 rounded-full bg-[#02E0FB]"></span>@endif
            </a>
        @endforeach

        {{-- CBS Entegrasyon — Aykome Maps --}}
        @php $cbsActive = request()->routeIs('maps.*'); @endphp
        <div class="my-3 border-t border-slate-700/50"></div>
        <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500">CBS & Harita</p>
        <a href="{{ route('maps.index') }}"
           data-sidebar-close
           class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ $cbsActive ? 'bg-gradient-to-r from-emerald-500/20 to-transparent font-semibold text-white ring-1 ring-emerald-500/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white' }}">
            <span class="{{ $cbsActive ? 'text-emerald-400' : 'opacity-55' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
            </span>
            <span class="flex-1 truncate">Aykome Maps</span>
            @if($cbsActive)<span class="ms-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>@endif
        </a>

        {{-- Saha Personeli — Görevlerim linki (@can tabanlı) --}}
        @can('field.tasks_view')
        <div class="my-3 border-t border-slate-700/50"></div>
        <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500">Saha İşlemleri</p>
        @php $myTasksActive = request()->routeIs('admin.my-tasks.*'); @endphp
        <a href="{{ route('admin.my-tasks.index') }}"
           data-sidebar-close
           class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ $myTasksActive ? 'bg-gradient-to-r from-emerald-500/25 to-transparent font-semibold text-white ring-1 ring-emerald-500/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white' }}"
        >
            <span class="{{ $myTasksActive ? 'text-emerald-400' : 'opacity-60' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            </span>
            <span class="flex-1 truncate">Görevlerim</span>
            @if($myTasksActive)<span class="ms-auto h-1.5 w-1.5 rounded-full bg-emerald-400"></span>@endif
        </a>
        @endcan

        {{-- Divider & PRO Active Modules — permission-based (@can) --}}
        @if(auth()->user()->canAny(['pro.live_map', 'pro.work_orders', 'pro.advanced_reports', 'pro.evrak_tevdi']))
        <div class="my-3 border-t border-slate-700/50"></div>
        <p class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500">PRO Modüller</p>

        @foreach($proModulesActive as $mod)
            @if(isset($mod['perm']) && !auth()->user()->can($mod['perm']))
                @continue
            @endif
            @php $proActive = request()->routeIs($mod['match']); @endphp
            <a
                href="{{ route($mod['route']) }}"
                data-sidebar-close
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ $proActive ? 'bg-gradient-to-r from-[#FA6001]/25 to-transparent font-semibold text-white ring-1 ring-[#FA6001]/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white' }}"
            >
                <span class="{{ $proActive ? 'text-[#FA6001]' : 'opacity-55' }}">{!! $mod['icon'] !!}</span>
                <span class="flex-1 truncate">{{ $mod['label'] }}</span>
                @if(!empty($mod['pulse']))
                    <span class="relative flex h-2 w-2 flex-shrink-0 mr-1">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                @endif
                <span class="flex-shrink-0 rounded-full bg-gradient-to-r from-[#FA6001]/40 to-[#02E0FB]/30 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-orange-300">Pro</span>
            </a>
        @endforeach

        @endif {{-- end canAny PRO --}}

        {{-- Yakında gelecek — tıklanabilir PRO öneri modüller — yönetici/yetkili kullanıcılara göster --}}
        @if(!auth()->user()->hasRole('field-team'))
        <div class="my-2 border-t border-slate-700/30"></div>
        <p class="mb-1 px-3 text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-600">Yakında</p>

        @foreach($proComingSoon as $mod)
            <button type="button"
                onclick="Swal && Swal.fire({ icon:'info', title:'Çok Yakında!', html:'<p style=\'color:#94a3b8;font-size:.875rem\'>{{ $mod['label'] }} modülü geliştirme aşamasında. Lisansınıza eklemek için <strong style=\'color:#02E0FB\'>destek@hgbilisim.com</strong> ile iletişime geçin.</p>', confirmButtonColor:\'#02AFC6\', background:\'#0f172a\', color:\'#f1f5f9\' })"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-800/40 hover:text-slate-300">
                <span class="opacity-50 text-[#FA6001]">{!! $mod['icon'] !!}</span>
                <span class="flex-1 truncate">{{ $mod['label'] }}</span>
                <span class="flex-shrink-0 rounded-full bg-gradient-to-r from-[#FA6001]/30 to-[#02E0FB]/20 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-orange-400">Beta</span>
            </button>
        @endforeach

        @foreach($premiumModules as $mod)
            <button type="button"
                onclick="Swal && Swal.fire({ icon:'info', title:'Yakında!', text:'{{ $mod['label'] }} modülü yakında aktif edilecek. Lisans planınıza eklenmesi için destek@hgbilisim.com ile iletişime geçin.', confirmButtonColor:'#02AFC6' })"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-800/30 hover:text-slate-400">
                <span class="opacity-40">{!! $mod['icon'] !!}</span>
                <span class="flex-1 truncate">{{ $mod['label'] }}</span>
                <span class="flex-shrink-0 rounded-full bg-slate-700/40 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-slate-500">Soon</span>
            </button>
        @endforeach
        @endif {{-- end !field-team --}}
    </nav>

    {{-- Footer --}}
    <div class="border-t border-slate-700/50 px-4 py-3 text-[10px] text-slate-600">
        HGB Bilişim © {{ date('Y') }} · v6.21
    </div>
</aside>
