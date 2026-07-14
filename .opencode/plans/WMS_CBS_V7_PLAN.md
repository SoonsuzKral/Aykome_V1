# AYKOME CBS v7 — WMS & Hat Kimliği Entegrasyon Planı

> Hedef: CBS modülünü yenilemek (geo3 WMS, 13 katman), 15m yol analizi + **Hat Kimliği** sorgulama eklemek,
> çizimleri Oracle DB'ye kaydetmek, ve aynı haritayı başvuru create/edit/show sayfalarına entegre etmek.

---

## 1. MEVCUT DURUM

| Öğe | Değer |
|---|---|
| WMS Sunucu | `geo4:7171` + `geo2:9191` (çift sunucu) |
| Proxy | `/maps/proxy?url=` (sadece geo4/geo2) |
| Layers | 21 adet (çoğu gereksiz) |
| Base Maps | Google Uydu, OSM, Topo |
| Çizim | Leaflet.Draw (nokta, çizgi, alan) |
| Veri Depolama | `gis_basvuru_noktalar` + `gis_cizimler` |
| Harita Entegrasyonu | Sadece `/maps` sayfasında |
| Hat Kimliği | Yok |

---

## 2. WMS MİMARİSİ (geo3 TEK SUNUCU)

```
ESKİ: geo4.sanliurfa.bel.tr:7171 + geo2.sanliurfa.bel.tr:9191 (çift)
YENİ: geo3.sanliurfa.bel.tr:8091/geoserver/wms (tek sunucu, 13 katman)
```

### 2.1 Yeni Katman Listesi (13 adet)

| # | Katman Adı | Layer Name | Varsayılan | Renk | Grup |
|---|---|---|---|---|---|
| 1 | Mahalle Sınırları | `cbs:MISMAP_MAHALLE_KOYLER` | ✅ | `#f97316` | İdari |
| 2 | Adalar | `cbs:MISMAP_KADASTRO_ADA` | ❌ | `#a855f7` | İdari |
| 3 | Parseller (Genel) | `smpns:MISMAP_NUM_KADASTRO_PARSEL` | ✅ | `#ef4444` | Kadastro |
| 4 | Parseller (TKGM) | `smpns:TKGM_PARSEL` | ❌ | `#22c55e` | Kadastro |
| 5 | Cadde/Sokak Hatları | `cbs:MISMAP_CADDE_SOKAK` | ❌ | `#64748b` | Adres |
| 6 | Binalar | `smpns:MISMAP_NUM_BINA` | ✅ | `#94a3b8` | Adres |
| 7 | Kapı Numaraları | `smpns:m_Numarataj` | ❌ | `#f59e0b` | Adres |
| 8 | Aykome İçmesuyu | `aykome:AYK_SU_ICMESUYU_LINKS` | ✅ | `#3b82f6` | Altyapı |
| 9 | Aykome Kanalizasyon | `aykome:AYK_SU_KANALIZASYON_LINKS` | ❌ | `#92400e` | Altyapı |
| 10 | Aykome Yağmursuyu | `aykome:AYK_SU_YAGMURSU_LINKS` | ❌ | `#67e8f9` | Altyapı |
| 11 | Aykome Elektrik | `aykome:AYK_ELEKTRIK_LINKS` | ❌ | `#eab308` | Altyapı |
| 12 | Doğalgaz (Hatlar) | `aykome:AYK_DOGALGAZ_LINKS` | ✅ | `#ef4444` | Altyapı |
| 13 | Doğalgaz (Noktalar) | `aykome:AYK_DOGALGAZ_NODES` | ❌ | `#3b82f6` | Altyapı |

### 2.2 Proxy Stratejisi

`MapsController::proxy()` hem eski geo4/geo2 hem de yeni geo3 domainlerine izin verecek.
Geriye uyum korunacak, varsayılan geo3 olacak.

---

## 3. 15 METRE YOL ANALİZİ

### 3.1 Veri Kaynağı

```
storage/shp/15_alti.js   → 15m altı yollar  (3.288 adet, ~2.7MB)  var EybAlti
storage/shp/15_ustu.js   → 15m üstü yollar  (908 adet, ~2MB)      var UrfaUstu
```

### 3.2 GeoJSON Özellikleri (Her Feature)

