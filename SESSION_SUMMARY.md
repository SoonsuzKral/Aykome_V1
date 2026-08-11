# Oturum Özeti — 9 Ağustos 2026

## Sprint 15 — E-İMZA 5070 PDF KÖK NEDEN ÇÖZÜMÜ (11 Ağustos, akşam)

### 🎯 Öz
Sprint 13'ten devreden "İmzalandıktan sonra çıkan hatalı PDF" sorunu KÖKTEN çözüldü: 7 belge tipinin
tümü tek sayfa, taşma 0, 5070 metni doğru yerde + kırmızı, gerçek token ile imzalı e2e geçti.

### ✅ Kök Nedenler (hepsi kanıtlandı + çözüldü)
1. **dompdf box-sizing uygulamıyor** → `width + padding` taşıyordu; squeeze'daki `width:100% !important`
   de kaldırıldı (inline mm tek kaynak). Portrait `170mm`, landscape `245mm`.
2. **`a4ContainerInlineWidth` guard bug'ı:** `str_contains($html,'a4-container')` — landscape sınıfı
   (`a4-landscape-container`) bu alt dizgeyi İÇERMİYOR → metraj inline genişlik HİÇ almıyordu
   (2 sayfa + taşmanın gerçek nedeni). İki sınıf da aranıyor.
3. **XPath `translate()` Türkçe Ğ bug'ı:** sadece ASCII küçültür → "DOĞRULAMA" eşleşmiyordu, 5070 hep
   fallback'e düşüyordu. Çözüm: XPath filtre + PHP `mb_stripos`, son (en derin) eşleşme.
4. **dompdf absolute elemanlarda `bottom`'ı yok sayar** → cover_letter footer'ı statik akış konumunda,
   container `min-height`'i onu 2. sayfaya itiyordu. Çözüm: `.a4-footer` static + `margin-top:14mm`.
5. **`@media print` UYGULANMAZ** → blade'lerdeki geçersiz @page kuralları; `@page { margin:6mm !important }`
   enjekte ediliyor (iç alan: portrait 198×285mm, landscape 285×198mm).
6. **pre_permit remote logo** (`isRemoteEnabled=false` → boş) → base64 `logo_base64`'e çevrildi.

### ✅ Değişiklikler
- `DocumentTemplateService.php`: `a4ContainerInlineWidth` (guard + 170/245mm + `min-height:0`),
  `pdfCssEnjekte` (@page 6mm + squeeze kuralları + `width:100%` kaldırıldı).
- `EImzaService.php`: `imzaYasalMetinEkle($html,$imzaTarihi,$pdfType)` — **Grup A** (ruhsat/pre_permit/
  cover_letter): doğrulama kodu ÜSTÜNE, font inherit (squeeze 10.5px); **Grup B** (metraj/makbuz/tahakkuk/
  taahhutname): belge EN ALTINA. `pdfTipineGoreEkCss` (coverLetterSabitle yerine): cover_letter statik
  footer + taahhutname satır aralıkları. Logo base64 cover_letter + pre_permit.
- **Akşam ekleri (kullanıcı testi sonrası):**
  - `EImzaService::pdfOlustur`: pre_permit logosu ÖNCE `PreExcavationPermitSetting.logo_path`
    (belediye logosu; kurum logosu NULL olduğu için logo gelmiyordu), yoksa kurum logosu.
  - `EImzaService::pdfOlustur`: tahakkuk için `metraj_satirlari` = `buildMetrajSatirlari()` →
    matbu formda TÜM zemin tipleri (başvuruda olmayanlar 0 satırı).
  - `ApplicationsController::downloadPrePermit`: `logo_base64` eklendi (aynı öncelik).
- `pre_permit.blade.php:48` base64 logo; `cover_letter.blade.php` `sayi-konu-tablo` sınıfı.
- Test: `test_pdf_generate.php` 7 tip; `test_verify_all.py` fold tüm whitespace'i siler + taahhutname;
  `e2e_sign.cjs` (proje `type:module` olduğu için **.cjs** zorunlu).

### ✅ Doğrulama
- `test_verify_all.py` → **7/7 PASS** (1 sayfa, taşma yok, 5070 var, font tamam).
- 5070 yerleşimi programatik: Grup A üstte (ruhsat 760<793, pre_permit 517<549, cover_letter 488<514),
  Grup B en altta (metraj 232/253, makbuz 619/641, tahakkuk 369/390, taahhutname 735/755).
- Görsel kalite: 7/7 kırmızı 5070, kenarlar temiz, **0 karakter çakışması** (rawdict).
- **Gerçek token e2e:** 7 PDF Pkcs11Bridge.exe ile imzalandı (bu sertifika RSA, PIN 062954),
  imzalı PDF'ler: sayfa+MediaBox+metin+font birebir korundu, 5070 kırmızı, `/FT /Sig`+ByteRange var.
  Dosyalar: `storage/app/test_*_5070.pdf` + `e2e_signed_*.pdf`.
- Akşam ekleri: pre_permit PDF'inde logo GÖRSELİ gömülü (169x160 px); tahakkuk'ta TÜM zemin tipleri
  (SICAK ASFALT...GÖRME ENGELLİ KARO + ZTB + Genel Toplam); ikisi de 1 sayfa, taşma yok.
- `test_renderfor.php` taahhutname eklendi → BLADE yolu (şablon yok).

### 📁 Dokümantasyon
- `e_imza_sorun/ÇÖZÜM_01.md` (YENİ) — bugünkü çözümün tam hikayesi: 7 kök neden + çözümler +
  doğrulamalar + kalan işler.
- `e_imza_sorun/DURUM_RAPORU_20260811.md` — tüm bölümler güncellendi, §6 kalan işler kapatıldı.

### ⚠️ Notlar
- Görsel kontrol model PNG okuyamadığı için programatik yapıldı (karakter bbox çakışması + kenar kontrolü).
- `baslat/tamamla` HTTP katmanı DEĞİŞMEDİ (auth/API-key); istenirse tarayıcı E2E: başvuru 1254 →
  E-İmza ile İmzala → Electron PIN penceresi → 062954.
- **Kullanıcı geri bildirimi (yarına devreden):** ruhsat/metraj/taahhütname "on numara"; ÖN KAZI İZNİ
  logosu + TAHAKKUK tüm zeminler akşam DÜZELTİLDİ ve doğrulandı. KALAN: (1) A4 içinde boşluklar
  (içerik kağıdı tam doldurmuyor — @page 6mm + padding 8/12mm + blade margin kombinasyonu,
  cover_letter statik footer), (2) ruhsat A4'e "ufak tam oturmamış". Kullanıcı yarın görselleri
  Claude.AI ile gönderecek → analiz edip ince ayar yapılacak.
- `e2e_sign.cjs` uzantısı KRİTİK: proje kökü `package.json` `type:module` → `.js` CommonJS çalışmaz.

### 📁 Değişen Dosyalar
- `app/Services/DocumentTemplateService.php`, `app/Services/EImzaService.php`
- `resources/views/admin/pdf/pre_permit.blade.php`, `cover_letter.blade.php`
- `test_pdf_generate.php`, `test_verify_all.py`, `test_renderfor.php`
- `e_imza_sorun/DURUM_RAPORU_20260811.md` (tüm bölümler güncellendi, kalan işler kapatıldı)

---

## Sprint 14 — ADRES → OTOMATİK ZEMİN SATIRI + 📍 SATIR İKONU + ÇİZİM→METRAJ→ALAN (11 Ağustos)

