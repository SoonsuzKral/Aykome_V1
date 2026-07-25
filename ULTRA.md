# AYKOME ULTRA — Proje Merkezi Doküman

> **Son Güncelleme:** 2026-07-25
> **Versiyon:** v6.22 (CBS+Deploy+Edit Fix+Create Fix)
> **Domain:** eyyubiye.aykome.bel.tr
> **Müşteri:** Eyyübiye Belediyesi (Şanlıurfa)
> **Geliştirme Makinesi:** MacBook Pro M4 Pro — macOS 15.6

---

## 1. PROJE KİMLİĞİ

**AYKOME** = Altyapı Yönetim ve Koordinasyon Merkezi
**Ürün:** HGB Bilişim ULTRA SAAS
**Ne işe yarar:** Belediyeler için kazı/altyapı izin yönetim sistemi. Kurumlar (TEDAŞ, ŞUSKİ, AKSA, Türk Telekom) harita üzerinden kazı alanı çizer, ücret hesaplanır, makbuz onaylanır, PDF ruhsat üretilir, saha kontrolü yapılır.

### Sistem Hiyerarşisi
```
HGB Bilişim (Super Admin)
  → Belediye (municipality-admin, municipality-staff)
    → Kurumlar (TEDAŞ, ŞUSKİ, AKSA, Türk Telekom)
      → Vatandaş
    → Saha Ekipleri (field-team)
```

### Aktif Müşteriler
- **Eyyübiye Belediyesi** (Şanlıurfa) — Canlıda
- AKSA, TEDAŞ, ŞUSKİ, Türk Telekom, HGB Bilişim Demo

### Domain
- **Canlı:** `eyyubiye.aykome.bel.tr`
- RDP Sunucu (Windows + Docker), VPN ile erişiliyor
- Dışarıya açık değil, kurum içi kullanım

---

## 2. TEKNİK STACK

### Backend
| Bileşen | Versiyon |
|---------|----------|
| PHP | 8.2 |
| Laravel | 12.0 |
| Oracle DB | 21c Free (Docker) |
| MySQL | MariaDB (yedek) |
| Redis | 7-alpine |

### Frontend
| Bileşen | Versiyon |
|---------|----------|
| Blade | Laravel default |
| Vue.js | 3.4.0 (Inertia) |
| TailwindCSS | 3.2.1 |
| Vite | 7.0.7 |
| Leaflet | 1.9.4 (CBS modülü) |
| Google Maps | API (harita izleme) |

### Önemli Paketler
| Paket | Görev |
|-------|-------|
| `yajra/laravel-oci8` | Oracle bağlantısı |
| `spatie/laravel-permission` | Rol/izin sistemi |
| `spatie/laravel-medialibrary` | Dosya/media yönetimi |
| `barryvdh/laravel-dompdf` | PDF üretimi (ruhsat, makbuz) |
| `laravel/reverb` | WebSocket (canlı bildirim) |
| `laravel/sanctum` | API token auth |
| `inertiajs/inertia-laravel` | Vue + Blade hibrit |

---

## 3. MİMARİ KATMANLAR

```
Route (web.php + admin.php)
  → Middleware (auth → license → field-team-scope)
    → Controllers (Admin/*, MapsController, ProfileController)
      → Services (Application, Pricing, MapDrawing, Drawing, TaskTransfer, License, AuditLogger)
        → Models (Eloquent)
          → Database (Oracle - Docker, MySQL - yedek)

Views:
├── admin/*         → Admin paneli (dashboard, applications, users, roles, vb.)
│   ├── applications/   → create(1408s), edit(824s), show(669s), index(318s)
│   ├── live-map-pro/   → Canlı saha haritası
│   ├── work-orders/    → Görev emirleri
│   ├── field-reports-pro/  → Gelişmiş saha raporu
│   ├── settings/       → Belge ayarları
│   └── ...             → logs, licenses, surface-types, institutions
├── maps/*          → CBS Entegrasyon (Aykome Maps)
│   ├── index.blade.php      → 3446 satır, ana harita
│   └── partials/_harita.blade.php → 269 satır, yeniden kullanılabilir
├── frontend/*      → Landing page (aykome_landing)
├── docs/*          → Kullanım kılavuzu
├── errors/*        → 403, 404, license-blocked
├── layouts/*       → app, admin, auth
└── partials/*      → sidebar, navbar, flash-message, scripts
```

