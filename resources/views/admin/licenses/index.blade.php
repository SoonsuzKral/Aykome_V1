@extends('layouts.admin')

@section('page-heading', 'Firmalar & Lisanslar')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Firmalar & Lisanslar</h1>
            <p class="mt-0.5 text-sm text-slate-500">Aktif kiracılar, bitiş tarihleri ve hızlı yenileme.</p>
        </div>
        <a href="{{ route('admin.licenses.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 transition">
            + Yeni Lisans
        </a>
    </div>

    {{-- KPI strip --}}
    @php
        $allLicenses = $licenses->getCollection();
        $kpi = [
            'active'   => $allLicenses->filter(fn($l) => $l->is_active && $l->valid_until?->isFuture() && !$l->valid_until?->lessThanOrEqualTo(now()->addDays(30)))->count(),
            'warning'  => $allLicenses->filter(fn($l) => $l->is_active && $l->valid_until?->isFuture() && $l->valid_until?->lessThanOrEqualTo(now()->addDays(30)))->count(),
            'expired'  => $allLicenses->filter(fn($l) => !$l->is_active || $l->valid_until?->isPast())->count(),
        ];
    @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center shadow-sm">
            <p class="text-2xl font-black text-emerald-700">{{ $kpi['active'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Aktif</p>
        </div>
        <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-center shadow-sm">
            <p class="text-2xl font-black text-amber-600">{{ $kpi['warning'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">⚠ 30 Gün İçinde</p>
        </div>
        <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-center shadow-sm">
            <p class="text-2xl font-black text-rose-600">{{ $kpi['expired'] }}</p>
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">🔴 Süresi Doldu</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Lisans Anahtarı</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Sahip / Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Kurum</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Bitiş</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Durum</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($licenses as $license)
                    @php
                        $until  = $license->valid_until;
                        $isExp  = !$license->is_active || ($until && $until->isPast());
                        $isWarn = $license->is_active && $until && $until->isFuture() && $until->lessThanOrEqualTo(now()->addDays(30));
                        $isOk   = $license->is_active && $until && $until->isFuture() && !$isWarn;

                        $rowBg  = $isExp  ? 'bg-rose-50/40'  : ($isWarn ? 'bg-amber-50/40' : '');
                        $status = $isExp
                            ? '<span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">🔴 Süresi Doldu</span>'
                            : ($isWarn
                                ? '<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">⚠️ '.$until->diffForHumans().'</span>'
                                : '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">✅ Aktif</span>');
                    @endphp
                    <tr class="hover:bg-slate-50/80 {{ $rowBg }} transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ str($license->license_key)->limit(28) }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $license->owner_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $license->institution?->name ?? '—' }}</td>
                        <td class="px-4 py-3 {{ $isExp ? 'font-bold text-rose-600' : ($isWarn ? 'font-semibold text-amber-600' : 'text-slate-600') }}">
                            {{ $until?->format('d.m.Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">{!! $status !!}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Hızlı Yenile --}}
                                <form method="POST" action="{{ route('admin.licenses.renew', $license) }}"
                                      onsubmit="return confirm('{{ addslashes($license->owner_name) }} lisansını 1 yıl uzatmak istediğinize emin misiniz?')">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg {{ $isExp ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700' }} px-2.5 py-1 text-xs font-semibold transition">
                                        +1 Yıl
                                    </button>
                                </form>
                                {{-- Süresini Bitir --}}
                                @if($license->is_active)
                                <form method="POST" action="{{ route('admin.licenses.kill', $license) }}"
                                      onsubmit="return confirm('{{ addslashes($license->owner_name) }} lisansını ANINDA DURDURMAK istediğinize emin misiniz? Kullanıcılar sisteme giremez!')">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-100 hover:text-rose-700 border border-rose-200 transition">
                                        Durdur
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('admin.licenses.edit', $license) }}"
                                   class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                                    Düzenle
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">Henüz lisans kaydı yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $licenses->links() }}</div>
    </div>

</div>
@endsection
