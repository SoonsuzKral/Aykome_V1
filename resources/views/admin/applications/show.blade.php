@extends('layouts.admin')

@section('page-heading', $application->application_no)

@section('content')
    @php
        $st = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
        $latestReceipt = $application->receipts->sortByDesc('id')->first();
        $latestReceiptMedia = $latestReceipt?->getFirstMedia('scan');
        $latestReceiptUrl = $latestReceiptMedia?->getUrl();

        $statusMeta = match($st) {
            'draft'                  => ['label' => 'Taslak',                 'class' => 'bg-slate-100 text-slate-700'],
            'submitted'              => ['label' => 'Ön Kazı Bekliyor',       'class' => 'bg-sky-100 text-sky-700'],
            'pre_excavation_approved'=> ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'pre_approved'           => ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'priced'                 => ['label' => 'Fiyatlandı',             'class' => 'bg-indigo-100 text-indigo-700'],
            'awaiting_payment'       => ['label' => 'Ödeme Bekliyor',         'class' => 'bg-amber-100 text-amber-700'],
            'receipt_pending'        => ['label' => 'Makbuz Bekliyor',        'class' => 'bg-orange-100 text-orange-700'],
            'approved'               => ['label' => 'Onaylandı',              'class' => 'bg-emerald-100 text-emerald-700'],
            'licensed'               => ['label' => 'Ruhsatlı',               'class' => 'bg-green-100 text-green-700'],
            'field_work'             => ['label' => 'Saha İşi',               'class' => 'bg-blue-100 text-blue-700'],
            'completed'              => ['label' => 'Tamamlandı',             'class' => 'bg-teal-100 text-teal-700'],
            'rejected'               => ['label' => 'Reddedildi',             'class' => 'bg-rose-100 text-rose-700'],
            default                  => ['label' => ucfirst(str_replace('_',' ',$st)), 'class' => 'bg-slate-100 text-slate-700'],
        };
    @endphp

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900">{{ $application->application_no }}</h1>
                <span id="app-status-badge" class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $application->institution?->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- 🏗 ÖN KAZI İZİN BELGESİ — Ön kazı onaylıysa yazdır (SADECE ALT KURUM) --}}
            @php $isAltKurum = $application->institution && !str_contains(strtolower($application->institution->name ?? ''), 'merkez'); @endphp
            @if($isAltKurum && in_array($st, ['pre_approved', 'awaiting_payment', 'receipt_pending', 'completed']))
                <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-cyan-900/20 transition hover:from-cyan-500 hover:to-sky-500 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    📥 Belediyenin Ön Kazı İzin Belgesini (PDF) Yazdır
                </a>
            @endif
            {{-- 🧾 TAHSİLAT MAKBUZU — Ödeme bekliyor veya makbuz bekliyor --}}
            @if(in_array($st, ['awaiting_payment', 'receipt_pending']))
                <a href="{{ route('admin.applications.payment-receipt', $application) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-white shadow-md shadow-amber-900/20 transition hover:bg-amber-600 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    Tahsilat Makbuzu İndir
                </a>
            @endif
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- LEFT COL --}}
        <div class="space-y-6 lg:col-span-2">

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
                    <div><dt class="text-xs font-medium text-slate-500">Proje / İşin Adı</dt><dd class="mt-0.5 font-mono text-slate-800">{{ $application->project_code }}{{ $application->project_code && $application->work_type ? ' / ' : '' }}{{ $application->work_type }}</dd></div>
                    @endif
                    <div><dt class="text-xs font-medium text-slate-500">Kazı Sebebi</dt><dd class="mt-0.5">{{ $application->excavation_reason ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">İş Türü</dt><dd class="mt-0.5">{{ $application->work_type ?? '—' }}</dd></div>
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
                                        <span class="inline-flex items-center rounded-md bg-white px-2 py-0.5 text-[11px] text-slate-600 ring-1 ring-slate-200">{{ $street }}</span>
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
                </dl>
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
                ])
            </div>

            {{-- ZEMİN SATIRLARI & HESAPLAMALAR (Read-Only) --}}
            @php
                $surfaceLines = $application->surfaceLines;
                $isDicle = $application->institution?->tax_number === '2950368442';
                $isInstApp = $application->institution_id && !$application->institution?->is_municipality;

                $toplamMiktar = 0;
                $ztb = 0;
                foreach ($surfaceLines as $line) {
                    $q = max((float)($line->quantity ?? 0), 0);
                    $up = max((float)($line->surfaceType?->price_per_m2 ?? 0), 0);
                    $toplamMiktar += $q;
                    $ztb += $q * $up;
                }

                $kdv = $ztb * 0.20;
                $ruhsatHarci = $isDicle ? 0 : $toplamMiktar * 9;
                $kesifBedeli = 361 + ($ztb * 0.01);
                $ztbToplam = $ztb + $kdv + $ruhsatHarci + $kesifBedeli;
                $teminat = $isInstApp ? 0 : $ztb * 0.50;
                $genelToplam = $ztbToplam + $teminat;
            @endphp

            @if($surfaceLines->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-slate-800">Zemin Satırları &amp; Hesaplamalar</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-300 text-left text-slate-600">
                                <th class="py-2 pr-2 font-medium">#</th>
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

            {{-- BELGE ARŞİVİ / DÖKÜMLER (Tüm aşamaların PDF'leri) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">📦 Belge Arşivi / Dökümler</h2>
                <p class="mb-3 text-xs text-slate-500">Başvuru sürecinde oluşturulmuş tüm belgelere buradan erişebilirsiniz.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                    {{-- Üst Yazı (Dilekçe) -- her zaman göster --}}
                    @if($application->institution && !str_contains(strtolower($application->institution->name ?? ''), 'merkez'))
                    <a href="{{ route('admin.applications.pdf.cover-letter', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-cyan-800">Üst Yazı</span>
                        <span class="text-[9px] text-slate-400">Dilekçe</span>
                    </a>
                    @endif

                    {{-- Ön Kazı İzin Belgesi -- her zaman göster (varsa) --}}
                    @if($application->status instanceof \BackedEnum ? in_array($application->status->value, ['pre_excavation_approved', 'priced', 'awaiting_payment', 'receipt_pending', 'approved', 'licensed', 'field_work', 'completed']) : false)
                    <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-cyan-800">Ön Kazı</span>
                        <span class="text-[9px] text-slate-400">İzin Belgesi</span>
                    </a>
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
                    <a href="{{ route('admin.applications.pdf.metraj', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-indigo-300 hover:bg-indigo-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 group-hover:bg-indigo-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-indigo-800">Metraj</span>
                        <span class="text-[9px] text-slate-400">Cetveli</span>
                    </a>

                    {{-- Tahakkuk Fişi -- her zaman göster --}}
                    <a href="{{ route('admin.applications.pdf.tahakkuk', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-rose-300 hover:bg-rose-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-700 group-hover:bg-rose-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-rose-800">Tahakkuk</span>
                        <span class="text-[9px] text-slate-400">Fişi</span>
                    </a>

                    {{-- Ruhsat Belgesi -- her zaman göster --}}
                    <a href="{{ route('admin.applications.pdf.ruhsat', $application) }}" target="_blank"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-center transition hover:border-emerald-300 hover:bg-emerald-50/70 hover:shadow-sm group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700 group-hover:text-emerald-800">Ruhsat</span>
                        <span class="text-[9px] text-slate-400">Belgesi</span>
                    </a>

                    {{-- Canlı Ruhsat (Permit Live) -- her zaman göster --}}
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
                </div>
            </div>

            {{-- Ek Ruhsat Modülü --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">📋 Ek Ruhsatlar</h2>
                @php $extraPermitCount = $application->extraPermits?->count() ?? 0; @endphp
                <p class="mb-3 text-xs text-slate-500">Bu başvuruya ek kazı ruhsatı tanımlayabilir veya mevcut ek ruhsatları görüntüleyebilirsiniz.</p>
                <div class="flex flex-wrap gap-2">
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
                            <a href="{{ $doc->url }}" download="{{ $doc->original_name }}" class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="İndir">
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
                                <p class="text-sm font-medium text-slate-800">{{ $entry->action }}</p>
                                @if(isset($entry->old_status) || isset($entry->new_status))
                                <p class="mt-0.5 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">{{ $entry->old_status ?? '—' }}</span>
                                    <span class="text-slate-400 mx-1">→</span>
                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 font-mono text-[10px] text-emerald-700">{{ $entry->new_status ?? '—' }}</span>
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

                if ($isMunicipality) {
                    $workflowSteps = [
                        1 => ['key' => 'submitted',   'label' => 'Tahakkuk & Tahsilat Fişi', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        2 => ['key' => 'pre_approved', 'label' => 'Kazı Metraj Bilgi',       'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        3 => ['key' => 'accrued',      'label' => 'Taahhütname',              'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        4 => ['key' => 'licensed',     'label' => 'Ruhsat',                   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                } else {
                    $workflowSteps = [
                        1 => ['key' => 'pending',          'label' => 'Ön Kazı',     'icon' => 'M12 2l.64 1.28a1 1 0 01.5.5L14.42 5.5l1.42-.36a1 1 0 011.1.5l.64 1.28 1.42.36a1 1 0 01.7 1.2l-.36 1.42 1.28.64a1 1 0 01.5 1.1l-.36 1.42.5 1.28a1 1 0 01-.5 1.1l-1.28.64.36 1.42a1 1 0 01-.7 1.2l-1.42.36-.64 1.28a1 1 0 01-1.1.5l-1.42-.36-1.28.64a1 1 0 01-1.1-.5L12 21.5l-1.28-.64a1 1 0 01-1.1.5l-1.42-.36-1.28.64a1 1 0 01-1.1-.5l-.64-1.28-1.42-.36a1 1 0 01-.7-1.2l.36-1.42-1.28-.64a1 1 0 01-.5-1.1l.36-1.42-.5-1.28a1 1 0 01.5-1.1l1.28-.64-.36-1.42a1 1 0 01.7-1.2l1.42-.36.64-1.28a1 1 0 011.1-.5l1.42.36 1.28-.64'],
                        2 => ['key' => 'pre_approved',     'label' => 'Saha Metraj', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        3 => ['key' => 'measurement_done', 'label' => 'Tahakkuk & Makbuz', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        4 => ['key' => 'accrued',          'label' => 'Taahhütname',  'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        5 => ['key' => 'licensed',         'label' => 'Ruhsat',      'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                }
                $currentStep = \App\Enums\ApplicationStatus::workflowStep($st, $isMunicipality);
                $currentStepNum = $currentStep['step'] ?? 0;
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
                        $expanded = true;
                    } elseif ($isPast) {
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
                @endphp
                <div class="rounded-2xl border shadow-sm transition {{ $cardClass }}">
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

                                    {{-- Tahsilat Fişi & Makbuz --}}
                                    @if(in_array($st, ['awaiting_payment', 'receipt_pending', 'pre_approved', 'measurement_done', 'approved', 'licensed', 'completed']))
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
                                    @endif

                                    {{-- Makbuz Yükleme ve Onay --}}
                                    @if(in_array($st, ['awaiting_payment', 'receipt_pending']) && $isCurrent)
                                        @if($can['approve_receipt'] ?? false)
                                            <form id="receipt-upload-form" method="POST"
                                                  action="{{ route('admin.applications.approve-receipt', $application) }}"
                                                  enctype="multipart/form-data" novalidate class="mb-2">
                                                @csrf
                                                @if(!$latestReceipt || $latestReceipt->status !== 'approved')
                                                <div class="mb-2 rounded-lg border border-dashed border-slate-300 bg-white p-2">
                                                    <p class="mb-1 text-xs font-medium text-slate-600">Makbuz yükle</p>
                                                    <div id="receipt-drop-zone" class="relative flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 px-2 py-3 text-center transition hover:border-emerald-400 hover:bg-emerald-50/30"
                                                         onclick="document.getElementById('receipt_file_input').click()">
                                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                        </svg>
                                                        <p class="text-xs text-slate-500"><span class="font-semibold text-emerald-700">Dosya seç</span></p>
                                                    </div>
                                                    <input type="file" id="receipt_file_input" name="receipt_file"
                                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png,image/jpg" class="sr-only">
                                                    <div id="receipt-file-preview" class="mt-1.5 hidden items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-800">
                                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span id="receipt-file-name" class="truncate"></span>
                                                        <button type="button" id="receipt-file-clear" class="ml-auto text-[10px] font-medium text-rose-600 hover:underline">Kaldır</button>
                                                    </div>
                                                </div>
                                                @endif
                                                <button type="submit" id="receipt-submit-btn"
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

                                    {{-- Tahakkuk Bilgileri --}}
                                    @if($can['update'] ?? false)
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
                                    {{-- ===== INSTITUTION: ÖN KAZI ===== --}}
                                    {{-- DURUM: draft/submitted (ilk aşama) --}}
                                    @if(in_array($st, ['draft', 'submitted']))

                                        @if($st === 'draft')
                                            <form method="POST" action="{{ route('admin.applications.submit', $application) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg bg-slate-800 py-2.5 text-sm font-medium text-white hover:bg-slate-900">Başvuruyu Belediyeye Gönder</button>
                                            </form>
                                        @endif

                                        @if($isAltKurum)
                                            {{-- ALT KURUM: Üst Yazı + Ön Kazı Onayı --}}
                                            <a href="{{ route('admin.applications.pdf.cover-letter', $application) }}" target="_blank"
                                               class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                                📄 Üst Yazı (Dilekçe) Görüntüle
                                            </a>

                                            @if($isCurrent && ($can['approve_pre_excavation'] ?? false))
                                            <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.remove('hidden')"
                                                    class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-cyan-700 py-2.5 text-sm font-medium text-white hover:bg-cyan-800">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                ✅ Ön Kazı Onayı Ver
                                            </button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->hasRole('super-admin') && $isAltKurum && in_array($st, ['submitted']))
                                            <a href="{{ route('admin.settings.pre-excavation-permit') }}" target="_blank"
                                               class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                Ön Kazı Belge Ayarları
                                            </a>
                                        @endif

                                    {{-- DURUM: pre_approved veya sonrası (ön kazı aşaması geçildi) --}}
                                    @elseif(in_array($st, ['pre_approved', 'awaiting_payment', 'payment_completed', 'receipt_pending', 'pre_excavation_approved', 'completed']))
                                        <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}" target="_blank"
                                           class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 py-2.5 text-sm font-medium text-cyan-700 hover:bg-cyan-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                            📥 Belediyenin Ön Kazı İzin Belgesini (PDF) Yazdır
                                        </a>
                                    @endif
                                @endif

                            {{-- ===== STEP 2: SAHA METRAJ (Kurum) / KAZI METRAJ BİLGİ (Belediye) ===== --}}
                            @elseif($num === 2)
                                @if($isCurrent || $isPast)
                                    @if(!$isMunicipality)
                                        {{-- INSTITUTION: approve price + surface edit --}}
                                        @if($isCurrent && ($can['approve_price'] ?? false))
                                            <form method="POST" action="{{ route('admin.applications.approve-price', $application) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">Metrajı Onayla &amp; Kuruma Gönder</button>
                                            </form>
                                        @endif

                                        {{-- Zemin Satırlarını Düzenle (Modal tetikleyici) --}}
                                        @if($can['update'] ?? false)
                                        <button type="button" onclick="document.getElementById('surface-edit-modal').classList.remove('hidden')"
                                                class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-100">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Zemin Satırlarını Düzenle
                                        </button>
                                        @endif
                                    @endif

                                    {{-- Saha Görevi Devri --}}
                                    @if(($can['transfer'] ?? false) && $fieldUsers->isNotEmpty() && $isCurrent)
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 mb-2">
                                            <p class="mb-2 text-xs font-semibold text-slate-600">Saha Görevi Devri</p>
                                            <form method="POST" action="{{ route('admin.applications.field-tasks.store', $application) }}" class="space-y-2">
                                                @csrf
                                                <select name="assigned_to" required class="block w-full rounded-lg border-slate-300 text-xs">
                                                    @foreach($fieldUsers as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="date" name="due_date" class="block w-full rounded-lg border-slate-300 text-xs">
                                                <textarea name="notes" rows="1" placeholder="Not" class="block w-full rounded-lg border-slate-300 text-xs"></textarea>
                                                <button type="submit" class="w-full rounded-lg bg-slate-700 py-1.5 text-xs font-medium text-white hover:bg-slate-800">Devret</button>
                                            </form>
                                        </div>
                                    @endif

                                    {{-- Metraj PDF --}}
                                    @if(in_array($st, ['pre_approved', 'awaiting_payment', 'payment_completed', 'receipt_pending', 'measurement_done', 'approved', 'licensed', 'completed']))
                                    <a href="{{ route('admin.applications.pdf.metraj', $application) }}" target="_blank"
                                       class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 mb-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                                        📄 Kazı Metraj Cetveli (PDF) Görüntüle
                                    </a>
                                    @endif

                                    {{-- PING-PONG: İmzalı Metraj Belgesi --}}
                                    @include('admin.applications._signed_document_upload', ['module' => 'metraj', 'label' => 'İmzalı Metraj Belgesi'])

                                @endif

                            {{-- ===== STEP 3: TAHAKKUK & MAKBUZ (Kurum) / TAAHHÜTNAME (Belediye) ===== --}}
                            @elseif($num === 3)
                                @if($isMunicipality)
                                    {{-- MUNICIPALITY STEP 3: TAAHHÜTNAME --}}
                                    @if($isCurrent || $isPast)
                                        <p class="mb-2 text-xs text-slate-500">Taahhütname belgesini buradan yükleyin.</p>
                                        @if($can['update'] ?? false)
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
                                    {{-- INSTITUTION STEP 3: TAHAKKUK & MAKBUZ --}}
                                    @if($isCurrent || $isPast)
                                        @if(in_array($st, ['awaiting_payment', 'receipt_pending']))
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
                                        @endif

                                        @if($can['approve_receipt'] ?? false && $isCurrent)
                                            <form id="receipt-upload-form" method="POST"
                                                  action="{{ route('admin.applications.approve-receipt', $application) }}"
                                                  enctype="multipart/form-data" novalidate class="mb-2">
                                                @csrf
                                                @if(!$latestReceipt || $latestReceipt->status !== 'approved')
                                                <div class="mb-2 rounded-lg border border-dashed border-slate-300 bg-white p-2">
                                                    <p class="mb-1 text-xs font-medium text-slate-600">Makbuz yükle</p>
                                                    <div id="receipt-drop-zone" class="relative flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 px-2 py-3 text-center transition hover:border-emerald-400 hover:bg-emerald-50/30"
                                                         onclick="document.getElementById('receipt_file_input').click()">
                                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                        </svg>
                                                        <p class="text-xs text-slate-500"><span class="font-semibold text-emerald-700">Dosya seç</span></p>
                                                    </div>
                                                    <input type="file" id="receipt_file_input" name="receipt_file"
                                                           accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png,image/jpg" class="sr-only">
                                                    <div id="receipt-file-preview" class="mt-1.5 hidden items-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-800">
                                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span id="receipt-file-name" class="truncate"></span>
                                                        <button type="button" id="receipt-file-clear" class="ml-auto text-[10px] font-medium text-rose-600 hover:underline">Kaldır</button>
                                                    </div>
                                                </div>
                                                @endif
                                                <button type="submit" id="receipt-submit-btn"
                                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-800 py-2.5 text-sm font-medium text-white hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Makbuz Onayla &amp; PDF Üret
                                                </button>
                                            </form>
                                        @endif

                                        @if($can['reject_receipt'] ?? false && $isCurrent)
                                            <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-3">
                                                <p class="mb-2 text-xs font-semibold text-rose-800">Makbuz Reddi</p>
                                                <form method="POST" action="{{ route('admin.applications.reject-receipt', $application) }}"
                                                      class="space-y-2"
                                                      onsubmit="return confirm('Makbuz reddedilsin mi? Başvuru ödeme bekleniyor durumuna alınacak.');">
                                                    @csrf
                                                    <textarea name="review_notes" rows="2" required placeholder="Ret gerekçesini yazın"
                                                        class="w-full rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-xs focus:border-rose-400 focus:outline-none focus:ring-1 focus:ring-rose-100"></textarea>
                                                    <button type="submit" class="w-full rounded-lg bg-rose-700 py-1.5 text-xs font-medium text-white hover:bg-rose-800">Makbuzu Reddet</button>
                                                </form>
                                            </div>
                                        @endif

                                        {{-- Tahakkuk Fişi Düzenle --}}
                                        @if($can['update'] ?? false)
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
                                    @endif
                                @endif

                            {{-- ===== STEP 4 (Kurum): TAAHHÜTNAME / (Belediye): RUHSAT ===== --}}
                            @elseif($num === 4)
                                @if($isMunicipality)
                                    {{-- MUNICIPALITY STEP 4: RUHSAT --}}
                                    @php
                                        $makbuzlarDolu = $application->ztb_receipt_info && $application->deposit_receipt_info;
                                        $isLicensed = $st === 'licensed';
                                    @endphp
                                    @if($isCurrent || $isPast)
                                        @if($makbuzlarDolu || $isLicensed)
                                            <div class="mt-4 mb-2">
                                            <a target="_blank" href="{{ route('admin.applications.pdf.ruhsat', $application) }}" class="flex items-center justify-center gap-2 w-full bg-sky-600 hover:bg-sky-700 text-white text-[14px] md:text-[15px] font-bold py-3.5 px-4 rounded-xl shadow-md transition-colors text-center border-0 leading-tight">
                                                🖨️ AÇIM RUHSATI (FR-290) BELGESİNİ İNDİR / YAZDIR
                                            </a>
                                            @include('admin.applications._signed_document_upload', ['module' => 'ruhsat', 'label' => 'İmzalı Ruhsat Belgesi'])
                                            </div>
                                        @else
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
                                                           placeholder="Örn: 22.07.2026-0938547">
                                                </div>
                                                <button type="submit"
                                                        class="w-full rounded-lg bg-cyan-600 py-2 text-sm font-bold text-white hover:bg-cyan-500 transition">
                                                    Makbuzları Kaydet
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @else
                                    {{-- INSTITUTION STEP 4: TAAHHÜTNAME --}}
                                    @if($isCurrent || $isPast)
                                        <p class="mb-2 text-xs text-slate-500">Taahhütname belgesini buradan yükleyin.</p>

                                        @if($can['update'] ?? false)
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
                                @endif

                            {{-- ===== STEP 5: RUHSAT (sadece Kurum) ===== --}}
                            @elseif($num === 5)
                                @php
                                    $isAltKurum = !$isMunicipality;
                                    $makbuzlarDolu = $application->ztb_receipt_info && $application->deposit_receipt_info;
                                    $isLicensed = $st === 'licensed';
                                @endphp

                                @if($isCurrent || $isPast)
                                    @if($makbuzlarDolu || $isLicensed)
                                        <div class="mt-4 mb-2">
                                        <a target="_blank" href="{{ route('admin.applications.pdf.ruhsat', $application) }}" class="flex items-center justify-center gap-2 w-full bg-sky-600 hover:bg-sky-700 text-white text-[14px] md:text-[15px] font-bold py-3.5 px-4 rounded-xl shadow-md transition-colors text-center border-0 leading-tight">
                                            🖨️ AÇIM RUHSATI (FR-290) BELGESİNİ İNDİR / YAZDIR
                                        </a>
                                        @include('admin.applications._signed_document_upload', ['module' => 'ruhsat', 'label' => 'İmzalı Ruhsat Belgesi'])
                                        </div>
                                    @else
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
                                                       @if($isAltKurum) readonly disabled
                                                       class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-400 cursor-not-allowed"
                                                       @else
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                                       @endif
                                                       value="{{ old('deposit_receipt_info', $isAltKurum ? 'Muaf / Sıfır' : $application->deposit_receipt_info) }}"
                                                       placeholder="Örn: 22.07.2026-0938548">
                                                @if($isAltKurum)
                                                    <p class="mt-1 text-[10px] text-slate-400">Alt kurum olduğu için teminat muaf.</p>
                                                @endif
                                            </div>
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-cyan-600 py-2 text-sm font-bold text-white hover:bg-cyan-500 transition">
                                                Makbuzları Kaydet
                                            </button>
                                        </form>
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
            @can('update', $application)
            <button type="button" onclick="document.getElementById('transfer-modal').classList.remove('hidden')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-indigo-700 shadow-sm hover:bg-indigo-50 hover:border-indigo-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                Görevi Devret
            </button>
            @endcan

            {{-- Başvuru İptal --}}
            @if(!in_array($st, ['cancelled', 'completed', 'licensed']))
            @can('update', $application)
            <button type="button" onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm font-medium text-rose-700 shadow-sm hover:bg-rose-50 hover:border-rose-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Başvuruyu İptal Et
            </button>
            @endcan
            @endif
        </div>
    </div>

{{-- Zemin Satırları Düzenleme Modalı --}}
<div id="surface-edit-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-3xl rounded-lg bg-white p-5 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-3">
            <h3 class="text-lg font-semibold text-slate-900">Zemin Satırlarını Düzenle</h3>
            <button type="button" onclick="document.getElementById('surface-edit-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.applications.update-surface-lines', $application) }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-xs" id="surface-edit-table">
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th class="py-2 pr-2 font-medium">#</th>
                            <th class="p-2 font-medium min-w-[160px]">Zemin Tipi</th>
                            <th class="p-2 font-medium min-w-[80px]">Genişlik (m)</th>
                            <th class="p-2 font-medium min-w-[80px]">Uzunluk (m)</th>
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
                            </td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][width_m]" value="{{ $line->width_m ? number_format((float)$line->width_m, 2, '.', '') : '' }}" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[{{ $idx }}][length_m]" value="{{ $line->length_m ? number_format((float)$line->length_m, 2, '.', '') : '' }}" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
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
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[0][width_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
                            <td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[0][length_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>
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
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 mt-4">
                <button type="button" onclick="document.getElementById('surface-edit-modal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800">Zemin Satırlarını Kaydet</button>
            </div>
        </form>
    </div>
</div>

{{-- Ön Kazı Onayı Modalı --}}
<div id="pre-excavation-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Ön Kazı Onayı</h3>
            <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.applications.approve-pre-excavation', $application) }}" id="pre-excavation-form">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Belediye Başkan Yardımcısı Adı</label>
                <input type="text" name="vice_mayor_name" required maxlength="255"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm placeholder-slate-400 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                       placeholder="Mustafa Kemal KARATAŞ">
            </div>
            <p class="mb-4 text-xs text-slate-500">Ön kazı izni onayı için başkan yardımcısının adını girin. Bu bilgi Ön Kazı İzin Belgesi'nde kullanılacaktır.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('pre-excavation-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800">Onayla</button>
            </div>
        </form>
    </div>
</div>

{{-- Görevi Devret Modalı --}}
<div id="transfer-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
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

{{-- Başvuru İptal Modalı --}}
<div id="cancel-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Başvuruyu İptal Et</h3>
            <button type="button" onclick="document.getElementById('cancel-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.applications.cancel', $application) }}" onsubmit="return confirm('Başvuruyu iptal etmek istediğinize emin misiniz?')">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">İptal Sebebi (opsiyonel)</label>
                <textarea name="cancellation_reason" rows="3" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-rose-400 focus:ring-1 focus:ring-rose-200" placeholder="İptal sebebini belirtin"></textarea>
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

    function addRow() {
        var idx = tbody.querySelectorAll('tr').length;
        var tr = document.createElement('tr');
        tr.className = 'border-b border-slate-200 hover:bg-slate-50';
        tr.setAttribute('data-index', idx);
        tr.innerHTML =
            '<td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">' + (idx + 1) + '</td>' +
            '<td class="p-2 align-top">' +
                '<select name="surface_lines[' + idx + '][surface_type_id]" required class="block w-full rounded border-slate-300 text-xs shadow-sm">' +
                    buildOptionHtml(0) +
                '</select>' +
            '</td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][width_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][length_m]" class="w-full rounded border-slate-300 text-xs shadow-sm" placeholder="0"></td>' +
            '<td class="p-2 align-top"><input type="text" inputmode="decimal" name="surface_lines[' + idx + '][quantity]" required class="w-full rounded border-slate-300 text-xs shadow-sm font-semibold" placeholder="0"></td>' +
            '<td class="p-2 align-top"><button type="button" class="remove-surface-row rounded border border-red-200 bg-red-50 px-1.5 py-1 text-[10px] font-medium text-red-600 hover:bg-red-100">🗑</button></td>';
        tbody.appendChild(tr);
        attachRemoveEvents();
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
})();

// ── E-İmza Buton ────────────────────────────────────────────────
(function () {
    var buttons = document.querySelectorAll('.e-imza-btn');
    if (!buttons.length) return;

    buttons.forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var appId = this.dataset.appId;
            var pdfType = this.dataset.pdfType;

            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> İmza başlatılıyor...';

            try {
                var res = await fetch('/api/e-imza/baslat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ application_id: appId, pdf_type: pdfType })
                });

                var data = await res.json();
                if (!res.ok) throw new Error(data.message || 'İmza başlatılamadı');

                var serverUrl = window.location.origin;
                var protocolUrl = 'aykome://sign?tid=' + encodeURIComponent(data.transaction_id) + '&token=' + encodeURIComponent(data.token) + '&server=' + encodeURIComponent(serverUrl);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'E-İmza işlemi başlatıldı',
                        text: 'Lütfen açılan uygulamada PIN\'inizi girin.',
                        timer: 5000,
                        showConfirmButton: false
                    });
                }

                window.location.href = protocolUrl;

                // Polling
                var pollInterval = setInterval(async function () {
                    try {
                        var durumRes = await fetch('/api/e-imza/durum/' + data.transaction_id, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        var durumData = await durumRes.json();
                        if (durumData.status === 'completed') {
                            clearInterval(pollInterval);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'İmza tamamlandı!', timer: 2000, showConfirmButton: false });
                            }
                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    } catch (e) {}
                }, 3000);

                // 10dk timeout
                setTimeout(function () { clearInterval(pollInterval); btn.disabled = false; btn.innerHTML = 'E-İmza ile İmzala'; }, 600000);

            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'E-İmza ile İmzala';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Hata', text: err.message || 'İmza başlatılamadı.' });
                }
            }
        });
    });
})();
</script>
@endpush
