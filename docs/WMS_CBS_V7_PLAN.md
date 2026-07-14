# AYKOME Maps v7 — WMS/CBS Entegrasyon Planı

> Hedef: Mevcut CBS modülünün WMS altyapısını yenilemek, 15m yol analizi eklemek,
> ve aynı haritayı başvuru oluşturma/düzenleme sayfalarına entegre etmek.

---

## 1. MEVCUT DURUM

| Öğe | Değer |
|---|---|
| WMS Sunucu | `geo4.sanliurfa.bel.tr:7171` + `geo2.sanliurfa.bel.tr:9191` |
| Proxy | `/maps/proxy?url=` (sadece geo4/geo2) |
| Layers | 9 (geo4) + 12 (geo2) = 21 adet |
| Base Maps | Google Uydu, OSM, Topo |
| Çizim | Leaflet.Draw (nokta, çizgi, alan, dikdörtgen, daire) |
| Veri Depolama | `gis_basvuru_noktalar` + `gis_cizimler` (Oracle) |
| Harita Entegrasyonu | Sadece `/maps` sayfasında |

---

## 2. YENI WMS MIMARISI

### 2.1 WMS Sunucu Değişikliği

```
ESKİ: geo4.sanliurfa.bel.tr:7171 + geo2.sanliurfa.bel.tr:9191
YENİ: geo3.sanliurfa.bel.tr:8091/geoserver/wms (TEK SUNUCU)
```

**Proxy güncellemesi:** `MapsController::proxy()` — geo3 domaini eklenecek, eski geo4/geo2 korunacak (geriye uyum).

### 2.2 Yeni Katman Listesi (13 adet, seçili)

| # | Katman Adı | Layer Name | Varsayılan | Renk |
|---|---|---|---|---|
| 1 | 🏠 Mahalle Sınırları | `cbs:MISMAP_MAHALLE_KOYLER` | ✅ | `#f97316` |
| 2 | 🟪 Adalar | `cbs:MISMAP_KADASTRO_ADA` | ❌ | `#a855f7` |
| 3 | 🟥 Parseller (Genel) | `smpns:MISMAP_NUM_KADASTRO_PARSEL` | ✅ | `#ef4444` |
| 4 | 🟩 Parseller (TKGM Güncel) | `smpns:TKGM_PARSEL` | ❌ | `#22c55e` |
| 5 | 🛣️ Cadde/Sokak Hatları | `cbs:MISMAP_CADDE_SOKAK` | ❌ | `#64748b` |
| 6 | 🏢 Binalar | `smpns:MISMAP_NUM_BINA` | ✅ | `#94a3b8` |
| 7 | 1️⃣ Kapı Numaraları | `smpns:m_Numarataj` | ❌ | `#f59e0b` |
| 8 | 💧 Aykome İçmesuyu | `aykome:AYK_SU_ICMESUYU_LINKS` | ✅ | `#3b82f6` |
| 9 | 🟫 Aykome Kanalizasyon | `aykome:AYK_SU_KANALIZASYON_LINKS` | ❌ | `#92400e` |
| 10 | 🌩️ Aykome Yağmursuyu | `aykome:AYK_SU_YAGMURSU_LINKS` | ❌ | `#67e8f9` |
| 11 | ⚡ Aykome Elektrik | `aykome:AYK_ELEKTRIK_LINKS` | ❌ | `#eab308` |
| 12 | 🔥 Doğalgaz (Hatlar) | `aykome:AYK_DOGALGAZ_LINKS` | ✅ | `#ef4444` |
| 13 | 🔵 Doğalgaz (Noktalar/Node) | `aykome:AYK_DOGALGAZ_NODES` | ❌ | `#3b82f6` |

### 2.3 Katman Grupları (Sol Panel)

```
▸ Altlık Harita (basemap radio)
  ○ Google Uydu (varsayılan)
  ○ OpenStreetMap
  ○ Topoğrafya

▸ İdari Sınırlar
  ☑ Mahalle Sınırları
  ☐ Adalar

▸ Kadastro & Parseller
  ☑ Parseller (Genel)
  ☐ Parseller (TKGM Güncel)

▸ Yapı & Adres
  ☑ Binalar
  ☐ Kapı Numaraları
  ☐ Cadde/Sokak Hatları

▸ Altyapı Şebekeleri
  ☑ Aykome İçmesuyu
  ☐ Aykome Kanalizasyon
  ☐ Aykome Yağmursuyu
  ☐ Aykome Elektrik
  ☑ Doğalgaz (Hatlar)
  ☐ Doğalgaz (Noktalar/Node)

▸ Yol Analizi (15m)
  ☐ 15 metre ALTINDAKİ yollar (🟢 yeşil)
  ☐ 15 metre ÜSTÜNDEKİ yollar (🔴 kırmızı)

▸ Çizim Araçları
  [📍 Nokta] [📏 Çizgi] [⬡ Alan]
  [▭ Dikdörtgen] [⭕ Daire] [🔘 İşaret]
  [🗑️ Temizle]

▸ Başvuru Filtresi
  ☑ Beklemede  ☑ Onaylandı  ☑ Saha
  ☑ Ödeme      ☑ Tamamlandı ☑ Red
```

