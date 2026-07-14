# CLAUDE CODE — AYKOME ÇALIŞMA KURALLARI

> Her oturumda bu dosyayı ÖNCE oku, sonra işleme başla.
> Yeni oturumda SESSION_SUMMARY.md dosyasını oku.
> Model: claude-sonnet-4-5 (Minimax 2.5)

---

## 1. API LİMİT STRATEJİSİ (ÖNCELİKLİ)

- API istek sayısını minimumda tut
- Küçük cevaplar yerine büyük, eksiksiz cevaplar üret
- Birden fazla görevi tek yanıtta birleştir
- Dosyaları tek tek değil toplu oku
- Gereksiz soru sorma — makul varsayım yap ve devam et
- Her zaman kaldığın yerden devam et

---

## 2. PROJE KİMLİĞİ

**Proje:** AYKOME — Altyapı Yönetim ve Koordinasyon Merkezi  
**Ürün:** HGB Bilişim  ULTRA SAAS  
**Stack:** Laravel + Vite + TailwindCSS + PHP + JS  
**DB:** MySQL/MariaDB  
**Auth:** Laravel Auth (mevcut, dokunma)  
**Versiyon:** v6 Ultra  

**Aktif Kurumlar:** AKSA, TEDAŞ, ŞUSKİ, Türk Telekom, HGB Bilişim  Demo  
**Mevcut Modüller (dokunma):**
- Dashboard, Başvurular, Harita İzleme, Raporlar
- Gelişmiş Rapor (PRO), Zemin Tipleri, Kurumlar
- Kullanıcılar, Roller, Profil, Firmalar & Lisanslar
- Belge Ayarları, Sistem Logları, Görevlerim
- Canlı Saha İzle (PRO), Görev Emri Yönetimi (PRO)
- Gelişmiş Saha Raporu (PRO), Evrak ve Tevdi / E-B (PRO)
- Kazı Metraj Tahmini (BETA)
- CBS Entegrasyon (SOON) ← BİZ BUNU YAPIYORUZ
- E-Tebligat Servisi (SOON)

---

## 3. YENİ MODÜL: AYKOME MAPS

**Menü Adı:** CBS Entegrasyon → **Aykome Maps**  
**Route:** `/maps`  
**Badge:** Sidebar'daki "SOON" badge'i kaldırılacak, aktif yapılacak

### 3.1 Temel Mimari

```
WMS/WFS Sunucuları  =  OTOYOL   (Büyükşehir verisi, sadece görüntülüyoruz)
Bizim Database      =  ARAÇLAR  (Çizimler, başvurular — sadece bizim data)
```

- WMS tile'ları direkt çekilir (CORS yok)
- WFS feature sorguları Laravel proxy üzerinden geçer
- Çizim ve başvurular Laravel DB'ye kaydedilir

### 3.2 Koordinat Sistemi

- **WMS/WFS:** EPSG:3857 (Web Mercator)
- **Leaflet:** WGS84 lat/lng — dönüşüm gerekli
- **BBOX:** `minX,minY,maxX,maxY` EPSG:3857

---

## 4. GERÇEK WMS/WFS ENDPOINT'LERİ (DOĞRULANMIŞ)

```javascript
// SUNUCU 1 — Kadastro + Bina (geo4)
const GEO4_WMS = "https://geo4.sanliurfa.bel.tr:7171/geoserver/wms";
const GEO4_WFS = "https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs";

// SUNUCU 2 — Altyapı Şebekeleri (geo2)
const GEO2_WMS = "https://geo2.sanliurfa.bel.tr:9191/geoserver/wms";
const GEO2_WFS = "https://geo2.sanliurfa.bel.tr:9191/geoserver/wfs";

// WFS için CORS proxy (Laravel'den yazılacak)
const PROXY = "/maps/proxy?url=";
```

---

## 5. KATMAN LİSTESİ (DOĞRULANMIŞ)

### geo4 — AKOS Grubu (uID: 45)
| Layer Name | Açıklama | Varsayılan |
|---|---|---|
| `smpns:MISMAP_NUM_KADASTRO_PARSEL` | Parseller | ✅ |
| `smpns:MISMAP_NUM_BINA` | Binalar | ✅ |
| `smpns:MISMAP_NUM_ILCE` | İlçe Sınırları | ✅ |
| `smpns:MISMAP_NUM_MAHALLE` | Mahalle | ✅ |
| `smpns:MISMAP_NUM_ADA` | Ada | ❌ |
| `smpns:MISMAP_NUM_CADDESOKAK` | Cadde Sokak | ✅ |
| `smpns:MISMAP_NUM_ADRES` | Adres | ❌ |
| `smpns:MISMAP_NUM_PAFTA` | Pafta | ❌ |
| `smpns:MISMAP_NUM_BAGIMSIZ` | Bağımsız Kullanım | ✅ |

