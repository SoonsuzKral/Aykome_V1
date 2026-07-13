@extends('layouts.admin')

@section('page-heading', 'Gelişmiş Saha Raporlama')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-6">

    {{-- ── HEADER ── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#FA6001] to-[#e05400] text-white shadow-sm flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold text-gray-900">Gelişmiş Saha Raporlama</h1>
                    <span class="rounded-full bg-gradient-to-r from-[#FA6001]/20 to-[#02E0FB]/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-500">PRO</span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">Saha ekiplerinizin performansını ve aşama tamamlama oranlarını analiz edin.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
            Son güncelleme: {{ now()->format('d.m.Y H:i') }}
        </div>
    </div>

    {{-- ── OVERALL STATS ── --}}
    @php
        $ov   = $overallStats;
        $rate = $ov['total'] > 0 ? round($ov['completed'] / $ov['total'] * 100) : 0;
        $ovItems = [
            ['label' => 'Toplam Görev',  'value' => $ov['total'],       'val_color' => 'text-gray-900',    'sub' => 'Tüm zamanlar', 'icon_bg' => 'bg-gray-100', 'icon_color' => 'text-gray-500',
             'icon' => '<path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>'],
            ['label' => 'Tamamlanan',    'value' => $ov['completed'],   'val_color' => 'text-emerald-600', 'sub' => 'Kapatılan iş', 'icon_bg' => 'bg-emerald-50', 'icon_color' => 'text-emerald-500',
             'icon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'],
            ['label' => 'Devam Eden',    'value' => $ov['in_progress'], 'val_color' => 'text-[#02AFC6]',  'sub' => 'Aktif saha', 'icon_bg' => 'bg-[#02E0FB]/10', 'icon_color' => 'text-[#02AFC6]',
             'icon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>'],
            ['label' => 'Geciken',       'value' => $ov['overdue'],     'val_color' => 'text-rose-600',   'sub' => 'Termini geçti', 'icon_bg' => 'bg-rose-50', 'icon_color' => 'text-rose-400',
             'icon' => '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach($ovItems as $s)
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $s['label'] }}</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl {{ $s['icon_bg'] }} {{ $s['icon_color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">{!! $s['icon'] !!}</svg>
                    </span>
                </div>
                <p class="text-3xl font-extrabold {{ $s['val_color'] }} tabular-nums">{{ $s['value'] }}</p>
                <p class="mt-1 text-[11px] text-gray-400">{{ $s['sub'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── CHART + STAGE RATES ── --}}
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">

        {{-- Monthly Chart --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900">Aylık Tamamlanan Görev Trendi</h2>
                <span class="rounded-full bg-[#FA6001]/10 border border-[#FA6001]/20 px-2.5 py-0.5 text-[10px] font-semibold text-orange-500">Son 6 Ay</span>
            </div>
            <div class="relative h-56">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        {{-- Stage Completion Rates --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-5 text-sm font-bold text-gray-900">Aşama Tamamlama Oranları</h2>
            <div class="space-y-5">
                @php
                    $stageAccents = [
                        ['bar' => 'bg-[#02E0FB]', 'bg' => 'bg-[#02E0FB]/10', 'text' => 'text-[#02AFC6]', 'label_bg' => 'bg-sky-50 text-sky-600'],
                        ['bar' => 'bg-[#FA6001]', 'bg' => 'bg-[#FA6001]/10', 'text' => 'text-orange-500', 'label_bg' => 'bg-orange-50 text-orange-600'],
                        ['bar' => 'bg-emerald-500','bg' => 'bg-emerald-50',   'text' => 'text-emerald-600','label_bg' => 'bg-emerald-50 text-emerald-600'],
                    ];
                @endphp
                @foreach($stageStats as $i => $stage)
                    @php $ac = $stageAccents[$i]; @endphp
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md {{ $ac['bg'] }} text-[10px] font-bold {{ $ac['text'] }}">{{ $i+1 }}</span>
                                <span class="text-xs font-semibold text-gray-700">{{ $stage['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold {{ $ac['text'] }}">{{ $stage['rate'] }}%</span>
                                <span class="text-[10px] text-gray-400">({{ $stage['done'] }}/{{ $overallStats['total'] }})</span>
                            </div>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $ac['bar'] }} transition-all duration-700"
                                 style="width: {{ $stage['rate'] }}%"></div>
                        </div>
                    </div>
                @endforeach

                {{-- Genel Tamamlama Daire --}}
                <div class="mt-2 flex items-center justify-center gap-5 rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4">
                    <div class="relative flex h-20 w-20 flex-shrink-0 items-center justify-center">
                        <svg class="-rotate-90" viewBox="0 0 36 36" width="80" height="80">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3.2"/>
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#10b981" stroke-width="3.2"
                                stroke-dasharray="{{ $rate }},100" stroke-linecap="round"/>
                        </svg>
                        <span class="absolute text-lg font-extrabold text-emerald-600">{{ $rate }}%</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Genel</p>
                        <p class="text-xl font-extrabold text-gray-900 leading-tight">Tamamlama</p>
                        <p class="text-xs text-gray-400">Oranı</p>
                        <div class="mt-1.5 h-1 w-16 rounded-full bg-gradient-to-r from-[#02E0FB] to-emerald-400"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SAHA PERSONELİ PERFORMANS KARNESİ ── --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Saha Personeli Performans Karnesi</h2>
                <p class="text-xs text-gray-400 mt-0.5">Anlık durum, tamamlama ve gecikme oranları dahil kapsamlı personel analizi</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-[11px] font-semibold text-gray-500">
                    {{ $personnel->count() }} personel
                </span>
                <a href="{{ route('admin.field-reports-pro.export-csv') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Excel Al
                </a>
                <a href="{{ route('admin.field-reports-pro.export-pdf') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                    PDF İndir
                </a>
            </div>
        </div>

        @if($personnel->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">Henüz saha ekibi üyesi yok.</p>
                <p class="mt-1 text-xs text-gray-400">field-team rolünde kullanıcı eklendiğinde buraya yansır.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/80 text-left">
                            <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Personel</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Şu An / Aktif İş</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Toplam</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Tamamlanan</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Geciken</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100 min-w-[180px]">Başarı Oranı</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100 min-w-[160px]">Gecikme Oranı</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">Performans</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($personnel as $p)
                            @php
                                $avColors = ['bg-blue-100 text-blue-700','bg-amber-100 text-amber-700','bg-violet-100 text-violet-700','bg-pink-100 text-pink-700','bg-emerald-100 text-emerald-700','bg-orange-100 text-orange-700','bg-sky-100 text-sky-600','bg-rose-100 text-rose-700'];
                                $avCls    = $avColors[$p->id % 8];

                                $perfBadge = match($p->perf_level) {
                                    'high'  => ['text' => 'Yüksek',  'cls' => 'bg-emerald-50 border border-emerald-200 text-emerald-700'],
                                    'mid'   => ['text' => 'Orta',    'cls' => 'bg-amber-50 border border-amber-200 text-amber-700'],
                                    default => ['text' => 'Düşük',   'cls' => 'bg-rose-50 border border-rose-200 text-rose-600'],
                                };

                                $successBarColor = match($p->perf_level) {
                                    'high'  => 'bg-emerald-400',
                                    'mid'   => 'bg-amber-400',
                                    default => 'bg-rose-400',
                                };
                            @endphp
                            <tr class="group hover:bg-gray-50/70 transition-colors">

                                {{-- Personel --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold {{ $avCls }}">
                                            {{ strtoupper(mb_substr($p->name, 0, 2)) }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ $p->name }}</p>
                                            <p class="text-[11px] text-gray-400">{{ $p->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Aktif İş --}}
                                <td class="px-4 py-4">
                                    @if($p->active_application_no)
                                        <div class="inline-flex items-center gap-1.5 rounded-lg border border-[#02E0FB]/30 bg-[#02E0FB]/8 px-2.5 py-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] animate-pulse flex-shrink-0"></span>
                                            <span class="text-xs font-bold text-[#02AFC6]">{{ $p->active_application_no }}</span>
                                        </div>
                                        <p class="mt-0.5 text-[10px] text-gray-400 px-1">Aktif görev</p>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                            Müsait
                                        </span>
                                    @endif
                                </td>

                                {{-- Toplam --}}
                                <td class="px-4 py-4 text-center">
                                    <span class="text-sm font-bold text-gray-700 tabular-nums">{{ $p->total_tasks }}</span>
                                </td>

                                {{-- Tamamlanan --}}
                                <td class="px-4 py-4 text-center">
                                    <span class="text-sm font-bold text-emerald-600 tabular-nums">{{ $p->completed_tasks }}</span>
                                </td>

                                {{-- Geciken --}}
                                <td class="px-4 py-4 text-center">
                                    @if($p->overdue_tasks > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-xs font-bold text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ $p->overdue_tasks }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-300 font-medium">—</span>
                                    @endif
                                </td>

                                {{-- Başarı Oranı Progress --}}
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-1 h-2 overflow-hidden rounded-full bg-gray-100 min-w-[80px]">
                                            <div class="h-full rounded-full {{ $successBarColor }} transition-all duration-700"
                                                 style="width: {{ $p->success_rate }}%"></div>
                                        </div>
                                        <span class="w-10 flex-shrink-0 text-right text-xs font-bold
                                            {{ $p->perf_level === 'high' ? 'text-emerald-600' : ($p->perf_level === 'mid' ? 'text-amber-600' : 'text-rose-500') }}">
                                            {{ $p->success_rate }}%
                                        </span>
                                    </div>
                                </td>

                                {{-- Gecikme Oranı Progress --}}
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-1 h-2 overflow-hidden rounded-full bg-gray-100 min-w-[80px]">
                                            <div class="h-full rounded-full transition-all duration-700
                                                {{ $p->delay_rate > 30 ? 'bg-rose-400' : ($p->delay_rate > 10 ? 'bg-amber-400' : 'bg-gray-300') }}"
                                                 style="width: {{ min($p->delay_rate, 100) }}%"></div>
                                        </div>
                                        <span class="w-10 flex-shrink-0 text-right text-xs font-bold
                                            {{ $p->delay_rate > 30 ? 'text-rose-500' : ($p->delay_rate > 10 ? 'text-amber-500' : 'text-gray-400') }}">
                                            {{ $p->delay_rate }}%
                                        </span>
                                    </div>
                                </td>

                                {{-- Performans Badge --}}
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full {{ $perfBadge['cls'] }} px-2.5 py-1 text-[10px] font-bold">
                                        @if($p->perf_level === 'high')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @elseif($p->perf_level === 'mid')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        @endif
                                        {{ $perfBadge['text'] }}
                                    </span>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 px-6 py-3 text-[11px] text-gray-400">
                <span class="font-semibold text-gray-500">Performans Seviyesi:</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Yüksek ≥ %80</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Orta ≥ %50</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-400"></span> Düşük &lt; %50</span>
                <span class="ml-auto flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] animate-pulse"></span>
                    Canlı başvuru verisi
                </span>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
const ctx = document.getElementById('monthlyChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! $chartLabels !!},
            datasets: [{
                label: 'Tamamlanan Görev',
                data: {!! $chartData !!},
                backgroundColor: 'rgba(250,96,1,0.15)',
                borderColor: '#FA6001',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(250,96,1,0.30)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#6b7280', font: { size: 11, family: 'Inter' } }
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#111827',
                    bodyColor: '#6b7280',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 10,
                    boxShadow: '0 4px 12px rgba(0,0,0,.08)',
                },
            },
            scales: {
                x: {
                    grid: { color: '#f3f4f6', drawBorder: false },
                    ticks: { color: '#9ca3af', font: { size: 11 } },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#f3f4f6', drawBorder: false },
                    ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 1 },
                    border: { display: false },
                    beginAtZero: true,
                },
            },
        }
    });
}
</script>
@endpush