---

## 3. 15 METRE YOL ANALİZİ

### 3.1 Veri Kaynağı

```
storage/shp/15_alti.js   → 15m altı yollar (GeoJSON)
storage/shp/15_ustu.js   → 15m üstü yollar (GeoJSON)
```

Bu dosyalar Eyyübiye sınırları içindeki yolları içerir.

### 3.2 Yükleme Stratejisi

JS dosyaları doğrudan Leaflet'e `L.geoJSON()` ile yüklenir.
Boyutları ~2MB olduğundan:

- **Local (dev):** `<script src="{{ asset('storage/shp/15_alti.js') }}">` ile direkt yükle
- **Production:** Laravel controller'dan serve et veya dosyaları chunks'a böl
- Alternatif: Dosyaları `public/storage/shp/` altına symlinkle

### 3.3 Görsel Stil

| Katman | Renk | Opaklık | Kalınlık |
|---|---|---|---|
| 15m ALTINDAKİ yollar | `#22c55e` (yeşil) | 0.6 | 4px |
| 15m ÜSTÜNDEKİ yollar | `#ef4444` (kırmızı) | 0.6 | 4px |

### 3.4 Yetki Mantığı (Bilgi Amaçlı)

```
15m ALTINDAKİ yollar → İlçe Belediyesi (Eyyübiye) yetkili
15m ÜSTÜNDEKİ yollar → Büyükşehir Belediyesi yetkili
```

Panelde kullanıcıya bu bilgi gösterilecek, uyarı/renk kodu ile.

---

## 4. HARİTA ENTEGRASYON NOKTALARI

Aynı harita motoru 3 yerde çalışacak:

### 4.1 `/maps` — CBS Entegrasyon (Tam Sayfa)

- Mevcut yapı korunacak
- WMS URL + layer listesi güncellenecek
- 15m katmanı eklenecek

### 4.2 Başvuru Oluştur (`/admin/applications/create`)

**Ekran:** Sağ tarafta küçük harita paneli
**Özellikler:**
- Basemap seçimi (Google/OSM)
- WMS katmanları (sadece parsel/bina/mahalle gibi temel olanlar)
- Çizim araçları (alan, dikdörtgen, nokta)
- Çizim sonrası: width_m, length_m, total_area_m2 otomatik doldurma
- Adres bilgisi otomatik çözümleme (ters geocode)

**Uygulama:**
Harita için ayrı bir Blade partial oluşturulacak:
```
resources/views/maps/partials/_harita.blade.php
```
Bu partial hem create hem edit hem de `/maps` sayfasında include edilecek.

**Partial parametreleri:**
```blade
@include('maps.partials._harita', [
    'mode' => 'embedded',    // embedded | fullscreen
    'application' => null,   // edit'te mevcut başvuru
    'drawingEnabled' => true, // çizim araçları açık/kapalı
    'height' => '400px',     // embedded yükseklik
])
```

### 4.3 Başvuru Düzenle (`/admin/applications/{id}/edit`)

- `_harita.blade.php` partial'ı kullanılacak
- **Mevcut çizimler haritada gösterilecek:**
  - Daha önce çizilen polygon/alan GeoJSON'dan yüklenir
  - width_m / length_m / total_area_m2 değerleri forma yansıtılır
  - Kullanıcı çizimi düzenleyebilir/silebilir
- `application` parametresi ile mevcut veri yüklenir

### 4.4 Başvuru Detay (`/admin/applications/{id}/show`)

- Okuma-only harita
- Mevcut çizim + başvuru noktası gösterilir
- WMS katmanları isteğe bağlı açılır

---

## 5. VERITABANI & API

### 5.1 Mevcut Tablolar (Korunacak)

```sql
gis_basvuru_noktalar  -- haritadan eklenen noktalar
gis_cizimler          -- çizim geometrileri
```

