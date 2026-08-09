# HGB Bilişim  AYKOME — SaaS Lisans Yönetimi Mimarisi

> **Belge versiyonu:** 1.0  
> **Oluşturma tarihi:** 2026-04-08  
> **Hedef okuyucu:** HGB Bilişim  teknik ekibi & gelecekte projeye dahil olacak geliştiriciler  

---

## 1. Genel Bakış

HGB Bilişim  AYKOME, belediyeler ve kamu kurumlarına **çok-kiracılı (multi-tenant) SaaS** olarak sunulan bir kazı izin yönetim platformudur.

```
HGB Bilişim  (Super Admin / Platform Sahibi)
    │
    ├── Belediye A — Lisans Anahtarı: AYKOME-BLD-A-2025 — Bitiş: 2026-12-31
    │       └── municipality-admin, municipality-staff, field-team kullanıcıları
    │
    ├── Belediye B — Lisans Anahtarı: AYKOME-BLD-B-2025 — Bitiş: 2026-06-01 (⚠ 60 gün kaldı)
    │       └── municipality-admin, municipality-staff kullanıcıları
    │
    └── Belediye C — Lisans Anahtarı: AYKOME-BLD-C-2024 — Bitiş: 2025-12-31 (🔴 SÜRESİ DOLDU)
            └── Sistem kilitli, giriş engellendi, e-posta uyarısı gönderildi
```

---

## 2. 3 Katmanlı Rol Sistemi

### Katman 1 — Super Admin (HGB Bilişim  Platformu)
| Rol | Spatie Adı | Açıklama |
|-----|------------|----------|
| Sistem Tanrısı | `super-admin` | Tüm firmalara, lisanslara, kullanıcılara tam erişim. Lisans satar, iptal eder, yenilenme süreçlerini yönetir. Sidebar'da "Firmalar & Lisanslar" ekranı görünür. |

**İzinler:** Tüm `spatie/laravel-permission` permission'ları + özel `licenses.manage` + `institutions.manage`

### Katman 2 — Admin (Belediye/Kurum Yöneticileri)
| Rol | Spatie Adı | Açıklama |
|-----|------------|----------|
| Belediye Yöneticisi | `municipality-admin` | Kendi belediyesine ait tüm başvuruları, kullanıcıları ve ayarları yönetir. Fiyat onayı, makbuz onayı, ruhsat üretimi yapabilir. |
| Belediye Personeli | `municipality-staff` | Başvuru oluşturabilir, düzenleyebilir ve onaylayabilir. Kullanıcı yönetemez. |
| Kurum Yöneticisi | `institution-manager` | TEDAŞ/ŞUSKİ gibi kurumların yöneticileri. Kendi kurumuna ait başvuruları tam yönetebilir. |

### Katman 3 — Alt Kullanıcı / Saha Ekibi
| Rol | Spatie Adı | Açıklama |
|-----|------------|----------|
| Kurum Personeli | `institution-staff` | Sadece başvuru oluşturabilir ve düzenleyebilir. Silme ve onay yetkisi yoktur. |
| Saha Ekibi | `field-team` | Kendisine atanmış saha görevlerini görebilir, fotoğraf yükleyebilir. Dashboard özel widget'larla basitleştirilmiştir. |

---

## 3. Lisans Modeli (Database)

```php
// licenses tablosu
Schema::create('licenses', function (Blueprint $table) {
    $table->id();
    $table->string('license_key')->unique();          // AYKOME-BLD-001-2025
    $table->string('owner_name');                     // Şehitkamil Belediyesi
    $table->foreignId('institution_id')->nullable()   // Bağlı kurum
          ->constrained('institutions')->nullOnDelete();
    $table->date('valid_from')->nullable();            // Başlangıç tarihi
    $table->date('valid_until');                       // Bitiş tarihi (zorunlu)
    $table->boolean('is_active')->default(true);       // Manuel aktif/pasif anahtarı
    $table->json('modules')->nullable();               // ['applications','map','reports']
    $table->integer('user_limit')->nullable();         // Maks kullanıcı sayısı (null=sınırsız)
    $table->text('notes')->nullable();                 // Satış notları
    $table->timestamps();
});
```

### Lisans Durumu Mantığı

```
SÜRECE GÖRE DURUM:
────────────────────────────────────────────────────
 valid_until > today + 30 gün   →  ✅  AKTİF
 valid_until ∈ (today, today+30]→  ⚠️  YAKINDA BİTECEK (uyarı)
 valid_until < today             →  🔴  SÜRESİ DOLDU (kilitli)
 is_active = false               →  ⛔  MANUEL KİLİTLİ
────────────────────────────────────────────────────
```

