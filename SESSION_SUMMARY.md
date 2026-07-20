# AYKOME CBS — Oturum Özeti (20 Temmuz 2026)

## Yapılan İşlemler

### v7.7 — Rotasyon Stabilizasyonu + Docker Security Hardening
- **Rotasyon nihai çözüm**: `leaflet-rotate` v0.2.8 kalıcı; `mousedown` bearing kaydet+sıfırla (Leaflet koordinat sistemi bozulmasın diye), `click` microtask ile paint öncesi restore, `dragend` normal restore
- **3 başarısız deneme**: `predrag` reset, capture-phase mousemove, pure CSS rotate wrapper — hepsi tile kaymasına/sekmeye yol açtı
- **Pure CSS rotate denendi** → "daha berbat oldu" → `08e5398` commit'ine revert
- **Docker port güvenliği**: Oracle 1521, Redis 6379, Adminer 8080 → `ports` → `expose` (sadece container içi)
- **network_mode:host kaldırıldı** (Windows Docker Desktop uyumluluk)
- **Caddy eklendi**: 80/443, Let's Encrypt TLS otomatik, `aykome.eyyubiye.bel.tr → serve:8001`
- **Güvenlik**: `db-switch` rotalarına `auth` + `role:super-admin` middleware
- **`.env.production` oluşturuldu**: APP_DEBUG=false, APP_FORCE_HTTPS=true, SESSION_DRIVER=redis
- **Dockerfile fix**: `arm64` → `x64` (Windows Server 2025)

### Kalan İşler
1. Sunucu deployment: `git clone` + `docker-compose up -d` + `php artisan migrate` + `npm run build`
2. Maps'i Başvurular/Harita İzleme'ye Docker mikroservis entegrasyonu
3. `gis_basvuru_noktalar` → `durum` kolonu ekle
4. PDF şablonları (ruhsat, ön yazı, tahsilat)
5. E-imza + kazı metraj tahmini
