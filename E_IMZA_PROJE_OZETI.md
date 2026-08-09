# Aykome E-İmza Proje Özeti

> **Tarih:** 30 Temmuz 2026
> **Proje:** AYKOME — Altyapı Yönetim ve Koordinasyon Merkezi
> **Ürün:** HGB Bilişim ULTRA SAAS v6.21
> **Stack:** Electron 28 + Laravel 11 + PKCS#11 + PAdES

---

## 1. Ne Yaptık — Tam Mimarı

### 1.1 Genel Akış

```
Web Uygulaması (Laravel)
  │
  ├── Kullanıcı "E-İmza ile İmzala" butonuna tıklar
  ├── AJAX → /api/e-imza/baslat (transaction_id + token alır)
  ├── Tarayıcı → aykome://sign?tid=...&token=...&server=... (deep link)
  │
  ▼
Electron Masaüstü Uygulaması
  │
  ├── Protocol handler (aykome://) yakalar
  ├── PIN penceresi açar
  ├── PDF'i sunucudan indirir (GET /api/e-imza/pdf/{id}?token=...)
  ├── Akıllı kart/PKCS#11 ile imzalar
  ├── İmzalı PDF'i sunucuya yükler (POST /api/e-imza/tamamla)
  │
  ▼
Web Uygulaması
  └── Sayfayı yeniler → imzalı belge görünür
```

### 1.2 Laravel Tarafı (Sunucu)

| Dosya | Görevi |
|---|---|
| `app/Http/Controllers/Api/EImzaController.php` | REST API: baslat, pdf, tamamla, indir, durum |
| `app/Services/EImzaService.php` | PDF oluşturma (DomPDF), transaction yönetimi, dosya saklama |
| `app/Models/EImzaTransaction.php` | Veritabanı modeli (e_imza_transactions tablosu) |
| `app/Http/Middleware/EImzaApiKey.php` | Machine-to-machine API Key doğrulama |
| `config/e-imza.php` | `E_IMZA_ENABLED`, `E_IMZA_API_KEY` |
| `routes/web.php` | `/api/e-imza/*` route'ları |

**API Endpoint'leri:**

| Endpoint | Metod | Auth | Açıklama |
|---|---|---|---|
| `/api/e-imza/baslat` | POST | Auth | Transaction başlat, PDF oluştur |
| `/api/e-imza/pdf/{id}` | GET | Token | İmzalanacak PDF'i indir |
| `/api/e-imza/tamamla` | POST | API Key | İmzalı PDF'i yükle |
| `/api/e-imza/durum/{id}` | GET | Auth | İşlem durumu sorgula |
| `/e-imza/indir/{id}` | GET | Auth | İmzalı PDF'i indir |

### 1.3 Electron Tarafı (Masaüstü)

```
aykome-e-imza/
├── main.js                          # Ana süreç (tray, IPC, protocol)
├── preload.js                       # contextBridge API
├── renderer/
│   ├── pin.html                     # PIN giriş penceresi
│   └── setup.html                   # 3 adımlı kurulum sihirbazı
├── src/
│   ├── protocol.js                  # aykome:// URL ayrıştırma
│   ├── config/store.js              # electron-store yapılandırması
│   ├── bridge/index.js              # Platform-agnostik PKCS#11 bridge
│   ├── pkcs11/
│   │   ├── signer.js                # İmza orkestratörü (cert + PAdES)
│   │   ├── cert-utils.js            # Sertifika ASN.1 ayrıştırma
│   │   ├── scanner.js               # PKCS#11 kütüphane detektörü
│   │   └── simulate.js              # Simülasyon modu (token yokken)
│   ├── pades/sign-pdf.js            # PAdES imza + görsel mühür
│   └── network/
│       ├── pdf-fetcher.js           # PDF indirme (axios)
│       └── uploader.js              # İmzalı PDF yükleme (multipart)
├── x64-worker/
│   ├── pkcs11-bridge.c              # C PKCS#11 bridge (macOS)
│   ├── pkcs11-bridge                # Derlenmiş x86_64 binary
│   ├── pkcs11.h / pkcs11t.h / pkcs11f.h  # PKCS#11 v2.40 header
│   └── bridge-worker.js             # Alternatif JS bridge
├── assets/
│   ├── icon.png                     # Tray ikonu
│   └── icon.ico                     # Windows installer ikonu
└── test-sign.js                     # CLI test (node test-sign.js)
```