---

## 4. DOCKER YAPISI (Geliştirme)

### Servisler (docker-compose.yml)
| Servis | İmaj/Kaynak | Port | Görev |
|--------|-------------|------|-------|
| `oracle` | gvenzl/oracle-free:slim | 1521 | Ana veritabanı |
| `redis` | redis:7-alpine | 6379 | Cache/queue/broadcast |
| `adminer` | Dockerfile (PHP+OCI8) | 8080 | DB GUI |
| `php` | Dockerfile (PHP+OCI8+Redis) | — | PHP CLI |
| `serve` | aykome-v6-php imajı | 8001 | Laravel dev server |
| `reverb` | aykome-v6-php imajı | 8090 | WebSocket server |

### Çalıştırma
```bash
bash start.sh          # Hepsi birden
./oracle.sh migrate    # Artisan komutu (Oracle)
./oracle.sh serve      # Dev server (port 8001)
npm run dev            # Vite HMR (port 5173)
```

### Önemli: `aykome-v6-php` Pull Hatası
`start.sh`'de `docker compose up -d` önce Docker Hub'dan imajı çekmeyi dener, bulamayınca build eder. Bu hata mesajı kafa karıştırıyor.

**Çözüm:** `--build` flag'i eklenir — her zaman local build yap, pull'ı dene.

---

## 5. MEVCUT MODÜLLER

### 5.1 Başvurular (Applications) — Çekirdek Modül
**Controller:** `Admin\ApplicationsController` (855 satır, 19 metod)
**Service:** `ApplicationService` (339 satır)
**Policy:** `ApplicationPolicy` (79 satır, 8 permission)
**Form Requests:** StoreApplication, StoreReceipt, RejectReceipt, TransferTask

**Status Machine (13 durum):**
```
draft → submitted → pre_excavation_approved → priced
  → awaiting_payment → receipt_pending → approved
  → licensed → field_work → completed
    ↘ rejected / archived
```

**Kritik controller metodları:**
- `checkApplicant()` — TCKN ile vatandaş sorgulama
- `store()` — Draft oluşturma (Oracle unique constraint retry)
- `update()` — Decimal comma-to-dot + surface line sync
- `submit()` → `approvePreExcavation()` → `approvePrice()` → `storeReceipt()` → `approveReceipt()` → `transfer()`
- `destroy()` / `bulkDestroy()` — Soft delete
- `data()` — DataTables AJAX endpoint
- `statusJson()` — 5sn canlı durum polling

**Veri İzolasyonu:**
- `field-team` → sadece atandığı görevler
- `institution-staff` → sadece kendi kurumu
- `super-admin/municipality` → tüm başvurular

**View'ler:**
- `create.blade.php` (1408 satır) — Leaflet çizim + yüzey hesabı + dosya yükleme + interaktif form
- `edit.blade.php` (1491 satır) — Mevcut veri ön yüklü (create ile eşitlendi)
- `show.blade.php` (669 satır) — Detay + timeline + makbuz yönetimi + 5sn polling
- `index.blade.php` (318 satır) — DataTables tarzı liste + toplu sil + filtreler

---

### 5.2 Aykome Maps (CBS Entegrasyon)
**Controller:** `MapsController` (821 satır, 16 metod)
**Service:** `DrawingService` (201 satır)
**Modeller:** GisBasvuruNokta, GisCizim, GisCizimYolIliskisi
**Tablolar:** gis_basvuru_noktalar, gis_cizimler, gis_cizim_yol_iliskisi, gis_katman_ayarlari

**WMS/WFS Sunucular (Şanlıurfa Büyükşehir):**
| Sunucu | Port | Görev |
|--------|------|-------|
| geo4.sanliurfa.bel.tr | 7171 | AKOS (Kadastro, Parsel, Bina) |
| geo2.sanliurfa.bel.tr | 9191 | MAKS+ (Altyapı şebekeleri) |
| geo3.sanliurfa.bel.tr | 8091 | Yeni tek sunucu (13 katman) |

