@extends('layouts.admin')

@section('page-heading', 'God-Mode: Rol & Yetki Matrisi')

@section('content')

@php
    use Spatie\Permission\Models\Role as SpatieRole;
    use Spatie\Permission\Models\Permission as SpatiePermission;

    $allRoles = SpatieRole::with('permissions')->orderBy('name')->get();
    $rolePermMap = $allRoles->mapWithKeys(fn ($r) => [
        $r->name => $r->permissions->pluck('name')->flip(),
    ]);

    $permGroups = [
        'SİSTEM'                => [
            'system.license'   => 'Lisans Yönetimi',
            'system.logs'      => 'Sistem Logları',
            'system.settings'  => 'Belge Ayarları',
        ],
        'PRO MODÜLLER'          => [
            'pro.live_map'         => 'Canlı Saha İzleme',
            'pro.work_orders'      => 'Görev Emri Yönetimi',
            'pro.advanced_reports' => 'Gelişmiş Raporlama',
        ],
        'BAŞVURULAR'            => [
            'applications.view'           => 'Görüntüle',
            'applications.create'         => 'Oluştur',
            'applications.edit'           => 'Düzenle',
            'applications.delete'         => 'Sil',
                'applications.approve_pre_excavation' => 'Ön Kazı Onayla',
                'applications.approve_price'  => 'Fiyat Onayla',
            'applications.approve_receipt'=> 'Makbuz Onayla',
            'applications.issue_license'  => 'Ruhsat Düzenle',
            'tasks.transfer'              => 'Göreve Aktar',
            'licenses.manage'             => 'Lisans Kayıtları',
            'surface_types.manage'        => 'Zemin Türleri',
        ],
        'KURUMLAR & KULLANICIYLAR' => [
            'users.manage'       => 'Kullanıcı Yönetimi',
            'institutions.manage'=> 'Kurum Yönetimi',
        ],
        'SAHA'                  => [
            'field.tasks_view'   => 'Görevleri Gör',
            'field.upload_media' => 'Fotoğraf Yükle',
            'field.upload'       => 'Medya Yükle (legacy)',
        ],
    ];

    $groupColors = [
        'SİSTEM'                   => ['dot' => '#FA6001', 'bg' => 'bg-orange-500/10', 'text' => 'text-orange-300', 'border' => 'border-orange-500/20'],
        'PRO MODÜLLER'             => ['dot' => '#02E0FB', 'bg' => 'bg-cyan-500/10',   'text' => 'text-cyan-300',   'border' => 'border-cyan-500/20'],
        'BAŞVURULAR'               => ['dot' => '#6366f1', 'bg' => 'bg-indigo-500/10', 'text' => 'text-indigo-300', 'border' => 'border-indigo-500/20'],
        'KURUMLAR & KULLANICIYLAR' => ['dot' => '#a855f7', 'bg' => 'bg-purple-500/10', 'text' => 'text-purple-300', 'border' => 'border-purple-500/20'],
        'SAHA'                     => ['dot' => '#10b981', 'bg' => 'bg-emerald-500/10','text' => 'text-emerald-300','border' => 'border-emerald-500/20'],
    ];

    $roleLabels = [
        'super-admin'          => ['label' => 'Super Admin',        'badge' => 'SA',  'color' => 'text-violet-300  bg-violet-500/15 border-violet-500/30'],
        'municipality-admin'   => ['label' => 'Bel. Yöneticisi',    'badge' => 'MA',  'color' => 'text-blue-300    bg-blue-500/15   border-blue-500/30'],
        'municipality-staff'   => ['label' => 'Bel. Personeli',     'badge' => 'MS',  'color' => 'text-sky-300     bg-sky-500/15    border-sky-500/30'],
        'institution-manager'  => ['label' => 'Kurum Yöneticisi',   'badge' => 'IM',  'color' => 'text-teal-300    bg-teal-500/15   border-teal-500/30'],
        'institution-staff'    => ['label' => 'Kurum Personeli',    'badge' => 'IS',  'color' => 'text-cyan-300    bg-cyan-500/15   border-cyan-500/30'],
        'field-team'           => ['label' => 'Saha Personeli',     'badge' => 'FT',  'color' => 'text-emerald-300 bg-emerald-500/15 border-emerald-500/30'],
    ];
@endphp

{{-- Header --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-semibold text-slate-900">Roller & Yetkiler</h1>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-[#FA6001]/20 to-[#02E0FB]/15 border border-[#FA6001]/30 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-[#FA6001]">
            ⚡ God-Mode
        </span>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
        + Yeni Rol
    </a>
</div>

@if(session('success'))
<div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700">
    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Role Summary Cards --}}