### Modelde Scope'lar

```php
// App\Models\License
public function scopeValid($query) { /* is_active=true AND valid_until >= today */ }
public function scopeExpiringSoon($query, int $days = 30) {
    return $query->where('is_active', true)
                 ->whereDate('valid_until', '>=', now())
                 ->whereDate('valid_until', '<=', now()->addDays($days));
}
public function scopeExpired($query) {
    return $query->whereDate('valid_until', '<', now());
}

// Örnek kullanım
License::expiringSoon(30)->with('institution')->get(); // Bu ay biten lisanslar
```

---

## 4. Middleware Katmanı

### `CheckLicense` Middleware (mevcut: `App\Http\Middleware\CheckLicense`)

```php
// Çalışma akışı:
// 1. Kullanıcı /admin/applications'a erişmeye çalışır
// 2. Middleware institution_id üzerinden License sorgular
// 3. Geçerli lisans yoksa → 403 "Lisansınızın süresi dolmuş" sayfasına yönlendir
// 4. Modül kısıtlaması varsa → modules array içinde kontrol et
```

```php
// routes/admin.php'deki kullanım
Route::middleware(['auth', 'verified', 'license'])->prefix('admin')->group(function () {
    // Genel lisans kontrolü — buraya giren tüm user'ların institution'ı aktif lisansa sahip olmalı

    Route::middleware('license:applications')->group(function () {
        // Sadece 'applications' modülü aktifse erişilebilir
    });

    Route::middleware('license:map')->group(function () {
        // Sadece 'map' modülü aktifse erişilebilir
    });
});
```

### Planlanan: `LicenseExpiry` Middleware Genişletmesi

```php
// Eklenecek özellik: Yakında Bitecek Uyarı Banner'ı
// Eğer lisans 30 gün içinde bitecekse yönetici dashboard'unda sarı banner göster
// Eğer 7 gün içinde bitecekse kırmızı kritik uyarı göster
// Bağlantı: Super Admin'e otomatik e-posta gönder
```

---

## 5. Cron Job Görevleri

### `licenses:check-expiry` Artisan Komutu

```bash
# Planlama (app/Console/Kernel.php veya routes/console.php)
Schedule::command('licenses:check-expiry')->dailyAt('08:00');
```

```php
// app/Console/Commands/CheckLicenseExpiry.php
class CheckLicenseExpiry extends Command
{
    protected $signature   = 'licenses:check-expiry';
    protected $description = 'Süresi dolan veya yakında dolacak lisansları kontrol et, e-posta gönder';

    public function handle(): void
    {
        // 1. Bugün süresi dolan lisanslar → is_active = false yap + e-posta
        $expired = License::query()
            ->where('is_active', true)
            ->whereDate('valid_until', '<', today())
            ->get();

        foreach ($expired as $license) {
            $license->update(['is_active' => false]);
            // Mail::to(config('aykome.super_admin_email'))->send(new LicenseExpiredMail($license));
            // Veya: Notification::route('mail', ...)->notify(new LicenseExpiredNotification($license));
        }

        // 2. 30 gün içinde bitecek lisanslar → uyarı e-postası
        $expiringSoon = License::query()
            ->where('is_active', true)
            ->whereDate('valid_until', '>=', today())
            ->whereDate('valid_until', '<=', today()->addDays(30))
            ->get();

        foreach ($expiringSoon as $license) {
            $daysLeft = today()->diffInDays($license->valid_until);
            // Mail::to(config('aykome.super_admin_email'))->send(new LicenseExpiringSoonMail($license, $daysLeft));
        }

        $this->info("İşlendi: {$expired->count()} süresi dolmuş, {$expiringSoon->count()} yakında dolacak.");
    }
}
```

### Cron Takvimi (Önerilen)

| Görev | Komut | Çalışma Zamanı | Amaç |
|-------|-------|----------------|------|
| Günlük lisans kontrolü | `licenses:check-expiry` | Her gün 08:00 | Expire olan lisansları kapat, uyarı e-postaları gönder |
| Haftalık özet raporu | `licenses:weekly-report` | Her Pazartesi 09:00 | Super Admin'e tüm firmaların lisans durumunu özetle |
| Temizlik | `licenses:cleanup-logs` | Her ay 1. gün | 90 günden eski lisans log kayıtlarını temizle |

---

## 6. Firma Kilitleme Akışı

