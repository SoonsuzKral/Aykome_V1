# AYKOME MAPS — CBS Entegrasyon Modülü Geliştirme Rehberi

**Versiyon:** 1.0  
**Proje:** AYKOME HGB Bilişim  ULTRA SAAS v6  
**Modül Adı:** CBS Entegrasyon → Aykome Maps  
**Route:** `/maps`  
**Son Güncelleme:** 2026-05-20

---

## 1. MODÜL AMACI

Aykome Maps, Şanlıurfa Büyükşehir Belediyesi GIS altyapısı ile tam entegrasyon sağlayan, tam ekran interaktif GIS haritası modülüdür.

**Temel işlevler:**

- WMS/WFS sunucularından Şanlıurfa Büyükşehir kadastro ve altyapı katmanlarını çekme
- Harita üzerinden tıklama ile parsel/ada/mahalle bilgisi sorgulama
- Tıklama sonrası kazı ruhsatı veya ortak kazı başvurusu açma
- Mevcut başvuruları harita üzerinde pin/marker olarak gösterme
- Tam mobil uyumlu (responsive) tasarım

**Koordinat sistemi:**
- WMS/WFS: EPSG:3857 (Web Mercator)
- Leaflet: WGS84 (lat/lng) — dönüşüm gerekli
- BBOX format: `minX,minY,maxX,maxY` (EPSG:3857)

---

## 2. WMS/WFS SUNUCU BİLGİLERİ

### Sunucu Tanımları

| Sunucu | Adres | Port | Grup | uID |
|--------|-------|------|------|-----|
| geo4   | geo4.sanliurfa.bel.tr | 7171 | AKOS (Kadastro) | 45 |
| geo2   | geo2.sanliurfa.bel.tr | 9191 | MAKS+ (Altyapı) | 404 |

### Endpoint'ler

```
# WMS - tile çekme (doğrudan kullanılabilir, CORS yok)
https://geo4.sanliurfa.bel.tr:7171/geoserver/wms
https://geo2.sanliurfa.bel.tr:9191/geoserver/wms

# WFS - feature sorgulama (Laravel proxy gerekli)
https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs
https://geo2.sanliurfa.bel.tr:9191/geoserver/wfs
```

### CORS Proxy Çözümü

WFS sorguları tarayıcıdan doğrudan yapılamaz (CORS politikası). Laravel proxy endpoint üzerinden yönlendirilecek:

```php
// GET /maps/proxy?url=encoded_wfs_url
Route::get('/proxy', [MapsController::class, 'proxy'])->name('proxy');
```

Proxy güvenliği: Yalnızca `geo4.sanliurfa.bel.tr` ve `geo2.sanliurfa.bel.tr` domainlerine izin verilecek.

### WMS Tile Çekme Örneği

```javascript
// Leaflet ile WMS katmanı ekleme
L.tileLayer('https://geo4.sanliurfa.bel.tr:7171/geoserver/wms', {
    layers: 'smpns:MISMAP_NUM_KADASTRO_PARSEL',
    format: 'image/png',
    transparent: true,
    opacity: 0.7,
    crs: L.CRS.EPSG3857
}).addTo(map);
```

### WFS Feature Sorgu Örneği

```
GET https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs?
  service=WFS&
  version=2.0.0&
  request=GetFeature&
  typeNames=smpns:MISMAP_NUM_KADASTRO_PARSEL&
  filter=<Filter><Within><PropertyName>geom</PropertyName><gml:Envelope srsName="EPSG:3857"><lowerCorner>3800000 4400000</lowerCorner><upperCorner>3900000 4500000</upperCorner></gml:Envelope></Within></Filter>
```

---

## 3. KATMAN LİSTESİ

### AKOS Grubu — geo4 (uID: 45)

