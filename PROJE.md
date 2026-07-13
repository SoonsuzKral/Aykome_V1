# AYKOME — Altyapı Yönetim ve Koordinasyon Merkezi

**Ürün:** HGB Bilişim ULTRA SAAS
**Versiyon:** v6

## Nedir?

Belediye merkezli, kurumların (TEDAŞ, ŞUSKİ, AKSA, Türk Telekom) kazı/altyapı başvurusu açabildiği, harita üzerinden alan çizimi yapılan, ücret hesaplanan, makbuzla onaylanan, görev devri ve saha kontrolü bulunan lisans bazlı kurumsal bir altyapı izin yönetim sistemi.

## Tech Stack

| Bileşen | Teknoloji |
|---------|-----------|
| Backend | Laravel 12 / PHP 8.2 |
| Frontend | Vue 3 + Inertia.js 2 |
| CSS | TailwindCSS 3 |
| Derleme | Vite 7 |
| DB | MySQL / MariaDB |
| Cache/Queue | Redis |
| Real-time | Laravel Reverb (WebSocket) |
| Broadcast | Pusher JS / Laravel Echo |
| Harita (mevcut) | Google Maps API |
| Harita (yeni) | Leaflet + WMS/WFS (CBS) |
| Yetki | Spatie Laravel Permission |
| Medya | Spatie Laravel Medialibrary |
| PDF | barryvdh/laravel-dompdf |
| Auth | Laravel Sanctum + Session |
| Oracle DB | yajra/laravel-oci8 |
| Route->JS | tightenco/ziggy |

## Öne Çıkan Paketler

- `laravel/reverb` — WebSocket sunucusu (anlık bildirim, canlı harita)
- `redis` — Kuyruk işleme, önbellek, Reverb scaling
- `spatie/laravel-permission` — Rol ve izin yönetimi
- `inertiajs/inertia-laravel` — SPA benzeri sayfa geçişleri

## Geliştirme Ortamı

```bash
# Tüm servisleri aynı anda çalıştır
composer dev
# => php artisan serve + queue:listen + pail (logs) + vite
```

## Lisans

Domain/IP bağımsız, veritabanı tabanlı lisans sistemi.
