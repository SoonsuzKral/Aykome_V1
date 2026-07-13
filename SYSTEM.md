# AYKOME SİSTEM DOKÜMANI

---

## 1. PROJENİN AMACI

**AYKOME** (Altyapı Yönetim ve Koordinasyon Merkezi), belediye merkezli çalışan, kurumların yetkileri doğrultusunda kazı ve altyapı başvurusu açabildiği, harita üzerinden alan çizimi yapılan, ücret hesaplanan, makbuzla onaylanan, görev devri ve saha kontrolü bulunan, lisans bazlı kurumsal bir altyapı izin yönetim sistemidir.

### Sistem Hiyerarşisi
**Belediye → Kurumlar (TEDAŞ, ŞUSKİ, AKSA vb.) → Vatandaş**

### Aktif Kurumlar
- AKSA
- TEDAŞ
- ŞUSKİ
- Türk Telekom
- HGB Bilişim  Demo

### Temel İş Akışı
1. Başvuru oluşturma (kurum personeli veya belediye)
2. Harita üzerinden kazı alanı belirleme (Google Maps API)
3. Zemin tipi ve fiyatlandırma hesaplama
4. Ödeme ve makbuz onayı
5. Ruhsat üretimi (PDF)
6. Görev devri ve saha kontrolü (3 aşama: kazı öncesi, kazı sonrası, zemin onarım sonrası)

---

## 2. TEKNİK STACK

### Backend
| Bileşen | Versiyon |
|---------|----------|
| Laravel | 12.0 |
| PHP | 8.2 |
| MySQL/MariaDB | - |

### Frontend
| Bileşen | Versiyon |
|---------|----------|
| Vue.js | 3.4.0 |
| Inertia.js | 2.0.0 |
| TailwindCSS | 3.2.1 |
| Vite | 7.0.7 |
| Axios | 1.11.0 |

### Paketler (composer.json)
- `laravel/framework: ^12.0`
- `inertiajs/inertia-laravel: ^2.0`
- `spatie/laravel-permission: ^6.25`
- `spatie/laravel-medialibrary: ^11.21`
- `barryvdh/laravel-dompdf: ^3.1`
- `laravel/sanctum: ^4.0`
- `laravel/reverb: ^1.10`
- `tightenco/ziggy: ^2.0`

### Harita
- Google Maps API (mevcut)
- **Leaflet** (Aykome Maps modülü için eklenecek)

---

## 3. KLASÖR YAPISI

```
Aykome/
├── app/
│   ├── Console/
│   │   └── Kernel.php
│   ├── Enums/
│   ├── Events/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── ApplicationsController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   ├── InstitutionController.php
│   │   │   │   ├── LicenseController.php
│   │   │   │   ├── SurfaceTypeController.php
│   │   │   │   ├── MapMonitorController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── FieldTaskController.php
│   │   │   │   ├── MyTasksController.php
│   │   │   │   ├── LiveMapController.php (PRO)
│   │   │   │   ├── WorkOrderController.php (PRO)
│   │   │   │   ├── FieldReportController.php (PRO)
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   └── SettingsController.php
│   │   │   ├── ProfileController.php
│   │   │   └── ApplicationController.php
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Notifications/
│   ├── Providers/
│   ├── Services/
│   │   ├── ApplicationService.php
│   │   ├── PricingService.php
│   │   ├── MapDrawingService.php
│   │   ├── LicenseService.php
│   │   ├── TaskTransferService.php
│   │   ├── AuditLogger.php
│   │   └── ...
│   └── View/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   ├── views/
│   │   ├── admin/
│   │   ├── frontend/
│   │   ├── layouts/
│   │   ├── partials/
│   │   └── docs/
│   └── ...
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── auth.php
├── storage/
├── tests/
├── vendor/
├── .env.example
├── composer.json
├── package.json
├── Aykome.md
└── SYSTEM.md (bu dosya)
```

---

## 4. MEVCUT MODÜLLER

### Route Yapısı (routes/admin.php)

| Modül | Route | Controller | Durum |
|-------|-------|------------|-------|
| Dashboard | `/admin` | DashboardController | ✅ Aktif |
| Başvurular | `/admin/applications` | ApplicationsController | ✅ Aktif |
| Harita İzleme | `/admin/map` | MapMonitorController | ✅ Aktif |
| Raporlar | `/admin/reports` | ReportController | ✅ Aktif |
| Gelişmiş Rapor (PRO) | `/admin/reports/advanced` | ReportController | ✅ PRO |
| Zemin Tipleri | `/admin/surface-types` | SurfaceTypeController | ✅ Aktif |
| Kurumlar | `/admin/institutions` | InstitutionController | ✅ Aktif |
| Kullanıcılar | `/admin/users` | UserController | ✅ Aktif |
| Roller | `/admin/roles` | RoleController | ✅ Aktif |
| Lisanslar | `/admin/licenses` | LicenseController | ✅ Aktif |
| Profil | `/admin/profile` | ProfileController | ✅ Aktif |
| Bildirimler | `/admin/notifications` | NotificationController | ✅ Aktif |
| Görevlerim | `/admin/my-tasks` | MyTasksController | ✅ Aktif |
| Sistem Logları | `/admin/logs` | AuditLogController | ✅ Super Admin |
| Ayarlar | `/admin/settings/permit` | SettingsController | ✅ Super Admin |
| Görev Emri Yönetimi (PRO) | `/admin/work-orders` | WorkOrderController | ✅ PRO |
| Gelişmiş Saha Raporu (PRO) | `/admin/field-reports-pro` | FieldReportController | ✅ PRO |
| Canlı Saha İzleme (PRO) | `/admin/live-map-pro` | LiveMapController | ✅ PRO |
| E-Belge / Evrak (PRO) | `/admin/e-document` | - | ✅ PRO |
| Harita Test | `/admin/map-test` | - | ✅ Test |