| Layer Name | Açıklama | Varsayılan |
|---|---|---|
| smpns:MISMAP_NUM_KADASTRO_PARSEL | Parseller | ✅ Açık |
| smpns:MISMAP_NUM_BINA | Binalar | ✅ Açık |
| smpns:MISMAP_NUM_ILCE | İlçe Sınırları | ✅ Açık |
| smpns:MISMAP_NUM_MAHALLE | Mahalle | ✅ Açık |
| smpns:MISMAP_NUM_ADA | Ada | ❌ Kapalı |
| smpns:MISMAP_NUM_CADDESOKAK | Cadde Sokak | ✅ Açık |
| smpns:MISMAP_NUM_ADRES | Adres | ❌ Kapalı |
| smpns:MISMAP_NUM_PAFTA | Pafta | ❌ Kapalı |
| smpns:MISMAP_NUM_BAGIMSIZ | Bağımsız Kullanım | ✅ Açık |

### MAKS+ Grubu — geo2 (uID: 404)

| Layer Name | Açıklama | Varsayılan |
|---|---|---|
| smpns:AYK_DOGALGAZ_LINKS | Doğalgaz Hatları | ✅ Açık |
| smpns:AYK_DOGALGAZ_NODES | Doğalgaz Noktaları | ❌ Kapalı |
| smpns:AYK_ELEKTRIK_LINKS | Elektrik Hatları | ❌ Kapalı |
| smpns:AYK_ELEKTRIK_NODES | Elektrik Noktaları | ❌ Kapalı |
| smpns:METROBUS_CAD | Metrobüs CAD | ❌ Kapalı |

**WFS typename format:** `smpns:LAYER_ADI`

---

## 4. DOSYA YAPISI

Oluşturulacak dosyalar (mevcut projenin kod stiline uygun):

```
app/Http/Controllers/MapsController.php      # 4 metod
resources/views/maps/index.blade.php          # Tam ekran harita view
database/migrations/xxxx_create_gis_tables.php # Tablo oluşturma
routes/web.php                                # Route ekleme
```

### MapsController — Metod Listesi

| Metod | Route | Açıklama |
|-------|-------|----------|
| index | GET /maps | Harita ana sayfası view |
| proxy | GET /maps/proxy?url=... | WFS CORS proxy |
| noktaKaydet | POST /maps/nokta-kaydet | Haritadan seçilen noktayı DB'ye kaydet |
| basvurularGeoJson | GET /maps/basvurular-geojson | Başvuruları GeoJSON olarak döndür |

---

## 5. MapsController METODLARI