### 🎯 Öz
Başvuru formlarında (create+edit) her "Mahalle & Sokak Ekle" girdisi artık Zemin Satırları tablosuna **otomatik zemin satırı** üretiyor; her satırda adres etiketi + **📍 harita ikonu** (2 haritada pulse marker); çizim→metraj akışı düzeltildi (düz çizgi de Alan m²'ye katılır, rowId'siz çizimler submit'te kaybolmuyor); alt kurumda adresli çizimsiz başvuru **JS + backend** engeli. `surface_lines.address` DB'ye kaydediliyor.

### ✅ 1. Adres → Otomatik Zemin Satırı (create + edit.blade.php)
- **`ensureSurfaceLineForAddress(mahalle, cadde)`**: mahalle&sokak listesine her cadde/sokak eklenince otomatik zemin satırı (dedupe'lu — aynı adres tekrar üretilmez). Örn: Batıkent Mah. + 8013/8014/8016/8020.SK → 4 otomatik satır.
- `addSurfaceLine()` → `address` alanı; `renderTable()` → zemin tipi altında 📍 adres etiketi; edit'te DB'den `address` ile yüklenir; submit'te `surface_lines[..][address]` hidden input ile gider.

### ✅ 2. 📍 Harita İkonu (satırlarda — YENİ istek)
- Her adresli satırda 🎯 Çiz butonunun yanına yeşil 📍 butonu: `maps.adres-ara` WMS → `haritadaGoster()` → **2 haritada da** pulse marker + tooltip + flyTo (cadde listesindeki 📍 ile birebir aynı davranış).

### ✅ 3. Çizim → Metraj → Alan (m²)
- **Otomatik satır bağlama**: Çiz butonuna basmadan doğrudan çizilse bile adresi dolu çizimsiz ilk satıra otomatik bağlanır (4 sokak art arda çizilse her çizim kendi satırına yazar).
- `syncArea` → genişlik önceliği: aktif satırın Genişlik (m) → poly_width → 1m; düz çizgide Uzunluk=çizgi, Miktar (m²)=len×width.
- `serializeAndSync` → rowId'siz düz çizgiler de Alan (m²)'e katılır (uzunluk × 1m); backend `MapDrawingService::calculateAreaM2FromGeoJson` **LineString → Haversine × 1m**.
- **Merge düzeltmesi (kritik)**: `prepareSurfaceLinesForSubmit` artık `polygon_geojson`'u rowDrawings ile EZMİYOR — mevcut rowId'siz feature'lar korunur (duplike rowId olmaz) → satıra atanmamış çizimler kaybolma sorunu çözüldü.
- `draw:created` sonunda toplamı ezen `_alanEl.value = row.quantity` yazımı kaldırıldı.

### ✅ 4. Backend
- `PricingService::upsertSurfaceLines` → `'address' => $data['address'] ?? null` (DB'ye kayıt; sütun mevcuttu).
- `ApplicationSurfaceArea` fillable'a `address` eklendi.
- **Alt kurum çizim zorunluluğu**: JS (submit engeli + uyarı) + backend `store`/`update`'te `hasAddressData() && !hasValidDrawing()` → `ValidationException` (kurum dışı alt kurumlarda — `isInstitutionApplication()` + kurum tipi kontrolü).
- Validasyon: `surface_lines.*.address` (nullable|string|max:500) — StoreApplicationRequest + ApplicationsController::update + UpdateSurfaceLinesRequest.

### ✅ 5. show.blade.php (veri kaybı koruması)
- "Zemin Satırlarını Düzenle" modalına **Adres kolonu** eklendi (thead + blade satırı + JS row template + colspan 7) — upsert delete+create yaptığı için adresler düzenleme sonrası korunur; `updateSurfaceLines` controller validasyonuna address eklendi.

### ✅ Doğrulama
- `php -l` 6 dosya temiz (PricingService, MapDrawingService, ApplicationsController, StoreApplicationRequest, UpdateSurfaceLinesRequest, ApplicationSurfaceArea)
- `php artisan view:cache` OK; DB'de `application_surface_areas.address` sütunu mevcut (doğrulandı)
- Kritik öğeler her iki blade'de doğrulandı: ensureSurfaceLineForAddress, row-show-btn, addHidden('address'), ilkAdresliCizimsiz, aykomeDrawingGoster, ZORUNLUDUR ✓
- `extractGeometries` FeatureCollection/Feature/LineString destekli ✓

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`, `show.blade.php`
- `app/Services/PricingService.php`, `app/Services/MapDrawingService.php`
- `app/Http/Controllers/Admin/ApplicationsController.php`, `app/Http/Requests/StoreApplicationRequest.php`, `app/Http/Requests/UpdateSurfaceLinesRequest.php`
- `app/Models/ApplicationSurfaceArea.php`, `SESSION_SUMMARY.md`

### 🧪 Test Senaryosu (tarayıcı)
Alt kurum → Batıkent Mah. + 8013/8014/8016/8020.SK ekle → 4 otomatik satır → satırdaki 📍 ile adresi haritada gör → zemin tipi seç → 🎯 Çiz → düz çizgi çiz → satırda Uzunluk/Miktar + Alan (m²) otomatik dolsun → kaydet → DB'de `address` kayıtları + çizimler dursun. Alt kurumda adres girip çizmeden kaydedilirse engel mesajı.

### ⚠️ Sıradaki / Kontrol
- Tarayıcı E2E: yukarıdaki senaryo; özellikle 4 adresli çizim akışı + show'dan satır düzenlemede adres korunumu
- Önceki sprint'ten devam: "İmzalandıktan sonra çıkan hatalı PDF" (Sprint 13, CLAUDE'A DEVREDİLEN madde) — imzalı PDF yerleşim/görsel kontrolü bekliyor

---

## Sprint 13 — PDF TÜRKÇE FONT + MAVİ BUTON TEMİZLİĞİ + İMZALAYAN OTOMASYONU (G1-G6)

### 🎯 Öz
Görevler: G1 Türkçe karakter (Helvetica çirkinliği), G2 PDF'te mavi UI butonları (B/I/A+/A-/Yazdır/Şablonu Düzenle), G3 PDF'lerin hâlâ indirilmesi, G4 Electron dll/token polling, G5 kırmızı EBYS imza damgası, G6 imzalayan bilgisinin otomatik alınması. **G1-G5 tamam ve doğrulandı; G6 tamam. Proje ayakta.**

### ✅ G1 — Türkçe karakter (KÖK NEDEN + KESİN ÇÖZÜM)
- Kök neden: dompdf Type1 (Helvetica/Times/Courier) Türkçe render EDEMEZ; config font_family remap built-in fontlara işlemiyordu.
- Çözüm: `DocumentTemplateService::pdfCssEnjekte($html)` — dompdf'e verilen HER HTML'e `<style>*{font-family:"DejaVu Sans",sans-serif !important}</style>` enjekte edilir (`</head>` öncesi).
- DejaVu fontlar storage/fonts'ta (24 dosya) + config/dompdf.php remap/isfontSubsettingEnabled.

### ✅ G2 — PDF'teki mavi butonlar (KÖK NEDEN + ÇÖZÜM)
- Kök neden: `admin/pdf/*.blade.php` blade'leri print-bar + toolbar (B/I/A+/A-) + "Yazdır/Şablonu Düzenle" HTML'ini gömüyor; `@media print{display:none}` ile gizleniyorlar AMA **dompdf `@media` kurallarını UYGULAMAZ** → hepsi PDF'e sızıyordu.
- Çözüm: `pdfCssEnjekte` aynı CSS ile `.no-print,.no-print-bar,.print-bar,.toolbar{display:none !important}` ekler.
- Uygulanan noktalar (7): EImzaService::pdfOlustur (template + blade akışı), ApplicationsController::generatePaymentReceipt + downloadPermitLive (loadView→render+enjekte+loadHTML), LicenseService::downloadPermit, FieldReportController::exportPdf, ReportController::exportPdf, WorkOrderController::exportPdf.

### ✅ G3 — PDF indirme → yeni sekmede açma (önceki sprint) + bu oturumda imzali_url akışı doğrulandı.

### ✅ G4 — Electron dll/token polling (önceki sprint'te tamamlandı; start.ps1'de çalışır).

### ✅ G5 — Kırmızı EBYS damga (önceki sprint'te kuruldu; bu oturumda doğrulandı)
- Damga: "Bu çıktı, 5070 sayılı... kağıt kopyasıdır." + "Bu belge güvenli elektronik imza ile imzalanmıştır." + "İmzalayan: Ad Soyad (Unvan)" — PyMuPDF ile 7 PDF türünde doğrulandı (damga ✓, İmzalayan ✓, sadece DejaVu ✓, Türkçe karakter ✓, toolbar kelimesi 0 ✓).

### ✅ G6 — İmzalayan Bilgisi modali KALDIRILDI (YENİ)
- `show.blade.php`: "İmzalayan Bilgisi" Swal formu (makam/ad/soyad/checkbox) TAMAMEN silindi; `E-İmza ile İmzala` → doğrudan `POST /api/e-imza/baslat` (imzalayan body'de YOK).
- `EImzaService::kullanicidanImzalayan(User $user)` (YENİ): ad/soyad `users.name`'den ayrıştırılır, unvan Spatie rolünden Türkçe map: municipality-makam→"Belediye Başkan Yardımcısı", municipality-admin→"Belediye Başkanı", municipality-mudur→"Fen İşleri Müdürü", municipality-sef→"Şef", municipality-buro→"Büro Personeli (Paraf)", institution-*→Kurum rolleri, field-team→"Saha Personeli". `ad_yazilsin=true` (ad HER imzada yazılır).
- `EImzaController::baslat`: imzalayan validasyonu kaldırıldı; `EImzaService::kullanicidanImzalayan(auth()->user())` ile backend'de doldurulur.
- Test: "Test Personeli" → `{"ad":"Test","soyad":"Personeli","unvan":"Büro Personeli (Paraf)","ad_yazilsin":true}` + PDF damga satırı "İmzalayan: Test Personeli (Büro Personeli (Paraf))" doğrulandı.

### 🔧 Altyapı (ayakta)
- `start.ps1` düzeltildi: `php.cmd` → `php` (winget PHP'de php.cmd yoktu; tüm servisler bu yüzden açılmıyordu).
- Çalışanlar: Oracle+Redis (Docker), Laravel 8001, Vite 5173 (npm run dev), Reverb 8090, queue:work, Electron. Doğrulama: Vite 200 ✓.

### 📁 Değişen Dosyalar (bu sprint)
- `app/Services/DocumentTemplateService.php` (+pdfCssEnjekte), `app/Services/EImzaService.php` (+kullanicidanImzalayan), `app/Http/Controllers/Api/EImzaController.php` (baslat imzalayan Auth'ten), `resources/views/admin/applications/show.blade.php` (Swal modali silindi), `app/Http/Controllers/Admin/ApplicationsController.php`, `app/Services/LicenseService.php`, `FieldReportController.php`, `ReportController.php`, `WorkOrderController.php` (loadView→pdfCssEnjekte+loadHTML), `start.ps1` (php.cmd→php).

### ⚠️ CLAUDE'A DEVREDİLECEK — "İMZALANDIKTAN SONRA ÇIKAN HATALI PDF" (kullanıcının tarifine göre)
1. **İmzalı PDF akışı (electron)**: orijinal.pdf artık temiz; imzalı nüsha (PAdES sonrası imzali_pdf) yerleşim/görsel bozuklukları için kontrol: dompdf subset fontlarının pdf-lib normalize + node-signpdf akışında korunması.
2. **Şablon (renderFor) akışı**: global/override şablon HTML'i kendi CSS'ini içerebilir (`@media print` blokları, font shorthand `font: bold 10pt Helvetica`). pdfCssEnjekte `* !important` ile eziyor ama şablon içindeki `.no-print` benzeri UI kalıntıları şablonda yoksa sorun yok; yine de şablonlu PDF'ler (ruhsat/tahakkuk excel-grid) DejaVu genişliğiyle taşma yapabilir → yerleşim kontrolü şart.
3. **Logolar**: şablon/blade `<img src="/storage/...">` → dompdf `isRemoteEnabled` kapalıysa boş kutu. Base64 embed önerilir (cover_letter'da logo_base64 yapılıyor; diğer belgeler kontrol edilsin).
4. **isRemoteEnabled / setOptions** kontrolü config/dompdf.php'de.
5. Doğrulama aracı hazır: PyMuPDF — font listesi (Helvetica/Courier/Times OLMAMALI), toolbar kelimeleri (Yazdır/Şablonu Düzenle/Bold/Italic OLMAMALI), damga satırları + "İmzalayan: ..." VAR olmalı. Kullanıcı imzalı PDF'te tam olarak NEYİN bozuk olduğunu söylerse ona göre kök neden bulunur.

---

## Sprint 12 — E-İMZA GERÇEK TOKEN İLE UÇTAN UCA DOĞRULANDI + BUG-1..4 ÇÖZÜMÜ

### 🎯 Öz
Proje ayağa kaldırıldı (Oracle+Redis+Laravel 8001+Vite+Electron), E-İmza hataları (BUG-1..4) çözüldü ve **gerçek AKIS token (AKIS_04815172B91F9090, MUSTAFA KEMAL KARATAŞ / TÜBİTAK Kamu SM v6, P-384)** ile gerçek ECDSA imzası + PAdES imzalı PDF üretildi.

### 🟢 Altyapı (çalışır durumda bırakıldı)
- Oracle `aykome-v6-oracle` (1521, aykome_user) + Redis (6379) → `docker start`
- `php artisan serve --port=8001` (0.0.0.0) — HTTP 200
- `npm run dev` (Vite 5173) — `public/build` yoktu, Vite dev server gerekliydi
- Electron local server 57898 `/health` → `{"status":"ok"}` (yeni kodla yeniden başlatıldı)
- 11 migration idempotent guard'larla `migrate --force` → **Pending 0**; `storage:link` kuruldu

### ✅ BUG-1 — `baslat` 422 (KRİTİK) — ÇÖZÜLDÜ
- `EImzaController::baslat`: `in:...` validasyonu `_signed` modülleri reddediyordu
- Normalize map: `cover_letter_signed→cover_letter`, `on_kazi_signed→pre_permit`, `metraj_signed→metraj`, `taahhutname_imzali→taahhutname`, `ruhsat_teslim→ruhsat`; geçersiz tip → 422 JSON

### ✅ BUG-2 — Pkcs11Bridge ofset riski — TEST EDİLDİ, SORUN YOK
- AKIS DLL ile `list` → `SLOTS 1 TOKEN AKIS_04815172B91F9090` (ofsetler doğru)
- **Yeni bulgu:** AKIS `cSign` null pointer'lı length sorgusunu reddediyor (0x21 CKR_ATTRIBUTE_SENSITIVE) → 512B ön-ayrılmış buffer ile çözüldü
- **Yeni bulgu:** AKIS CKM_ECDSA ham veri değil **48 byte SHA-384 digest** imzalıyor; `CKM_ECDSA_SHA384` (0x1045) ise MECHANISM_INVALID → CKM_ECDSA + SHA-384 prehash doğru yol. PAdES (sign-pdf.js) zaten SHA-384 üretiyor ✓

### ✅ BUG-3 — `eimza-status` div — ÇÖZÜLDÜ
- `show.blade.php` header'a div eklendi; JS portlar bitince kırmızı "Bulunamadı" gösteriyor, başarıda yeşil

### ✅ BUG-4 — cert_serial kullanılmıyordu — ÇÖZÜLDÜ (AKIS gerçeğiyle)
- **Debug kanıtı:** AKIS sertifika objesinde `CKA_SERIAL_NUMBER = 0x00` (boş!), eşleştirme anahtarı `CKA_ID` = SubjectKeyIdentifier (8AF5073D63381357403FE4E014DC4DC061A2DCE6)
- `Pkcs11Bridge.cs` `cert` komutu opsiyonel serial: CKA_SERIAL_NUMBER → CKA_ID sırasıyla eşleştirir (SerialEquals leading-zero tolerant); yanlış serial → ERR No cert ✓
- `cert-utils.js` → `skiHex` çıkarımı (2.5.29.14), `main.js list-certs` → `serial: skiHex || serialHex` (setup'ta store'a SKI kaydedilir)
- `src/bridge/index.js` + `signer.js` → `getCertificate(pin, certSerial)` zinciri

### ✅ Yeni tespit — xref stream uyumsuzluğu — ÇÖZÜLDÜ
- node-signpdf 3.0.0 klasik xref tablo ister; pdf-lib save() xref STREAM üretiyor → "Expected xref at NaN"
- `pdf-fetcher.js`: indirilen PDF xref stream içeriyorsa `PDFDocument.load + save({useObjectStreams:false})` ile normalize

### ✅ Uçtan Uca Canlı Test (gerçek token)
```
cert (SKI'li) → CERT_OK        | cert (yanlış serial) → ERR No cert
sign (SHA-384) → SIGNATURE (DER ECDSA, 101B)
executeSign → PAdES imzalı PDF (19 KB) — ByteRange ✓ adbe.pkcs7 ✓ CN: MUSTAFA KEMAL KARATAŞ TCKN: 47788818304
```

### 📁 Değişen Dosyalar
- `app/Http/Controllers/Api/EImzaController.php` (pdf_type normalize)
- `app/Http/Controllers/Admin/ApplicationsController.php` (viewModuleDocument normalize)
- `resources/views/admin/applications/show.blade.php` (eimza-status div + JS kırmızı durum)
- `resources/views/admin/applications/_signed_document_upload.blade.php` (sync baseKey `_imzali/_teslim/on_kazi→pre_permit` + eImzaDone sync bakışı)
- `aykome-e-imza/x64-worker/Pkcs11Bridge.cs` (serial/CKA_ID eşleştirme, 512B sign buffer, temiz final) + exe yeniden derlendi (eski exe: `Pkcs11Bridge.exe.bak`)
- `aykome-e-imza/src/bridge/index.js`, `src/pkcs11/signer.js`, `src/pkcs11/cert-utils.js` (skiHex), `main.js` (list-certs SKI), `src/network/pdf-fetcher.js` (xref normalize)

### ⚠️ Notlar
- test-init.js (pkcs11js) eski — AKIS ile C_Initialize CKR_ARGUMENTS_BAD; bridge yolu kullanılıyor, dosya zararsız
- Setup kurulumunda `cert_serial` store'a SKI olarak kaydedilecek (yeni kurulumlar); eski kayıtlar `cert_serial` uyuşmazsa ilk sertifikaya düşer (fallback)

### Sıradaki
- Tarayıcı E2E: başvuru detayında "E-İmza ile İmzala" → Electron PIN penceresi → tamamla → "E-İmzalandı" şeridi + Görüntüle
- Canlı deploy: migration + .env (güçlü E_IMZA_API_KEY, APP_URL=https) + Electron `server_url` canlı + `npm run build:win` → Setup.exe
- Git commit + push (onay bekliyor)

---


## Sprint 11 — LOCAL SHP CADDE SORGULAMA + 15m KONTROL (Sonnet 4.6 analizi)

### 🎯 Öz
Kullanıcı hataları Claude Sonnet 4.6'ya verdi; Sonnet `claude_opus/` klasörüne 3 dosya koydu (`AYKOME_ANALIZ_VE_COZUM.md`, `CLAUDE_CODE_PROMPT.md`, `maps-address.js`). Debug ettik + Sonnet'in yaklaşımını (local SHP) düzeltilmiş haliyle entegre ettik.

### 🔍 Debug Bulguları (kanıtlandı)
1. `15_alti.js` (3288 cadde) + `15_ustu.js` (908 cadde) projede hazır — WFS'e gerek yok.
2. `KADIKENDİ` bbox'ında local SHP'te **335 cadde** var (dün WFS'te 5'ti) — local çok daha kapsamlı.
3. **Sonnet'in `maps-address.js` hatalı:** `CADDE_SOKAK_ADI` alanı arıyor ama 15_alti.js'te **YOK** (doğrusu `CADDE_SO_1` + `CADDE_SO_2`).
4. `MAHALLE_AD` Türkçe karakter sorunlu (`KADIKENDI` vs `KADIKENDİ`) + tutarsız — bbox filtresi doğru yol.

### ✅ Yapılanlar
1. **`public/js/maps-address.js` (YENİ)** — Sonnet'ten düzeltilmiş:
   - `caddeAdi()` → `CADDE_SO_1 + ' ' + CADDE_SO_2` (doğru alan)
   - `buildTumCaddeler()` → 15_alti+15_ustu birleştir (3775 benzersiz cadde)
   - `caddelerInBbox()` → bbox filtresi (MAHALLE_AD'a güvenme)
   - `sokakAra()` → "8125" → "8125 SOKAK"
   - `nearestRoadAnd15()` → 15m ALT/ÜST kararı (local)
   - `parseAdres()` → adres çözümleme
2. **Blade (create+edit)** — script tag'lar: `15_alti.js` + `15_ustu.js` + `maps-address.js`
   - Mahalle-bul handler'ı **local-first**: `aykomeCaddelerInBbox` → WFS yedek
   - 15m kontrol butonu **local**: `aykome15mKontrol` (roadQuery yerine)
3. **MapsController** — `wfsCaddeBul` Eyyübiye bbox default (önceki sprintten kalan)
4. **Harita katman** — Google uydu HTTP→HTTPS + Esri yerine Google uydu

### ✅ Canlı Test Sonuçları
- `buildTumCaddeler()` → **3775 cadde**
- `sokakAra('8125')` → **"8125 SOKAK"**
- `caddelerInBbox(KADIKENDİ)` → **288 cadde** (11 NISAN ÇARSISI, 2078, 8001...)
- `nearestRoadAnd15(38.74,37.14)` → `{source:'ustu', cadde:'8010 SOKAK', genislik:'20'}` → **15m ÜSTÜ**

### 📁 Değişen Dosyalar
- `public/js/maps-address.js` (yeni), `resources/views/admin/applications/create+edit.blade.php`
- `resources/views/maps/index.blade.php` + `_harita.blade.php` (HTTPS Google uydu + search)
- `app/Http/Controllers/MapsController.php` (Eyyübiye bbox)
- `claude_opus/` (Sonnet dosyaları — referans)

---
## Sprint 10 — OPUS WFS ÇÖZÜMÜ ENTEGRASYONU (WFS 1.1.0 + BBOX + mahalleler öncache)

### 🎯 Öz
Kullanıcı WMS konum sorununu Claude Opus 4.5'e verdi; Opus `claude_opus/` klasörüne 3 dosya koydu (`MapsController.php`, `maps_routes.php`, `adres_cascading_blade.html`). Canlı WFS testleriyle doğrulayıp Opus'un İYİ kısımlarını mevcut sisteme entegre ettik; hatalı kısımlarını (trUpper, m_Numarataj) eledik.

### ✅ Opus'un Doğrulanan İyi Katkıları (entegre)
1. **WFS 1.1.0** — 2.0.0'dan daha stabil. Canlı test: `ILCE_NO='63011'` → 165 Eyyübiye mahallesi, `BBOX=...` → cadde geldi.
2. **`BBOX` query parametresi** — `BBOX="minLng,minLat,maxLng,maxLat,EPSG:4326"` (CQL'deki karmaşık `BBOX(GEOMETRY,...)`'den basit).
3. **`mahalleler` öncache endpoint** (`GET /maps/mahalleler`) — tüm Eyyübiye mahalleleri cache'li (1 saat), client-side arama. Frontend autocomplete artık önyüklü listeden filtreler (API isteği yok).
4. **Cascading autocomplete** — mahalle yazınca öneri dropdown, seçince cadde listesi otomatik (''Bul'' tetik alır).

### ❌ Opus'un Hatalı Kısımları (Elenen)
1. **`trUpper()` yanlış** — `Kadıkendi`→`KADIKENDI` (ASCII I); bizim `trUppercase()` (i→İ, ı→I) doğru, korunan.
2. **`smpns:m_Numarataj` (kapı) 500 hatası** — canlı testte XML ExceptionReport; Opus'un `kapiNumaralari` endpoint'i çalışmayacak. Çözüm: `kapiNoAra()` bina fallback (`smpns:MISMAP_NUM_BINA.ULUSAL_BINA_NO` + ADA/PARSEL/MAHALLE).

### ✅ Backend Yeni
- `wfsGet(array $params)` — ortak WFS 1.1.0 HTTP client (SSL verify=false)
- `mahalleler(Request)` — tüm Eyyübiye mahalleleri (cache 1h, ?q= filtre)
- `kapiNoAra(Request)` — bina fallback (ULUSAL_BINA_NO)
- `centroid()`, `flattenCoords()`, `getBbox()` — GeoJSON geometri yardımcıları
- route: `GET /maps/mahalleler`, `GET /maps/kapi-no`

### ✅ Frontend Yeni (create + edit)
- Mahalle inputuna **cascading autocomplete**: sayfa yüklenirken `fetch('/maps/mahalleler')` → client-side filtre (yazınca anında) → seçimde input dolar + "🔍 Bul" otomatik tetiklenir.

### ✅ Canlı WFS Doğrulama
- `mahalleler` (ILCE_NO=63011) → **165 mahalle** (BATIKENT, BÜYÜKHAN, YENİCE, KÜÇÜKHAN, BEYAZYAPRAK...)
- Bina fallback → 5 bina BBOX'ta (ADA/PARSEL/MAHALLE dolu, m geen no tespit)

### 📁 Değişen Dosyalar
- `app/Http/Controllers/MapsController.php` (+wfsGet, mahalleler, kapiNoAra, centroid, getBbox)
- `routes/web.php` (+mahalleler, kapi-no)
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php` (cascading autocomplete + önyükleme)
- `claude_opus/` (Opus dosyaları — referans)
- `SESSION_SUMMARY.md`

### ⚠️ Bilinen Sınır
- Gerçek kapı numarası WMS'te yok (m_Numarataj 500). Bina fallback (`ULUSAL_BINA_NO`) çalışır ama kapı no eşleşmesi sınırlı. Kullanıcı kapı no için bina veya serbest metin kullanır.

---
## Sprint 9b — WMS ADRES BULMA DÜZELTMELERİ (Türkçe İ + tam bbox nokta atışı)

### 🎯 Öz
Sprint 9a'da kurulan WMS adres bulma "işe yaramadı" raporu üzerine debug + kök neden çözüldü. Kullanıcının gerçek adresi **"8125. Sk. 122 Kadıkendi, 63000 Eyyübiye/Şanlıurfa"** ile uçtan uca canlı WMS testi geçti.

### 🐛 Kök Nedenler
1. **parseAddress bozuktu** — "8125. Sk. 122 Kadıkendi" girdisinde mahalle boş, kapı=8125 (sokak no), cadde çöp geliyordu. Türkçe adres formatlarına göre yeniden yazıldı: `mahalle='Kadıkendi', cadde='8125', kapi='122'`.
2. **GeoServer ILIKE Türkçe İ sorunu** — `mb_strtoupper('Kadıkendi')` → `KADIKENDI` (ASCII I) üretiyor ama GeoServer verisi `KADIKENDİ` (Türkçe İ noktalı). `KADIKENDİ` eşleşiyordu, `KADIKENDI` değil. Çözüm: **`trUppercase()`** — Türkçe kurallı büyütme (küçük `i`→`İ`, küçük `ı`→`I`) + `turkeVariants()` (Türkçe/ASCII varyant döngüsü).
3. **bbox filtre CRS** — `BBOX(GEOMETRY,...)` host'te 0 dönüyor; **`BBOX(GEOMETRY,...,'EPSG:4326')`** (CRS parametresi) şart. Kanıtlandı.

### ✅ Doğru Katman Şemaları (canlı DescribeFeatureType)
| Katman | Doğru Alan |
|---|---|
| `cbs:MISMAP_MAHALLE_KOYLER` | `MAHALLE_ADI` |
| `cbs:MISMAP_CADDE_SOKAK` | `CADDE_SOKAK_ADI` |

### ✅ Nokta Atışı (tam bbox)
- `wfsMahalleBul` artık mahalle poligonunun TAM bbox'ını dönruyor
- `wfsCaddeBul` caddeyi **mahalle bbox'ı içinde** arıyor → "8125 SOKAK" 2 yerde varsa (38.84 & 38.74) Kadıkendi'deki (38.74) seçilir

### ✅ E2E Canlı Test (kullanıcının adresi)
```
PARSE: mah=Kadıkendi cad=8125
MAHALLE OK: KADIKENDİ
CADDE '8125': 1
  OK: 8125 SOKAK
```

### 📁 Değişen Dosyalar
- `app/Http/Controllers/MapsController.php` (parseAddress, trUppercase, turkeVariants, wfsMahalleBul/CaddeBul bbox)

---
## Sprint 9a — WMS NOKTA ATIŞI ADRES BULMA (Mahalle→Cadde→Sokak→Kapı)

### 🎯 Öz
Başvuru formlarına (create+edit) WMS tabanlı nokta atışı adres bulma kuruldu. İki yöntem: (1) adresi tam yaz → backend mahalle/cadde/kapı ayırır → WMS doğrular → haritada göster; (2) mahalle yaz → "🔍 Bul" → o mahallenin tüm cadde/sokakları gelir → autocomplete ile seç. Aykome Maps'teki ada/parsel mantığının mahalle/cadde uyarlaması.

### ✅ Backend (`MapsController`)
- `adresAra(q)` — parseAddress() ile mahalle/cadde/kapı ayırır; WFS sırası: `MAHALLE_KOYLER`→`CADDE_SOKAK`. Cache 10dk.
- `mahalleCaddeler(mahalle)` — mahalle poligonu (`MAHALLE_ADI ILIKE`) → bbox → `BBOX(GEOMETRY,...,'EPSG:4326')` ile o mahallenin TÜM caddeleri.
- route: `GET /maps/adres-ara`, `GET /maps/mahalle-caddeler`
- **DOĞRULANMIŞ ŞEMA (canlı GeoServer testi):**
  - Mahalle katmanı `cbs:MISMAP_MAHALLE_KOYLER` → `MAHALLE_ADI` (MAHALLE_AD YANLIŞ)
  - Cadde katmanı `cbs:MISMAP_CADDE_SOKAK` → `CADDE_SOKAK_ADI` (CADDE_SO_1/2 YOK)
  - `BBOX` filtresinde CRS şart: `BBOX(GEOMETRY, minx,miny,maxx,maxy,'EPSG:4326')` (native bbox 0 döner)
  - INTERSECTS tam poligon CRS uyumsuz — BBOX-CRS doğru yöntem

### ✅ Frontend (create + edit)
- "📍 Konum Bul" butonu + locSpin spinner → `maps/adres-ara` → pulse marker + tooltip (cadde adı) 2 haritada (`appDrawMap` + `appCbsMap`) + animasyonlu `flyTo`
- "+ Mahalle & Sokak Ekle": her mahalle satırında "🔍 Bul" butonu → `mahalle-caddeler` → ufak dropdown (autocomplete) → seçince cadde satırı eklenir (tekrar WMS gitme gerekmez)
- Her cadde satırında "📍" (Göster) butonu → WMS'ten tek cadde ara → pulse marker
- `search-spinner` + `locSpin`/`locPulse` animasyon CSS

### ✅ Doğrulama
- Canlı WMS testi: EYYÜBİYE mahallesi poligonu (63 nokta) + "3097 SOKAK", "3129 SOKAK" vb. 5 cadde geldi
- php -l, route:list, view:cache, node --check her iki dosya OK

### 📁 Değişen Dosyalar
- `app/Http/Controllers/MapsController.php` (+adresAra, mahalleCaddeler, parseAddress, wfs* yardımcıları)
- `routes/web.php` (+2 route)
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`
- `SESSION_SUMMARY.md`

---

## Sprint 10 — BİLGİ KATMANI (Dinamik Alan Seçici) + Üst Yazı Hasar Düzeltmeleri

### 🎯 Öz
Taslak / Şablon Yönetimi editörüne **Bilgi Katmanı** eklendi: sağda sidebar panel, içinde başvurudan gelecek tüm alan adları (sunucudan JSON). Kullanıcı alana tıklayınca imleç konumuna `{alan_adi}` token'ı eklenir; PDF'te başvurunun kendi verisiyle değiştirilir. Böylece başvuru verilerinin NEREYE/NEYE geleceğine kullanıcı karar verir — 21 alan, 5 grup (Başvuru/Kişi/Tarihler/Alanlar/İmza). TÜM belge tiplerinde çalışır.

### ✅ Yeni özellik — `app/Services/DocumentTemplateService.php`
- `fieldCatalog(): array` — 5 grup, 21 alan (kayıt: key/label/tip).
- `fieldValue(Application, $key): string` — token→veri eşlemesi (match); bilinmeyen `''` → token dokunulmaz. İmza alanları kurum fallback'li (`$app->mudur_adi ?? $app->institution?->mudur_adi`).
- `hydrateTemplateTokens($html, $app)` — GENEL hidrasyon: Adım 1 mevcut sabit cover token map'i (`{KURUM_ADI}` vb.) korunur; Adım 2 `preg_replace_callback('/\{([a-z_çğıiöşü0-9]+)\}/u')` dinamik token'ları `fieldValue`'dan besler, `e()` escape.
- `renderFor()` — cover_letter koşulu KALDIRILDI → TÜM belgelerde token hidrasyonu.

### ✅ Controller + Editör
- `DocumentTemplateController::editorView()` +`$data['fieldCatalog']` (3 edit metoduna otomatik).
- `editor.blade.php`: `#fp-toggle` butonu + `#field-panel` sağ panel (300px, translateX animasyon), `renderCatalog()` grup çizimi + `#fp-search` filtre, `insertToken()` → `document.execCommand('insertText')` (imleç korunur; kilitli hücre `isLockedCell` guard; READ_ONLY'de panel gizli). `body.panel-open .editor-wrap { right:300px }`.

### ✅ Hasar düzeltmeleri (kullanıcı raporları)
- **GEÇERSİZ/TASLAK kök neden:** Kurum şablonları blade'den `sampleApp` (verification_code boş/GEÇERSİZ) ile üretildiğinde footer'daki doğrulama kodu STATİK gömülüyordu → `maskCoverDynamicFields` `{DOGRULAMA_KODU}` token'ına çevirir, hidrasyonda `$app->verification_code` basılır. ✅
- **Dicle imza yetkilileri basılmama:** Şablon imza kutusu `sampleApp`'te boş statik `<b></b>` gömüyordu → `maskCoverDynamicFields` `{TESIS_SORUMLUSU}`, `{MUDUR_ADI}`, `{MUDUR_UNVAN}`, `{DUZENLEYEN}`, `{KAZI_MIKTAR}` token'larına çevir; hidrasyonda başvuru/kurum verisiyle doldurulur (fallback). ✅
- **Sayı/proje kodu/adres gelmiyor:** Bu alanlar şablonda hiç token'sızdı → kullanıcı artık Bilgi Katmanı'ndan `{basvuru_no}`, `{proje_kodu}`, `{adres}` vb. ekler. ✅
- **Çift logo:** `maskCoverDynamicFields` şablona gömülü `<img>`'i siler; runtime'da `downloadCoverLetter` dinamik enjkete eder (tek logo). ✅
- **Print-bar sıkışıkığı:** `wrapStandalone` + `cover_letter.blade.php` print-bar yeniden tasarlandı: başlık + `✕ Kapat` + `📄 PDF Olarak Kaydet` + `🖨️ Yazdır` (geniş, gradient, butonlar ayrık). ✅

### ✅ Doğrulama
- `php -l` 3 dosya OK; `view:cache` OK.
- `fieldValue` uç testi: basvuru_no→2026-0972, proje_kodu→6325121, kurum_adi→DICLE, baslangıc→05.08.2026, kazi_miktari→10,00 m²/m. Bilinmeyen token korunur.
- `renderFor` (Dicle 972): sabit token kalıntısı 0, print bar YENİ, şablonda logo <img> 0, doğrulama kodu BASILDI, GEÇERSİZ/TASLAK YOK.
- 7 kurum şablonu reseed edildi (yeni token'lar eklendi).

### 📁 Değişen Dosyalar
- `app/Services/DocumentTemplateService.php`, `app/Http/Controllers/Admin/DocumentTemplateController.php`
- `resources/views/admin/document-templates/editor.blade.php`, `resources/views/admin/pdf/cover_letter.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki
- Tarayıcıda: super-admin → Taslak/Şablon → Üst Yazı düzenle → Bilgi Katmanı paneli → alan tıkla → kaydet → PDF'te başvuru verisi basılı. Kurum şablonu düzenleme + yeni kurum otomatik şablonu kontrolü.

---

## Sprint 9 — KURUM BAZLI ÜST YAZI ŞABLON YÖNETİMİ (merkezden, dinamik kurum adı)

### 🎯 Öz
Alt kurumlara Taslak/Şablon yönetimi kapatılmıştı; artık MERKEZ belediye, her alt kurumun (AKSA, Dicle, ŞUSKİ, TT, Turkcell, Vodafone...) Üst Yazı şablonunu ayrı ayrı düzenliyor. Yeni alt kurum eklenince üst yazı şablonu otomatik oluşuyor (master/blade kopyası + dinamik kurum adı). Alt kurum personeli başvuru detayında "Belediyeye Gönder"den ÖNCE üst yazısını düzenleyebilir (mevcut override akışı — korundu), gönderince kilitli.

### ✅ Yeni metodlar — `app/Services/DocumentTemplateService.php`
- `seedInstitutionCover(int $institutionId, ?string $masterHtml)` — global master kopyalar/blade fallback; `maskInstitutionName` ile kurum adını `{KURUM_ADI}` token'ına çevirip `saveInstitution('cover_letter')` yazar.
- `maskInstitutionName(string $html)` — DOMDocument+XPath: antet başlık (`td[text-align:center] span.font-bold`) + imza altı (`span[font-size:12.5px]`) kurum adı text node'larını token'a çevirir. Güvenli: yalnızca tek text node + tam kurum adı regex'i; gömülü adlar (yüklenici paragrafı) dokunulmaz.
- `hydrateInstitutionTokens(string $html, ?string $kurumAdi)` — PDF'te token'ı `mb_strtoupper(kurum adı)` ile değiştirir (boşsa token'ı siler).
- `renderFor()` → cover_letter tipinde hidrasyon eklendi (PDF akışı).
- `KURUM_ADI_TOKEN = '{KURUM_ADI}'` sabiti.

### ✅ Controller + Route
- `DocumentTemplateController`: `index()` artık alt kurum listesi (`is_municipality=false`) + her kurum için hasTemplate geçiyor. Yeni: `editInstitutionCover`, `updateInstitutionCover`, `destroyInstitutionCover` (üçü de `guardAccess` → merkez personel; `abort_unless(!is_municipality)`).
- `routes/admin.php`: `document-templates/institutions/{institution}/cover` GET/POST/DELETE (edit-institution-cover / update-institution-cover / destroy-institution-cover).
- `InstitutionController::store()`: yeni alt kurumda otomatik `seedInstitutionCover`.

### ✅ View
- `document-templates/index.blade.php`: "🏢 Kurum Üst Yazı Şablonları" bölümü (renk kodu daire + kurum adı + rozet + "Şablon Düzenle"). Master kart korundu.
- `document-templates/editor.blade.php`: `scope === 'institution_cover'` → "🏢 {kurum} — Üst Yazı Şablonu".

### ✅ DB
- `institution_document_templates` boştu → 7 mevcut alt kuruma otomatik seed yapıldı (her birinde token=2). Toplam 7 satır.
- Migration gerekmedi (tablo zaten var).

### ✅ Doğrulama
- `php -l` 4 dosya OK; `view:cache` OK; 3 route listelendi.
- Sentinel test: maskelenmiş HTML → token=2, hydrate → AKSA adı basıldı.
- Uçtan uca: Dicle başvurusu (id=972) `renderFor('cover_letter')` → kurum şablonu seçildi, "DICLE ELEKTRIK DAĞITIM A.Ş." basıldı, token kalıntısı yok, logo img var.

### 📁 Değişen Dosyalar
- `app/Services/DocumentTemplateService.php`, `app/Http/Controllers/Admin/DocumentTemplateController.php`, `app/Http/Controllers/Admin/InstitutionController.php`, `routes/admin.php`
- `resources/views/admin/document-templates/index.blade.php`, `editor.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki
- Tarayıcıda: super-admin → Taslak/Şablon → kurum listesi; kurum şablonu düzenle; yeni kurum ekle → otomatik şablon; alt kurum başvurusu PDF üst yazısında kendi kurum adı/logo.

---

## Önceki — Sprint 8b (7 Ağustos)

### 🎯 Öz
Önceki sprint'te (8a) cadde/sokak input yapısı yanlışlıkla tamamen kaldırılmıştı. Kullanıcı düzeltmesi: **cadde/sokak inputlarını geri getir** (üst yazı tablosu için gerekli), ama **yeşil div arama bağlantılarını** (cadde yazınca harita üstünde çıkan `renderStreetJumpBar`) ve **adres inputundaki "Konum Bul" butonunu** kaldır. Yeni adres bulma algoritması henüz kurulmayacak.

### ✅ Geri Getirilenler (create + edit)
- **HTML:** "Mahalle & Sokak Listesi" bloğu (`address-components-container`, `+ Mahalle & Sokak Ekle`, hidden `address_components`)
- **JS:** `esc()`, `initAddressComponents()` — sadece veri girişi (mahalle + cadde/sokak listesini `address_components` JSON'a serileştirir). Cadde yazınca haritaya otomatik gitme YOK.
- Submit: hidden `address_components` form'da otomatik gider

### ✅ Kaldırılanlar (kullanıcının istemedikleri)
- `#btn-find-location` "📍 Konum Bul" butonu + locSpin/locPulse animasyon CSS + `flyToAnimated` JS
- `renderStreetJumpBar` yeşil div arama bağlantıları (cadde yazınca harita üstünde çıkan) + `#street-jump-bar`
- `executeSmartGeocode`, `initAddressAutocomplete`, `btn-search-address` (eski geocode/arama zinciri kalıntıları)

### ✅ Doğrulama
- `php artisan view:cache` OK
- create/edit: cadde/sokak referansı 10/10; kaldırılacaklar 0/0
- Not: Animasyon kodu maps/index'te duruyor — WMS konum bulma sistemi kurulurken oradan taşınacak

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki (BÜYÜK GÖREV)
- **WMS tabanlı nokta atışı konum bulma sistemi** — mahalle/sokak/kapı numarasına kadar. Kullanıcı algoritmayı anlatacak.

---

## Sprint 8 — KONUM YAPISI TEMİZLİĞİ + ANİMASYONLU KONUM BUL

### 🎯 Öz
Başvuru oluştur/düzenle sayfalarında eski, hatalı konum bulma yapısı (mahalle/cadde/sokak DOM'u, yeşil sokak jump butonları, eski geocode zinciri, adres autocomplete) tamamen kaldırıldı. Yerine **animasyonlu "📍 Konum Bul"** butonu + pulse marker + animasyonlu flyTo taşındı (maps/index deseni). Bu, yeni WMS konum bulma sisteminin altyapısını hazırlar.

### ✅ Kaldırılanlar (create + edit, ~668 satır)
- **HTML:** `#btn-search-address` ("🔍 Haritada Bul"), Mahalle & Sokak Listesi bloğu (`#address_components` hidden, `#address-components-container`, `+ Mahalle & Sokak Ekle`), `#street-jump-bar`, `#address-autocomplete-list`, `#map-search-input`
- **JS fonksiyonları:** `flyToSuggestion`, `parseAddressForGeocode`, `executeSmartGeocode`, `renderStreetJumpBar`, `initAddressAutocomplete` (Nominatim), `prepareAddressComponents`, `initAddressComponents`, `esc`
- **Event bağları:** autocomplete, btn-search, map-search Enter, nested cadde/sokak debounce-geocode
- `show.blade.php` readonly görüntüleme + `maps/index` original animasyonuna **dokunulmadı**

### ✅ Eklenenler (animasyon forma taşındı)
- "📍 Konum Bul" butonu + `locSpin` spinner animasyonu (click'te döner)
- `flyToAnimated(lat,lon)` — iki haritaya animasyonlu `flyTo({animate:true,duration:1})` + `locPulse` pulse turuncu marker (`loc-marker`)
- S2S `/admin/api/geocode`'i çağırır (WMS sistemi aynı endpoint'i kullanacak)

### ✅ Doğrulama
- `php artisan view:cache` OK (tüm blade derlenir)
- create/edit'te eski referanslar 0; yeni animasyon referansı 8/8
- `git diff`: toplam -668 satır, +113 satır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki (BÜYÜK GÖREV)
- **WMS tabanlı nokta atışı konum bulma sistemi** — mahalle/sokak/kapı numarasına kadar. `maps/index`'teki ada/parsel sorgulama mantığının aynısı mahalle/cadde/sokak/kapı sorgulamasına uyarlanacak; `_harita` partial + `ApplicationsController::geocodeProxy`/proxy S2S altyapısı kullanılacak.

---

## Sprint 7 — MERKEZ BELEDİYE (VATANDAŞ) MODÜL SERBEST ERİŞİMİ

### 🎯 Öz
Alt kurum başvurularında çalışan modül PDF'leri (Tahakkuk/Metraj/Taahhütname/Ruhsat) + Düzenle butonları merkez belediye (vatandaş, `institution.is_municipality=true`) başvurularında görünmüyordu. Kök nedenler bulundu ve tek dosyada (show.blade.php) çözüldü — alt kurum akışına DOKUNULMADI.

### ✅ Kök Nedenler (Explore doğrulandı)
1. `$passedMetraj` (`show.blade.php:31`) `pre_approved` statüsünü dışlıyordu; merkez başvuruda metraj aşaması tam `pre_approved`'la geliyor (`ApplicationService::approveReceipt` is_municipality dalı → `PreApproved`) → **Metraj PDF + Düzenle gizli**.
2. Merkez Step 1 (Tahakkuk) kartında `pdf.tahakkuk` linki HİÇ yoktu — sadece tahsilat fişi/makbuz vardı.
3. PDF route'ları sunucuda çalışıyor (sadece `authorize('view')`) — sorun tamamen UI buton görünürlüğü.
4. Makbuz no inputları (`ztb_receipt_info`/`deposit_receipt_info`) merkezde de görünüyordu.
5. "Kuruma Gönder / Devret" butonu merkezde anlamsızdı.

### ✅ Değişiklikler (hepsi `show.blade.php`)
- **`$isMuniApp`** değişkeni erken tanımlandı: `(bool) ($application->institution?->is_municipality ?? false)`.
- **`$passedMetraj`** başvuru tipine duyarlı: muni'de `['draft','submitted','pending','rejected']` dışı hepsi geçer (pre_approved dahil); alt kurum eski listesini korur.
- **Step 1 (Tahakkuk):** muni'de `@if($isMuniApp || in_array(...))` ile Tahsilat Fişi & Makbuz bloğu her aşamada açık + **📄 Tahakkuk Fişi (PDF) Görüntüle/Yazdır** linki + **✏️ Tahakkuk Belgesini Düzenle (Kaydet)** (belediye personeli) eklendi. "Tahakkuk Bilgileri" formu (makbuz no) `$isInstitutionApplication()` şartına bağlandı → merkezde GİZLİ, alt kurumda kalır.
- **Step 2 (Metraj):** `@if($isMuniApp ? true : $passedMetraj)` — muni'de her aşamada PDF + Düzenle açık.
- **Step 3 (Taahhütname):** `@if($isMuniApp ? true : ($isCurrent || $isPast))` — muni'de her zaman açık.
- **Step 4 (Ruhsat):** aynı gevşetme + makbuz no formu `@if(!$isMuniApp && ...)` — merkezde GİZLİ.
- **Üst Yazı (Dilekçe)** Belge Arşivi'nde muni'de de görünür (`$isMuniApp ||`).
- **"Kuruma Gönder / Devret"** butonu `$application->isInstitutionApplication()` şartıyla merkezde GİZLİ.

### ✅ Doğrulama
- `php artisan view:cache` OK (tüm blade derlenir)
- `workflowStep('pre_approved', true)` → step 2 (Kazı Metraj) — merkez akışı doğrulandı
- Alt kurum akışına hiç dokunulmadı (`isInstitutionApplication()` şartları korundu)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/show.blade.php` (tek dosya, +40/-12)
- `SESSION_SUMMARY.md`

### Sıradaki
- Tarayıcıda merkez belediye başvurusu: Step 1→4 modül PDF'leri + Düzenle butonları, makbuz formları gizli, Kuruma Gönder butonu yok
- Alt kurum başvurusu: akış değişmediğini doğrula

---

## Sprint 6 — KAZI METRAJ TAHMİNİ (PRO) — İstatistiksel Tahmin Motoru

### 🎯 Öz
BETA→PRO: Başvuru formuna gömülü, **sıfır maliyet / offline / LLM'siz** istatistiksel metraj tahmin motoru. Kurum+mahalle adaptif hiyerarşi; veri azsa global/varsayılan düşüş.

### ✅ 1. `app/Services/ProjectForecastService.php` (YENİ)
- `predict(institutionId, mahalle, totalM2, excludeAppId)` → tam paket: toplam m² + zemin bazlı yüzde/m²/fiyat satırları + `forecast_total` + güven etiketi
- Adaptif seviyeler: **L1 kurum+mahalle** (≥3 örnek) → **L2 kurum** (≥5) → **L3 global** (≥5) → **L4 varsayılan dağılım**
- `defaultDistribution()` — SurfaceTypeSeeder gerçek adlarıyla eşleşen akıllı varsayılan (%55 asfalt, %18 parke, %12 beton, %6 stabilize, %6 toprak, %3 çim; DB'de yoksa ilk aktif tipe bakiye)
- Oracle uyumlu: ham JSON SQL fonksiyonu YOK — mahalle filtresi PHP tarafında `address_components` array'inde (yavaş ama güvenli)
- Fiyat öngörüsü AykomeMath çekirdeğiyle uyumlu (amount = m² × birim fiyat; recalculateTotals ile bağlanır)

### ✅ 2. Controller + Route
- `ApplicationsController::metrajTahmin(Request)` — validasyon (`institution_id/mahalle/total_area_m2/exclude_application_id`) + `ProjectForecastService::predict` + `AuditLogger::log('metraj.forecast', ...)` + JSON
- `POST admin/applications/metraj-tahmin` → `admin.applications.metraj-tahmin` (check-applicant yanına, resource'dan önce)

### ✅ 3. View — `partials/_metraj_tahmin.blade.php` (YENİ)
- create + edit'e `@include` (zemin kartı ile Kurum & İmza Yetkili arasına)
- "🎯 Metraj Tahmini Al" butonu → AJAX POST → sonuç kartı: güven + seviye etiketi + zemin tablosu (pay/m²/birim fiyat/öngörü) + toplam öngörü
- "♻️ Zemin Satırlarına Uygula" → global `addSurfaceLine()` satır ekler + `recalculateAll()` (mevcut çizim/satır API'sine bağlanır, yeni mimari yok)

### ✅ Doğrulama
- `php -l` service + controller + routes OK; `php artisan route:list --name=metraj-tahmin` → 1 route
- `php artisan view:cache` OK (tüm blade derlenir); partial JS `node --check` OK
- Saf istatistik mantığı birim testi: %55/%45 dengeli, tek tip %100, boş → [] — hepsi geçti
- Not: host CLI OCI8'siz olduğundan DB'li tinker çalışmadı (bilinen çevresel kısıt) — servis Eloquent sorgusu sadece; Docker Oracle'da `./oracle.sh tinker` ile `app(ProjectForecastService::class)->predict(1,'EYYÜBİYE',180)` test edilebilir

### 📁 Değişen Dosyalar
- `app/Services/ProjectForecastService.php` (yeni), `resources/views/admin/applications/partials/_metraj_tahmin.blade.php` (yeni)
- `app/Http/Controllers/Admin/ApplicationsController.php` (+`metrajTahmin`), `routes/admin.php` (+1 route)
- `resources/views/admin/applications/create.blade.php` + `edit.blade.php` (partial include)
- `SESSION_SUMMARY.md` (bu özet)

### Sıradaki
- Docker'da DB'li predict testi; tarayıcıda create/edit "Tahmini Al" → uygula akışı
- Varsayılan dağılım gerçek veriyle doldukça otomatik isabet kazanır

---

## Önceki — 2 Ağustos 2026

## Sprint 5 — EBYS 5 MADDELİK İŞ PAKETİ (Backend + Migration + View)

### ✅ 1. "Başkan Yrd. adı zorunludur" onay kilidi KALDIRILDI
- `ApplicationsController::approvePreExcavation()`: `if ($stage==='vice_mayor' && $viceMayorName==='')` → `withErrors` bloğu silindi
- Yeni akış: ad boşsa `SignatoryEngine::resolve('pre_permit', institution_id, 'belediye_baskan_yardimcisi')?->ad_soyad` (global makam ayarı) çekilir; ayar yoksa boş string → **onay asla bloke olmaz**
- `show.blade.php`: `vice_mayor_name` input'undan `required` kaldırıldı; value global ayardan prefill edilir (`$application->vice_mayor_name ?: SignatoryEngine::resolve(...)?->ad_soyad ?? ''`)
- `ProcessEngine::approve()` zaten `!empty($name)` guard'lı — boş string ile de son adım (Ön Kazı İzni) verilir

### ✅ 2. Görevi Devret listesi → tüm Eyyübiye merkez kullanıcıları
- `ApplicationsController::show()` `$fieldUsers` sorgusu: `->role('field-team')` yerine **merkez rol seti**:
  `super-admin, municipality-admin, municipality-staff, municipality-buro, municipality-sef, municipality-mudur, municipality-makam, field-team`
- Alt kurum hariç tutma: `whereNull('institution_id') OR orWhereHas('institution', is_municipality=true)` — AKSA/TEDAŞ/ŞUSKİ/TT kullanıcıları listeye girmez

### ✅ 3. Belge Arşivi'nde Ruhsat butonları gizleme (admin hariç)
- `show.blade.php` arşivde Ruhsat / Canlı Ruhsat / Eski Ruhsat kartları `@if($ruhsatVisible)` ile sarıldı
- `$ruhsatVisible = hasAnyRole(['super-admin','municipality-admin']) || in_array(status_value, ['licensed','completed'])`
- Non-admin kullanıcılar ruhsat çıktılarını yalnızca ruhsatlanmış/tamamlanmış başvuruda görür

### ✅ 4. Process/Workflow seçimi + process_id + süreç tabanlı onay rotası
- **Migration:** `2026_08_02_000001_add_process_id_to_applications_table` → `process_id` FK → `process_definitions`, `nullOnDelete`
- `Application` model: `process_id` fillable + `process()` belongsTo + `documentOverrides()` hasMany
- `create.blade.php`: süreç seçici (indigo kutu, `name="process_id"`, "— Varsayılan Süreç —" opsiyonu); `ApplicationsController::create()` → `$processes` geçiyor
- `StoreApplicationRequest`: `process_id => nullable|integer|exists:process_definitions,id`
- `ApplicationService::createDraft` → `process_id` persist; `submit()` → `steps(null, $application)` (başvurunun süreci)
- `ProcessEngine`: yeni `processFor(Application)` (process_id varsa onu, yoksa aktif süreç); `steps()` ikinci parametre olarak Application alıyor; `currentStep/nextStep/approve` başvurunun sürecini kullanır → **seçilen süreç adımlarına göre ilerler**

### ✅ 5. EBYS Taslak Motoru — Global + Başvuru Bazlı Şablon Yönetimi (REBUILD)
> 🔄 **Revizyon:** Önceki textarea/iframe WYSIWYG (`custom_document_templates`, `CustomDocumentTemplate`, `customTemplate*`, "Taslağı Aç & Word Gibi Düzenle" butonu) **tamamen silindi** — yönetici onayıyla üretim EBYS mimarisine geçildi.
> **Migration:** `2026_08_02_000010_create_global_document_templates_table` (document_type unique, content_data LONGTEXT, editor_type) + `2026_08_02_000011_create_application_document_overrides_table` (application_id FK cascade, document_type, unique[application_id,document_type])
> **Model:** `GlobalDocumentTemplate`, `ApplicationDocumentOverride` (+ `Application::documentOverrides()` hasMany)
> **Service:** `DocumentTemplateService` — TYPES kaydı (cover_letter/on_kazi = word, ruhsat/tahakkuk = excel), kaynak hiyerarşisi **override → global → blade**, blade compile + `.a4-container` DOM çıkarımı, ruhsat/tahakkuk hücre matrisi (Jexcel JSON), standalone PDF sarmalayıcı (`renderFor()`)
> **Controller/Route:** `DocumentTemplateController` — `admin/document-templates` (index/edit/update) + `admin/applications/{application}/edit-document/{documentType}` (edit/save/destroy override)
> **PDF çizim:** `downloadPrePermit`('on_kazi'), `downloadCoverLetter`, `downloadRuhsat`, `downloadTahakkuk` → önce `renderFor()` (override/global HTML dönerse `response()`), yoksa normal blade akışı (sıfır hata hedefi)
> **UI:** Sidebar "📝 Taslak / Şablon Yönetimi" (4 kutu: Üst Yazı / Ön Kazı = Word, Ruhsat / Tahakkuk = Excel) + tam ekran editör: **CKEditor 5** (A4 kağıt + Word şeridi, CDN cdn.ckeditor.com — anahtar gerektirmez) / **Jspreadsheet (jExcel)** (hücre hücre tıklanabilir, satır/sütun ekle-sil, jsDelivr CDN). Arşivde her belgeye "✏️ Taslak" (başvuruya özel override) + editörde "↺ Varsayılana Dön" (override silme)

### Doğrulama
- `php -l` OK (service + controller + models + routes + ApplicationsController); `php artisan view:cache` OK (tüm blade derleniyor)
- `php artisan route:list --name=document-templates` → 3 route (index/edit/update); `--name=edit-document` → 3 route (edit/save/destroy)
- Service'te DB'siz yol test edildi: word blade → fragment/css çıkarımı, excel grid → standalone HTML
- Migration'lar server'da çalıştırılmalı: `php artisan migrate` (OCI8 eklentisi olmayan host CLI'de çalışmaz, Docker Oracle container'ında koş)
- **Deploy notu:** Editörler CDN bağımlı (CKEditor 5 `cdn.ckeditor.com`, Jspreadsheet/jsuites + jQuery `jsDelivr`/`code.jquery.com`) — sunucuda dış ağ/CDN erişimi gerekir

### 📁 Değişen Dosyalar
- `app/Http/Controllers/Admin/ApplicationsController.php`, `app/Http/Requests/StoreApplicationRequest.php`, `app/Services/ApplicationService.php`, `app/Services/ProcessEngine.php`, `app/Models/Application.php`
- `app/Http/Controllers/Admin/DocumentTemplateController.php` (yeni), `app/Services/DocumentTemplateService.php` (yeni)
- `app/Models/GlobalDocumentTemplate.php`, `app/Models/ApplicationDocumentOverride.php` (yeni)
- `routes/admin.php`
- `database/migrations/2026_08_02_000001_add_process_id_to_applications_table.php` (yeni), `2026_08_02_000010_create_global_document_templates_table.php` (yeni), `2026_08_02_000011_create_application_document_overrides_table.php` (yeni)
- `resources/views/admin/applications/show.blade.php`, `create.blade.php`, `partials/sidebar.blade.php`
- `resources/views/admin/document-templates/index.blade.php` + `editor.blade.php` (yeni)

---

## Sprint 4 — BACKEND GEOCODING PROXY (S2S) — Tarayıcı CORS/403 Bypass

### 🎯 Mimarî Karar
- Harici sitelere (Yandex/Nominatim) **Javascript'ten sorgu ATILMIYOR** → tüm geocoding artık Laravel backend'in içinden `Illuminate\Support\Facades\Http` ile gidiyor (server-to-server)

### ✅ 1. Route (`routes/admin.php`)
- `Route::get('api/geocode', [ApplicationsController::class, 'geocodeProxy'])->name('api.geocode');` — `license:applications` grubu içinde (create/edit sayfalarıyla aynı yetki)
- URL: `GET /admin/api/geocode?q=...` · Name: `admin.api.geocode`

### ✅ 2. `ApplicationsController::geocodeProxy()` (app/Http/Controllers/Admin/ApplicationsController.php)
- `use Illuminate\Support\Facades\Http;` eklendi
- Aşama 1 — **S2S Yandex**: `Http::get("https://geocode-maps.yandex.ru/1.x/?apikey=b7500431-...&geocode=...&results=1")` → `response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos` → `explode(' ')` → `lat=coords[1]`, `lon=coords[0]`
- Aşama 2 — **Nominatim fallback**: `Http::withHeaders(['User-Agent' => 'Aykome-Eyyubiye-GIS-Backend/1.0'])` + `str_ireplace([' sokak',' sok.',' cadde'],'',$query)` (OSM ban yememek için)
- **Autocomplete LIST modu**: `?list=1&limit=6` → Nominatim çoklu sonuç `{success:true, list:[...]}` (adres öneri dropdown'ı da proxy'den)
- Her iki aşama try/catch; başarısızsa `{success:false}`

### ✅ 3. Frontend Temizliği (create + edit.blade.php)
- `executeSmartGeocode` komple sadeleştirildi: yalnızca `fetch('/admin/api/geocode?q=' + encodeURIComponent(fetchStr))` → `data.success` ise `setView([lat,lon], 19)` (appDrawMap + appCbsMap); değilse alert **"Merkezi sunucu veritabanımız / api havuzumuz adresi isabetli bulamadı."**
- `initAddressAutocomplete` fetch'i de proxy'ye bağlandı: `fetch('/admin/api/geocode?list=1&limit=6&q=...')`
- create/edit içinde **harici URL / eski Yandex key KALMADI** (`https://geocode-maps.yandex`, `https://nominatim.openstreetmap`, `3818fa95` → 0 eşleşme)

### ✅ Doğrulama
- `php -l` controller + routes OK; `php artisan route:list` → `admin/api/geocode admin.api.geocode` ✓
- Tinker S2S testi: Yandex HTTP **200** (403 yok!) — `3213 Sokak, Şıh Maksut Mahallesi, Eyyübiye` → `lat=37.14024 lon=38.787637` ✓
- Nominatim list modu: HTTP 200 + `display_name/lat/lon` önerileri ✓
- `node --check` her iki JS OK; `php artisan view:cache` OK
- Not: CLI PHP'de OCI8 eklentisi yok (DB default=oracle) → tinker'da `response()->json()` Oracle connection dener; bu çevresel, `geocodeProxy` DB'ye hiç dokunmuyor

### 📁 Değişen Dosyalar
- `routes/admin.php`, `app/Http/Controllers/Admin/ApplicationsController.php`
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`

---



## Sprint 3 — UI ROLLBACK + JITTER FIX + KEY MÜHÜRLEME (Frontend Only)

### ❌→✅ METRAJ BİLGİ KARTLARI UCUBESİ SİLİNDİ (UI Rollback)
- `#drawing-calculations` div'i (`calc_length`/`calc_width`/`calc_area`) her iki dosyadan KOMPLE silindi → sayfa orijinal stabil düzene döndü (GeoJSON + Alan m²)
- Genişlik ihtiyacı için orijinal `total_area_m2` (Alan m²) inputunun altına aynı Tailwind sınıfında ufacık `<input id="poly_width" type="number" step="0.01" min="0.01" value="1">` eklendi; `@if($isInstitutionUser) disabled readonly @endif` ile kurum kilidi korundu
- `syncCalcDisplay`/`calcWidthEl` JS'i tamamen kaldırıldı → yerine temiz `syncArea(areaM2, lineLenM)` + `poly_width` input listener'ı: `poly_width` değerini oku → length × width çarp → `input#total_area_m2`'ye PUSH (dispatchEvent('input') + recalculateAll) — ekranda custom id/kargaşa yok

### ✅ JITTER FIX — flyTo KÖKÜNDEN setView'e ÇEVRİLDİ
- `create.blade.php` + `edit.blade.php` içindeki TÜM `.flyTo(...)` çağrıları silindi → `.setView([lat, lon], zoom)` (anlık ışınlanma, titreme yok):
  - `flyToSuggestion()` → `targetMainMap.setView` + `targetCbsMap.setView`
  - `executeSmartGeocode` → `mD.setView(...)` + `mC.setView(...)`
- Diğer modüller (`maps/index`, `admin/map`, `live-map-pro`) kendi `map.flyTo`/`mapsMap.flyTo`'larını kullanıyor — dokunulmadı (ayrı modül, jitter kaynağı değil)

### ✅ YANDEX KEY MÜHÜRÜ (%100 tek key)
- `3818fa95...` eski key kod tabanında SIFIR kaldı (yalnızca dokümanlarda geçmiş kayıt)
- `executeSmartGeocode` içinde hardcoded `b7500431-b7c9-4c6b-bcb3-fcd91b3a7339` doğrulandı (create:1591, edit:1787)
- `php artisan view:clear` + `view:cache` çalıştırıldı — derlenmiş view cache'teki eski key sızıntısı temizlendi

### Doğrulama
- `node --check` create/edit extracted JS OK; `php artisan view:cache` OK
- create/edit içinde `.flyTo(` kalıntısı yok (yalnızca fonksiyon adı `flyToSuggestion` + içi setView)

---

## Sprint 2 — Yandex Nihai Geocoder Key + Echo/WS Sessizleme (Frontend Only)

### ✅ Yandex Geocoder Nihai Key Enjektesi (`executeSmartGeocode` yeniden yazıldı)
- `create.blade.php` + `edit.blade.php` içindeki `executeSmartGeocode` kullanıcının verdiği yeni zeki düzenle değiştirildi; eski hibrit çöpe atıldı
- **NİHAİ KEY:** `b7500431-b7c9-4c6b-bcb3-fcd91b3a7339` (fonksiyon içinde `const yandexApiKey`, artık global const yok — `3818fa95...` kalıntısı her iki dosyadan silindi)
- Regex zekası: `(\d+)\.(?![\s\w])` → rakam+noktayı temizler (`123.`→`123`), `SK\.|SK|SOK\.|SOK` → `" Sokak"`, `\d+` içeriyorsa "sokak/cadde" yoksa `" Sokak"` ekler
- 3 aşamalı fallback: ① Yandex (`yndxData && !yndxData.statusCode` ile 403 bypass) → ② OSM (`osmQuery` = Sokak kelimesi silinmiş rakam) → ③ mahalleye çakıl (zoom 17, `console.warn("Cadde iska, mahalleye düştü!")`)
- `mC = window.appCbsMap` da setView alıyor (Sprint 3 itibarıyla); final alert: **"Açık kaynak sistemleri cadde/mahalle lokasyonunu eşleyemedi..."**
- Key Yandex döngüsünde lat/lon tersi düzeltmesi korundu (`{lat: +spt[1], lon: +spt[0]}`)

### ✅ Pusher/Reverb Connection Error Sessizleme (`resources/js/echo.js`)
- `Pusher.logToConsole = false` (kırmızı WS spam'i kapalı), Echo `debug: false`
- `VITE_BROADCAST_CONNECTION` yoksa/`'false'` ise ya da `VITE_REVERB_APP_KEY` boşsa → `window.Echo = null` (hiç başlatma)
- `new Echo(...)` try/catch içine alındı — WS sunucusu offline/crash olursa catch atıp `window.Echo = null`
- Error mute: `window.Echo.connector.pusher.connection.bind('error', () => console.warn('WSC kapalı - Mute'))` + `state_change` sessiz
- `admin-notifications` channel dinleyicileri `if (window.Echo) { ... }` bloğuna sarıldı (null güvenli)

### Doğrulama
- `node --check` create/edit extracted JS OK; `php artisan view:cache` OK
- `npm run build` OK (echo-C22ew5T4.js 76.08 kB)

---


## Bu Oturum — Yandex Mahalle Fallback + Metraj Bilgi Kartları DOM (Frontend Only)

### 🎯 Neden
- Jump butonları Yandex'e **mahallesiz** sokak metni yolluyordu → "Şıh Maksut Mahallesindeki 3213. Sokağı" fail veriyordu
- "Genişlik (m)" input'u haritada yoktu, sadece Alt Zemin tablosundaydı → memur çarpımı göremiyordu

### ✅ Değişiklik 1 — Sokak Butonlarına MAHALLE Yedirme + Fallback
- `renderStreetJumpBar()`: her yeşil butona ana parent wrapper'ın mahalle adı gömülüyor:
  `<button class="..." data-mahalle="Şıh Maksut Mahallesi">3213. Sokak</button>` (`btn.setAttribute('data-mahalle', mahalle)`)
- Click handler artık sokak + mahalle birlikte gönderiyor (birebir kullanıcının istediği query):
  `qs = (street + " " + mahalleVal).trim() + " Eyyübiye, Şanlıurfa"`
- **Fail ise Fallback:** `fb_qs = mahalleVal + ", Eyyübiye, Şanlıurfa"` ile salt mahalle geocode → flyTo(17) + alert **"Tam sokak bulunamadı, mahallenin tam orta lokasyonuna odaklanılmıştır"**
- `searchYandexAPI()` güçlendirildi: tam sanitize zinciri (`.SK`/`.SOK`/`123SK`→ Sokak, `123.`→`123. `, whitespace collapse) + **Eyyübiye string'i query'de yoksa otomatik `"Şanlıurfa, Eyyübiye, "` prefix** (Adres Bul için de korur)

### ✅ Değişiklik 2 — METRAJ BİLGİ KARTLARI (GeoJSON textarea'nın TAM YANINA)
- `#drawing-calculations` kutusu harita altından kaldırılıp GeoJSON grid'inin SAĞ kolonuna taşındı (create:341, edit:342) → Draw Box bittiği yerde görünür
- Kartlar (dikey `space-y-3`):
  1. **Uzunluk (m)** → `#calc_length` readonly (JS polyline metrajı)
  2. **Genişlik (m)** → `#calc_width` default `1.0`, **kurum rolünde `disabled readonly`** (`@if($isInstitutionUser)`) — belediye/memurda düzenlenebilir yetki
  3. **Toplam Alan (m²)** → `#calc_area` `name="alan_m2"` readonly kalın kırmızı — Uzunluk × Genişlik reaktivitesi
- `draw:created` → `syncCalcDisplay()`: polyline LatLng distance toplamı → `calc_length`; `#calc_width` float değerini çek → **ÇARP → `.toFixed(2)` → `calc_area` (id + `name="alan_m2"`) anında push**; `#calc_width` input listener'ı aynı çarpımı yeniden yazar + `recalculateAll()`
- readonly mantığı artık `$application->institution->name` yerine `$isInstitutionUser` (create'te de doğru çalışır; "merkez Bld değilse kilit")

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`
- Backend/API/DB'ye dokunulmadı (show.blade.php'de çizim haritası/metraj DOM'u yok, kapsam dışı)

### Doğrulama
- `node --check` her iki JS OK; `php artisan view:cache` OK; duplike ID yok
- `$isInstitutionUser` Blade değişkeni her iki controller'da view'a geçiyor (create:142, edit:375)

### Sıradaki
- Manuel test: 3213. Sokak butonu → Şıh Maksut Mahallesi ile Yandex isabeti; kasıtlı hatalı sokak → mahalle merkezine flyTo + fallback alert
- Belediye: genişliği değiştir → Toplam Alan anında yeniden çarpılır; kurum: genişlik kilitli 1.0

---

## Önceki — Nominatim → Yandex Maps Geocoder MİGRASYONU (Frontend Only)

### 🎯 Neden
- OpenStreetMap (Nominatim) açık kaynak zafiyeti: Eyyübiye Batıkent arandığında Karaköprü Batıkent'e atlayabiliyor, `.SK` sokak noktaları çözülemiyor
- Sektör standardı **Yandex Maps Geocoder API**'ye kökten geçildi — API key: `3818fa95-9ec2-4532-97b3-bd4dbadbff78`

### ✅ Değişiklik 1 — `searchYandexAPI(addressText)` (create:1620, edit:1816)
- Eski `searchOSM` helper'ı tamamen kaldırıldı (her iki dosyada da 0 referans kaldı)
- Yeni fonksiyon (kullanıcının verdiği algoritma aynen):
  - `.SK` → ` Sokak`, `123SK` → `123 Sokak` temizliği
  - **Eyyübiye demir atma:** `searchString = "Şanlıurfa, Eyyübiye, " + cleanAddress` (JS içinde zorla enjekte — ikiz adres/karışıklık imkansız)
  - `https://geocode-maps.yandex.ru/1.x/?apikey=...&format=json&geocode=...&results=1`
  - Yandex `Point.pos` **"Longitude Latitude"** boşluklu string → `split(' ')` → `lon=coords[0]`, `lat=coords[1]` → Leaflet `[lat, lon]` döner
  - `data.response?.GeoObjectCollection?.featureMember?.[0]?.GeoObject` optional-chaining guard; bulunamazsa `null`

### ✅ Değişiklik 2 — Yeşil Sokak Jump Butonları → Yandex
- `renderStreetJumpBar` buton click: `await searchYandexAPI(street)` → bulunursa `flyTo([lat, lon], 18, {animate:true, duration:1.5})`, bulunamazsa alert
- Sadece sokak metni geçilir; Eyyübiye prefix'i fonksiyon içinde zorlanır

### ✅ Değişiklik 3 — "🔍 Haritada Bul" Butonu → Yandex
- `btn-search-address` click: eski 3-aşamalı Nominatim fallback zinciri (searchOSM) silindi
- Yerine: `await searchYandexAPI(rawQuery)` → her iki haritaya (appDrawMap + appCbsMap) `flyTo([lat, lon], 18, {animate:true, duration:1.5})`
- `#map-status`'a `📍 {adres} bulundu.` yazılır; bulunamazsa alert

### 🧩 Korunan (Kapsam dışı)
- `geocodeStreetSmart` + `geocodeStreetFlyTo` (Nestead Cadde/Sokak keyup debounce) Nominatim ile kaldı — sadece Jump Butonu ve Adres Bul event'leri Yandex'e bağlandı (kullanıcının istediği kapsam)
- Controller/Route/Backend/API key dosyalarına dokunulmadı

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `node --check /tmp/create_extracted.js` + `node --check /tmp/edit_extracted.js` OK
- `rg searchOSM` → 0 sonuç; `php artisan view:cache` OK

### Sıradaki
- Manuel test: `3087.SK` / `3213SK` sokak butonu → Yandex ile Eyyübiye'de nokta atışı; "Eyyübiye Batıkent" yazıp 🔍 → Karaköprü'ye DEĞİL Eyyübiye'ye gitmeli

---

## Önceki — Harita Altı Hesaplama Paneli + Yandex Geospacing v2 (Frontend Only)

### ✅ Değişiklik 1 — `#drawing-calculations` (3 Görünür Input)
- Harita altı aksiyon satırından hemen sonra eklendi (create:332, edit:333)
- Kalıp: `<div id="drawing-calculations" class="mt-4 p-4 border rounded bg-slate-50 flex gap-4">`
  - `#calc_length` → `readonly`, `type=number`, m
  - `#calc_width` → `value="1.0"`, kurumda `readonly` (`@if(isset($application) && $application->institution && !str_contains(strtolower($application->institution->name), 'merkez'))` create'te; edit'te `isset`'siz)
  - `#calc_area` → `readonly`, `style="font-weight:bold; color:red;"`, m²
- Edit'te `$application->institution` `loadMissing` ile yüklü; kurumsuz başvuruda guard null-safe

### ✅ Değişiklik 2 — `draw:created` Hesaplama Senkronu
- `L.GeometryUtil.geodesicArea()` için `leaflet-geometryutil@0.10.3` CDN script'i eklendi (jsDelivr path doğrulandı: `src/leaflet.geometryutil.js`)
- `geodesicArea(layer)`: plugin yüklüyse `geodesicArea`, yoksa `polyArea`/`rectArea` fallback (try/catch)
- `syncCalcDisplay(area, lineLen, layer)`:
  - polyline → `calc_length.value = lineLen` + `calc_area = lineLen × calc_width.value` (boşsa 1)
  - polygon/rectangle → `calc_length.value = "Çokgen"` + `calc_area.value = area`
  - Zorla `calc_area`'ya yazılır + `dispatchEvent(new Event('input'))` + `setTimeout(() => recalculateAll(), 200)`
- `#calc_width` input listener: `calc_area = calc_length × calc_width` + `recalculateAll()`

### ✅ Değişiklik 3 — Yandex Benzeri Agresif Geospacing v2
- `sanitizeStreetQuery()` regex zinciri (aynen):
  ```
  q.replace(/[\.]\s*SK/gi,' SOKAK')
   .replace(/[\.]\s*SOK/gi,' SOKAK')
   .replace(/(\d+)SK/gi,'$1 SOKAK')
   .replace(/(\d+)\.(?![\s\w])/g,'$1. ')
   .replace(/\s+/g,' ').trim()
  q.replace(/MAHALLESİ|MAH\.|MAH/gi,'Mahallesi')
  ```
- `'3213. SK'` → `'3213 SOKAK'`, `'3213SK'` → `'3213 SOKAK'`, `'3213.'` → `'3213. '`
- `geocodeStreetSmart()`: `fetchStr = q + ", Şanlıurfa, Türkiye"` (Eyyübiye kısıtlaması KALKTI), `countrycodes=tr&limit=1`; fallback zinciri korunur (mahalleli → mahallesiz)
- Sokak butonları `geocodeStreetSmart(...,18)` → yeni mantıkla çalışır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `node --check /tmp/create_extracted.js` + `node --check /tmp/edit_extracted.js` OK (jscheck.js yeniden koşuldu)
- `php artisan view:cache` OK (tüm blade derleniyor)
- Backend'e/route'a/API key'e dokunulmadı

### Sıradaki
- Manuel test: `3087.SOKAK`/`3213SK` butonu → flyTo(18); polyline çiz → Uzunluk/Genişlik/Alan kutuları anında dolu; polygon çiz → Uzunluk="Çokgen" + kırmızı Alan m²

---

## Önceki — UI Rendering Cerrahi Operasyon (3 Değişiklik — Frontend Only)

### ✅ Değişiklik 1 — Yeşil Sokak Butonları
- `renderStreetJumpBar` butonları artık: `bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-3 rounded shadow-lg border border-emerald-800 text-[11px] uppercase tracking-wide cursor-pointer` (yemyeşil, harita tavanından kopuyor)

### ✅ Değişiklik 2 — Yandex Benzeri Akıllı Geospacing
- `sanitizeStreetQuery()`: `'3087.SOKAK'` → `'3087. SOKAK'` (`(\d+)\.` → `$1. `), `sokak`→`Sokak`, `cadde.*`→`Cadde`, boşluk temizliği
- `geocodeStreetSmart()`: önce `"[Sokak], [Mahalle] Eyyübiye Şanlıurfa"` ara → bulamazsa **mahalleyi çıkarıp** `"[Sokak] Eyyübiye"` ile tekrar ara (fallback zinciri)
- Sokak butonları (`geocodeStreetSmart(...,18)`) ve debounce'lu cadde araması (`geocodeStreetFlyTo`) bu mantığı kullanıyor

### ✅ Değişiklik 3 — Polyline Matematiği HTML'e ÇAKILIYOR (EN KRİTİK)
- Polyline branch'inde genişlik: kurum `1` / belediye input değeri / boşsa **varsayılan 1** (artık 0 kalmaz)
- Çizim sonrası **Defansif DOM yazımı** (renderTable + serializeAndSync sonrası):
  - `.row-width` → kurumda `value=1.00` + `readOnly=true`, belediyede `readOnly=false` + değer
  - `.row-length` → `.value = length_m`
  - `.row-quantity` → `.value = quantity` (Genişlik x Uzunluk)
  - `#total_area_m2` (Alan m²) → `.value = quantity` ZORLA
  - `.dispatchEvent(new Event('input'))` → reaktif tetik
- `recalculateAll()` artık "Hesaplanan tutar" panelini (`#surface-total-display`) de güncelliyor (M²*Fiyat anında)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK; JS syntax check (node --check) her iki dosya için OK
- Backend'e/route'a/API key'e dokunulmadı

### Sıradaki
- Manuel test: 3087.SOKAK butonu → flyTo; polyline çiz → width/length/miktar/alan kutuları anında dolu

---

## Önceki — Harita Arayüz Devrimi (3 Efsane Adım — UI/JS)

### ✅ Adım 1 — Dinamik Sokak Uçuş Butonları (Quick-Jump)
- `#street-jump-bar` (absolute top-2 left-10 right-2 z-[1000] flex gap-2) haritanın TAM tepesine eklendi
- `renderStreetJumpBar()`: Adres JSON bloğundaki her sokak → şeffaf/kaydırılabilir chip buton (keyup'da + cadde ekle/sil'de güncellenir)
- Tıklayınca gizli Nominatim sorgusu `"[Sokak], [Mahalle] Eyyübiye, Şanlıurfa, Türkiye"` → `flyToSuggestion(.., 18)`

### ✅ Adım 2 — Polyline Renk Seçici
- `#draw_color_picker` (input type=color) Leaflet `L.Control` olarak sağ üste eklendi
- `buildDrawControl()` + `refreshDrawControl()` refactor: renk değişince draw toolbar yeniden kurulur → yeni çizimler seçilen renkle çizilir
- Mevcut overlay'ler `repaintOverlays()` ile boyanır; kurum değişince picker kurum rengiyle senkronize edilir

### ✅ Adım 3 — Uzunluk x Genişlik = Miktar (m²) Tam Senkron
- Polyline çizilince `row.length_m = lineLen` (gerçek metre), kurumda `width=1` / belediyede input değeri
- `row.quantity = length × width` → `renderTable()` + `recalculateAll()` (fatura anında M²*Fiyat)
- **`serializeAndSync` poliline branch'i**: `layer._rowId` ile satır eşleşip `totalArea += row.quantity` → **Alan (m²) inputu `total_area_m2` artık polyline metrajını yansıtıyor** (düzenleme/silme dahil)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK; JS syntax check (node --check) her iki dosya için OK
- Backend'e dokunulmadı

### Sıradaki
- Tarayıcıda kurum + belediye kullanıcısı ile manuel test: sokak butonları, renk seçici, polyline → length/width/area senkronu

---

## Önceki (2 Ağustos sabah) — Başvuru Formu UX (5 Maddelik İş Paketi)

### ✅ Tamamlanan
1. **Adres zorunluluğu kaldırıldı** — Doğrulandı: `StoreApplicationRequest`'te `address_text` zaten `nullable`, migration kolonu zaten `nullable`, HTML'de `required` yoktu. Ek değişiklik gerekmedi.
2. **Polyline uzunluğu → Length_m enjeksiyonu** (create + edit `draw:created`)
   - `lineLen = polyLen(layer.getLatLngs())` hesaplanır, aktif satırın `length_m`'ine yazılır
   - `rowDrawings[rowId]` artık `LineString` GeoJSON'u da saklıyor (`shape:'polyline'`)
   - Polyline için `quantity = length × width` reaktif hesap
3. **Rol bazlı genişlik kilidi (kurum = 1m readonly)**
   - `renderTable()`: `isInstitutionUser` ise width input `readonly` + `value="1.00"`
   - `addSurfaceLine()`: kurum için `width_m = 1` varsayılan
   - `row-quantity` handler: kurumda width hep 1 kalır, `length = qty/1`
   - Polygon çizimi: kurumda `width=1, length=area`; belediyede `sqrt(area)` (eski davranış)
   - `prepareSurfaceLinesForSubmit()`: kurumda width submit'te `1` olarak zorlanır
   - `initInstitutionWatcher` → kurum değişince `renderTable()` (readonly senkron)
4. **Google-tarzı autocomplete arama** (`initAddressAutocomplete`)
   - `#address_text` 500ms debounce + Nominatim (`countrycodes=tr&limit=6`)
   - `< 4` karakterde istek atılmaz; dropdown `#address-autocomplete-list`; seçimde `flyToSuggestion(…, 18)`
   - Escape / dış tıklama ile kapanır; stale istek `_seq` guard ile engellenir
5. **Mahalle/Cadde bloğunda haritaya gösterme**
   - `.comp-street` input'larına 700ms debounce → `geocodeStreetFlyTo()` Nominatim → her iki haritaya flyTo (17)
   - `flyToSuggestion()`: `window.appDrawMap` (çizim) + `window.appCbsMap` (CBS referans) hedef alır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK (tüm blade derleniyor)
- Backend matematiği / fiyat mantığı / UI dokunulmadı — sadece frontend JS + HTML

### Sıradaki
- Tarayıcıda kurum (Dicle/ŞUSKİ…) ve belediye kullanıcısı ile manuel test: polyline → length, width kilidi, autocomplete, cadde flyTo
- `/maps` (CBS Entegrasyon) modülüne devam edilebilir

---

## Önceki (31 Temmuz)
- ECDSA Kamu SM Token ile E-İmza (PKCS#11, Apple Silicon/Rosetta 2)
- Electron + Laravel entegrasyonu, x86_64 bridge, PAdES imzalama

## Bu Oturum — Docker + Oracle + Web Sunucu Ayağa Kaldırma

### ✅ Tamamlanan
1. **Görev 1 — Oracle + E-İmza Migration Yaması**
   - SQLite tamamen terk edildi, default bağlantı `oracle`
   - `2026_07_31_000001_eimza_oracle_yamasi` migration Oracle'da Ran (batch 1014)
   - E-imza tabloları Oracle'da mevcut
2. **Görev 2 — CSS Fix'leri**
   - `layouts/admin.blade.php` → Leaflet z-index override
   - İptal modalına `cancel_document` input + `enctype="multipart/form-data"`
   - Butonlar `bg-blue-600 text-white font-bold` + inline style (kullanıcı zorunluluğu)
3. **Görev 3 — Çoklu Cadde Kaybı Araştırması**
   - Sonuç: mevcut kod zaten doğru — `prepareAddressComponents()` create+edit'te `querySelectorAll('.comp-street')` ile TÜM sokakları topluyor; backend (ApplicationsController 171-178, 443-450) JSON decode ediyor; show + cover_letter tüm sokakları render ediyor
4. **Görev 5 — Docker Compose + Web Sunucu**
   - `docker-compose.yml` güncellendi: oracle, redis, php, serve, reverb, adminer
   - Ortak env YAML anchor `&php-env`: DB_HOST=oracle, DB_DATABASE=FREEPDB1, DB_USERNAME=aykome_user, DB_PASSWORD=aykome123, REDIS_HOST=redis
   - serve: host `8001` → container `8000` (`php artisan serve --host=0.0.0.0 --port=8000`)
   - reverb: `8090`, adminer: `8080`, php: `tail -f /dev/null`
   - Tüm container'lar Up: `aykome-v6-oracle` (healthy), redis, php, serve, reverb, adminer

### 🐛 Kritik Çözüm — HTTP 500 / ORA-12541
- **Belirti:** `GET http://127.0.0.1:8001/login` → HTTP 500; log'da `oci_connect(): ORA-12541: TNS:no listener` (Oci8.php:145)
- **Kafa karışıklığı:** serve container'ı İÇİNDE CLI çalışıyordu (`migrate:status` OK, manuel `oci_connect` OK), hatta Http\Kernel ile simüle `/login` 200 döndü — ama gerçek HTTP isteği 500 veriyordu
- **Kök neden:** `php artisan serve` süreci başladığında eski config cache (host=127.0.0.1) kullanıyordu; `config:clear` sonrası serve süreci config'i yeniden okumuyordu
- **Çözüm:**
  1. Host'ta eski OCI8'siz `php artisan serve --port=8001` (PID 1774) → `kill 1774` (8001'i OrbStack'e bıraktı)
  2. `docker exec aykome-v6-serve php artisan config:cache` (host=oracle doğrulandı)
  3. `docker restart aykome-v6-serve`
- **Sonuç:** `login:200`, `admin:302` (auth redirect normal), `migrate --force` → "Nothing to migrate"

### Dosya Yapısı
| Dosya | İşlem |
|---|---|
| `docker-compose.yml` | Oracle + Redis + PHP + serve + reverb + adminer servisleri |
| `database/migrations/2026_07_31_000001_eimza_oracle_yamasi.php` | Oracle yaması (Ran) |
| `resources/views/layouts/admin.blade.php` | Leaflet z-index fix |
| `resources/views/admin/applications/show.blade.php` | İptal modalı + buton fix'leri |

### Sıradaki (Görev 4)
- ~~"Kuruma Gönder (Devret)" vs "Saha Personeline Ata" ayrımı~~ **TAMAMLANDI** (aşağıda)
- E-imza akışını 8001 üzerinden Electron ile test et (Electron main.js `server_url: http://127.0.0.1:8001`)

### ✅ Ek — Görev 4: Kuruma Devret (transferToInstitution)
- **Akış:** Başvuruyu kullanıcıya değil bir kuruma (institution) devreder — `institution_id` güncellenir
- **Route:** `POST admin/applications/{application}/transfer-institution`
- **Controller:** `transferToInstitution()` — authorize + validate + `institution_id` güncelle + timeline log (`institution.transferred`) + AuditLogger (`application.transfer_institution`)
- **Policy:** `transferToInstitution` — belediye yönetimi her başvuruyu, kurum personeli sadece kendi kurumunun başvurusunu devredebilir
- **View:** show.blade.php'ye "Kuruma Devret" butonu + `transfer-institution-modal` (kurum seçici, `bg-sky-600`)
- **Doğrulama:** başvuru 930 kurum 1→2→1 devri başarılı; timeline + audit kayıtları oluşuyor

### 🐛 Bonus Fix — AuditLogger Oracle'da sessizce çöküyordu
- **Belirti:** AuditLog hiç kaydedilmiyordu (`application.transfer_institution` görünmüyordu)
- **Kök neden:** `AuditLog::$timestamps = false` → Laravel `created_at` göndermiyordu → Oracle `ORA-01400: cannot insert NULL into AUDIT_LOGS.CREATED_AT` → AuditLogger `catch (Throwable)` ile sessizce yutuyordu
- **Çözüm:** `$timestamps = false` → `public const UPDATED_AT = null` (Eloquent `created_at`'i kendisi yönetir)
- **Etki:** Tüm audit log akışları (giriş, onaylar, transferler, vs.) artık gerçekten yazılıyor

## Ortam
- **Geliştirme:** macOS (Apple Silicon) + OrbStack; hedef sunucu Windows Server 2025 + Docker
- **Oracle:** `aykome_user/aykome123@FREEPDB1` (port 1521), ağ `aykome_default`
- **Kullanıcı tercihleri:** butonlar `bg-blue-600 text-white font-bold` + inline style; modallar `z-[99999] fixed`; tüm metinler Türkçe

### ✅ Kullanıcı GÖREVİ: Haritada Tam Yerleşim Fixleri (UI Placement) — commit 7e52753
- **GÖREV 1 — Koordinat (lat/lon) ile bul:** create/edit.blade.php'de Adres (`address_text`) div'inin bittiği yerin ALTINA, "Mahalle & Sokak Listesi" kısmının HEMEN ÜSTÜNE `coord_lat` + `coord_lon` + `btn_coord_search` ("📌 Koordinatla Konumlan") bloğu eklendi. JS: `btn_coord_search` → parse + Şanlıurfa bbox doğrulama (33-43 / 26-45) → `haritadaGoster(lat, lon)` (pulse marker + flyTo, 2 harita). `coord-result-info` ile durum mesajı.
- **GÖREV 2 — Harita İçi arama (Leaflet L.Control):** create/edit'teki `#draw-search-box` DOM overlay'i (haritanın DIŞINDA/harf) KALDIRILDI. Yerine initMap içinde `L.Control.extend` → `mapInsideSearch` input + `btn_map_inside_search` → `/maps/adres-ara` fetch → başarılıysa `haritadaGoster` animasyonu. KeyDown Enter ve click tetikli.
- **_harita.blade.php partial:** HTML gömülü arama overlay'i (`cbs-search-input`/`cbs-coord-input`) de aynı şekilde native `L.Control`'e çevrildi (`cbs-native-search`/`cbs-native-coord`), id çakışmasız (canvas-scoped yerine kontrol içi closure). Adres arama `/maps/adres-ara`, koordinat Enter ile pulse + flyTo.
- **Korundu:** geo4/geo3 WMS/WFS, proxy, `/maps` route'ları, hiçbir endpoint logic'i değişmedi.

### 🔧 GERİ BİLDİRİM DÜZELTMESİ — commit ef33a13
- **GÖREV 1:** Çift koordinat kutusu (ayrık `coord_lat`+`coord_lon`) tamamen silindi → tek `coord_single_input` + "Kordinat İle Bul" butonu (bg #0ea5e9, `sm:col-span-2`, Tailwind ile hizalandı). JS: `.split(',')` + regex fallback (`;` `/` `|` boşluk), Şanlıurfa bbox doğrulaması, `haritadaGoster(parsedLat, parsedLng, 'Özel Koordinat Konumu')`.
- **GÖREV 2:** `_harita.blade.php` (CBS referans/WMS haritası) üzerindeki `Leaflet L.Control` arama + HTML overlay + `cbs-search-*` CSS'i tamamen kaldırıldı. Arama artık SADECE ana çizim haritasının (`initMap` içindeki `MapInsideSearchControl`) üzerinde.
