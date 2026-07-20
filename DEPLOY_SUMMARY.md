# DEPLOYMENT ÖZETİ — 20 Temmuz 2026

## Durum
- Docker compose ayakta: Oracle, Redis, Adminer, PHP, Serve, Reverb, Caddy
- `http://localhost:8001` açılıyor
- `.env` SESSION_DRIVER=redis, CACHE_STORE=redis, QUEUE_CONNECTION=redis
- DB_HOST=oracle, REDIS_HOST=redis

## Sorun
- Oracle'da `USERS` tablosu yok → login olmuyor
- `php artisan migrate` dediğinde "Nothing to migrate" diyor
- Büyük ihtimal migrations tablosu dolu ama tablolar yok

## Çözüm (Sırasıyla Yapılacak)

### 1. Oracle tablolarını kontrol et
```bash
docker exec aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 <<< "SELECT table_name FROM user_tables ORDER BY table_name;"
```

### 2. Migration'ları zorla sıfırdan çalıştır
```bash
docker exec aykome-v6-php php artisan migrate:fresh --force --seed
```

### 3. Test et
Tarayıcı: `http://localhost:8001`
Giriş: admin@aykome.local / varsayılan şifre

### 4. Oracle durum kolonu ekle (opsiyonel)
```bash
docker exec aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 <<< "ALTER TABLE GIS_BASVURU_NOKTALAR ADD (DURUM VARCHAR2(20) DEFAULT 'aktif');"
```

### 5. Adminer (Oracle web GUI)
`http://localhost:8080`

Sistem: Oracle, Sunucu: oracle, Kullanıcı: aykome_user, Şifre: aykome123, Veritabanı: FREEPDB1

---

## Kalan İşler (Sıralı)
1. Migration'ları çalıştır (Oracle tabloları oluşsun)
2. Maps'i Başvurular/Harita İzleme'ye entegre et
3. PDF şablonları (ruhsat belgesi, ön yazı, tahsilat)
4. E-imza entegrasyonu
5. Kazı metraj tahmini
