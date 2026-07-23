@extends('layouts.admin')

@section('page-heading', 'Yeni başvuru')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <style>
        #application-drawing-map { min-height: 500px; position: relative; z-index: 1; }
        #application-drawing-map .leaflet-container { border-radius: 0.75rem; }
        .leaflet-pane { z-index: 10 !important; }
        .leaflet-top, .leaflet-bottom { z-index: 99 !important; }
        .row-tooltip { background: #1e293b !important; color: #fff !important; border: none !important; border-radius: 4px !important; padding: 2px 8px !important; font-size: 11px !important; font-weight: 600 !important; box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important; }
        .row-tooltip::before { border-top-color: #1e293b !important; }
        .leaflet-draw-toolbar a {
            background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.png') !important;
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

    <form method="POST" action="{{ route('admin.applications.store') }}" enctype="multipart/form-data" class="space-y-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        @if($institutions->count() > 1)
            <div>
                <label class="block text-sm font-medium text-slate-700" for="institution_id">Kurum</label>
                <select
                    id="institution_id"
                    name="institution_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm @error('institution_id') border-red-300 ring-red-100 @enderror"
                >
                    <option value="">—</option>
                    @foreach($institutions as $i)
                        <option value="{{ $i->id }}" data-tax="{{ $i->tax_number }}" @selected((string) old('institution_id') === (string) $i->id)>{{ $i->name }}</option>
                    @endforeach
                </select>
                @error('institution_id')
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
                            <label class="block text-sm font-medium text-slate-700">Ad</label>
                            <input type="text" value="{{ $applicantPrefill['first_name'] }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 text-slate-500 shadow-sm">
                            <input type="hidden" name="applicant_first_name" value="{{ $applicantPrefill['first_name'] }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Soyad</label>
                            <input type="text" value="{{ $applicantPrefill['last_name'] }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 text-slate-500 shadow-sm">
                            <input type="hidden" name="applicant_last_name" value="{{ $applicantPrefill['last_name'] }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">TC Kimlik / Vergi No</label>
                            <div class="mt-1 flex items-center gap-2">
                                @if($institutionPrefill)
                                    <input type="text" value="{{ $institutionPrefill['tax_number'] }}" readonly
                                        class="block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 font-mono text-sm tracking-widest text-slate-500 shadow-sm">
                                    <input type="hidden" name="applicant_national_id" value="{{ $institutionPrefill['tax_number'] }}">
                                    <input type="hidden" id="tc_no" name="tc_no" value="{{ $institutionPrefill['tax_number'] }}">
                                    <input type="hidden" id="identity_no" name="identity_no" value="{{ $institutionPrefill['tax_number'] }}">
                                @else
                                    <input type="text" value="{{ $applicantPrefill['national_id_masked'] }}" readonly
                                        class="block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 font-mono text-sm tracking-widest text-slate-500 shadow-sm">
                                    <input type="hidden" name="applicant_national_id" value="{{ $applicantPrefill['national_id'] }}">
                                    <input type="hidden" id="tc_no" name="tc_no" value="{{ $applicantPrefill['national_id'] }}">
                                    <input type="hidden" id="identity_no" name="identity_no" value="{{ $applicantPrefill['national_id'] }}">
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
                            <label class="block text-sm font-medium text-slate-700" for="applicant_first_name">Ad</label>
                            <input id="applicant_first_name" type="text" name="applicant_first_name" value="{{ old('applicant_first_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('applicant_first_name') border-red-300 ring-red-100 @enderror">
                            @error('applicant_first_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="applicant_last_name">Soyad</label>
                            <input id="applicant_last_name" type="text" name="applicant_last_name" value="{{ old('applicant_last_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('applicant_last_name') border-red-300 ring-red-100 @enderror">
                            @error('applicant_last_name')
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
                            <input type="hidden" id="tc_no" name="tc_no" value="{{ old('tc_no', old('applicant_national_id')) }}">
                            <input type="hidden" id="identity_no" name="identity_no" value="{{ old('identity_no', old('applicant_national_id')) }}">
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
                        <label class="block text-sm font-medium text-slate-700" for="work_type">Çalışma türü</label>
                        <input id="work_type" type="text" name="work_type" value="{{ old('work_type') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('work_type') border-red-300 ring-red-100 @enderror">
                        @error('work_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700" for="address_text">Adres</label>
                        <input id="address_text" type="text" name="address_text" value="{{ old('address_text') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('address_text') border-red-300 ring-red-100 @enderror">
                        @error('address_text')
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
                <input id="map-search-input" type="text"
                    class="absolute top-4 right-12 z-10 w-72 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm shadow-[0_4px_20px_rgba(250,96,1,0.22)] focus:outline-none focus:ring-2 focus:ring-[#02E0FB] focus:border-[#02E0FB] placeholder:text-slate-400"
                    placeholder="Sokak, bina veya bölge ara...">
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
            ])
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
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

                tr.innerHTML =
                    '<td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">' + (idx + 1) + '</td>' +
                    '<td class="p-2 align-top pt-2"><select data-row-id="' + row.rowId + '" class="surface-type-select block w-full rounded border-slate-300 text-xs shadow-sm"><option value="">—</option>' +
                        SURFACE_TYPES.map(function (st) {
                            return '<option value="' + st.id + '" data-price="' + st.price_per_m2 + '"' + (parseInt(st.id) === parseInt(row.surface_type_id) ? ' selected' : '') + '>' + st.name + ' - ' + Number(st.price_per_m2).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' \u20BA</option>';
                        }).join('') +
                    '</select></td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-width w-full rounded border-slate-300 text-xs shadow-sm" value="' + (row.width_m || '') + '" placeholder="0"></td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-length w-full rounded border-slate-300 text-xs shadow-sm" value="' + (row.length_m || '') + '" placeholder="0"></td>' +
                    '<td class="p-2 align-top"><input type="text" inputmode="decimal" data-row-id="' + row.rowId + '" class="row-quantity w-full rounded border-slate-300 text-xs shadow-sm font-semibold" value="' + (qty || '') + '" placeholder="0"></td>' +
                    '<td class="p-2 align-top pt-3 text-xs text-slate-600 font-mono"><span class="row-unit-price" data-row-id="' + row.rowId + '">' + Number(unitPrice).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span> ₺/m²</td>' +
                    '<td class="p-2 align-top"><button type="button" data-row-id="' + row.rowId + '" class="row-draw-btn rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-medium text-slate-600 hover:bg-slate-50 transition ' + (activeDrawRowId === row.rowId ? 'ring-2 ring-amber-400 bg-amber-50' : '') + '">' + (hasDrawing ? '🔄 Çiz' : '🎯 Çiz') + '</button></td>' +
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
                        if (w > 0 && l <= 0) {
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
            };
            surfaceLines.push(row);
            renderTable();
            return row;
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

            surfaceLines.forEach(function (row, idx) {
                function addHidden(name, value) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'surface_lines[' + idx + '][' + name + ']';
                    inp.value = value != null ? value : '';
                    container.appendChild(inp);
                }
                addHidden('surface_type_id', row.surface_type_id || '');
                addHidden('width_m', row.width_m || '');
                addHidden('length_m', row.length_m || '');
                addHidden('quantity', row.quantity || '');
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
            const lastNameInput = document.getElementById('applicant_last_name');
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
                        if (firstNameInput && p.data.applicant_first_name) firstNameInput.value = p.data.applicant_first_name;
                        if (lastNameInput && p.data.applicant_last_name) lastNameInput.value = p.data.applicant_last_name;
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
            var defaultCenter = Number.isFinite(initLat) && Number.isFinite(initLng) ? [initLat, initLng] : [39.0, 35.0];

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
                zoomControl: true,
            });

            var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
            });
            var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19, attribution: '&copy; <a href="https://esri.com">Esri</a>',
            });
            var terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 17, attribution: '&copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
            });
            osm.addTo(map);
            L.control.layers({ Standart: osm, Uydu: satellite, Arazi: terrain }, null, { position: 'topleft' }).addTo(map);

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

            var drawControl = new L.Control.Draw({
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
            map.addControl(drawControl);

            var repaintOverlays = function () {
                drawnItems.eachLayer(function (layer) {
                    if (layer.setStyle) layer.setStyle({ color: strokeColor, fillColor: strokeColor });
                });
            };

            // My Location
            var myLocationMarker = null;
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
                        if (!navigator.geolocation) { if (statusEl) statusEl.textContent = 'Konum servisi desteklenmiyor.'; return; }
                        if (statusEl) statusEl.textContent = 'Konum alınıyor...';
                        navigator.geolocation.getCurrentPosition(
                            function (pos) {
                                var p = [pos.coords.latitude, pos.coords.longitude];
                                map.setView(p, 17);
                                if (myLocationMarker) map.removeLayer(myLocationMarker);
                                myLocationMarker = L.marker(p).addTo(map);
                                myLocationMarker.bindPopup('📍 Konumum');
                                if (statusEl) statusEl.textContent = 'Konum işaretlendi.';
                            },
                            function () { if (statusEl) statusEl.textContent = 'Konum alınamadı.'; },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
                        );
                    });
                    return btn;
                },
            });
            map.addControl(new MyLocationControl({ position: 'topright' }));

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
                if (layer instanceof L.Polygon && !(layer instanceof L.Rectangle) && !(layer instanceof L.Circle)) {
                    area = polyArea(layer.getLatLngs()[0]);
                } else if (layer instanceof L.Rectangle) {
                    area = rectArea(layer.getBounds());
                } else if (layer instanceof L.Circle) {
                    area = Math.PI * layer.getRadius() * layer.getRadius();
                }
                area = parseFloat(area.toFixed(2));

                var capturedRowId = activeDrawRowId;

                if (capturedRowId && area > 0) {
                    var row = surfaceLines.find(function (r) { return r.rowId === capturedRowId; });
                    if (row) {
                        row.quantity = area;
                        var sqrtVal = parseFloat(Math.sqrt(area).toFixed(2));
                        row.width_m = sqrtVal;
                        row.length_m = sqrtVal;

                        var feature = null;
                        if (layer instanceof L.Polygon) {
                            var coords = (layer.getLatLngs()[0] || []).map(function (p) { return [p.lng, p.lat]; });
                            if (coords.length) {
                                coords.push([coords[0][0], coords[0][1]]);
                                feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { rowId: capturedRowId, shape: 'polygon' } };
                            }
                        } else if (layer instanceof L.Circle) {
                            var cc = layer.getLatLng(), rr = layer.getRadius(), cnt = 64, ccoords = [];
                            for (var ci = 0; ci < cnt; ci++) {
                                var ca = (ci / cnt) * 2 * Math.PI;
                                ccoords.push([cc.lng + (rr / (111320 * Math.cos(cc.lat * Math.PI / 180))) * Math.sin(ca), cc.lat + (rr / 111320) * Math.cos(ca)]);
                            }
                            ccoords.push(ccoords[0]);
                            feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [ccoords] }, properties: { rowId: capturedRowId, shape: 'circle' } };
                        }
                        if (feature) rowDrawings[capturedRowId] = feature;

                        layer.bindTooltip('Sat\u0131r: #' + capturedRowId, { permanent: true, direction: 'top', offset: [0, -10], className: 'row-tooltip' });

                        activeDrawRowId = null;
                        updateActiveDrawIndicator();
                        renderTable();
                        serializeAndSync('Çizim sat\u0131r #' + capturedRowId + ' için kaydedildi.');
                        return;
                    }
                }

                if (capturedRowId) {
                    layer.bindTooltip('Sat\u0131r: #' + capturedRowId, { permanent: true, direction: 'top', offset: [0, -10], className: 'row-tooltip' });
                }

                serializeAndSync('Çizim haritaya işlendi.');
            });

            map.on(L.Draw.Event.EDITED, function () { serializeAndSync('Çizim güncellendi.'); });
            map.on(L.Draw.Event.DELETED, function () { serializeAndSync('Çizim silindi.'); });

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
                if (!bounds.isEmpty()) map.fitBounds(bounds);
                serializeAndSync('GeoJSON haritaya uygulandı.');
                renderTable();
            });

            // Institution color change
            institutionSelect?.addEventListener('change', function () {
                strokeColor = getDrawColor();
                updateColorPreview();
                repaintOverlays();
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

        // ─── INSTITUTION → DICLE + TEMINAT ───────────────────────────────
        function initInstitutionWatcher() {
            var sel = document.getElementById('institution_id');
            if (!sel) return;

            function checkDicle() {
                var opt = sel.options[sel.selectedIndex];
                isDicleElektrik = opt && opt.dataset.tax === '2950368442';
                isInstitutionUser = opt && opt.value !== '';
                recalculateAll();
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

        // ─── BOOT ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            initTcknLookup();
            initInstitutionWatcher();
            initDocumentUpload();
            initAutoDateAdder();

            // Add initial empty row
            addSurfaceLine({});

            var mapEngine = initMap();

            // Connect search to map
            if (mapEngine) {
                var si = document.getElementById('map-search-input');
                if (si) {
                    si.addEventListener('keydown', async function (e) {
                        if (e.key !== 'Enter') return;
                        e.preventDefault();
                        var q = si.value.trim();
                        if (!q) return;
                        var statusEl = document.getElementById('map-status');
                        if (statusEl) statusEl.textContent = 'Adres aranıyor...';
                        try {
                            var res = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=1&countrycodes=tr');
                            var data = await res.json();
                            if (data.length) {
                                mapEngine.map.setView([data[0].lat, data[0].lon], 18);
                                var addr = document.getElementById('address_text');
                                if (addr) addr.value = data[0].display_name;
                                if (statusEl) statusEl.textContent = 'Adres bulundu.';
                            } else if (statusEl) {
                                statusEl.textContent = 'Adres bulunamadı.';
                            }
                        } catch (e) {
                            if (statusEl) statusEl.textContent = 'Arama hatası.';
                        }
                    });
                }
            }

            // Add Row button
            document.getElementById('add-row-btn')?.addEventListener('click', function () {
                addSurfaceLine({});
            });

            // Submit hook
            document.querySelector('form')?.addEventListener('submit', function () {
                prepareSurfaceLinesForSubmit();
            });
        });
    </script>
@endpush