```
Senaryo: Belediye A'nın lisansı bugün bitti.

1. Cron Job çalışır (08:00)
   ├── License::where('valid_until', '<', today())->get() → Lisans bulundu
   ├── license->update(['is_active' => false])            → Kilitlendi
   └── LicenseExpiredMail gönderildi → Super Admin + Kurum sorumlusu

2. Belediye A yöneticisi giriş yapar
   ├── Auth başarılı ✅
   ├── Middleware: CheckLicense::handle()
   │       └── License::scopeValid() → null döner (is_active = false)
   └── 403 → "Lisansınızın süresi dolmuştur" sayfası

3. Super Admin paneline girip lisansı yeniler
   ├── /admin/licenses/{id}/edit → valid_until güncellenir
   ├── is_active = true yapılır
   └── Belediye A tekrar erişim kazanır ✅
```

---

## 7. Super Admin Route'ları ve Sayfaları

```php
// routes/admin.php — Super Admin'e özel, role:super-admin middleware ile korunmalı

Route::middleware(['role:super-admin'])->group(function () {

    // Firma & Lisans Yönetimi (ANA MENÜ — Sidebar'da görünür)
    Route::prefix('licenses')->name('licenses.')->group(function () {
        Route::get('/',           [LicenseController::class, 'index'])->name('index');   // Tüm firmalar listesi
        Route::get('/create',     [LicenseController::class, 'create'])->name('create'); // Yeni lisans sat
        Route::post('/',          [LicenseController::class, 'store'])->name('store');
        Route::get('/{l}/edit',   [LicenseController::class, 'edit'])->name('edit');     // Yenile / düzenle
        Route::put('/{l}',        [LicenseController::class, 'update'])->name('update');
        Route::post('/{l}/renew', [LicenseController::class, 'renew'])->name('renew');   // Hızlı yenileme
        Route::post('/{l}/lock',  [LicenseController::class, 'lock'])->name('lock');     // Manuel kilitle
    });

    // Sistem Sağlık İzleme (gelecek özellik)
    Route::get('system-health', [SystemController::class, 'health'])->name('system.health');

});
```

---

## 8. Lisans Satış Akışı (İş Akışı)

```
Super Admin HGB Bilişim  Panelinde:
─────────────────────────────────────────────────────────
1. Müşteri (Belediye X) ile anlaşma yapılır.
2. Super Admin → /admin/licenses/create
3. Doldurulur:
   ├── Lisans Anahtarı: AYKOME-BLD-BELX-2026 (otomatik üretilir)
   ├── Firma Adı: Belediye X
   ├── Bağlı Kurum: Institution dropdown → Belediye X seçilir
   ├── Başlangıç: 2026-01-01
   ├── Bitiş: 2027-01-01 (1 yıllık)
   ├── Modüller: [applications, map, reports] (checkbox'larla seçilir)
   └── Kullanıcı Limiti: 10
4. Kayıt oluşturulur → institution.license_id güncellenir
5. Belediye X'in admin kullanıcısına e-posta ile:
   ├── Lisans anahtarı
   ├── Kullanıcı giriş bilgileri
   └── Erişim URL'si
6. Belediye X artık sisteme erişebilir.
```

---

## 9. Gelecekte Eklenecek Özellikler

### Phase 1 (Öncelikli)
- [ ] `CheckLicense` middleware'ini institution_id bazlı çalışacak şekilde tam bağla
- [ ] `licenses:check-expiry` Artisan komutu yaz ve `routes/console.php`'ye ekle
- [ ] Lisans yenileme için "Hızlı Yenile" butonu (1 yıl uzat)
- [ ] Super Admin dashboard'a "Kritik Lisanslar" widget'ı ekle

### Phase 2 (Orta vadeli)
- [ ] Çok modüllü lisans satışı (sadece map modülü, sadece applications modülü vb.)
- [ ] Lisans log tablosu (kim ne zaman ne yaptı)
- [ ] Otomatik e-posta sistemi (LicenseExpiredMail, LicenseExpiringSoonMail)
- [ ] Kullanıcı limiti middleware kontrolü

### Phase 3 (Uzun vadeli)
- [ ] Self-service yenileme portalı (belediye kendi lisansını kredi kartıyla yenileyebilsin)
- [ ] Stripe/iyzico entegrasyonu
- [ ] Firma bazlı özelleştirilmiş domain (belediyex.aykome.com.tr)
- [ ] White-label (firma logosu, rengi)

---

## 10. Güvenlik Notları

- Lisans anahtarları `.env` veya veritabanında saklanmalı, **asla kaynak koduna yazılmamalı**.
- `super-admin` rolü tek kişide olmalı (veya çok sıkı MFA korumalı).
- Lisans kontrolü cache'lenerek her request'te DB sorgusu azaltılmalı: `Cache::remember("license:{$institutionId}", 3600, ...)`.
- Cron job başarısızlıklarını logla: `Log::channel('slack')->error(...)` veya Sentry.

