@extends('layouts.admin')

@section('title', 'Süreç ve Onay Rotası — Yönetim')

@section('content')
    {{-- Üst Başlık & Form Alanı --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-slate-900">⚙️ Süreç ve Onay Rotası</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Onay silsilesini burada kurarsınız. Her adım; <b>Adım Yetkilisi (Rol)</b> ve
                o adımın <b>karışabildiği modüllerini</b> içerir. Başvurular bu rotaya göre adım adım ilerler;
                rolü rotada olmayan kullanıcı onay tuşunu göremez.
                <span class="font-semibold text-slate-700">Sadece belediye merkez yönetimi</span> erişebilir.
            </p>
        </div>

        {{-- Yeni Süreç Formu --}}
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-800">＋ Yeni Süreç Ekle</h2>
            <form method="POST" action="{{ route('admin.processes.store-definition') }}" class="space-y-2.5">
                @csrf
                <input type="text" name="name" required maxlength="190" placeholder="Süreç adı (örn. Ön Kazı Onay Silsilesi)"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                <input type="text" name="description" maxlength="500" placeholder="Açıklama (isteğe bağlı)"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    Bu süreci varsayılan aktif süreç yap
                </label>
                <button type="submit" class="w-full rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">
                    Süreci Oluştur
                </button>
            </form>
        </div>
    </div>

    {{-- Süreç Listesi --}}
    <div class="grid gap-6 xl:grid-cols-2">
        @forelse($processes as $process)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm {{ $process->is_active ? '' : 'opacity-80' }}">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-800">{{ $process->name }}</h2>
                            @if($process->is_default)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Varsayılan</span>
                            @endif
                            @if(! $process->is_active)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">Pasif</span>
                            @endif
                        </div>
                        @if($process->description)
                            <p class="mt-0.5 text-xs text-slate-500">{{ $process->description }}</p>
                        @endif
                        <p class="mt-0.5 text-[11px] text-slate-400">slug: {{ $process->slug }} · {{ $process->steps()->count() }} adım</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.processes.blueprint', $process) }}"
                           class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-[11px] font-bold text-cyan-700 hover:bg-cyan-100">
                            🎨 Visual Editor
                        </a>
                        @if(! $process->is_default)
                            <form method="POST" action="{{ route('admin.processes.set-default', $process) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-[11px] font-bold text-emerald-700 hover:bg-emerald-50">Varsayılan Yap</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.processes.toggle-active', $process) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50">
                                {{ $process->is_active ? 'Pasife Al' : 'Aktifleştir' }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Adımlar --}}
                <div class="divide-y divide-slate-100">
                    @forelse($process->steps as $step)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-slate-900 text-[11px] font-bold text-white">{{ $step->step_order }}</span>
                                        <span class="text-sm font-semibold text-slate-800">{{ $step->name }}</span>
                                        <span class="rounded-md bg-cyan-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-cyan-700">{{ $step->role_key }}</span>
                                        @if(! $step->is_active)
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">PASİF</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach(($step->roles ?? []) as $role)
                                            <span class="rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">{{ $role }}</span>
                                        @endforeach
                                    </div>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        @foreach(($step->approvable_modules ?? []) as $module)
                                            <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                                ✅ {{ $moduleOptions[$module] ?? $module }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-1.5">
                                    @if(! $loop->first)
                                        <form method="POST" action="{{ route('admin.processes.reorder-step', [$step, 'up']) }}">
                                            @csrf
                                            <button type="submit" title="Yukarı taşı" class="rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-500 hover:bg-slate-50">↑</button>
                                        </form>
                                    @endif
                                    @if(! $loop->last)
                                        <form method="POST" action="{{ route('admin.processes.reorder-step', [$step, 'down']) }}">
                                            @csrf
                                            <button type="submit" title="Aşağı taşı" class="rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-500 hover:bg-slate-50">↓</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.processes.destroy-step', $step) }}" onsubmit="return confirm('Bu adımı silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50">Sil</button>
                                    </form>
                                </div>
                            </div>

                            {{-- Düzenle --}}
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs font-semibold text-cyan-700 hover:text-cyan-800">✏️ Adımı Düzenle</summary>
                                <form method="POST" action="{{ route('admin.processes.update-step', $step) }}" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Adı</label>
                                            <input type="text" name="name" value="{{ $step->name }}" required maxlength="190" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Anahtarı (role_key)</label>
                                            <input type="text" name="role_key" value="{{ $step->role_key }}" required maxlength="50" pattern="[a-zA-Z0-9_-]+" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Yetkilisi (Rol / Roller)</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($roleOptions as $key => $label)
                                                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium {{ in_array($key, $step->roles ?? [], true) ? 'border-violet-300 bg-violet-50 text-violet-800' : 'border-slate-200 bg-white text-slate-600' }}">
                                                    <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $step->roles ?? [], true)) class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-1 block text-[11px] font-bold text-slate-500">Karışabildiği Modüller (Ne Onaylar?)</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($moduleOptions as $key => $label)
                                                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium {{ in_array($key, $step->approvable_modules ?? [], true) ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600' }}">
                                                    <input type="checkbox" name="approvable_modules[]" value="{{ $key }}" @checked(in_array($key, $step->approvable_modules ?? [], true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center gap-3">
                                        <label class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($step->is_active) class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"> Aktif adım
                                        </label>
                                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700">Güncelle</button>
                                    </div>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-400">
                            Bu sürece henüz adım eklenmedi.
                        </div>
                    @endforelse
                </div>

                {{-- Adım Ekle --}}
                <details class="border-t border-slate-100 px-5 py-4">
                    <summary class="cursor-pointer text-sm font-bold text-slate-700 hover:text-slate-900">＋ Bu Sürece Adım Ekle</summary>
                    <form method="POST" action="{{ route('admin.processes.store-step') }}" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        @csrf
                        <input type="hidden" name="process_definition_id" value="{{ $process->id }}">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Adı</label>
                                <input type="text" name="name" required maxlength="190" placeholder="örn. Fen İşleri Müdürü Onayı" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Anahtarı (role_key)</label>
                                <input type="text" name="role_key" required maxlength="50" pattern="[a-zA-Z0-9_-]+" placeholder="örn. mudur" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Yetkilisi — Seç: [Rol]</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($roleOptions as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:border-violet-300">
                                        <input type="checkbox" name="roles[]" value="{{ $key }}" class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Karışabildiği Modüller — Hangi Adıma Karışır / Ne Görüntüler? [Onayla Modülü]</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($moduleOptions as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:border-emerald-300">
                                        <input type="checkbox" name="approvable_modules[]" value="{{ $key }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="mt-4 w-full rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Adımı Ekle</button>
                    </form>
                </details>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <p class="text-sm text-slate-500">Henüz süreç tanımlanmamış.</p>
                <p class="mt-1 text-xs text-slate-400">Yukarıdaki formdan ilk süreci oluşturun, ardından silsile adımlarını ekleyin.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        💡 <b>Hiyerarşi kuralı:</b> En tepedeki makamlar (Super Admin, Belediye Yöneticisi, Makam rolü) tüm adımları onaylayabilir.
        Alt roller yalnızca kendilerine atanan adımlardaki onay tuşunu görür. Başvurular bu rotaya göre statü atlar.
    </div>
@endsection
