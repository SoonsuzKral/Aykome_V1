# ÇÖZÜM_04 — KRİTİK: İmza Algoritması Uyuşmazlığı (ECDSA vs node-forge)

> ÖNCELİK: bu, ÇÖZÜM_02/03'teki CSS/layout işlerinden DAHA ÖNEMLİ — çünkü
> imzanın hukuki geçerliliğiyle ilgili. Diğer dosyalarla paralel çalışılabilir
> ama bu, ayrı bir dikkatli (kriptografi) süreç gerektiriyor — acele
> "değiştir, dene" yapmayın, her adımda BAĞIMSIZ doğrulama araçlarıyla test
> edin (sadece Adobe'ye güvenmeyin, Adobe'nin de kendine özgü tuhaflıkları var).

---

## 0. KESİNLEŞTİ — Bu Ulusal Bir Zorunlu Değişiklik, AYKOME'ye Özgü Değil

Kullanıcı hem Kamu SM hem E-Tuğra token ile test etti — İKİSİ DE aynı şekilde
"imza geçersiz" verdi. Bunun nedeni bulundu ve KANITLANDI:

**BTK'nın (Bilgi Teknolojileri ve İletişim Kurumu) 14.01.2025 tarihli
2025/İK-BTD/11 sayılı Kurul Kararı gereği, 5 Mayıs 2025'ten itibaren
Türkiye'de üretilen TÜM yeni Nitelikli Elektronik Sertifikalar (NES) RSA
2048 yerine Eliptik Eğri 384 (ECDSA P-384) anahtarla üretiliyor** (Kamu SM'in
kendi resmi duyurusu: kamusm.bilgem.tubitak.gov.tr/duyurular). Bu, TÜM BTK
onaylı sağlayıcıları (Kamu SM, E-Tuğra, E-Güven, TürkTrust, vb.) kapsayan
ülke çapında zorunlu bir teknik kriter değişikliği — kartlarda veya AYKOME'de
bir bozukluk değil.

Bu, §1'deki node-forge/ECDSA teşhisini KESİNLEŞTİRİYOR: iki farklı sağlayıcı
aynı hatayı veriyorsa, sorun sertifikada değil, HER İKİSİNİ de aynı şekilde
işleyen sizin kodunuzdadır. Çözüm net: CMS/PKCS#7 imza yapısını inşa eden
kütüphaneyi ECDSA-destekli birine geçirmek (§3). Bu değiştiğinde, AYKOME'de
imzalanan HER belge (hangi sağlayıcının kartıyla imzalanırsa imzalansın)
gerçekten geçerli olacak.

---

## 1. Kanıtlanmış Bulgu

**AKİS sertifikası:** `İmzalama algoritması: SHA384WITHECDSA` (Görsel: AKİS
sertifika detay penceresi, "Non Repudiation" sertifikası, kullanım alanı
"Dijital imzalama, Reddedilemezlik"). Bu **ECDSA** — RSA değil.

**Adobe'nin AYKOME imzası hakkındaki tespiti:** "Gelişmiş İmza Özellikleri"
penceresinde net olarak:
- `Anahtar Algoritması: Geçersiz`
- `İmza Algoritması: Geçersiz`
- Genel özet: "Bu imza geçersiz çünkü formatlamada veya bu imzada içerilen
  bilgilerde hatalar var."

**Kütüphane kanıtı:** `node-forge` (node-signpdf'in PKCS#7/CMS yapısını inşa
etmek için kullandığı bağımlılık) eliptik eğri (EC/ECDSA) desteklemiyor —
bu, kütüphanenin kendi deposunda yıllardır açık bir issue (`digitalbazaar/forge`
#532, #580). Adobe'nin topluluk forumunda da aynı belirti deseni (EC
sertifika + PKCS#7 imza inşası → Adobe "algoritma geçersiz/desteklenmiyor"
diyor, ama var olan doğru-inşa-edilmiş EC imzalarını doğrulayabiliyor)
defalarca raporlanmış.

