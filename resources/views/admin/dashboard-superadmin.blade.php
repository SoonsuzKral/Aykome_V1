@extends('layouts.admin')

@section('page-heading', 'Platform Genel Bakış')

@section('content')
    {{-- ── KPI Kartları ────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Toplam Lisans --}}
        <div class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#02E0FB]/10">
                <svg class="h-5 w-5 text-[#02AFC6]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.17-.789 3.376 3.376 0 016.34 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Toplam Lisans</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">{{ $stats['total'] }}</p>
                <p class="mt-0.5 text-xs text-emerald-600">{{ $stats['active'] }} aktif</p>
            </div>
        </div>

        {{-- Kritik Uyarı --}}
        <div class="flex items-start gap-4 rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-amber-700">30 Günde Bitecek</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-amber-800">{{ $stats['expiring_soon'] }}</p>
                <p class="mt-0.5 text-xs text-amber-600">{{ $stats['expired'] }} süresi dolmuş</p>
            </div>
        </div>

        {{-- Toplam Kullanıcı --}}
        <div class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#FA6001]/10">
                <svg class="h-5 w-5 text-[#FA6001]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Toplam Kullanıcı</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">{{ $stats['total_users'] }}</p>
                <p class="mt-0.5 text-xs text-slate-400">Tüm kurumlar</p>
            </div>
        </div>

        {{-- Toplam Gelir --}}
        <div class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Toplam Tahsilat</p>
                <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900">₺{{ number_format($stats['revenue'], 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs text-slate-400">{{ $stats['applications'] }} başvuru</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ── Kritik Lisanslar (Sol, 2/3) ─────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            @if($criticalLicenses->isNotEmpty())
            <div class="rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-amber-100 px-5 py-4">
                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <h2 class="text-sm font-semibold text-amber-800">Yakında Bitecek Lisanslar (30 gün)</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs text-slate-500">
                            <th class="px-5 py-2 font-medium">Lisans Anahtarı</th>
                            <th class="px-5 py-2 font-medium">Firma</th>
                            <th class="px-5 py-2 font-medium text-right">Bitiş</th>
                            <th class="px-5 py-2 font-medium text-right">Kalan</th>
                            <th class="px-5 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($criticalLicenses as $lic)
                            @php $daysLeft = now()->diffInDays($lic->valid_until, false); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $lic->license_key }}</td>
                                <td class="px-5 py-3 text-slate-800">{{ $lic->owner_name }}</td>
                                <td class="px-5 py-3 text-right text-slate-600">{{ \Carbon\Carbon::parse($lic->valid_until)->format('d.m.Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $daysLeft <= 7 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $daysLeft }} gün
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.licenses.edit', $lic) }}" class="text-xs font-medium text-[#02AFC6] hover:underline">Yenile</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($expiredLicenses->isNotEmpty())
            <div class="rounded-xl border border-red-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-red-100 px-5 py-4">
                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <h2 class="text-sm font-semibold text-red-700">Süresi Dolmuş Lisanslar</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs text-slate-500">
                            <th class="px-5 py-2 font-medium">Lisans Anahtarı</th>
                            <th class="px-5 py-2 font-medium">Firma</th>
                            <th class="px-5 py-2 font-medium text-right">Doldu</th>
                            <th class="px-5 py-2 font-medium text-right">Durum</th>
                            <th class="px-5 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($expiredLicenses as $lic)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-500 line-through">{{ $lic->license_key }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $lic->owner_name }}</td>
                                <td class="px-5 py-3 text-right text-red-600">{{ \Carbon\Carbon::parse($lic->valid_until)->format('d.m.Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                        {{ $lic->is_active ? 'Süre Doldu' : 'Kilitli' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.licenses.edit', $lic) }}" class="text-xs font-medium text-[#FA6001] hover:underline">Yenile</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Son Başvurular --}}
            @php
                $superStatusMeta = [
                    'draft' => ['label' => 'Taslak', 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />'],
                    'submitted' => ['label' => 'Gönderildi', 'class' => 'bg-sky-100 text-sky-700 ring-sky-300', 'icon' => '<path d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5" />'],
                    'pre_excavation_approved' => ['label' => 'Ön Kazı Onaylı', 'class' => 'bg-cyan-100 text-cyan-700 ring-cyan-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />'],
                    'priced' => ['label' => 'Fiyatlandı', 'class' => 'bg-indigo-100 text-indigo-700 ring-indigo-300', 'icon' => '<path d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />'],
                    'awaiting_payment' => ['label' => 'Ödeme Bekliyor', 'class' => 'bg-amber-100 text-amber-700 ring-amber-300', 'icon' => '<path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'receipt_pending' => ['label' => 'Makbuz Bekliyor', 'class' => 'bg-orange-100 text-orange-700 ring-orange-300', 'icon' => '<path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />'],
                    'approved' => ['label' => 'Onaylandı', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'rejected' => ['label' => 'Reddedildi', 'class' => 'bg-red-100 text-red-700 ring-red-300', 'icon' => '<path d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'licensed' => ['label' => 'Ruhsatlı', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />'],
                    'field_work' => ['label' => 'Saha İşi', 'class' => 'bg-purple-100 text-purple-700 ring-purple-300', 'icon' => '<path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />'],
                    'completed' => ['label' => 'Tamamlandı', 'class' => 'bg-teal-100 text-teal-700 ring-teal-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'archived' => ['label' => 'Arşiv', 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 015.25 3.75h9.75A2.25 2.25 0 0117.25 6v3.776" />'],
                ];
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-800">Son Başvurular (Platform Geneli)</h2>
                    <a href="{{ route('admin.applications.index') }}" class="text-xs text-[#02AFC6] hover:underline">Tümü →</a>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs text-slate-500">
                            <th class="px-5 py-2 font-medium">No</th>
                            <th class="px-5 py-2 font-medium">Kurum</th>
                            <th class="px-5 py-2 font-medium">Durum</th>
                            <th class="px-5 py-2 font-medium text-right">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentApplications as $app)
                            @php
                                $sv = $app->status instanceof \BackedEnum ? $app->status->value : (string) $app->status;
                                $sm = $superStatusMeta[$sv] ?? ['label' => $sv, 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'];
                            @endphp
                            <tr class="transition hover:bg-gradient-to-r hover:from-cyan-50/50 hover:to-transparent">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.applications.show', $app) }}" class="font-mono text-xs font-medium text-[#02AFC6] hover:underline">{{ $app->application_no }}</a>
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $app->institution->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $sm['class'] }}">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            {!! $sm['icon'] !!}
                                        </svg>
                                        {{ $sm['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-slate-500">{{ $app->created_at->format('d.m.Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-4 text-sm text-slate-500">Henüz başvuru yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Tüm Lisanslar Özet (Sağ, 1/3) ──────────────────────────────── --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">Tüm Lisanslar</h2>
                        <a href="{{ route('admin.licenses.index') }}" class="text-xs text-[#02AFC6] hover:underline">Yönet →</a>
                    </div>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($allLicenses as $lic)
                        @php
                            $now2 = now();
                            $until = \Carbon\Carbon::parse($lic->valid_until);
                            if (!$lic->is_active) { $dot = 'bg-slate-400'; $label = 'Kilitli'; }
                            elseif ($until < $now2) { $dot = 'bg-red-500'; $label = 'Süre Doldu'; }
                            elseif ($until <= $now2->copy()->addDays(30)) { $dot = 'bg-amber-400'; $label = 'Kritik'; }
                            else { $dot = 'bg-emerald-500'; $label = 'Aktif'; }
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-slate-800">{{ $lic->owner_name }}</p>
                                <p class="truncate font-mono text-[10px] text-slate-400">{{ $lic->license_key }}</p>
                            </div>
                            <span class="shrink-0 text-[10px] font-semibold {{ $dot === 'bg-red-500' ? 'text-red-600' : ($dot === 'bg-amber-400' ? 'text-amber-600' : ($dot === 'bg-slate-400' ? 'text-slate-500' : 'text-emerald-600')) }}">
                                {{ $label }}
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-slate-500">Lisans bulunamadı.</p>
                    @endforelse
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    <a href="{{ route('admin.licenses.create') }}" class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#02E0FB]/10 py-2 text-xs font-semibold text-[#02AFC6] hover:bg-[#02E0FB]/20 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Yeni Lisans Sat
                    </a>
                </div>
            </div>

            {{-- Durum Özeti --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Durum Özeti</h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Aktif</span>
                        <span class="font-semibold text-emerald-600">{{ $stats['active'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Yakında Bitiyor</span>
                        <span class="font-semibold text-amber-600">{{ $stats['expiring_soon'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Süresi Dolmuş</span>
                        <span class="font-semibold text-red-600">{{ $stats['expired'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Manuel Kilitli</span>
                        <span class="font-semibold text-slate-500">{{ $stats['locked'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