<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    @foreach($allRoles as $role)
        @php
            $rl = $roleLabels[$role->name] ?? ['label' => $role->name, 'badge' => strtoupper(substr($role->name,0,2)), 'color' => 'text-slate-300 bg-slate-500/15 border-slate-500/30'];
        @endphp
        <div class="rounded-xl border {{ $rl['color'] }} p-3 text-center">
            <div class="mx-auto mb-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-current/10 text-xs font-black">{{ $rl['badge'] }}</div>
            <p class="text-xs font-semibold leading-tight">{{ $rl['label'] }}</p>
            <p class="mt-1 text-[10px] opacity-70">{{ $role->permissions->count() }} izin</p>
            <a href="{{ route('admin.roles.edit', $role) }}" class="mt-2 inline-block rounded-md bg-current/10 px-2 py-0.5 text-[10px] font-semibold hover:bg-current/20 transition">Düzenle</a>
        </div>
    @endforeach
</div>

{{-- Permission Matrix --}}
<div class="overflow-x-auto rounded-2xl border border-slate-700/60 bg-slate-900 shadow-xl">
    <div class="border-b border-slate-700/50 px-5 py-3 flex items-center gap-2">
        <span class="text-base font-bold text-white">Yetki Matrisi</span>
        <span class="text-xs text-slate-500">— tüm roller ve izinler</span>
    </div>

    <table class="w-full text-xs">
        <thead>
            <tr class="border-b border-slate-700/50">
                <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-widest text-slate-500 w-48">İzin</th>
                @foreach($allRoles as $role)
                    @php $rl = $roleLabels[$role->name] ?? ['label' => $role->name, 'badge' => strtoupper(substr($role->name,0,2)), 'color' => 'text-slate-300 bg-slate-500/15 border-slate-500/30']; @endphp
                    <th class="px-3 py-3 text-center">
                        <span class="inline-block rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide {{ $rl['color'] }}">
                            {{ $rl['badge'] }}
                        </span>
                        <div class="mt-0.5 text-[9px] text-slate-500 font-normal leading-tight">{{ $rl['label'] }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($permGroups as $groupName => $perms)
                @php $gc = $groupColors[$groupName] ?? ['dot' => '#888', 'bg' => 'bg-slate-800', 'text' => 'text-slate-400', 'border' => 'border-slate-700/30']; @endphp
                {{-- Group Header --}}
                <tr class="border-t border-slate-700/40 {{ $gc['bg'] }}">
                    <td colspan="{{ $allRoles->count() + 1 }}" class="px-4 py-2">
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-[0.15em] {{ $gc['text'] }}">
                            <span class="h-1.5 w-1.5 rounded-full" style="background:{{ $gc['dot'] }}"></span>
                            {{ $groupName }}
                        </span>
                    </td>
                </tr>
                @foreach($perms as $permName => $permLabel)
                    <tr class="border-t border-slate-800/80 hover:bg-slate-800/40 transition-colors">
                        <td class="px-4 py-2.5 font-mono text-slate-400">
                            <span class="text-slate-600 text-[10px]">{{ $permName }}</span>
                            <div class="text-[11px] font-semibold text-slate-300">{{ $permLabel }}</div>
                        </td>
                        @foreach($allRoles as $role)
                            @php $has = isset($rolePermMap[$role->name][$permName]); @endphp
                            <td class="px-3 py-2.5 text-center">
                                @if($has)
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @else
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-700">
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="border-t border-slate-700/40 px-5 py-3 flex items-center justify-between">
        <p class="text-[10px] text-slate-600">
            Toplam {{ SpatiePermission::count() }} izin · {{ $allRoles->count() }} rol · Super Admin tüm izinlere sahip
        </p>
        <div class="flex items-center gap-4 text-[10px] text-slate-500">
            <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500/50 inline-block"></span> Yetkili</span>
            <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-slate-700 inline-block"></span> Yetkisiz</span>
        </div>
    </div>
</div>

{{-- Paginated Role List (alt kısım) --}}
<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-slate-500">Rol Listesi & Düzenleme</div>
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-slate-600">Rol</th>
                <th class="px-4 py-3 text-left font-medium text-slate-600">İzin sayısı</th>
                <th class="px-4 py-3 text-right"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($roles as $roleRow)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $roleLabels[$roleRow->name]['label'] ?? $roleRow->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $roleRow->permissions_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.roles.edit', $roleRow) }}" class="text-emerald-700 hover:underline">Düzenle</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="border-t border-slate-100 px-4 py-3">{{ $roles->links() }}</div>
</div>
@endsection