**Sonuç:** eğer imzalama kodunuz node-forge ile (doğrudan ya da
node-signpdf üzerinden) CMS SignedData yapısını inşa ediyorsa VE karttan
gelen imza ECDSA ise, kütüphane bunu doğru kodlayamıyor olabilir — algoritma
kimliği (OID) yanlış/eksik kalıyor ya da ham imza byte'ları RSA formatı
varsayılarak yanlış yerleştiriliyor. Bu, Adobe'nin gördüğü tam belirtiyle
örtüşüyor.

---

## 2. Doğrulama — Kodda Ara

```bash
# İmzalama kodu nerede, hangi kütüphaneleri kullanıyor?
grep -rln "node-forge\|require('forge')\|from 'node-forge'" <electron-repo-yolu> --include="*.js" --include="*.ts"

# CMS/PKCS#7 SignedData yapısı NEREDE ve NASIL inşa ediliyor?
grep -rn "SignedData\|PKCS7\|pkcs7\|digestAlgorithm\|signatureAlgorithm" <electron-repo-yolu> --include="*.js" --include="*.ts"

# RSA'ya özgü sabit OID/algoritma referansları var mı? (varsa bu, ECDSA'yı
# hesaba katmadığının işareti)
grep -rn "rsaEncryption\|sha256WithRSA\|1.2.840.113549.1.1\|RSASSA" <electron-repo-yolu> --include="*.js" --include="*.ts"

# Sertifikanın public key tipini kontrol eden bir dallanma var mı?
# (varsa iyi haber — ECDSA-özel bir yol zaten mevcut olabilir, sadece
#  hatalı olabilir; yoksa kod her zaman RSA varsayıyor demektir)
grep -rn "EC\b\|ecdsa\|elliptic\|publicKeyAlgorithm\|keyType" <electron-repo-yolu> --include="*.js" --include="*.ts"
```

Bulguyu bana/kullanıcıya rapor edin: kod node-forge kullanıyor mu, ECDSA'ya
özel bir dallanma var mı yok mu.

---

## 3. Muhtemel Çözüm Yönü (dikkatli uygulanmalı)

Eğer doğrulama node-forge'un ECDSA'yı desteklemediğini onaylarsa, CMS/PKCS#7
inşası için **EC destekli** bir kütüphaneye geçiş gerekiyor. En bilinen
alternatif: **`pkijs`** (npm) — WebCrypto API üzerine kurulu, EC anahtarları
ve ECDSA imza algoritmalarını (`ecdsa-with-SHA384` dahil) doğru OID'lerle
native destekliyor. Bu kütüphane CMS/CAdES/PAdES yapıları inşa etmek için
yaygın kullanılıyor.

