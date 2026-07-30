@extends('layouts.admin')

@section('page-heading', 'Rol Düzenle')

@section('content')
@php
    $roleLabels = [
        'super-admin'          => 'Super Admin',
        'municipality-admin'   => 'Belediye Yöneticisi',
        'municipality-staff'   => 'Belediye Personeli',
        'institution-manager'  => 'Kurum Yöneticisi',
        'institution-staff'    => 'Kurum Personeli',
        'field-team'           => 'Saha Personeli',
    ];

    // ── Standart (DB bağımlı) gruplar ────────────────────────────────────────
    $permGroups = [
        'SİSTEM' => [
            'system.license'  => 'Lisans Yönetimi',
            'system.logs'     => 'Sistem Logları',
            'system.settings' => 'Belge Ayarları',
        ],
        'BAŞVURULAR' => [
            'applications.view'            => 'Başvuruları Görüntüle',
            'applications.create'          => 'Başvuru Oluştur',
            'applications.edit'            => 'Başvuru Düzenle',
            'applications.delete'          => 'Başvuru Sil',
                    'applications.approve_pre_excavation' => 'Ön Kazı Onayla',
                    'applications.approve_price'   => 'Fiyat Onayla',
            'applications.approve_receipt' => 'Makbuz Onayla',
            'applications.issue_license'   => 'Ruhsat Düzenle',
            'tasks.transfer'               => 'Göreve Aktar',
            'licenses.manage'              => 'Lisans Kayıtları',
            'surface_types.manage'         => 'Zemin Türleri',
        ],
        'KURUMLAR & KULLANICIYLAR' => [
            'users.manage'           => 'Kullanıcı Yönetimi',
            'users.view_all_scoped'  => 'Alt Kurum Kullanıcılarını Yönet',
            'institutions.manage'    => 'Kurum Yönetimi',
        ],
        'SAHA' => [
            'field.tasks_view'   => 'Görevleri Gör',
            'field.upload_media' => 'Fotoğraf Yükle',
            'field.upload'       => 'Medya Yükle (legacy)',
        ],
    ];

    $groupColors = [
        'SİSTEM'                   => ['badge' => 'bg-orange-100 text-orange-700 border-orange-200',   'check' => 'accent-orange-500'],
        'BAŞVURULAR'               => ['badge' => 'bg-indigo-100 text-indigo-700 border-indigo-200',   'check' => 'accent-indigo-600'],
        'KURUMLAR & KULLANICIYLAR' => ['badge' => 'bg-purple-100 text-purple-700 border-purple-200',  'check' => 'accent-purple-600'],
        'SAHA'                     => ['badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200','check' => 'accent-emerald-600'],
    ];

    // ── 6 PRO Şalter — hardcoded, DB koşulundan bağımsız ─────────────────────
    $proPerms = [
        'pro.live_map'         => ['icon' => '📡', 'label' => 'Canlı Saha İzleme'],
        'pro.field_tracking'   => ['icon' => '📍', 'label' => 'Saha Check-in / Mesai'],
        'pro.work_orders'      => ['icon' => '📋', 'label' => 'Görev Emri & Kanban'],
        'pro.advanced_reports' => ['icon' => '📊', 'label' => 'Gelişmiş Rapor Motoru'],
        'pro.field_reports'    => ['icon' => '📈', 'label' => 'Saha Personel Analizi'],
        'pro.evrak_tevdi'      => ['icon' => '📑', 'label' => 'Evrak & Tevdi'],
    ];

    $selected     = old('permissions', $role->permissions->pluck('name')->all());
    $allPermNames = $permissions->pluck('name')->all();
    $proKeys      = array_keys($proPerms);
@endphp

