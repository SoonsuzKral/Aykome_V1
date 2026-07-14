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

## Kalan İşler (Bir Sonraki Oturum)
1. Başvuru formu tasarımı derinleştirme
2. Draw report → başvuru formu entegrasyon testi
3. Migration'lar Docker'da çalıştırılmalı (OCI_DEFAULT hatası)
4. `GisKatmanAyar` modeli eksik (controller raw DB kullanıyor)

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
