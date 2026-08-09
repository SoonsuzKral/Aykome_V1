# AYKOME E-İmza Entegrasyon Planı

## Genel Bakış

Belediye personelinin Kamu SM / E-Güven / TÜRKTRUST USB token'ları ile 
Aykome üzerinden PDF belgeleri (ruhsat, taahhütname, metraj vb.) 
PAdES B-T standartında imzalamasını sağlar.

## Mimarİ

```
┌─ KULLANICI BİLGİSAYARI (Windows/Mac) ─────────────────────┐
│                                                             │
│  🖥️ TARAYICI (Chrome/Edge)                                 │
│  Aykome'de "E-İmza ile İmzala" butonu                      │
│    → fetch /api/e-imza/baslat                              │
│    → window.location = "aykome://sign?tid=xxx&server=..."  │
│                    ⬇ (custom protocol)                      │
│  🖊️ ELEKTRON UYGULAMASI (systray)                         │
│    → transaction al → PDF indir                            │
│    → PIN sor (BrowserWindow)                               │
│    → PKCS#11 ile token'a bağlan → PAdES imza               │
│    → İmzalı PDF'i API'ye yükle                             │
│    → Pencere kapanır → Web UI polling ile görür            │
│                    ⬆ USB ⬆                                   │
│               ┌──────────────┐                               │
│               │  KAMU SM     │                               │
│               │  E-GÜVEN     │                               │
│               │  TÜRKTRUST   │                               │
│               └──────────────┘                               │
└─────────────────────────────────────────────────────────────┘
         ⬇ HTTPS ⬆
┌─ BELEDİYE SUNUCUSU (Laravel) ─────────────────────────────┐
│  POST /api/e-imza/baslat     → PDF oluştur + transaction  │
│  GET  /api/e-imza/pdf/{tid}  → PDF'i döndür (token ile)  │
│  POST /api/e-imza/tamamla    → imzalı PDF'i al + kaydet  │
│  GET  /api/e-imza/durum/{tid}→ durum sorgula (polling)   │
│  e_imza_transactions tablosu                               │
│  module_documents JSON güncellemesi                        │
└───────────────────────────────────────────────────────────┘
```

## Bileşenler

### Laravel (Sunucu)

| Dosya | Açıklama |
|---|---|
| `app/Http/Controllers/Api/EImzaController.php` | 4 endpoint: baslat, pdf, tamamla, durum |
| `app/Models/EImzaTransaction.php` | Transaction model (FK→applications) |
| `app/Models/EImzaIstemci.php` | Masaüstü istemci kaydı (API key) |
| `app/Services/EImzaService.php` | PDF oluşturma, transaction yönetimi, cleanup |
| `app/Http/Middleware/EImzaApiKey.php` | API Key doğrulama middleware |
| `config/e-imza.php` | enabled, api_key |
| `database/migrations/..._create_e_imza_transactions_table.php` | Tablolar |
| `routes/web.php` | 4 route tanımı |

**API Endpoint'leri:**
- `POST /api/e-imza/baslat` — Auth gerektirir (web kullanıcısı). PDF oluşturur, transaction kaydeder.
- `GET /api/e-imza/pdf/{transaction_id}?token=...` — Token ile korumalı PDF indirme.
- `POST /api/e-imza/tamamla` — API Key ile korumalı. İmzalı PDF'i alır, kaydeder.
- `GET /api/e-imza/durum/{transaction_id}` — Auth gerektirir. Polling için durum sorgulama.

### Electron (Masaüstü)