### Sidebar Menü (app.blade.php üzerinden yüklenen)

- Dashboard
- Başvurular
- Harita İzleme
- Raporlar
- Zemin Tipleri
- Kurumlar
- Kullanıcılar
- Roller
- Lisanslar
- Görevlerim
- Sistem Logları (Super Admin)
- **CBS Entegrasyon** → `/maps` (SOON - aktif yapılacak)

### Lisans Modülleri (license middleware kontrolü)
- `applications` - Başvuru modülü
- `map` - Harita izleme
- `reports` - Raporlar
- `pro.work_orders` - Görev emri (PRO)
- `pro.field_reports` - Saha raporu (PRO)
- `pro.live_map` - Canlı harita (PRO)
- `pro.evrak_tevdi` - E-belge (PRO)
- `pro.advanced_reports` - Gelişmiş raporlar (PRO)

---

## 5. DATABASE TABLOLARI

### Migration'lardan Çıkarılan Tablolar

| Tablo Adı | Açıklama |
|-----------|----------|
| `users` | Kullanıcılar (name, email, password, institution_id, phone, national_id, is_active) |
| `password_reset_tokens` | Şifre sıfırlama token'ları |
| `sessions` | Oturum tablosu |
| `permissions` | Spatie Permission - izinler |
| `roles` | Spatie Permission - roller |
| `model_has_permissions` | Spatie Permission - model-permission eşleştirme |
| `model_has_roles` | Spatie Permission - model-role eşleştirme |
| `role_has_permissions` | Spatie Permission - role-permission eşleştirme |
| `institutions` | Kurumlar (name, slug, color_code, is_municipality) |
| `licenses` | Lisanslar (license_key, owner_name, valid_from, valid_until, is_active, modules, user_limit) |
| `surface_types` | Zemin tipleri (name, price_per_m2, active) |
| `media` | Medya dosyaları (Spatie MediaLibrary) |
| `applications` | Başvurular (application_no, institution_id, status, applicant bilgileri, excavation_reason, work_type, start/end_date, total_area_m2, total_price, payment_status, approval_status, address_text, license_document_path) |
| `excavation_areas` | Kazı alanları (application_id, polygon_geojson, total_area_m2, center_lat, center_lng, address_text) |
| `application_surface_areas` | Başvuru zemin alanları (application_id, surface_type_id, width_m, length_m, quantity, multiplier, amount) |
| `receipts` | Makbuzlar (application_id, uploaded_by, status, reviewed_by, reviewed_at, review_notes) |
| `field_tasks` | Saha görevleri (application_id, assigned_to, assigned_by, status, due_date, notes) |
| `field_task_media` | Saha görev medyası (field_task_id, step, image_path, caption) |
| `application_timeline_logs` | Başvuru zaman çizelgesi (application_id, user_id, action, meta, message) |
| `notifications` | Bildirimler (Laravel notifications) |
| `audit_logs` | Sistem logları (user_id, user_name, user_role, action, subject_type, subject_id, description, ip_address, user_agent, meta) |
| `permit_settings` | İzin ayarları (institution bilgileri, director bilgileri, logo, signature, footer_note) |

### Ek Alanlar (Migration'larla Eklenen)
- `users.last_seen` - Son görülme
- `users.field_status` - Saha personel durumu
- `applications.receipt_file_path` - Makbuz dosya yolu
- `applications.tckn`, `applications.tckn_alias` - T.C. Kimlik No
- `surface_types.color_code` - Renk kodu
- `institutions.extra_fields` - Ek alanlar (JSON)
- `field_tasks.stage_data` - Aşama verileri (JSON)
- `permit_settings.extra_fields` - Ek alanlar

---

## 6. MİMARİ KURALLAR

### Middleware Zinciri
```php
Route::middleware(['auth', 'verified', 'license', 'field-team-scope'])->prefix('admin')
```

### Lisans Kontrolü
- `license` middleware: Modül bazlı erişim kontrolü
- Lisans veritabanı tabanlı (license_key, domain-IP bağımsız)
- Modül bazlı açma/kapama