```php
<?php

namespace App\Http\Controllers;

use App\Models\GisBasvuruNokta;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapsController extends Controller
{
    /**
     * Harita ana sayfası
     */
    public function index()
    {
        return view('maps.index');
    }

    /**
     * WFS CORS proxy — yalnızca geo4/geo2 domainlerine izin verilir
     */
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['error' => 'URL parametresi gerekli'], 400);
        }

        $decodedUrl = urldecode($url);

        // Güvenlik: yalnızca izin verilen domainlere yönlendir
        if (!str_contains($decodedUrl, 'geo4.sanliurfa.bel.tr') &&
            !str_contains($decodedUrl, 'geo2.sanliurfa.bel.tr')) {
            return response()->json(['error' => 'İzin verilmeyen domain'], 403);
        }

        try {
            $response = Http::timeout(30)->get($decodedUrl);
            return response($response->body(), $response->status(), [
                'Content-Type' => $response->header('Content-Type', 'application/xml'),
            ]);
        } catch (\Exception $e) {
            Log::error('WFS Proxy hatası: ' . $e->getMessage());
            return response()->json(['error' => 'WFS sorgusu başarısız'], 500);
        }
    }

    /**
     * Haritadan seçilen noktayı DB'ye kaydet
     */
    public function noktaKaydet(Request $request)
    {
        $data = $request->validate([
            'basvuru_id'   => ['nullable', 'integer'],
            'basvuru_tipi' => ['required', 'in:kazi_ruhsat,ortak_kazi'],
            'lat'          => ['required', 'numeric', 'between:-90,90'],
            'lng'          => ['required', 'numeric', 'between:-180,180'],
            'ilce'         => ['nullable', 'string', 'max:100'],
            'mahalle'      => ['nullable', 'string', 'max:100'],
            'ada'          => ['nullable', 'string', 'max:50'],
            'parsel'       => ['nullable', 'string', 'max:50'],
            'wfs_response' => ['nullable', 'json'],
        ]);

        $nokta = GisBasvuruNokta::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Nokta kaydedildi.',
            'data'    => $nokta
        ]);
    }

    /**
     * Başvuruları GeoJSON olarak döndür (haritada pin olarak gösterilecek)
     */
    public function basvurularGeoJson()
    {
        $basvurular = Application::whereIn('status', ['licensed', 'field_work', 'completed'])
            ->whereNotNull('address_text')
            ->select('id', 'application_no', 'status', 'address_text')
            ->with('excavationArea')
            ->get()
            ->map(function ($app) {
                if ($app->excavationArea && $app->excavationArea->center_lat && $app->excavationArea->center_lng) {
                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [
                                (float) $app->excavationArea->center_lng,
                                (float) $app->excavationArea->center_lat
                            ]
                        ],
                        'properties' => [
                            'id'             => $app->id,
                            'application_no' => $app->application_no,
                            'status'         => $app->status,
                            'address'        => $app->address_text,
                        ]
                    ];
                }
                return null;
            })
            ->filter()
            ->values();

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $basvurular
        ]);
    }
}
```

---

## 6. DATABASE TABLOLARI

### Tablo 1: gis_basvuru_noktalar

Haritadan tıklanan noktaların kaydedildiği tablo.

```php
// migration: xxxx_create_gis_tables.php
Schema::create('gis_basvuru_noktalar', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('basvuru_id')->nullable();
    $table->enum('basvuru_tipi', ['kazi_ruhsat', 'ortak_kazi']);
    $table->decimal('lat', 15, 8);
    $table->decimal('lng', 15, 8);
    $table->string('ilce', 100)->nullable();
    $table->string('mahalle', 100)->nullable();
    $table->string('ada', 50)->nullable();
    $table->string('parsel', 50)->nullable();
    $table->json('wfs_response')->nullable();
    $table->timestamps();

    $table->foreign('basvuru_id')->references('id')->on('applications')->onDelete('set null');
});
```

### Tablo 2: gis_cizimler (opsiyonel)

Harita üzerinde kullanıcı çizimleri için.

```php
Schema::create('gis_cizimler', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->enum('tip', ['nokta', 'cizgi', 'alan']);
    $table->json('geometri'); // GeoJSON
    $table->unsignedBigInteger('basvuru_id')->nullable();
    $table->decimal('lat', 15, 8)->nullable();
    $table->decimal('lng', 15, 8)->nullable();
    $table->text('aciklama')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('basvuru_id')->references('id')->on('applications')->onDelete('set null');
});
```

### Modeller

```php
// app/Models/GisBasvuruNokta.php
class GisBasvuruNokta extends Model
{
    protected $table = 'gis_basvuru_noktalar';

    protected $fillable = [
        'basvuru_id',
        'basvuru_tipi',
        'lat',
        'lng',
        'ilce',
        'mahalle',
        'ada',
        'parsel',
        'wfs_response',
    ];

    protected $casts = [
        'wfs_response' => 'array',
        'lat'          => 'float',
        'lng'          => 'float',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class, 'basvuru_id');
    }
}
```

---

## 7. ROUTE'LAR

`routes/web.php`'e eklenecek:

```php
Route::middleware(['auth'])->prefix('maps')->name('maps.')->group(function () {
    Route::get('/',                   [MapsController::class, 'index'])->name('index');
    Route::get('/proxy',              [MapsController::class, 'proxy'])->name('proxy');
    Route::post('/nokta-kaydet',      [MapsController::class, 'noktaKaydet'])->name('noktaKaydet');
    Route::get('/basvurular-geojson', [MapsController::class, 'basvurularGeoJson'])->name('basvurularGeoJson');
});
```