### 1.4 İmza Akışı (Detaylı)

```
1. Electron, aykome:// protokolünü alır
2. PIN penceresini açar (her zaman üstte, 420x520)
3. PDF'i sunucudan indirir (token doğrulamalı)
4. PKCS#11 bridge'i çağırır:
   a. list → slot/token bul
   b. cert → sertifika DER oku
   c. cert-utils.js → CN, TCKN, issuer çıkar
   d. sign-pdf.js → PAdES placeholder ekle
   e. ByteRange hesapla
   f. PKCS#7 CMS oluştur (node-forge)
   g. sign → ECDSA imzala (akıllı kartta)
   h. İmzayı PKCS#7'ye göm
   i. PDF'e görsel mühür ekle (pdf-lib)
5. İmzalı PDF'i sunucuya yükle (multipart/form-data)
6. PIN penceresini kapat
7. Web sayfası yenilenir → imzalı belge görünür
```

### 1.5 Sertifika Bilgileri

**Gerçek Token:**
- Token: `AKIS_043727D2A91F90E0` (TÜBİTAK UEKAE Kamu SM)
- Anahtar: `CKK_EC` (secp384r1), non-extractable
- İmza Mekanizması: `CKM_ECDSA` (raw r||s → DER dönüşümü)
- İmzalayan: `ZEYNELABİDİN AKTAŞOĞLU`
- TCKN: `39758093026`
- PIN: `321463`

**Simülasyon Modu:**
- Self-signed RSA 2048-bit
- İmzalayan: `Ahmet YILMAZ`
- TCKN: `12345678901`
- PIN: `sim123`
- Kullanım: Geliştirme/test, token gerekmez

---

## 2. macOS vs Windows Arası Farklar

| Özellik | macOS | Windows |
|---|---|---|
| **Bridge** | C binary (`x64-worker/pkcs11-bridge`) | `pkcs11js` npm paketi |
| **Mimari** | ARM64 + Rosetta 2 (x86_64 binary) | Native x64 |
| **PKCS#11 yüklem** | `dlopen` ile C'de | `pkcs11js.PKCS11.load()` ile JS'de |
| **Kütüphane** | `libakisp11.dylib` | `akisp11.dll` (System32/SysWOW64) |
| **Derleme** | `arch -x86_64 gcc -arch x86_64` | MinGW veya MSVC (opsiyonel) |
| **Auto-start** | Yok (manuel LaunchAgent) | Windows Registry / Startup klasörü |
| **Kurulum** | DMG (manuel) | NSIS installer (.exe) |
| **Protocol handler** | `app.setAsDefaultProtocolClient` | `app.setAsDefaultProtocolClient` + NSIS kaydı |
| **Systray** | Tray (üst menü çubuğu) | Tray (sistem tepsisi) |
| **Test durumu** | ✅ Gerçek token ile çalışıyor | 🔄 Test edilmedi (derleme hazır) |

### 2.1 Platform Seçimi (Kod İçi)

`src/bridge/index.js`:
```js
class BridgeWorker {
  listTokens() {
    if (this.platform === 'win32') return new WinPkcs11Bridge(this.p11Path).listTokens();
    // macOS: C binary çağır
  }
  getCertificate(pin) {
    if (this.platform === 'win32') return new WinPkcs11Bridge(this.p11Path).getCertificate(pin);
    // macOS: C binary çağır
  }
  signData(pin, data) {
    if (this.platform === 'win32') return new WinPkcs11Bridge(this.p11Path).signData(pin, data);
    // macOS: C binary çağır
  }
}
```

### 2.2 WinPkcs11Bridge (pkcs11js API)

```js
const pkcs11js = require('pkcs11js');
const mod = new pkcs11js.PKCS11();
mod.load(libPath);
mod.C_Initialize();

const slots = mod.C_GetSlotList(true);
const session = mod.C_OpenSession(slots[0], CKF_SERIAL_SESSION | CKF_RW_SESSION);
mod.C_Login(session, CKU_USER, pin);

// Sertifika bul
mod.C_FindObjectsInit(session, [{ type: CKA_CLASS, value: CKO_CERTIFICATE }]);
const objs = mod.C_FindObjects(session);
mod.C_FindObjectsFinal(session);
const attrs = mod.C_GetAttributeValue(session, objs[0], [{ type: CKA_VALUE }]);

// İmza
mod.C_SignInit(session, { mechanism: CKM_ECDSA }, key);
const sig = mod.C_Sign(session, data, outBuf);

mod.C_Logout(session);
mod.C_CloseSession(session);
mod.C_Finalize();
mod.close();
```

