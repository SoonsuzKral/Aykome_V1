# AYKOME CBS — Oturum Özeti (14 Temmuz 2026)

## Yapılan İşlemler

### v7.2 — Draggable + Cascading
- **Modal Drag**: `makeDraggable()` fonksiyonu ile 3 panel (draw-report, hat-kimligi, basvuru) serbest sürüklenebilir
- **Cascading Selection**: Draw report'da parsel → cadde/sokak → kapı no aşamalı seçim
- **Loading Overlay**: `showLoadingOverlay()` / `hideLoadingOverlay()` + blur animasyon
- **CQL Filter Fix**: Ada/Parsel aramasında 400 hatası için tek `encodeURIComponent`

### v7.3 — WFS Sadeleştirme + WMS GetFeatureInfo
- **WFS 4'ten 2'ye düşürüldü**: Sadece `KADASTRO_PARSEL` + `MISMAP_NUM_BINA` (geo4:7171)
  - `cbs:MISMAP_CADDE_SOKAK` → kaldırıldı (WFS yok, 400 hatası)
  - `smpns:m_Numarataj` / `smpns:MISMAP_NUM_ADRES` → kaldırıldı (WFS yok, 400/400 hatası)
- **Cadde/Sokak**: Parsel WFS property'sinden çekilir (`CADDE_SO_1` + `CADDE_SO_2`)
- **Kapı No (Numarataj)**: Tamamen kaldırıldı (sunucuda WFS layer'ı yayınlanmamış)
- **WMS GetFeatureInfo**: Harita tıklamada Ada/Parsel/İlçe/Mahalle direkt WMS'den
- **Nominatim fallback**: WMS başarısız olursa OSM reverse geocode
- **Popup**: Ada/Parsel bilgisi gösterilir
- **OpenPanel kaldırıldı**: Çizim sonrası direkt loading overlay + draw report açılır
- **Aşamalı seçim**: Parsel seç → cadde seç (2 aşama, kapı no yok)

### v7.4 — WMS GetFeatureInfo + Draw Report Akış Düzeltme
- **handleDrawCreated temizlendi**: İnline WFS parsel sorgusu + sidebar tablosu kaldırıldı (draw report'a devredildi)
- **WFS 2 aşamalı**: Parsel (count 1000) + Bina → WMS GetFeatureInfo ile numarataj takviyesi
- **WMS GetFeatureInfo**: Parsel centroid noktalarından (en fazla 30) `m_Numarataj`, `CADDE_SOKAK`, `MISMAP_NUM_BINA` sorgusu
- **Kapı No**: Draw report'ta `🚪 KAPI_NO` gösterilir
- **Akış**: Çizim → Draw Report (parsel+cadde+kapi+bina) → Kullanıcı onay → Başvuru formu

### v7.5 — Draw Report Yeniden Yapılandırma (Kapı Seçimi + Yol Hat Adımı)
- **Kapı Numaraları**: Artık seçilebilir checkbox (önceden sadece text idi)
- **Bina Adı**: `BINA_ADI` kapı no yanında gösterilir
- **Tümünü Seç**: Her cadde için toplu seçim/kaldırma butonu (`toggleDrKapiAll`)
- **Yol Hat Sorgula** ayrı adım: cadde/kapı seçimi → [🔍 Yol Hat Sorgula] → [📝 Başvuruya İlerle]
- **`afterDrawCheck`**: Altyapı sorgusu kaldırıldı (yol hat adımına taşındı)
- **Başvuru formu**: Kapı no + bina adı bilgileri artık forma aktarılır
- **`clearDrawing`**: Yeni global state'ler (`_drKapiSecili`, `_yolHatSorgulandi` vs.) sıfırlanır

## Kalan İşler (Bir Sonraki Oturum)
1. Migration'lar Docker'da çalıştırılmalı (OCI_DEFAULT hatası)
2. `GisKatmanAyar` modeli eksik (controller raw DB kullanıyor)
3. ionCube + custom lisans sistemi kurulumu

## Dosya Yapısı
- `resources/views/maps/index.blade.php` (~2000 satır) — tüm CBS UI + JS
- `app/Http/Controllers/MapsController.php` (548 satır) — proxy, drawing CRUD, katman, arama
- `app/Models/GisBasvuruNokta.php` — başvuru noktaları
- `app/Models/GisCizim.php` — çizimler
- `app/Models/GisCizimYolIliskisi.php` — çizim-yol ilişkisi
- `app/Services/DrawingService.php` — findRelatedRoads, save/update/delete drawing
- `routes/web.php` — maps route group (16 endpoint)
- `tests/Feature/MapsControllerTest.php` — 17 test ✅

## Komutlar
```bash
# Test
php vendor/bin/phpunit tests/Feature/MapsControllerTest.php

# Cache temizleme
php artisan view:clear

# Migration (Docker'da)
docker exec -it $(docker ps -qf "name=laravel") php artisan migrate
```

## Notlar
- WMS sunucu: `geo3.sanliurfa.bel.tr:8091` (tüm WMS layer'ları sorunsuz çalışır)
- WFS sunucu: `geo4.sanliurfa.bel.tr:7171` (sadece KADASTRO_PARSEL + BINA çalışır)
- Proxy: `GET /maps/proxy?url=...` (CORS bypass, domain whitelist)
- 17 test, 38 assertion, tamamı geçiyor
