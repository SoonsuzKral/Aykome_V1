@extends('layouts.admin')

@section('page-heading', 'Evrak & Makam Ayarları')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">📜 Evrak & Makam Ayarları</h1>
        <p class="mt-1 text-sm text-slate-500">PDF çıktılarında kullanılan imzacı/makam isimlerini buradan merkezi yönetin. Tüm evraklar (Ruhsat, Metraj, Ön Kazı vb.) buradaki ayarlardan okunur.</p>
    </div>

    {{-- Kurum Sekmeleri --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.document-settings.index') }}"
           class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $scope ? 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'bg-slate-900 text-white shadow-sm' }}">
            🏛️ Merkez (Global)
        </a>
        @foreach($institutions as $inst)
            <a href="{{ route('admin.document-settings.index', ['institution_id' => $inst->id]) }}"
               class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $scope && $scope->id === $inst->id ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $inst->name }} @if($inst->is_municipality)<span class="text-[10px] opacity-60">BELEDİYE</span>@endif
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Yeni İmzacı Ekle --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">＋</span>
                    Yeni İmzacı Ekle
                </h2>
                <form method="POST" action="{{ route('admin.document-settings.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Kurum</label>
                        <select name="institution_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                            <option value="">Merkez (Global)</option>
                            @foreach($institutions as $inst)
                                <option value="{{ $inst->id }}" @selected($scope && $scope->id === $inst->id)>{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Evrak Tipi</label>
                        <select name="document_type" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                            @foreach($documentTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Rol / Makam</label>
                        <select name="role_key" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                            <option value="">— Özel rol yok —</option>
                            @foreach($roleKeys as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Unvan</label>
                        <input type="text" name="unvan" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200" placeholder="Fen İşleri Müdürü">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">İsim Soyisim</label>
                        <input type="text" name="ad_soyad" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200" placeholder="Burak Bakır Yücetepe">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Sıra</label>
                        <input type="number" name="sort" value="0" min="0" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">
                        💾 Kaydet
                    </button>
                </form>
            </div>
        </div>

        {{-- İmzacı Listesi --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-800">
                        {{ $scope ? $scope->name : 'Merkez (Global) Imzacıları' }}
                        <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $settings->count() }} kayıt</span>
                    </h2>
                </div>

                @if($settings->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-slate-400">
                        Bu kapsam için henüz imzacı ayarı yok.
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Evrak Tipi</th>
                                <th class="px-3 py-3">Rol</th>
                                <th class="px-3 py-3">Unvan</th>
                                <th class="px-3 py-3">İsim Soyisim</th>
                                <th class="px-3 py-3">Durum</th>
                                <th class="px-5 py-3 text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($settings as $setting)
                            <tr class="align-top hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <span class="rounded-md bg-cyan-50 px-2 py-1 text-[11px] font-bold text-cyan-700">{{ $documentTypes[$setting->document_type] ?? $setting->document_type }}</span>
                                </td>
                                <td class="px-3 py-3 text-xs font-medium text-slate-600">{{ $roleKeys[$setting->role_key] ?? ($setting->role_key ?: '—') }}</td>
                                <td class="px-3 py-3">
                                    <form method="POST" action="{{ route('admin.document-settings.update', $setting) }}" class="flex flex-col gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="unvan" value="{{ $setting->unvan }}" class="rounded-md border border-slate-200 px-2 py-1 text-xs focus:border-cyan-400 focus:outline-none">
                                        <input type="text" name="ad_soyad" value="{{ $setting->ad_soyad }}" class="rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-800 focus:border-cyan-400 focus:outline-none">
                                        <input type="number" name="sort" value="{{ $setting->sort }}" min="0" class="w-16 rounded-md border border-slate-200 px-2 py-1 text-xs focus:border-cyan-400 focus:outline-none">
                                        <div class="mt-1 flex items-center gap-2">
                                            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-slate-700">Kaydet</button>
                                            <label class="flex cursor-pointer items-center gap-1 text-[11px] text-slate-500">
                                                <input type="checkbox" name="is_active" value="1" @checked($setting->is_active) onchange="this.form.submit()" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"> Aktif
                                            </label>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-3 py-3">
                                    <form method="POST" action="{{ route('admin.document-settings.destroy', $setting) }}" onsubmit="return confirm('Bu imzacı ayarını silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                💡 <b>Öncelik:</b> Kuruma özel imzacı ayarı varsa o kullanılır, yoksa Merkez (Global) ayar kullanılır. Hiçbiri yoksa başvuru/kurum alanlarındaki yetkili bilgisi gösterilir.
            </div>
        </div>
    </div>
@endsection