**16 endpoint:**
```
Core:       GET /maps, GET /maps/proxy
15m Yol:    GET /maps/15m/alti, /15m/ustu, /15m/sorgula
Çizim:      POST /maps/drawing/save, PUT/DELETE/GET
Katman:     POST /maps/katman/kaydet, GET /katman/yukle
Arama:      GET /maps/ara (Nominatim + WFS proxy)
Başvuru:    GET /maps/basvurular/geojson, POST /maps/nokta-kaydet,
            POST /maps/basvuru-olustur, GET /maps/basvuru-sorgula,
            GET /maps/tckn-sorgula/{tckn}
```

**Ana view:** `maps/index.blade.php` (3446 satır)
- Sol panel: 9 accordion katman grubu
- Harita: Leaflet + WMS + çizim araçları + 15m yol
- 4-adımlı Wizard: Konum → Detay → Evrak → Özet
- Draggable pencereler (draw report, hat kimliği, başvuru)
- GetFeatureInfo tıklama → parsel/ada/mahalle sorgusu

**Partial:** `maps/partials/_harita.blade.php` (269 satır)
- Yeniden kullanılabilir harita componenti
- `create.blade.php`, `edit.blade.php`, `show.blade.php`'ye `@include` ile gömülür
- Parametreler: mode, drawingEnabled, hatKimligiEnabled, show15mRoads, height, readOnly

---

### 5.3 Diğer Admin Modülleri
| Modül | Controller | Route |
|-------|-----------|-------|
| Dashboard | DashboardController | /admin |
| Harita İzleme | MapMonitorController | /admin/map |
| Canlı Saha İzleme (PRO) | LiveMapController | /admin/live-map-pro |
| Görev Emri (PRO) | WorkOrderController | /admin/work-orders |
| Saha Raporu (PRO) | FieldReportController | /admin/field-reports-pro |
| Kullanıcılar | UserController | /admin/users |
| Roller | RoleController | /admin/roles |
| Lisanslar | LicenseController | /admin/licenses |
| Kurumlar | InstitutionController | /admin/institutions |
| Zemin Tipleri | SurfaceTypeController | /admin/surface-types |
| Raporlar | ReportController | /admin/reports |
| Gelişmiş Rapor (PRO) | ReportController | /admin/reports/advanced |
| Sistem Logları | AuditLogController | /admin/logs |
| Belge Ayarları | SettingsController | /admin/settings/permit |
| Bildirimler | NotificationController | /admin/notifications |
| Görevlerim | MyTasksController | /admin/my-tasks |
| E-Belge/Evrak (PRO) | — | /admin/e-document |

---

### 5.4 Servis Katmanı
| Servis | Görev |
|--------|-------|
| `ApplicationService` | Başvuru CRUD + state machine |
| `PricingService` | Fiyat hesaplama (KDV, ruhsat harcı, keşif, teminat) |
| `MapDrawingService` | GeoJSON alan hesabı (Shoelace) + excavation area sync |
| `DrawingService` | Yol kesişim tespiti (15m road data) |
| `TaskTransferService` | Saha görevi atama |
| `LicenseService` | Lisans kontrolü + PDF üretimi |
| `AuditLogger` | Tüm önemli aksiyonları logla |

---

### 5.5 Veritabanı (47 Migration)
| Grup | Tablolar |
|------|----------|
| Auth | users, password_reset_tokens, sessions |
| Spatie | permissions, roles, model_has_permissions, model_has_roles, role_has_permissions |
| Çekirdek | institutions, licenses, surface_types, applications, application_surface_areas, excavation_areas |
| Finans | receipts, payment_transactions |
| Saha | field_tasks, field_task_media |
| CBS | gis_basvuru_noktalar, gis_cizimler, gis_cizim_yol_iliskisi, gis_katman_ayarlari |
| Belge | permit_settings, pre_excavation_permit_settings, application_documents |
| Log | audit_logs, application_timeline_logs, notifications |

---

## 6. PRODUCTION (Eyyübiye Belediyesi)

### Sunucu Bilgileri
- **Domain:** eyyubiye.aykome.bel.tr
- **Makine:** Windows Server (RDP)
- **Erişim:** VPN üzerinden RDP
- **Servis:** Docker (Oracle + Laravel + Redis)
- **Laravel:** Windows tarafında çalışıyor