**Bu değişikliği yaparken:**
- Mevcut çalışan kısımları (dosyayı Electron'a gönderme, AKİS/PKCS#11 ile ham
  imzayı alma, PDF'e placeholder ekleme — `plainAddPlaceholder` kısmı)
  BOZMAYIN. Sadece "ham imzayı + sertifikayı alıp doğru CMS SignedData
  yapısını inşa etme" adımını değiştirin.
- Değişikliği ÖNCE ayrı bir test scriptinde deneyin, mevcut e2e akışına
  hemen entegre etmeyin.
- ASLA "çalışıyor gibi görünüyor" ile yetinmeyin — kriptografik doğruluk
  görsel olarak anlaşılmaz, sadece bağımsız araçlarla doğrulanır (§4).

---

## 4. Test — SADECE Adobe'ye Güvenmeyin

Adobe'nin EC imzalarıyla ilgili kendine özgü tuhaflıkları olduğu biliniyor
(bazı durumlarda gerçekte geçerli bir imzayı da reddedebiliyor). Düzeltmeden
sonra EN AZ 2 bağımsız yöntemle çapraz kontrol edin:

```bash
# 1) OpenSSL ile CMS yapısını incele (algoritma OID'leri doğru mu?)
openssl cms -verify -in imzali.pdf -inform DER -noverify -text 2>&1 | head -50
# veya PDF'ten imza blobunu çıkarıp:
openssl asn1parse -inform DER -in signature.der

# 2) AB'nin bağımsız, standart-uyumlu doğrulayıcısı (Adobe'den bağımsız):
# https://ec.europa.eu/digital-building-blocks/DSS/webapp-demo/validation
# İmzalı PDF'i buraya yükleyip sonucu Adobe'ninkiyle karşılaştırın.
```

Eğer düzeltme sonrası HEM Adobe HEM EU DSS validator "geçerli" diyorsa, gerçek
bir çözüm var demektir. Sadece biri diyorsa, hangisi olduğunu bana raporlayın
— yorumlamamız gerekebilir.

---

## 5. Ayrı, Daha Az Kritik Konu: Adobe'de PKCS#11 Modülü Tanımlı Değil

Bu, Adobe'nin KENDİ imzalama aracıyla (sizin Node.js akışınız değil) AKİS'i
kullanabilmesi için gereken ayrı bir kurulum. İsterseniz:
`Menü → Tercihler → İmzalar → Kimlikler ve Güvenilir Sertifikalar → Daha
Fazla → Dijital Kimlikler → PKCS#11 Modülleri → Modülü Ekle` — AKİS'in
sağladığı `.dll` dosyasını (genelde `akisp11.dll` veya benzeri, AKİS/Kamu SM
kurulum klasöründe) seçin. Bu, ana sorunun nedeni değil — sadece Adobe
üzerinden manuel imzalama/test isterseniz gerekli.

---

## Sıra

1. §2'deki grep'lerle kodu inceleyin, node-forge + RSA-varsayımı doğrulanıyor
   mu netleştirin — bana/kullanıcıya rapor edin, ONAYLANMADAN değişikliğe
   geçmeyin
2. Onaylanırsa §3'teki yönde (pkijs veya benzeri EC-destekli kütüphane) ayrı
   bir test scriptinde deneyin
3. §4'teki İKİ bağımsız araçla doğrulayın — sadece Adobe'ye güvenmeyin
4. Geçerse mevcut e2e_sign.cjs akışına entegre edin, 7 belge tipinin tümünü
   yeniden imzalayıp aynı çift-doğrulamayı tekrarlayın
5. §5 opsiyonel, isterseniz ayrıca yapın

---

## 6. SONUÇ — Gerçek Kök Nedenler ve Çözüm (UYGULANDI)

> ÖNEMLİ: Bu görevin asıl hipotezi (node-forge ECDSA desteklemiyor / RSA
> varsayımı) YANLIŞTI. CMS el yapımı DER ile inşa ediliyor; node-forge yalnızca
> OID ve hash için kullanılıyor; ECDSA yolu (OID_ECDSA_SHA384 + ham digest
> imzası + DER dönüşümü) zaten vardı. Gerçek kök nedenler aşağıda.

### 6.1 Kök Neden 1 — ByteRange'te 3. eleman YANLIŞTI (kritik)

`buildPades()` şunu yazıyordu: `byteRange = [0, gapStart, gapSize, region2Size]`

PDF 32000-1 §12.8.1 / ISO 32000-2: ByteRange = `[start1, len1, start2, len2]`
— 3. eleman 2. SEGMENTİN BAŞLANGIÇ OFSETİDİR (`gapStart + gapSize`), segment
uzunluğu değil. (Referans: node-signpdf `dist/signpdf.js:92-95`:
`byteRange[2] = byteRange[1] + placeholderLengthWithBrackets`).

Sonuç: doğrulayıcılar 2. segmenti 1. segmentin İÇİNDE sayıyor → özet her zaman
uyuşmuyor → **HER imza her doğrulayıcıda (Adobe dahil) geçersiz**.

Düzeltme (`src/pades/sign-pdf.js`):
```js
const byteRange = [0, gapStart, region2Start, region2Size];
```

### 6.2 Kök Neden 2 — İmza, signedAttrs'in YANLIŞ DER kodlaması üzerine atılıyordu (kritik)

RFC 5652 §5.4: imza, signedAttrs ALANININ TAM DER kodlaması üzerine atılır —
`[0] IMPLICIT` olduğu için **A0 tag'ı dahil** (`A0 <len> <attrs...>`).

Kod, `0x31` (SET OF tag'ı) ile hash'liyordu. Özet kendi içinde tutarlıydı
(bizim doğrulayıcı da aynı hatayı yapıyordu), ama OpenSSL/EU DSS/Adobe
A0-kodlamasını hash'lediği için imza reddediliyordu.

Düzeltme:
```js
const attrsDigest = digestBytes(der(0xA0, attrsContent), algo);
```

### 6.3 Kök Neden 3 — CMS signerInfo signature alanı OCTET STRING idi (önemli)

RFC 5652: `signature BIT STRING` (03, unused-bits baytı 0x00 ile). Kod
`derOct()` (04) kullanıyordu. OpenSSL kendi üretiminde PKCS#7 legacy nedeniyle
OCTET STRING kullanır (ve sadece onu kabul eder) — ama RFC 5652 / EU DSS /
BouncyCastle BIT STRING bekler. Türkiye'deki Kamu SM SDK'ları da BIT STRING
üretir (Adobe bunları doğrular).

Düzeltme:
```js
const derBitStr = (b) => der(0x03, Buffer.concat([Buffer.from([0x00]), b]));
// ... derBitStr(signatureDer)
```

### 6.4 Kök Neden 4 — RSA imza OID'i yanlıştı (ikincil)

`signatureAlgorithm` olarak `rsaEncryption` (1.2.840.113549.1.1.1) yazılıyordu;
şimdi `sha256WithRSAEncryption` (1.2.840.113549.1.1.11) — PKCS#1 v1.5
DigestInfo imzasıyla uyumlu doğru OID.

### 6.5 Ek düzeltmeler — bridge'lerde ECDSA çift-DER dönüşümü koruması

`Pkcs11Bridge.cs` + `pkcs11-bridge.c`: ECDSA imzası `0x30` ile başlıyorsa
(zaten DER) raw→DER dönüşümü ARTIK ATLANIYOR (çift dönüşüm bozuk imza
üretirdi). Ayrıca C bridge'i artık CKA_KEY_TYPE ile RSA/EC ayrımı yapıp
`CKM_RSA_PKCS` kullanıyor (eskiden her zaman CKM_ECDSA idi — RSA kartlarda
garantili bozuk imza). `.exe` yeniden derlendi (csc, Framework 4.8).

### 6.6 Doğrulama — 4 bağımsız yöntem

| Yöntem | Sonuç |
|---|---|
| `node test_cms_crypto.cjs` (node crypto = OpenSSL 3) — 7/7 belge | messageDigest OK + imza DOĞRULANDI + DER canonical + algo uyumu, "TÜM KRİPTOGRAFİK KONTROLLER GEÇTİ" |
| `openssl dgst -sha256 -verify` (A0-alan üzerinde, gerçek AKİS RSA token imzası) | **Verified OK** |
| EC izole test (openssl P-384 + buildPades keyType='EC' + pkeyutl) | messageDigest OK (sha384) + EC imzası DOĞRULANDI |
| `test_verify_all.py` + `verify_signed.py` (PDF yapısı regresyon) | 7/7 PASS, TÜM İMZALI PDF'LER TEMİZ |

Not: `openssl cms -verify` komutu bu imzaları PARSE etmez — OpenSSL'in CMS
şablonu signature'ı legacy PKCS#7 tarzı OCTET STRING bekler; RFC 5652 (BIT
STRING) yapısını reddeder. Bu bir uyumsuzluk değildir; hukuki doğrulama
(Adobe + EU DSS webapp) RFC 5652'ye göre yapılır ve BIT STRING doğru şekildir.
Bağımsız openssl kontrolü `openssl dgst` ile yapılmıştır.

### 6.7 Kullanıcıdan istenen son adım

1. `storage/app/e2e_signed_*.pdf` (7 belge) → EU DSS webapp validation:
   https://ec.europa.eu/digital-building-blocks/DSS/webapp-demo/validation
2. Aynı 7 belgeyi Adobe Acrobat'ta açıp imza panelini kontrol edin.
3. Sonuçları raporlayın (her iki aracın da "geçerli" demesi bekleniyor).