### 5.2 API Endpoint'leri (Mevcut + Yeni)

| Method | Route | Açıklama |
|---|---|---|
| GET | `/maps` | Harita sayfası |
| GET | `/maps/proxy?url=` | WFS CORS proxy |
| POST | `/maps/nokta-kaydet` | Nokta/çizim kaydet |
| GET | `/maps/basvurular-geojson` | Başvuruları GeoJSON olarak al |
| GET | `/maps/proxy/geo3?url=` | **YENİ:** geo3 proxy endpoint'i |
| GET | `/maps/15m-alti` | **YENİ:** 15m altı GeoJSON serve et |
| GET | `/maps/15m-ustu` | **YENİ:** 15m üstü GeoJSON serve et |
| POST | `/maps/drawing/save` | **YENİ:** Çizimi başvuruya bağla |
| GET | `/maps/drawing/{applicationId}` | **YENİ:** Başvuru çizimini getir |

---

## 6. ÖN YÜZ YAPISI

```
resources/views/maps/
├── index.blade.php              # Ana CBS sayfası (full)
├── partials/
│   ├── _harita.blade.php        # Paylaşılan harita component
│   ├── _sol_panel.blade.php     # Katman kontrol paneli
│   ├── _draw_tools.blade.php    # Çizim araçları
│   ├── _15m_analiz.blade.php    # 15m analiz toggle
│   └── _basvuru_formu.blade.php # Başvuru form modalı
└── js/
    ├── maps-core.js             # Ortak harita motoru
    ├── maps-layers.js           # WMS katman yönetimi
    ├── maps-draw.js             # Çizim yönetimi
    ├── maps-15m.js              # 15m analiz katmanı
    └── maps-search.js           # Adres arama
```

---

## 7. UYGULAMA SIRASI (AŞAMALAR)

### 🟢 AŞAMA 1 — WMS Altyapı Değişikliği