```javascript
{
  "properties": {
    "CADDE_SOKA": 15152,          // ═══ HAT KİMLİĞİ (Benzersiz ID) ═══
    "CADDE_SO_1": "EVREN 72",     // Cadde/Sokak Adı
    "CADDE_SO_2": "SOKAK",        // Tür (Cadde/Sokak/Bulvar)
    "ILÇE": "EYYÜBIYE",           // İlçe
    "MAHALLE_AD": "BATIKENT",     // Mahalle
    "SORUMLULUK": "İlçe Belediyesi", // Yetki (İlçe/Büyükşehir)
    "ANA__ARTER": "Hayir",        // Ana Arter mi?
    "KAPLAMA_CI": "ASFALT YOL",   // Kaplama Türü
    "ESKI_CADDE": "",             // Eski Cadde Adı
    "SERIT_SAYI": 0,              // Şerit Sayısı
    "YAYA_GEÇI": 0,               // Yaya Geçidi
    "GENISLIGI": 7,               // ═══ GENİŞLİK (metre) ═══
    "UZUNLUGU": "93,2150",        // ═══ UZUNLUK (metre) ═══
    "EGIMI": 0,                   // Eğim
    "HIZ_LIMITI": 0,              // Hız Limiti
    "KALDIRIM_T": "",             // Kaldırım Türü
    "TRAFIK_YÖ": "",              // Trafik Yönü
    "UAVT_YOL_T": "ASFALT YOL",   // UAVT Yol Türü
    "KAYIT_TARI": "08/08/2018"    // Kayıt Tarihi
  }
}
```

### 3.3 Yetki Mantığı

```
15m ALTINDAKİ yollar (GENISLIGI < 15) → İlçe Belediyesi yetkili
15m ÜSTÜNDEKİ yollar (GENISLIGI >= 15) → Büyükşehir Belediyesi yetkili
```

### 3.4 Görsel Stil

| Katman | Renk | Opaklık | Kalınlık |
|---|---|---|---|
| 15m ALTINDAKİ yollar | `#22c55e` (yeşil) | 0.6 | 4px |
| 15m ÜSTÜNDEKİ yollar | `#ef4444` (kırmızı) | 0.6 | 4px |

---

## 4. HAT KİMLİĞİ SİSTEMİ ⭐ (YENİ)

### 4.1 Nedir?

Her yolun `CADDE_SOKA` alanı benzersiz bir **Hat Kimliği (Line ID)** numarasıdır.
Bu numara üzerinden yolun tüm teknik özelliklerine (genişlik, uzunluk, kaplama, şerit sayısı, sorumluluk, vb.) erişilebilir.

### 4.2 Kullanıcı Akışı

```
Kullanıcı 15m yol katmanı açıkken haritada bir yola tıklar
  → Tıklanan noktadaki yol(lar) tespit edilir
  → Popup içinde özet bilgi:
      ┌─────────────────────────────────────┐
      │ 🛣️ HAT KİMLİĞİ: #15152              │
      │─────────────────────────────────────│
      │ Cadde/Sokak: EVREN 72 SOKAK         │
      │ Mahalle: BATIKENT | EYYÜBIYE        │
      │ Genişlik: 7 m | Uzunluk: 93,22 m   │
      │ Yetki: İlçe Belediyesi              │
      │─────────────────────────────────────│
      │ [🔍 Tümünü Göster] [📋 Başvuru Yap] │
      └─────────────────────────────────────┘
  → [Tümünü Göster] → sağ panelde detay raporu (tüm 20 property)
```

### 4.3 Nerede Kullanılır?

| Durum | Açıklama |
|---|---|
| **15m yol katmanına tıklama** | Doğrudan yol feature'a tıklandığında popup açar |
| **Çizim yapıldıktan sonra** | Kullanıcı bir alan/çizgi çizer, altındaki yollar otomatik sorgulanır |
| **Başvuru formunda** | Parsel bilgisi ile birlikte yol/hat kimliği forma eklenir |
| **Raporlarda** | Hat kimliği bazında kazı/başvuru istatistikleri |

### 4.4 Teknik Altyapı (JS)