---

## 3. Derleme Talimatları

### 3.1 macOS

```bash
# Bağımlılıkları yükle
cd aykome-e-imza
npm install

# Test et (gerçek token ile)
node test-sign.js /path/to/belge.pdf /tmp/imzali.pdf

# C bridge'i yeniden derle (gerekirse)
cd x64-worker
arch -x86_64 gcc -arch x86_64 -o pkcs11-bridge pkcs11-bridge.c -ldl

# Electron uygulamasını başlat
npm run dev     # Geliştirme modu
npm run build   # DMG paketle

# macOS build
npm run build:mac
```

### 3.2 Windows

```bash
# Bağımlılıkları yükle
cd aykome-e-imza
npm install

# NOT: pkcs11js native addon'u Windows'ta derlenmeli
# Windows Build Tools gerekli:
npm install --global windows-build-tools

# Electron uygulamasını derle
npm run build:win
# dist/ klasöründe Aykome E-İmza Setup.exe oluşur

# NSIS kurulumcusu:
# - autoLaunch: true (Windows başlangıcında otomatik açılır)
# - customProtocol: aykome (deep link kaydı)
# - createDesktopShortcut: true
# - createStartMenuShortcut: true
```

### 3.3 Ön Koşullar

| Gereksinim | macOS | Windows |
|---|---|---|
| **Node.js** | v20+ (ARM64) | v20+ (x64) |
| **Python** | Yok | build-tools için 3.x |
| **C Derleyici** | Xcode CLI tools | Visual Studio Build Tools |
| **Akıllı Kart** | AKIS token + `libakisp11.dylib` | AKIS token + `akisp11.dll` |
| **Rosetta 2** | Gerekli (x86_64 binary için) | Yok |

---

## 4. Kurulum Adımları (Son Kullanıcı)

### 4.1 Windows

1. **AKIS Sürücüsünü yükle** — Kamu SM kart okuyucu + `akisp11.dll`
   - Genellikle `C:\Windows\System32\akisp11.dll`
   - 32-bit sistem: `C:\Windows\SysWOW64\akisp11.dll`
2. **Aykome E-İmza Setup.exe** çalıştır
   - NSIS kurulumcusu ile:
     - Masaüstü kısayolu
     - Başlat menüsü kısayolu
     - `aykome://` protokol kaydı
     - Otomatik başlatma (Windows açılışında)
3. **Kurulum sihirbazı**:
   - Adım 1: Token seç (AKIS otomatik bulunur)
   - Adım 2: PIN gir, sertifika seç
   - Adım 3: Sunucu adresini gir (`https://aykome.eyyubiye.bel.tr`)
4. **Systray'de çalışır** — Aykome ikonu görünür

### 4.2 macOS

1. **AKIS Sürücüsünü yükle** — `libakisp11.dylib` (`/usr/local/lib/`)
2. **Aykome E-İmza.dmg** mount et, uygulamayı Applications'a sürükle
3. **İlk çalıştırmada**:
   - Kurulum sihirbazı açılır
   - Simülasyon modu veya gerçek token seç
4. **Systray'de çalışır** — Üst menü çubuğunda ikon

### 4.3 Kullanım

1. Web uygulamasına gir (`https://aykome.eyyubiye.bel.tr`)
2. Başvuru detayında "E-İmza ile İmzala" butonuna tıkla
3. Masaüstü uygulaması açılır → PIN gir
4. İmzalanır → PDF otomatik yüklenir
5. Sayfa yenilenir → İmzalı belge görünür

---

## 5. Gelecek Yapılacaklar

### 5.1 Sunucu (SSL + Deploy)

- [ ] **SSL Sertifikası kurulumu** — `aykome.eyyubiye.bel.tr`
  - Let's Encrypt (certbot) veya kurum içi CA
  - Nginx/Apache reverse proxy yapılandırması
- [ ] **Docker Compose deploy** — Güncellemeleri sunucuya çek
  ```bash
  git pull && docker compose down && docker compose up -d --build
  php artisan migrate --force
  php artisan config:cache && php artisan route:cache
  npm run build
  ```