---

## 8. EKRAN TASARIMI

### Layout Yapısı

```
┌─────────────────────────────────────────────────────────────┐
│  AYKOME NAVBAR (mevcut — dokunma)                           │
├──────────┬──────────────────────────────────────────────────┤
│          │                                                  │
│ SOL      │         LEAFLET HARİTA (tam ekran)               │
│ PANEL    │                                                  │
│ [≡]      │   Haritaya tıkla →                               │
│          │   ┌─────────────────────────┐                   │
│ ALTLIK   │   │ 📍 Parsel: 245/12       │                   │
│ ○ Uydu   │   │ Ada: 245 | Eyyübiye     │                   │
│ ○ OSM    │   │─────────────────────────│                   │
│          │   │ [📋 Başvuru Yap]        │                   │
│ AKOS     │   │ [🤝 Ortak Kazı]        │                   │
│ ☑ Parsel │   └─────────────────────────┘                   │
│ ☑ Bina   │                                                  │
│ ☑ İlçe   │                                                  │
│ ☑ Mahall │                                                  │
│ ☐ Ada    │                                                  │
│ ☑ C.Sokak│                                                  │
│          │                                                  │
│ MAKS+    │                                                  │
│ ☑ Dgaz   │                                                  │
│ ☐ DgNode │                                                  │
│ ☐ Elektk │                                                  │
│ ☐ ElNode │                                                  │
│          │                                                  │
│ BAŞVURU  │                                                  │
│ ● Aktif  │                                                  │
│ ● Onaylı │                                                  │
└──────────┴──────────────────────────────────────────────────┤
│  37.1234° K | 38.7891° D | Zoom: 18                        │
└─────────────────────────────────────────────────────────────┘
```

### Bileşen Detayları

**1. Sol Panel (Katman Kontrol)**
- Toggle butonu (mobilde gizli, toggle ile açılır)
- Grup başlıkları: AKOS (geo4), MAKS+ (geo2)
- Her katman: checkbox + opacity slider
- Altlık seçici: Uydu (Google) / OSM (OpenStreetMap)
- Başvuru durumu filtreleme: Aktif / Onaylı / Tamamlanmış

**2. Harita Alanı**
- Leaflet 1.9.4 — tam ekran, mevcut layout içinde
- WMS katmanları (L.tileLayer wms)
- GeoJSON başvuru pin'leri (L.geoJSON + L.circleMarker)
- Popup: koordinat + parsel + ada + mahalle + butonlar
- Alt bar: koordinat gösterimi (WGS84)

**3. Popup İçeriği**
```
📍 Parsel: 245/12
Ada: 245 | Eyyübiye / Karaköprü
Mahalle: Yeşildirek
─────────────────────
[📋 Başvuru Yap] [🤝 Ortak Kazı]
```

**4. Bootstrap Modal (Başvuru Formu)**
- WFS verisi ile otomatik dolu form
- Başvuru tipi seçimi (kazi_ruhsat / ortak_kazi)
- Koordinat (readonly)
- Parsel/Ada/Mahalle bilgileri (readonly)
- Açıklama textarea
- AJAX POST → DB kaydet → toast bildirim

**5. Mobil Uyum**
- Sol panel: varsayılan gizli, hamburger/toggle ile açılır
- Harita: tam genişlik/yükseklik
- Popup: dar ekran için optimize
- Butonlar: büyük dokunmatik alan

### View Dosyası Örneği