<div class="max-w-3xl">
    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">{{ $roleLabels[$role->name] ?? $role->name }}</h2>
            <p class="text-sm text-slate-500">İzinleri düzenle — gruplu seçim</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">← Geri</a>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Rol Adı --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">Rol adı</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Permission Groups --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-700">İzinler</div>
                <div class="flex gap-2">
                    <button type="button" onclick="toggleAll(true)"
                        class="rounded-md bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                        Tümünü Seç
                    </button>
                    <button type="button" onclick="toggleAll(false)"
                        class="rounded-md bg-slate-50 border border-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Temizle
                    </button>
                </div>
            </div>

            <div class="space-y-4">

                {{-- ═══════════════════════════════════════════════════════════════
                     💎 PRO MODÜLLER — 6 Satılabilir Lisans Şalteri
                     Hardcoded: DB'de var olup olmadığından bağımsız HER ZAMAN görünür.
                     Kaydetme için migration'ın çalışmış olması gerekir (artık çalışıyor).
                ════════════════════════════════════════════════════════════════ --}}
                <div class="rounded-xl overflow-hidden border border-cyan-400/50 shadow-[0_0_40px_rgba(2,224,251,0.20),0_0_12px_rgba(2,224,251,0.10)]">
                    {{-- Dark gradient header --}}
                    <div class="flex items-center justify-between bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border-b border-cyan-500/30 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-gradient-to-br from-[#02E0FB] to-[#FA6001] text-[10px] font-black text-white shadow">M</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/50 bg-cyan-500/20 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-widest text-cyan-300">
                                💎 PRO MODÜLLER
                            </span>
                            <span class="rounded-full bg-orange-500/20 border border-orange-400/30 px-1.5 py-px text-[9px] font-bold text-orange-300">6 Özellik</span>
                        </div>
                        <button type="button" onclick="toggleGroup('group-pro-modules')"
                            class="text-[10px] font-semibold text-cyan-400 hover:text-white transition">
                            Tümünü Seç/Kaldır
                        </button>
                    </div>
                    {{-- 6 checkboxes — 3 sütun --}}
                    <div id="group-pro-modules" class="grid grid-cols-1 gap-px bg-[#02E0FB]/5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($proPerms as $permKey => $meta)
                        <label class="group/pro flex cursor-pointer items-center gap-3 bg-slate-950/70 px-4 py-3.5 transition-all hover:bg-slate-900/90 hover:shadow-inner">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permKey }}"
                                   class="perm-checkbox h-4 w-4 flex-shrink-0 rounded border-slate-600 accent-cyan-500"
                                   @checked(in_array($permKey, $selected, true))>
                            <div class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-100 leading-tight">
                                    {{ $meta['icon'] }} {{ $meta['label'] }}
                                </span>
                                <span class="block font-mono text-[10px] text-cyan-400/60 truncate mt-0.5">{{ $permKey }}</span>
                            </div>
                            <span class="flex-shrink-0 rounded-full border border-cyan-400/25 bg-gradient-to-r from-[#02E0FB]/20 to-[#FA6001]/15 px-1.5 py-px text-[9px] font-bold uppercase tracking-wide text-orange-300">
                                PRO
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                {{-- ═══════════════════════════════════════════════════════════════ --}}

                {{-- Standart gruplar (DB kayıtlarına bağlı) --}}
                @foreach($permGroups as $groupName => $perms)
                    @php $gc = $groupColors[$groupName] ?? ['badge' => 'bg-slate-100 text-slate-600 border-slate-200', 'check' => 'accent-slate-600']; @endphp
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="flex items-center justify-between bg-slate-50 px-4 py-2.5 border-b border-slate-200">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-widest {{ $gc['badge'] }}">
                                {{ $groupName }}
                            </span>
                            <button type="button"
                                onclick="toggleGroup('group-{{ Str::slug($groupName) }}')"
                                class="text-[10px] font-semibold text-slate-400 hover:text-slate-600 transition">
                                Tümünü Seç/Kaldır
                            </button>
                        </div>
                        <div id="group-{{ Str::slug($groupName) }}" class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
                            @foreach($perms as $permName => $permLabel)
                                @if(in_array($permName, $allPermNames, true))
                                    <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-3 hover:bg-slate-50 transition-colors">
                                        <input type="checkbox" name="permissions[]" value="{{ $permName }}"
                                            class="perm-checkbox h-4 w-4 rounded border-slate-300 {{ $gc['check'] }} flex-shrink-0"
                                            @checked(in_array($permName, $selected, true))>
                                        <div class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-800">{{ $permLabel }}</span>
                                            <span class="block font-mono text-[10px] text-slate-400 truncate">{{ $permName }}</span>
                                        </div>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Tanınmayan izinler (PRO anahtarları hariç) --}}
                @php
                    $listedPerms = collect($permGroups)->flatMap(fn ($g) => array_keys($g))->merge($proKeys)->all();
                    $extra = $permissions->filter(fn ($p) => !in_array($p->name, $listedPerms));
                @endphp
                @if($extra->isNotEmpty())
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">DİĞER</span>
                        </div>
                        <div class="grid grid-cols-1 gap-px bg-slate-100 sm:grid-cols-2">
                            @foreach($extra as $p)
                                <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-3 hover:bg-slate-50">
                                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}"
                                        class="perm-checkbox h-4 w-4 rounded border-slate-300 flex-shrink-0"
                                        @checked(in_array($p->name, $selected, true))>
                                    <span class="font-mono text-xs text-slate-700">{{ $p->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">İptal</a>
            <button type="submit" class="rounded-lg bg-emerald-700 px-6 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Güncelle</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleAll(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
}
function toggleGroup(groupId) {
    const group = document.getElementById(groupId);
    if (!group) return;
    const boxes = group.querySelectorAll('.perm-checkbox');
    const allChecked = Array.from(boxes).every(cb => cb.checked);
    boxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endpush