```javascript
// maps-hatkimligi.js
const HatKimligi = {
    active: false,

    toggle() {
        this.active = !this.active;
        map.getContainer().style.cursor = this.active ? 'crosshair' : '';
    },

    onRoadClick(e) {
        if (!this.active) return;
        const props = e.target.feature.properties;
        this.showPopup(e.latlng, props);
    },

    showPopup(latlng, props) {
        // Popup: özet bilgi + Tümünü Göster + Başvuru Yap butonları
    },

    showDetail(hatKimligi) {
        fetch(`/maps/15m/sorgula?hat_kimligi=${hatKimligi}`)
            .then(r => r.json())
            .then(data => this.renderDetailPanel(data));
    },

    findRoadsUnderDrawing(geojson) {
        // turf.js intersect ile çizim altındaki yolları bul
    }
};
```

### 4.5 Hat Kimliği Butonu (UI)

Sol panelde yeni buton:
```
▸ Yol Analizi (15m)
  ☐ 15 metre ALTINDAKİ yollar (🟢)
  ☐ 15 metre ÜSTÜNDEKİ yollar (🔴)
  [🔍 Hat Kimliği Sorgula]   ← YENİ toggle
```

---

## 5. HARİTA ENTEGRASYON NOKTALARI

| Sayfa | Mode | Çizim | Hat Kimliği | Yükseklik |
|---|---|---|---|---|
| `/maps` | fullscreen | ✅ | ✅ | 100vh |
| `create.blade.php` | embedded | ✅ | ✅ | 400px |
| `edit.blade.php` | embedded | ✅ | ✅ | 400px |
| `show.blade.php` | embedded | ❌ | ✅ | 350px |

---

## 6. VERİTABANI

### 6.1 Mevcut Tablolar (Korunacak)

```sql
gis_basvuru_noktalar  -- haritadan eklenen noktalar
gis_cizimler          -- çizim geometrileri
```

### 6.2 Yeni Tablo: `gis_cizim_yol_iliskisi`

```sql
CREATE TABLE gis_cizim_yol_iliskisi (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cizim_id BIGINT NOT NULL,           -- FK → gis_cizimler.id
    hat_kimligi BIGINT NOT NULL,        -- CADDE_SOKA değeri
    yol_adi VARCHAR(200),               -- CADDE_SO_1
    yol_turu VARCHAR(50),               -- CADDE_SO_2
    mahalle VARCHAR(100),
    ilce VARCHAR(100),
    genislik DECIMAL(10,2),
    uzunluk DECIMAL(15,4),
    sorumluluk VARCHAR(100),
    properties JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (cizim_id, hat_kimligi)
);
```

---

## 7. API ENDPOINT'LERİ

| Method | Route | Controller | Açıklama |
|---|---|---|---|
| GET | `/maps` | `index()` | Harita sayfası |
| GET | `/maps/proxy` | `proxy()` | WFS proxy (geo3) |
| GET | `/maps/15m/alti` | `geoJson15Alti()` | 15m altı serve |
| GET | `/maps/15m/ustu` | `geoJson15Ustu()` | 15m üstü serve |
| GET | `/maps/15m/sorgula` | `roadQuery()` | Hat Kimliği sorgula |
| POST | `/maps/drawing/save` | `drawingSave()` | Çizim kaydet |
| PUT | `/maps/drawing/{id}` | `drawingUpdate()` | Çizim güncelle |
| DELETE | `/maps/drawing/{id}` | `drawingDelete()` | Çizim sil |
| GET | `/maps/drawing/app/{appId}` | `drawingGetByApp()` | Başvuru çizimleri |
| POST | `/maps/nokta-kaydet` | `noktaKaydet()` | Nokta kaydet |
| GET | `/maps/basvurular/geojson` | `basvurularGeoJson()` | Başvuru pinleri |
| POST | `/maps/katman/kaydet` | `katmanKaydet()` | Katman tercihi kaydet |
| GET | `/maps/katman/yukle` | `katmanYukle()` | Katman tercihi yükle |
| GET | `/maps/ara` | `search()` | Adres arama |

---

## 8. ÖN YÜZ YAPISI

### 8.1 Blade Şablonları

```
resources/views/maps/
├── index.blade.php
├── partials/
│   ├── _harita.blade.php            # ⭐ Paylaşılan component
│   ├── _sol_panel.blade.php
│   ├── _15m_analiz.blade.php
│   ├── _hat_kimligi_panel.blade.php # ⭐ Yeni
│   ├── _draw_tools.blade.php
│   ├── _popup_content.blade.php
│   └── _basvuru_formu.blade.php
```