```blade
@extends('layouts.admin')

@section('title', 'Aykome Maps')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#map { height: calc(100vh - 64px); width: 100%; }
.layer-panel { background: white; border-right: 1px solid #e2e8f0; }
</style>
@endpush

@section('content')
<div class="flex h-screen">
    <!-- Sol Panel -->
    <div id="layer-panel" class="w-72 flex-shrink-0 border-r border-slate-200 bg-white p-4 overflow-y-auto hidden lg:block">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-900">Katmanlar</h2>
            <button onclick="togglePanel()" class="lg:hidden text-slate-500">✕</button>
        </div>

        <!-- Altlık -->
        <div class="mb-4">
            <h3 class="text-xs font-bold uppercase text-slate-500 mb-2">Altlık</h3>
            <div class="space-y-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="basemap" value="google" checked class="accent-[#FA6001]">
                    <span class="text-sm">🛰️ Uydu</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="basemap" value="osm" class="accent-[#FA6001]">
                    <span class="text-sm">🗺️ OSM</span>
                </label>
            </div>
        </div>

        <!-- AKOS Grubu -->
        <div class="mb-4">
            <h3 class="text-xs font-bold uppercase text-slate-500 mb-2">AKOS (geo4)</h3>
            <div class="space-y-2">
                @foreach(['Parsel','Bina','İlçe','Mahalle','Cadde Sokak','Bağımsız'] as $layer)
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-slate-700">{{ $layer }}</span>
                    <input type="checkbox" class="accent-emerald-600" checked>
                </label>
                @endforeach
            </div>
        </div>

        <!-- MAKS+ Grubu -->
        <div class="mb-4">
            <h3 class="text-xs font-bold uppercase text-slate-500 mb-2">MAKS+ (geo2)</h3>
            <div class="space-y-2">
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-slate-700">Doğalgaz Hatları</span>
                    <input type="checkbox" class="accent-emerald-600" checked>
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-slate-700">Doğalgaz Noktaları</span>
                    <input type="checkbox" class="accent-emerald-600">
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-slate-700">Elektrik Hatları</span>
                    <input type="checkbox" class="accent-emerald-600">
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm text-slate-700">Elektrik Noktaları</span>
                    <input type="checkbox" class="accent-emerald-600">
                </label>
            </div>
        </div>
    </div>

    <!-- Harita -->
    <div class="flex-1 relative">
        <button onclick="togglePanel()" class="absolute top-4 left-4 z-[1000] bg-white p-2 rounded-lg shadow-lg lg:hidden">
            ☰
        </button>
        <div id="map"></div>
        <!-- Koordinat Bar -->
        <div class="absolute bottom-0 left-0 right-0 bg-white/90 backdrop-blur px-4 py-2 text-xs font-mono border-t border-slate-200">
            <span id="coord-display">37.123456° K | 38.789012° D | Zoom: 18</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Harita başlatma, katman yönetimi, tıklama olayları, WFS sorgusu, popup, AJAX kaydetme
</script>
@endpush
```

---

## 9. TIKLAMA AKIŞI

```
Kullanıcı Haritaya Tıklar
        │
        ▼
Leaflet 'click' olayı → lat/lng al
        │
        ▼
WFS Proxy sorgusu (GetFeature)
  • typeName: smpns:MISMAP_NUM_KADASTRO_PARSEL
  • filter: Within (tıklanan nokta parsel geometrisi içinde)
        │
        ▼
Parsel/ada/mahalle bilgisi gelir (XML/JSON)
        │
        ▼
Popup açılır:
  📍 Parsel: 245/12
  Ada: 245 | Eyyübiye
  Mahalle: Yeşildirek
  ───────────────
  [Başvuru Yap] [Ortak Kazı]
        │
        ▼
Kullanıcı butona tıklar → Bootstrap Modal açılır
        │
        ▼
Form doldurulur (WFS verisi otomatik dolu)
        │
        ▼
AJAX POST /maps/nokta-kaydet
        │
        ▼
DB kaydet → JSON yanıt
        │
        ▼
Toast bildirim (SweetAlert2) → "Nokta kaydedildi"
        │
        ▼
Gerekirse başvuru formuna yönlendir
```