### geo2 — MAKS+ Grubu (uID: 404)
| Layer Name | Açıklama | Varsayılan |
|---|---|---|
| `smpns:AYK_DOGALGAZ_LINKS` | Doğalgaz Hatları | ✅ |
| `smpns:AYK_DOGALGAZ_NODES` | Doğalgaz Noktaları | ❌ |
| `smpns:AYK_ELEKTRIK_LINKS` | Elektrik Hatları | ❌ |
| `smpns:AYK_ELEKTRIK_NODES` | Elektrik Noktaları | ❌ |
| `smpns:METROBUS_CAD` | Metrobüs CAD | ❌ |

> ⚠️ AKOS grubundaki tahminli layer isimler network'ten teyit edilmeli.
> WFS typename formatı: `smpns:LAYER_ADI`

---

## 6. EKRAN TASARIMI

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

### Tıklama Akışı
```
Haritaya tıkla
  → WFS proxy → parsel/ada bilgisi çek
  → Popup: koordinat + parsel + ada + mahalle
  → [Başvuru Yap] veya [Ortak Kazı] butonu
  → Bootstrap Modal (form otomatik dolu)
  → Submit → AJAX → MapsController → database
```

---

## 7. DOSYA YAPISI (OLUŞTURULACAKLAR)

```
app/Http/Controllers/MapsController.php
resources/views/maps/index.blade.php
database/migrations/xxxx_create_gis_tables.php
routes/web.php  ← route eklenecek
```

### MapsController metodları
```php
index()           // Harita ana sayfası
proxy()           // WFS CORS proxy (GET /maps/proxy?url=...)
noktaKaydet()     // Haritadan seçilen nokta kaydet (POST)
basvurularGeoJson() // Başvuruları GeoJSON olarak döndür (GET)
```

---

## 8. DATABASE (YENİ TABLOLAR)

```sql
-- Haritadan seçilen başvuru noktaları
CREATE TABLE gis_basvuru_noktalar (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    basvuru_id BIGINT,
    basvuru_tipi ENUM('kazi_ruhsat','ortak_kazi'),
    lat DECIMAL(15,8),
    lng DECIMAL(15,8),
    ilce VARCHAR(100),
    mahalle VARCHAR(100),
    ada VARCHAR(50),
    parsel VARCHAR(50),
    wfs_response JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- İsteğe bağlı: harita üzerinde çizimler
CREATE TABLE gis_cizimler (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    tip ENUM('nokta','cizgi','alan'),
    geometri JSON,          -- GeoJSON
    basvuru_id BIGINT NULL,
    lat DECIMAL(15,8),
    lng DECIMAL(15,8),
    aciklama TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 9. TASARIM — MEVCUT PROJE İLE UYUMLU

- Sidebar: TailwindCSS dark theme (mevcut stil korunacak)
- Harita paneli: beyaz/light, sidebar ile kontrast
- Katman panel toggle butonu sol üstte
- Bootstrap 3.3.7 Modal (mevcut projede var)
- Leaflet 1.9.4 (CDN)
- Font Awesome 5 ikonları (mevcut projede var)
- Tüm metinler Türkçe

---

## 10. ROUTE'LAR (web.php'ye eklenecek)

```php
Route::middleware(['auth'])->prefix('maps')->name('maps.')->group(function () {
    Route::get('/',                   [MapsController::class, 'index'])->name('index');
    Route::get('/proxy',              [MapsController::class, 'proxy'])->name('proxy');
    Route::post('/nokta-kaydet',      [MapsController::class, 'noktaKaydet'])->name('noktaKaydet');
    Route::get('/basvurular-geojson', [MapsController::class, 'basvurularGeoJson'])->name('basvurularGeoJson');
});
```

---

## 11. GELIŞTIRME SIRASI

- [ ] 1. `MapsController.php` oluştur (4 metod)
- [ ] 2. Route'ları `web.php`'ye ekle
- [ ] 3. Migration oluştur ve çalıştır
- [ ] 4. `resources/views/maps/index.blade.php` — tam ekran harita view
- [ ] 5. Sol katman paneli (AKOS + MAKS+ grupları, toggle)
- [ ] 6. WMS katmanları Leaflet'e ekle
- [ ] 7. Harita tıklama → WFS proxy sorgusu → popup
- [ ] 8. Popup'tan başvuru/ortak kazı modalı
- [ ] 9. Modal submit → AJAX → DB kayıt
- [ ] 10. Mevcut başvuruları haritada pin olarak göster
- [ ] 11. Sidebar'da "CBS Entegrasyon" linkini aktif yap → `/maps`

---

## 12. BAŞLANGIÇ PROTOKOLÜ

Claude Code her oturumda şunu yap:
1. Bu dosyayı oku
2. Şu dosyaları toplu incele: `routes/web.php`, `resources/views/layouts/app.blade.php`, `app/Http/Controllers/` klasörü, `resources/views/` klasörü
3. Kaldığın görevden devam et
4. Gereksiz soru sorma

---

*AYKOME HGB Bilişim  ULTRA SAAS v6 | claude-sonnet-4-5*