| Dosya | Açıklama |
|---|---|
| `main.js` | Electron main process: systray, custom protocol, IPC |
| `preload.js` | contextBridge (PIN UI → main) |
| `src/protocol.js` | `aykome://sign?tid=xxx&server=URL` handler |
| `src/pkcs11/scanner.js` | PKCS#11 kütüphanesi otomatik tarama |
| `src/pkcs11/signer.js` | PKCS#11 ile imzalama (Windows: gerçek, Mac: simüle) |
| `src/pkcs11/cert-utils.js` | Sertifika parse (node-forge) |
| `src/pkcs11/simulate.js` | Mac'te test için simülasyon (2048-bit RSA) |
| `src/pades/sign-pdf.js` | PAdES B-T imza (node-signpdf + pdf-lib) |
| `src/network/pdf-fetcher.js` | PDF indirme (axios) |
| `src/network/uploader.js` | İmzalı PDF yükleme (multipart form-data) |
| `src/config/store.js` | electron-store wrapper |
| `renderer/pin.html` | PIN giriş penceresi (dark theme) |
| `renderer/setup.html` | İlk kurulum sihirbazı (PKCS#11 + sertifika + sunucu) |

### Web UI (show.blade.php)

- `_signed_document_upload.blade.php`'ye E-İmza butonu eklendi
- Her step'te (metraj, tahakkuk, makbuz, taahhütname) E-İmza başlatma
- Ruhsat step'inde ayrı E-İmza butonu
- module_documents JSON'ında `e_imza` objesi ile takip
- AJAX → custom protocol → polling → UI güncelleme

## Veritabanı

### e_imza_transactions

| Kolon | Tip | Açıklama |
|---|---|---|
| id | BIGINT PK | |
| application_id | BIGINT FK | applications.id |
| pdf_type | VARCHAR(50) | ruhsat, pre_permit, taahhutname, metraj, tahakkuk |
| status | ENUM | pending, completed, expired, cancelled |
| transaction_id | VARCHAR(100) UNIQUE | txn_uuid |
| token | VARCHAR(255) | HMAC koruma |
| orijinal_pdf | VARCHAR(255) | PDF yolu |
| imzali_pdf | VARCHAR(255) NULL | İmzalı PDF yolu |
| imzalayan_info | JSON NULL | {ad, soyad, tckn, sertifika_turu} |
| expires_at | TIMESTAMP | 10dk |
| completed_at | TIMESTAMP NULL | |

### e_imza_istemcileri

Masaüstü uygulamalarının API key kaydı.

## İmza Akışı (Detaylı)

```
1. Kullanıcı "E-İmza ile İmzala" butonuna tıklar
2. JS → POST /api/e-imza/baslat {application_id, pdf_type}
3. Laravel:
   a. DomPDF ile PDF oluştur
   b. Transaction_id üret (txn_uuid)
   c. Token üret (HMAC-SHA256)
   d. PDF'i storage/app/e-imza/{tid}/orijinal.pdf kaydet
   e. e_imza_transactions tablosuna kaydet (status: pending, expires_at: 10dk)
   f. Response: {transaction_id, expires_at}
4. JS → window.location = "aykome://sign?tid=xxx&server=URL"
   → Custom protocol, Electron uygulamasını açar
5. Electron (renderer/pin.html):
   a. PIN giriş penceresi göster
   b. Kullanıcı PIN girer → "İmzala" butonu
6. IPC → main.js:
   a. GET /api/e-imza/pdf/{tid}?token=xxx → PDF indir
   b. PKCS#11 ile token'a bağlan:
      - Slot aç, session başlat
      - PIN ile login
      - Sertifikayı bul
      - Private key'i bul
   c. node-signpdf ile PAdES B-T imza
      - İmza görseli: "Bu belge Aykome E-İmza ile imzalanmıştır"
      - Ad Soyad, Tarih
   d. POST /api/e-imza/tamamla (multipart, X-EImza-Api-Key)
      - Transaction_id + imzalı PDF + imzalayan bilgisi
7. Laravel:
   a. API Key doğrula (EImzaApiKey middleware)
   b. İmzalı PDF'i kaydet
   c. module_documents JSON'ını güncelle
   d. Transaction status: completed
8. JS polling (3sn):
   GET /api/e-imza/durum/{tid}
   → completed görünce → Swal "İmza tamamlandı" → reload
```

## Geliştirme Durumu

- [x] Laravel: Migration + Model
- [x] Laravel: EImzaService (PDF oluşturma, transaction)
- [x] Laravel: EImzaController (4 endpoint)
- [x] Laravel: EImzaApiKey middleware + Routes
- [x] Laravel: Schedule cleanup
- [x] Web UI: show.blade.php buton + JS polling
- [x] Electron: Proje iskeleti (main.js, preload.js)
- [x] Electron: PKCS#11 (scanner, signer, cert-utils, simulate)
- [x] Electron: PAdES (node-signpdf + pdf-lib)
- [x] Electron: Network (PDF indir, imzalı PDF yükle)
- [x] Electron: PIN UI (pin.html)
- [x] Electron: Setup wizard (setup.html)
- [x] Electron: Custom protocol (aykome://)
- [x] Electron: Systray
- [x] Dependencies installed

## Test Stratejisi

1. **Mac (local):** `php artisan serve` → Electron simülasyon modu ile test
2. **Windows:** Kamu SM token tak → `electron-builder --win` ile setup.exe üret
3. **Sunucu:** Oracle DB için migration'ı çalıştır, .env'de `E_IMZA_ENABLED=true`

## Dağıtım

### Masaüstü
```bash
cd aykome-e-imza
npm run build:win    # Windows setup.exe → dist/
npm run build:mac    # Mac .dmg → dist/
```

### Sunucu
```bash
php artisan migrate
# .env'ye ekle:
# E_IMZA_ENABLED=true
# E_IMZA_API_KEY=<random-64-char>
```

## Notlar

- PAdES seviyesi: **B-T** (imza + zaman damgası)
- İmza görseli: "Bu belge Aykome E-İmza ile imzalanmıştır" + imzalayan adı + tarih
- Mühür görseli eklenmez
- Mac'te PKCS#11 simülasyon modu çalışır (gerçek token gerekmez)
- Token sertifika süresi local kontrol edilir (BTK sunucusuna gerek yok)
- electron-store ayarları: pkcs11_path, cert_serial, server_url, api_key