---

## 11. Mevcut Durum (2026-04-08 — Rev 2)

| Bileşen | Durum | Not |
|---------|-------|-----|
| `licenses` tablosu | ✅ Var | `valid_from`, `valid_until`, `is_active`, `modules`, `user_limit` |
| `License` modeli | ✅ Var | `scopeValid()`, `scopeExpiringSoon()`, `scopeExpired()` |
| `LicenseController` | ✅ Var | CRUD tamamlandı |
| `CheckLicense` middleware | ✅ Var | Modül bazlı kontrol aktif |
| Route'lar | ✅ Var | `admin.licenses.*` — super-admin için sidebar'da |
| Super Admin Dashboard | ✅ Var | `dashboard-superadmin.blade.php` — KPI, kritik lisanslar, platform geneli başvurular |
| 3 Katmanlı Rol Sistemi | ✅ Var | super-admin / municipality-admin+staff / field-team — AykomeSeeder belgelenmiş |
| PRO Modüller (Stub) | ✅ Var | work-orders, field-reports-pro, e-document — Coming Soon sayfaları |
| **Audit Log Sistemi** | ✅ **YENİ** | `audit_logs` tablosu, `AuditLogger` service, `AuditLogController`, DataTables AJAX view |
| **DB Bildirim Sistemi** | ✅ **YENİ** | `NewApplicationCreatedNotification`, `ReceiptUploadedNotification` — database channel |
| **SweetAlert Premium Toast** | ✅ **YENİ** | `partials/scripts.blade.php` — sağ üst köşe animasyonlu toast |
| **Hata Sayfaları** | ✅ **YENİ** | `errors/404.blade.php` + `errors/403.blade.php` — Lottie animasyonlu |
| **Harita Null Fix** | ✅ **YENİ** | `Number("") = NaN` fix, `min-height: 500px`, fallback Türkiye merkezi (39, 35) |
| Cron Job | 🔲 Yok | `licenses:check-expiry` yazılacak |
| E-posta bildirimleri | 🔲 Yok | `LicenseExpiredMail`, `LicenseExpiringSoonMail` yazılacak |
| Self-service portal | 🔲 Yok | Phase 3 |

---

## 12. Audit Log Sistemi

### Tablo: `audit_logs`

| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint | PK |
| `user_id` | FK nullable | Kimdir (user silinirse null kalır) |
| `user_name` | string | Denormalized isim — geçmiş değişmez |
| `user_role` | string | İşlem anındaki rol |
| `action` | string indexed | `auth.login`, `tckn.query`, `receipt.approve` vb. |
| `subject_type` | string nullable | `Application`, `User`, `License` vb. |
| `subject_id` | bigint nullable | İlgili kayıt ID'si |
| `description` | string 512 | İnsan okunabilir açıklama |
| `ip_address` | string 45 | IPv4/IPv6 |
| `user_agent` | string 512 | Tarayıcı bilgisi |
| `meta` | json nullable | Ek bilgiler (TCKN, PDF yolu vb.) |
| `created_at` | timestamp | İmmutable — `updated_at` yok |

### Kayıt Tetikleyiciler

| Olay | Action Kodu | Tetikleyen |
|------|------------|------------|
| Sisteme giriş | `auth.login` | `AppServiceProvider` (Laravel Login event) |
| Sistemden çıkış | `auth.logout` | `AppServiceProvider` (Laravel Logout event) |
| TCKN sorgusu | `tckn.query` | `ApplicationsController::checkApplicant()` |
| Başvuru oluşturma | `application.create` | `ApplicationsController::store()` |
| Başvuru güncelleme | `application.update` | `ApplicationsController::update()` |
| Fiyat onayı | `price.approve` | `ApplicationsController::approvePrice()` |
| Makbuz yükleme | `receipt.upload` | `ApplicationsController::storeReceipt()` |
| Makbuz onayı | `receipt.approve` | `ApplicationsController::approveReceipt()` |
| Makbuz reddi | `receipt.reject` | `ApplicationsController::rejectReceipt()` |
| Görev devri | `task.transfer` | `ApplicationsController::transfer()` |

### Erişim

`/admin/logs` — yalnızca `super-admin` rolü erişebilir. DataTables server-side AJAX ile sayfalama ve arama.

---

_Bu belge, HGB Bilişim  AYKOME'nin SaaS lisans altyapısını tanımlamaktadır. Güncellemeler her major geliştirme sonrası bu dosyaya yansıtılmalıdır._