### 8.2 JS Modülleri

```
resources/js/maps/
├── maps-core.js                # initMap, basemap
├── maps-wms.js                 # WMS katman yönetimi
├── maps-15m.js                 # 15m yol katmanı
├── maps-hatkimligi.js          # ⭐ Hat Kimliği
├── maps-draw.js                # Çizim + kaydetme
├── maps-click.js               # GetFeatureInfo
├── maps-search.js              # Adres arama
├── maps-filter.js              # Başvuru filtresi
└── maps-utils.js               # Yardımcılar
```

### 8.3 _harita Partial Parametreleri

```blade
@include('maps.partials._harita', [
    'mode' => 'embedded',
    'application' => $application ?? null,
    'drawingEnabled' => true,
    'hatKimligiEnabled' => true,
    'show15mRoads' => false,
    'height' => '400px',
    'readOnly' => false,
])
```

---

## 9. SERVICE LAYER

| Service | Metod | Açıklama |
|---|---|---|
| **MapsService** | `getWmsLayerRegistry()` | 13 katman tanımı |
| | `getDefaultLayers()` | Varsayılan katmanlar |
| | `buildWmsUrl()` | WMS tile URL |
| **RoadAnalysisService** | `get15mAlti()` | 15m altı GeoJSON |
| | `get15mUstu()` | 15m üstü GeoJSON |
| | `getRoadAtPoint(lat, lng)` | Koordinattaki yol |
| | `getRoadById(hatKimligi)` | CADDE_SOKA ile yol |
| | `getRoadsInGeoJSON(geojson)` | Çizim içindeki yollar |
| **DrawingService** | `saveDrawing(data)` | Çizim kaydet |
| | `updateDrawing(id, data)` | Çizim güncelle |
| | `deleteDrawing(id)` | Çizim sil |
| | `getByApplication(appId)` | Başvuru çizimleri |
| | `findRelatedRoads(geojson)` | Kesişen yolları bul |
| | `calculateArea(geojson)` | Alan hesapla |

---

## 10. MODELLER

### GisBasvuruNokta (MEVCUT)

```php
class GisBasvuruNokta extends Model {
    protected $casts = ['wfs_response' => 'array', 'lat' => 'float', 'lng' => 'float'];
    public function application() { return $this->belongsTo(Application::class); }
}
```

### GisCizim (YENİ model)

```php
class GisCizim extends Model {
    protected $casts = ['geometri' => 'array', 'lat' => 'float', 'lng' => 'float'];
    public function application() { return $this->belongsTo(Application::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function yolIliskileri() { return $this->hasMany(GisCizimYolIliskisi::class); }
}
```

### GisCizimYolIliskisi (YENİ)

```php
class GisCizimYolIliskisi extends Model {
    protected $casts = ['hat_kimligi' => 'integer', 'genislik' => 'float', 'properties' => 'array'];
    public function cizim() { return $this->belongsTo(GisCizim::class); }
}
```

---

## 11. UYGULAMA AŞAMALARI (10 Adım)

### 🟢 AŞAMA 1 — WMS geo3 + 13 Katman (1 saat)
- [ ] `MapsController::proxy()` — geo3 domain ekle
- [ ] WMS URL güncelle, 13 katmanlı liste
- [ ] Grup yapısı (İdari/Kadastro/Yapı/Altyapı)
- [ ] Test: tüm katmanlar görünüyor

### 🟢 AŞAMA 2 — 15m Yol Analizi (1 saat)
- [ ] `geoJson15Alti()`, `geoJson15Ustu()` metodları
- [ ] Leaflet'e L.geoJSON ile yükle
- [ ] Panel toggle + renk lejantı

