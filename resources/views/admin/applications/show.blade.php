@extends('layouts.admin')

@section('page-heading', $application->application_no)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}" />
<style>
    .leaflet-pane { z-index: 10 !important; }
    .leaflet-top, .leaflet-bottom { z-index: 10 !important; }
    .leaflet-control { z-index: 10 !important; }
</style>
@endpush

@section('content')
    @php
        $st = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
        // MERKEZ BELEDİYE (VATANDAŞ) AYRIMI: başvurunun institution'ı is_municipality=true ise
        // bu başvuru serbest-erişimli belediye akışıdır (kuruma gönderme yok, tüm onay belediyede).
        $isMuniApp = (bool) ($application->institution?->is_municipality ?? false);
        // GÖREV 2 (KATI EDİTÖR KİLİDİ): Enum/string kaçağına karşı raw değere eriş,
        // kullanıcı rolünü boolean'a indir, düzenleme yalnızca belediye VEYA
        // draft/rejected/revision durumlarında açılır. Alt kurum belgeyi submit
        // ettikten sonra (başka statü) bu değişken false → mavi edit butonu ASLA render olmaz.
        $kullaniciBelediyeMi = auth()->user()->isMunicipalityPersonel() ?? auth()->user()->is_municipality;
        $guncelStatus = isset($application->status->value) ? $application->status->value : $application->status;
        $duzenlemeAcik = $kullaniciBelediyeMi || in_array($guncelStatus, ['draft', 'rejected', 'revision']);
        $latestReceipt = $application->receipts->sortByDesc('id')->first();
        $latestReceiptMedia = $latestReceipt?->getFirstMedia('scan');
        $latestReceiptUrl = $latestReceiptMedia?->getUrl();

        // GÖREV 2 (PERSISTENT UPSTREAM VISIBILITY): Bir kez üretilen Ön Kazı / Metraj belgesi
        // sonraki TÜM aşamalarda (tahakkuk, taahhütname, ruhsat, arşiv) KALICI görünür.
        // dar statü listesi yerine "aşama aşıldı" semantiğine bağlanır.
        // MERKEZ BELEDİYE SERBEST ERİŞİM: muni başvurusunda metraj aşaması pre_approved statüsüyle
        // gelir; $passedMetraj pre_approved'ı dışlarsa Metraj PDF/Düzenle gizli kalır → muni'de gevşet.
        $passedOnKazi = ! in_array($st, ['draft', 'submitted', 'pending', 'rejected']);
        $passedMetraj = $isMuniApp
            ? ! in_array($st, ['draft', 'submitted', 'pending', 'rejected'])
            : ! in_array($st, ['draft', 'submitted', 'pending', 'pre_excavation_approved', 'pre_approved', 'rejected']);

        $statusMeta = match($st) {
            'draft'                  => ['label' => 'Taslak',                 'class' => 'bg-slate-100 text-slate-700'],
            'submitted'              => ['label' => 'Ön Kazı Bekliyor',       'class' => 'bg-sky-100 text-sky-700'],
            'pre_excavation_approved'=> ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'pre_approved'           => ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'priced'                 => ['label' => 'Fiyatlandı',             'class' => 'bg-indigo-100 text-indigo-700'],
            'awaiting_payment'       => ['label' => 'Ödeme Bekliyor',         'class' => 'bg-amber-100 text-amber-700'],
            'receipt_pending'        => ['label' => 'Makbuz Bekliyor',        'class' => 'bg-orange-100 text-orange-700'],
            'excavation_completed'   => ['label' => 'Kazı Tamamlandı',        'class' => 'bg-blue-100 text-blue-700'],
            'metrage_pending'        => ['label' => 'Metraj Açıldı',          'class' => 'bg-sky-100 text-sky-700'],
            'metrage_sent'           => ['label' => 'Metraj Kurum Onayında',  'class' => 'bg-indigo-100 text-indigo-700'],
            'metrage_revision'       => ['label' => 'Metraj Revizyon',        'class' => 'bg-rose-100 text-rose-700'],
            'metrage_approved'       => ['label' => 'Metraj Onaylı',          'class' => 'bg-emerald-100 text-emerald-700'],
            'tahakkuk_pending'       => ['label' => 'Tahakkuk & Makbuz Açıldı','class' => 'bg-indigo-100 text-indigo-700'],
            'payment_completed'      => ['label' => 'Ödeme Tamamlandı',       'class' => 'bg-teal-100 text-teal-700'],
            'approved'               => ['label' => 'Onaylandı',             'class' => 'bg-emerald-100 text-emerald-700'],
            'licensed'               => ['label' => 'Ruhsatlandı',           'class' => 'bg-green-100 text-green-700'],
            'field_work'             => ['label' => 'Saha Çalışması',        'class' => 'bg-blue-100 text-blue-700'],
            'completed'              => ['label' => 'Tamamlandı',            'class' => 'bg-teal-100 text-teal-700'],
            'rejected'               => ['label' => 'Reddedildi',            'class' => 'bg-rose-100 text-rose-700'],
            'taahhutname_pending'    => ['label' => 'Taahhütname Açıldı',    'class' => 'bg-violet-100 text-violet-700'],
            'taahhutname_sent'       => ['label' => 'Taahhütname Kuruma Gönderildi', 'class' => 'bg-violet-100 text-violet-700'],
            'tahakkuk_sent'          => ['label' => 'Tahakkuk & Makbuz Kuruma Gönderildi', 'class' => 'bg-amber-100 text-amber-700'],
            'ruhsat_sent'            => ['label' => 'Ruhsat Kuruma Gönderildi', 'class' => 'bg-green-100 text-green-700'],
            'archived'               => ['label' => 'Arşivlendi',            'class' => 'bg-gray-200 text-gray-600'],
            'cancelled'              => ['label' => 'İptal Edildi',          'class' => 'bg-rose-100 text-rose-700'],
            default                  => ['label' => \App\Enums\ApplicationStatus::tryFrom($st)?->label() ?? ucfirst(str_replace('_', ' ', $st)), 'class' => 'bg-slate-100 text-slate-700'],
        };

        // ÇİZİM VERİ KAYNAKLARI: "Çizim Alanı" ve "CBS Konum" haritaları hem
        // excavationAreas (başvuru formundaki çizimler) hem de gis_cizimler
        // (harita ekranından kaydedilen çizimler) verisini birlikte çizer.
        $haritaAreas = collect($application->excavationAreas->pluck('polygon_geojson')->filter()->values())
            ->merge(
                $application->gisCizimleri
                    ->map(fn ($c) => ['type' => 'FeatureCollection', 'features' => [$c->geometri]])
                    ->filter(fn ($v) => is_array($v) && is_array($v['features'] ?? null) && ! empty($v['features'][0]))
                    ->values()
            )
            ->values();
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900">{{ $application->application_no }}</h1>
                <span id="app-status-badge" class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $application->institution?->name }}</p>
            <div id="eimza-status" class="mt-1 text-xs font-medium text-slate-400">● E-İmza uygulaması kontrol ediliyor...</div>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- 🏆 RUHSAT BELGESİ AL — Licensed veya sonrası durumlarda aktif --}}
            @if(in_array($st, ['licensed', 'field_work', 'completed']))
                <a href="{{ route('admin.applications.permit-live', $application) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-emerald-900/30 transition hover:from-emerald-500 hover:to-teal-500 active:scale-95 ring-2 ring-emerald-400/30 ring-offset-1 ring-offset-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Ruhsat Belgesi Al
                </a>
            @endif
            @if($application->license_document_path)
                <a href="{{ route('admin.applications.license-pdf', $application) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/></svg>
                    Eski Ruhsat PDF
                </a>
            @endif
            <a href="{{ route('admin.applications.index') }}"
               class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">← Listeye dön</a>
        </div>
    </div>

    {{-- ══════════ GÖREV 1: BAŞVURU TEPESİ BELEDİYE MANUEL YETKİ KİLİT MEKANİZMASI (Alert Bar) ══════════ --}}
    {{-- Yalnızca alt kurum (taşeron) başvurularında. Vatandaş/Merkez akışı baypas edilir. --}}
    @if($application->isInstitutionApplication())
        @php
            $alertViewerIsMuni = auth()->user()->isMunicipalityPersonel();
            $alertKaStage = $st;
        @endphp
        @if(in_array($alertKaStage, ['excavation_completed', 'metrage_pending', 'metrage_sent', 'metrage_revision', 'metrage_approved', 'tahakkuk_pending', 'tahakkuk_sent', 'payment_completed', 'taahhutname_pending', 'taahhutname_sent', 'approved', 'licensed', 'ruhsat_sent', 'pre_excavation_approved', 'pre_approved', 'awaiting_payment', 'receipt_pending']))
        <div class="mb-6 rounded-2xl border border-blue-300 bg-gradient-to-r from-blue-50 to-sky-50 p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-lg">🔐</span>
                    <div>
                        @if($alertKaStage === 'excavation_completed')
                            <p class="text-sm font-bold text-blue-900">Kurum Kazı Çalışmalarını Tamamladı, Saha Metraj Aşamasında</p>
                            <p class="mt-0.5 text-xs text-blue-700">Metraj modülü kapalıdır. Modül ancak belediye yetkilisinin kilit açmasıyla görüntülenir.</p>
                        @elseif($alertKaStage === 'metrage_pending')
                            <p class="text-sm font-bold text-blue-900">Saha Metraj Modülü Açık</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye metrajı doldurup kuruma gönderecek; kurum onayı bekleniyor.</p>
                        @elseif($alertKaStage === 'metrage_sent')
                            <p class="text-sm font-bold text-blue-900">Saha Metraj Kurum Onayında</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye metrajı kuruma gönderdi; alt kurum onayı/reddini bekliyor.</p>
                        @elseif($alertKaStage === 'metrage_revision')
                            <p class="text-sm font-bold text-blue-900">Kurum Metrajı Kabul Etmedi — Revizyon Gerekli</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye metrajı yeniden düzenleyip tekrar göndermelidir.</p>
                        @elseif($alertKaStage === 'metrage_approved')
                            <p class="text-sm font-bold text-blue-900">Kurum Saha Metrajını Onayladı</p>
                            <p class="mt-0.5 text-xs text-blue-700">Tahakkuk &amp; Makbuz modülü kapalıdır; belediye kilidi ile açılacaktır.</p>
                        @elseif($alertKaStage === 'tahakkuk_pending')
                            <p class="text-sm font-bold text-blue-900">Tahakkuk &amp; Makbuz Aşaması</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye makbuz bilgilerini doldurup kuruma göndermeli; Ruhsat modülü belediye kilidi ile açılacaktır.</p>
                        @elseif($alertKaStage === 'tahakkuk_sent')
                            <p class="text-sm font-bold text-blue-900">Tahakkuk &amp; Makbuz Kuruma Gönderildi</p>
                            <p class="mt-0.5 text-xs text-blue-700">Alt kurumdan ödeme (makbuz onayı) bekleniyor.</p>
                        @elseif($alertKaStage === 'payment_completed')
                            <p class="text-sm font-bold text-blue-900">Ödeme Tamamlandı</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye Taahhütname modülünü kilit açıp kuruma gönderecek.</p>
                        @elseif($alertKaStage === 'taahhutname_pending')
                            <p class="text-sm font-bold text-blue-900">Taahhütname Belediye Hazırlığında</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye taahhütnameyi hazırlayıp kuruma göndermeli; kurum e-imzalayacak.</p>
                        @elseif($alertKaStage === 'taahhutname_sent')
                            <p class="text-sm font-bold text-blue-900">Taahhütname Gönderildi — Ruhsat Hazır</p>
                            <p class="mt-0.5 text-xs text-blue-700">Taahhütname &amp; Ruhsat belediyede birlikte hazırdır; belediye Ruhsat modülünü açabilir.</p>
                        @elseif($alertKaStage === 'approved')
                            <p class="text-sm font-bold text-blue-900">Ödeme Tamamlandı — Ruhsat Hazırlığı</p>
                            <p class="mt-0.5 text-xs text-blue-700">Ruhsat modülü kapalıdır; belediye kilidi ile açılacaktır.</p>
                        @elseif($alertKaStage === 'licensed')
                            <p class="text-sm font-bold text-blue-900">Ruhsat Belediye Hazırlığında</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye ruhsat belgesini hazırlayıp kuruma gönderecek.</p>
                        @elseif($alertKaStage === 'ruhsat_sent')
                            <p class="text-sm font-bold text-blue-900">Ruhsat Kuruma Gönderildi</p>
                            <p class="mt-0.5 text-xs text-blue-700">Alt kurum ruhsatı görüntüleyip saha çalışmasına geçebilir.</p>
                        @elseif($alertKaStage === 'awaiting_payment')
                            <p class="text-sm font-bold text-blue-900">🧾 Ödeme Bekleniyor — Makbuz Yükleyin</p>
                            <p class="mt-0.5 text-xs text-blue-700">Alt kurum ödeme dekontunu yüklediğinde belediye makbuzu onaylayıp Taahhütname modülünü açacaktır.</p>
                        @elseif($alertKaStage === 'receipt_pending' && $latestReceipt && $latestReceipt->status !== 'approved')
                            <p class="text-sm font-bold text-blue-900">🧾 Ödeme Evrakları Geldi — Onay Bekleniyor</p>
                            <p class="mt-0.5 text-xs text-blue-700">Alt kurum ödeme dekontunu yükledi; belediye makbuzu onaylayınca Taahhütname modülü açılır.</p>
                        @elseif($alertKaStage === 'receipt_pending')
                            <p class="text-sm font-bold text-blue-900">🧾 Makbuz Onaylandı — Taahhütname Açılacak</p>
                            <p class="mt-0.5 text-xs text-blue-700">Belediye makbuz onayını tamamladı; Taahhütname modülüne geçilebilir.</p>
                        @else
                            <p class="text-sm font-bold text-blue-900">Ön Kazı Onaylı — Kazı Yapılabilir</p>
                            <p class="mt-0.5 text-xs text-blue-700">Kurum kazı çalışmalarını tamamlayınca belediye ileri modülleri manuel açacaktır.</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    {{-- BELEDİYE: manuel kilit açma butonları --}}
                    @if($alertViewerIsMuni && $alertKaStage === 'excavation_completed')
                        <form method="POST" action="{{ route('admin.applications.open-metraj', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">🔓 KAZI METRAJ MODÜLÜNÜ AÇ</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && $alertKaStage === 'metrage_approved')
                        <form method="POST" action="{{ route('admin.applications.open-tahakkuk', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">🔓 TAHAKKUK VE MAKBUZ MODÜLÜNÜ AÇ</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && in_array($alertKaStage, ['payment_completed', 'approved']))
                        <form method="POST" action="{{ route('admin.applications.open-taahhutname', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">🔓 TAAHHÜTNAME MODÜLÜNÜ AÇ</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && $alertKaStage === 'receipt_pending' && $latestReceipt && $latestReceiptMedia && $latestReceipt->status !== 'approved')
                        <form method="POST" action="{{ route('admin.applications.approve-receipt', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-emerald-700">✅ Makbuzu Onayla</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && $alertKaStage === 'tahakkuk_pending')
                        <form method="POST" action="{{ route('admin.applications.send-tahakkuk', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-sky-700">📤 Tahakkuku Kuruma Gönder</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && $alertKaStage === 'taahhutname_pending')
                        <form method="POST" action="{{ route('admin.applications.send-taahhutname', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-sky-700">📤 Taahhütnameyi Kuruma Gönder</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && in_array($alertKaStage, ['approved', 'taahhutname_sent']))
                        <form method="POST" action="{{ route('admin.applications.open-ruhsat', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-blue-700">🔓 RUHSAT MODÜLÜNÜ AÇ (FİNAL)</button>
                        </form>
                    @endif
                    @if($alertViewerIsMuni && $alertKaStage === 'licensed')
                        <form method="POST" action="{{ route('admin.applications.send-ruhsat', $application) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-sky-700">📤 Ruhsatı Kuruma Gönder</button>
                        </form>
                    @endif
                    {{-- AYKOME BİRİM ŞEFİ: saha kazı tamamla (alt kurumdan yetki alındı) --}}
                    @if($alertViewerIsMuni && auth()->user()->hasAnyRole(['municipality-sef', 'municipality-admin', 'municipality-makam', 'super-admin']) && in_array($alertKaStage, ['pre_excavation_approved', 'pre_approved']))
                        <form method="POST" action="{{ route('admin.applications.complete-field-work', $application) }}"
                              onsubmit="return confirm('Saha çalışmalarının tamamlandığını onaylıyor musunuz?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow hover:bg-emerald-700">✅ Saha Çalışmaları Tamamlandı</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endif
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- LEFT COL --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- ❌ İPTAL EDİLDİ — iptal sebebi her zaman görünür --}}
            @if($st === 'cancelled')
            <div class="rounded-2xl border-2 border-rose-300 bg-rose-50 p-6 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-rose-800">Başvuru İptal Edildi</h2>
                        <p class="mt-1 text-xs text-rose-600">{{ $application->updated_at?->format('d.m.Y H:i') ?? '—' }}</p>
                        @if($application->rejection_reason)
                        <div class="mt-3 rounded-xl border border-rose-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-rose-500">İptal Sebebi</p>
                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $application->rejection_reason }}</p>
                        </div>
                        @else
                        <p class="mt-3 text-sm text-rose-700">Bu başvuru iptal edilmiştir. İptal sebebi bildirilmemiştir.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Application Info --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Başvuru Bilgileri</h2>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <dt class="text-xs font-medium text-slate-500">Başvuru Türü</dt>
                        <dd class="mt-0.5">
                            @if($application->application_type === 'ariza')
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Arıza (Acil Kazı)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-700">
                                    Normal Başvuru
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs font-medium text-slate-500">Başvuran</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">TC Kimlik</dt><dd class="mt-0.5">{{ $application->applicant_national_id ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Telefon</dt><dd class="mt-0.5">{{ $application->applicant_phone ?? '—' }}</dd></div>
                    @if($application->project_code || $application->work_type)
                    @php
                        $displayIs = [];
                        if ($application->project_code) $displayIs[] = 'Kod: ' . $application->project_code;
                        if ($application->work_type) $displayIs[] = 'İş Cinsi: ' . $application->work_type;
                    @endphp
                    <div><dt class="text-xs font-medium text-slate-500">Proje / İşin Adı</dt><dd class="mt-0.5 font-mono text-slate-800">{{ implode(' / ', $displayIs) }}</dd></div>
                    @endif
                    <div><dt class="text-xs font-medium text-slate-500">Kazı Sebebi</dt><dd class="mt-0.5">{{ $application->excavation_reason ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">İşin Adı (Cinsi)</dt><dd class="mt-0.5">{{ $application->work_type ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Başlangıç</dt><dd class="mt-0.5">{{ $application->start_date?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Bitiş</dt><dd class="mt-0.5">{{ $application->end_date?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Adres</dt><dd class="mt-0.5 text-slate-700">{{ $application->address_text ?? '—' }}</dd></div>
                    @if(!empty($application->address_components) && is_array($application->address_components))
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500 mb-1">Mahalle & Sokaklar</dt>
                        <dd class="mt-0.5">
                            <div class="space-y-2">
                                @foreach($application->address_components as $ac)
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-2">
                                    <p class="text-xs font-semibold text-slate-700">{{ $ac['mahalle'] ?? '—' }}</p>
                                    @if(!empty($ac['streets']) && is_array($ac['streets']))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($ac['streets'] as $street)
                                        <button type="button" class="address-chip inline-flex items-center rounded-md bg-white px-2 py-0.5 text-[11px] text-slate-600 ring-1 ring-slate-200 transition hover:bg-cyan-50 hover:text-cyan-700 hover:ring-cyan-300"
                                                data-adres="{{ trim(($ac['mahalle'] ?? '') . ', ' . $street) }}" title="Adresi haritada göster">📍 {{ $street }}</button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </dd>
                    </div>
                    @endif
                    @if($application->description)
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Açıklama</dt><dd class="mt-0.5 text-slate-700">{{ $application->description }}</dd></div>
                    @endif
                    @if($application->preExcavationApprover)
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onaylayan</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->preExcavationApprover?->name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onay Tarihi</dt><dd class="mt-0.5">{{ $application->pre_excavation_approved_at?->format('d.m.Y H:i') }}</dd></div>
                    @endif
                    @if($application->vice_mayor_name)
                    <div><dt class="text-xs font-medium text-slate-500">Başkan Yardımcısı</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->vice_mayor_name }}</dd></div>
                    @endif
                    @if(!$application->institution?->is_municipality && in_array($st, ['draft', 'submitted', 'pending']))
                    @php
                        $onayStage = $application->approval_stage ?? 'staff';
                        $onayMeta = [
                            'staff'      => ['label' => 'Personel Onayı', 'class' => 'bg-sky-100 text-sky-700'],
                            'director'   => ['label' => 'Müdür Onayı',    'class' => 'bg-indigo-100 text-indigo-700'],
                            'vice_mayor' => ['label' => 'Başkan Yrd.',    'class' => 'bg-amber-100 text-amber-700'],
                            'approved'   => ['label' => 'Onaylandı',      'class' => 'bg-emerald-100 text-emerald-700'],
                        ];
                        $onay = $onayMeta[$onayStage] ?? ['label' => ucfirst($onayStage), 'class' => 'bg-slate-100 text-slate-700'];
                    @endphp
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500 mb-1">Onay Akışı</dt>
                        <dd class="mt-0.5">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $onay['class'] }}">
                                    {{ $onay['label'] }}
                                </span>
                                @if($application->staffApprover)
                                <span class="text-[11px] text-slate-500">Personel: {{ $application->staffApprover->name }} ({{ $application->staff_approved_at?->format('d.m.Y H:i') }})</span>
                                @endif
                                @if($application->directorApprover)
                                <span class="text-[11px] text-slate-500">Müdür: {{ $application->directorApprover->name }} ({{ $application->director_approved_at?->format('d.m.Y H:i') }})</span>
                                @endif
                                @if($application->viceMayorApprover)
                                <span class="text-[11px] text-slate-500">Başkan Yrd.: {{ $application->viceMayorApprover->name }} ({{ $application->vice_mayor_approved_at?->format('d.m.Y H:i') }})</span>
                                @endif
                            </div>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- ZEMİN SATIRLARI & HESAPLAMALAR (Read-Only) --}}
            @php
                // TEK MUHASEBE KAYNAĞI: Tüm tutarlar Model accessor'larından gelir (calcFigures).
                $surfaceLines = $application->surfaceLines;
                $cf = $application->calcFigures();
                $toplamMiktar = (float) $cf['toplam_miktar'];
                $ztb = (float) $cf['ztb_amount'];
                $kdv = (float) $cf['kdv_amount'];
                $ruhsatHarci = (float) $cf['license_fee'];
                $kesifBedeli = (float) $cf['discovery_fee'];
                $ztbToplam = (float) $cf['ztb_total'];
                $teminat = (float) $cf['teminat'];
                $genelToplam = (float) $cf['general_total'];
            @endphp

            @if($surfaceLines->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-slate-800">Zemin Satırları &amp; Hesaplamalar</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-300 text-left text-slate-600">
                                <th class="py-2 pr-2 font-medium">#</th>
                                <th class="p-2 font-medium min-w-[180px]">Adres</th>
                                <th class="p-2 font-medium min-w-[180px]">Zemin Tipi</th>
                                <th class="p-2 font-medium min-w-[100px]">Genişlik (m)</th>
                                <th class="p-2 font-medium min-w-[100px]">Uzunluk (m)</th>
                                <th class="p-2 font-medium min-w-[120px]">Miktar (m²)</th>
                                <th class="p-2 font-medium min-w-[110px]">Birim Fiyat</th>
                                <th class="p-2 font-medium min-w-[140px]">Satır Tutarı (₺)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surfaceLines as $idx => $line)
                            @php
                                $qty = max((float)($line->quantity ?? 0), 0);
                                $unitPrice = max((float)($line->surfaceType?->price_per_m2 ?? 0), 0);
                                $rowTotal = $qty * $unitPrice;
                            @endphp
                            <tr class="border-b border-slate-200 hover:bg-slate-100/50 transition">
                                <td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">{{ $idx + 1 }}</td>
                                <td class="p-2 align-top pt-2 text-slate-700 max-w-[200px] break-words">{{ $line->address ?: '—' }}</td>
                                <td class="p-2 align-top pt-2 font-medium text-slate-800">{{ $line->surfaceType?->name ?? '—' }}</td>
                                <td class="p-2 align-top pt-2 text-slate-700">{{ $line->width_m ? number_format((float)$line->width_m, 2, ',', '.') : '—' }}</td>
                                <td class="p-2 align-top pt-2 text-slate-700">{{ $line->length_m ? number_format((float)$line->length_m, 2, ',', '.') : '—' }}</td>
                                <td class="p-2 align-top pt-2 font-semibold text-slate-800">{{ number_format($qty, 2, ',', '.') }}</td>
                                <td class="p-2 align-top pt-2 text-slate-600 font-mono">{{ number_format($unitPrice, 2, ',', '.') }} ₺/m²</td>
                                <td class="p-2 align-top pt-2 text-right font-mono text-xs font-semibold text-slate-800">{{ number_format($rowTotal, 2, ',', '.') }} ₺</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- HESAP KARTLARI --}}
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Toplam Miktar</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($toplamMiktar, 2, ',', '.') }} m²</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Zemin Tahrip Bedeli</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ztb, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">KDV (%20)</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($kdv, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ruhsat Harcı</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ruhsatHarci, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Keşif Bedeli</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($kesifBedeli, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">ZTB Toplam</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ztbToplam, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Teminat</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($teminat, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Genel Toplam</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($genelToplam, 2, ',', '.') }} ₺</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Normal Çizim Haritası (çizilen alan net görünür) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">🗺️ Çizim Alanı (Normal Harita)</h2>
                <div id="app-normal-map" class="w-full rounded-xl border border-slate-200 bg-slate-50" style="height:350px"></div>
            </div>

            {{-- CBS Referans Haritası --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">📍 CBS Harita Konumu</h2>
                @include('maps.partials._harita', [
                    'mode' => 'embedded',
                    'drawingEnabled' => false,
                    'hatKimligiEnabled' => true,
                    'show15mRoads' => false,
                    'height' => '350px',
                    'readOnly' => true,
                    'application' => $application,
                    'areas' => $haritaAreas,
                ])
            </div>

            {{-- BELGE ARŞİVİ / DÖKÜMLER (Tüm aşamaların PDF'leri) — belediye personeli + başvurunun sahibi alt kurum --}}
            @php
                // CELL-BASED AUTH: Belediye yönetimi her başvuruyu düzenleyebilir;
                // alt kurum personeli YALNIZCA kendi kurumunun başvurusunda butonu görür.
                $user = auth()->user();
                $canEditTemplate = $user->isMunicipalityPersonel()
                    || ($user->institution_id && (int) $user->institution_id === (int) $application->institution_id);
            @endphp
            @if($canEditTemplate)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">📦 Belge Arşivi / Dökümler</h2>
                <p class="mb-3 text-xs text-slate-500">Başvuru sürecinde oluşturulmuş tüm belgelere buradan erişebilirsiniz.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                    {{-- Üst Yazı (Dilekçe) -- her zaman göster; MERKEZ BELEDİYE (vatandaş) de görsün --}}
                    @if($application->institution && ($isMuniApp || !str_contains(strtolower($application->institution->name ?? ''), 'merkez')))
                    <div class="relative">
                    <a href="{{ route('admin.applications.pdf.cover-letter', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-cyan-800">Üst Yazı</span>
                        <span class="text-[9px] text-slate-400">Dilekçe</span>
                    </a>
                    @if($kullaniciBelediyeMi && $canEditTemplate)
                    <a href="{{ route('admin.applications.edit-document', [$application, 'cover_letter']) }}" title="Bu başvuruya özel taslağı düzenle" class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-1 text-[9px] font-bold text-white shadow hover:bg-indigo-700">✏️ Taslak</a>
                    @endif
                    </div>
                    @endif

                    {{-- Ön Kazı İzin Belgesi -- GÖREV 2: aşama aşıldıysa KALICI görünür --}}
                    @if($passedOnKazi)
                    <div class="relative">
                    <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-cyan-800">Ön Kazı</span>
                        <span class="text-[9px] text-slate-400">İzin Belgesi</span>
                    </a>
                    @if($kullaniciBelediyeMi && $canEditTemplate)
                    <a href="{{ route('admin.applications.edit-document', [$application, 'on_kazi']) }}" title="Bu başvuruya özel taslağı düzenle" class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-1 text-[9px] font-bold text-white shadow hover:bg-indigo-700">✏️ Taslak</a>
                    @endif
                    </div>
                    @endif

                    {{-- Tahsilat Makbuzu -- her zaman göster --}}
                    <a href="{{ route('admin.applications.payment-receipt', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-amber-300 hover:bg-amber-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 group-hover:bg-amber-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-amber-800">Tahsilat</span>
                        <span class="text-[9px] text-slate-400">Makbuzu</span>
                    </a>

                    {{-- Kazı Metraj Cetveli -- her zaman göster --}}
                    <div class="relative">
                    <a href="{{ route('admin.applications.pdf.metraj', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-indigo-300 hover:bg-indigo-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 group-hover:bg-indigo-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-indigo-800">Metraj</span>
                        <span class="text-[9px] text-slate-400">Cetveli</span>
                    </a>
                    @if($kullaniciBelediyeMi && $canEditTemplate)
                    <a href="{{ route('admin.applications.edit-document', [$application, 'metraj']) }}" title="Bu başvuruya özel taslağı düzenle" class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-1 text-[9px] font-bold text-white shadow hover:bg-indigo-700">✏️ Taslak</a>
                    @endif
                    </div>

                    {{-- Tahakkuk Fişi -- her zaman göster --}}
                    <div class="relative">
                    <a href="{{ route('admin.applications.pdf.tahakkuk', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-rose-300 hover:bg-rose-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-700 group-hover:bg-rose-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-rose-800">Tahakkuk</span>
                        <span class="text-[9px] text-slate-400">Fişi</span>
                    </a>
                    @if($kullaniciBelediyeMi && $canEditTemplate)
                    <a href="{{ route('admin.applications.edit-document', [$application, 'tahakkuk']) }}" title="Bu başvuruya özel taslağı düzenle" class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-1 text-[9px] font-bold text-white shadow hover:bg-indigo-700">✏️ Taslak</a>
                    @endif
                    </div>

                    @php
                        $isAdminUser = auth()->user()->hasAnyRole(['super-admin', 'municipality-admin']);
                        $statusVal = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
                        $ruhsatVisible = $isAdminUser || in_array($statusVal, ['licensed', 'completed']);
                    @endphp

                    {{-- Ruhsat / Canlı / Eski Ruhsat — yalnızca ruhsatlanmış/tamamlanmış başvurularda (admin hariç) --}}
                    @if($ruhsatVisible)
                    {{-- Ruhsat Belgesi --}}
                    <div class="relative">
                    <a href="{{ route('admin.applications.pdf.ruhsat', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-emerald-300 hover:bg-emerald-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-emerald-800">Ruhsat</span>
                        <span class="text-[9px] text-slate-400">Belgesi</span>
                    </a>
                    @if($kullaniciBelediyeMi && $canEditTemplate)
                    <a href="{{ route('admin.applications.edit-document', [$application, 'ruhsat']) }}" title="Bu başvuruya özel taslağı düzenle" class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-1 text-[9px] font-bold text-white shadow hover:bg-indigo-700">✏️ Taslak</a>
                    @endif
                    </div>

                    {{-- Canlı Ruhsat (Permit Live) --}}
                    <a href="{{ route('admin.applications.permit-live', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-teal-300 hover:bg-teal-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-teal-700 group-hover:bg-teal-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-teal-800">Canlı</span>
                        <span class="text-[9px] text-slate-400">Ruhsat</span>
                    </a>

                    {{-- Eski Ruhsat PDF (varsa) --}}
                    @if($application->license_document_path)
                    <a href="{{ route('admin.applications.license-pdf', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-sky-300 hover:bg-sky-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700 group-hover:bg-sky-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-sky-800">Eski Ruhsat</span>
                        <span class="text-[9px] text-slate-400">PDF</span>
                    </a>
                    @endif
                    @endif
                </div>
            </div>
            @endif

            {{-- Ek Ruhsat Modülü — yalnızca belediye personeli --}}
            @if(auth()->user()->isMunicipalityPersonel())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">📋 Ek Ruhsatlar</h2>
                @php $extraPermitCount = $application->extraPermits?->count() ?? 0; @endphp
                <p class="mb-3 text-xs text-slate-500">Bu başvuruya ek kazı ruhsatı tanımlayabilir veya mevcut ek ruhsatları görüntüleyebilirsiniz.</p>
                <div class="flex flex-wrap gap-2">
                    @php
                        $isInstAppHere = $application->isInstitutionApplication();
                    @endphp
                    @if(! $isInstAppHere && $canEditTemplate)
                    <form method="POST" action="{{ route('admin.applications.create-additional-permit', $application) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            + Ek Ruhsat Süreci Oluştur
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('admin.extra-permits.create', $application) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-cyan-700 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        + Ek Ruhsat Ekle
                    </a>
                    @if($extraPermitCount > 0)
                    <a href="{{ route('admin.extra-permits.index', $application) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Ek Ruhsatlar ({{ $extraPermitCount }})
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Yüklenen Belgeler --}}
            @if($application->documents->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Yüklenen Belgeler</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($application->documents as $doc)
                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                        @if($doc->isImage())
                            <a href="{{ $doc->url }}" target="_blank" class="shrink-0">
                                <img src="{{ $doc->url }}" class="h-14 w-14 rounded-lg object-cover shadow-sm" alt="">
                            </a>
                        @else
                            <a href="{{ $doc->url }}" target="_blank" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-rose-100 to-rose-200 shadow-sm">
                                <svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </a>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $doc->original_name }}</p>
                            <p class="text-xs text-slate-500">{{ $doc->size_for_humans }} · {{ $doc->isPdf() ? 'PDF' : 'Görsel' }}</p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <a href="{{ $doc->url }}" target="_blank" class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Görüntüle">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ $doc->url }}" @if($doc->isPdf()) target="_blank" @else download="{{ $doc->original_name }}" @endif class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="{{ $doc->isPdf() ? 'Görüntüle' : 'İndir' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- === MAKBUZ BÖLÜMÜ (Tamamen Bağımsız Form) === --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" id="receipt-section">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Makbuz</h2>
                    @if($latestReceipt)
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                            @if($latestReceipt->status === 'approved') bg-emerald-100 text-emerald-700
                            @elseif($latestReceipt->status === 'rejected') bg-rose-100 text-rose-700
                            @else bg-amber-100 text-amber-700 @endif">
                            {{ $latestReceipt->status === 'approved' ? 'Onaylandı' : ($latestReceipt->status === 'rejected' ? 'Reddedildi' : 'İnceleniyor') }}
                        </span>
                    @endif
                </div>

                @if($latestReceipt)
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Yükleyen</dt><dd class="mt-0.5 font-medium">{{ $latestReceipt->uploader?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Yükleme Zamanı</dt><dd class="mt-0.5">{{ $latestReceipt->created_at?->format('d.m.Y H:i') }}</dd></div>
                        @if($latestReceipt->notes)
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Not</dt><dd class="mt-0.5">{{ $latestReceipt->notes }}</dd></div>
                        @endif
                        @if($latestReceipt->review_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-500">İnceleme Notu (Ret)</dt>
                            <dd class="mt-0.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-rose-800">{{ $latestReceipt->review_notes }}</dd>
                        </div>
                        @endif
                    </dl>

                    @if($latestReceiptUrl)
                        <div class="mt-4">
                            <a href="{{ $latestReceiptUrl }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 hover:border-emerald-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a3 3 0 013-3h8a1 1 0 110 2H8zm0 5a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                                Makbuz Dosyasını Görüntüle
                            </a>
                        </div>
                    @endif
                @else
                    <p class="mt-4 flex items-center gap-2 text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Henüz makbuz yüklenmedi.
                    </p>
                @endif

                {{-- Makbuz yükleme formu — yalnızca onay yetkisi YOKSA göster (standalone upload) --}}
                @if(($can['update'] ?? false) && !($can['approve_receipt'] ?? false))
                <div class="mt-5 border-t border-slate-100 pt-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        {{ $latestReceipt ? 'Yeni Makbuz Yükle (Güncelle)' : 'Makbuz Yükle' }}
                    </p>
                    <form
                        id="receipt-upload-form"
                        method="POST"
                        action="{{ route('admin.applications.receipts.store', $application) }}"
                        enctype="multipart/form-data"
                        novalidate
                    >
                        @csrf
                        <div id="receipt-drop-zone"
                            class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition hover:border-amber-400 hover:bg-amber-50/40"
                            onclick="document.getElementById('receipt_file_input').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <p id="receipt-file-label" class="text-sm text-slate-600">
                                Dosyayı buraya sürükleyin veya <span class="font-semibold text-amber-600">seçmek için tıklayın</span>
                            </p>
                            <p class="text-xs text-slate-400">PDF, JPEG, PNG — Maks 5 MB</p>
                        </div>
                        <input type="file" id="receipt_file_input" name="receipt_file"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png,image/jpg"
                            class="sr-only" required>
                        <div id="receipt-file-preview" class="mt-2 hidden items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span id="receipt-file-name" class="truncate"></span>
                            <button type="button" id="receipt-file-clear" class="ml-auto flex-shrink-0 text-xs font-medium text-rose-600 hover:underline">Kaldır</button>
                        </div>
                        <div class="mt-3">
                            <label for="receipt_notes" class="block text-xs font-medium text-slate-600">Açıklama (opsiyonel)</label>
                            <textarea id="receipt_notes" name="notes" rows="2"
                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                placeholder="Makbuz hakkında not (opsiyonel)"></textarea>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="submit" id="receipt-submit-btn"
                                class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                Makbuzu Yükle
                            </button>
                            <span id="receipt-upload-status" class="hidden items-center gap-2 text-sm text-slate-500">
                                <svg class="h-4 w-4 animate-spin text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Yükleniyor…
                            </span>
                        </div>
                    </form>
                </div>
                @endif
            </div>

            {{-- Field Tasks --}}
            @if($application->fieldTasks->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-slate-800">Saha Görevleri</h2>
                    <ul class="space-y-2">
                        @foreach($application->fieldTasks as $task)
                            @php
                                $taskBadge = match($task->status) {
                                    'pending'     => 'bg-amber-100 text-amber-700',
                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                    'completed'   => 'bg-emerald-100 text-emerald-700',
                                    default       => 'bg-slate-100 text-slate-700',
                                };
                                $taskLabel = match($task->status) {
                                    'pending'     => 'Beklemede',
                                    'in_progress' => 'Devam ediyor',
                                    'completed'   => 'Tamamlandı',
                                    default       => $task->status,
                                };
                            @endphp
                            <li class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                                <div>
                                    <span class="font-medium text-slate-800">{{ $task->assignee?->name ?? '—' }}</span>
                                    <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $taskBadge }}">{{ $taskLabel }}</span>
                                    @if($task->due_date)
                                        <span class="ml-2 text-xs text-slate-500">Termin: {{ $task->due_date->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('admin.field-tasks.show', $task) }}"
                                   class="text-xs font-medium text-emerald-700 hover:underline">Detay →</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Süreç Takip ve Zaman Çizelgesi (Audit History) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Süreç Takip ve Zaman Çizelgesi</h2>
                <ol class="relative border-s-2 border-slate-200 ps-1">
                    @php
                        $audits = collect($application->history->map(function ($a) {
                            return (object) [
                                'type' => 'audit',
                                'sort' => $a->created_at,
                                'action' => $a->action,
                                'user' => $a->user?->name ?? 'Sistem',
                                'date' => $a->created_at,
                                'old_status' => $a->old_status,
                                'new_status' => $a->new_status,
                            ];
                        }))->merge($application->timelineLogs->map(function ($l) {
                            return (object) [
                                'type' => 'log',
                                'sort' => $l->created_at,
                                'action' => $l->action,
                                'user' => $l->user?->name ?? 'Sistem',
                                'date' => $l->created_at,
                                'message' => $l->message,
                            ];
                        }))->sortByDesc('sort');
                    @endphp
                    @forelse($audits as $entry)
                        <li class="ms-6 pb-5 last:pb-0">
                            <span class="absolute -start-[0.52rem] mt-1.5 h-3.5 w-3.5 rounded-full border-2 border-white {{ $entry->type === 'audit' ? 'bg-emerald-400' : 'bg-[#02E0FB]' }}"></span>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2">
                                <p class="text-sm font-medium text-slate-800">{{ $entry->type === 'log' ? \App\Models\ApplicationTimelineLog::actionLabel($entry->action) : $entry->action }}</p>
                                @if(isset($entry->old_status) || isset($entry->new_status))
                                <p class="mt-0.5 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600">{{ \App\Enums\ApplicationStatus::tryLabel($entry->old_status) }}</span>
                                    <span class="text-slate-400 mx-1">→</span>
                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] text-emerald-700">{{ \App\Enums\ApplicationStatus::tryLabel($entry->new_status) }}</span>
                                </p>
                                @endif
                                @if(isset($entry->message) && $entry->message)<p class="mt-0.5 text-xs text-slate-600">{{ $entry->message }}</p>@endif
                                <p class="mt-1 text-[11px] text-slate-400">{{ $entry->user }} · {{ $entry->date?->format('d.m.Y H:i') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="ms-6 text-sm text-slate-500">Henüz işlem kaydı bulunmuyor.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="space-y-4">
            {{-- Workflow Step Navigation — Collapsible Accordion --}}
            @php
                $isMunicipality = $application->institution?->is_municipality ?? false;
                $isAltKurum = !$isMunicipality;
                // Kullanıcı bazlı alt kurum kontrolü (giriş yapan kişi)
                $isUserInstitution = !auth()->user()->isMunicipalityPersonel();
                // Ön kazı adımı oluşturulmuş mu? (submitted sonrası = ön kazı işleme alınmış)
                $onKaziIslendi = !in_array($st, ['draft', 'submitted']);
                // Belediye reddetti mi? (rejected = edit tekrar açılır)
                $belediyeReddetti = $st === 'rejected';

                if ($isMunicipality) {
                    $workflowSteps = [
                        1 => ['key' => 'submitted',   'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        2 => ['key' => 'pre_approved', 'label' => 'Kazı Metraj Bilgi',       'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        3 => ['key' => 'accrued',      'label' => 'Taahhütname',              'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        4 => ['key' => 'licensed',     'label' => 'Ruhsat',                   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                } else {
                    // KURAL 1 (Workflow Lock): Akış ÜST YAZI ile başlar. Belediye Ön Kazı'yı
                    // üretince Üst Yazı kilitlenir (red/revize → submitted → tekrar açılır).
                    $workflowSteps = [
                        1 => ['key' => 'pending',          'label' => 'Üst Yazı',    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        2 => ['key' => 'pre_approved',     'label' => 'Ön Kazı',     'icon' => 'M12 2l.64 1.28a1 1 0 01.5.5L14.42 5.5l1.42-.36a1 1 0 011.1.5l.64 1.28 1.42.36a1 1 0 01.7 1.2l-.36 1.42 1.28.64a1 1 0 01.5 1.1l-.36 1.42.5 1.28a1 1 0 01-.5 1.1l-1.28.64.36 1.42a1 1 0 01-.7 1.2l-1.42.36-.64 1.28a1 1 0 01-1.1.5l-1.42-.36-1.28.64a1 1 0 01-1.1-.5L12 21.5l-1.28-.64a1 1 0 01-1.1.5l-1.42-.36-1.28.64a1 1 0 01-1.1-.5l-.64-1.28-1.42-.36a1 1 0 01-.7-1.2l.36-1.42-1.28-.64a1 1 0 01-.5-1.1l.36-1.42-.5-1.28a1 1 0 01.5-1.1l1.28-.64-.36-1.42a1 1 0 01.7-1.2l1.42-.36.64-1.28a1 1 0 011.1-.5l1.42.36 1.28-.64'],
                        3 => ['key' => 'pre_approved',     'label' => 'Saha Metraj', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        4 => ['key' => 'measurement_done', 'label' => 'Tahakkuk & Makbuz', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        5 => ['key' => 'accrued',          'label' => 'Taahhütname', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        6 => ['key' => 'licensed',         'label' => 'Ruhsat',      'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                }
                $currentStep = \App\Enums\ApplicationStatus::workflowStep($st, $isMunicipality);
                $currentStepNum = $currentStep['step'] ?? 0;

                // BELEDiYE KULLANICISI + ALT KURUM BAŞVURUSU + SUBMITTED:
                // Onay rotası Step 2 (Ön Kazı)'dedir.
                // workflowStep() 'submitted' → step:1 döndürür ama belediye için
                // Step 1 'past' (alt kurum gönderdi), Step 2 'current' olmalı.
                if ($kullaniciBelediyeMi && $isAltKurum && $st === 'submitted') {
                    $currentStepNum = 2;
                }
            @endphp

            @foreach($workflowSteps as $num => $step)
                @php
                    $isPast = $num < $currentStepNum;
                    $isCurrent = $num === $currentStepNum;
                    $isFuture = $num > $currentStepNum;

                    if ($isCurrent) {
                        $cardClass = 'border-cyan-400 bg-cyan-50 ring-2 ring-cyan-200';
                        $iconClass = 'text-cyan-700 bg-cyan-100';
                        $textClass = 'text-cyan-900';
                        $badgeClass = 'bg-cyan-600 text-white';
                        $badgeText = 'Aktif';
                        // GÖREV 5 (YENİLEME DAVRANIŞI): Sayfa yenilendiğinde yalnızca TAMAMLANAN
                        // modüller açık gelir; aktif adım da kapalı başlar — kullanıcı tıklayınca açılır.
                        $expanded = false;
                    } elseif ($isPast) {
                        // GÖREV 5 (YENİLEME DAVRANIŞI): Tamamlanmış modüllerin içeriği de sayfa
                        // yüklendiğinde KAPALI gelir (block → hidden); kullanıcı başlığa tıklayınca
                        // açılır. Kart kendisi görünür, yalnızca içerik bölümü kapalı başlar.
                        $cardClass = 'border-emerald-300 bg-emerald-50/70';
                        $iconClass = 'text-emerald-700 bg-emerald-100';
                        $textClass = 'text-emerald-900';
                        $badgeClass = 'bg-emerald-500 text-white';
                        $badgeText = 'Tamamlandı';
                        $expanded = false;
                    } else {
                        $cardClass = 'border-slate-200 bg-slate-50/50';
                        $iconClass = 'text-slate-400 bg-slate-100';
                        $textClass = 'text-slate-500';
                        $badgeClass = 'bg-slate-300 text-white';
                        $badgeText = 'Pasif';
                        $expanded = false;
                    }

                    $stepId = 'step-panel-' . $num;

                    // GÖREV 1 (Gecikmeli Visibility): Belediye metrajı henüz kuruma göndermediyse
                    // (metrage_pending / metrage_revision) alt kurum kullanıcısı Step 3 (Saha Metraj)
                    // kartını ASLA görmez — d-none ile tamamen gizlenir.
                    $hideStep3FromKurum = $num === 3
                        && $isUserInstitution
                        && $application->isInstitutionApplication()
                        && in_array($st, ['metrage_pending', 'metrage_revision']);

                    // GÖREV 5 (YENİLEME DAVRANIŞI): Sayfa yenilendiğinde YALNIZCA "Tamamlandı"
                    // (geçmiş) modüller açık gelir; aktif ve gelecek adımlar kapalı başlar.
                    // Hiçbir adım özel durumla açık gelmez — kullanıcı tıklayınca açılır.
                @endphp
                <div class="rounded-2xl border shadow-sm transition {{ $cardClass }}{{ $hideStep3FromKurum ? ' hidden' : '' }}">
                    <button type="button"
                            onclick="toggleStep('{{ $stepId }}')"
                            class="flex w-full items-center gap-3 px-4 py-3 text-left transition">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $iconClass }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold {{ $textClass }}">{{ $step['label'] }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">{{ $badgeText }}</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $num }}. adım</p>
                        </div>
                        @if($isPast)
                            <svg class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg id="{{ $stepId }}-chevron" class="h-4 w-4 flex-shrink-0 text-slate-400 transition-transform {{ $expanded ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        @endif
                    </button>

                    {{-- Collapsible body --}}
                    <div id="{{ $stepId }}" class="{{ $expanded ? 'block' : 'hidden' }}">
                        <div class="border-t border-slate-200/60 px-4 py-3">
                            {{-- ===== STEP 1: ÖN KAZI (Kurum) / TAHAKKUK & TAHSİLAT FİŞİ (Belediye) ===== --}}
                            @if($num === 1)
                                @if($isMunicipality)
                                    {{-- ===== MUNICIPALITY: TAHAKKUK & TAHSİLAT FİŞİ ===== --}}
                                    @if($st === 'draft')
                                        <form method="POST" action="{{ route('admin.applications.submit', $application) }}" class="mb-3">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg bg-slate-800 py-2.5 text-sm font-medium text-white hover:bg-slate-900">Başvuruyu Gönder</button>
                                        </form>
                                    @endif

                                    {{-- Zemin Satırlarını Düzenle — her durumda göster --}}
                                    @if($can['update'] ?? false)
                                    <button type="button" onclick="document.getElementById('surface-edit-modal').classList.remove('hidden')"
                                            class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-100">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Zemin Satırlarını Düzenle
                                    </button>
                                    @endif

                                    {{-- Fiyat Onayı (tahakkuk başlatma) --}}
                                    @if(in_array($st, ['submitted']) && $isCurrent && ($can['approve_price'] ?? false))
                                        <form method="POST" action="{{ route('admin.applications.approve-price', $application) }}" class="mb-2">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">
                                                <span class="flex items-center justify-center gap-2">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    ✅ Tahakkuk Et & Fiyatlandır
                                                </span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tahsilat Fişi & Makbuz -- MERKEZ BELEDİYE (vatandaş): serbest erişim — statüden bağımsız göster + Tahakkuk Fişi PDF --}}
                                    @if($isMuniApp || in_array($st, ['excavation_completed', 'metrage_pending', 'metrage_sent', 'metrage_revision', 'metrage_approved', 'awaiting_payment', 'receipt_pending', 'pre_approved', 'measurement_done', 'tahakkuk_pending', 'tahakkuk_sent', 'taahhutname_pending', 'taahhutname_sent', 'approved', 'licensed', 'completed']))
                                        {{-- MERKEZ BELEDİYE: Tahakkuk Fişi (PDF) — alt kurum Step 4 ile aynı yapı --}}
                                        @if($isMuniApp)
                                        <a href="{{ route('admin.applications.pdf.tahakkuk', $application) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-rose-300 bg-rose-50 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            📄 Tahakkuk Fişi (PDF) Görüntüle / Yazdır
                                        </a>
                                        @endif
                                        <a href="{{ route('admin.applications.pdf.tahsilat-fisi', $application) }}"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-amber-400 bg-amber-50 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                            </svg>
                                            Tahsilat Fişi (PDF)
                                        </a>
                                        <a href="{{ route('admin.applications.payment-receipt', $application) }}"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                            Tahsilat Makbuzu (Özet)
                                        </a>
                                        {{-- MERKEZ BELEDİYE: Tahakkuk Belgesini Düzenle (Kaydet) — belediye personeli --}}
                                        @if($isMuniApp && !$isUserInstitution && ($can['update'] ?? false))
                                        <a href="{{ route('admin.applications.edit-document', [$application, 'tahakkuk']) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            ✏️ Tahakkuk Belgesini Düzenle (Kaydet)
                                        </a>
                                        @endif
                                    @endif

                                    {{-- Makbuz Yükleme ve Onay --}}
                                    @if(in_array($st, ['awaiting_payment', 'receipt_pending']) && $isCurrent)
                                        @if($can['approve_receipt'] ?? false)
                                            <form id="muni-receipt-upload-form" method="POST"
                                                  action="{{ route('admin.applications.approve-receipt', $application) }}"
                                                  enctype="multipart/form-data" novalidate class="mb-2">
                                                @csrf
                                                @if(!$latestReceipt || $latestReceipt->status !== 'approved')
                                                <div class="mb-2 rounded-lg border border-dashed border-slate-300 bg-white p-2">
                                                    <p class="mb-1 text-xs font-medium text-slate-600">Makbuz yükle</p>
                                                    <div id="muni-receipt-drop-zone" class="relative flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 px-2 py-3 text-center transition hover:border-emerald-400 hover:bg-emerald-50/30"
                                                         onclick="document.getElementById('muni-receipt_file_input').click()">
                                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                        </svg>
                                                        <p class="text-xs text-slate-500"><span class="font-semibold text-emerald-700">Dosya seç</span></p>
                                                    </div>
                                                    <input type="file" id="muni-receipt_file_input" name="receipt_file"
                                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png,image/jpg" class="sr-only">
                                                    <div id="muni-receipt-file-preview" class="mt-1.5 hidden items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-800">
                                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span id="muni-receipt-file-name" class="truncate"></span>
                                                        <button type="button" id="muni-receipt-file-clear" class="ml-auto text-[10px] font-medium text-rose-600 hover:underline">Kaldır</button>
                                                    </div>
                                                </div>
                                                @endif
                                                <button type="submit" id="muni-receipt-submit-btn"
                                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-800 py-2.5 text-sm font-medium text-white hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Makbuz Onayla
                                                </button>
                                            </form>
                                        @endif

                                        @if($can['reject_receipt'] ?? false)
                                            <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-3 mb-2">
                                                <p class="mb-2 text-xs font-semibold text-rose-800">Makbuz Reddi</p>
                                                <form method="POST" action="{{ route('admin.applications.reject-receipt', $application) }}"
                                                      class="space-y-2"
                                                      onsubmit="return confirm('Makbuz reddedilsin mi?');">
                                                    @csrf
                                                    <textarea name="review_notes" rows="2" required placeholder="Ret gerekçesini yazın"
                                                        class="w-full rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-xs focus:border-rose-400 focus:outline-none focus:ring-1 focus:ring-rose-100"></textarea>
                                                    <button type="submit" class="w-full rounded-lg bg-rose-700 py-1.5 text-xs font-medium text-white hover:bg-rose-800">Makbuzu Reddet</button>
                                                </form>
                                            </div>
                                        @endif
                                    @endif

                                    {{-- Tahakkuk Bilgileri (makbuz no inputları) — MERKEZ BELEDİYE (vatandaş) GİZLİ; yalnızca alt kurum akışında --}}
                                    @if(($can['update'] ?? false) && $application->isInstitutionApplication())
                                    <form method="POST" action="{{ route('admin.applications.save-receipt-info', $application) }}" class="mb-3 rounded-xl border border-slate-200 bg-white shadow-sm">
                                        <div class="p-4 space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <p class="text-xs font-semibold text-slate-600">Tahakkuk Bilgileri</p>
                                        <input type="text" name="ztb_receipt_info" value="{{ old('ztb_receipt_info', $application->ztb_receipt_info) }}"
                                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder-slate-400 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200" placeholder="ZTB Makbuz No ve Tarihi">
                                        <input type="text" name="deposit_receipt_info" value="{{ old('deposit_receipt_info', $application->deposit_receipt_info) }}"
                                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder-slate-400 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200" placeholder="Teminat Makbuz No ve Tarihi">
                                        <button type="submit" class="w-full rounded-lg bg-cyan-600 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cyan-500 transition">
                                            Tahakkuk Bilgilerini Kaydet
                                        </button>
                                        </div>
                                    </form>
                                    @endif

                                    {{-- PING-PONG: İmzalı Tahakkuk Fişi --}}
                                    @include('admin.applications._signed_document_upload', ['module' => 'tahakkuk', 'label' => 'İmzalı Tahakkuk Fişi'])
                                    {{-- PING-PONG: İmzalı Tahsilat Makbuzu --}}
                                    @include('admin.applications._signed_document_upload', ['module' => 'makbuz', 'label' => 'İmzalı Tahsilat Makbuzu'])

                @else
                                    {{-- ===== INSTITUTION: ÜST YAZI (Step 1) ===== --}}

                                    {{-- GÖREV 1 + GÖREV 2: Üst Yazı evrakı KALICI görünür.
                                         - Kurum uygulamasında Üst Yazı (Dilekçe) PDF'i her durumda görüntülenir.
                                         - Editör (World) butonu YALNIZCA draft durumunda; submit/devralma sonrası tamamen silinir.
                                         - Ön kazı işleme alındıysa (draft/submitted değilse) Ön Kazı İzin PDF'i de KALICI görünür. --}}
                                    @if($isAltKurum)
                                        {{-- Üst Yazı (Dilekçe) Görüntüle / İndir — KALICI --}}
                                        <a href="{{ route('admin.applications.pdf.cover-letter', $application) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📄 Üst Yazı (Dilekçe) Görüntüle / İndir (PDF)
                                        </a>

                                        {{-- Üst Yazı Taslak Düzenleme: alt kurum kullanıcısı, SADECE draft/rejected/revision --}}
                                        @if(!$kullaniciBelediyeMi && in_array($guncelStatus, ['draft', 'rejected', 'revision']))
                                        <a href="{{ route('admin.applications.edit-document', [$application, 'cover_letter']) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            ✏️ Üst Yazı Taslağını Düzenle
                                        </a>
                                        @endif

                                        {{-- 16.08 (5. tur) FIX: Belediye personeli için de aynı buton — eskiden
                                             SADECE alt kurum görüyordu. Başvuru belediyeye geldikten sonra, Ön Kazı
                                             İzni GERÇEKTEN e-imza ile imzalanana kadar (Onay Rotası geçilmeden ÖNCE)
                                             belediye personeli de Üst Yazı'yı Word gibi düzenleyebilmeli. --}}
                                        @php
                                            $ustYaziImzalandiMi = data_get($application->module_documents, 'pre_permit.status') === 'completed';
                                        @endphp
                                        @if($kullaniciBelediyeMi && !$ustYaziImzalandiMi)
                                        <a href="{{ route('admin.applications.edit-document', [$application, 'cover_letter']) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            ✏️ Üst Yazı Taslak Düzenleme (Word)
                                        </a>
                                        @endif
                                    @endif

                                    {{-- draft/submitted: gönder ile onay rotası (yalnızca bu statülerde) --}}
                                    @if(in_array($st, ['draft', 'submitted']))
                                        @if($st === 'draft')
                                            <form method="POST" action="{{ route('admin.applications.submit', $application) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg bg-slate-800 py-2.5 text-sm font-medium text-white hover:bg-slate-900">Başvuruyu Belediyeye Gönder</button>
                                            </form>
                                        @endif

                                        {{-- BELEDİYE KULLANICISI: Onay rotası Step 1'de (Üst Yazı) değil,
                                             Step 2'de (Ön Kazı) görünür. Burada sadece bilgi mesajı. --}}
                                        @if($isAltKurum && $kullaniciBelediyeMi && $st === 'submitted')
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 mb-2">
                                                <p class="text-xs font-semibold text-amber-800">📋 Üst Yazı Alındı</p>
                                                <p class="text-[11px] text-amber-700 mt-1">Alt kurum üst yazısı iletildi. Onay rotası için <strong>Ön Kazı İzni</strong> adımına geçiniz.</p>
                                            </div>
                                        @endif

                                        @if($isAltKurum && !$kullaniciBelediyeMi)
                                            @php $onayStage = $application->approval_stage ?? ($processCurrentStep?->role_key ?? 'staff'); @endphp

                                            {{-- Süreç & Onay Rotası: action_type'a göre dinamik buton --}}
                                            @php $actionType = $processCurrentStep?->action_type ?? 'onay'; @endphp

                                            @include('admin.applications._process_steps_indicator')

                                            {{-- PARAF BUTONU --}}
                                            @if($isCurrent && ($can['paraf'] ?? false) && $actionType === 'paraf')
                                                <form method="POST" action="{{ route('admin.applications.paraf-step', $application) }}" class="mb-2">
                                                    @csrf
                                                    <button type="submit"
                                                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 py-2.5 text-sm font-medium text-white hover:bg-amber-700">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                        </svg>
                                                        📝 {{ $processCurrentStep?->name ?: 'Paraf At' }} — Paraf At &amp; Gönder
                                                    </button>
                                                </form>

                                            {{-- E-İMZA BUTONU (GÖREV: 16.08 fix — artık GERÇEK e-imza akışını
                                                 tetikler; eskiden var olmayan bir modal açmaya çalışıp
                                                 sessizce başarısız oluyordu) --}}
                                            @elseif($isCurrent && ($can['e_imza'] ?? false) && $actionType === 'e_imza')
                                                @php $stepPdfType = data_get($processCurrentStep?->signature_config, 'pdf_type') ?: 'cover_letter'; @endphp
                                                <button type="button" class="e-imza-btn mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                                                        data-app-id="{{ $application->id }}"
                                                        data-pdf-type="{{ $stepPdfType }}"
                                                        data-vice-mayor-name="{{ $application->vice_mayor_name }}"
                                                        data-update-vice-mayor-url="{{ route('admin.applications.update-vice-mayor-name', $application) }}">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                                    </svg>
                                                    🔏 {{ $processCurrentStep?->name ?: 'E-İmza At' }} — E-İmza At &amp; Gönder
                                                </button>

                                            {{-- ONAY BUTONU (varsayılan) --}}
                                            @elseif($isCurrent && ($can['approve_current'] ?? false))
                                                @if($processCurrentStepIsFinal)
                                                <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.remove('hidden')"
                                                        class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 py-2.5 text-sm font-medium text-white hover:bg-amber-700">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    ✅ {{ $processCurrentStep?->name ?: 'Başkan Yrd. Onayı' }} / Ön Kazı İzni Ver
                                                </button>
                                                @else
                                                <form method="POST" action="{{ route('admin.applications.approve-pre-excavation', $application) }}" class="mb-2">
                                                    @csrf
                                                    <button type="submit"
                                                            class="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-medium text-white {{ $onayStage === 'director' ? 'bg-indigo-700 hover:bg-indigo-800' : 'bg-cyan-700 hover:bg-cyan-800' }}">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        ✅ {{ $processCurrentStep?->name ?: 'Onayla' }} — Onayla &amp; Gönder
                                                    </button>
                                                </form>
                                                @endif
                                            @endif
                                        @endif
                                    @endif {{-- /in_array draft|submitted --}}

                                    {{-- İşlem Tabı (Üst Yazı): SADECE alt kurum kullanıcısı görür --}}
                                    @if($isAltKurum && $isUserInstitution && ($isCurrent || $isPast))
                                    <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50/60 p-3 space-y-2">
                                        <p class="text-xs font-semibold text-blue-800 mb-1">📋 İşlem Tabı — Üst Yazı</p>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-1">Açıklama</label>
                                            <textarea name="step_aciklama_1" rows="2" class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-blue-400 focus:outline-none" placeholder="Açıklama veya not giriniz..."></textarea>
                                        </div>
                                        @include('admin.applications._signed_document_upload', ['module' => 'cover_letter_signed', 'label' => '🗂 İmzalı Üst Yazı Nüshası'])
                                    </div>
                                    @endif
                                @endif

{{-- ===== STEP 2: ÖN KAZI — BELEDiYE: ONAY ROTASI + Ön Kazı İzni / KURUM: Görüntüleme ===== --}}
                            @elseif($num === 2)
                                @if($isCurrent || $isPast)
                                    @if(!$isMunicipality)

                                        {{-- ===== BELEDiYE KULLANICISI: ONAY ROTASI BURAYA ===== --}}
                                        @if(!$isUserInstitution && $isAltKurum && in_array($st, ['submitted']))
                                            @php $onayStage = $application->approval_stage ?? ($processCurrentStep?->role_key ?? 'staff'); @endphp
                                            @php $actionType = $processCurrentStep?->action_type ?? 'onay'; @endphp

                                            <div class="mb-3 rounded-xl border border-cyan-300 bg-cyan-50 p-3">
                                                <p class="text-xs font-semibold text-cyan-800 mb-2">📋 Ön Kazı Onay Rotası</p>

                                                @include('admin.applications._process_steps_indicator')

                                                {{-- PARAF BUTONU --}}
                                                @if($isCurrent && ($can['paraf'] ?? false) && $actionType === 'paraf')
                                                    <form method="POST" action="{{ route('admin.applications.paraf-step', $application) }}" class="mb-2">
                                                        @csrf
                                                        <button type="submit"
                                                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 py-2.5 text-sm font-medium text-white hover:bg-amber-700">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                            </svg>
                                                            📝 {{ $processCurrentStep?->name ?: 'Paraf At' }} — Paraf At &amp; Gönder
                                                        </button>
                                                    </form>

                                                {{-- E-İMZA BUTONU (GÖREV: 16.08 fix — artık GERÇEK e-imza akışını
                                                     tetikler; eskiden var olmayan bir modal açmaya çalışıp
                                                     sessizce başarısız oluyordu) --}}
                                                @elseif($isCurrent && ($can['e_imza'] ?? false) && $actionType === 'e_imza')
                                                    @php $stepPdfType = data_get($processCurrentStep?->signature_config, 'pdf_type') ?: 'pre_permit'; @endphp
                                                    <button type="button" class="e-imza-btn mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                                                            data-app-id="{{ $application->id }}"
                                                            data-pdf-type="{{ $stepPdfType }}"
                                                            data-vice-mayor-name="{{ $application->vice_mayor_name }}"
                                                            data-update-vice-mayor-url="{{ route('admin.applications.update-vice-mayor-name', $application) }}">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                                        </svg>
                                                        🔏 {{ $processCurrentStep?->name ?: 'E-İmza At' }} — E-İmza At &amp; Gönder
                                                    </button>

                                                {{-- ONAY BUTONU (varsayılan) --}}
                                                @elseif($isCurrent && ($can['approve_current'] ?? false))
                                                    @if($processCurrentStepIsFinal)
                                                    <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.remove('hidden')"
                                                            class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 py-2.5 text-sm font-medium text-white hover:bg-amber-700">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        ✅ {{ $processCurrentStep?->name ?: 'Başkan Yrd. Onayla' }} / Ön Kazı İzni Ver
                                                    </button>
                                                    @else
                                                    <form method="POST" action="{{ route('admin.applications.approve-pre-excavation', $application) }}" class="mb-2">
                                                        @csrf
                                                        <button type="submit"
                                                                class="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-medium text-white {{ $onayStage === 'director' ? 'bg-indigo-700 hover:bg-indigo-800' : 'bg-cyan-700 hover:bg-cyan-800' }}">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            ✅ {{ $processCurrentStep?->name ?: 'Onayla' }} — Onayla &amp; Gönder
                                                        </button>
                                                    </form>
                                                    @endif
                                                @else
                                                    {{-- Sıradaki adım bilgisi --}}
                                                    @php
                                                        $stageLabels2 = [
                                                            'municipality-buro'  => 'Büro Personeli Onayı Bekleniyor',
                                                            'municipality-sef'   => 'Birim Şefi Parafı Bekleniyor',
                                                            'municipality-mudur' => 'Müdür E-İmzası Bekleniyor',
                                                            'municipality-makam' => 'Başkan Yrd. E-İmzası Bekleniyor',
                                                        ];
                                                        $stageLabel2 = $stageLabels2[$application->approval_stage ?? ''] ?? ucfirst($application->approval_stage ?? 'Bekliyor');
                                                    @endphp
                                                    <p class="text-[11px] text-cyan-700">⏳ {{ $stageLabel2 }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Ön Kazı İzin Belgesi (PDF) Görüntüle / İndir -- GÖREV 2 kalıcı --}}
                                        @if($passedOnKazi)
                                        <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}" target="_blank"
                                           class="flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 py-2.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100 mb-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📥 Ön Kazı İzin Belgesi (PDF) Görüntüle / İndir
                                        </a>
                                        @endif

                                        {{-- BELEDiYE KULLANICISI: Taslak düzenleme ve imzalı nüsha — ÖN KAZIDA --}}
                                        {{-- NOT: Üst Yazı taslak düzenleme Step 1'а (alt kurum için, draft/revision durumunda) --}}
                                        @if(!$isUserInstitution)
                                            {{-- Ön Kazı İzni (on_kazi) taslak düzenleme --}}
                                            @if($kullaniciBelediyeMi && $canEditTemplate)
                                            <a href="{{ route('admin.applications.edit-document', [$application, 'on_kazi']) }}" target="_blank"
                                               class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                ✏️ Ön Kazı İzni Taslak Düzenleme (Word)
                                            </a>
                                            @endif
                                            {{-- İmzalı Ön Kazı Nüshası yükleme (belediye) --}}
                                            <div class="mt-2 rounded-xl border border-cyan-200 bg-cyan-50/60 p-3 space-y-2">
                                                <p class="text-xs font-semibold text-cyan-800 mb-1">📋 İşlem Tabı — Ön Kazı</p>
                                                @include('admin.applications._signed_document_upload', ['module' => 'on_kazi_signed', 'label' => '🗂 İmzalı Ön Kazı Nüshası'])
                                            </div>
                                        @else
                                        {{-- ALT KURUM: Ön Kazı işlem tabı --}}
                                        <div class="mt-3 rounded-xl border border-cyan-200 bg-cyan-50/60 p-3 space-y-2">
                                            <p class="text-xs font-semibold text-cyan-800 mb-1">📋 İşlem Tabı — Ön Kazı</p>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-600 mb-1">Açıklama</label>
                                                <textarea rows="2" class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-cyan-400 focus:outline-none" placeholder="Ön kazı hakkında not..."></textarea>
                                            </div>
                                            @include('admin.applications._signed_document_upload', ['module' => 'on_kazi_signed', 'label' => '🗂 İmzalı Ön Kazı Nüshasını Yükle'])
                                        </div>
                                        @endif
                                    @endif

                                    {{-- ===== MUNICIPALITY APP: STEP 2 = KAZI METRAJ BİLGİ (yalnızca belediye uygulaması) ===== --}}
                                    @if($isMunicipality && !$isUserInstitution)
                                        {{-- GÖREV 1 (EBYS KİLİT): Saha Personeli Devret eylemi KALDIRILDI. field-tasks.store
                                             statüyü FieldWork'a çekip sahte/ileri kilit açabiliyordu; bu yapıda kullanılmıyor. --}}

                                        {{-- Belediye personeli için metraj PDF ve düzenleme -- GÖREV 2 kalıcı --}}
                                        {{-- MERKEZ BELEDİYE (vatandaş) SERBEST ERİŞİM: pre_approved dahil her aşamada göster --}}
                                        @if($isMuniApp ? true : $passedMetraj)
                                        <a href="{{ route('admin.applications.pdf.metraj', $application) }}" target="_blank"
                                           class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 mb-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📄 Kazı Metraj Cetveli (PDF) Görüntüle
                                        </a>

                                        {{-- GÖREV 3: Metraj edit butonu yalnızca $duzenlemeAcik (belediye VEYA draft/rejected/revision) --}}
                                        <!-- LOG DEBUG - Metraj BldMi: {{ $kullaniciBelediyeMi ? 'Evet' : 'Hayir' }} | ST: {{ $guncelStatus }} | DznAcik: {{ $duzenlemeAcik ? 'Evet' : 'Hayir' }} -->
                                        @if($duzenlemeAcik)
                                        <a href="{{ route('admin.applications.edit-document', [$application, 'metraj']) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            ✏️ Belgeyi Düzenle (Kaydet)
                                        </a>
                                        @endif
                                        @endif

                                        {{-- PING-PONG: İmzalı Metraj Belgesi --}}
                                        @include('admin.applications._signed_document_upload', ['module' => 'metraj', 'label' => 'İmzalı Metraj Belgesi'])
                                    @endif

                                @endif

{{-- ===== STEP 3: SAHA METRAJ (Kurum) / TAAHHÜTNAME (Belediye) ===== --}}
                            @elseif($num === 3)
                                @if($isMunicipality)
                                    {{-- MUNICIPALITY STEP 3: TAAHHÜTNAME --}}
                                    {{-- MERKEZ BELEDİYE (vatandaş) SERBEST ERİŞİM: kart her aşamada açık --}}
                                    @if($isMuniApp ? true : ($isCurrent || $isPast))
                                        <p class="mb-2 text-xs text-slate-500">Taahhütname belgesini buradan yükleyin.</p>
                                        <a href="{{ route('admin.applications.pdf.taahhutname', $application) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📄 Taahhütname (PDF) Görüntüle
                                        </a>
                                        {{-- Taahhütname Notu: Yalnızca belediye personeli görür --}}
                                        @if(!$isUserInstitution && ($can['update'] ?? false))
                                        <form method="POST" action="{{ route('admin.applications.save-receipt-info', $application) }}" class="mb-2 p-2 rounded-lg border border-slate-200 bg-white">
                                            @csrf
                                            @method('PUT')
                                            <p class="mb-1 text-xs font-semibold text-slate-600">Taahhütname Notu</p>
                                            <textarea name="taahhutname_notu" rows="2" class="mb-1 block w-full rounded border-slate-300 text-xs" placeholder="Taahhütname ile ilgili not...">{{ old('taahhutname_notu', $application->taahhutname_notu ?? '') }}</textarea>
                                            <button type="submit" class="w-full rounded bg-indigo-600 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Kaydet</button>
                                        </form>
                                        @endif
                                        @include('admin.applications._signed_document_upload', ['module' => 'taahhutname', 'label' => 'İmzalı Taahhütname'])
                                    @endif
                                @else
                                    {{-- INSTITUTION STEP 3: SAHA METRAJ --}}
                                    {{-- KURAL: "Zemin Satırlarını Düzenle" butonu ALT KURUM için KESİNLİKLE GİZLİ --}}
                                    {{-- UYARI: Bu kartta ASLA tahakkuk formu, makbuz veya tahsilat no bilgisi İSTENMEZ; onlar STEP 4'tedir. --}}
                                    {{-- GÖREV 2 KATI STATÜ DUVARI: excavation_completed ve öncesinde kart TAMAMEN render EDİLMEZ;
                                         modül ancak belediye metrage_pending kilidini açınca görünür. --}}
                                    {{-- GÖREV 1 (Gecikmeli Visibility): Alt kurum kullanıcısı Step 3'ü yalnızca
                                         statü metrage_sent (belediye "Kuruma Gönder" dediğinde) ve sonrasında görür;
                                         metrage_pending/metrage_revision anında belediye çalışması gizlidir. --}}
                                    @php
                                        // GÖREV 2: metraj bir kez "Kuruma Gönder" edildiyse (metrage_sent sonrası) ileri
                                        // statülerde de (tahakkuk_sent/ruhsat_sent dahil) KALICI görünür.
                                        $step3KurumGorebilir = $passedMetraj || in_array($st, ['metrage_sent', 'metrage_approved', 'measurement_done', 'priced', 'awaiting_payment', 'receipt_pending', 'tahakkuk_pending', 'tahakkuk_sent', 'payment_completed', 'taahhutname_pending', 'taahhutname_sent', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                        $step3BelediyeGorebilir = $passedMetraj || in_array($st, ['metrage_pending', 'metrage_sent', 'metrage_revision', 'metrage_approved', 'measurement_done', 'priced', 'awaiting_payment', 'receipt_pending', 'tahakkuk_pending', 'tahakkuk_sent', 'payment_completed', 'taahhutname_pending', 'taahhutname_sent', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                        $step3Gorunur = ($isUserInstitution && $step3KurumGorebilir) || (!$isUserInstitution && $step3BelediyeGorebilir);
                                    @endphp
                                    @if(($isCurrent || $isPast) && $step3Gorunur)
                                        {{-- Metraj (PDF) Görüntüle / İndir — her iki taraf -- GÖREV 2 kalıcı --}}
                                        @if($passedMetraj)
                                        <a href="{{ route('admin.applications.pdf.metraj', $application) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 py-2.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📄 Kazı Metraj Cetveli (PDF) Görüntüle / İndir
                                        </a>
                                        @endif

                                        {{-- GÖREV 3 PİNG-PONG Kontrol Paneli (Alt kurum / taşeron): onay + red (zorunlu not) --}}
                                        {{-- Vatandaş/Merkez (isInstitution=false) için eski akış (approve-price) korunur. --}}
                                        @if($isUserInstitution && $isCurrent && $application->isInstitutionApplication() && $st === 'metrage_sent')
                                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-3 space-y-2 mb-2">
                                            <p class="text-xs font-semibold text-indigo-800">📋 Metraj Onay Kontrolü (Kurum)</p>
                                            <form method="POST" action="{{ route('admin.applications.approve-metrage', $application) }}">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Kazı metraj formunu onaylıyor musunuz?')"
                                                        class="w-full flex items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition">
                                                    ✅ Kazı Metraj Formunu Onaylıyorum
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.applications.reject-metrage', $application) }}" class="space-y-1">
                                                @csrf
                                                <textarea name="reject_note" rows="2" required placeholder="Reddetme gerekçesi (zorunlu)..."
                                                          class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-rose-400 focus:outline-none"></textarea>
                                                <button type="submit" onclick="return confirm('Metrajı belediyeye geri gönderiyorsunuz. Emin misiniz?')"
                                                        class="w-full rounded-lg bg-rose-600 py-2.5 text-sm font-bold text-white hover:bg-rose-700 transition">
                                                    ❌ ONAYLAMIYORUM / Belediyeye Geri Gönder
                                                </button>
                                            </form>
                                        </div>
                                        @elseif($isUserInstitution && $isCurrent && !$application->isInstitutionApplication())
                                        <form method="POST" action="{{ route('admin.applications.approve-price', $application) }}" class="mb-2">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Kazı metrajını onaylıyor musunuz?')"
                                                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                ✅ Kazı Metrajını Onaylıyorum
                                            </button>
                                        </form>
                                        @endif

                                        {{-- Belediye personeli: zemin düzenleme + metraj onayı + saha görevi + taslak düzenleme --}}
                                        @if(!$isUserInstitution)
                                            @if($can['update'] ?? false)
                                            <button type="button" onclick="document.getElementById('surface-edit-modal').classList.remove('hidden')"
                                                    class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-100">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Zemin Satırlarını Düzenle
                                            </button>
                                            @endif

                                            @if(($can['approve_price'] ?? false) && auth()->user()->hasAnyRole(['super-admin', 'municipality-admin']))
                                            @if($application->isInstitutionApplication() && in_array($st, ['metrage_pending', 'metrage_revision']))
                                            <form method="POST" action="{{ route('admin.applications.send-metrage', $application) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">🟦 Saha Metrajı Alt Kurumun Onayına Gönder</button>
                                            </form>
                                            @elseif(!$application->isInstitutionApplication())
                                            <form method="POST" action="{{ route('admin.applications.approve-price', $application) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">Metrajı Onayla &amp; Kuruma Gönder</button>
                                            </form>
                                            @endif
                                            @endif

                                            {{-- GÖREV 1 (EBYS KİLİT): "Saha Personeline Devret" eylemi KÖKTEN SİLİNDİ.
                                                 field-tasks.store statüyü FieldWork'a çekip sahte/ileri kilit açabiliyordu;
                                                 bu yapıda kullanılmıyor, form kargaşasını azaltır. --}}

                                            {{-- GÖREV 3: Metraj edit butonu yalnızca $duzenlemeAcik (belediye VEYA draft/rejected/revision) --}}
                                            <!-- LOG DEBUG - Metraj2 BldMi: {{ $kullaniciBelediyeMi ? 'Evet' : 'Hayir' }} | ST: {{ $guncelStatus }} | DznAcik: {{ $duzenlemeAcik ? 'Evet' : 'Hayir' }} -->
                                            @if($duzenlemeAcik)
                                            <a href="{{ route('admin.applications.edit-document', [$application, 'metraj']) }}" target="_blank"
                                               class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                ✏️ Belgeyi Düzenle (Kaydet)
                                            </a>
                                            @endif

                                            {{-- PING-PONG: İmzalı Metraj Belgesi --}}
                                            @include('admin.applications._signed_document_upload', ['module' => 'metraj', 'label' => 'İmzalı Metraj Belgesi'])
                                        @endif

                                        {{-- BÜYÜK YASA: Saha Metraj adımı aktif ise alt kuruma işlem tabı --}}
                                        @if($isUserInstitution && ($isCurrent || $isPast))
                                        <div class="mt-3 rounded-xl border border-indigo-200 bg-indigo-50/60 p-3 space-y-2">
                                            <p class="text-xs font-semibold text-indigo-800 mb-1">📋 İşlem Tabı — Saha Metraj</p>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-600 mb-1">Açıklama</label>
                                                <textarea rows="2" class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-indigo-400 focus:outline-none" placeholder="Saha metraj hakkında not..."></textarea>
                                            </div>
                                            @include('admin.applications._signed_document_upload', ['module' => 'metraj_signed', 'label' => '🗂 İmzalı Metraj Nüshasını Yükle'])
                                        </div>
                                        @endif
                                    @endif
                                @endif

                            {{-- ===== STEP 4 (Kurum): TAHAKKUK & MAKBUZ / (Belediye): RUHSAT ===== --}}
                            @elseif($num === 4)
                                @if($isMunicipality)
                                    {{-- MUNICIPALITY STEP 4: RUHSAT --}}
                                    @php
                                        $makbuzlarDolu = $application->ztb_receipt_info && $application->deposit_receipt_info;
                                        $isLicensed = $st === 'licensed';
                                    @endphp
                                    <!-- LOG DEBUG - Ruhsat BldMi: {{ $kullaniciBelediyeMi ? 'Evet' : 'Hayir' }} | ST: {{ $guncelStatus }} | DznAcik: {{ $duzenlemeAcik ? 'Evet' : 'Hayir' }} -->
                                    @if(auth()->user()->hasAnyRole(['super-admin', 'municipality-admin']) && $duzenlemeAcik)
                                        <a href="{{ route('admin.applications.edit-document', [$application, 'ruhsat']) }}" target="_blank"
                                           class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            📊 Taslağı Aç & Bu Başvuruya Özel Düzenle (Excel)
                                        </a>
                                    @endif
                                    {{-- MERKEZ BELEDİYE (vatandaş) SERBEST ERİŞİM: ruhsat kartı her aşamada açık --}}
                                    @if($isMuniApp ? true : ($isCurrent || $isPast))
                                        {{-- GÖREV: Belediye Ruhsat modülünü açtığı (licensed) ve ilerlettiği (ruhsat_sent)
                                             andan itibaren Ruhsat belgesi KALICI görünür; makbuz formu PDF'in ALTINDA sunulur. --}}
                                        <div class="mt-4 mb-2">
                                        <a target="_blank" href="{{ route('admin.applications.pdf.ruhsat', $application) }}" class="flex items-center justify-center gap-2 w-full bg-sky-600 hover:bg-sky-700 text-white text-[14px] md:text-[15px] font-bold py-3.5 px-4 rounded-xl shadow-md transition-colors text-center border-0 leading-tight">
                                            🖨️ AÇIM RUHSATI (FR-290) BELGESİNİ İNDİR / YAZDIR
                                        </a>
                                        @include('admin.applications._signed_document_upload', ['module' => 'ruhsat', 'label' => 'İmzalı Ruhsat Belgesi'])
                                        </div>
                                        {{-- MERKEZ BELEDİYE: makbuz no formu GİZLİ (yalnızca alt kurum akışında belediye doldurur) --}}
                                        @if(!$isMuniApp && !$makbuzlarDolu && !$isLicensed)
                                        <form method="POST" action="{{ route('admin.applications.save-receipt-info', $application) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-600 mb-1">ZTB Makbuz No ve Tarihi:</label>
                                                <input type="text" name="ztb_receipt_info" value="{{ old('ztb_receipt_info', $application->ztb_receipt_info) }}"
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                                       placeholder="Örn: 22.07.2026-0938547">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Teminat Makbuz No ve Tarihi:</label>
                                                <input type="text" name="deposit_receipt_info"
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                                       value="{{ old('deposit_receipt_info', $application->deposit_receipt_info) }}"
                                                       placeholder="Örn: 22.07.2026-0938548">
                                            </div>
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-cyan-600 py-2 text-sm font-bold text-white hover:bg-cyan-500 transition">
                                                Makbuzları Kaydet
                                            </button>
                                        </form>
                                        @endif
                                    @endif
@else
                                    {{-- INSTITUTION STEP 4: TAHAKKUK & MAKBUZ --}}
                                    {{-- KURAL: Alt kurum için bu kart YALNIZCA tahakkuk oluşturulduysa (state tahakkuk bekliyorsa) görünür --}}
                                    {{-- BÜYÜK YASA: Bu kartta ASLA taahhütname PDF / e-imza / İşlem Tabı-Taahhütname bulunmaz; onlar STEP 5'tedir. --}}
                                    @php
                                        // GÖREV 2 KATI STATÜ DUVARI: Tahakkuk & Makbuz modülü ancak belediye
                                        // open-tahakkuk kilidini açınca (tahakkuk_pending) belediye hazırlar;
                                        // GÖREV 4 GLOBAL SENT-MODEL: Alt kurum Tahakkuk & Makbuz kartını yalnızca
                                        // belediye "tahakkuk_sent" gönderimi yaptıktan SONRA görür.
                                        // GÖREV 3: Tahakkuk & Makbuz evrakı belediye "tahakkuk_sent" gönderimiyle
                                        // KALICI olarak alt kuruma görünür. Taahhütname kilitleri (taahhutname_*) de bu
                                        // listeye eklendi — çünkü openTaahhutname status'ü taahhutname_pending/sent'e
                                        // çevirir ve alt kurumun Step 4 evrakını görmeye devam etmesi gerekir.
                                        // GÖREV 4: Alt kurumun ÖDEME DEKONTU yükleyebilmesi için kart,
                                        // fiyatlandırma (awaiting_payment) ve makbuz bekleme (receipt_pending)
                                        // anlarından itibaren görünür olmalıdır — aksi halde dekont girişi imkânsız olur.
                                        $tahakkukGeldi = in_array($st, ['awaiting_payment', 'receipt_pending',
                                            'tahakkuk_sent', 'accrued', 'approved',
                                            'payment_completed', 'taahhutname_pending', 'taahhutname_sent',
                                            'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                    @endphp
                                    @if($isUserInstitution && !$tahakkukGeldi)
                                        {{-- Alt kurum: Tahakkuk henüz hazır değil — bu adım gizli (d-none eşdeğeri) --}}
                                        <p class="text-xs text-slate-400 text-center py-4">Bu adım belediye tahakkuk&nbsp;&amp;&nbsp;makbuz bilgilerini hazırladıktan sonra aktif olacaktır.</p>
                                    @elseif(($isCurrent || $isPast) || ($isUserInstitution && in_array($st, ['awaiting_payment', 'receipt_pending'])))
                                        {{-- GÖREV 2: ZTB/Teminat makbuz parametreleri ARTIK BURADA YOK; onlar Step 6 (Ruhsat)
                                             modülüne taşındı çünkü doğrudan Ruhsat belgesinin üstüne basılırlar. --}}

                                        {{-- GÖREV 3: Tahakkuk & Tahsilat belgelerini A4 olarak görüntüle/yazdır —
                                             Belediye (ve kurum onay aşamasında Altkurum) evraka bakmadan imzalayıp
                                             "Yükle ve Gönder" yapamaz. --}}
                                        <div class="mb-2 grid gap-2">
                                            <a href="{{ route('admin.applications.pdf.tahakkuk', $application) }}" target="_blank"
                                               class="flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 py-2.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                                📄 Tahakkuk Fişini Görüntüle (A4 Düzenle)
                                            </a>
                                            <a href="{{ route('admin.applications.pdf.tahsilat-fisi', $application) }}" target="_blank"
                                               class="flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 py-2.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                                📄 Tahsilat Fişini Görüntüle (A4 Düzenle)
                                            </a>
                                        </div>

                                        {{-- GÖREV 4: Merkez belediye, tahakkuk/tahsilat belgelerini GÖNDERMEDEN önce
                                             düzenleyip kaydedebilir — metraj modülündeki "Belgeyi Düzenle (Kaydet)" ile aynı. --}}
                                        @if(!$isUserInstitution && ($can['update'] ?? false))
                                        <div class="mb-2 grid gap-2">
                                            <a href="{{ route('admin.applications.edit-document', [$application, 'tahakkuk']) }}" target="_blank"
                                               class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                ✏️ Tahakkuk Belgesini Düzenle (Kaydet)
                                            </a>
                                            <a href="{{ route('admin.applications.edit-document', [$application, 'tahsilat_fisi']) }}" target="_blank"
                                               class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                ✏️ Tahsilat Fişini Düzenle (Kaydet)
                                            </a>
                                        </div>
                                        @endif

                                        {{-- İmzalı Tahakkuk + İmzalı Tahsilat (ping-pong) — "Yükle ve Gönder" / "E-imzala ve Gönder" --}}
                                        @include('admin.applications._signed_document_upload', ['module' => 'tahakkuk', 'label' => 'İmzalı Tahakkuk'])
                                        @include('admin.applications._signed_document_upload', ['module' => 'makbuz', 'label' => 'İmzalı Tahsilat Makbuzu'])

                                        {{-- GÖREV 4: ALT KURUM ÖDEME DEKONTU (MAKBUZ) YÜKLEME FORMÜ — banka dekontu / e-devlet
                                             tahsilat belgesi alt kurum tarafından buradan yüklenip belediyeye gönderilir.
                                             Dosya yüklenince storeReceipt → addReceipt → status ReceiptPending'e geçer ve
                                             belediyenin tepesindeki "Ödeme Evrakları Geldi / TAAHHÜTNAME MODÜLÜNÜ AÇ" kiliti serbest kalır. --}}
                                        @if($isUserInstitution && ($can['update'] ?? false) && in_array($st, ['awaiting_payment', 'receipt_pending', 'tahakkuk_sent', 'accrued', 'approved', 'payment_completed', 'taahhutname_pending', 'taahhutname_sent']))
                                        <div class="mt-2 rounded-lg border-2 border-dashed border-emerald-300 bg-emerald-50/40 p-3">
                                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-emerald-700">🧾 Ödeme Makbuzunu (Dekont) Yükle / Gönder</p>

                                            @if($latestReceipt)
                                                <div class="mb-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] text-slate-600">
                                                    Son makbuz durumu:
                                                    @if($latestReceipt->status === 'approved')
                                                        <span class="font-semibold text-emerald-700">✅ Onaylandı</span>
                                                    @elseif($latestReceipt->status === 'rejected')
                                                        <span class="font-semibold text-rose-700">❌ Reddedildi</span>
                                                    @else
                                                        <span class="font-semibold text-amber-700">⏳ Belediye onayı bekliyor</span>
                                                    @endif
                                                    @if($latestReceipt->review_notes)
                                                        <span class="block text-rose-600 mt-0.5">Not: {{ $latestReceipt->review_notes }}</span>
                                                    @endif
                                                </div>
                                            @endif

                                            <form method="POST" action="{{ route('admin.applications.receipts.store', $application) }}"
                                                  enctype="multipart/form-data" class="space-y-2">
                                                @csrf
                                                <input type="file" name="receipt_file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                                       class="block w-full text-sm text-slate-600 border border-slate-300 rounded-lg cursor-pointer bg-white file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-colors shadow-sm">
                                                <input type="text" name="notes" placeholder="Açıklama (örn: banka dekont no, ödeme tutarı...)"
                                                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-200">
                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 py-2.5 text-sm font-bold text-white shadow-md hover:bg-emerald-800 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    ⬆️ Dekontu Belediyeye Gönder
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                    @endif
                                @endif

{{-- ===== STEP 5: TAAHHÜTNAME (sadece Kurum) ===== --}}
                            @elseif($num === 5)
                                @php
                                    $makbuzlarDolu = $application->ztb_receipt_info && $application->deposit_receipt_info;
                                    $isLicensed = $st === 'licensed';
                                    // GÖREV 4 GLOBAL SENT-MODEL: Taahhütname için
                                    // - Belediye: taahhutname_pending anından (hazırlık) görür.
                                    // - Alt kurum: yalnızca belediye "taahhutname_sent" gönderimi sonrası görür.
                                    $taahhutnameKurumGorebilir = in_array($st, ['taahhutname_sent', 'payment_completed', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                    $taahhutnameBelediyeGorebilir = in_array($st, ['taahhutname_pending', 'taahhutname_sent', 'payment_completed', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                    $taahhutnameAdimiAcik = $isUserInstitution ? $taahhutnameKurumGorebilir : $taahhutnameBelediyeGorebilir;
                                @endphp

                                {{-- KURAL: Ruhsat PDF butonu bu adımda DEĞİL, Step 6 (Ruhsat) içinde olmalıdır.
                                     Step 5: Taahhütname adımıdır. Ruhsat buraya yanlışlıkla eklenmişse çıkar. --}}

                                {{-- Taahhütname görüntüleme + E-imza --}}
                                @if(($isCurrent || $isPast) && $taahhutnameAdimiAcik)
                                    <p class="mb-2 text-xs text-slate-500">Taahhütname belgesini buradan görüntüleyin ve e-imzalayın.</p>
                                    <a href="{{ route('admin.applications.pdf.taahhutname', $application) }}" target="_blank"
                                       class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                        📄 Taahhütname (PDF) Görüntüle
                                    </a>

                                    {{-- GÖREV 5: Merkez belediye taahhütnameyi GÖNDERMEDEN önce düzenleyip
                                         kaydedebilir — tahakkuk/tahsilattaki "Düzenle (Kaydet)" ile aynı. --}}
                                    @if(!$isUserInstitution && ($can['update'] ?? false))
                                    <a href="{{ route('admin.applications.edit-document', [$application, 'taahhutname']) }}" target="_blank"
                                       class="mb-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        ✏️ Taahhütname Belgesini Düzenle (Kaydet)
                                    </a>
                                    @endif

                                    {{-- Taahhütname Notu: Alt kurum için GİZLİ (d-none), sadece belediye görür --}}
                                    @if(!$isUserInstitution && ($can['update'] ?? false))
                                    <form method="POST" action="{{ route('admin.applications.save-receipt-info', $application) }}" class="mb-2 p-2 rounded-lg border border-slate-200 bg-white">
                                        @csrf
                                        @method('PUT')
                                        <p class="mb-1 text-xs font-semibold text-slate-600">Taahhütname Notu</p>
                                        <textarea name="taahhutname_notu" rows="2" class="mb-1 block w-full rounded border-slate-300 text-xs" placeholder="Taahhütname ile ilgili not...">{{ old('taahhutname_notu', $application->taahhutname_notu ?? '') }}</textarea>
                                        <button type="submit" class="w-full rounded bg-indigo-600 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Kaydet</button>
                                    </form>
                                    @endif

                                    @include('admin.applications._signed_document_upload', ['module' => 'taahhutname', 'label' => 'İmzalı Taahhütname'])

                                    {{-- BÜYÜK YASA: Taahhütname adımı aktif ise alt kuruma işlem tabı --}}
                                    @if($isUserInstitution && ($isCurrent || $isPast))
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/60 p-3 space-y-2">
                                        <p class="text-xs font-semibold text-amber-800 mb-1">📋 İşlem Tabı — Taahhütname</p>
                                        <div>
                                            <label class="block text-[10px] font-medium text-slate-600 mb-1">Açıklama</label>
                                            <textarea rows="2" class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-amber-400 focus:outline-none" placeholder="Taahhütname hakkında not..."></textarea>
                                        </div>
                                        @include('admin.applications._signed_document_upload', ['module' => 'taahhutname_imzali', 'label' => '🗂 İmzalı Taahhütname Nüshası Yükle'])
                                        {{-- MAVİ E-İMZA BUTONU: Yalnızca taahhütname adımı AKTİF iken ve kullanıcı
                                             bu adımda yetkiliyse (GÖREV 3) görünür. Ruhsat aşamasında (adım geçmiş)
                                             çift e-imza görünmesin; yeşil Ruhsat butonu kalır. --}}
                                        @if($isCurrent && ($can['e_imza'] ?? false))
                                        <a href="{{ route('admin.applications.pdf.taahhutname', $application) }}" target="_blank"
                                           class="flex items-center justify-center gap-2 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700 transition">
                                            ✍️ E-imza ile İmzala
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                @endif

                            {{-- ===== STEP 6: RUHSAT (sadece Kurum) ===== --}}
                            @elseif($num === 6)
                                @php
                                    $makbuzlarDoluStep6 = $application->ztb_receipt_info && $application->deposit_receipt_info;
                                    $isLicensedStep6 = $st === 'licensed';
                                    // GÖREV 2/3 KATI STATÜ DUVARI (viewer-bazlı) + GÖREV 4 GLOBAL SENT-MODEL:
                                    // - Belediye: ZTB/Teminat makbuz parametrelerini (Ruhsat belgesine basılan)
                                    //   doldurup ruhsatı hazırlayabilmesi için modül belediye hazırlık/ihracı
                                    //   anından itibaren açıktır (tahakkuk_sent/payment_completed/approved/licensed).
                                    // - Alt kurum: Ruhsat modülünü belediye "ruhsat_sent" takdimi yapınca görür.
                                    //   (licensed artık SADECE belediye hazırlığıdır; kuruma GİZLİ.)
                                    $ruhsatMuniPrep = !$isUserInstitution && $application->isInstitutionApplication() && in_array($st, ['tahakkuk_pending', 'payment_completed', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                    $ruhsatAdimiAcik = $isUserInstitution
                                        ? in_array($st, ['ruhsat_sent', 'field_work', 'completed'])
                                        : in_array($st, ['tahakkuk_pending', 'payment_completed', 'approved', 'licensed', 'ruhsat_sent', 'field_work', 'completed']);
                                @endphp

                                {{-- KURAL: Alt kurum Ruhsat adımını asla düzenleyemez. Edit butonu gizli. --}}
                                {{-- Belediye yöneticisi ruhsatı düzenleyebilir --}}
                                <!-- LOG DEBUG - Ruhsat2 BldMi: {{ $kullaniciBelediyeMi ? 'Evet' : 'Hayir' }} | ST: {{ $guncelStatus }} | DznAcik: {{ $duzenlemeAcik ? 'Evet' : 'Hayir' }} -->
                                @if($ruhsatAdimiAcik && !$isUserInstitution && auth()->user()->hasAnyRole(['super-admin', 'municipality-admin']) && $duzenlemeAcik)
                                    <a href="{{ route('admin.applications.edit-document', [$application, 'ruhsat']) }}" target="_blank"
                                       class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        📊 Taslağı Aç & Bu Başvuruya Özel Düzenle (Excel)
                                    </a>
                                @endif

                                @if(($isCurrent || $isPast) || $ruhsatMuniPrep)
                                    @if($ruhsatAdimiAcik || $makbuzlarDoluStep6 || $isLicensedStep6)
{{-- GÖREV: Belediye Ruhsat modülünü açtığı (licensed) ve ilerlettiği (ruhsat_sent) andan itibaren
     ruhsat belgesi KALICI görünür; alt kurum "ruhsat_sent" gönderimi sonrası ruhsatı (FENNİ MESUL
     imza kutusuyla) işler. Makbuz formu yalnızca belediye hazırlık aşamasında PDF butonuyla birlikte sunulur. --}}
                                        <div class="mt-2 mb-2">
                                        <a target="_blank" href="{{ route('admin.applications.pdf.ruhsat', $application) }}" class="flex items-center justify-center gap-2 w-full bg-sky-600 hover:bg-sky-700 text-white text-[14px] md:text-[15px] font-bold py-3.5 px-4 rounded-xl shadow-md transition-colors text-center border-0 leading-tight">
                                            🖨️ AÇIM RUHSATI (FR-290) BELGESİNİ İNDİR / YAZDIR
                                        </a>
                                        {{-- Alt kurum Ruhsat adımını düzenleyemez — yalnızca görüntüler --}}
                                        @if(!$isUserInstitution)
                                        @include('admin.applications._signed_document_upload', ['module' => 'ruhsat', 'label' => 'İmzalı Ruhsat Belgesi'])
                                        @endif
                                        </div>

                                        {{-- BÜYÜK YASA: Ruhsat adımı aktif ise alt kuruma işlem tabı --}}
                                        @if($isUserInstitution && ($isCurrent || $isPast))
                                        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 space-y-2">
                                            <p class="text-xs font-semibold text-emerald-800 mb-1">📋 İşlem Tabı — Ruhsat</p>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-600 mb-1">Açıklama</label>
                                                <textarea rows="2" class="block w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-xs focus:border-emerald-400 focus:outline-none" placeholder="Ruhsat hakkında not..."></textarea>
                                            </div>
                                            @include('admin.applications._signed_document_upload', ['module' => 'ruhsat_teslim', 'label' => '🗂 İmzalı Ruhsat Nüshası Yükle', 'showEImza' => false])
                                            {{-- GÖREV 3: Ruhsat e-imza linki SADECE bu adımda yetkili kullanıcıya görünür. --}}
                                            @if($can['e_imza'] ?? false)
                                            <a href="{{ route('admin.applications.pdf.ruhsat', $application) }}" target="_blank"
                                               class="flex items-center justify-center gap-2 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 transition">
                                                ✍️ E-imza ile İmzala
                                            </a>
                                            @endif
                                        </div>
                                        @endif

                                        {{-- Makbuz dolmamışsa belediye ruhsat PDF'inin ALTINDA doldurur (buton asla kaybolmaz) --}}
                                        @if(!$isUserInstitution && !$makbuzlarDoluStep6 && !$isLicensedStep6)
                                        <form method="POST" action="{{ route('admin.applications.save-receipt-info', $application) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-600 mb-1">ZTB Makbuz No ve Tarihi:</label>
                                                <input type="text" name="ztb_receipt_info" value="{{ old('ztb_receipt_info', $application->ztb_receipt_info) }}"
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                                       placeholder="Örn: 22.07.2026-0938547">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Teminat Makbuz No ve Tarihi:</label>
                                                <input type="text" name="deposit_receipt_info"
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                                       value="{{ old('deposit_receipt_info', $application->deposit_receipt_info) }}"
                                                       placeholder="Örn: 22.07.2026-0938548">
                                            </div>
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-cyan-600 py-2 text-sm font-bold text-white hover:bg-cyan-500 transition">
                                                Makbuzları Kaydet
                                            </button>
                                        </form>
                                        @endif
                                    @elseif($isUserInstitution)
                                        <p class="text-xs text-slate-400 text-center py-2">Ruhsat makbuzları belediye tarafından doldurulacaktır.</p>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Başvuruyu Düzenle — her durumda göster --}}
            @can('update', $application)
                <a href="{{ route('admin.applications.edit', $application) }}"
                   class="flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm hover:bg-emerald-50 hover:border-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                    Başvuruyu Düzenle
                </a>
            @endcan

            {{-- Görevli Bilgisi + Devret --}}
            @if($application->assignee)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Görevli Kullanıcı</p>
                <p class="text-sm font-medium text-slate-800">{{ $application->assignee->name }}</p>
            </div>
            @endif
            <div class="flex flex-col gap-3 mt-4 mb-4 w-full">
            @can('update', $application)
            <button type="button" onclick="document.getElementById('transfer-modal').classList.remove('hidden')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"
                    style="background-color:#4f46e5 !important;color:#ffffff !important;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                Görevi Devret
            </button>
            @endcan

            {{-- MERKEZ BELEDİYE (vatandaş) SERBEST ERİŞİM: kuruma gönderme yok — buton gizli --}}
            @if(($can['transfer_institution'] ?? false) && $application->isInstitutionApplication())
            <button type="button" onclick="document.getElementById('transfer-institution-modal').classList.remove('hidden')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:opacity-90"
                    style="background-image:linear-gradient(90deg,#0284c7,#7c3aed);color:#ffffff !important;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Kuruma Gönder / Devret
            </button>
            @endif

            {{-- Başvuru İptal --}}
            @if(!in_array($st, ['cancelled', 'completed', 'licensed']))
            @can('update', $application)
            <button type="button" onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-rose-700"
                    style="background-color:#e11d48 !important;color:#ffffff !important;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Başvuruyu İptal Et
            </button>
            @endcan
            @endif
            </div>
        </div>
    </div>



<!-- Modallar: izole root (nested parent buglarindan kacinmak icin sayfa sonuna tasindi) -->
{{-- Zemin Satırları Düzenleme Modalı --}}
<div id="surface-edit-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 px-4 overflow-y-auto hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl flex flex-col p-5 my-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-3">
            <h3 class="text-lg font-semibold text-slate-900">Zemin Satırlarını Düzenle</h3>
            <div class="flex items-center gap-2">
                @if($application->isInstitutionApplication())
                {{-- AYKOME 2 Yıl Kuralı: aynı adrese tekrar kazı → katı (çarpan) fiyat.
                     SADECE alt kurum başvurularında görünür (merkez belediye/vatandaş hariç). --}}
                <button type="button" id="toggle-kati-btn" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                    × Katı Ekle
                </button>
                @endif
                <button type="button" onclick="document.getElementById('surface-edit-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.applications.update-surface-lines', $application) }}">
            @csrf
            @if(!empty($application->address_components) && is_array($application->address_components))
            <div class="mb-3 rounded-lg border border-slate-100 bg-slate-50 p-2">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Adres Kısayolları — tıklayınca ilk satıra yazılır</p>
                    <input type="text" id="adres-chip-search" placeholder="🔍 Adres ara..." class="w-40 rounded-md border-slate-200 text-[11px] px-2 py-1 shadow-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200">
                </div>
                <div class="flex max-h-24 flex-wrap gap-1 overflow-y-auto" id="adres-chip-list">
                    @foreach($application->address_components as $ac)
                        @foreach($ac['streets'] ?? [] as $street)
                        <button type="button" class="chip-adres rounded-md bg-white px-2 py-0.5 text-[11px] text-slate-600 ring-1 ring-slate-200 transition hover:bg-cyan-50 hover:text-cyan-700 hover:ring-cyan-300"
                                data-adres="{{ trim(($ac['mahalle'] ?? '') . ', ' . $street) }}">{{ $street }}</button>
                        @endforeach
                    @endforeach
                </div>
            </div>
            @endif
            {{-- ZEMiN SATIRLARI TABLOSU: 10+ satırdan sonra scroll bar (50 adrese kadar rahat kullanım) --}}
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto border border-slate-100 rounded-lg">
                <table class="w-full text-xs" id="surface-edit-table">
                    <thead class="sticky top-0 bg-white z-10">
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th class="py-2 pr-2 font-medium">#</th>
                            <th class="p-2 font-medium min-w-[160px]">Zemin Tipi</th>
                            <th class="p-2 font-medium min-w-[140px]">Adres</th>
                            <th class="p-2 font-medium min-w-[80px]">Genişlik (m)</th>
                            <th class="p-2 font-medium min-w-[80px]">Uzunluk (m)</th>
                            <th class="p-2 font-medium min-w-[70px] kati-col hidden bg-amber-50">Katı (x)</th>
                            <th class="p-2 font-medium min-w-[100px]">Miktar (m²)</th>
                            <th class="p-2 font-medium min-w-[60px]">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="surface-edit-tbody">
                        @forelse($application->surfaceLines as $idx => $line)
                        <tr class="border-b border-slate-200 hover:bg-slate-50" data-index="{{ $idx }}">
                            <td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">{{ $idx + 1 }}</td>
                            <td class="p-2 align-top">
                                <select name="surface_lines[{{ $idx }}][surface_type_id]" required class="block w-full rounded border-slate-300 text-xs shadow-sm">
                                    <option value="">—</option>
                                    @foreach($surfaceTypes as $st)
                                    <option value="{{ $st->id }}" {{ $line->surface_type_id == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                @if($line->address)
                                <div class="mt-1 rounded bg-cyan-50 px-1.5 py-0.5 text-[10px] font-medium text-cyan-700">📍 {{ $line->address }}</div>
                                @endif
                            </td>
                            <td class="p-2 align-top">
                                <div class="flex items-center gap-1">
                                    <input type="text" name="surface_lines[{{ $idx }}][address]" value="{{ $line->address ?? '' }}" class="min-w-0 flex-1 rounded border-slate-300 text-xs shadow-sm" placeholder="Mahalle, cadde/sokak...">
                                    <button type="button" class="row-address-show shrink-0 rounded border border-emerald-200 bg-emerald-50 px-1.5 py-1 text-[10px] text-emerald-700 hover:bg-emerald-100" title="Bu adresi haritada göster">📍</button>
                                </div>
                            </td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][width_m]" value="{{ $line->width_m ? number_format((float)$line->width_m, 2, '.', '') : '' }}" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][length_m]" value="{{ $line->length_m ? number_format((float)$line->length_m, 2, '.', '') : '' }}" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top kati-col hidden bg-amber-50/40">
                                <input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][multiplier]" value="{{ $line->multiplier && (float)$line->multiplier != 1 ? number_format((float)$line->multiplier, 2, '.', '') : '' }}" class="w-full rounded border-amber-300 text-xs shadow-sm font-semibold text-amber-800" placeholder="1">
                            </td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][quantity]" value="{{ $line->quantity ? number_format((float)$line->quantity, 2, '.', '') : '' }}" required class="w-full rounded border-slate-300 text-xs shadow-sm font-semibold" placeholder="0"></td>
                            <td class="p-2 align-top">
                                <button type="button" class="remove-surface-row rounded border border-red-200 bg-red-50 px-1.5 py-1 text-[10px] font-medium text-red-600 hover:bg-red-100">🗑</button>
                            </td>
                        </tr>
                        @empty
                        <tr class="border-b border-slate-200 hover:bg-slate-50" data-index="0">
                            <td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">1</td>
                            <td class="p-2 align-top">
                                <select name="surface_lines[0][surface_type_id]" required class="block w-full rounded border-slate-300 text-xs shadow-sm">
                                    <option value="">—</option>
                                    @foreach($surfaceTypes as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2 align-top">
                                <div class="flex items-center gap-1">
                                    <input type="text" name="surface_lines[0][address]" class="min-w-0 flex-1 rounded border-slate-300 text-xs shadow-sm" placeholder="Mahalle, cadde/sokak...">
                                    <button type="button" class="row-address-show shrink-0 rounded border border-emerald-200 bg-emerald-50 px-1.5 py-1 text-[10px] text-emerald-700 hover:bg-emerald-100" title="Bu adresi haritada göster">📍</button>
                                </div>
                            </td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[0][width_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[0][length_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top kati-col hidden bg-amber-50/40">
                                <input type="text" inputmode="decimal" name="surface_lines[0][multiplier]" class="w-full rounded border-amber-300 text-xs shadow-sm font-semibold text-amber-800" placeholder="1">
                            </td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[0][quantity]" required class="w-full rounded border-slate-300 text-xs shadow-sm font-semibold" placeholder="0"></td>
                            <td class="p-2 align-top">
                                <button type="button" class="remove-surface-row rounded border border-red-200 bg-red-50 px-1.5 py-1 text-[10px] font-medium text-red-600 hover:bg-red-100">🗑</button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-surface-row-btn" class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">
                + Yeni Satır Ekle
            </button>

            {{-- KATI AÇIKLAMASI: Katı Ekle ile açılır. Metin uygulamaya (kati_aciklama) kaydedilir.
                 PDF metraj cetvelinde TOPLAM'dan bir üst satır olarak görünür. --}}
            <div id="kati-aciklama-wrapper" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50/60 p-3">
                <label class="mb-1 block text-[11px] font-semibold text-amber-800">📝 Açıklama (Kurul Kararı / Katı Sebebi) — PDF metraj cetvelinde görünür</label>
                <textarea id="kati-aciklama-global" name="kati_aciklama"
                    data-db-val="{{ $application->kati_aciklama ?? '' }}"
                    rows="2" class="w-full rounded-lg border-amber-300 text-xs shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-200"
                    placeholder="Örn: 2 yıl içinde aynı adrese tekrar kazı — AYKOME kurul kararı ile 5 katı fiyat uygulanır.">{{ $application->kati_aciklama ?? '' }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 mt-4">
                <button type="button" onclick="document.getElementById('surface-edit-modal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800">Zemin Satırlarını Kaydet</button>
            </div>
        </form>
    </div>
</div>

{{-- Başkan Yrd. Onayı / Ön Kazı İzni Modalı --}}
<div id="pre-excavation-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 px-4 overflow-y-auto hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl flex flex-col p-6 my-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Başkan Yrd. Onayı / Ön Kazı İzni</h3>
            <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @php
            $onKaziImzalandiMiModal = data_get($application->module_documents, 'pre_permit.status') === 'completed';
        @endphp
        @if(!$onKaziImzalandiMiModal)
        <a href="{{ route('admin.applications.edit-document', [$application, 'on_kazi']) }}" target="_blank"
           class="mb-4 flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
            ✏️ Ön Kazı İzni Taslak Düzenleme (Word)
        </a>
        @endif
        <form method="POST" action="{{ route('admin.applications.approve-pre-excavation', $application) }}" id="pre-excavation-form">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Evrağa Basılacak Başkan Yrd. / Müdür V. Adı Soyadı</label>
                <input type="text" name="vice_mayor_name" value="{{ $application->vice_mayor_name ?: (\App\Services\SignatoryEngine::resolve('pre_permit', $application->institution_id, 'belediye_baskan_yardimcisi')?->ad_soyad ?? '') }}" maxlength="255"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder-slate-400 focus:border-amber-400 focus:ring-1 focus:ring-amber-200"
                       placeholder="Başkan yardımcısının adı soyadı (boş bırakılırsa makam ayarından yazılır)">
            </div>
            <p class="mb-4 text-xs text-slate-500">Ön kazı izni onayı, evrağa basılacak Başkan Yardımcısı / Müdür Vekili adını onaylayıp (gerekirse değiştirip) verilir. Bu bilgi Ön Kazı İzin Belgesi ve e-devlet şablonlarında kullanılır.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Onayla ve Ön Kazı İzni Ver</button>
            </div>
        </form>
    </div>
</div>

{{-- Görevi Devret Modalı --}}
<div id="transfer-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 px-4 overflow-y-auto hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl flex flex-col p-6 my-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Görevi Devret</h3>
            <button type="button" onclick="document.getElementById('transfer-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.applications.transfer', $application) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Başvuruyu Devret</label>
                <select name="assigned_to" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200">
                    <option value="">Kullanıcı Seçin</option>
                    @foreach($fieldUsers as $u)
                        <option value="{{ $u->id }}" {{ $application->assigned_to == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Devir Sebebi (opsiyonel)</label>
                <textarea name="transfer_reason" rows="2" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200" placeholder="Devir sebebini kısaca belirtin"></textarea>
            </div>
            <p class="mb-4 text-xs text-slate-500">Başvurunun sorumluluğu seçilen kullanıcıya devredilecektir.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('transfer-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-800">Devret</button>
            </div>
        </form>
    </div>
</div>

{{-- Kuruma Devret Modalı --}}
<div id="transfer-institution-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 px-4 overflow-y-auto hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl flex flex-col p-6 my-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Kuruma Gönder / Devret</h3>
            <button type="button" onclick="document.getElementById('transfer-institution-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.applications.transfer-institution', $application) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Kurum Seçin</label>
                <select name="institution_id" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-200">
                    <option value="">Kurum Seçin</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Devir Sebebi (opsiyonel)</label>
                <textarea name="transfer_reason" rows="2" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-200" placeholder="Devir sebebini kısaca belirtin"></textarea>
            </div>
            <p class="mb-4 text-xs text-slate-500">Başvurunun sorumluluğu seçilen kuruma devredilecektir. Alt kurum, başvuruyu kendi ekranından takip edebilecek.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('transfer-institution-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-medium text-white hover:bg-sky-800" style="background-image:linear-gradient(90deg,#0284c7,#7c3aed);color:#fff !important;">Kuruma Gönder</button>
            </div>
        </form>
    </div>
</div>

{{-- Başvuru İptal Modalı --}}
<div id="cancel-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 px-4 overflow-y-auto hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl flex flex-col p-6 my-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Başvuruyu İptal Et</h3>
            <button type="button" onclick="document.getElementById('cancel-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.applications.cancel', $application) }}" enctype="multipart/form-data" onsubmit="return confirm('Başvuruyu iptal etmek istediğinize emin misiniz?')">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">İptal Sebebi (opsiyonel)</label>
                <textarea name="cancellation_reason" rows="3" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-rose-400 focus:ring-1 focus:ring-rose-200" placeholder="İptal sebebini belirtin"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">İptal Gerekçesi Belgesi (opsiyonel)</label>
                <input type="file" name="cancel_document" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-slate-700 hover:file:bg-slate-200">
            </div>
            <p class="mb-4 text-xs text-rose-600">Bu işlem geri alınamaz. Başvuru iptal edildi olarak işaretlenecektir.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Vazgeç</button>
                <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-medium text-white hover:bg-rose-800">Başvuruyu İptal Et</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Read-Only Harita Poligon Yükleme ───────────────────────────────────
(function () {
    var canvas = document.querySelector('[id^="maps-map-canvas-"]');
    if (!canvas) return;
    var mapKey = 'cbsMap_' + canvas.id;
    var drawnKey = 'cbsDrawnItems_' + canvas.id;
    var map = window[mapKey];
    var drawnItems = window[drawnKey];
    if (!map || !drawnItems) return;

    var areas = @json($application->excavationAreas->pluck('polygon_geojson')->filter()->values());
    if (areas && areas.length) {
        areas.forEach(function (raw) {
            try {
                var p = typeof raw === 'string' ? JSON.parse(raw) : raw;
                if (p && p.features && p.features.length) {
                    L.geoJSON(p, {
                        style: { color: '#2563EB', weight: 2, fillOpacity: 0.1 },
                    }).addTo(drawnItems);
                }
            } catch (e) { /* skip */ }
        });
    }

    // Fit bounds to show all drawn items
    if (drawnItems.getLayers().length) {
        setTimeout(function () { map.fitBounds(drawnItems.getBounds().pad(0.1), { maxZoom: 18 }); }, 500);
    }
})();
</script>
<script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}"></script>
<script>
// ── Normal (OSM) Çizim Haritası ────────────────────────────────────────
(function () {
    var el = document.getElementById('app-normal-map');
    if (!el) return;

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.imagePath = '{{ asset('assets/vendor/leaflet/images') }}';
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon-2x.png') }}',
        iconUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon.png') }}',
        shadowUrl: '{{ asset('assets/vendor/leaflet/images/marker-shadow.png') }}'
    });

    var center = @json([
        'lat' => (float)($application->center_lat ?? 37.1598),
        'lng' => (float)($application->center_lng ?? 38.7969),
    ]);

    var nMap = L.map(el, {
        center: [center.lat, center.lng],
        zoom: 16,
        scrollWheelZoom: false,
        attributionControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22, maxNativeZoom: 19,
    }).addTo(nMap);

    var nDrawn = new L.FeatureGroup();
    nMap.addLayer(nDrawn);

    var areas = @json($haritaAreas);
    if (areas && areas.length) {
        areas.forEach(function (raw) {
            try {
                var p = typeof raw === 'string' ? JSON.parse(raw) : raw;
                if (p && p.features && p.features.length) {
                    L.geoJSON(p, {
                        style: { color: '#E87722', weight: 2.5, fillOpacity: 0.15 },
                        pointToLayer: function (f, ll) { return L.marker(ll); },
                    }).addTo(nDrawn);
                }
            } catch (e) { /* skip */ }
        });
    }

    if (nDrawn.getLayers().length) {
        setTimeout(function () { nMap.fitBounds(nDrawn.getBounds().pad(0.15), { maxZoom: 18 }); }, 400);
    }
    setTimeout(function () { nMap.invalidateSize(); }, 400);

    // Detaydaki adres chips'leri + zemin satırı 📍 bu haritayı ve çizimleri kullanır
    window._aykomeAnaHarita = nMap;
    window._aykomeCizimler = nDrawn;
})();
</script>
<script>
(function () {
    const fileInput   = document.getElementById('receipt_file_input');
    const dropZone    = document.getElementById('receipt-drop-zone');
    const preview     = document.getElementById('receipt-file-preview');
    const fileName    = document.getElementById('receipt-file-name');
    const clearBtn    = document.getElementById('receipt-file-clear');
    const submitBtn   = document.getElementById('receipt-submit-btn');
    const statusEl    = document.getElementById('receipt-upload-status');
    const uploadForm  = document.getElementById('receipt-upload-form');

    if (!fileInput || !uploadForm) return;

    const showFile = (file) => {
        if (!file) return;
        fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        preview.classList.remove('hidden');
        preview.classList.add('flex');
        dropZone.classList.add('border-amber-400', 'bg-amber-50/40');
        dropZone.classList.remove('border-slate-300', 'bg-slate-50');
    };

    const clearFile = () => {
        fileInput.value = '';
        preview.classList.add('hidden');
        preview.classList.remove('flex');
        dropZone.classList.remove('border-amber-400', 'bg-amber-50/40');
        dropZone.classList.add('border-slate-300', 'bg-slate-50');
    };

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) showFile(fileInput.files[0]);
        else clearFile();
    });

    clearBtn?.addEventListener('click', (e) => { e.stopPropagation(); clearFile(); });

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-amber-400', 'bg-amber-50/40');
    });
    dropZone.addEventListener('dragleave', () => {
        if (!fileInput.files.length) {
            dropZone.classList.remove('border-amber-400', 'bg-amber-50/40');
        }
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Transfer to input via DataTransfer
            try {
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                fileInput.files = dt.files;
                showFile(files[0]);
            } catch (_) {
                showFile(files[0]);
            }
        }
    });

    uploadForm.addEventListener('submit', (e) => {
        // Eğer onay formu ise (approve-receipt) ve dosya seçme alanı gösteriliyorsa dosya kontrolü yap
        const isApproveForm = uploadForm.action && uploadForm.action.includes('approve-receipt');
        const hasFileInput = !!fileInput;
        const hasFileSelected = hasFileInput && fileInput.files && fileInput.files.length > 0;

        // Onay formunda dosya alanı gösteriliyorsa ama dosya seçilmemişse → uyar
        if (hasFileInput && !hasFileSelected && !isApproveForm) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Dosya seçilmedi', text: 'Lütfen yüklenecek makbuz dosyasını seçin.', confirmButtonColor: '#D97706' });
            } else {
                alert('Lütfen dosya seçin.');
            }
            return;
        }

        // Dosya varsa boyut kontrolü
        if (hasFileSelected) {
            const file = fileInput.files[0];
            if (file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Dosya çok büyük', text: 'Maksimum dosya boyutu 5 MB olmalıdır.', confirmButtonColor: '#DC2626' });
                }
                return;
            }
        }

        // Onay işlemi için confirm
        if (isApproveForm) {
            if (!confirm('Makbuz onaylanacak ve ruhsat PDF üretilecek. Devam edilsin mi?')) {
                e.preventDefault();
                return;
            }
        }

        // Yükleniyor durumu
        if (submitBtn) submitBtn.disabled = true;
        if (statusEl) { statusEl.classList.remove('hidden'); statusEl.classList.add('flex'); }

        if (hasFileSelected) {
            const file = fileInput.files[0];
            console.log('[Makbuz] Gönderiliyor...', { fileName: file.name, fileSize: file.size, formAction: uploadForm.action });
        }
    });
})();
</script>
<script>
// ── Live status polling (5-second interval) ──────────────────────────────────
(function () {
    const badge       = document.getElementById('app-status-badge');
    const statusUrl   = '{{ route('admin.applications.status', $application) }}';
    let   lastStatus  = '{{ $application->status instanceof \BackedEnum ? $application->status->value : $application->status }}';

    if (!badge) return;

    setInterval(async () => {
        try {
            const res  = await fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();

            if (data.status !== lastStatus) {
                lastStatus = data.status;

                // Swap badge classes and text
                badge.className = 'rounded-full px-3 py-1 text-xs font-semibold ' + data.badge_class;
                badge.textContent = data.label;

                // Pulse animation
                badge.classList.add('animate-pulse');
                setTimeout(() => badge.classList.remove('animate-pulse'), 2000);

                // SweetAlert2 toast
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Durum Güncellendi',
                        text: 'Yeni durum: ' + data.label,
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                    });
                }

                // Audio notification
                try {
                    const audio = new Audio('/sounds/notification.mp3');
                    audio.volume = 0.4;
                    audio.play().catch(() => {});
                } catch (e) {}
            }
        } catch (e) {}
    }, 5000);
})();
</script>
<script>
// ── Signed Document Upload (Ping-Pong) ──────────────────────────────
(function () {
    document.querySelectorAll('.signed-doc-upload').forEach(function (container) {
        var submitBtn = container.querySelector('.sdoc-submit');
        var fileInput = container.querySelector('.sdoc-file');
        var statusEl = container.querySelector('.sdoc-status');
        var module = container.dataset.module;
        var appId = container.dataset.appId;

        if (!submitBtn) return;

        submitBtn.addEventListener('click', function () {
            var file = fileInput?.files?.[0];
            if (!file) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Dosya seçilmedi', text: 'Lütfen bir dosya seçin.', confirmButtonColor: '#D97706' });
                }
                return;
            }

            var formData = new FormData();
            formData.append('module', module);
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

            submitBtn.disabled = true;
            submitBtn.textContent = 'Yükleniyor...';
            if (statusEl) { statusEl.classList.remove('hidden'); statusEl.textContent = 'Yükleniyor...'; }

            fetch('{{ route('admin.applications.upload-signed-document', $application) }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' },
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) {
                    throw new Error(result.data?.message || 'Yükleme hatası');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Yüklendi', text: result.data.message, timer: 2000, showConfirmButton: false });
                }
                setTimeout(function () { location.reload(); }, 1500);
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Hata', text: err.message || 'Dosya yüklenemedi.' });
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Yükle';
                if (statusEl) statusEl.classList.add('hidden');
            });
        });
    });
})();

function toggleStep(id) {
    const el = document.getElementById(id);
    const chevron = document.getElementById(id + '-chevron');
    if (!el) return;
    const isHidden = el.classList.contains('hidden');
    el.classList.toggle('hidden');
    if (chevron) chevron.classList.toggle('rotate-180');
}

// ── Surface Edit Modal Row Management ─────────────────────────────────
(function () {
    var tbody = document.getElementById('surface-edit-tbody');
    var addBtn = document.getElementById('add-surface-row-btn');
    if (!tbody) return;

    var surfaceTypes = @json($surfaceTypes->map(fn($st) => ['id' => $st->id, 'name' => $st->name])->values());

    // Surface lines data for JS-based address hydration
    var surfaceLinesData = <?= json_encode($application->surfaceLines->map(fn($line) => [
        'id' => $line->id,
        'surface_type_id' => $line->surface_type_id,
        'address' => $line->address ?? '',
        'width_m' => $line->width_m,
        'length_m' => $line->length_m,
        'quantity' => $line->quantity,
    ])->values()) ?>;

    function buildOptionHtml(selectedId) {
        var html = '<option value="">—</option>';
        surfaceTypes.forEach(function (st) {
            var sel = st.id === selectedId ? ' selected' : '';
            html += '<option value="' + st.id + '"' + sel + '>' + st.name.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
        });
        return html;
    }

    function recalcIndices() {
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function (row, i) {
            var idxCell = row.querySelector('td:first-child');
            if (idxCell) idxCell.textContent = i + 1;
            var fields = row.querySelectorAll('select, input');
            fields.forEach(function (el) {
                var name = el.getAttribute('name');
                if (name) el.setAttribute('name', name.replace(/\[\d+\]/g, '[' + i + ']'));
            });
        });
    }

    function addRow(data) {
        data = data || {};
        var idx = tbody.querySelectorAll('tr').length;
        var tr = document.createElement('tr');
        tr.className = 'border-b border-slate-200 hover:bg-slate-50';
        tr.setAttribute('data-index', idx);
        var addressBadgeHtml = data.address ? '<div class="mt-1 rounded bg-cyan-50 px-1.5 py-0.5 text-[10px] font-medium text-cyan-700">📍 ' + escapeHtml(data.address) + '</div>' : '';
        tr.innerHTML =
            '<td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">' + (idx + 1) + '</td>' +
            '<td class="p-2 align-top">' +
                '<select name="surface_lines[' + idx + '][surface_type_id]" required class="block w-full rounded border-slate-300 text-xs shadow-sm">' +
                    buildOptionHtml(data.surface_type_id || 0) +
                '</select>' +
                addressBadgeHtml +
            '</td>' +
            '<td class="p-2 align-top"><div class="flex items-center gap-1"><input type="text" name="surface_lines[' + idx + '][address]" value="' + (data.address || '') + '" class="min-w-0 flex-1 rounded border-slate-300 text-xs shadow-sm" placeholder="Mahalle, cadde/sokak..."><button type="button" class="row-address-show shrink-0 rounded border border-emerald-200 bg-emerald-50 px-1.5 py-1 text-[10px] text-emerald-700 hover:bg-emerald-100" title="Bu adresi haritada göster">📍</button></div></td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][width_m]" value="' + (data.width_m || '') + '" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][length_m]" value="' + (data.length_m || '') + '" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>' +
            '<td class="p-2 align-top kati-col ' + (document.getElementById('toggle-kati-btn') && !document.getElementById('toggle-kati-btn').classList.contains('kati-active') ? 'hidden ' : '') + 'bg-amber-50/40"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][multiplier]" value="' + (data.multiplier || '') + '" class="w-full rounded border-amber-300 text-xs shadow-sm font-semibold text-amber-800" placeholder="1"></td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][quantity]" value="' + (data.quantity || '') + '" required class="w-full rounded border-slate-300 text-xs shadow-sm font-semibold" placeholder="0"></td>' +
            '<td class="p-2 align-top"><button type="button" class="remove-surface-row rounded border border-red-200 bg-red-50 px-1.5 py-1 text-[10px] font-medium text-red-600 hover:bg-red-100">🗑</button></td>';
        tbody.appendChild(tr);
        attachRemoveEvents();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Hydrate address values from JS data when modal opens (ensures address is never empty after hydration)
    function hydrateAddressValues() {
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row, i) {
            var data = surfaceLinesData[i];
            if (!data) return;
            var addressInput = row.querySelector('input[name$="[address]"]');
            if (addressInput && data.address) {
                addressInput.value = data.address;
            }
        });
    }

    function attachRemoveEvents() {
        tbody.querySelectorAll('.remove-surface-row').forEach(function (btn) {
            btn.removeEventListener('click', handleRemove);
            btn.addEventListener('click', handleRemove);
        });
    }

    function handleRemove(e) {
        var row = e.target.closest('tr');
        if (!row) return;
        if (tbody.querySelectorAll('tr').length <= 1) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'En az bir satır kalmalıdır.', confirmButtonColor: '#D97706' });
            }
            return;
        }
        row.remove();
        recalcIndices();
    }

    if (addBtn) { addBtn.addEventListener('click', addRow); }
    attachRemoveEvents();

    // Hydrate address values when modal opens (JS-based address binding fix)
    var modal = document.getElementById('surface-edit-modal');
    if (modal) {
        var modalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!modal.classList.contains('hidden')) {
                        hydrateAddressValues();
                        // DB'de herhangi bir satırda KAT > 1 varsa otomatik aç.
                        // window._aykomeKatiModalAcilisKontrol: ikinci IIFE'de tanımlı,
                        // window üzerinden erişiliyor (scope sorunu çözümü).
                        if (typeof window._aykomeKatiModalAcilisKontrol === 'function') {
                            window._aykomeKatiModalAcilisKontrol();
                        }
                    }
                }
            });
        });
        modalObserver.observe(modal, { attributes: true });
    }
})();

// ── Zemin Satırları Düzenle Modalı: ÇİFT YÖNLÜ otomatik hesap + virgüllü ondalık ──
// BAŞVURU OLUŞTUR ile birebir aynı mantık:
//   - Miktar (m²) yazılınca  : Genişlik = Uzunluk = √m² (create 1562-1564 karesel mantık)
//   - Genişlik/Uzunluk yazınca: Miktar = Genişlik × Uzunluk
// "0,6" gibi virgüllü girişler de kabul edilir (0,6 m çizgi genişliği).
(function () {
    var tbody = document.getElementById('surface-edit-tbody');
    if (!tbody) return;

    function aykomeParseDec(str) {
        var s = String(str == null ? '' : str).replace(/,/g, '.').replace(/\.{2,}/g, '.');
        var n = parseFloat(s);
        return Number.isFinite(n) ? n : null;
    }

    // Genişlik × Uzunluk → Miktar
    function netWl(row) {
        if (!row) return;
        var w = row.querySelector('input[name$="[width_m]"]');
        var l = row.querySelector('input[name$="[length_m]"]');
        var q = row.querySelector('input[name$="[quantity]"]');
        if (!w || !l || !q) return;
        var wv = aykomeParseDec(w.value);
        var lv = aykomeParseDec(l.value);
        if ((wv == null || wv <= 0) && lv != null && lv > 0) wv = 1;
        if (wv != null && lv != null && wv > 0 && lv > 0) {
            q.value = (wv * lv).toFixed(2);
        }
    }

    // Miktar (m²) → Genişlik = Uzunluk = √m² (başvuru oluştur ile aynı)
    function netQ(row) {
        if (!row) return;
        var w = row.querySelector('input[name$="[width_m]"]');
        var l = row.querySelector('input[name$="[length_m]"]');
        var q = row.querySelector('input[name$="[quantity]"]');
        if (!w || !l || !q) return;
        var qv = aykomeParseDec(q.value);
        if (qv != null && qv > 0) {
            var kk = Math.sqrt(qv).toFixed(2);
            w.value = kk;
            l.value = kk;
        }
    }

    tbody.addEventListener('input', function (ev) {
        var nm = ev.target && ev.target.name ? ev.target.name : '';
        if (nm.indexOf('[quantity]') > -1) {
            netQ(ev.target.closest('tr'));
        } else if (nm.indexOf('[width_m]') > -1 || nm.indexOf('[length_m]') > -1) {
            netWl(ev.target.closest('tr'));
        }
    });

    var addBtn = document.getElementById('add-surface-row-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            setTimeout(function () {
                tbody.querySelectorAll('tr').forEach(netWl);
            }, 0);
        });
    }

    // KATI (AYKOME 2 yil kurali) toggle: Kati Ekle basilinca .kati-col sutunlari
    // acilir/kapanir, aciklama kutusu gorunur olur.
    var katiBtn = document.getElementById('toggle-kati-btn');
    var katiAciklamaWrapper = document.getElementById('kati-aciklama-wrapper');

    // KAT görünlürlüğünü aktifleştir (buton UI)
    function katiGoster() {
        if (!katiBtn) return;
        katiBtn.classList.add('kati-active', 'bg-amber-500', 'text-white');
        katiBtn.classList.remove('bg-amber-50', 'text-amber-700');
        katiBtn.textContent = '\u2713 Kat\u0131 A\u00e7\u0131k';
        document.querySelectorAll('.kati-col').forEach(function (el) {
            el.classList.remove('hidden');
        });
        if (katiAciklamaWrapper) katiAciklamaWrapper.classList.remove('hidden');
    }

    // Katı Ekle butonuna MANUĒL basilinca: inputları temizle, sonra aç
    function katiAc() {
        document.querySelectorAll('input[name$="[multiplier]"]').forEach(function(inp) {
            inp.value = '';
        });
        katiGoster();
    }

    // Otomatik açma (modal açılınca DB kontrolü): inputları TEMIZLEME, sadece göster
    function katiAcOto() {
        katiGoster(); // inputları koruyarak sadece sütunu aç
    }

    function katiKapat() {
        if (!katiBtn) return;
        katiBtn.classList.remove('kati-active', 'bg-amber-500', 'text-white');
        katiBtn.classList.add('bg-amber-50', 'text-amber-700');
        katiBtn.textContent = '\u00d7 Kat\u0131 Ekle';
        document.querySelectorAll('.kati-col').forEach(function (el) {
            el.classList.add('hidden');
        });
        if (katiAciklamaWrapper) katiAciklamaWrapper.classList.add('hidden');
    }

    // Modal açılınca: herhangi satırda multiplier > 1 varsa KAT otomatik açık gelsin
    // inputları KORUYARAK (DB değerlerini göster)
    function katiModalAcilisKontrol() {
        var anyKat = false;
        document.querySelectorAll('input[name$="[multiplier]"]').forEach(function(inp) {
            var v = parseFloat(String(inp.value || '0').replace(',', '.'));
            if (v > 1) anyKat = true;
        });
        if (anyKat) {
            katiAcOto();
        } else {
            katiKapat();
        }
    }
    // Global scope'a aç (MutationObserver farklı IIFE scope'undan erişiyor)
    window._aykomeKatiModalAcilisKontrol = katiModalAcilisKontrol;

    if (katiBtn) {
        katiBtn.addEventListener('click', function () {
            if (katiBtn.classList.contains('kati-active')) {
                katiKapat();
            } else {
                katiAc(); // Manuel: temizle + aç
            }
        });
    }

    // Adres chip arama kutusu: yazinca listeyi filtreler
    var adresSearch = document.getElementById('adres-chip-search');
    if (adresSearch) {
        adresSearch.addEventListener('input', function () {
            var term = adresSearch.value.trim().toLowerCase();
            document.querySelectorAll('.chip-adres').forEach(function (chip) {
                var adres = (chip.getAttribute('data-adres') || '').toLowerCase();
                chip.classList.toggle('hidden', term.length > 0 && adres.indexOf(term) === -1);
            });
        });
    }

    // Eski "global aciklamayi tum satirlara kopyala" kodu KALDIRILDI.
    // kati_aciklama artik name="kati_aciklama" textarea ile direkt uygulamaya kaydedilir.
    // (updateSurfaceLines controller bunu ayri handle ediyor)
})();

// ── Adres → Haritada Göster (modal 📍 + detay adres chips'leri ortak yardımcı) ──
// Başvuru oluştur sayfasındaki /maps/adres-ara aynı uç noktasını kullanır;
// bulunamazsa başvurunun çizimine (fitBounds) düşer.
(function () {
    var arZ = @json(route('maps.adres-ara'));

    window.aykomeAdresiHaritadaGoster = function (adres) {
        if (!adres || !String(adres).trim()) return;
        var q = String(adres).replace(/\s+/g, ' ').trim();

        fetch(arZ + '?q=' + encodeURIComponent(q))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var m = window._aykomeAnaHarita;
                if (d && d.success && d.lat && m) {
                    if (window._aykomeAdresMarker) window._aykomeAdresMarker.remove();
                    window._aykomeAdresMarker = L.marker([parseFloat(d.lat), parseFloat(d.lon)])
                        .bindPopup('<b>📍 ' + (d.detail || adres) + '</b>')
                        .addTo(m)
                        .openPopup();
                    m.flyTo([parseFloat(d.lat), parseFloat(d.lon)], 18, { animate: true, duration: 1 });
                    return;
                }
                if (m && window._aykomeCizimler && window._aykomeCizimler.getLayers().length) {
                    m.fitBounds(window._aykomeCizimler.getBounds().pad(0.15), { maxZoom: 18 });
                    return;
                }
                alert('Adres haritada bulunamadı: ' + adres);
            })
            .catch(function () { alert('Adres arama hatası: ' + adres); });
    };

    document.querySelectorAll('.chip-adres').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var adres = btn.getAttribute('data-adres') || '';
            var tbody = document.getElementById('surface-edit-tbody');
            var input = tbody && tbody.querySelector('input[name$="[address]"]');
            if (!input) return;
            input.value = adres;
            input.focus();
        });
    });

    // Detay sayfası "Mahalle & Sokaklar" — adres çipine tıklayınca haritada göster
    document.querySelectorAll('.address-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.aykomeAdresiHaritadaGoster(btn.getAttribute('data-adres') || '');
        });
    });

    document.body.addEventListener('click', function (ev) {
        var b = ev.target.closest('.row-address-show');
        if (!b) return;
        var row = b.closest('tr');
        var input = row && row.querySelector('input[name$="[address]"]');
        if (input) window.aykomeAdresiHaritadaGoster(input.value);
    });
})();

</script>
@include('partials._eimza_signing_js')
@endpush
