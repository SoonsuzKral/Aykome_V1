@extends('layouts.admin')

@section('page-heading', 'Yeni başvuru')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <style>
        #application-drawing-map { min-height: 500px; position: relative; z-index: 1; }
        #application-drawing-map .leaflet-container { border-radius: 0.75rem; }
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
                        <option value="{{ $i->id }}" @selected((string) old('institution_id') === (string) $i->id)>{{ $i->name }}</option>
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
                                <input type="text" value="{{ $applicantPrefill['national_id_masked'] }}" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 font-mono text-sm tracking-widest text-slate-500 shadow-sm">
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2 py-2 text-xs font-semibold text-amber-700">
                                    🔒 Kilitli
                                </span>
                            </div>
                            <input type="hidden" name="applicant_national_id" value="{{ $applicantPrefill['national_id'] }}">
                            <input type="hidden" id="tc_no" name="tc_no" value="{{ $applicantPrefill['national_id'] }}">
                            <input type="hidden" id="identity_no" name="identity_no" value="{{ $applicantPrefill['national_id'] }}">
                            <p class="mt-1 text-xs text-slate-500">Kimlik bilgisi oturumdaki hesaba bağlıdır; değiştirilemez.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telefon</label>
                            <input type="text" value="{{ $applicantPrefill['phone'] }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 text-slate-500 shadow-sm">
                            <input type="hidden" name="applicant_phone" value="{{ $applicantPrefill['phone'] }}">
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
                </fieldset>

                <fieldset class="grid gap-4 sm:grid-cols-2">
                    <legend class="col-span-full text-sm font-semibold text-slate-800">Kazı</legend>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700" for="excavation_reason">İşin Adı : </label>
                        <input id="excavation_reason" type="text" name="excavation_reason" value="{{ old('excavation_reason') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('excavation_reason') border-red-300 ring-red-100 @enderror">
                        @error('excavation_reason')
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
                        <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('end_date') border-red-300 ring-red-100 @enderror">
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
                <button type="button" id="surface-calc-btn" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Yüzey tipine göre hesapla</button>
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

        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend class="col-span-full text-sm font-semibold text-slate-800">Yüzey &amp; keşif</legend>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700" for="surface_type_id">Yüzey tipi</label>
                <select id="surface_type_id" name="surface_type_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('surface_type_id') border-red-300 ring-red-100 @enderror">
                    <option value="">—</option>
                    @foreach($surfaceTypes as $s)
                        <option value="{{ $s->id }}" data-price="{{ $s->price_per_m2 }}" @selected((string) old('surface_type_id') === (string) $s->id)>{{ $s->name }} — {{ $s->price_per_m2 }} TL/m²</option>
                    @endforeach
                </select>
                @error('surface_type_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="width_m">Genişlik (m)</label>
                <input id="width_m" type="number" step="any" name="width_m" value="{{ old('width_m') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('width_m') border-red-300 ring-red-100 @enderror">
                @error('width_m')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="length_m">Uzunluk (m)</label>
                <input id="length_m" type="number" step="any" name="length_m" value="{{ old('length_m') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('length_m') border-red-300 ring-red-100 @enderror">
                @error('length_m')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="quantity">Miktar</label>
                <input id="quantity" type="number" step="any" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('quantity') border-red-300 ring-red-100 @enderror">
                @error('quantity')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="multiplier">Kat / çarpan</label>
                <input id="multiplier" type="number" step="any" name="multiplier" value="{{ old('multiplier', 1) }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm @error('multiplier') border-red-300 ring-red-100 @enderror">
                @error('multiplier')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <input type="hidden" id="calculated_amount" name="calculated_amount" value="{{ old('calculated_amount') }}">
            </div>
        </fieldset>

        <fieldset class="grid gap-4 sm:grid-cols-2 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <legend class="col-span-full text-sm font-semibold text-slate-800">Teminat &amp; Belgeler</legend>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="deposit_amount">Teminat Bedeli (TL)</label>
                <div class="relative mt-1">
                    <input id="deposit_amount" type="text" inputmode="decimal" name="deposit_amount" value="{{ old('deposit_amount') }}" placeholder="0.00" class="block w-full rounded-lg border-slate-300 pr-10 shadow-sm @error('deposit_amount') border-red-300 ring-red-100 @enderror">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-slate-500">₺</span>
                </div>
                @error('deposit_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="excavation_amount">Kazı Bedeli (TL)</label>
                <div class="relative mt-1">
                    <input id="excavation_amount" type="text" inputmode="decimal" name="excavation_amount" value="{{ old('excavation_amount') }}" placeholder="0.00" class="block w-full rounded-lg border-slate-300 pr-10 shadow-sm @error('excavation_amount') border-red-300 ring-red-100 @enderror">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-slate-500">₺</span>
                </div>
                @error('excavation_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
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
            (() => {
                const mapEl = document.getElementById('application-drawing-map');
                const geojsonInput = document.getElementById('polygon_geojson');
                const areaInput = document.getElementById('total_area_m2');
                const centerLatInput = document.getElementById('center_lat');
                const centerLngInput = document.getElementById('center_lng');
                const centerDisplay = document.getElementById('center-display');
                const clearBtn = document.getElementById('map-clear-btn');
                const applyGeojsonBtn = document.getElementById('map-apply-geojson-btn');
                const surfaceCalcBtn = document.getElementById('surface-calc-btn');
                const statusEl = document.getElementById('map-status');
                const institutionSelect = document.getElementById('institution_id');
                const activeColorDot = document.getElementById('active-draw-color-dot');
                const lineLengthDisplay = document.getElementById('line-length-display');
                const surfaceTotalDisplay = document.getElementById('surface-total-display');
                const calculatedAmountInput = document.getElementById('calculated_amount');
                const surfaceTypeSelect = document.getElementById('surface_type_id');
                const widthInput = document.getElementById('width_m');
                const lengthInput = document.getElementById('length_m');
                const quantityInput = document.getElementById('quantity');
                const multiplierInput = document.getElementById('multiplier');
                const tcknInput = document.getElementById('applicant_national_id');
                const tcknCheckBtn = document.getElementById('tckn-check-btn');
                const tcknCheckStatus = document.getElementById('tckn-check-status');
                const firstNameInput = document.getElementById('applicant_first_name');
                const lastNameInput = document.getElementById('applicant_last_name');
                const phoneInput = document.getElementById('applicant_phone');
                const tcNoInput = document.getElementById('tc_no');
                const identityNoInput = document.getElementById('identity_no');

                if (!mapEl || !geojsonInput || !areaInput) {
                    return;
                }

                const institutions = @json($institutionOptions);
                const surfaceTypes = @json($surfaceTypeOptions);
                const _latStr = centerLatInput?.value?.trim() ?? '';
                const _lngStr = centerLngInput?.value?.trim() ?? '';
                const initialCenterLat = _latStr !== '' ? Number(_latStr) : NaN;
                const initialCenterLng = _lngStr !== '' ? Number(_lngStr) : NaN;

                const defaultCenter = Number.isFinite(initialCenterLat) && Number.isFinite(initialCenterLng)
                    ? [initialCenterLat, initialCenterLng]
                    : [39.0, 35.0];

                const normalizeInstitutionColor = (institution) => {
                    if (!institution || typeof institution !== 'object') {
                        return '#DC2626';
                    }
                    const slug = String(institution.slug || '').toLowerCase();
                    const name = String(institution.name || '').toLowerCase();
                    if (institution.is_municipality || slug === 'belediye' || name.includes('belediye')) return '#16A34A';
                    if (slug === 'tedas' || name.includes('tedaş') || name.includes('tedas')) return '#DC2626';
                    if (slug === 'suski' || name.includes('şuski') || name.includes('suski')) return '#2563EB';
                    if (slug === 'aksa' || name.includes('aksa')) return '#EA580C';
                    return institution.color_code || '#DC2626';
                };

                const getSelectedDrawColor = () => {
                    if (!institutionSelect) return normalizeInstitutionColor(institutions[0]);
                    const selectedId = Number(institutionSelect.value);
                    const selected = institutions.find((i) => Number(i.id) === selectedId);
                    return normalizeInstitutionColor(selected || institutions[0]);
                };

                const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };

                const setCenter = (latLng) => {
                    if (!latLng) {
                        if (centerDisplay) centerDisplay.textContent = '—';
                        if (centerLatInput) centerLatInput.value = '';
                        if (centerLngInput) centerLngInput.value = '';
                        return;
                    }
                    const lat = Number(latLng.lat).toFixed(6);
                    const lng = Number(latLng.lng).toFixed(6);
                    if (centerLatInput) centerLatInput.value = lat;
                    if (centerLngInput) centerLngInput.value = lng;
                    if (centerDisplay) centerDisplay.textContent = `${lat}, ${lng}`;
                };

                const getSelectedSurfacePrice = () => {
                    const id = Number(surfaceTypeSelect?.value || 0);
                    const s = surfaceTypes.find((i) => Number(i.id) === id);
                    return s ? Number(s.price_per_m2) : 0;
                };

                const toNumeric = (v, f = 0) => {
                    let s = String(v ?? '').trim();
                    if (!s) return f;
                    if (/^\d+\.\d+$/.test(s)) return Number(s);
                    s = s.replace(/\./g, '').replace(',', '.');
                    const n = Number(s);
                    return Number.isFinite(n) ? n : f;
                };
                const EARTH_RADIUS_METERS = 6378137;
                const toRadians = (v) => Number(v) * Math.PI / 180;

                const distanceMeters = (a, b) => {
                    const dLat = toRadians(b.lat - a.lat);
                    const dLng = toRadians(b.lng - a.lng);
                    const sinLat = Math.sin(dLat / 2);
                    const sinLng = Math.sin(dLng / 2);
                    const hav = sinLat * sinLat + Math.cos(toRadians(a.lat)) * Math.cos(toRadians(b.lat)) * sinLng * sinLng;
                    return 2 * EARTH_RADIUS_METERS * Math.asin(Math.min(1, Math.sqrt(hav)));
                };

                const polylineLengthMeters = (pts) => {
                    let t = 0;
                    for (let i = 1; i < pts.length; i++) t += distanceMeters(pts[i - 1], pts[i]);
                    return t;
                };

                const polygonAreaMeters = (pts) => {
                    if (pts.length < 3) return 0;
                    let avgLat = 0;
                    for (let i = 0; i < pts.length; i++) avgLat += pts[i].lat;
                    avgLat /= pts.length;
                    const scale = Math.cos(toRadians(avgLat));
                    let s = 0;
                    for (let i = 0; i < pts.length; i++) {
                        const a = pts[i], b = pts[(i + 1) % pts.length];
                        s += (EARTH_RADIUS_METERS * toRadians(a.lng) * scale) * (EARTH_RADIUS_METERS * toRadians(b.lat))
                           - (EARTH_RADIUS_METERS * toRadians(b.lng) * scale) * (EARTH_RADIUS_METERS * toRadians(a.lat));
                    }
                    return Math.abs(s) / 2;
                };

                const rectangleAreaMeters = (b) => {
                    const w = distanceMeters({ lat: b.getSouth(), lng: b.getWest() }, { lat: b.getSouth(), lng: b.getEast() });
                    const h = distanceMeters({ lat: b.getSouth(), lng: b.getWest() }, { lat: b.getNorth(), lng: b.getWest() });
                    return w * h;
                };

                const updateLineLengthDisplay = (m = 0) => { if (lineLengthDisplay) lineLengthDisplay.textContent = `${m.toFixed(3)} m`; };

                const formatTR = (v) => {
                    if (v == null || isNaN(v)) return '0,00';
                    return Number(v).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const updateSurfaceSummary = () => {
                    const area = Math.max(toNumeric(areaInput.value), 0);
                    const w = Math.max(toNumeric(widthInput?.value), 0);
                    const l = Math.max(toNumeric(lengthInput?.value), 0);
                    const q = Math.max(toNumeric(quantityInput?.value, 1), 0);
                    const m = Math.max(toNumeric(multiplierInput?.value, 1), 0);
                    const up = Math.max(getSelectedSurfacePrice(), 0);

                    const layers = drawnItems.getLayers();
                    const hasPolygon = layers.some(l => l instanceof L.Polygon || l instanceof L.Circle);
                    const hasPolyline = layers.some(l => l instanceof L.Polyline && !(l instanceof L.Polygon)) && !hasPolygon;

                    let measured;
                    if (hasPolygon && area > 0) {
                        measured = area * q;
                    } else if (hasPolyline && w > 0) {
                        measured = w * l * q;
                    } else {
                        measured = w > 0 && l > 0 ? w * l * q : area * q;
                    }

                    const total = measured * up * m;
                    if (surfaceTotalDisplay) surfaceTotalDisplay.textContent = `${formatTR(total)} TL`;
                    if (calculatedAmountInput) calculatedAmountInput.value = total.toFixed(3);
                    return total;
                };

                const setTcknStatus = (msg, tone = 'neutral') => {
                    if (!tcknCheckStatus) return;
                    tcknCheckStatus.textContent = msg;
                    tcknCheckStatus.classList.remove('text-slate-500', 'text-emerald-700', 'text-red-600', 'text-cyan-700');
                    const cls = { neutral: 'text-slate-500', success: 'text-emerald-700', error: 'text-red-600', info: 'text-cyan-700' }[tone] || 'text-slate-500';
                    tcknCheckStatus.classList.add(cls);
                };

                const fillApplicantFields = (data) => {
                    if (!data || typeof data !== 'object') return;
                    if (firstNameInput && typeof data.applicant_first_name === 'string') firstNameInput.value = data.applicant_first_name;
                    if (lastNameInput && typeof data.applicant_last_name === 'string') lastNameInput.value = data.applicant_last_name;
                    if (phoneInput && typeof data.applicant_phone === 'string') phoneInput.value = data.applicant_phone;
                    if (tcknInput && typeof data.applicant_national_id === 'string') tcknInput.value = data.applicant_national_id;
                    const norm = typeof data.applicant_national_id === 'string' ? data.applicant_national_id : String(tcknInput?.value || '').replace(/\D+/g, '');
                    if (tcNoInput) tcNoInput.value = norm;
                    if (identityNoInput) identityNoInput.value = norm;
                    const addr = document.getElementById('address_text');
                    if (addr && typeof data.address_text === 'string' && data.address_text) addr.value = data.address_text;
                };

                const checkApplicantByTckn = async () => {
                    const raw = String(tcknInput?.value || '');
                    const tckn = raw.replace(/\D+/g, '');
                    if (tckn.length !== 11) { setTcknStatus('TCKN 11 haneli olmalıdır.', 'error'); return; }
                    if (tcknInput) tcknInput.value = tckn;
                    if (tcNoInput) tcNoInput.value = tckn;
                    if (identityNoInput) identityNoInput.value = tckn;
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    if (!csrf) { setTcknStatus('CSRF token bulunamadı.', 'error'); return; }
                    if (tcknCheckBtn) { tcknCheckBtn.disabled = true; tcknCheckBtn.classList.add('opacity-70', 'cursor-not-allowed'); }
                    setTcknStatus('TCKN sorgulanıyor...', 'info');
                    try {
                        const r = await fetch(@json(route('admin.applications.check-applicant')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ applicant_national_id: tckn }),
                        });
                        const p = await r.json().catch(() => ({}));
                        if (!r.ok) { setTcknStatus(typeof p?.message === 'string' ? p.message : 'Sorgu sırasında bir hata oluştu.', 'error'); return; }
                        if (p?.found && p?.data) {
                            fillApplicantFields(p.data);
                            setTcknStatus(`Kayıt bulundu (${p.source === 'application' ? 'başvuru arşivi' : 'kullanıcı kaydı'}). Alanlar otomatik dolduruldu.`, 'success');
                            return;
                        }
                        setTcknStatus(p?.message || 'Bu TCKN için kayıt bulunamadı.', 'neutral');
                    } catch { setTcknStatus('TCKN sorgusu sırasında ağ hatası oluştu.', 'error'); }
                    finally { if (tcknCheckBtn) { tcknCheckBtn.disabled = false; tcknCheckBtn.classList.remove('opacity-70', 'cursor-not-allowed'); } }
                };

                tcknCheckBtn?.addEventListener('click', checkApplicantByTckn);
                tcknInput?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); checkApplicantByTckn(); } });

                const toPath = (ring) => Array.isArray(ring) ? ring.filter(p => Array.isArray(p) && p.length >= 2).map(p => ({ lat: Number(p[1]), lng: Number(p[0]) })).filter(p => Number.isFinite(p.lat) && Number.isFinite(p.lng)) : [];

                const parseFeatureGeometry = (geometry) => {
                    if (!geometry || typeof geometry !== 'object') return null;
                    if (geometry.type === 'Polygon') {
                        const path = toPath(geometry.coordinates[0] ?? []);
                        return path.length >= 3 ? { kind: 'polygon', path } : null;
                    }
                    if (geometry.type === 'LineString') {
                        const path = toPath(geometry.coordinates);
                        return path.length >= 2 ? { kind: 'polyline', path } : null;
                    }
                    if (geometry.type === 'Point' && Array.isArray(geometry.coordinates) && geometry.coordinates.length >= 2) {
                        const lng = Number(geometry.coordinates[0]), lat = Number(geometry.coordinates[1]);
                        return Number.isFinite(lat) && Number.isFinite(lng) ? { kind: 'marker', position: { lat, lng } } : null;
                    }
                    return null;
                };

                const parseGeoJsonFeatures = (raw) => {
                    if (!raw) return [];
                    let data;
                    try { data = typeof raw === 'string' ? JSON.parse(raw) : raw; } catch { return []; }
                    if (!data || typeof data !== 'object') return [];
                    if (data.type === 'FeatureCollection' && Array.isArray(data.features))
                        return data.features.map(f => { const p = parseFeatureGeometry(f?.geometry); return p ? { ...p, type: 'Feature', geometry: f.geometry, properties: f?.properties || {} } : null; }).filter(Boolean);
                    if (data.type === 'Feature') { const p = parseFeatureGeometry(data.geometry); return p ? [{ ...p, type: 'Feature', geometry: data.geometry, properties: data.properties || {} }] : []; }
                    const p = parseFeatureGeometry(data);
                    return p ? [{ ...p, type: 'Feature', geometry: data, properties: {} }] : [];
                };

                // Haritayı başlat
                const map = L.map('application-drawing-map', {
                    center: defaultCenter,
                    zoom: 14,
                    zoomControl: true,
                });

                // Tile katmanları (ücretsiz, API anahtarı gerekmez)
                const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19, attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
                });
                const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19, attribution: '&copy; <a href="https://esri.com">Esri</a>',
                });
                const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    maxZoom: 17, attribution: '&copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
                });
                osm.addTo(map);

                // Katman değiştirici
                L.control.layers({ Standart: osm, Uydu: satellite, Arazi: terrain }, null, { position: 'topleft' }).addTo(map);

                // Görünüm butonları
                const styleRow = document.getElementById('map-style-panel');
                if (styleRow) {
                    const switchLayer = (id) => {
                        const layers = { 'style-standard': osm, 'style-satellite': satellite, 'style-terrain': terrain };
                        Object.entries(layers).forEach(([k, l]) => { if (k === id) map.addLayer(l); else map.removeLayer(l); });
                        document.querySelectorAll('#map-style-panel button').forEach(b => {
                            const active = b.id === id;
                            b.className = active
                                ? 'w-full rounded-lg border border-[#FA6001]/30 bg-[#FA6001]/10 px-3 py-1.5 text-left text-[11px] font-semibold text-[#FA6001] transition hover:bg-[#FA6001]/20'
                                : 'w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-left text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50';
                        });
                    };
                    document.getElementById('style-standard')?.addEventListener('click', () => switchLayer('style-standard'));
                    document.getElementById('style-satellite')?.addEventListener('click', () => switchLayer('style-satellite'));
                    document.getElementById('style-terrain')?.addEventListener('click', () => switchLayer('style-terrain'));
                }

                // Adres arama (Nominatim — ücretsiz)
                const searchInput = document.getElementById('map-search-input');
                if (searchInput) {
                    searchInput.addEventListener('keydown', async (e) => {
                        if (e.key !== 'Enter') return;
                        e.preventDefault();
                        const q = searchInput.value.trim();
                        if (!q) return;
                        setStatus('Adres aranıyor...');
                        try {
                            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1&countrycodes=tr`);
                            const data = await res.json();
                            if (data.length) {
                                const { lat, lon, display_name } = data[0];
                                map.setView([lat, lon], 18);
                                const addr = document.getElementById('address_text');
                                if (addr) addr.value = display_name;
                                setStatus('Adres bulundu.');
                            } else { setStatus('Adres bulunamadı.'); }
                        } catch { setStatus('Arama hatası.'); }
                    });
                }

                let strokeColor = getSelectedDrawColor();
                const drawnItems = new L.FeatureGroup();
                map.addLayer(drawnItems);

                const updateColorPreview = () => { if (activeColorDot) activeColorDot.style.backgroundColor = strokeColor; };
                updateColorPreview();

                // Çizim kontrolü (Leaflet.draw)
                const drawControl = new L.Control.Draw({
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

                const repaintOverlays = () => {
                    drawnItems.eachLayer((layer) => {
                        if (layer.setStyle) layer.setStyle({ color: strokeColor, fillColor: strokeColor });
                    });
                };

                // Konumum (harita üzerinde buton)
                let myLocationMarker = null;
                const MyLocationControl = L.Control.extend({
                    onAdd: function () {
                        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control leaflet-control-custom');
                        btn.innerHTML = '📍';
                        btn.title = 'Konumum';
                        btn.setAttribute('type', 'button');
                        btn.style.cssText = 'width:36px;height:36px;font-size:18px;background:#fff;border:2px solid rgba(0,0,0,0.2);background-clip:padding-box;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;';
                        btn.onmouseover = () => { btn.style.background = '#f4f4f4'; };
                        btn.onmouseout = () => { btn.style.background = '#fff'; };
                        L.DomEvent.on(btn, 'click', function (e) {
                            L.DomEvent.stopPropagation(e);
                            L.DomEvent.preventDefault(e);
                            if (!navigator.geolocation) { setStatus('Tarayıcınız konum servisini desteklemiyor.'); return; }
                            setStatus('Konumunuz alınıyor...');
                            navigator.geolocation.getCurrentPosition(
                                (pos) => {
                                    const p = [pos.coords.latitude, pos.coords.longitude];
                                    map.setView(p, 17);
                                    if (myLocationMarker) map.removeLayer(myLocationMarker);
                                    myLocationMarker = L.marker(p, {
                                        icon: L.divIcon({
                                            className: '',
                                            html: '<svg viewBox="0 0 32 42" width="28" height="36" xmlns="http://www.w3.org/2000/svg"><path d="M16 0C7.2 0 0 7.2 0 16c0 12 16 26 16 26s16-14 16-26C32 7.2 24.8 0 16 0zm0 22c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z" fill="#0284C7" stroke="#fff" stroke-width="2"/><circle cx="16" cy="16" r="5" fill="#fff"/></svg>',
                                            iconSize: [28, 36],
                                            iconAnchor: [14, 36],
                                        }),
                                    }).addTo(map);
                                    myLocationMarker.bindPopup('📍 Konumum');
                                    setStatus('Konumunuz haritada işaretlendi.');
                                },
                                () => setStatus('Konum alınamadı. Konum iznini kontrol edin.'),
                                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
                            );
                        });
                        return btn;
                    },
                });
                map.addControl(new MyLocationControl({ position: 'topright' }));

                // Serileştir (GeoJSON + alan + merkez)
                const serializeAndSync = (message = 'Çizim güncellendi.') => {
                    const features = [];
                    let totalArea = 0, totalLineLength = 0;
                    const bounds = L.latLngBounds();
                    let centerCandidate = null;

                    drawnItems.eachLayer((layer) => {
                        let feature = null;
                        if (layer instanceof L.Polygon && !(layer instanceof L.Rectangle) && !(layer instanceof L.Circle)) {
                            const pts = layer.getLatLngs()[0];
                            const coords = pts.map(p => [p.lng, p.lat]);
                            coords.push([pts[0].lng, pts[0].lat]);
                            feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { shape: 'polygon' } };
                            totalArea += polygonAreaMeters(pts);
                            pts.forEach(p => bounds.extend(p));
                            centerCandidate = bounds.getCenter();
                        } else if (layer instanceof L.Rectangle) {
                            const b = layer.getBounds(), ne = b.getNorthEast(), sw = b.getSouthWest();
                            const coords = [[sw.lng, sw.lat], [ne.lng, sw.lat], [ne.lng, ne.lat], [sw.lng, ne.lat], [sw.lng, sw.lat]];
                            feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { shape: 'rectangle' } };
                            totalArea += rectangleAreaMeters(b);
                            bounds.extend(ne); bounds.extend(sw);
                            centerCandidate = b.getCenter();
                        } else if (layer instanceof L.Circle) {
                            const c = layer.getLatLng(), r = layer.getRadius(), pts = 64, coords = [];
                            for (let i = 0; i < pts; i++) {
                                const a = (i / pts) * 2 * Math.PI;
                                coords.push([c.lng + (r / (111320 * Math.cos(c.lat * Math.PI / 180))) * Math.sin(a), c.lat + (r / 111320) * Math.cos(a)]);
                            }
                            coords.push(coords[0]);
                            feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { shape: 'circle', center_lat: c.lat, center_lng: c.lng, radius_m: r } };
                            totalArea += Math.PI * r * r;
                            const cb = layer.getBounds();
                            if (cb) { bounds.extend(cb.getNorthEast()); bounds.extend(cb.getSouthWest()); }
                            centerCandidate = c;
                        } else if (layer instanceof L.Polyline) {
                            const pts = layer.getLatLngs();
                            const coords = pts.map(p => [p.lng, p.lat]);
                            feature = { type: 'Feature', geometry: { type: 'LineString', coordinates: coords }, properties: { shape: 'polyline' } };
                            totalLineLength += polylineLengthMeters(pts);
                            pts.forEach(p => bounds.extend(p));
                            if (!centerCandidate) centerCandidate = pts[Math.floor(pts.length / 2)];
                        } else if (layer instanceof L.Marker) {
                            const p = layer.getLatLng();
                            feature = { type: 'Feature', geometry: { type: 'Point', coordinates: [p.lng, p.lat] }, properties: { shape: 'marker' } };
                            bounds.extend(p);
                            if (!centerCandidate) centerCandidate = p;
                        }
                        if (feature) features.push(feature);
                    });

                    geojsonInput.value = features.length ? JSON.stringify({ type: 'FeatureCollection', features }) : '';
                    areaInput.value = Number.isFinite(totalArea) ? totalArea.toFixed(3) : '0';
                    document.getElementById('total_area_m2')?.dispatchEvent(new Event('input'));
                    updateLineLengthDisplay(Number.isFinite(totalLineLength) ? totalLineLength : 0);
                    if (totalLineLength > 0 && lengthInput) lengthInput.value = totalLineLength.toFixed(3);
                    if (centerCandidate) setCenter({ lat: centerCandidate.lat, lng: centerCandidate.lng });
                    else setCenter(null);
                    updateSurfaceSummary();
                    setStatus(message);
                };

                // Track whether current drawing has area (polygon/circle) vs just lines
                let _hasAreaShape = false;

                // Auto-fill width_m and length_m from bounding box (only for area shapes)
                const autoFillDimensions = () => {
                    if (!widthInput || !lengthInput || !_hasAreaShape) return;
                    const b = drawnItems.getBounds();
                    if (!b || !b.isValid()) return;
                    try {
                        const w = distanceMeters({ lat: b.getCenter().lat, lng: b.getWest() }, { lat: b.getCenter().lat, lng: b.getEast() });
                        const h = distanceMeters({ lat: b.getSouth(), lng: b.getCenter().lng }, { lat: b.getNorth(), lng: b.getCenter().lng });
                        if (w > 0 && h > 0) { widthInput.value = Math.max(w, h).toFixed(3); lengthInput.value = Math.min(w, h).toFixed(3); }
                    } catch(e) {}
                };

                // Çizim olayları
                map.on(L.Draw.Event.CREATED, (e) => {
                    const layer = e.layer;
                    if (layer.setStyle) layer.setStyle({ color: strokeColor, fillColor: strokeColor });
                    drawnItems.addLayer(layer);
                    serializeAndSync('Çizim haritaya işlendi.');
                });
                map.on(L.Draw.Event.EDITED, () => serializeAndSync('Çizim güncellendi.'));
                map.on(L.Draw.Event.DELETED, () => serializeAndSync('Çizim silindi.'));

                // Harita tıklama
                map.on('click', (e) => {
                    if (drawnItems.getLayers().length === 0) setCenter({ lat: e.latlng.lat, lng: e.latlng.lng });
                });

                // Temizle
                clearBtn?.addEventListener('click', () => {
                    drawnItems.clearLayers();
                    geojsonInput.value = '';
                    areaInput.value = '0';
                    document.getElementById('total_area_m2')?.dispatchEvent(new Event('input'));
                    setCenter(null);
                    setStatus('Çizim temizlendi.');
                });

                // GeoJSON uygula
                applyGeojsonBtn?.addEventListener('click', () => {
                    const features = parseGeoJsonFeatures(geojsonInput.value);
                    drawnItems.clearLayers();
                    if (!features.length) { setStatus('GeoJSON verisi haritada gösterilemedi.'); return; }
                    const bounds = L.latLngBounds();
                    features.forEach((f) => {
                        let layer = null;
                        if (f.kind === 'polygon') {
                            layer = L.polygon(f.path, { color: strokeColor, fillColor: strokeColor, fillOpacity: 0.22, weight: 2 });
                            f.path.forEach(p => bounds.extend(p));
                        } else if (f.kind === 'polyline') {
                            layer = L.polyline(f.path, { color: strokeColor, weight: 3 });
                            f.path.forEach(p => bounds.extend(p));
                        } else if (f.kind === 'marker') {
                            layer = L.marker(f.position);
                            bounds.extend(f.position);
                        }
                        if (layer) drawnItems.addLayer(layer);
                    });
                    if (!bounds.isEmpty()) map.fitBounds(bounds);
                    serializeAndSync('GeoJSON haritaya uygulandı.');
                });

                // Kurum değişince renk
                institutionSelect?.addEventListener('change', () => {
                    strokeColor = getSelectedDrawColor();
                    updateColorPreview();
                    repaintOverlays();
                    setStatus('Kurum rengine göre çizim rengi güncellendi.');
                });

                // Yüzey tipi hesaplama
                [surfaceTypeSelect, widthInput, lengthInput, quantityInput, multiplierInput, areaInput].forEach(el => el?.addEventListener('input', updateSurfaceSummary));
                surfaceCalcBtn?.addEventListener('click', () => { const t = updateSurfaceSummary(); setStatus(`Yüzey tipine göre hesaplandı: ${formatTR(t)} TL`); });

                // Mevcut GeoJSON varsa yükle
                if (geojsonInput.value.trim() !== '') {
                    applyGeojsonBtn?.click();
                } else {
                    setStatus('Haritadan bir alan seçin.');
                    updateSurfaceSummary();
                }

                // Harita hareket
                map.on('moveend', () => {
                    if (drawnItems.getLayers().length === 0) {
                        const c = map.getCenter();
                        setCenter({ lat: c.lat, lng: c.lng });
                    }
                });

                // Auto-fill width_m and length_m (bilgi amaçlı, hesaplamada kullanılmaz)
                drawnItems.on('layeradd', () => {
                    if (!widthInput || !lengthInput) return;
                    const b = drawnItems.getBounds();
                    if (!b || !b.isValid()) return;
                    try {
                        const w = distanceMeters({lat: b.getCenter().lat, lng: b.getWest()}, {lat: b.getCenter().lat, lng: b.getEast()});
                        const h = distanceMeters({lat: b.getSouth(), lng: b.getCenter().lng}, {lat: b.getNorth(), lng: b.getCenter().lng});
                        if (w > 0 && h > 0) { widthInput.value = Math.max(w,h).toFixed(3); lengthInput.value = Math.min(w,h).toFixed(3); }
                    } catch(e) {}
                });
                drawnItems.on('layerremove', () => {
                    if (!widthInput || !lengthInput) return;
                    if (!drawnItems.getLayers().length) {
                        widthInput.value = ''; lengthInput.value = '';
                    }
                });
            })();

            // Document upload — supports multiple files
            (() => {
                const dz = document.getElementById('document-dropzone');
                const inp = document.getElementById('document-input');
                const preview = document.getElementById('document-preview');
                const status = document.getElementById('document-status');
                let allFiles = [];

                function render() {
                    preview.innerHTML = '';
                    if (!allFiles.length) { status.textContent = ''; return; }
                    status.textContent = allFiles.length + ' dosya seçildi.';
                    allFiles.forEach((f, i) => {
                        const img = f.type.startsWith('image/');
                        const div = document.createElement('div');
                        div.className = 'relative rounded-lg border border-slate-200 bg-white p-2 shadow-sm';
                        div.innerHTML = `
                            <div class="flex items-center gap-2">
                                ${img ? `<img src="${URL.createObjectURL(f)}" class="h-10 w-10 rounded object-cover">` : `<span class="flex h-10 w-10 items-center justify-center rounded bg-slate-100 text-xs font-bold text-slate-500">PDF</span>`}
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium text-slate-700 max-w-[180px]">${f.name}</p>
                                    <p class="text-[10px] text-slate-500">${(f.size / 1024).toFixed(1)} KB</p>
                                </div>
                                <button type="button" class="rm-file shrink-0 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600" data-idx="${i}">&times;</button>
                            </div>`;
                        preview.appendChild(div);
                    });
                    preview.querySelectorAll('.rm-file').forEach(b => b.addEventListener('click', () => {
                        allFiles.splice(Number(b.dataset.idx), 1);
                        render();
                        syncInput();
                    }));
                }

                function syncInput() {
                    const dt = new DataTransfer();
                    allFiles.forEach(f => dt.items.add(f));
                    try { inp.files = dt.files; } catch(e) {}
                }

                dz?.addEventListener('click', () => inp?.click());
                dz?.addEventListener('dragover', e => { e.preventDefault(); dz.classList.replace('border-slate-300','border-sky-400'); });
                dz?.addEventListener('dragleave', () => dz.classList.replace('border-sky-400','border-slate-300'));
                dz?.addEventListener('drop', e => {
                    e.preventDefault();
                    dz.classList.replace('border-sky-400','border-slate-300');
                    Array.from(e.dataTransfer.files).forEach(f => { if (!allFiles.some(x => x.name===f.name && x.size===f.size)) allFiles.push(f); });
                    syncInput();
                    render();
                });
                inp?.addEventListener('change', () => {
                    Array.from(inp.files).forEach(f => { if (!allFiles.some(x => x.name===f.name && x.size===f.size)) allFiles.push(f); });
                    syncInput();
                    render();
                });
            })();

            // Para formatı: her tuşta anında formatla
            (() => {
                function digitsOnly(v) {
                    return String(v).replace(/[^0-9]/g, '');
                }

                function formatInput(el) {
                    let liraPart = el.value.replace(/,00$/, '');
                    let d = digitsOnly(liraPart);
                    if (!d) { el.value = ''; return; }
                    d = String(parseInt(d, 10) || 0);
                    let formatted = d.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    el.value = formatted + ',00';
                    let pos = formatted.length;
                    el.setSelectionRange(pos, pos);
                }

                ['deposit_amount', 'excavation_amount'].forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;

                    let initVal = el.value;
                    if (initVal) {
                        let n = parseFloat(String(initVal).replace(',', '.'));
                        if (Number.isFinite(n) && n !== 0) {
                            el.value = n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }

                    el.addEventListener('input', function() { formatInput(this); });
                    el.addEventListener('blur', function() { formatInput(this); });
                });

                // Alan (m²) formatı
                (function() {
                    const areaInput = document.getElementById('total_area_m2');
                    if (!areaInput) return;

                    function fmtArea(el) {
                        let raw = el.value.replace(',', '.');
                        let n = parseFloat(raw);
                        if (isNaN(n) || n < 0) { el.value = '0'; return; }
                        el.value = n.toLocaleString('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                    }

                    areaInput.addEventListener('blur', function() { fmtArea(this); });
                })();

                document.querySelector('form')?.addEventListener('submit', function() {
                    ['deposit_amount', 'excavation_amount'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            let raw = digitsOnly(el.value);
                            let n = parseFloat(raw) / 100;
                            el.value = Number.isFinite(n) ? n.toFixed(2) : '';
                        }
                    });
                    // Alan (m²) — formatı temizle, decimal koru
                    const areaEl = document.getElementById('total_area_m2');
                    if (areaEl) {
                        let v = areaEl.value.replace(',', '.').replace(/\s/g, '');
                        let n = parseFloat(v);
                        areaEl.value = Number.isFinite(n) && n >= 0 ? n.toString() : '0';
                    }
                });
            })();
    </script>
@endpush