- [ ] `MapsController::proxy()` — geo3 domaini ekle
- [ ] `maps/index.blade.js` — WMS URL'yi `geo3` olarak güncelle
- [ ] Layer listesini 13 katman ile değiştir (wms_v1.html'deki)
- [ ] Grup yapısını güncelle (İdari/Kadastro/Yapı/Altyapı)
- [ ] Test: tüm katmanlar haritada görünüyor

### 🟢 AŞAMA 2 — 15m Yol Analizi

- [ ] `MapsController`'a `geoJson15Alti()` ve `geoJson15Ustu()` metodları ekle
- [ ] `storage/shp/15_alti.js` ve `15_ustu.js` dosyalarını serve et
- [ ] Leaflet'e `L.geoJSON()` ile yükle
- [ ] Panelde toggle checkbox ekle
- [ ] Lejant/renk açıklaması ekle
- [ ] Test: 15m altı/üstü yollar doğru renkte gösteriliyor

### 🟢 AŞAMA 3 — Paylaşımlı Harita Componenti

- [ ] `_harita.blade.php` partial'ı oluştur
- [ ] JS core fonksiyonlarını partial'a taşı (initMap, katman yönetimi)
- [ ] `mode` parametresi ile embedded/fullscreen desteği
- [ ] Yükseklik/genişlik parametreleri

### 🟢 AŞAMA 4 — Başvuru Oluştur'a Harita Ekle

- [ ] `create.blade.php`'ye `@include('maps.partials._harita')` ekle
- [ ] Çizim sonrası width/length/area alanlarını otomatik doldur
- [ ] Koordinat ve adres bilgisini forma aktar
- [ ] Test: yeni başvuru oluştururken haritadan çizim yapılabiliyor

### 🟢 AŞAMA 5 — Başvuru Düzenle'ye Harita Ekle

- [ ] `edit.blade.php`'ye harita partial'ını ekle
- [ ] Mevcut çizimi GeoJSON'dan yükle
- [ ] Çizim güncelleme/silme desteği
- [ ] Değişiklikleri kaydetme API'si
- [ ] Test: mevcut başvuru düzenlenirken çizim görünüyor ve güncellenebiliyor

### 🟢 AŞAMA 6 — Başvuru Detay'da Harita

- [ ] `show.blade.php`'ye harita partial'ını ekle (read-only mode)
- [ ] Çizim ve başvuru noktası göster
- [ ] WMS katman toggle (isteğe bağlı)

### 🟢 AŞAMA 7 — WFS GetFeatureInfo (Tıklama Sorgusu)

- [ ] WMS tıklama → GetFeatureInfo sorgusu (wms_v1.html'deki gibi)
- [ ] Popup içinde parsel/ada/mahalle bilgisi
- [ ] "Başvuru Yap" butonu popup içinde (mevcut mantık)

### 🟢 AŞAMA 8 — Performans & İyileştirme

- [ ] 15m JS dosyaları için lazy load (boyut ~2MB)
- [ ] WMS tile cache süresi ayarla
- [ ] Leaflet.Draw yerine daha hafif bir çizim kütüphanesi değerlendir
- [ ] Mobil uyumluluk testi

---

## 8. DOSYA DEĞİŞİKLİK LİSTESİ

| Dosya | İşlem |
|---|---|
| `app/Http/Controllers/MapsController.php` | Düzenle (proxy + 15m serve + drawing save) |
| `app/Models/GisBasvuruNokta.php` | İncele (gerekirse alan ekle) |
| `app/Models/GisCizim.php` | İncele/oluştur |
| `routes/web.php` / `routes/admin.php` | Düzenle (yeni route'lar) |
| `resources/views/maps/index.blade.php` | Düzenle (WMS + 15m) |
| `resources/views/maps/partials/_harita.blade.php` | **YENİ** |
| `resources/views/maps/partials/_sol_panel.blade.php` | **YENİ** (opsiyonel) |
| `resources/views/admin/applications/create.blade.php` | Düzenle (harita partial) |
| `resources/views/admin/applications/edit.blade.php` | Düzenle (harita partial) |
| `resources/views/admin/applications/show.blade.php` | Düzenle (harita partial) |
| `storage/shp/15_alti.js` | Mevcut (serve edilecek) |
| `storage/shp/15_ustu.js` | Mevcut (serve edilecek) |
| `database/migrations/xxxx_create_gis_tables.php` | İncele (gerekirse migration) |

---

## 9. TEKNİK NOTLAR

### 9.1 WMS Tile URL Formatı (geo3)
```
https://geo3.sanliurfa.bel.tr:8091/geoserver/wms?
  SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap
  &LAYERS=LAYER_ADI
  &STYLES=&FORMAT=image/png&TRANSPARENT=TRUE
  &SRS=EPSG:3857&WIDTH=256&HEIGHT=256
  &BBOX={bbox-epsg-3857}
```

### 9.2 WFS GetFeatureInfo (Tıklama)
```
https://geo3.sanliurfa.bel.tr:8091/geoserver/wms?
  SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo
  &LAYERS=...&QUERY_LAYERS=...
  &BBOX=...&WIDTH=...&HEIGHT=...
  &X=...&Y=...
  &INFO_FORMAT=application/json&SRS=EPSG:4326
  &FEATURE_COUNT=10&BUFFER=20
```

### 9.3 Koordinat Sistemi
- WMS: EPSG:3857 (Web Mercator)
- Leaflet: WGS84 (lat/lng)
- GeoJSON: EPSG:4326 (WGS84)
- 15m verisi: EPSG:4326

### 9.4 15m Veri Yükleme (Performans)
15_alti.js (~2.7MB) ve 15_ustu.js (~2MB) için:
```js
// Lazy load ile sayfa açılış hızı korunur
fetch('/maps/15m-alti')
  .then(r => r.json())
  .then(data => {
    L.geoJSON(data, {
      style: { color: '#22c55e', weight: 4, opacity: 0.6 }
    }).addTo(mapsMap);
  });
```

---

## 10. TEST SENARYOLARI

- [ ] Tüm WMS katmanları haritada görüntüleniyor
- [ ] Katman toggle çalışıyor (checkbox aç/kapa)
- [ ] Opaklık slider'ı çalışıyor
- [ ] Basemap değiştirme (Google/OSM/Topo) çalışıyor
- [ ] 15m altı/üstü katmanları doğru renk ve konumda
- [ ] Harita tıklama → GetFeatureInfo → Popup gösteriyor
- [ ] Çizim araçları çalışıyor (nokta/çizgi/alan/dikdörtgen/daire)
- [ ] Çizim → Parsel tablosu gösteriyor
- [ ] Başvuru oluştur sayfasında harita çalışıyor
- [ ] Başvuru düzenle sayfasında mevcut çizim görünüyor
- [ ] Başvuru detay sayfasında harita görünüyor (read-only)
- [ ] Proxy WFS sorgusu çalışıyor
- [ ] Mobil görünümde sol panel düzgün çalışıyor
