@extends('layouts.admin')

@section('page-heading', 'Yeni başvuru')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.draw.css') }}" />
    <style>
        /* WMS KONUM BUL ANİMASYONU */
        @keyframes locSpin { to { transform: rotate(360deg); } }
        @keyframes locPulse {
            0% { box-shadow: 0 0 0 0 rgba(232,119,34,0.7); }
            70% { box-shadow: 0 0 0 14px rgba(232,119,34,0); }
            100% { box-shadow: 0 0 0 0 rgba(232,119,34,0); }
        }
        .loc-marker { background:#E87722; width:16px; height:16px; border-radius:50%; border:3px solid #fff; animation: locPulse 1.5s infinite; }
        .loc-tooltip { background:#0f172a !important; color:#fff !important; border:none !important; border-radius:6px !important; padding:2px 8px !important; font-size:11px !important; font-weight:600 !important; box-shadow:0 2px 8px rgba(0,0,0,0.3) !important; }
        .loc-tooltip::before { border-top-color:#0f172a !important; }
        .search-spinner { display:inline-block;width:14px;height:14px;border:2px solid #e2e8f0;border-top-color:#E87722;border-radius:50%;animation:searchSpin 0.6s linear infinite;vertical-align:middle;margin-right:6px }
        @keyframes searchSpin { to { transform: rotate(360deg); } }
        #application-drawing-map { min-height: 500px; position: relative; z-index: 1; }
        #application-drawing-map .leaflet-container { border-radius: 0.75rem; }
        .leaflet-pane { z-index: 10 !important; }
        .leaflet-top, .leaflet-bottom { z-index: 99 !important; }
        .row-tooltip { background: #1e293b !important; color: #fff !important; border: none !important; border-radius: 4px !important; padding: 2px 8px !important; font-size: 11px !important; font-weight: 600 !important; box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important; }
        .row-tooltip::before { border-top-color: #1e293b !important; }
        .leaflet-draw-toolbar a {
            background-image: url('{{ asset('assets/vendor/leaflet/images/spritesheet.png') }}') !important;
            background-size: 300px 30px !important;
            transition: filter 0.15s;
        }
        .leaflet-draw-toolbar a:hover { filter: brightness(1.1); }
        .leaflet-draw-section .leaflet-draw-draw-polyline { border-left: 3px solid #3B82F6 !important; }
        .leaflet-draw-section .leaflet-draw-draw-polygon { border-left: 3px solid #10B981 !important; }
        .leaflet-draw-section .leaflet-draw-draw-rectangle { border-left: 3px solid #F59E0B !important; }
        .leaflet-draw-section .leaflet-draw-draw-circle { border-left: 3px solid #8B5CF6 !important; }
        .leaflet-draw-section .leaflet-draw-draw-marker { border-left: 3px solid #EF4444 !important; }
        .leaflet-draw-section .leaflet-draw-edit-edit { border-left: 3px solid #06B6D4 !important; }
        .leaflet-draw-section .leaflet-draw-edit-remove { border-left: 3px solid #DC2626 !important; }
        .leaflet-draw-actions a { background-color: #4B5563 !important; border-left: 1px solid #6B7280 !important; }
        .leaflet-draw-actions a:hover { background-color: #374151 !important; }
    </style>
@endpush

@section('content')
    @php
        $institutionOptions = $institutions->map(fn ($item) => [
            'id' => (int) $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'color_code' => $item->color_code,
            'is_municipality' => (bool) $item->is_municipality,
            'tax_number' => $item->tax_number,
        ])->values();

        $surfaceTypeOptions = $surfaceTypes->map(fn ($item) => [
            'id' => (int) $item->id,
            'name' => $item->name,
            'price_per_m2' => (float) $item->price_per_m2,
        ])->values();
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Yeni kazı başvurusu</h1>
        <p class="text-sm text-slate-600">Başvuru bilgilerini, harita çizimini ve keşif satırını tek akışta tamamlayın.</p>
    </div>

    <form id="application-form" method="POST" action="{{ route('admin.applications.store') }}" enctype="multipart/form-data" class="space-y-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        @if($institutions->count() > 1)
            <div>
                <label class="block text-sm font-medium text-slate-700" for="institution_id">Kurum</label>
                <select
                    id="institution_id"
                    name="institution_id"
                    onchange="imzaKartGuncelle()"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm @error('institution_id') border-red-300 ring-red-100 @enderror"
                >
                    <option value="">—</option>
                    @foreach($institutions as $i)
                        <option value="{{ $i->id }}" data-tax="{{ $i->tax_number }}" data-name="{{ $i->name }}" data-phone="{{ $i->phone }}" data-is-merkez="{{ $i->is_municipality ? '1' : '0' }}" @selected((string) old('institution_id') === (string) $i->id)>{{ $i->name }}</option>
                    @endforeach
                </select>
                @error('institution_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if($processes->isNotEmpty())
            <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4">
                <label class="block text-sm font-medium text-slate-700" for="process_id">
                    Süreç / Onay Rotası
                </label>
                <select
                    id="process_id"
                    name="process_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm @error('process_id') border-red-300 ring-red-100 @enderror"
                >
                    <option value="">— Varsayılan Süreç —</option>
                    @foreach($processes as $p)
                        <option value="{{ $p->id }}" @selected((string) old('process_id') === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Başvurunun onay adımları bu süreçteki adımlara göre ilerler. Boş bırakılırsa aktif varsayılan süreç kullanılır.</p>
                @error('process_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-8">
                <fieldset class="grid gap-4 sm:grid-cols-2">
                    <legend class="col-span-full text-sm font-semibold text-slate-800">Başvuru sahibi</legend>

                    @if($isInstitutionUser)
                        {{-- ── Kurum personeli / yöneticisi ─────────────────────────────────────
                             Vatandaş adına başvuru AÇILAMAZ. Bilgiler oturumdaki kullanıcıdan
                             otomatik doldurulur; alanlar salt okunur ve kilitlidir.
                        ──────────────────────────────────────────────────────────────────────── --}}
                        <div class="col-span-full mb-1 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                            <svg class="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75A4.5 4.5 0 0 0 12 2.25a4.5 4.5 0 0 0-4.5 4.5v3.75M3.75 10.5h16.5M6 21h12a.75.75 0 0 0 .75-.75V11.25a.75.75 0 0 0-.75-.75H6a.75.75 0 0 0-.75.75v8.999c0 .414.336.751.75.751Z"/></svg>
                            <p class="text-xs text-amber-700">Kurum personeli olarak <strong>kendi adınıza</strong> başvuru oluşturuyorsunuz. Başvurucu bilgileri oturumunuzdan otomatik atandı.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Başvuru Sahibi (Ad Soyad / Kurum Ünvanı)</label>
                            <input type="text" value="{{ trim($applicantPrefill['first_name'] . ' ' . $applicantPrefill['last_name']) }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 text-slate-500 shadow-sm">
                            <input type="hidden" name="applicant_first_name" value="{{ $applicantPrefill['first_name'] }}">
                            <input type="hidden" name="applicant_last_name" value="{{ $applicantPrefill['last_name'] }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">TC Kimlik / Vergi No</label>
                            <div class="mt-1 flex items-center gap-2">
                                @if($institutionPrefill)
                                    <input type="text" value="{{ $institutionPrefill['tax_number'] }}" readonly
                                        class="block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 font-mono text-sm tracking-widest text-slate-500 shadow-sm">
                                    <input type="hidden" name="applicant_national_id" value="{{ $institutionPrefill['tax_number'] }}">
                                    <input type="hidden" name="tc_no" value="{{ $institutionPrefill['tax_number'] }}">
                                    <input type="hidden" name="identity_no" value="{{ $institutionPrefill['tax_number'] }}">
                                @else
                                    <input type="text" value="{{ $applicantPrefill['national_id_masked'] }}" readonly
                                        class="block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 font-mono text-sm tracking-widest text-slate-500 shadow-sm">
                                    <input type="hidden" name="applicant_national_id" value="{{ $applicantPrefill['national_id'] }}">
                                    <input type="hidden" name="tc_no" value="{{ $applicantPrefill['national_id'] }}">
                                    <input type="hidden" name="identity_no" value="{{ $applicantPrefill['national_id'] }}">
                                @endif
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-xs font-semibold text-amber-700">
                                    🔒 Kilitli
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Kimlik bilgisi oturumdaki hesaba bağlıdır; değiştirilemez.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telefon</label>
                            <input type="text" value="{{ $institutionPrefill['phone'] ?? $applicantPrefill['phone'] }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 text-slate-500 shadow-sm">
                            <input type="hidden" name="applicant_phone" value="{{ $institutionPrefill['phone'] ?? $applicantPrefill['phone'] }}">
                        </div>
                    @else
                        {{-- ── Belediye / Super Admin: tam serbest TCKN girişi + sorgulama ──── --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="applicant_first_name">Başvuru Sahibi (Ad Soyad / Kurum Ünvanı)</label>
                            <input id="applicant_first_name" type="text" name="applicant_first_name" value="{{ old('applicant_first_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('applicant_first_name') border-red-300 ring-red-100 @enderror">
                            @error('applicant_first_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="applicant_national_id">TC Kimlik No</label>
                            <div class="mt-1 flex gap-2">
                                <input id="applicant_national_id" type="text" name="applicant_national_id" value="{{ old('applicant_national_id', old('tc_no', old('identity_no'))) }}" maxlength="11" required class="block w-full rounded-lg border-slate-300 shadow-sm @error('applicant_national_id') border-red-300 ring-red-100 @enderror">
                                <button
                                    type="button"
                                    id="tckn-check-btn"
                                    class="inline-flex shrink-0 items-center rounded-lg border border-cyan-300 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-100"
                                >
                                    TCKN Sorgula
                                </button>
                            </div>
                            <input type="hidden" id="tc_no_belediye" name="tc_no" value="{{ old('tc_no', old('applicant_national_id')) }}">
                            <input type="hidden" id="identity_no_belediye" name="identity_no" value="{{ old('identity_no', old('applicant_national_id')) }}">
                            <p id="tckn-check-status" class="mt-1 text-xs text-slate-500"></p>
                            @error('applicant_national_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="applicant_phone">Telefon</label>
                            <input id="applicant_phone" type="text" name="applicant_phone" value="{{ old('applicant_phone') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('applicant_phone') border-red-300 ring-red-100 @enderror">
                            @error('applicant_phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Başvuru / Arıza seçimi --}}
                    <div class="col-span-full mt-1">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Başvuru Türü</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="application_type" value="basvuru" {{ old('application_type', 'basvuru') === 'basvuru' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                                <span class="text-sm text-slate-700 font-medium">Normal Başvuru</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="application_type" value="ariza" {{ old('application_type') === 'ariza' ? 'checked' : '' }} class="h-4 w-4 text-red-500 border-slate-300 focus:ring-red-400">
                                <span class="text-sm text-slate-700 font-medium">Arıza (Acil Kazı)</span>
                            </label>
                        </div>
                        @error('application_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <fieldset class="grid gap-4 sm:grid-cols-2">
                    <legend class="col-span-full text-sm font-semibold text-slate-800">Kazı</legend>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700" for="excavation_reason">Projenin Adı : </label>
                        <input id="excavation_reason" type="text" name="excavation_reason" value="{{ old('excavation_reason') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('excavation_reason') border-red-300 ring-red-100 @enderror">
                        @error('excavation_reason')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="project_code">Proje Kodu</label>
                        <input id="project_code" type="text" name="project_code" value="{{ old('project_code') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('project_code') border-red-300 ring-red-100 @enderror">
                        @error('project_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="work_type">İşin Adı (Cinsi)</label>
                        <input id="work_type" type="text" name="work_type" value="{{ old('work_type') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('work_type') border-red-300 ring-red-100 @enderror">
                        @error('work_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700" for="address_text">Adres</label>
                        <div class="mt-1 flex gap-2">
                            <input id="address_text" type="text" name="address_text" value="{{ old('address_text') }}" class="block w-full rounded-lg border-slate-300 shadow-sm @error('address_text') border-red-300 ring-red-100 @enderror" placeholder="Mahalle, cadde/sokak, kapı no girin (örn: 15 Temmuz Mah. 123. Sokak 5)">
                            <button type="button" id="btn-find-location" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800 whitespace-nowrap">
                                <span id="find-loc-spinner" class="hidden" style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:locSpin .6s linear infinite;vertical-align:middle;margin-right:6px"></span>
                                📍 Konum Bul
                            </button>
                        </div>
                        <p id="loc-result-info" class="mt-1 text-xs text-slate-500"></p>
                        {{-- 15M ALT/ÜST KONTROLÜ — konum bulunduktan sonra görünür --}}
                        <div id="road15-wrap" class="mt-1 hidden flex-wrap items-center gap-2">
                            <button type="button" id="btn-road15" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                <span id="road15-spinner" class="hidden" style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:locSpin .6s linear infinite;vertical-align:middle;margin-right:5px"></span>
                                📏 15m Yol Kontrolü
                            </button>
                            <span id="road15-result" class="rounded-full px-3 py-1 text-[11px] font-bold hidden"></span>
                        </div>
                        @error('address_text')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 mt-4 mb-2 w-full p-2 bg-gray-50/50 border border-gray-100 rounded shadow-sm sm:col-span-2">
                        <div class="relative w-full">
                            <span class="absolute left-3 top-2 text-gray-500">📍</span>
                            <input type="text" id="coord_single_input" placeholder="Tam koordinatı buraya kopyalayın (Örn: 37.161939, 38.775730)" class="form-control text-sm w-full py-2 pl-9 pr-3 border border-gray-300 rounded focus:border-teal-400 focus:ring-1">
                        </div>
                        <button type="button" id="btn_coord_search" class="btn text-white font-semibold text-sm px-5 py-2 rounded shadow transition whitespace-nowrap" style="background-color: #0ea5e9;">
                            Kordinat İle Bul
                        </button>
                    </div>
                    <p id="coord-result-info" class="mb-2 text-xs text-slate-500"></p>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Mahalle & Sokak Listesi</label>
                        <p class="text-xs text-slate-500 mb-2">Başvuruya ait mahalle ve sokakları ekleyin. Üst yazıda otomatik tablo olarak görünecektir.</p>
                        <input type="hidden" name="address_components" id="address_components" value='{{ old('address_components_json', old('address_components', '[]')) }}'>
                        <div id="address-components-container" class="space-y-2">
                        </div>
                        <button type="button" id="add-address-component-btn" class="mt-2 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">
                            + Mahalle & Sokak Ekle
                        </button>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button type="button" id="toplu-kontrol-btn" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                🧮 Toplu Kontrol Et (15m + Tüm Bilgiler)
                            </button>
                            <span id="toplu-kontrol-spinner" class="text-[10px] text-slate-500 hidden"> Kontrol ediliyor…</span>
                        </div>
                        <div id="toplu-kontrol-sonuc" class="mt-2 hidden"></div>
                        @error('address_components')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="start_date">Başlangıç</label>
                        <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('start_date') border-red-300 ring-red-100 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="end_date">Bitiş</label>
                        <div class="mt-1 flex gap-2">
                            <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" required class="block w-full rounded-lg border-slate-300 shadow-sm @error('end_date') border-red-300 ring-red-100 @enderror">
                            <select id="auto_date_adder" class="shrink-0 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30">
                                <option value="">+ Gün</option>
                                <option value="10">+10 Gün</option>
                                <option value="15">+15 Gün</option>
                                <option value="30">+1 Ay</option>
                            </select>
                        </div>
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700" for="description">Açıklama</label>
                        <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm @error('description') border-red-300 ring-red-100 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>
            </div>

            <aside class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <h3 class="text-sm font-semibold text-slate-800">Hızlı notlar</h3>
                <ul class="space-y-2 text-xs text-slate-600">
                    <li>• Çizim yaptıktan sonra GeoJSON, alan ve merkez otomatik güncellenir.</li>
                    <li>• Kurum değiştiğinde çizim rengi kurum rengine göre ayarlanır.</li>
                    <li>• Yüzey tipi seçilip “Yüzey tipine göre hesapla” ile metraj tutarı anlık hesaplanır.</li>
                    <br>
                    <li><strong> <b>. Genişlik ve Uzunluk" değerleri çizilen şeklin tam kenar ölçülerini değil; şekli harita üzerinde içine alan en dış sınırların (iz düşüm kutusunun) yatay ve dikey mesafesini gösterir.</b></strong></li>
                </ul>
            </aside>
        </div>

        <!-- ────────────────────────────────────────────────────────────
             ZEMİN SATIRLARI & HESAPLAMALAR
             ──────────────────────────────────────────────────────────── -->
        <div id="surface-lines-section" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Zemin Satırları &amp; Hesaplamalar</h3>
                <button type="button" id="add-row-btn" class="rounded-lg border border-cyan-300 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 hover:bg-cyan-100">
                    + Yeni Boş Satır Ekle
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="surface-lines-table" class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-300 text-left text-slate-600">
                            <th class="py-2 pr-2 font-medium">#</th>
                            <th class="p-2 font-medium min-w-[180px]">Zemin Tipi</th>
                            <th class="p-2 font-medium min-w-[100px]">Genişlik (m)</th>
                            <th class="p-2 font-medium min-w-[100px]">Uzunluk (m)</th>
                            <th class="p-2 font-medium min-w-[120px]">Miktar (m²)</th>
                            <th class="p-2 font-medium min-w-[110px]">Birim Fiyat</th>
                            <th class="p-2 font-medium min-w-[120px]">Harita</th>
                            <th class="p-2 font-medium min-w-[140px]">Satır Tutarı (₺)</th>
                            <th class="p-2 font-medium min-w-[80px]">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="surface-lines-tbody">
                        <!-- JS tarafından doldurulacak -->
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-xs text-slate-500">
                <span id="active-draw-indicator" class="hidden inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-amber-700">
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span id="active-draw-label">Harita çizim modu aktif</span>
                </span>
            </div>

            {{-- HESAP KARTLARI --}}
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Toplam Miktar</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-toplam-miktar">0.00</span> m²</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Zemin Tahrip Bedeli</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-ztb">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">KDV (%20)</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-kdv">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ruhsat Harcı</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-ruhsat-harci">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Keşif Bedeli</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-kesif-bedeli">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">ZTB Toplam</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-ztb-toplam">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Teminat</p>
                    <p class="mt-1 text-lg font-bold text-slate-800"><span id="calc-teminat">0.00</span> ₺</p>
                </div>
                <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Genel Toplam</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700"><span id="calc-genel-toplam">0.00</span> ₺</p>
                </div>
            </div>

            {{-- Hidden inputs for submit --}}
            <div id="surface-lines-hidden-inputs"></div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-800">Harita / alan</h3>
                <span id="active-draw-color" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600">
                    <span id="active-draw-color-dot" class="inline-block h-2.5 w-2.5 rounded-full bg-red-600"></span>
                    Çizim rengi
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Harita üzerinden polygon, polyline veya marker çizin. GeoJSON, alan ve merkez koordinatları otomatik doldurulur.
            </p>

            <div class="relative mt-3">
                <div id="map-style-panel" class="absolute top-[4.5rem] right-12 z-10 flex w-40 flex-col gap-1 rounded-xl border border-gray-200 bg-white/95 p-2 shadow-[0_4px_20px_rgba(250,96,1,0.15)] backdrop-blur-sm">
                    <p class="pb-0.5 pl-1 text-[9px] font-black uppercase tracking-wider text-slate-400">Görünüm</p>
                    <button id="style-standard" class="w-full rounded-lg border border-[#FA6001]/30 bg-[#FA6001]/10 px-3 py-1.5 text-left text-[11px] font-semibold text-[#FA6001] transition hover:bg-[#FA6001]/20">⊙ Standart</button>
                    <button id="style-satellite" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-left text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">🛰 Uydu</button>
                    <button id="style-terrain"  class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-left text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">⛰ Arazi</button>
                </div>
                <div id="application-drawing-map" class="w-full rounded-xl border border-slate-200 bg-slate-50" style="min-height:500px"></div>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <button type="button" id="map-clear-btn" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Çizimi temizle</button>
                <button type="button" id="map-apply-geojson-btn" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">GeoJSON'u haritaya uygula</button>
                <span id="map-status" class="text-xs text-slate-500">Haritadan bir alan seçin.</span>
            </div>

            <div class="mt-3 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div>
                    <label class="block text-sm font-medium text-slate-700" for="polygon_geojson">GeoJSON</label>
                    <textarea id="polygon_geojson" name="polygon_geojson" rows="6" class="mt-1 w-full rounded-lg border-slate-300 font-mono text-xs shadow-sm @error('polygon_geojson') border-red-300 ring-red-100 @enderror" placeholder='{"type":"FeatureCollection","features":[...]}'>{{ old('polygon_geojson') }}</textarea>
                    @error('polygon_geojson')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="total_area_m2">Alan (m²)</label>
                        <input id="total_area_m2" type="text" inputmode="decimal" name="total_area_m2" value="0" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('total_area_m2') border-red-300 ring-red-100 @enderror">
                        <input id="poly_width" type="number" step="0.01" min="0.01" value="1" @if($isInstitutionUser) disabled readonly @endif title="Çizgi Genişliği (m)" placeholder="Genişlik (m)" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @error('total_area_m2')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <input type="hidden" name="center_lat" id="center_lat" value="{{ old('center_lat') }}">
                    <input type="hidden" name="center_lng" id="center_lng" value="{{ old('center_lng') }}">

                    <p class="text-xs text-slate-500">
                        Merkez koordinat: <span id="center-display">{{ old('center_lat') && old('center_lng') ? old('center_lat').', '.old('center_lng') : '—' }}</span>
                    </p>

                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700">Polyline uzunluğu</span>
                            <span id="line-length-display">0 m</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="font-medium text-slate-700">Hesaplanan tutar</span>
                            <span id="surface-total-display">0.00 TL</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">📍 CBS Referans Haritası</h3>
            <p class="mb-2 text-xs text-slate-500">Parsel, bina, altyapı ve 15m yol katmanlarını görüntülemek için kullanın.</p>
            @include('maps.partials._harita', [
                'mode' => 'embedded',
                'drawingEnabled' => false,
                'hatKimligiEnabled' => true,
                'show15mRoads' => false,
                'height' => '350px',
                'application' => $application ?? null,
                'areas' => ($application ?? null) ? $application->excavationAreas->pluck('polygon_geojson')->filter()->values() : collect(),
            ])
        </div>

        @include('admin.applications.partials._metraj_tahmin', [
            'tahminEditMode' => false,
        ])

        {{-- Kurum & İmza Yetkili Bilgileri — yalnızca kurum başvurusu (Vatandaş değil) ise görünür --}}
        <fieldset id="imza-yetkili-karti" class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 sm:grid-cols-2">
            <legend class="col-span-full text-sm font-semibold text-slate-800">Kurum & İmza Yetkili Bilgileri</legend>

            {{-- SOL KOLON: KAZI SORUMLUSU --}}
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-slate-800">Kazı Sorumlusu</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="kazi_sorumlusu_ad_soyad">Ad Soyad</label>
                        <input id="kazi_sorumlusu_ad_soyad" type="text" name="kazi_sorumlusu_ad_soyad" value="{{ old('kazi_sorumlusu_ad_soyad') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('kazi_sorumlusu_ad_soyad') border-red-300 ring-red-100 @enderror" placeholder="Kazı sorumlusunun adı soyadı">
                        @error('kazi_sorumlusu_ad_soyad')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="kazi_sorumlusu_unvan">Ünvan</label>
                        <input id="kazi_sorumlusu_unvan" type="text" name="kazi_sorumlusu_unvan" value="{{ old('kazi_sorumlusu_unvan') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('kazi_sorumlusu_unvan') border-red-300 ring-red-100 @enderror" placeholder="Örn: Tesis Sorumlusu / Şef">
                        @error('kazi_sorumlusu_unvan')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="kazi_sorumlusu_telefon">Telefon</label>
                        <input id="kazi_sorumlusu_telefon" type="text" name="kazi_sorumlusu_telefon" value="{{ old('kazi_sorumlusu_telefon') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('kazi_sorumlusu_telefon') border-red-300 ring-red-100 @enderror" placeholder="Telefon numarası">
                        @error('kazi_sorumlusu_telefon')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- SAĞ KOLON: KURUM ÜST YÖNETİCİSİ --}}
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h4 class="mb-3 border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-slate-800">Kurum Üst Yöneticisi</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="kurum_ust_yoneticisi_ad_soyad">Adı Soyad</label>
                        <input id="kurum_ust_yoneticisi_ad_soyad" type="text" name="kurum_ust_yoneticisi_ad_soyad" value="{{ old('kurum_ust_yoneticisi_ad_soyad') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('kurum_ust_yoneticisi_ad_soyad') border-red-300 ring-red-100 @enderror" placeholder="Üst yöneticinin adı soyadı">
                        @error('kurum_ust_yoneticisi_ad_soyad')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="kurum_ust_yoneticisi_unvan">Ünvan</label>
                        <input id="kurum_ust_yoneticisi_unvan" type="text" name="kurum_ust_yoneticisi_unvan" value="{{ old('kurum_ust_yoneticisi_unvan') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('kurum_ust_yoneticisi_unvan') border-red-300 ring-red-100 @enderror" placeholder="Örn: İl Müdürü / Genel Müdür">
                        @error('kurum_ust_yoneticisi_unvan')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- EN ALT TAM SATIR: YAZIYI DÜZENLEYEN --}}
            <div class="rounded-lg border border-slate-200 bg-white p-4 sm:col-span-2">
                <h4 class="mb-3 border-b border-slate-200 pb-2 text-sm font-bold uppercase tracking-wide text-slate-800">Yazıyı Düzenleyen</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="yaziyi_duzenleyen_ad_soyad">Ad Soyad</label>
                        <input id="yaziyi_duzenleyen_ad_soyad" type="text" name="yaziyi_duzenleyen_ad_soyad" value="{{ old('yaziyi_duzenleyen_ad_soyad') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('yaziyi_duzenleyen_ad_soyad') border-red-300 ring-red-100 @enderror" placeholder="Yazıyı düzenleyen kişinin adı soyadı">
                        @error('yaziyi_duzenleyen_ad_soyad')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="yaziyi_duzenleyen_iletisim">İletişim</label>
                        <input id="yaziyi_duzenleyen_iletisim" type="text" name="yaziyi_duzenleyen_iletisim" value="{{ old('yaziyi_duzenleyen_iletisim') }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('yaziyi_duzenleyen_iletisim') border-red-300 ring-red-100 @enderror" placeholder="Telefon / e-posta">
                        @error('yaziyi_duzenleyen_iletisim')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <legend class="col-span-full text-sm font-semibold text-slate-800">Belgeler</legend>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kazı Belgeleri (PDF, Resim)</label>
                <div class="mt-1 flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-white px-4 py-6 transition hover:border-sky-400" id="document-dropzone">
                    <div class="text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-sky-600">Tıklayarak</span> veya sürükleyerek belge yükleyin</p>
                        <p class="text-xs text-slate-500">PDF, JPG, PNG, DOC (max 20MB)</p>
                    </div>
                </div>
                <input type="file" name="documents[]" id="document-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden">
                <div id="document-preview" class="mt-3 flex flex-wrap gap-2"></div>
                @error('documents.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <p id="document-status" class="mt-1 text-xs text-slate-500"></p>
            </div>
        </fieldset>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.applications.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</a>
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Kaydet</button>
        </div>
    </form>
@endsection

@push('scripts')
    {{-- ADRES ARAMA MODÜLÜ — cadde/sokak geometrileri kendisi /maps/15m/alti + /maps/15m/ustu'dan yükler --}}
    <script src="{{ asset('js/maps-address.js') }}"></script>
    <script>
        window.imzaKartGuncelle = function () {
            const kart = document.getElementById('imza-yetkili-karti');
            if (!kart) return;
            @if($isInstitutionUser)
                kart.style.display = '';
            @else
                const sel = document.getElementById('institution_id');
                const opt = sel && sel.selectedOptions && sel.selectedOptions[0];
                const bos = !sel || !sel.value;
                const isMerkez = opt ? (opt.dataset.isMerkez || '0') : '0';
                kart.style.display = (bos || isMerkez === '1') ? 'none' : '';
            @endif
        };
        document.addEventListener('DOMContentLoaded', window.imzaKartGuncelle);
    </script>
    <script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('assets/vendor/leaflet/leaflet.draw.js') }}"></script>
    <script src="{{ asset('assets/vendor/leaflet/leaflet.geometryutil.js') }}"></script>
    <script>
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.imagePath = '{{ asset('assets/vendor/leaflet/images') }}';
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon-2x.png') }}',
            iconUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon.png') }}',
            shadowUrl: '{{ asset('assets/vendor/leaflet/images/marker-shadow.png') }}'
        });
    </script>
    <script>
        // ─── STATE & CONFIG ────────────────────────────────────────────────
        const SURFACE_TYPES = @json($surfaceTypeOptions);
        const INSTITUTIONS = @json($institutionOptions);

        let surfaceLines = [];
        let nextRowId = 1;
        let isDicleElektrik = @json(auth()->user()?->institution?->tax_number === '2950368442');
        let isInstitutionUser = @json(auth()->user()?->institution_id ? true : false);
        let activeDrawRowId = null;
        let rowDrawings = {};

        function rowHasLineDrawing(rowId) {
            var f = rowDrawings[rowId];
            return !!(f && f.geometry && f.geometry.type === 'LineString');
        }

        // ─── PURE CALCULATION FUNCTIONS ───────────────────────────────────
        function calculateRowTotal(quantity, unitPrice) {
            const q = Math.max(parseFloat(quantity) || 0, 0);
            const p = Math.max(parseFloat(unitPrice) || 0, 0);
            return q * p;
        }

        function hasValidRows() {
            return surfaceLines.some(function (r) {
                return r.surface_type_id != null && (parseFloat(r.quantity) || 0) > 0;
            });
        }

        function recalculateAll() {
            var toplamMiktar = 0;
            var ztb = 0;

            surfaceLines.forEach(function (row) {
                var q = Math.max(parseFloat(row.quantity) || 0, 0);
                var up = Math.max(parseFloat(row.price_per_m2) || 0, 0);
                toplamMiktar += q;
                ztb += q * up;
            });

            if (!hasValidRows() || toplamMiktar <= 0) {
                document.getElementById('calc-toplam-miktar').textContent = '0.00';
                document.getElementById('calc-ztb').textContent = '0.00';
                document.getElementById('calc-kdv').textContent = '0.00';
                document.getElementById('calc-ruhsat-harci').textContent = '0.00';
                document.getElementById('calc-kesif-bedeli').textContent = '0.00';
                document.getElementById('calc-ztb-toplam').textContent = '0.00';
                document.getElementById('calc-teminat').textContent = '0.00';
                document.getElementById('calc-genel-toplam').textContent = '0.00';
                var sTot0 = document.getElementById('surface-total-display');
                if (sTot0) sTot0.textContent = '0.00 TL';
                return;
            }

            var kdv = ztb * 0.20;
            var ruhsatHarci = isDicleElektrik ? 0 : toplamMiktar * 9;
            var kesifBedeli = 361 + (ztb * 0.01);
            var ztbToplam = ztb + kdv + ruhsatHarci + kesifBedeli;
            var teminat = isInstitutionUser ? 0 : ztb * 0.50;
            var genelToplam = ztbToplam + teminat;

            document.getElementById('calc-toplam-miktar').textContent = toplamMiktar.toFixed(2);
            document.getElementById('calc-ztb').textContent = ztb.toFixed(2);
            document.getElementById('calc-kdv').textContent = kdv.toFixed(2);
            document.getElementById('calc-ruhsat-harci').textContent = ruhsatHarci.toFixed(2);
            document.getElementById('calc-kesif-bedeli').textContent = kesifBedeli.toFixed(2);
            document.getElementById('calc-ztb-toplam').textContent = ztbToplam.toFixed(2);
            document.getElementById('calc-teminat').textContent = teminat.toFixed(2);
            document.getElementById('calc-genel-toplam').textContent = genelToplam.toFixed(2);
            var sTot = document.getElementById('surface-total-display');
            if (sTot) sTot.textContent = ztb.toFixed(2) + ' TL';
        }

        // ─── ROW RENDERING ────────────────────────────────────────────────
        function renderTable() {
            var tbody = document.getElementById('surface-lines-tbody');
            if (!tbody) return;
            tbody.innerHTML = '';

            surfaceLines.forEach(function (row, idx) {
                var tr = document.createElement('tr');
                tr.className = 'border-b border-slate-200 hover:bg-slate-100/50 transition';
                tr.dataset.rowId = row.rowId;

                var unitPrice = parseFloat(row.price_per_m2) || 0;
                var qty = parseFloat(row.quantity) || 0;
                var rowTotal = calculateRowTotal(qty, unitPrice);
                var hasDrawing = rowDrawings[row.rowId] != null;
                var widthLocked = isInstitutionUser && rowHasLineDrawing(row.rowId);
                var widthVal = widthLocked ? '1.00' : (row.width_m || '');
                var widthLockedAttr = widthLocked ? ' readonly' : '';

                tr.innerHTML =
                    '<td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">' + (idx + 1) + '</td>' +
                    '<td class="p-2 align-top pt-2"><select data-row-id="' + row.rowId + '" class="surface-type-select block w-full rounded border-slate-300 text-xs shadow-sm"><option value="">—</option>' +
                        SURFACE_TYPES.map(function (st) {
                            return '<option value="' + st.id + '" data-price="' + st.price_per_m2 + '"' + (parseInt(st.id) === parseInt(row.surface_type_id) ? ' selected' : '') + '>' + st.name + ' - ' + Number(st.price_per_m2).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' \u20BA</option>';
                        }).join('') +
                    '</select>' + (row.address ? '<div class="mt-1 text-[10px] font-medium leading-tight text-slate-600">📍 ' + esc(row.address) + '</div>' : '') + '</td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-width w-full rounded border-slate-300 text-xs shadow-sm" value="' + widthVal + '"' + widthLockedAttr + ' placeholder="0"></td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-length w-full rounded border-slate-300 text-xs shadow-sm" value="' + (row.length_m || '') + '" placeholder="0"></td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-quantity w-full rounded border-slate-300 text-xs shadow-sm font-semibold" value="' + (qty || '') + '" placeholder="0"></td>' +
                    '<td class="p-2 align-top pt-3 text-xs text-slate-600 font-mono"><span class="row-unit-price" data-row-id="' + row.rowId + '">' + Number(unitPrice).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span> ₺/m²</td>' +
                    '<td class="p-2 align-top whitespace-nowrap"><button type="button" data-row-id="' + row.rowId + '" class="row-draw-btn rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-medium text-slate-600 hover:bg-slate-50 transition ' + (activeDrawRowId === row.rowId ? 'ring-2 ring-amber-400 bg-amber-50' : '') + '">' + (hasDrawing ? '🔄 Çiz' : '🎯 Çiz') + '</button>' + (row.address ? '<button type="button" data-row-id="' + row.rowId + '" class="row-show-btn ml-1 rounded border border-emerald-200 bg-emerald-50 px-1.5 py-1 text-[10px] font-medium text-emerald-700 hover:bg-emerald-100 transition" title="Adresi haritada göster">📍</button>' : '') + '</td>' +
                    '<td class="p-2 align-top pt-3 text-right font-mono text-xs font-semibold text-slate-800"><span class="row-total" data-row-id="' + row.rowId + '">' + rowTotal.toFixed(2) + '</span> ₺</td>' +
                    '<td class="p-2 align-top pt-2 whitespace-nowrap"><button type="button" data-row-id="' + row.rowId + '" class="row-copy-btn rounded border border-cyan-200 bg-cyan-50 px-1.5 py-1 text-[10px] font-medium text-cyan-700 hover:bg-cyan-100 transition" title="Kopyala">📋</button> <button type="button" data-row-id="' + row.rowId + '" class="row-remove-btn rounded border border-red-200 bg-red-50 px-1.5 py-1 text-[10px] font-medium text-red-600 hover:bg-red-100 transition" title="Sil">🗑</button></td>';

                tbody.appendChild(tr);
            });

            attachRowEvents();
            recalculateAll();
        }

        // ─── LIGHTWEIGHT UPDATE (no DOM rebuild — fixes focus loss) ───────
        function updateAllDisplays() {
            surfaceLines.forEach(function (row) {
                var unitPrice = parseFloat(row.price_per_m2) || 0;
                var qty = parseFloat(row.quantity) || 0;
                var total = calculateRowTotal(qty, unitPrice);
                var totalEl = document.querySelector('.row-total[data-row-id="' + row.rowId + '"]');
                if (totalEl) totalEl.textContent = total.toFixed(2);
                var unitPriceEl = document.querySelector('.row-unit-price[data-row-id="' + row.rowId + '"]');
                if (unitPriceEl) unitPriceEl.textContent = Number(unitPrice).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});
            });
            recalculateAll();
        }

        // ─── EVENT DELEGATION ─────────────────────────────────────────────
        function attachRowEvents() {
            document.querySelectorAll('.surface-type-select').forEach(function (el) {
                el.addEventListener('change', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    var row = surfaceLines.find(function (r) { return r.rowId === rowId; });
                    if (!row) return;
                    var opt = this.options[this.selectedIndex];
                    row.surface_type_id = this.value ? parseInt(this.value) : null;
                    row.price_per_m2 = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
                    updateAllDisplays();
                });
            });

            document.querySelectorAll('.row-width, .row-length').forEach(function (el) {
                el.addEventListener('input', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    var row = surfaceLines.find(function (r) { return r.rowId === rowId; });
                    if (!row) return;
                    var w = parseFloat(document.querySelector('.row-width[data-row-id="' + rowId + '"]')?.value) || 0;
                    var l = parseFloat(document.querySelector('.row-length[data-row-id="' + rowId + '"]')?.value) || 0;
                    row.width_m = w;
                    row.length_m = l;
                    if (w > 0 && l > 0) {
                        row.quantity = w * l;
                        var qtyInput = document.querySelector('.row-quantity[data-row-id="' + rowId + '"]');
                        if (qtyInput) qtyInput.value = row.quantity.toFixed(2);
                    }
                    updateAllDisplays();
                });
            });

            document.querySelectorAll('.row-quantity').forEach(function (el) {
                el.addEventListener('input', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    var row = surfaceLines.find(function (r) { return r.rowId === rowId; });
                    if (!row) return;
                    var qty = parseFloat(this.value) || 0;
                    row.quantity = qty;

                    var w = parseFloat(document.querySelector('.row-width[data-row-id="' + rowId + '"]')?.value) || 0;
                    var l = parseFloat(document.querySelector('.row-length[data-row-id="' + rowId + '"]')?.value) || 0;

                    if (qty > 0) {
                        if (isInstitutionUser && rowHasLineDrawing(rowId)) {
                            row.width_m = 1;
                            row.length_m = parseFloat(qty.toFixed(2));
                            var lenInput = document.querySelector('.row-length[data-row-id="' + rowId + '"]');
                            if (lenInput) lenInput.value = row.length_m;
                            var widInput = document.querySelector('.row-width[data-row-id="' + rowId + '"]');
                            if (widInput) widInput.value = '1.00';
                        } else if (w > 0 && l <= 0) {
                            row.length_m = parseFloat((qty / w).toFixed(2));
                            row.width_m = w;
                            var lenInput = document.querySelector('.row-length[data-row-id="' + rowId + '"]');
                            if (lenInput) lenInput.value = row.length_m;
                        } else if (l > 0 && w <= 0) {
                            row.width_m = parseFloat((qty / l).toFixed(2));
                            row.length_m = l;
                            var widInput = document.querySelector('.row-width[data-row-id="' + rowId + '"]');
                            if (widInput) widInput.value = row.width_m;
                        } else {
                            var sqrtVal = parseFloat(Math.sqrt(qty).toFixed(2));
                            row.width_m = sqrtVal;
                            row.length_m = sqrtVal;
                            var wi = document.querySelector('.row-width[data-row-id="' + rowId + '"]');
                            var li = document.querySelector('.row-length[data-row-id="' + rowId + '"]');
                            if (wi) wi.value = sqrtVal;
                            if (li) li.value = sqrtVal;
                        }
                    }

                    updateAllDisplays();
                });
            });

            document.querySelectorAll('.row-draw-btn').forEach(function (el) {
                el.addEventListener('click', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    setActiveDrawRow(rowId);
                });
            });

            document.querySelectorAll('.row-show-btn').forEach(function (el) {
                el.addEventListener('click', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    var row = surfaceLines.find(function (r) { return r.rowId === rowId; });
                    if (row && row.address) showAddressOnMap(row.address);
                });
            });

            document.querySelectorAll('.row-copy-btn').forEach(function (el) {
                el.addEventListener('click', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    copySurfaceLine(rowId);
                });
            });

            document.querySelectorAll('.row-remove-btn').forEach(function (el) {
                el.addEventListener('click', function () {
                    var rowId = parseInt(this.dataset.rowId);
                    removeSurfaceLine(rowId);
                });
            });
        }

        // ─── CRUD OPERATIONS ──────────────────────────────────────────────
        function addSurfaceLine(data) {
            const row = {
                rowId: nextRowId++,
                surface_type_id: data.surface_type_id || null,
                surface_type_name: data.surface_type_name || '',
                price_per_m2: data.price_per_m2 || 0,
                width_m: data.width_m || 0,
                length_m: data.length_m || 0,
                quantity: data.quantity || 0,
                address: data.address || '',
            };
            surfaceLines.push(row);
            renderTable();
            return row;
        }

        // ─── ADRES ↔ SATIR BAĞLANTISI ─────────────────────────────────────
        // Mahalle & Sokak listesindeki her cadde için otomatik zemin satırı üretir (aynı adres tekrar üretilmez)
        function ensureSurfaceLineForAddress(mahalle, cadde) {
            var mh = String(mahalle || '').trim();
            var cd = String(cadde || '').trim();
            if (!mh && !cd) return;
            var adres = (mh ? mh + ', ' : '') + cd;
            adres = adres.replace(/,\s*$/, '').trim();
            if (!adres) return;
            var varMi = surfaceLines.some(function (r) {
                return String(r.address || '').trim().toUpperCase() === adres.toUpperCase();
            });
            if (!varMi) addSurfaceLine({ address: adres });
        }

        // Satırdaki 📍 ikonu — cadde listesindeki 📍 ile aynı mantık:
        // önce cadde/sokak kısmını tek başına dene (cadde listesi gibi), olmazsa tam adresle dene
        function showAddressOnMap(adres) {
            if (!adres) return;
            var parts = String(adres).split(',').map(function (p) { return p.trim(); }).filter(Boolean);
            var sokak = parts.length > 1 ? parts[parts.length - 1] : String(adres).replace(/\s+/g, ' ').trim();
            var tam = String(adres).replace(/\s+/g, ' ').trim();

            function ara(q) {
                return fetch(@json(route('maps.adres-ara')) + '?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d && d.success && d.lat) {
                            if (typeof haritadaGoster === 'function') haritadaGoster(parseFloat(d.lat), parseFloat(d.lon), d.cadde || q);
                            if (typeof window.aykomeDrawingGoster === 'function') window.aykomeDrawingGoster(parseFloat(d.lat), parseFloat(d.lon), d.cadde || q);
                            return true;
                        }
                        return false;
                    })
                    .catch(function () { return false; });
            }

            ara(tam).then(function (ok) {
                if (ok) return;
                ara(sokak).then(function (ok2) {
                    if (!ok2) alert('Adres haritada bulunamadı: ' + adres);
                });
            });
        }

        function removeSurfaceLine(rowId) {
            surfaceLines = surfaceLines.filter(function (r) { return r.rowId !== rowId; });
            delete rowDrawings[rowId];
            if (activeDrawRowId === rowId) {
                activeDrawRowId = null;
                updateActiveDrawIndicator();
            }
            renderTable();
        }

        function copySurfaceLine(rowId) {
            const original = surfaceLines.find(function (r) { return r.rowId === rowId; });
            if (!original) return;
            const copy = JSON.parse(JSON.stringify(original));
            copy.rowId = nextRowId++;
            surfaceLines.push(copy);
            if (rowDrawings[rowId]) {
                rowDrawings[copy.rowId] = JSON.parse(JSON.stringify(rowDrawings[rowId]));
            } else {
                delete rowDrawings[copy.rowId];
            }
            renderTable();
        }

        // ─── MAP INTEGRATION ──────────────────────────────────────────────
        function setActiveDrawRow(rowId) {
            if (activeDrawRowId === rowId) {
                activeDrawRowId = null;
                updateActiveDrawIndicator();
                renderTable();
                setMapStatus('Çizim modu devre dışı.');
                return;
            }
            activeDrawRowId = rowId;
            updateActiveDrawIndicator();
            renderTable();
            setMapStatus('Satır ' + rowId + ' için haritaya çizim yapın.');
        }

        function updateActiveDrawIndicator() {
            const ind = document.getElementById('active-draw-indicator');
            const lbl = document.getElementById('active-draw-label');
            if (activeDrawRowId) {
                ind.classList.remove('hidden');
                lbl.textContent = 'Satır ' + activeDrawRowId + ' için çizim yapılıyor...';
            } else {
                ind.classList.add('hidden');
            }
        }

        function setMapStatus(msg) {
            const el = document.getElementById('map-status');
            if (el) el.textContent = msg;
        }

        // ─── SUBMIT HOOK ──────────────────────────────────────────────────
        function prepareSurfaceLinesForSubmit() {
            const container = document.getElementById('surface-lines-hidden-inputs');
            container.innerHTML = '';

            var validRows = surfaceLines.filter(function (r) { return r.surface_type_id; });
            validRows.forEach(function (row, idx) {
                function addHidden(name, value) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'surface_lines[' + idx + '][' + name + ']';
                    inp.value = value != null ? value : '';
                    container.appendChild(inp);
                }
                addHidden('surface_type_id', row.surface_type_id);
                addHidden('width_m', (isInstitutionUser && rowHasLineDrawing(row.rowId)) ? 1 : (row.width_m || ''));
                addHidden('length_m', row.length_m || '');
                addHidden('quantity', row.quantity || '');
                addHidden('address', row.address || '');
            });

            // Merge row drawings into polygon_geojson
            const allFeatures = Object.values(rowDrawings).filter(Boolean);
            const geoInput = document.getElementById('polygon_geojson');
            if (allFeatures.length > 0) {
                geoInput.value = JSON.stringify({ type: 'FeatureCollection', features: allFeatures });
            }
        }

        // ─── TCKN LOOKUP ──────────────────────────────────────────────────
        function initTcknLookup() {
            const input = document.getElementById('applicant_national_id');
            const btn = document.getElementById('tckn-check-btn');
            const status = document.getElementById('tckn-check-status');
            const firstNameInput = document.getElementById('applicant_first_name');
            const phoneInput = document.getElementById('applicant_phone');
            const tcNoInput = document.getElementById('tc_no');
            const identityNoInput = document.getElementById('identity_no');

            function setStatus(msg, tone) {
                if (!status) return;
                status.textContent = msg;
                status.className = 'mt-1 text-xs';
                var cls = { neutral: 'text-slate-500', success: 'text-emerald-700', error: 'text-red-600', info: 'text-cyan-700' }[tone] || 'text-slate-500';
                status.classList.add(cls);
            }

            async function check() {
                var raw = String(input?.value || '');
                var tckn = raw.replace(/\D+/g, '');
                if (tckn.length !== 11) { setStatus('TCKN 11 haneli olmalıdır.', 'error'); return; }
                if (input) input.value = tckn;
                if (tcNoInput) tcNoInput.value = tckn;
                if (identityNoInput) identityNoInput.value = tckn;
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                if (!csrf) { setStatus('CSRF token bulunamadı.', 'error'); return; }
                if (btn) { btn.disabled = true; btn.classList.add('opacity-70', 'cursor-not-allowed'); }
                setStatus('TCKN sorgulanıyor...', 'info');
                try {
                    var r = await fetch(@json(route('admin.applications.check-applicant')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ applicant_national_id: tckn }),
                    });
                    var p = await r.json().catch(function () { return {}; });
                    if (!r.ok) { setStatus(typeof p?.message === 'string' ? p.message : 'Sorgu hatası.', 'error'); return; }
                    if (p?.found && p?.data) {
                        var fullName = ((p.data.applicant_first_name || '') + ' ' + (p.data.applicant_last_name || '')).trim();
                        if (firstNameInput && fullName) firstNameInput.value = fullName;
                        if (phoneInput && p.data.applicant_phone) phoneInput.value = p.data.applicant_phone;
                        if (input && p.data.applicant_national_id) input.value = p.data.applicant_national_id;
                        var norm = p.data.applicant_national_id || tckn;
                        if (tcNoInput) tcNoInput.value = norm;
                        if (identityNoInput) identityNoInput.value = norm;
                        var addr = document.getElementById('address_text');
                        if (addr && p.data.address_text) addr.value = p.data.address_text;
                        setStatus('Kayıt bulundu. Alanlar dolduruldu.', 'success');
                        return;
                    }
                    setStatus(p?.message || 'Kayıt bulunamadı.', 'neutral');
                } catch (e) { setStatus('Ağ hatası.', 'error'); }
                finally { if (btn) { btn.disabled = false; btn.classList.remove('opacity-70', 'cursor-not-allowed'); } }
            }

            btn?.addEventListener('click', check);
            input?.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); check(); } });
        }

        // ─── MAP ENGINE ───────────────────────────────────────────────────
        function initMap() {
            var mapEl = document.getElementById('application-drawing-map');
            var geojsonInput = document.getElementById('polygon_geojson');
            var areaInput = document.getElementById('total_area_m2');
            var centerLatInput = document.getElementById('center_lat');
            var centerLngInput = document.getElementById('center_lng');
            var centerDisplay = document.getElementById('center-display');
            var clearBtn = document.getElementById('map-clear-btn');
            var applyGeojsonBtn = document.getElementById('map-apply-geojson-btn');
            var statusEl = document.getElementById('map-status');
            var institutionSelect = document.getElementById('institution_id');
            var activeColorDot = document.getElementById('active-draw-color-dot');
            var lineLengthDisplay = document.getElementById('line-length-display');

            if (!mapEl || !geojsonInput || !areaInput) return;

            var _latStr = centerLatInput?.value?.trim() || '';
            var _lngStr = centerLngInput?.value?.trim() || '';
            var initLat = _latStr ? Number(_latStr) : NaN;
            var initLng = _lngStr ? Number(_lngStr) : NaN;
            var defaultCenter = Number.isFinite(initLat) && Number.isFinite(initLng) ? [initLat, initLng] : [37.1598, 38.7969];

            var normalizeColor = function (inst) {
                if (!inst || typeof inst !== 'object') return '#DC2626';
                var slug = String(inst.slug || '').toLowerCase();
                var name = String(inst.name || '').toLowerCase();
                if (inst.is_municipality || slug === 'belediye' || name.includes('belediye')) return '#16A34A';
                if (slug === 'tedas' || name.includes('tedaş') || name.includes('tedas')) return '#DC2626';
                if (slug === 'suski' || name.includes('şuski') || name.includes('suski')) return '#2563EB';
                if (slug === 'aksa' || name.includes('aksa')) return '#EA580C';
                return inst.color_code || '#DC2626';
            };

            var getDrawColor = function () {
                if (!institutionSelect) return normalizeColor(INSTITUTIONS[0]);
                var id = Number(institutionSelect.value);
                var sel = INSTITUTIONS.find(function (i) { return Number(i.id) === id; });
                return normalizeColor(sel || INSTITUTIONS[0]);
            };

            var setCenter = function (latLng) {
                if (!latLng) {
                    if (centerDisplay) centerDisplay.textContent = '—';
                    if (centerLatInput) centerLatInput.value = '';
                    if (centerLngInput) centerLngInput.value = '';
                    return;
                }
                var lat = Number(latLng.lat).toFixed(6);
                var lng = Number(latLng.lng).toFixed(6);
                if (centerLatInput) centerLatInput.value = lat;
                if (centerLngInput) centerLngInput.value = lng;
                if (centerDisplay) centerDisplay.textContent = lat + ', ' + lng;
            };

            var toRad = function (v) { return Number(v) * Math.PI / 180; };
            var EARTH_R = 6378137;

            var dist = function (a, b) {
                var dLat = toRad(b.lat - a.lat);
                var dLng = toRad(b.lng - a.lng);
                var slat = Math.sin(dLat / 2);
                var slng = Math.sin(dLng / 2);
                var hav = slat * slat + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * slng * slng;
                return 2 * EARTH_R * Math.asin(Math.min(1, Math.sqrt(hav)));
            };

            var polyLen = function (pts) {
                var t = 0;
                for (var i = 1; i < pts.length; i++) t += dist(pts[i - 1], pts[i]);
                return t;
            };

            var polyArea = function (pts) {
                if (pts.length < 3) return 0;
                var avg = 0;
                for (var i = 0; i < pts.length; i++) avg += pts[i].lat;
                avg /= pts.length;
                var sc = Math.cos(toRad(avg));
                var s = 0;
                for (var i = 0; i < pts.length; i++) {
                    var a = pts[i], b = pts[(i + 1) % pts.length];
                    s += (EARTH_R * toRad(a.lng) * sc) * (EARTH_R * toRad(b.lat))
                       - (EARTH_R * toRad(b.lng) * sc) * (EARTH_R * toRad(a.lat));
                }
                return Math.abs(s) / 2;
            };

            var rectArea = function (b) {
                var w = dist({ lat: b.getSouth(), lng: b.getWest() }, { lat: b.getSouth(), lng: b.getEast() });
                var h = dist({ lat: b.getSouth(), lng: b.getWest() }, { lat: b.getNorth(), lng: b.getWest() });
                return w * h;
            };

            var geodesicArea = function (layer) {
                var pts = layer instanceof L.Polyline ? (layer.getLatLngs() || []) : (layer.getLatLngs()[0] || []);
                if (typeof L.GeometryUtil !== 'undefined' && L.GeometryUtil.geodesicArea && pts.length >= 3) {
                    try { return L.GeometryUtil.geodesicArea(pts); } catch (e) { /* plugin yoksa fallback */ }
                }
                if (layer instanceof L.Rectangle) return rectArea(layer.getBounds());
                return polyArea(pts);
            };

            var lastLineLen = 0;
            var syncArea = function (areaM2, lineLenM) {
                var areaInput = document.getElementById('total_area_m2');
                if (!areaInput) return;
                // Genişlik önceliği: aktif satırın Genişlik (m) değeri → poly_width → 1m
                var w = NaN;
                var aktifRow = surfaceLines.find(function (r) { return r.rowId === activeDrawRowId; });
                if (aktifRow) w = parseFloat(aktifRow.width_m);
                if (!Number.isFinite(w) || w <= 0) w = parseFloat((document.getElementById('poly_width') || {}).value);
                if (!Number.isFinite(w) || w <= 0) w = 1;
                if (lineLenM > 0) {
                    lastLineLen = lineLenM;
                    areaInput.value = (lineLenM * w).toFixed(2);
                } else if (areaM2 > 0) {
                    areaInput.value = areaM2.toFixed(2);
                }
                if (areaInput.dispatchEvent) areaInput.dispatchEvent(new Event('input'));
                setTimeout(function () { recalculateAll(); }, 200);
            };

            var polyWidthEl = document.getElementById('poly_width');
            if (polyWidthEl) {
                polyWidthEl.addEventListener('input', function () {
                    var areaInput = document.getElementById('total_area_m2');
                    if (!areaInput || lastLineLen <= 0) return;
                    var w = parseFloat(polyWidthEl.value);
                    if (!Number.isFinite(w) || w <= 0) w = 1;
                    areaInput.value = (lastLineLen * w).toFixed(2);
                    recalculateAll();
                });
            }

            var toPath = function (ring) {
                return Array.isArray(ring) ? ring.filter(function (p) { return Array.isArray(p) && p.length >= 2; }).map(function (p) { return { lat: Number(p[1]), lng: Number(p[0]) }; }).filter(function (p) { return Number.isFinite(p.lat) && Number.isFinite(p.lng); }) : [];
            };

            var parseGeo = function (raw) {
                if (!raw) return [];
                var data;
                try { data = typeof raw === 'string' ? JSON.parse(raw) : raw; } catch (e) { return []; }
                if (!data || typeof data !== 'object') return [];
                if (data.type === 'FeatureCollection' && Array.isArray(data.features)) return data.features;
                if (data.type === 'Feature') return [data];
                return [{ type: 'Feature', geometry: data, properties: {} }];
            };

            // Map init
            var map = L.map('application-drawing-map', {
                center: defaultCenter,
                zoom: 14,
                zoomControl: false,
            });

            // CBS "Koordinat ile Bul" → çizim haritasına da konum göstermek için
            window.aykomeDrawingGoster = function (lat, lon, label) {
                var mk = window._drawingSearchMarker;
                if (mk) map.removeLayer(mk);
                window._drawingSearchMarker = L.marker([lat, lon], {
                    icon: L.divIcon({ className: '', html: '<div style="background:#FA6001;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 4px rgba(250,96,1,.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
                }).addTo(map).bindPopup('<b>' + (label || '') + '</b><br>Koordinat ile bulundu').openPopup();
                map.flyTo([lat, lon], 17, { animate: true, duration: 1 });
            };

            var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 22, maxNativeZoom: 19, attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
            });
            var satellite = L.tileLayer('https://mt0.google.com/vt/lyrs=s&hl=tr&x={x}&y={y}&z={z}', {
                maxZoom: 22, maxNativeZoom: 19, attribution: '&copy; <a href="https://google.com">Google</a>',
            });
            var terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 22, maxNativeZoom: 17, attribution: '&copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
            });
            osm.addTo(map);
            L.control.zoom({ position: 'bottomright', zoomInTitle: 'Yakınlaştır', zoomOutTitle: 'Uzaklaştır' }).addTo(map);
            L.control.layers({ Standart: osm, Uydu: satellite, Arazi: terrain }, null, { position: 'topright' }).addTo(map);

            // ─── HARİTA-İÇİ ADRES ARAMA (Leaflet L.Control — harita yüzeyine gömülü, blade'de input YOK) ────
            var MapInsideSearchControl = L.Control.extend({
                options: { position: 'topleft' },
                onAdd: function () {
                    var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                    L.DomEvent.disableClickPropagation(div);
                    L.DomEvent.disableScrollPropagation(div);
                    div.style.cssText = 'display:flex;align-items:center;gap:2px;padding:4px;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.2);';
                    div.innerHTML = '<input type="text" id="mapInsideSearch" placeholder="Cadde, sokak ara..." autocomplete="off" class="px-2 py-1 w-64 border rounded shadow" style="font-size:13px;border:1px solid #cbd5e1;outline:none;"> <button type="button" id="btn_map_inside_search" class="bg-blue-600 text-white px-2 py-1 rounded" style="border:none;cursor:pointer;font-size:13px;">Ara</button>';
                    return div;
                }
            });
            map.addControl(new MapInsideSearchControl());

            var mapInsideSearchGo = function () {
                var inp = document.getElementById('mapInsideSearch');
                var btn = document.getElementById('btn_map_inside_search');
                var q = (inp ? inp.value : '').trim();
                if (q.length < 3) { alert('Lütfen cadde, sokak veya mahalle adı girin.'); return; }
                if (btn) btn.textContent = '...';
                fetch(@json(route('maps.adres-ara')) + '?q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (btn) btn.textContent = 'Ara';
                        if (d && d.success && d.lat) {
                            haritadaGoster(parseFloat(d.lat), parseFloat(d.lon), d.cadde || d.detail || q);
                        } else {
                            alert(d.message || 'Cadde sistemde bulunamadı.');
                        }
                    })
                    .catch(function () { if (btn) btn.textContent = 'Ara'; alert('Arama servisi yanıt vermedi.'); });
            };
            document.getElementById('btn_map_inside_search')?.addEventListener('click', mapInsideSearchGo);
            document.getElementById('mapInsideSearch')?.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); mapInsideSearchGo(); }
            });

            // Style panel
            var styleRow = document.getElementById('map-style-panel');
            if (styleRow) {
                var switchLayer = function (id) {
                    var layers = { 'style-standard': osm, 'style-satellite': satellite, 'style-terrain': terrain };
                    Object.entries(layers).forEach(function (e) { if (e[0] === id) map.addLayer(e[1]); else map.removeLayer(e[1]); });
                    document.querySelectorAll('#map-style-panel button').forEach(function (b) {
                        var active = b.id === id;
                        b.className = active
                            ? 'w-full rounded-lg border border-[#FA6001]/30 bg-[#FA6001]/10 px-3 py-1.5 text-left text-[11px] font-semibold text-[#FA6001] transition hover:bg-[#FA6001]/20'
                            : 'w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-left text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50';
                    });
                };
                document.getElementById('style-standard')?.addEventListener('click', function () { switchLayer('style-standard'); });
                document.getElementById('style-satellite')?.addEventListener('click', function () { switchLayer('style-satellite'); });
                document.getElementById('style-terrain')?.addEventListener('click', function () { switchLayer('style-terrain'); });
            }

            var strokeColor = getDrawColor();
            var drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            var updateColorPreview = function () { if (activeColorDot) activeColorDot.style.backgroundColor = strokeColor; };
            updateColorPreview();

            var drawControl = null;
            var buildDrawControl = function () {
                return new L.Control.Draw({
                    edit: { featureGroup: drawnItems, edit: true, remove: true },
                    draw: {
                        polygon: { shapeOptions: { color: strokeColor, fillOpacity: 0.22, weight: 2 } },
                        polyline: { shapeOptions: { color: strokeColor, weight: 3 } },
                        circle: { shapeOptions: { color: strokeColor, fillOpacity: 0.22, weight: 2 } },
                        rectangle: { shapeOptions: { color: strokeColor, fillOpacity: 0.22, weight: 2 } },
                        marker: true,
                        circlemarker: false,
                    },
                });
            };
            var refreshDrawControl = function () {
                if (drawControl) map.removeControl(drawControl);
                drawControl = buildDrawControl();
                map.addControl(drawControl);
            };
            refreshDrawControl();

            var repaintOverlays = function () {
                drawnItems.eachLayer(function (layer) {
                    if (layer.setStyle) layer.setStyle({ color: strokeColor, fillColor: strokeColor });
                });
            };

            // My Location
            var myLocationMarker = null;
            var markMyLocation = function (lat, lng) {
                var p = [lat, lng];
                map.setView(p, 17);
                if (myLocationMarker) map.removeLayer(myLocationMarker);
                myLocationMarker = L.marker(p).addTo(map);
                myLocationMarker.bindPopup('📍 Konumum');
            };
            var geoErrorMsg = function (err) {
                if (!err) return 'Konum alınamadı.';
                if (err.code === 1) return 'Konum izni reddedildi — tarayıcının adres çubuğundaki kilit simgesinden "Konum" iznini verin.';
                if (err.code === 2) return 'Konum bulunamadı (cihaz GPS\'i yanıt vermedi). IP\'ye göre deneniyor...';
                if (err.code === 3) return 'Konum isteği zaman aşımına uğradı — tekrar deneyin.';
                return 'Konum alınamadı: ' + (err.message || 'bilinmeyen hata');
            };
            var ipFallback = function () {
                if (statusEl) statusEl.textContent = 'IP tabanlı konum deneniyor...';
                fetch('https://ipapi.co/json/', { cache: 'no-cache' })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                    .then(function (d) {
                        if (!d || !Number.isFinite(parseFloat(d.latitude)) || !Number.isFinite(parseFloat(d.longitude))) throw new Error('no-coord');
                        markMyLocation(parseFloat(d.latitude), parseFloat(d.longitude));
                        if (statusEl) statusEl.textContent = 'IP tabanlı konum işaretlendi (hassas değil) — ' + (d.city ? d.city + ', ' + d.region : '');
                    })
                    .catch(function () { if (statusEl) statusEl.textContent = 'Konum alınamadı. Tarayıcı iznini kontrol edin veya haritaya tıklayıp konumu elle işaretleyin.'; });
            };
            var MyLocationControl = L.Control.extend({
                onAdd: function () {
                    var btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
                    btn.innerHTML = '📍';
                    btn.title = 'Konumum';
                    btn.setAttribute('type', 'button');
                    btn.style.cssText = 'width:36px;height:36px;font-size:18px;background:#fff;border:2px solid rgba(0,0,0,0.2);background-clip:padding-box;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;';
                    btn.onmouseover = function () { btn.style.background = '#f4f4f4'; };
                    btn.onmouseout = function () { btn.style.background = '#fff'; };
                    L.DomEvent.on(btn, 'click', function (e) {
                        L.DomEvent.stopPropagation(e);
                        L.DomEvent.preventDefault(e);
                        if (!navigator.geolocation) { ipFallback(); return; }
                        if (statusEl) statusEl.textContent = 'Konum alınıyor... (cihaz GPS\'i bekleniyor)';
                        navigator.geolocation.getCurrentPosition(
                            function (pos) {
                                markMyLocation(pos.coords.latitude, pos.coords.longitude);
                                if (statusEl) statusEl.textContent = 'Konum işaretlendi.';
                            },
                            function (err) {
                                if (statusEl) statusEl.textContent = geoErrorMsg(err);
                                if (err && err.code === 2) ipFallback();
                            },
                            { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 },
                        );
                    });
                    return btn;
                },
            });
            map.addControl(new MyLocationControl({ position: 'topright' }));

            // Draw color picker
            var ColorPickerControl = L.Control.extend({
                onAdd: function () {
                    var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    container.style.cssText = 'display:flex;align-items:center;gap:6px;background:#fff;padding:5px 8px;border:2px solid rgba(0,0,0,0.2);background-clip:padding-box;border-radius:4px;font-size:11px;font-weight:600;color:#475569;';
                    container.innerHTML = '<span>Renk</span>';
                    var picker = document.createElement('input');
                    picker.type = 'color';
                    picker.id = 'draw_color_picker';
                    picker.value = strokeColor;
                    picker.title = 'Çizim rengini seçin';
                    picker.style.cssText = 'width:36px;height:28px;padding:0;border:none;background:transparent;cursor:pointer;';
                    container.appendChild(picker);
                    L.DomEvent.disableClickPropagation(container);
                    L.DomEvent.disableScrollPropagation(container);
                    picker.addEventListener('input', function () {
                        strokeColor = picker.value;
                        updateColorPreview();
                        repaintOverlays();
                        refreshDrawControl();
                        if (statusEl) statusEl.textContent = 'Çizim rengi: ' + strokeColor;
                    });
                    return container;
                },
            });
            map.addControl(new ColorPickerControl({ position: 'topright' }));

            // Serialize
            var serializeAndSync = function (message) {
                if (!message) message = 'Çizim güncellendi.';
                var features = [];
                var totalArea = 0, totalLineLength = 0;
                var bounds = L.latLngBounds();
                var centerCandidate = null;

                drawnItems.eachLayer(function (layer) {
                    var feature = null;
                    var props = {};

                    if (layer instanceof L.Polygon && !(layer instanceof L.Rectangle) && !(layer instanceof L.Circle)) {
                        var pts = layer.getLatLngs()[0];
                        var coords = pts.map(function (p) { return [p.lng, p.lat]; });
                        coords.push([pts[0].lng, pts[0].lat]);
                        props.shape = 'polygon';
                        if (activeDrawRowId) props.rowId = activeDrawRowId;
                        feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: props };
                        totalArea += polyArea(pts);
                        pts.forEach(function (p) { bounds.extend(p); });
                        centerCandidate = bounds.getCenter();
                    } else if (layer instanceof L.Rectangle) {
                        var b = layer.getBounds(), ne = b.getNorthEast(), sw = b.getSouthWest();
                        var coords2 = [[sw.lng, sw.lat], [ne.lng, sw.lat], [ne.lng, ne.lat], [sw.lng, ne.lat], [sw.lng, sw.lat]];
                        props.shape = 'rectangle';
                        if (activeDrawRowId) props.rowId = activeDrawRowId;
                        feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords2] }, properties: props };
                        totalArea += rectArea(b);
                        bounds.extend(ne); bounds.extend(sw);
                        centerCandidate = b.getCenter();
                    } else if (layer instanceof L.Circle) {
                        var c = layer.getLatLng(), r = layer.getRadius(), p_cnt = 64, coords3 = [];
                        for (var i = 0; i < p_cnt; i++) {
                            var a = (i / p_cnt) * 2 * Math.PI;
                            coords3.push([c.lng + (r / (111320 * Math.cos(c.lat * Math.PI / 180))) * Math.sin(a), c.lat + (r / 111320) * Math.cos(a)]);
                        }
                        coords3.push(coords3[0]);
                        props.shape = 'circle';
                        if (activeDrawRowId) props.rowId = activeDrawRowId;
                        feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords3] }, properties: props };
                        totalArea += Math.PI * r * r;
                        var cb = layer.getBounds();
                        if (cb) { bounds.extend(cb.getNorthEast()); bounds.extend(cb.getSouthWest()); }
                        centerCandidate = c;
                    } else if (layer instanceof L.Polyline) {
                        var pts2 = layer.getLatLngs();
                        var coords4 = pts2.map(function (p) { return [p.lng, p.lat]; });
                        props.shape = 'polyline';
                        feature = { type: 'Feature', geometry: { type: 'LineString', coordinates: coords4 }, properties: props };
                        totalLineLength += polyLen(pts2);
                        var plRowId = layer._rowId;
                        if (plRowId != null) {
                            var plRow = surfaceLines.find(function (r) { return r.rowId === plRowId; });
                            if (plRow) totalArea += parseFloat(plRow.quantity) || 0;
                        } else {
                            // Satıra bağlanmamış düz çizgi: uzunluk × 1m (varsayılan kanal genişliği)
                            totalArea += polyLen(pts2) * 1;
                        }
                        pts2.forEach(function (p) { bounds.extend(p); });
                        if (!centerCandidate) centerCandidate = pts2[Math.floor(pts2.length / 2)];
                    } else if (layer instanceof L.Marker) {
                        var p2 = layer.getLatLng();
                        props.shape = 'marker';
                        feature = { type: 'Feature', geometry: { type: 'Point', coordinates: [p2.lng, p2.lat] }, properties: props };
                        bounds.extend(p2);
                        if (!centerCandidate) centerCandidate = p2;
                    }
                    if (feature) features.push(feature);
                });

                geojsonInput.value = features.length ? JSON.stringify({ type: 'FeatureCollection', features }) : '';
                areaInput.value = Number.isFinite(totalArea) ? totalArea.toFixed(3) : '0';
                if (lineLengthDisplay) lineLengthDisplay.textContent = (Number.isFinite(totalLineLength) ? totalLineLength : 0).toFixed(3) + ' m';
                if (centerCandidate) setCenter({ lat: centerCandidate.lat, lng: centerCandidate.lng });
                else setCenter(null);
                if (statusEl) statusEl.textContent = message;
            };

            // Draw events
            map.on(L.Draw.Event.CREATED, function (e) {
                var layer = e.layer;
                if (layer.setStyle) layer.setStyle({ color: strokeColor, fillColor: strokeColor });
                drawnItems.addLayer(layer);

                var area = 0;
                var lineLen = 0;
                if (layer instanceof L.Polygon && !(layer instanceof L.Rectangle) && !(layer instanceof L.Circle)) {
                    area = geodesicArea(layer);
                } else if (layer instanceof L.Rectangle) {
                    area = geodesicArea(layer);
                } else if (layer instanceof L.Circle) {
                    area = Math.PI * layer.getRadius() * layer.getRadius();
                } else if (layer instanceof L.Polyline) {
                    lineLen = polyLen(layer.getLatLngs() || []);
                }
                area = parseFloat(area.toFixed(2));
                lineLen = parseFloat(lineLen.toFixed(2));
                syncArea(area, lineLen);

                var capturedRowId = activeDrawRowId;

                // Aktif satır yoksa: adresi dolu ve çizimi olmayan ilk satıra otomatik bağla
                // (kullanıcı adresleri tek tek çizse bile her çizim doğru satıra yazılır)
                if (!capturedRowId) {
                    var ilkAdresliCizimsiz = surfaceLines.find(function (r) {
                        return r.address && !rowDrawings[r.rowId] && !r.surface_type_id;
                    }) || surfaceLines.find(function (r) {
                        return r.address && !rowDrawings[r.rowId];
                    });
                    if (ilkAdresliCizimsiz) {
                        capturedRowId = ilkAdresliCizimsiz.rowId;
                        activeDrawRowId = capturedRowId;
                        updateActiveDrawIndicator();
                    }
                }

                if (capturedRowId && (area > 0 || lineLen > 0)) {
                    var row = surfaceLines.find(function (r) { return r.rowId === capturedRowId; });
                    if (row) {
                        var feature = null;

                        if (area > 0) {
                            row.quantity = area;
                            var sqrtVal = parseFloat(Math.sqrt(area).toFixed(2));
                            row.width_m = sqrtVal;
                            row.length_m = sqrtVal;

                            if (layer instanceof L.Polygon) {
                                var coords = (layer.getLatLngs()[0] || []).map(function (p) { return [p.lng, p.lat]; });
                                if (coords.length) {
                                    coords.push([coords[0][0], coords[0][1]]);
                                    feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { rowId: capturedRowId, shape: 'polygon' } };
                                    layer._rowId = capturedRowId;
                                }
                            } else if (layer instanceof L.Circle) {
                                var cc = layer.getLatLng(), rr = layer.getRadius(), cnt = 64, ccoords = [];
                                for (var ci = 0; ci < cnt; ci++) {
                                    var ca = (ci / cnt) * 2 * Math.PI;
                                    ccoords.push([cc.lng + (rr / (111320 * Math.cos(cc.lat * Math.PI / 180))) * Math.sin(ca), cc.lat + (rr / 111320) * Math.cos(ca)]);
                                }
                                ccoords.push(ccoords[0]);
                                feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [ccoords] }, properties: { rowId: capturedRowId, shape: 'circle' } };
                                layer._rowId = capturedRowId;
                            }
                        } else if (lineLen > 0) {
                            row.length_m = lineLen;
                            var wEl2 = document.querySelector('.row-width[data-row-id="' + capturedRowId + '"]');
                            var wCur = wEl2 ? parseFloat(wEl2.value) : NaN;
                            if (isInstitutionUser) {
                                row.width_m = 1;
                            } else if (Number.isFinite(wCur) && wCur > 0) {
                                row.width_m = wCur;
                            } else if (parseFloat(row.width_m) > 0) {
                                row.width_m = parseFloat(row.width_m);
                            } else {
                                row.width_m = 1;
                            }
                            row.quantity = parseFloat((row.length_m * row.width_m).toFixed(2));

                            var lcoords = (layer.getLatLngs() || []).map(function (p) { return [p.lng, p.lat]; });
                            if (lcoords.length >= 2) {
                                feature = { type: 'Feature', geometry: { type: 'LineString', coordinates: lcoords }, properties: { rowId: capturedRowId, shape: 'polyline' } };
                            }
                            layer._rowId = capturedRowId;
                        }

                        if (feature) rowDrawings[capturedRowId] = feature;

                        layer.bindTooltip('Sat\u0131r: #' + capturedRowId, { permanent: true, direction: 'top', offset: [0, -10], className: 'row-tooltip' });

                        activeDrawRowId = null;
                        updateActiveDrawIndicator();
                        renderTable();
                        serializeAndSync('Çizim sat\u0131r #' + capturedRowId + ' için kaydedildi.');

                        // ── Defansif DOM yazımı: ekranda sıfır kalmasın ──
                        var _wEl = document.querySelector('.row-width[data-row-id="' + capturedRowId + '"]');
                        if (_wEl) {
                            var locked = isInstitutionUser && rowHasLineDrawing(capturedRowId);
                            if (locked) { _wEl.value = '1.00'; _wEl.readOnly = true; }
                            else { _wEl.readOnly = false; _wEl.value = row.width_m ? row.width_m.toFixed(2) : ''; }
                        }
                        var _lEl = document.querySelector('.row-length[data-row-id="' + capturedRowId + '"]');
                        if (_lEl) _lEl.value = row.length_m ? row.length_m.toFixed(2) : '';
                        var _qEl = document.querySelector('.row-quantity[data-row-id="' + capturedRowId + '"]');
                        if (_qEl) _qEl.value = row.quantity ? row.quantity.toFixed(2) : '';
                        if (_lEl && _lEl.dispatchEvent) _lEl.dispatchEvent(new Event('input'));
                        return;
                    }
                }

                if (capturedRowId) {
                    layer.bindTooltip('Sat\u0131r: #' + capturedRowId, { permanent: true, direction: 'top', offset: [0, -10], className: 'row-tooltip' });
                }

                serializeAndSync('Çizim haritaya işlendi.');
            });

            map.on(L.Draw.Event.EDITED, function () { serializeAndSync('Çizim güncellendi.'); renderTable(); });
            map.on(L.Draw.Event.DELETED, function (e) {
                if (e && e.layers) {
                    e.layers.eachLayer(function (l) {
                        if (l._rowId != null) delete rowDrawings[l._rowId];
                    });
                }
                renderTable();
                serializeAndSync('Çizim silindi.');
            });

            map.on('click', function (e) {
                if (drawnItems.getLayers().length === 0) setCenter({ lat: e.latlng.lat, lng: e.latlng.lng });
            });

            // Clear
            clearBtn?.addEventListener('click', function () {
                drawnItems.clearLayers();
                geojsonInput.value = '';
                areaInput.value = '0';
                rowDrawings = {};
                setCenter(null);
                renderTable();
                if (statusEl) statusEl.textContent = 'Çizim temizlendi.';
            });

            // Apply GeoJSON
            applyGeojsonBtn?.addEventListener('click', function () {
                var features = parseGeo(geojsonInput.value);
                drawnItems.clearLayers();
                if (!features.length) { if (statusEl) statusEl.textContent = 'GeoJSON yüklenemedi.'; return; }
                var bounds = L.latLngBounds();
                features.forEach(function (f) {
                    if (!f.geometry || !f.geometry.type) return;
                    var g = f.geometry;
                    var layer = null;
                    var rid = f.properties && f.properties.rowId;
                    if (g.type === 'Polygon') {
                        var ring = g.coordinates[0] || [];
                        var pts = ring.map(function (p) { return [p[1], p[0]]; });
                        if (pts.length >= 4) {
                            layer = L.polygon(pts, { color: strokeColor, fillColor: strokeColor, fillOpacity: 0.22, weight: 2 });
                            pts.forEach(function (p) { bounds.extend(p); });
                        }
                        if (rid) { rowDrawings[rid] = f; }
                    } else if (g.type === 'LineString') {
                        var pts2 = g.coordinates.map(function (p) { return [p[1], p[0]]; });
                        if (pts2.length >= 2) {
                            layer = L.polyline(pts2, { color: strokeColor, weight: 3 });
                            pts2.forEach(function (p) { bounds.extend(p); });
                        }
                    } else if (g.type === 'Point') {
                        layer = L.marker([g.coordinates[1], g.coordinates[0]]);
                        bounds.extend([g.coordinates[1], g.coordinates[0]]);
                    }
                    if (layer) {
                        if (rid) layer.bindTooltip('Sat\u0131r: #' + rid, { permanent: true, direction: 'top', offset: [0, -10], className: 'row-tooltip' });
                        drawnItems.addLayer(layer);
                    }
                });
                if (bounds.isValid()) map.fitBounds(bounds);
                serializeAndSync('GeoJSON haritaya uygulandı.');
                renderTable();
            });

            // Institution color change
            institutionSelect?.addEventListener('change', function () {
                strokeColor = getDrawColor();
                updateColorPreview();
                repaintOverlays();
                refreshDrawControl();
                var dp = document.getElementById('draw_color_picker');
                if (dp) dp.value = strokeColor;
                if (statusEl) statusEl.textContent = 'Çizim rengi güncellendi.';
            });

            // Load existing GeoJSON
            if (geojsonInput.value.trim() !== '') {
                applyGeojsonBtn?.click();
            } else {
                if (statusEl) statusEl.textContent = 'Haritadan bir alan seçin.';
            }

            map.on('moveend', function () {
                if (drawnItems.getLayers().length === 0) {
                    var c = map.getCenter();
                    setCenter({ lat: c.lat, lng: c.lng });
                }
            });

            window.map = map;
            window.appDrawMap = map;
            return { drawnItems: drawnItems, map: map, serializeAndSync: serializeAndSync };
        }

        // ─── DOCUMENT UPLOAD ──────────────────────────────────────────────
        function initDocumentUpload() {
            var dz = document.getElementById('document-dropzone');
            var inp = document.getElementById('document-input');
            var preview = document.getElementById('document-preview');
            var status = document.getElementById('document-status');
            var allFiles = [];

            function render() {
                if (!preview || !status) return;
                preview.innerHTML = '';
                if (!allFiles.length) { status.textContent = ''; return; }
                status.textContent = allFiles.length + ' dosya seçildi.';
                allFiles.forEach(function (f, i) {
                    var img = f.type.startsWith('image/');
                    var div = document.createElement('div');
                    div.className = 'relative rounded-lg border border-slate-200 bg-white p-2 shadow-sm';
                    div.innerHTML =
                        '<div class="flex items-center gap-2">' +
                            (img ? '<img src="' + URL.createObjectURL(f) + '" class="h-10 w-10 rounded object-cover">' : '<span class="flex h-10 w-10 items-center justify-center rounded bg-slate-100 text-xs font-bold text-slate-500">PDF</span>') +
                            '<div class="min-w-0">' +
                                '<p class="truncate text-xs font-medium text-slate-700 max-w-[180px]">' + f.name + '</p>' +
                                '<p class="text-[10px] text-slate-500">' + (f.size / 1024).toFixed(1) + ' KB</p>' +
                            '</div>' +
                            '<button type="button" class="rm-file shrink-0 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600" data-idx="' + i + '">&times;</button>' +
                        '</div>';
                    preview.appendChild(div);
                });
                preview.querySelectorAll('.rm-file').forEach(function (b) {
                    b.addEventListener('click', function () {
                        allFiles.splice(Number(b.dataset.idx), 1);
                        render();
                        syncInput();
                    });
                });
            }

            function syncInput() {
                if (!inp) return;
                var dt = new DataTransfer();
                allFiles.forEach(function (f) { dt.items.add(f); });
                try { inp.files = dt.files; } catch (e) {}
            }

            dz?.addEventListener('click', function () { inp?.click(); });
            dz?.addEventListener('dragover', function (e) { e.preventDefault(); if (dz) dz.classList.replace('border-slate-300', 'border-sky-400'); });
            dz?.addEventListener('dragleave', function () { if (dz) dz.classList.replace('border-sky-400', 'border-slate-300'); });
            dz?.addEventListener('drop', function (e) {
                e.preventDefault();
                if (dz) dz.classList.replace('border-sky-400', 'border-slate-300');
                Array.from(e.dataTransfer.files).forEach(function (f) { if (!allFiles.some(function (x) { return x.name === f.name && x.size === f.size; })) allFiles.push(f); });
                syncInput();
                render();
            });
            inp?.addEventListener('change', function () {
                Array.from(inp.files).forEach(function (f) { if (!allFiles.some(function (x) { return x.name === f.name && x.size === f.size; })) allFiles.push(f); });
                syncInput();
                render();
            });
        }

        function setField(id, val, lock) {
            var el = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
            if (!el) return;
            if (val !== null) el.value = val || '';
            if (lock) { el.readOnly = true; }
            else { el.removeAttribute('readonly'); }
        }

        // ─── INSTITUTION → DICLE + TEMINAT + AUTO-FILL ──────────────────
        function initInstitutionWatcher() {
            var sel = document.getElementById('institution_id');
            if (!sel) return;

            function checkDicle() {
                var opt = sel.options[sel.selectedIndex];
                var isMerkez = opt && opt.dataset.isMerkez === '1';
                var isEmpty = !opt || opt.value === '';

                // TC Kimlik No alanı: alt kurum seçildiğinde vergi no girilir → 11 hane
                // kısıtı yalnızca merkez belediye başvurularında uygulanır.
                var natIdInput = document.getElementById('applicant_national_id');
                if (natIdInput) {
                    if (isEmpty || isMerkez) {
                        natIdInput.maxLength = 11;
                        natIdInput.setAttribute('required', 'required');
                    } else {
                        natIdInput.removeAttribute('maxlength');
                        natIdInput.removeAttribute('required');
                    }
                }

                if (isEmpty || isMerkez) {
                    isDicleElektrik = false;
                    isInstitutionUser = false;
                    setField('applicant_first_name', null, false);
                    setField('applicant_national_id', null, false);
                    setField('tc_no', null, false);
                    setField('identity_no', null, false);
                    setField('applicant_phone', null, false);
                } else {
                    isDicleElektrik = opt.dataset.tax === '2950368442';
                    isInstitutionUser = true;
                    setField('applicant_first_name', opt.dataset.name, true);
                    setField('applicant_national_id', opt.dataset.tax, true);
                    setField('tc_no', opt.dataset.tax, true);
                    setField('identity_no', opt.dataset.tax, true);
                    setField('applicant_phone', opt.dataset.phone, true);
                }
                renderTable();
            }

            sel.addEventListener('change', checkDicle);
            checkDicle();
        }

        // ─── AUTO DATE ADDER ──────────────────────────────────────────────
        function initAutoDateAdder() {
            var adder = document.getElementById('auto_date_adder');
            var startDate = document.getElementById('start_date');
            var endDate = document.getElementById('end_date');
            if (!adder || !startDate || !endDate) return;

            adder.addEventListener('change', function () {
                var days = parseInt(this.value);
                if (!days || !startDate.value) { this.value = ''; return; }
                var d = new Date(startDate.value);
                if (isNaN(d.getTime())) { this.value = ''; return; }
                if (days === 30) {
                    d.setMonth(d.getMonth() + 1);
                } else {
                    d.setDate(d.getDate() + days);
                }
                var y = d.getFullYear();
                var m = String(d.getMonth() + 1).padStart(2, '0');
                var dd = String(d.getDate()).padStart(2, '0');
                endDate.value = y + '-' + m + '-' + dd;
                this.value = '';
            });
        }

        // ─── (ESKİ KONUM BULMA SİSTEMİ KALDIRILDI) ────────────────────────
        // YENİ KONUM BUL (ANİMASYONLU): maps/index'teki search-spinner + pulse
        // marker + animasyonlu flyTo deseni forma taşındı. WMS konum bulma
        // ─── CADDE/SOKAK VERİ GİRİŞİ (üst yazı tablosu için) ────────────────
        // Sadece veri girişi: mahalle + cadde/sokak listesini address_components
        // hidden alanına serileştirir. Haritaya otomatik gitme YOK (yeşil arama
        // bağlantıları + geocode kaldırıldı).
        // ─── WMS KONUM GÖSTER (2 harita: çizim + CBS) ─────────────────────
        function wmsMaps() {
            return [
                window.appDrawMap || (window.map || null),
                window.appCbsMap || (window.cbsMap || null)
            ].filter(Boolean);
        }

        // Pulse marker + animasyonlu flyTo + cadde etiketi — 2 haritada
        function haritadaGoster(lat, lon, etiket) {
            var maps = wmsMaps();
            if (!maps.length) return;
            maps.forEach(function (m) {
                var key = m === (window.appDrawMap || window.map) ? '_locMarkerMain' : '_locMarkerCbs';
                if (window[key]) m.removeLayer(window[key]);
                var mk = L.marker([lat, lon], {
                    icon: L.divIcon({ className: '', html: '<div class="loc-marker"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
                }).addTo(m);
                if (etiket) {
                    mk.bindTooltip(etiket, { permanent: true, direction: 'top', offset: [0, -12], className: 'loc-tooltip' }).openTooltip();
                }
                window[key] = mk;
                if (typeof m.flyTo === 'function') {
                    m.flyTo([lat, lon], 17, { animate: true, duration: 1 });
                } else if (typeof m.setView === 'function') {
                    m.setView([lat, lon], 17);
                }
            });
        }

        // Birden çok caddeyi toplu göster + fitBounds
        function haritadaCaddeleriGoster(caddeler) {
            var maps = wmsMaps();
            if (!maps.length) return;
            var bounds = [];
            var etiketler = [];
            maps.forEach(function (m) {
                if (window._locBatchMain && m === (window.appDrawMap || window.map)) {
                    m.removeLayer(window._locBatchMain); window._locBatchMain = null;
                }
                if (window._locBatchCbs && m === (window.appCbsMap || window.cbsMap)) {
                    m.removeLayer(window._locBatchCbs); window._locBatchCbs = null;
                }
            });
            var layerGroup = L.layerGroup();
            caddeler.forEach(function (c) {
                var marker = L.marker([c.lat, c.lon], {
                    icon: L.divIcon({ className: '', html: '<div class="loc-marker"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
                });
                marker.bindPopup('<b>' + (c.etiket || c.name || '') + '</b>');
                layerGroup.addLayer(marker);
                bounds.push([c.lat, c.lon]);
            });
            var main = window.appDrawMap || window.map;
            if (main) { layerGroup.addTo(main); window._locBatchMainQt = layerGroup; }
            if (bounds.length) {
                maps.forEach(function (m) {
                    if (typeof m.fitBounds === 'function') m.fitBounds(bounds, { padding: [40, 40], animate: true, duration: 0.8 });
                });
            }
        }

        // ─── KONUM BUL — WMS adres doğrulama (S2S) ─────────────────────────
        document.getElementById('btn-find-location')?.addEventListener('click', async function () {
            var input = document.getElementById('address_text');
            var spinner = document.getElementById('find-loc-spinner');
            var info = document.getElementById('loc-result-info');
            var q = input ? input.value.trim() : '';
            if (!q || q.length < 3) { alert('Lütfen adres girin.'); return; }
            if (spinner) spinner.classList.remove('hidden');
            if (info) info.textContent = 'Konum aranıyor...';
            try {
                var resp = await fetch(@json(route('maps.adres-ara')) + '?q=' + encodeURIComponent(q));
                var data = await resp.json();
                if (data && data.success && data.lat) {
                    haritadaGoster(parseFloat(data.lat), parseFloat(data.lon), data.cadde || data.detail || '');
                    if (info) info.textContent = '✅ ' + (data.detail || 'Konum bulundu') + ' (güven: ' + (data.confidence || '') + ')';
                    // 15m kontrol butonunu göster + son konumu sakla
                    var wrap = document.getElementById('road15-wrap');
                    if (wrap) wrap.classList.remove('hidden');
                    window._sonKonum = { lat: parseFloat(data.lat), lon: parseFloat(data.lon), etiket: data.detail || data.cadde || '' };
                } else {
                    if (info) info.textContent = '';
                    alert(data.message || 'Adres WMS veritabanında bulunamadı.\nLütfen mahalle + cadde/sokak + kapı no formatında girin.');
                }
            } catch (e) {
                console.error('Konum bul hatası:', e);
                if (info) info.textContent = '';
                alert('Konum bulma servisi yanıt vermedi.');
            } finally {
                if (spinner) spinner.classList.add('hidden');
            }
        });

        // ─── KOORDİNAT İLE BUL (tek kutu, virgülle ayrık) — WMS nokta atışı ────
        document.getElementById('btn_coord_search')?.addEventListener('click', function () {
            var input = document.getElementById('coord_single_input');
            var rawCoord = input ? (input.value || '') : '';
            var info = document.getElementById('coord-result-info');
            var fail = function (msg, dangerously) {
                if (info) { info.textContent = msg || 'Geçersiz Koordinat, Lütfen Örnekteki gibi virgüllü ayırarak (X, Y) kopyalayın.'; info.className = 'mb-2 text-xs ' + (dangerously ? 'text-red-600' : 'text-slate-500'); }
                if (!info) alert(msg || 'Geçersiz Koordinat, Lütfen Örnekteki gibi virgüllü ayırarak (X, Y) kopyalayın.');
            };
            if (!rawCoord.trim()) { fail('Lütfen koordinat girin.'); return; }
            var coords = rawCoord.split(',');
            // Gelişmiş regex bölücü: virgül yoksa boşluk / noktalı virgül / " - " ayraçlarını da kabul et
            if (coords.length < 2) {
                var m = rawCoord.trim().match(/^([-+]?\d+(?:[.,]\d+)?)\s*[,;\s/\-|]+\s*([-+]?\d+(?:[.,]\d+)?)$/);
                if (m) coords = [m[1], m[2]];
            }
            if (coords.length < 2) { fail(); return; }
            var parsedLat = parseFloat((coords[0] || '').replace(',', '.'));
            var parsedLng = parseFloat((coords[1] || '').replace(',', '.'));
            if (!isFinite(parsedLat) || !isFinite(parsedLng) || parsedLat < 30 || parsedLat > 45 || parsedLng < 20 || parsedLng > 50) {
                fail('⚠️ Geçersiz koordinat — Şanlıurfa bölgesi (33-40 K, 26-45 D) için girin. Farklı bir koordinat mı kopyaladınız?');
                return;
            }
            if (info) { info.textContent = ''; info.className = 'mb-2 text-xs text-slate-500'; }
            haritadaGoster(parsedLat, parsedLng, 'Özel Koordinat Konumu');
            if (info) { info.textContent = '📍 Konum haritada işaretlendi: ' + parsedLat.toFixed(6) + ', ' + parsedLng.toFixed(6); info.className = 'mb-2 text-xs text-emerald-700'; }
        });

        // ─── 15M ALT/ÜST YOL KONTROLÜ — local veri (maps-address.js) ────────
        document.getElementById('btn-road15')?.addEventListener('click', async function () {
            var spin = document.getElementById('road15-spinner');
            var result = document.getElementById('road15-result');
            var konum = window._sonKonum;
            if (!konum) { alert('Önce konum bulun.'); return; }
            if (spin) spin.classList.remove('hidden');
            if (result) { result.classList.add('hidden'); result.textContent = ''; }

            // Yerel cadde verisi hazır olmasını bekle (JSON + geometri)
            try { await window.aykomeVeriHazir(); } catch (e) {}
            if (typeof window.aykome15mKontrol !== 'function') {
                if (spin) spin.classList.add('hidden');
                if (result) { result.textContent = '⚠️ Yerel yol verisi yüklenmedi'; result.className = 'rounded-full px-3 py-1 text-[11px] font-bold bg-slate-100 text-slate-600'; result.classList.remove('hidden'); }
                return;
            }

            var hit = window.aykome15mKontrol(konum.lat, konum.lon);
            if (spin) spin.classList.add('hidden');
            if (!hit) {
                if (result) { result.textContent = '⚠️ Yakın yol bulunamadı'; result.className = 'rounded-full px-3 py-1 text-[11px] font-bold bg-slate-100 text-slate-600'; result.classList.remove('hidden'); }
                return;
            }
            // source: alti → 15m ALTINDA / ustu → 15m ÜSTÜ
            var ust = hit.source === 'ustu';
            var genislik = hit.genislik || '?';
            var yolAd = hit.cadde || '?';
            if (result) {
                result.textContent = ust
                    ? '🔴 15m ÜSTÜ (genişlik: ' + genislik + 'm) — ' + yolAd
                    : '🟢 15m ALTINDA (genişlik: ' + genislik + 'm) — ' + yolAd;
                result.className = 'rounded-full px-3 py-1 text-[11px] font-bold ' + (ust ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');
                result.classList.remove('hidden');
            }
        });

        function esc(v) { return String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        // ─── BOOT ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            initTcknLookup();
            initInstitutionWatcher();
            initDocumentUpload();
            initAutoDateAdder();

            // Add initial empty row
            addSurfaceLine({});

            var mapEngine = initMap();

            // Add Row button
            document.getElementById('add-row-btn')?.addEventListener('click', function () {
                addSurfaceLine({});
            });

            // ─── MAHALLE & SOKAK VERİ GİRİŞİ (üst yazı tablosu) ─────────────
            function initAddressComponents() {
                var hiddenInput = document.getElementById('address_components');
                var container = document.getElementById('address-components-container');
                var addBtn = document.getElementById('add-address-component-btn');
                if (!hiddenInput || !container) return;

                var components = [];
                try { components = JSON.parse(hiddenInput.value || '[]'); } catch(e) { components = []; }
                if (!Array.isArray(components)) components = [];

                function syncHidden() {
                    hiddenInput.value = JSON.stringify(components);
                }

                function render() {
                    container.innerHTML = '';
                    components.forEach(function (comp, idx) {
                        var streets = Array.isArray(comp.streets) ? comp.streets : [];
                        var wrapper = document.createElement('div');
                        wrapper.className = 'p-3 rounded-lg border border-slate-200 bg-white space-y-2';
                        wrapper.setAttribute('data-mahalle-idx', idx);

                        var header = document.createElement('div');
                        header.className = 'flex items-center gap-2';
                        header.innerHTML =
                            '<input type="text" class="comp-mahalle flex-1 rounded border-slate-300 text-xs shadow-sm" value="' + esc(comp.mahalle) + '" placeholder="Mahalle Adı (örn: 15 TEMMUZ MAHALLESİ)" data-idx="' + idx + '">' +
                            '<button type="button" class="mahalle-bul flex-shrink-0 rounded bg-cyan-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-cyan-700" data-idx="' + idx + '" title="Bu mahallenin cadde/sokaklarını getir">🔍 Bul</button>' +
                            '<button type="button" class="comp-remove flex-shrink-0 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600" data-idx="' + idx + '" title="Mahalleyi Kaldır">&times;</button>';
                        wrapper.appendChild(header);
                        // Cadde/sokak öneri kutusu (WMS mahalleCaddeler sonucu)
                        var oneri = document.createElement('div');
                        oneri.className = 'mahalle-cadde-oneri hidden mt-1 max-h-40 overflow-y-auto rounded-lg border border-cyan-200 bg-white text-[11px] shadow-sm';
                        oneri.setAttribute('data-idx', idx);
                        wrapper.appendChild(oneri);

                        var streetsContainer = document.createElement('div');
                        streetsContainer.className = 'ml-2 space-y-1';
                        streetsContainer.setAttribute('data-streets-container', idx);

                        streets.forEach(function (s, si) {
                            var sRow = document.createElement('div');
                            sRow.className = 'flex items-center gap-1';
                            sRow.innerHTML =
                                '<span class="text-[10px] text-slate-400 w-4">' + (si + 1) + '.</span>' +
                                '<input type="text" class="comp-street flex-1 rounded border-slate-200 text-[11px] shadow-sm" value="' + esc(s) + '" placeholder="Cadde/Sokak adı" data-idx="' + idx + '" data-street-idx="' + si + '" data-lat="" data-lon="">' +
                                '<button type="button" class="street-show flex-shrink-0 rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white hover:bg-emerald-700" data-idx="' + idx + '" data-street-idx="' + si + '" title="Haritada göster">📍</button>' +
                                '<button type="button" class="street-kontrol flex-shrink-0 rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-bold text-white hover:bg-indigo-700" data-idx="' + idx + '" data-street-idx="' + si + '" title="15m kontrolü ve tüm bilgiler">📋</button>' +
                                '<button type="button" class="street-remove flex-shrink-0 rounded p-0.5 text-red-300 hover:text-red-500" data-idx="' + idx + '" data-street-idx="' + si + '">&times;</button>';
                            streetsContainer.appendChild(sRow);
                            var sRes = document.createElement('div');
                            sRes.className = 'street-kontrol-sonuc ml-6 hidden text-[10px] leading-5';
                            sRes.setAttribute('data-idx', idx);
                            sRes.setAttribute('data-street-idx', si);
                            streetsContainer.appendChild(sRes);
                        });

                        wrapper.appendChild(streetsContainer);

                        var addStreetBtn = document.createElement('button');
                        addStreetBtn.type = 'button';
                        addStreetBtn.className = 'ml-2 text-[10px] font-medium text-cyan-700 hover:text-cyan-900 hover:underline';
                        addStreetBtn.setAttribute('data-add-street-idx', idx);
                        addStreetBtn.textContent = '+ Cadde / Sokak Ekle';
                        wrapper.appendChild(addStreetBtn);

                        container.appendChild(wrapper);
                    });

                    // Her cadde/sokak için otomatik zemin satırı üret (dedupe'lu)
                    components.forEach(function (comp) {
                        var mh = String(comp.mahalle || '').trim();
                        if (!mh) return;
                        (Array.isArray(comp.streets) ? comp.streets : []).forEach(function (s) {
                            if (s) ensureSurfaceLineForAddress(mh, s);
                        });
                    });
                    attachEvents();
                }

                function attachEvents() {
                    container.querySelectorAll('.comp-mahalle').forEach(function (el) {
                        el.addEventListener('input', function () {
                            var idx = parseInt(this.dataset.idx);
                            if (!isNaN(idx) && components[idx]) components[idx].mahalle = this.value;
                            syncHidden();
                            // MAHALLE AUTOCOMPLETE — önyüklü listeden client-side filtre
                            var wrapperEl = this.closest('[data-mahalle-idx]');
                            if (wrapperEl) {
                                var dd = wrapperEl.querySelector('.mahalle-dd');
                                if (!dd) {
                                    dd = document.createElement('div');
                                    dd.className = 'mahalle-dd hidden mt-1 max-h-40 overflow-y-auto rounded-lg border border-cyan-200 bg-white text-[11px] shadow-sm';
                                    wrapperEl.insertBefore(dd, wrapperEl.querySelector('.mahalle-cadde-oneri'));
                                }
                                var q = this.value.trim().toUpperCase();
                                var list = window._eyMahalleler || [];
                                var filt = q.length < 1 ? list : list.filter(function (m) {
                                    return (m.ad || '').toUpperCase().indexOf(q) !== -1;
                                });
                                dd.innerHTML = '';
                                if (!filt.length) { dd.classList.add('hidden'); return; }
                                filt.slice(0, 12).forEach(function (m) {
                                    var it = document.createElement('button');
                                    it.type = 'button';
                                    it.className = 'block w-full px-2 py-1 text-left hover:bg-cyan-50 truncate';
                                    it.textContent = m.ad;
                                    it.addEventListener('click', function () {
                                        var inp = wrapperEl.querySelector('.comp-mahalle');
                                        if (inp) inp.value = m.ad;
                                        if (components[idx]) components[idx].mahalle = m.ad;
                                        syncHidden();
                                        dd.classList.add('hidden');
                                        // Mahalle Bul'u otomatik tetikle — caddeleri getir
                                        var bul = wrapperEl.querySelector('.mahalle-bul');
                                        if (bul) bul.click();
                                    });
                                    dd.appendChild(it);
                                });
                                dd.classList.remove('hidden');
                            }
                        });
                        el.addEventListener('blur', function () {
                            var wrapperEl = this.closest('[data-mahalle-idx]');
                            if (wrapperEl) {
                                var dd = wrapperEl.querySelector('.mahalle-dd');
                                if (dd) setTimeout(function () { dd.classList.add('hidden'); }, 250);
                            }
                        });
                    });

                    container.querySelectorAll('.comp-street').forEach(function (el) {
                        el.addEventListener('input', function () {
                            var idx = parseInt(this.dataset.idx);
                            var si = parseInt(this.dataset.streetIdx);
                            if (!isNaN(idx) && !isNaN(si) && components[idx] && components[idx].streets) {
                                components[idx].streets[si] = this.value;
                            }
                            syncHidden();
                            // Autocomplete: cadde yazınca mahallenin cache'inden filtrele
                            var val = this.value.trim();
                            if (val.length >= 2) {
                                var wrapper = this.closest('[data-mahalle-idx]');
                                var oneri = wrapper ? wrapper.querySelector('.mahalle-cadde-oneri') : null;
                                if (oneri && window._mahalleCaddeler) {
                                    var filt = window._mahalleCaddeler.filter(function (c) {
                                        return c.name.toLowerCase().indexOf(val.toLowerCase()) !== -1;
                                    });
                                    oneri.innerHTML = '';
                                    filt.slice(0, 8).forEach(function (c) {
                                        var it = document.createElement('button');
                                        it.type = 'button';
                                        it.className = 'block w-full px-2 py-1 text-left hover:bg-cyan-50 truncate';
                                        it.textContent = c.name;
                                        it.addEventListener('click', function () {
                                            var input = this.closest('[data-mahalle-idx]').querySelector('.comp-street[data-street-idx="' + si + '"]');
                                            if (input) {
                                                input.value = c.name;
                                                input.dataset.lat = c.lat;
                                                input.dataset.lon = c.lon;
                                                if (components[idx]) components[idx].streets[si] = c.name;
                                                syncHidden();
                                                // Zemin satırına da otomatik ekle — "render()" içindeki
                                                // ensureSurfaceLineForAddress döngüsüyle aynı mantık
                                                var mhInput = this.closest('[data-mahalle-idx]').querySelector('.comp-mahalle');
                                                if (mhInput) ensureSurfaceLineForAddress(mhInput.value, c.name);
                                            }
                                            oneri.classList.add('hidden');
                                        });
                                        oneri.appendChild(it);
                                    });
                                    oneri.classList.remove('hidden');
                                }
                            }
                        });
                    });

                    // Mahalle Bul — WMS mahalleCaddeler (S2S + cache)
                    // ─── MAHALLE BUL → CADDE LİSTVİEW + SEARCH ──────────────
                    container.querySelectorAll('.mahalle-bul').forEach(function (el) {
                        el.addEventListener('click', async function () {
                            var idx = parseInt(this.dataset.idx);
                            var mahalleInput = container.querySelector('.comp-mahalle[data-idx="' + idx + '"]');
                            var oneri = container.querySelector('.mahalle-cadde-oneri[data-idx="' + idx + '"]');
                            if (!mahalleInput) return;
                            var mh = mahalleInput.value.trim();
                            if (mh.length < 2) { alert('Önce mahalle adı yazın.'); return; }

                            // LISTVIEW: arama inputu + scroll'lu sonuç listesi
                            function renderCaddeListesi() {
                                if (!oneri) return;
                                var q = '';
                                var aramaInput = oneri.querySelector('.cadde-liste-ara');
                                if (aramaInput) q = aramaInput.value.trim().toLowerCase();
                                var liste = oneri.querySelector('.cadde-liste-icerik');
                                if (!liste) return;
                                liste.innerHTML = '';
                                var filt = window._mahalleCaddeler || [];
                                if (q) filt = filt.filter(function (c) { return (c.name || '').toLowerCase().indexOf(q) !== -1; });
                                if (!filt.length) { liste.innerHTML = '<div style="padding:8px;color:#94a3b8;text-align:center">Cadde bulunamadı.</div>'; return; }
                                filt.slice(0, 60).forEach(function (c) {
                                    var satir = document.createElement('div');
                                    satir.className = 'flex items-center gap-1 px-2 py-1 hover:bg-cyan-50 cursor-pointer border-b border-slate-50';
                                    satir.innerHTML = '<span class="flex-1 truncate">' + (c.name || '') + '</span>' +
                                        '<button type="button" class="cadde-liste-sec flex-shrink-0 rounded bg-cyan-600 px-1.5 py-0.5 text-[10px] font-bold text-white hover:bg-cyan-700" title="Ekle">+ Ekle</button>' +
                                        '<button type="button" class="cadde-liste-goster flex-shrink-0 rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white hover:bg-emerald-700" title="Haritada göster">📍</button>';
                                    satir.querySelector('.cadde-liste-sec').addEventListener('click', function () {
                                        if (!components[idx]) return;
                                        components[idx].streets.push(c.name);
                                        syncHidden();
                                        render();
                                    });
                                    satir.querySelector('.cadde-liste-goster').addEventListener('click', function () {
                                        if (c.lat && c.lon) haritadaGoster(parseFloat(c.lat), parseFloat(c.lon), c.name);
                                    });
                                    liste.appendChild(satir);
                                });
                            }

                            if (oneri) {
                                oneri.innerHTML =
                                    '<div style="padding:6px;border-bottom:1px solid #e2e8f0;">' +
                                        '<input type="text" class="cadde-liste-ara w-full rounded border border-cyan-300 px-2 py-1 text-[11px]" placeholder="Cadde/sokak ara (bu mahallede)..." style="outline:none;">' +
                                    '</div>' +
                                    '<div class="cadde-liste-icerik" style="max-height:220px;overflow-y:auto;"></div>';
                                oneri.classList.remove('hidden');
                                oneri.querySelector('.cadde-liste-ara').addEventListener('input', renderCaddeListesi);
                                oneri.querySelector('.cadde-liste-ara').addEventListener('keydown', function (e) {
                                    if (e.key === 'Enter') { e.preventDefault(); renderCaddeListesi(); }
                                });
                                oneri.querySelector('.cadde-liste-ara').focus();
                            }
                            try {
                                // ── Yerel veri hazır olsun (JSON + geometri) ──
                                await window.aykomeVeriHazir();
                                window._mahalleCaddeler = [];
                                var mahalleBbox = (window._eyMahalleler || []).find(function (m) {
                                    return (m.ad || '').toUpperCase() === (mh || '').toUpperCase();
                                });
                                if (mahalleBbox && mahalleBbox.bbox && typeof window.aykomeCaddelerInBbox === 'function') {
                                    window._mahalleCaddeler = window.aykomeCaddelerInBbox(mahalleBbox);
                                }
                                if (!window._mahalleCaddeler.length && typeof window.aykomeCaddelerInBbox === 'function') {
                                    // İlçe geniş bbox fallback (Eyyübiye)
                                    window._mahalleCaddeler = window.aykomeCaddelerInBbox({ minLng: 38.60, minLat: 36.90, maxLng: 39.00, maxLat: 37.30 });
                                }
                                // ── YEDEK: WFS proxy ──
                                if (!window._mahalleCaddeler.length) {
                                    var resp = await fetch(@json(route('maps.mahalle-caddeler')) + '?mahalle=' + encodeURIComponent(mh));
                                    var data = await resp.json();
                                    window._mahalleCaddeler = (data && data.caddeler) ? data.caddeler : [];
                                }
                                renderCaddeListesi();
                            } catch (e) {
                                console.error('Mahalle cadde hatası:', e);
                                if (oneri) oneri.classList.add('hidden');
                            }
                        });
                    });

                    // Her cadde için "Göster" — WMS'te nokta atışı
                    container.querySelectorAll('.street-show').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var sRow = this.closest('.flex');
                            var input = sRow ? sRow.querySelector('.comp-street') : null;
                            if (!input) return;
                            var lat = parseFloat(input.dataset.lat);
                            var lon = parseFloat(input.dataset.lon);
                            var name = input.value.trim();
                            if (!name) { alert('Cadde/sokak adı girin.'); return; }
                            if (!isNaN(lat) && !isNaN(lon)) {
                                haritadaGoster(lat, lon, name);
                                return;
                            }
                            // WMS'ten tek cadde ara
                            fetch(@json(route('maps.adres-ara')) + '?q=' + encodeURIComponent(name))
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    if (d && d.success && d.lat) {
                                        input.dataset.lat = d.lat; input.dataset.lon = d.lon;
                                        haritadaGoster(parseFloat(d.lat), parseFloat(d.lon), d.cadde || name);
                                    } else {
                                        alert('Cadde sistemde bulunamadı.');
                                    }
                                })
                                .catch(function () { alert('Sorgu hatası.'); });
                        });
                    });

                    container.querySelectorAll('.street-remove').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var idx = parseInt(this.dataset.idx);
                            var si = parseInt(this.dataset.streetIdx);
                            if (!isNaN(idx) && !isNaN(si) && components[idx] && components[idx].streets) {
                                components[idx].streets.splice(si, 1);
                                syncHidden();
                                render();
                            }
                        });
                    });

                    container.querySelectorAll('.street-kontrol').forEach(function (el) {
                        el.addEventListener('click', async function () {
                            var idx = parseInt(this.dataset.idx);
                            var si = parseInt(this.dataset.streetIdx);
                            if (isNaN(idx) || isNaN(si) || !components[idx] || !components[idx].streets || !components[idx].streets[si]) return;
                            var streetName = (components[idx].streets[si] || '').trim();
                            var mahalle = (components[idx].mahalle || '').trim();
                            var resEl = container.querySelector('.street-kontrol-sonuc[data-idx="' + idx + '"][data-street-idx="' + si + '"]');
                            if (!streetName) {
                                if (resEl) { resEl.classList.remove('hidden'); resEl.textContent = 'Önce cadde/sokak adı girin.'; }
                                return;
                            }
                            try { await window.aykomeVeriHazir(); } catch (e) {}
                            var d = (typeof window.aykomeSokakDetay === 'function') ? window.aykomeSokakDetay(mahalle, streetName) : null;
                            if (!resEl) return;
                            resEl.classList.remove('hidden');
                            resEl.innerHTML = streetDetayHtml(d, mahalle, streetName).html;
                        });
                    });

                    container.querySelectorAll('[data-add-street-idx]').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var idx = parseInt(this.dataset.addStreetIdx);
                            if (!isNaN(idx) && components[idx]) {
                                components[idx].streets.push('');
                                syncHidden();
                                render();
                            }
                        });
                    });

                    container.querySelectorAll('.comp-remove').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var idx = parseInt(this.dataset.idx);
                            if (!isNaN(idx) && components[idx]) {
                                components.splice(idx, 1);
                                syncHidden();
                                render();
                            }
                        });
                    });
                }

                addBtn?.addEventListener('click', function () {
                    components.push({ mahalle: '', streets: [] });
                    syncHidden();
                    render();
                });

                render();
            }

            // WMS MAHALLE ÖN YÜKLEME — cascading autocomplete için (Opus mantığı)
            window._eyMahalleler = [];
            try {
                fetch(@json(route('maps.mahalleler')))
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d && d.success) window._eyMahalleler = d.data || [];
                    })
                    .catch(function () { /* sessiz */ });
            } catch (e) { /* sessiz */ }

            initAddressComponents();

            // ─── CADDE KONTROL SONUCU RENDER (tek satır + toplu için ortak) ──
            function streetDetayHtml(d, mahalle, query) {
                if (!d) {
                    return {
                        html: '<span class="inline-block rounded bg-red-100 px-2 py-0.5 font-bold text-red-700">⚠️ Bulunamadı</span> ' +
                              '<span class="text-slate-500">' + esc(mahalle + (mahalle ? ' / ' : '') + query) + ' eşleşen kayıt bulunamadı.</span>',
                        sorumluluk: null
                    };
                }
                var ust = (d.sorumluluk || '').indexOf('ÜSTÜ') !== -1;
                var badge = '<span class="inline-block rounded-full px-2 py-0.5 font-bold ' +
                    (ust ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700') + '">' +
                    esc(d.sorumluluk || '') + '</span>';
                var cols = [
                    ['Türü', d.turu], ['Genişlik', d.genislik ? d.genislik + ' m' : '—'],
                    ['Uzunluk', d.uzunluk ? d.uzunluk + ' m' : '—'], ['Şerit', d.serit || '—'],
                    ['Trafik Yönü', d.trafik_yolu || '—'], ['Kaplama', d.kaplama || '—'],
                    ['An Arter', d.arter || '—'], ['UAVT Tür', d.uavt_turu || '—']
                ];
                var info = cols.map(function (c) {
                    return '<span class="whitespace-nowrap"><b>' + c[0] + ':</b> ' + esc(c[1] == null ? '—' : String(c[1])) + '</span>';
                }).join(' &nbsp;·&nbsp; ');
                var caddeNo = (d.cadde_soka == null ? '' : d.cadde_soka);
                return {
                    html: badge +
                        '<div class="mt-0.5 text-slate-600">' + info + '</div>' +
                        '<div class="text-[10px] text-slate-400">' + esc(d.mahalle || '') + ' · ' + esc(d.cadde_adi || '') + ' · Sokak No: ' + esc(caddeNo) + '</div>',
                    sorumluluk: d.sorumluluk
                };
            }

            // ─── TOPLU KONTROL ET ────────────────────────────────
            var topluBtn = document.getElementById('toplu-kontrol-btn');
            var topluSonuc = document.getElementById('toplu-kontrol-sonuc');
            var topluSpin = document.getElementById('toplu-kontrol-spinner');
            if (topluBtn) topluBtn.addEventListener('click', async function () {
                var hiddenInput = document.getElementById('address_components');
                if (!hiddenInput) return;
                var comps = [];
                try { comps = JSON.parse(hiddenInput.value || '[]'); } catch (e) { comps = []; }
                if (!Array.isArray(comps)) comps = [];

                var toplamCadd = comps.reduce(function (n, c) { return n + (Array.isArray(c.streets) ? c.streets.length : 0); }, 0);
                if (toplamCadd === 0) {
                    topluSonuc.classList.remove('hidden');
                    topluSonuc.innerHTML = '<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">Önce en az bir mahalle ve cadde/sokak ekleyin.</div>';
                    return;
                }

                topluBtn.disabled = true;
                if (topluSpin) topluSpin.classList.remove('hidden');
                if (topluSonuc) topluSonuc.classList.add('hidden');
                try { await window.aykomeVeriHazir(); } catch (e) {}

                var satirlar = [];
                var sayac = { alt: 0, ust: 0, yok: 0 };
                comps.forEach(function (c, ci) {
                    var mah = (c.mahalle || '').trim();
                    (c.streets || []).forEach(function (s, si) {
                        var ad = (s || '').trim();
                        if (!ad) return;
                        var d = (typeof window.aykomeSokakDetay === 'function') ? window.aykomeSokakDetay(mah, ad) : null;
                        if (!d) { sayac.yok++; } else if ((d.sorumluluk || '').indexOf('ÜSTÜ') !== -1) { sayac.ust++; } else { sayac.alt++; }
                        satirlar.push({ m: mah, mIdx: ci, si: si, ad: ad, d: d });
                    });
                });

                var ozet = '<div class="mb-2 flex flex-wrap items-center gap-2">' +
                    '<span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700">🔵 15 Metre Altı: ' + sayac.alt + '</span>' +
                    '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">🟡 15 Metre Üstü: ' + sayac.ust + '</span>' +
                    '<span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-700">❌ Bulunamadı: ' + sayac.yok + '</span>' +
                    '<span class="text-[11px] text-slate-400">Toplam ' + toplamCadd + ' cadde/sokak</span>' +
                    '</div>';

                var html = ozet;
                // Mahalle grubu başına tablo
                var gruplar = {};
                satirlar.forEach(function (r) {
                    if (!gruplar[r.m]) gruplar[r.m] = [];
                    gruplar[r.m].push(r);
                });
                Object.keys(gruplar).forEach(function (mmm) {
                    var grp = gruplar[mmm];
                    html += '<div class="mb-3 rounded-lg border border-slate-200 bg-white"><div class="rounded-t-lg bg-slate-50 px-3 py-1.5 text-[11px] font-bold text-slate-700 border-b border-slate-200">📌 ' + esc(mmm || '—') + ' (' + grp.length + ' cadence)</div><table class="w-full text-[11px]"><tbody>';
                    grp.forEach(function (r) {
                        html += '<tr class="border-b border-slate-50 align-top">' +
                            '<td class="px-3 py-1.5 font-medium whitespace-nowrap">' + esc(r.ad) + '</td>' +
                            '<td class="px-3 py-1.5">' + streetDetayHtml(r.d, r.m, r.ad).html.replace('<div class="mt-0.5 text-slate-600">', '<div class="flex flex-wrap gap-x-3 gap-y-0.5 text-slate-600">') + '</td>' +
                            '</tr>';
                    });
                    html += '</tbody></table></div>';
                });

                topluSonuc.innerHTML = html;
                topluSonuc.classList.remove('hidden');
                topluBtn.disabled = false;
                if (topluSpin) topluSpin.classList.add('hidden');
            });

            // Submit hook
            document.getElementById('application-form')?.addEventListener('submit', function (e) {
                prepareSurfaceLinesForSubmit();

                // ALT KURUM KURALI: adres girildiyse haritada çizim zorunludur
                if (isInstitutionUser) {
                    var acRaw = (document.getElementById('address_components') || {}).value || '';
                    var acParsed = [];
                    try { acParsed = JSON.parse(acRaw || '[]'); } catch (_e) {}
                    var adresVarMi = (Array.isArray(acParsed) && acParsed.some(function (c) { return c && c.streets && c.streets.length; }))
                        || ((document.getElementById('address_text') || {}).value || '').trim().length > 0;
                    var geoVal = ((document.getElementById('polygon_geojson') || {}).value || '').trim();
                    var cizimVarMi = Object.keys(rowDrawings).length > 0 || geoVal.length > 0;
                    if (adresVarMi && !cizimVarMi) {
                        e.preventDefault();
                        alert('Adres girdiğiniz için haritada kazı alanı çizmeniz ZORUNLUDUR.\nZemin satırındaki 🎯 Çiz butonuna basıp çizim yapın (düz çizgi de kabul edilir).');
                    }
                }
            });
        });
    </script>
@endpush