- [ ] **SSL sonrası** `.env` güncelle:
  ```
  APP_URL=https://aykome.eyyubiye.bel.tr
  E_IMZA_ENABLED=true
  E_IMZA_API_KEY=eimza_aykome_dev_2026
  ```

### 5.2 Windows Build (Test)

- [ ] Windows makinede `npm run build:win` ile derleme testi
- [ ] `pkcs11js` native addon derleme sorunlarını çöz
- [ ] AKIS DLL otomatik bulma doğrulama
- [ ] NSIS kurulumcusu test (auto-start, protocol handler)
- [ ] Gerçek AKIS token ile imza testi

### 5.3 macOS İyileştirmeleri

- [ ] C bridge'de path parametresini düzgün çalıştır (şu an hardcoded)
- [ ] ARM64 native PKCS#11 bridge (eğer AKIS ARM64 dylib sağlarsa)
- [ ] Notarization (Apple Gatekeeper)

### 5.4 Güvenlik

- [ ] PIN brute-force koruması (maks 3 deneme)
- [ ] Token süre dolduğunda otomatik cleanup (`php artisan e-imza:temizle`)
- [ ] API Key rotasyon mekanizması
- [ ] Electron auto-updater (github releases)

### 5.5 Özellik Geliştirmeleri

- [ ] Çoklu sertifika desteği (E-Güven, TÜRKTRUST)
- [ ] Toplu imza (batch signing)
- [ ] İmza doğrulama (signed PDF doğrulama)
- [ ] İmza geçmişi log'u
- [ ]离线 mod (çevrimdışıyken kuyruğa al, sonra gönder)

---

## 6. Önemli Notlar

### 6.1 ECDSA ve node-forge Uyumsuzluğu

node-forge'un `certificateFromAsn1()` fonksiyonu ECDSA sertifikalarını desteklemez (sadece RSA). Çözüm:
- Sertifikayı manuel ASN.1 TLV parse et
- `forge.asn1.fromDer()` ile TBS certificate'i parse et
- `forge.pkcs7.createSignedData()` için manuel sertifika objesi oluştur
- İmza callback'i ile ECDSA imzayı dışarıdan sağla

### 6.2 ByteRange Hatan

`node-signpdf`'in `plainAddPlaceholder` fonksiyonu geleneksel xref tablosu kullanan PDF'lerle çalışır. xref-stream (PDF 1.5+) PDF'lerde çalışmaz. DomPDF çıktısı geleneksel xref kullanır → uyumludur.

### 6.3 Türkçe Karakter Sorunu

PDF görsel mühürde Türkçe karakterler (İ, ğ, ü, ş, ö, ç) WinAnsi font encoding'inde sorun çıkarır. Çözüm:
```js
cnName.replace(/İ/g, 'I').replace(/ı/g, 'i').replace(/ğ/g, 'g')...
```

### 6.4 C Bridge State Sorunu (macOS)

AKIS kütüphanesinin `C_Initialize(NULL)` çağrısı, kütüphane önceden yüklenip düzgün kapatılmazsa asılı kalabilir. Çözüm:
- Her işlem öncesi `dlopen` + `C_Initialize`
- Her işlem sonrası `C_Finalize` + `dlclose`
- State bozulursa, kütüphaneyi fork'lanmış child process'te çağır

---

## 7. Dosya Yapısı (Özet)

```
aykome-e-imza/
├── assets/              # İkonlar
├── renderer/            # HTML/JS UI
├── src/
│   ├── bridge/          # PKCS#11 bridge (platform seçici)
│   ├── config/          # electron-store
│   ├── network/         # PDF indirme/yükleme
│   ├── pades/           # PAdES imza inşası
│   └── pkcs11/          # Sertifika, scanner, signer, simulate
├── x64-worker/          # C bridge + pkcs11js
├── main.js              # Electron ana süreç
├── preload.js           # contextBridge
├── package.json         # Bağımlılıklar + build config
└── test-sign.js         # CLI test
```

```
app/
├── Http/
│   ├── Controllers/Api/EImzaController.php
│   └── Middleware/EImzaApiKey.php
├── Models/EImzaTransaction.php
├── Services/EImzaService.php
├── config/e-imza.php
└── routes/web.php       # e-imza route'ları
```

---

*HGB Bilişim ULTRA SAAS — AYKOME E-İmza Modülü*
