@extends('layouts.admin')

@section('page-heading', 'Saha Paneli')

@section('content')
<div class="space-y-6">

    {{-- ── SAHAYA ÇIK / AYRIL CHECK-İN BUTONU — yalnızca field-team + pro.field_tracking ── --}}
    @if(auth()->user()->hasRole('field-team') && auth()->user()->can('pro.field_tracking'))
    @php $isOnField = auth()->user()->is_on_field; @endphp
    <section id="checkin-banner"
             class="rounded-2xl border-2 px-6 py-5 shadow-sm transition-all duration-300 {{ $isOnField ? 'border-emerald-300 bg-gradient-to-r from-emerald-50 to-teal-50' : 'border-dashed border-gray-200 bg-white' }}">

        {{-- Durum satırı --}}
        <div class="flex items-center gap-3">
            <div id="checkin-indicator"
                 class="relative flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl {{ $isOnField ? 'bg-emerald-100' : 'bg-gray-100' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $isOnField ? 'text-emerald-600' : 'text-gray-400' }}" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                @if($isOnField)
                <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-emerald-500">
                    <span class="h-2 w-2 animate-ping rounded-full bg-emerald-300 absolute"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-white relative"></span>
                </span>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p id="checkin-title" class="text-sm font-bold {{ $isOnField ? 'text-emerald-700' : 'text-gray-700' }}">
                    {{ $isOnField ? 'Sahada Aktifsiniz' : 'Şu An Ofiste / Pasif' }}
                </p>
                <p id="checkin-sub" class="text-xs {{ $isOnField ? 'text-emerald-500' : 'text-gray-400' }}">
                    {{ $isOnField ? 'Konumunuz takip ediliyor. Çıkış yapmak için butona tıklayın.' : 'Sahaya çıkmadan önce mesainizi başlatın.' }}
                </p>
            </div>
            <div id="checkin-loading" class="hidden flex-shrink-0">
                <svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>

        {{-- ★ ANA CHECK-İN BUTONU ★ --}}
        <div class="mt-4 w-full overflow-hidden">
            <button id="btn-check-in"
                data-on="{{ $isOnField ? '1' : '0' }}"
                class="w-full flex items-center justify-center gap-2 rounded-full px-6 py-3.5 text-sm font-bold shadow-lg transition duration-200 hover:scale-[1.02] active:scale-95 leading-snug
                    {{ $isOnField
                        ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-200'
                        : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-200' }}">
                @if($isOnField)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
                    <span class="truncate">Sahadan Ayrıl — Mesai Bitir</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span class="truncate">Sahaya Çık — Mesaime Başla</span>
                @endif
            </button>
        </div>
    </section>
    @endif {{-- pro.field_tracking --}}

    {{-- Welcome Banner --}}
    <section class="rounded-2xl border border-cyan-100 bg-gradient-to-r from-cyan-50 to-blue-50 px-6 py-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-cyan-500">HGB Bilişim  AYKOME — SAHA PANELİ</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-800">Merhaba, {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Bugün sana atanmış görevleri yönet. Saha kontrollerini tamamla.</p>
            </div>
            @can('create', \App\Models\Application::class)
            <a href="{{ route('admin.applications.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#FA6001] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Yeni Başvuru
            </a>
            @endcan
        </div>
    </section>

    {{-- Task Stats --}}
    <section class="grid gap-4 sm:grid-cols-3">
        {{-- Bekleyen --}}
        <div class="flex items-center gap-4 rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-3xl">⏳</div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-amber-500">Bekleyen</p>
                <p class="mt-0.5 text-4xl font-bold tabular-nums text-amber-500">{{ $taskStats['pending'] }}</p>
            </div>
        </div>
        {{-- Devam Eden --}}
        <div class="flex items-center gap-4 rounded-2xl border border-cyan-100 bg-white p-5 shadow-sm">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-3xl">🔧</div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-cyan-500">Devam Eden</p>
                <p class="mt-0.5 text-4xl font-bold tabular-nums text-cyan-600">{{ $taskStats['in_progress'] }}</p>
            </div>
        </div>
        {{-- Tamamlanan --}}
        <div class="flex items-center gap-4 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-3xl">✅</div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-500">Tamamlanan</p>
                <p class="mt-0.5 text-4xl font-bold tabular-nums text-emerald-600">{{ $taskStats['completed'] }}</p>
            </div>
        </div>
    </section>

    {{-- Bana Atanmış Görevler --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Bana Atanmış Görevler</h2>
            <span class="rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs font-semibold text-cyan-600 ring-1 ring-cyan-100">
                {{ $myTasks->count() }} görev
            </span>
        </div>

        @if($myTasks->isEmpty())
            <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white py-14 text-gray-400 shadow-sm">
                <span class="text-5xl">📭</span>
                <p class="mt-4 text-base font-semibold text-gray-600">Atanmış saha göreviniz yok</p>
                <p class="mt-1 text-sm text-gray-400">Yöneticiniz size bir görev atadığında burada görünecek.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($myTasks as $task)
                    @php
                        $ts = $task->status;
                        $cardBorder = match($ts) {
                            'in_progress' => 'border-cyan-100',
                            'completed'   => 'border-emerald-100',
                            default       => 'border-amber-100',
                        };
                        $badgeBg = match($ts) {
                            'in_progress' => 'bg-cyan-50 text-cyan-700',
                            'completed'   => 'bg-emerald-50 text-emerald-700',
                            default       => 'bg-amber-50 text-amber-700',
                        };
                        $badgeLabel = match($ts) {
                            'in_progress' => 'Devam Ediyor',
                            'completed'   => 'Tamamlandı',
                            default       => 'Beklemede',
                        };
                        $s1 = $task->stage_1_status ?? 'pending';
                        $s2 = $task->stage_2_status ?? 'pending';
                        $s3 = $task->stage_3_status ?? 'pending';
                        $doneCount = collect([$s1,$s2,$s3])->filter(fn($s)=>$s==='done')->count();
                    @endphp
                    <div class="rounded-2xl border {{ $cardBorder }} bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-gray-800">
                                    {{ $task->application?->application_no ?? 'Başvuru yok' }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-gray-400">
                                    {{ $task->application?->address_text ?? '—' }}
                                </p>
                            </div>
                            <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $badgeBg }}">
                                {{ $badgeLabel }}
                            </span>
                        </div>

                        {{-- Termin --}}
                        @if($task->due_date)
                            <div class="mt-3 text-xs">
                                @if($task->due_date->isPast() && $ts !== 'completed')
                                    <span class="font-semibold text-rose-500">🔴 Gecikti: {{ $task->due_date->format('d.m.Y') }}</span>
                                @elseif($task->due_date->isToday())
                                    <span class="font-semibold text-amber-500">🟡 Bugün: {{ $task->due_date->format('d.m.Y') }}</span>
                                @else
                                    <span class="text-gray-400">🗓 Termin: {{ $task->due_date->format('d.m.Y') }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- 3-Stage Progress --}}
                        <div class="mt-4">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Saha Aşamaları</span>
                                <span class="text-[10px] font-bold text-gray-500">{{ $doneCount }}/3</span>
                            </div>
                            <div class="grid grid-cols-3 gap-1.5">
                                @foreach([['Kazı Öncesi',$s1],['Kazı Sonrası',$s2],['Zemin Onarım',$s3]] as [$slabel, $sstatus])
                                    <div class="rounded-lg px-2 py-1.5 text-center {{ $sstatus === 'done' ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50 border border-gray-100' }}">
                                        <p class="text-[9px] font-semibold leading-tight {{ $sstatus === 'done' ? 'text-emerald-600' : 'text-gray-400' }}">
                                            {{ $slabel }}
                                        </p>
                                        <p class="mt-0.5 text-[10px] {{ $sstatus === 'done' ? 'text-emerald-500' : 'text-gray-300' }}">
                                            {{ $sstatus === 'done' ? '✓' : '○' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('admin.field-tasks.inspect', ['fieldTask' => $task->id, 'stage' => max(1, $doneCount < 3 ? $doneCount + 1 : 3)]) }}"
                               class="flex items-center justify-center gap-1.5 rounded-xl bg-cyan-600 py-2.5 text-xs font-bold text-white transition hover:bg-cyan-700 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                Sahaya Git
                            </a>
                            <a href="{{ route('admin.field-tasks.show', $task) }}"
                               class="flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white py-2.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                Detay
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Son Başvurularım --}}
    @if($myApplications->isNotEmpty())
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Son Başvurularım</h2>
            <a href="{{ route('admin.applications.index') }}" class="text-xs font-medium text-cyan-600 hover:underline">Tümünü gör →</a>
        </div>
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @foreach($myApplications as $app)
                @php
                    $appStatus = $app->status instanceof \BackedEnum ? $app->status->value : (string) $app->status;
                    $appBadge = match($appStatus) {
                        'licensed'         => 'bg-emerald-50 text-emerald-700',
                        'completed'        => 'bg-teal-50 text-teal-700',
                        'awaiting_payment' => 'bg-amber-50 text-amber-700',
                        'submitted'        => 'bg-sky-50 text-sky-700',
                        'rejected'         => 'bg-rose-50 text-rose-700',
                        default            => 'bg-gray-100 text-gray-500',
                    };
                    $appLabel = match($appStatus) {
                        'draft' => 'Taslak', 'submitted' => 'Gönderildi',
                        'awaiting_payment' => 'Ödeme Bekliyor', 'receipt_pending' => 'Makbuz Bekliyor',
                        'licensed' => 'Ruhsatlı', 'completed' => 'Tamamlandı',
                        'rejected' => 'Reddedildi', default => str_replace('_', ' ', $appStatus),
                    };
                @endphp
                <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3 last:border-0 hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-800">{{ $app->application_no }}</p>
                        <p class="truncate text-xs text-gray-400">{{ $app->address_text ?? '—' }}</p>
                    </div>
                    <div class="ml-3 flex flex-shrink-0 flex-col items-end gap-1">
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $appBadge }}">{{ $appLabel }}</span>
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-[10px] font-medium text-cyan-600 hover:underline">Detay →</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
/* ── SAHAYA ÇIK / AYRIL — Check-in toggle ── */
(function () {
    const btn      = document.getElementById('btn-check-in');
    const banner   = document.getElementById('checkin-banner');
    const title    = document.getElementById('checkin-title');
    const sub      = document.getElementById('checkin-sub');
    const loader   = document.getElementById('checkin-loading');
    const indicator = document.getElementById('checkin-indicator');
    if (!btn) return;

    const CHECKIN_URL  = '{{ route('admin.field.checkin') }}';
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btn.addEventListener('click', async () => {
        const goingOnField = btn.dataset.on !== '1';

        /* GPS konum al */
        let lat = null, lng = null;
        if (goingOnField && navigator.geolocation) {
            try {
                const pos = await new Promise((res, rej) =>
                    navigator.geolocation.getCurrentPosition(res, rej, { timeout: 8000 })
                );
                lat = pos.coords.latitude;
                lng = pos.coords.longitude;
            } catch (e) {
                console.warn('[CheckIn] GPS alınamadı, konum boş gönderilecek.');
            }
        }

        /* Loading state */
        btn.disabled = true;
        loader.classList.remove('hidden');

        try {
            const res  = await fetch(CHECKIN_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ is_on_field: goingOnField, lat, lng }),
            });
            const data = await res.json();

            if (data.success) {
                btn.dataset.on = goingOnField ? '1' : '0';

                if (goingOnField) {
                    /* → Sahaya çıktı: UI güncelle, ping başlat, AYNI SAYFADA kal */
                    banner.classList.remove('border-dashed', 'border-gray-200', 'bg-white');
                    banner.classList.add('border-emerald-300', 'bg-gradient-to-r', 'from-emerald-50', 'to-teal-50');
                    title.textContent = 'Sahada Aktifsiniz';
                    title.className   = 'text-sm font-bold text-emerald-700';
                    sub.textContent   = 'Konumunuz merkeze iletiliyor. Çıkış için butona tıklayın.';
                    sub.className     = 'text-xs text-emerald-500';
                    btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-600', 'shadow-emerald-200');
                    btn.classList.add('bg-rose-500', 'hover:bg-rose-600', 'shadow-rose-200');
                    btn.innerHTML     = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg><span class="truncate">Sahadan Ayrıl — Mesai Bitir</span>`;
                    startLocationPing();
                    if (window.Swal) {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: data.message, timer: 3500, timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    }
                } else {
                    /* → Sahadan ayrıldı: UI güncelle, ping durdur */
                    stopLocationPing();
                    banner.classList.remove('border-emerald-300', 'bg-gradient-to-r', 'from-emerald-50', 'to-teal-50');
                    banner.classList.add('border-dashed', 'border-gray-200', 'bg-white');
                    title.textContent = 'Şu An Ofiste / Pasif';
                    title.className   = 'text-sm font-bold text-gray-700';
                    sub.textContent   = 'Sahaya çıkmadan önce mesainizi başlatın.';
                    sub.className     = 'text-xs text-gray-400';
                    btn.classList.remove('bg-rose-500', 'hover:bg-rose-600', 'shadow-rose-200');
                    btn.classList.add('bg-emerald-500', 'hover:bg-emerald-600', 'shadow-emerald-200');
                    btn.innerHTML     = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg><span class="truncate">Sahaya Çık — Mesaime Başla</span>`;
                    if (window.Swal) {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'info',
                            title: data.message, timer: 3000, timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    }
                }
            }
        } catch (e) {
            console.error('[CheckIn] Hata:', e);
        } finally {
            btn.disabled = false;
            loader.classList.add('hidden');
        }
    });

    /* ── GPS PING (arka plan konum güncellemesi) ── */
    let pingInterval = null;
    const UPDATE_URL = '{{ route('admin.field.location') }}';

    function startLocationPing() {
        if (pingInterval) clearInterval(pingInterval);
        pingInterval = setInterval(async () => {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(async pos => {
                try {
                    await fetch(UPDATE_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                    });
                } catch (_) {}
            });
        }, 30000); // 30 saniye — Job 2 dk'da temizliyor, 30 sn ping kesinlikle güvenli
    }

    function stopLocationPing() {
        if (pingInterval) { clearInterval(pingInterval); pingInterval = null; }
    }

    /* Eğer sayfa açıldığında zaten sahada ise ping başlat */
    if (btn.dataset.on === '1') startLocationPing();
})();
</script>
@endpush