### İzin Sistemi (Spatie Permission)
- `permission:...` - İzin kontrolü
- `role:...` - Rol kontrolü
- Tenant-scope: Her kurum kendi verisini görür
- Super Admin: Platform geneli görüntüleme

### Layout Sistemi
- Ana layout: `resources/views/layouts/app.blade.php`
- Blade + Inertia.js karma kullanımı
- TailwindCSS ile stilendirme

### Service Katmanı
- `ApplicationService` - Başvuru işlemleri
- `PricingService` - Fiyat hesaplama
- `MapDrawingService` - Harita çizimi
- `LicenseService` - Lisans yönetimi
- `TaskTransferService` - Görev devri
- `AuditLogger` - Audit loglama

### PDF Üretimi
- barryvdh/laravel-dompdf
- Ruhsat şablonu: `PermitSetting` tablosundan veri çekme
- Kurum bazlı özelleştirme

---

## 7. AKTİF GELİŞTİRME — AYKOME MAPS MODÜLÜ

### CBS Entegrasyon (Aykome Maps)

**Route:** `/maps`

**Amaç:** WMS/WFS sunucularından Şanlıurfa Büyükşehir Belediyesi harita katmanlarını çekmek ve başvuru noktalarını harita üzerinde göstermek.

### WMS/WFS Sunucuları
```
geo4.sanliurfa.bel.tr:7171/geoserver/wms  → geo4 (AKOS Grubu)
geo4.sanliurfa.bel.tr:7171/geoserver/wfs  → geo4 (AKOS Grubu)
geo2.sanliurfa.bel.tr:9191/geoserver/wms  → geo2 (MAKS+ Grubu)
geo2.sanliurfa.bel.tr:9191/geoserver/wfs  → geo2 (MAKS+ Grubu)
```

### Katman Listesi

**geo4 (AKOS Grubu)** - uID: 45
| Layer | Açıklama | Varsayılan |
|-------|----------|------------|
| smpns:MISMAP_NUM_KADASTRO_PARSEL | Parseller | ✅ |
| smpns:MISMAP_NUM_BINA | Binalar | ✅ |
| smpns:MISMAP_NUM_ILCE | İlçe Sınırları | ✅ |
| smpns:MISMAP_NUM_MAHALLE | Mahalle | ✅ |
| smpns:MISMAP_NUM_CADDESOKAK | Cadde Sokak | ✅ |
| smpns:MISMAP_NUM_BAGIMSIZ | Bağımsız Kullanım | ✅ |

**geo2 (MAKS+ Grubu)** - uID: 404
| Layer | Açıklama | Varsayılan |
|-------|----------|------------|
| smpns:AYK_DOGALGAZ_LINKS | Doğalgaz Hatları | ✅ |
| smpns:AYK_ELEKTRIK_LINKS | Elektrik Hatları | ❌ |
| smpns:AYK_DOGALGAZ_NODES | Doğalgaz Noktaları | ❌ |
| smpns:AYK_ELEKTRIK_NODES | Elektrik Noktaları | ❌ |
| smpns:METROBUS_CAD | Metrobüs CAD | ❌ |

### Koordinat Sistemi
- WMS/WFS: EPSG:3857 (Web Mercator)
- Leaflet: WGS84 (lat/lng)
- Dönüşüm gerekli

### Yapılacaklar (CLAUDE.md'den)
1. `MapsController.php` oluştur (4 metod)
2. Route'ları `web.php`'ye ekle
3. Migration oluştur ve çalıştır
4. `resources/views/maps/index.blade.php` — tam ekran harita view
5. Sol katman paneli (AKOS + MAKS+ grupları, toggle)
6. WMS katmanları Leaflet'e ekle
7. Harita tıklama → WFS proxy sorgusu → popup
8. Popup'tan başvuru/ortak kazı modalı
9. Modal submit → AJAX → DB kayıt
10. Mevcut başvuruları haritada pin olarak göster
11. Sidebar'da "CBS Entegrasyon" linkini aktif yap → `/maps`

---

## 8. YENİ OTURUM PROTOKOLÜ

Her yeni oturumda (veya `/new` komutunda) şu adımlar izlenir:

1. **CLAUDE.md dosyasını oku** — Proje kök dizinindeki çalışma kuralları dosyası
2. **Proje yapısını kontrol et** — routes/web.php, admin.php, app.blade.php
3. **Kaldığın yerden devam et** — Son görevden kaldığın yerden devam et
4. **Gereksiz soru sorma** — Varsayımlar yapıp ilerle

### Environment Dosyaları
- `.env` — Yerel geliştirme
- `.env.example` — Şablon

### Önemli Yapılandırma Anahtarları
- `DB_CONNECTION=mysql`
- `DB_DATABASE=aykome`
- `GOOGLE_MAPS_API_KEY`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `BROADCAST_CONNECTION=log`

---

*Bu doküman projenin tamamını kapsayan referans dosyasıdır. Güncellemeler proje geliştikçe yapılmalıdır.*