---

## 10. SIDEBAR GÜNCELLEMESİ

Mevcut sidebar'da "CBS Entegrasyon" menü öğesi bulunur (`partials/sidebar.blade.php`). Bu öğe şu anda "SOON" (yakında) badge'li ve inactive durumdadır.

**Yapılacak değişiklik:**
1. SOON badge'i kaldır
2. Route'u `route('maps.index')` olarak güncelle
3. İkon ekle (harita/pin ikonu)

```blade
{{-- Mevcut (SOON) --}}
<a href="#" class="...">
    🗺️ CBS Entegrasyon
    <span class="ml-auto text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded">SOON</span>
</a>

{{-- Güncellenecek --}}
<a href="{{ route('maps.index') }}" class="...">
    🗺️ CBS Entegrasyon
</a>
```

---

## 11. YAPILACAKLAR LİSTESİ

- [ ] 1. `php artisan make:model GisBasvuruNokta -m` — Model + migration oluştur
- [ ] 2. Migration dosyasını düzenle (yukarıdaki şemaya göre)
- [ ] 3. `php artisan migrate` — Tabloyu oluştur
- [ ] 4. `app/Http/Controllers/MapsController.php` oluştur (4 metod)
- [ ] 5. Route'ları `routes/web.php`'ye ekle
- [ ] 6. `resources/views/maps/index.blade.php` — tam ekran harita view
- [ ] 7. Sol katman paneli (AKOS + MAKS+ grupları, checkbox + toggle)
- [ ] 8. WMS katmanlarını Leaflet'e ekle (L.tileLayer wms)
- [ ] 9. Harita tıklama → WFS proxy sorgusu → popup
- [ ] 10. Popup'tan başvuru/ortak kazı butonları
- [ ] 11. Bootstrap modal (form, WFS verisiyle otomatik dolu)
- [ ] 12. Modal submit → AJAX POST → DB kaydet
- [ ] 13. Mevcut başvuruları haritada pin olarak göster (GeoJSON endpoint)
- [ ] 14. Sidebar'da "CBS Entegrasyon" linkini aktif yap → `/maps`
- [ ] 15. Mobil uyum testi (panel toggle, responsive popup)
- [ ] 16. Test: harita yükleme, katman açma/kapama, tıklama akışı

---

## 12. SONRAKİ OTURUM BAŞLANGIÇ PROMPTU

Aşağıdaki metin, yeni bir Claude Code oturumunda kaldığın yerden devam etmek için kullanılmalıdır:

```
CLAUDE.md dosyasını oku. SYSTEM.md dosyasını oku. docs/AYKOME_MAPS.md dosyasını oku.

Aykome Maps modülünü geliştiriyorum. Şu adımdan devam ediyorum: [ADIM NUMARASI / AÇIKLAMA].

Son durum: [örneğin "Controller oluşturuldu, route'lar eklendi, migration çalıştırıldı" vb.]

Tamamlanan görevler:
- [ ] ...
- [ ] ...

Sıradaki görev:
- [ ] ...

Tüm dosyalar mevcut projenin kod stiline uygun olmalı (Laravel 12, TailwindCSS, Türkçe arayüz).
Sor sorma, kaldığın yerden devam et.
```

---

## NOTLAR

- WMS tile'ları doğrudan çekilebilir (CORS yok)
- WFS feature sorguları mutlaka Laravel proxy üzerinden geçmeli
- geo4/geo2 sunucularının uptime'ı bağımsız olduğundan graceful degradation gerekli
- Harita ilk yüklemede varsayılan olarak Şanlıurfa merkezinin (37.1598, 38.7969) yakınına zoom yapmalı
- Başvuru pin'leri için mevcut `applications` tablosundaki `excavation_areas` ilişkisi kullanılacak

---

*AYKOME HGB Bilişim  ULTRA SAAS v6 | CBS Entegrasyon Modülü*