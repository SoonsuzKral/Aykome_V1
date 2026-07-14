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
            {{-- 🏗 ÖN KAZI İZİN BELGESİ — Ön kazı onaylıysa indir --}}
            @if($st === 'pre_excavation_approved' && $application->pre_excavation_document_path)
                <a href="{{ route('admin.applications.pre-excavation-permit', $application) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-cyan-900/20 transition hover:from-cyan-500 hover:to-sky-500 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Ön Kazı İzin Belgesi İndir
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
                    <div><dt class="text-xs font-medium text-slate-500">Başvuran</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">TC Kimlik</dt><dd class="mt-0.5">{{ $application->applicant_national_id ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Telefon</dt><dd class="mt-0.5">{{ $application->applicant_phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Alan</dt><dd class="mt-0.5">{{ number_format((float)$application->total_area_m2, 2, ',', '.') }} m²</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Genişlik</dt><dd class="mt-0.5">{{ $application->width_m ? number_format((float)$application->width_m, 2, ',', '.') . ' m' : '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Uzunluk</dt><dd class="mt-0.5">{{ $application->length_m ? number_format((float)$application->length_m, 2, ',', '.') . ' m' : '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Keşif / Tutar</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ number_format((float)($application->discovery_amount ?? $application->total_price ?? 0), 2, ',', '.') }} ₺</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Teminat Bedeli</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $application->deposit_amount ? number_format((float)$application->deposit_amount, 2, ',', '.') . ' ₺' : '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Kazı Bedeli</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $application->excavation_amount ? number_format((float)$application->excavation_amount, 2, ',', '.') . ' ₺' : '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Adres</dt><dd class="mt-0.5 text-slate-700">{{ $application->address_text ?? '—' }}</dd></div>
                    @if($application->description)
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Açıklama</dt><dd class="mt-0.5 text-slate-700">{{ $application->description }}</dd></div>
                    @endif
                    @if($application->preExcavationApprover)
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onaylayan</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->preExcavationApprover?->name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onay Tarihi</dt><dd class="mt-0.5">{{ $application->pre_excavation_approved_at?->format('d.m.Y H:i') }}</dd></div>
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

            {{-- Timeline --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Zaman Çizelgesi</h2>
                <ol class="relative border-s-2 border-slate-200 ps-1">
                    @forelse($application->timelineLogs as $log)
                        <li class="ms-6 pb-5 last:pb-0">
                            <span class="absolute -start-[0.52rem] mt-1.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-[#02E0FB]"></span>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2">
                                <p class="text-sm font-medium text-slate-800">{{ $log->action }}</p>
                                @if($log->message)<p class="mt-0.5 text-xs text-slate-600">{{ $log->message }}</p>@endif
                                <p class="mt-1 text-[11px] text-slate-400">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at?->format('d.m.Y H:i') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="ms-6 text-sm text-slate-500">Kayıt yok.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="space-y-6">
            {{-- Actions --}}
            @if($can['update'] ?? false)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-slate-800">İşlemler</h2>
                    <div class="flex flex-col gap-2">
                        @if($st === 'draft')
                            <form method="POST" action="{{ route('admin.applications.submit', $application) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-slate-800 py-2 text-sm font-medium text-white hover:bg-slate-900">Belediyeye gönder</button>
                            </form>
                        @endif
                        @if($can['approve_pre_excavation'] ?? false && $st === 'submitted')
                            <form method="POST" action="{{ route('admin.applications.approve-pre-excavation', $application) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-cyan-700 py-2 text-sm font-medium text-white hover:bg-cyan-800">
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Ön Kazı İzni Onay Ver
                                    </span>
                                </button>
                            </form>
                        @endif
                        @hasrole('super-admin')
                            @if(in_array($st, ['submitted', 'pre_excavation_approved']))
                                <a href="{{ route('admin.settings.pre-excavation-permit') }}"
                                   target="_blank"
                                   class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Ön Kazı Belge Ayarları
                                </a>
                            @endif
                        @endhasrole
                        @if($can['approve_price'] ?? false && $st === 'pre_excavation_approved')
                            <form method="POST" action="{{ route('admin.applications.approve-price', $application) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2 text-sm font-medium text-white hover:bg-emerald-800">Fiyat Onay Ver</button>
                            </form>
                        @endif
                        {{-- Tahsilat Makbuzu: Ödeme Bekliyor veya Makbuz Bekliyor --}}
                        @if(in_array($st, ['awaiting_payment', 'receipt_pending']))
                            <a href="{{ route('admin.applications.payment-receipt', $application) }}"
                               class="w-full inline-flex items-center justify-center gap-2 rounded-lg border-2 border-amber-400 bg-amber-50 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                Tahsilat Makbuzu İndir
                            </a>
                        @endif
                        @if($can['approve_receipt'] ?? false)
                            {{-- BİRLEŞİK FORM: Dosya yükleme + Onay aynı form içinde --}}
                            <form id="receipt-upload-form"
                                  method="POST"
                                  action="{{ route('admin.applications.approve-receipt', $application) }}"
                                  enctype="multipart/form-data"
                                  novalidate>
                                @csrf

                                {{-- Makbuz yoksa veya approved değilse dosya yükleme alanını göster --}}
                                @if(!$latestReceipt || $latestReceipt->status !== 'approved')
                                <div class="mb-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-600">
                                        {{ $latestReceipt ? 'Yeni makbuz yükleyerek onayla' : 'Makbuz yükle ve onayla' }}
                                    </p>
                                    <div id="receipt-drop-zone"
                                        class="relative flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-3 py-5 text-center transition hover:border-emerald-400 hover:bg-emerald-50/30"
                                        onclick="document.getElementById('receipt_file_input').click()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <p id="receipt-file-label" class="text-xs text-slate-600">
                                            Dosya seçin veya <span class="font-semibold text-emerald-700">buraya sürükleyin</span>
                                        </p>
                                        <p class="text-[10px] text-slate-400">PDF, JPEG, PNG — Maks 5 MB</p>
                                    </div>
                                    <input type="file" id="receipt_file_input" name="receipt_file"
                                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png,image/jpg"
                                        class="sr-only">
                                    <div id="receipt-file-preview" class="mt-2 hidden items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span id="receipt-file-name" class="truncate"></span>
                                        <button type="button" id="receipt-file-clear" class="ml-auto flex-shrink-0 text-[10px] font-medium text-rose-600 hover:underline">Kaldır</button>
                                    </div>
                                    <div class="mt-2">
                                        <label for="receipt_notes" class="block text-[10px] font-medium text-slate-500">Not (opsiyonel)</label>
                                        <textarea id="receipt_notes" name="notes" rows="1"
                                            class="mt-0.5 w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-1 focus:ring-emerald-200"
                                            placeholder="Makbuz notu (opsiyonel)"></textarea>
                                    </div>
                                </div>
                                @endif

                                <button type="submit" id="receipt-submit-btn"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-800 py-2 text-sm font-medium text-white hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Makbuz onayla &amp; PDF üret
                                </button>
                                <span id="receipt-upload-status" class="mt-1 hidden items-center justify-center gap-2 text-xs text-slate-500">
                                    <svg class="h-3.5 w-3.5 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    İşleniyor…
                                </span>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Receipt Reject --}}
            @if($can['reject_receipt'] ?? false)
                <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-rose-800">Makbuz Reddi</h2>
                    <form method="POST" action="{{ route('admin.applications.reject-receipt', $application) }}"
                          class="space-y-3"
                          onsubmit="return confirm('Makbuz reddedilsin mi? Başvuru ödeme bekleniyor durumuna alınacak.');">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Ret gerekçesi *</label>
                            <textarea name="review_notes" rows="3" required
                                class="mt-1 w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                placeholder="Ret gerekçesini yazın"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-rose-700 py-2 text-sm font-medium text-white hover:bg-rose-800">Makbuzu Reddet</button>
                    </form>
                </div>
            @endif

            {{-- Task Transfer --}}
            @if(($can['transfer'] ?? false) && $fieldUsers->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-slate-800">Saha Görevi Devri</h2>
                    <form method="POST" action="{{ route('admin.applications.field-tasks.store', $application) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Saha personeli</label>
                            <select name="assigned_to" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                @foreach($fieldUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Termin tarihi</label>
                            <input type="date" name="due_date" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Not</label>
                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-slate-700 py-2 text-sm font-medium text-white hover:bg-slate-800">Devret</button>
                    </form>
                </div>
            @endif

            @can('update', $application)
                <a href="{{ route('admin.applications.edit', $application) }}"
                   class="flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm hover:bg-emerald-50 hover:border-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                    Başvuruyu Düzenle
                </a>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
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
@endpush