### 🟢 AŞAMA 3 — Hat Kimliği Sistemi (1.5 saat) ⭐
- [ ] `maps-hatkimligi.js` modülü
- [ ] Yol click event → popup
- [ ] Detay paneli (tüm property'ler)
- [ ] Toggle buton + crosshair imleç
- [ ] `roadQuery()` endpoint

### 🟢 AŞAMA 4 — Çizim + Yol İlişkisi (1.5 saat) ⭐
- [ ] `gis_cizim_yol_iliskisi` migration + model
- [ ] turf.js intersect ile kesişen yollar
- [ ] Çizim kaydedilince otomatik yol bağlantısı

### 🟢 AŞAMA 5 — Paylaşımlı Harita Componenti (1 saat)
- [ ] `_harita.blade.php` partial
- [ ] Parametre desteği (mode, wmsLayers, hatKimligiEnabled)

### 🟢 AŞAMA 6 — Başvuru Oluştur'a Harita (1 saat)
- [ ] `create.blade.php` → `@include('maps.partials._harita')`
- [ ] Çizim → width/length/area otomatik doldurma

### 🟢 AŞAMA 7 — Başvuru Düzenle'ye Harita (1 saat)
- [ ] Mevcut çizim yükleme, güncelleme, silme
- [ ] İlişkili yolları gösterme

### 🟢 AŞAMA 8 — Başvuru Detay'da Harita (30 dk)
- [ ] Read-only harita, çizim + yol gösterimi

### 🟢 AŞAMA 9 — GetFeatureInfo + Katman Tercihleri (1 saat)
- [ ] WMS tıklama → parsel/ada/mahalle popup
- [ ] Katman tercihlerini DB'ye kaydetme

### 🟢 AŞAMA 10 — Performans (30 dk)
- [ ] Lazy load, cache, mobil uyum

---

## 12. DOSYA DEĞİŞİKLİK LİSTESİ

| Dosya | İşlem |
|---|---|
| `app/Http/Controllers/MapsController.php` | Düzenle |
| `app/Services/MapsService.php` | **YENİ** |
| `app/Services/DrawingService.php` | **YENİ** |
| `app/Services/RoadAnalysisService.php` | **YENİ** |
| `app/Models/GisCizim.php` | **YENİ** |
| `app/Models/GisCizimYolIliskisi.php` | **YENİ** |
| `routes/web.php` | Düzenle |
| `resources/views/maps/index.blade.php` | Düzenle |
| `resources/views/maps/partials/_harita.blade.php` | **YENİ** |
| `resources/views/maps/partials/_sol_panel.blade.php` | **YENİ** |
| `resources/views/maps/partials/_hat_kimligi_panel.blade.php` | **YENİ** |
| `resources/views/maps/partials/_15m_analiz.blade.php` | **YENİ** |
| `resources/views/maps/partials/_draw_tools.blade.php` | **YENİ** |
| `resources/views/admin/applications/create.blade.php` | Düzenle |
| `resources/views/admin/applications/edit.blade.php` | Düzenle |
| `resources/views/admin/applications/show.blade.php` | Düzenle |
| `resources/js/maps/maps-hatkimligi.js` | **YENİ** |
| `resources/js/maps/maps-core.js` | **YENİ** |
| `resources/js/maps/maps-15m.js` | **YENİ** |
| `resources/js/maps/maps-draw.js` | **YENİ** |
| `resources/js/maps/maps-wms.js` | **YENİ** |
| `resources/js/maps/maps-click.js` | **YENİ** |
| `resources/js/maps/maps-search.js` | **YENİ** |
| `resources/js/maps/maps-filter.js` | **YENİ** |
| `resources/js/maps/maps-utils.js` | **YENİ** |
| `database/migrations/xxxx_create_gis_cizim_yol_iliskisi_table.php` | **YENİ** |

---

## 13. TOPLAM İŞ YÜKÜ

| Aşama | Süre |
|---|---|
| 1 — WMS geo3 + 13 Katman | 1 saat |
| 2 — 15m Yol Analizi | 1 saat |
| 3 — Hat Kimliği Sistemi ⭐ | 1.5 saat |
| 4 — Çizim + Yol İlişkisi ⭐ | 1.5 saat |
| 5 — Paylaşımlı Harita Componenti | 1 saat |
| 6 — Başvuru Oluştur Entegrasyonu | 1 saat |
| 7 — Başvuru Düzenle Entegrasyonu | 1 saat |
| 8 — Başvuru Detay Entegrasyonu | 30 dk |
| 9 — GetFeatureInfo + Katman Tercihleri | 1 saat |
| 10 — Performans & İyileştirme | 30 dk |
| **TOPLAM** | **~10 saat** |

---

*Belge sonu — AYKOME CBS v7 | HGB Bilişim ULTRA SAAS*