### Deployment Sorunları
1. **Git pull karmaşası:** Çok fazla commit arasında hangisini çekeceği karışıyor
2. **DB migration:** Production'da migration'lar bazen çalışmıyor
3. **DB senkronizasyonu:** Geliştirmedeki yeni tabloları production'a aktarmak için backup/restore yapılıyor
4. **Hangi sürüm çalışıyor belli değil:** Sürüm takibi yok

### Deploy Çözümü — Git Tag + Deploy Script

**Yayınlama (geliştirme makinesi):**
```bash
git add .
git commit -m "v6.22: Yeni modül eklendi"
git tag v6.22 -m "CBS entegrasyon + 15m yol analizi"
git push origin main --tags
```

**Production'da deploy (RDP'de PowerShell):**
```powershell
.\deploy.ps1 v6.22
```

---

## 7. YAPILACAKLAR / PLAN

### Acil Düzeltmeler
- [x] `start.sh`'e `--build` flag'i eklendi (pull hatası çözümü)
- [x] `deploy.ps1` script'i yazıldı
- [x] Git tag workflow'u oturumlaştı

### Yeni Modüller (Planlanan)
- [ ] **E-Tebligat Servisi** — Dijital tebligat gönderme/alma
- [ ] **Kazı Metraj Tahmini (BETA→PRO)** — AI tabanlı metraj hesaplama
- [ ] **Entegre Ödeme Sistemi** — Kredi kartı/havale ile online ödeme
- [ ] **Saha Mobil Uygulaması** — GPS arka plan takip + push notification
- [ ] **Raporlama 2.0** — Özelleştirilebilir dashboard + grafikler
- [ ] **Çoklu Dil Desteği** — İngilizce/Arapça arayüz
- [ ] **API Modülü** — Harici sistemlere REST API

### CBS Geliştirmeleri
- [ ] WFS GetFeatureInfo iyileştirme (daha hızlı sorgu)
- [ ] Çizim araçlarına snap/alignment özelliği
- [ ] 15m yol analizi performans optimizasyonu
- [ ] Mobil harita deneyimi iyileştirme

### Altyapı
- [ ] CI/CD pipeline (GitHub Actions → RDP)
- [ ] Production log izleme (Laravel Pail + Sentry)
- [ ] Oracle → PostgreSQL geçiş değerlendirmesi
- [ ] Docker image'ları private registry'e push

---

## 8. ÖNEMLİ DOSYALAR

### Yapılandırma
| Dosya | İçerik |
|-------|--------|
| `.env` | DB bağlantıları, API key'ler, Reverb ayarları |
| `docker-compose.yml` | 6 servis (oracle, redis, adminer, php, serve, reverb) |
| `docker/php/Dockerfile` | PHP 8.2 + OCI8 + Redis + InstantClient ARM64 |
| `docker/adminer/Dockerfile` | Adminer + OCI8 |
| `vite.config.js` | Vite build yapılandırması |
| `tailwind.config.js` | TailwindCSS tema |
| `package.json` | npm bağımlılıkları |

### Script'ler
| Script | İşlev |
|--------|-------|
| `start.sh` | Tüm Docker servislerini başlat + migrate |
| `oracle.sh` | Oracle container'a artisan komutu gönder |
| `test_maps.sh` | CBS modülü curl test suite (9 test) |

### Routes
| Dosya | İçerik |
|-------|--------|
| `routes/web.php` | /, /maps/*, /db-switch/*, /docs, /tanitim |
| `routes/admin.php` | /admin/* (tüm yönetim paneli rotaları) |
| `routes/auth.php` | Auth rotaları (login, register, password) |

---

## 9. OTURUM GEÇMİŞİ

### 2026-07-25 — Oturum 1: ULTRA.md + start.sh + deploy.ps1
- `start.sh`'e `--build` flag'i eklendi (pull hatası çözümü)
- `ULTRA.md` oluşturuldu (proje merkezi doküman)
- `deploy.ps1` yazıldı (RDP PowerShell deploy script)
- Commit: `aa9e7d0 v6.22 baslangici` + `b0ccd7f deploy.ps1`

### 2026-07-25 — Oturum 2: edit.blade.php Rewrite + Controller Fix
- `edit.blade.php` tamamen yeniden yazıldı (1445 satır, create ile eşitlendi)
  - Başvuru sahibi alanları (first_name, last_name, national_id)
  - Kazı detayları (excavation_reason, work_type, start_date, end_date, project_code)
  - Harita arama, stil paneli, arazi katmanı, TCKN sorgulama
  - Surface lines hydrasyonu + doküman yükleme
- `ApplicationsController@update()`:
  - 7 yeni alan validasyonu (applicant_first_name/../excavation_reason/../end_date)
  - National ID normalizasyonu (numeric)
  - Tüm alanlar `update()` çağrısına eklendi
- `ApplicationsController@edit()`:
  - `isInstitutionUser`, `applicantPrefill`, `institutionPrefill` view'e gönderiliyor
  - Institution ilişkileri expand edildi
- Commit: `4c88fac feat: edit.blade.php create sayfasi ile esitlendi, controller update() genisletildi`

### 2026-07-25 — Oturum 3: createDraft() Bug Fix (project_code + application_type)
- `ApplicationService::createDraft()`: `Application::create()` çağrısına `project_code` ve `application_type` eklendi (daha önce bu alanlar kaydedilmiyordu, hep NULL/silinmişti)
- `ApplicationsController::edit()`: `surfaceLinesData` mapping'ine `id` ve `address` alanları eklendi, `->toArray()` ile güvenli JSON serialization sağlandı
- Bug'lar:
  1. Proje Kodu kaydedilmiyor → düzenlemede gelmiyordu ✅
  2. Uygulama Türü (basvuru/ariza) kaydedilmiyor → hep "Normal Başvuru" görünüyordu ✅
  3. Zemin Tipleri edit sayfasında gelmiyor → mapping iyileştirildi ✅
- Commit: `796321f fix: createDraft()'a project_code ve application_type eklendi`

### 2026-07-25 — Oturum 4: Lifecycle Hydration (Zemin Tipi Görünürlük)
- `edit.blade.php`: Zemin tipi satırlarının JS `surfaceLines` dizisine hidrate edilmemesi sorunu çözüldü
  - `INITIAL_SURFACE_LINES` (pre-mapped array) → `EXISTING_SURFACE_LINES` (raw model data, `surfaceType` ilişkisi yüklü)
  - `rowDrawings` GeoJSON `polygon_geojson` textarea'sından parse edilerek yükleniyor
  - `renderTable()` + `recalculateAll()` explicit olarak hidrasyon sonrası çağrılıyor
  - `surface_type.name` ve `surface_type.price_per_m2` nested model ilişkisinden çekiliyor (fallback: `SURFACE_TYPES` array)

### Önceki Oturumlar (SESSION_SUMMARY.md)
- v7.6 — Harita döndürme (leaflet-rotate) + bearing toggle temizliği
- v7.5 — Draw report'ta kapı seçimi checkbox + yol hat adımı
- v7.4 — WMS GetFeatureInfo + Draw report akış düzeltme
- v7.3 — WFS sadeleştirme (2 layer) + WMS GetFeatureInfo
- v7.2 — Draggable paneller + cascading selection
- Detay için: `SESSION_SUMMARY.md` ve `docs/AYKOME_MAPS.md`

---

## 10. HIZLI KOMUTLAR

```bash
# Geliştirme
bash start.sh                          # Tüm servisleri başlat
./oracle.sh migrate                    # Migration çalıştır
./oracle.sh db:seed --class=AykomeSeeder  # Seed verileri
./oracle.sh tinker                     # PHP interactive shell
npm run dev                            # Vite HMR
php vendor/bin/phpunit tests/Feature/MapsControllerTest.php  # CBS test

# Deploy
git tag v6.22 -m "Açıklama"
git push origin main --tags
# RDP'de: .\deploy.ps1 v6.22

# Backup
# Oracle dump (RDP'de)
docker exec aykome-v6-oracle expdp aykome_user/aykome123@FREEPDB1 \
  schemas=aykome_user directory=DATA_PUMP_DIR \
  dumpfile=aykome_$(date +%Y%m%d).dmp logfile=export.log
```

---

*AYKOME HGB Bilişim ULTRA SAAS v6.21+ | eyyubiye.aykome.bel.tr*
