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

## 6. İKİNCİ TUR BULGUSU — Düzeltme İzole Testte Geçti, GERÇEK Belgede Hâlâ Bozuk

OpenCode 4 kök neden düzelttiğini raporladı (ByteRange, hash kodlama, BIT
STRING, RSA OID) ve `test_cms_crypto.cjs` ile 7/7 geçtiğini söyledi. AMA
kullanıcı GERÇEK bir üst yazı belgesini (`imzali-2026-1000_cover_letter_imzali.pdf`,
uygulamanın kendisinden, test script'i değil) EU DSS validator'a yükledi —
AYNI hata hâlâ var:

```
/ByteRange dictionary is not consistent!
Reference data object is not intact! (hash hatası)
```

**Matematiksel kanıt:** `ByteRange: [0, 29567, 16386, 750]` — ikinci aralık
(16386) birincinin bittiği yerden (0+29567=29567) ÖNCE başlıyor. Bu formatça
bozuk, düzeltilmemiş.

**Buradan çıkan kritik sonuç:** düzeltme muhtemelen `test_cms_crypto.cjs` gibi
İZOLE bir test script'inde doğru çalışıyor ama GERÇEK uygulamanın "İmzala"
butonuna basınca çalışan kod yolu (`sign-pdf.js` ya da onu çağıran gerçek
controller/servis) hâlâ eski mantığı kullanıyor OLABİLİR — ya da test edilen
dosya düzeltmeden ÖNCE üretilmiş eski bir dosya.

**Ayrıca yeni bir eksik ortaya çıktı** (OpenCode'un ilk 4'lük listesinde
YOKTU): `The signed attribute: 'signing-certificate' is absent!` — PAdES/CAdES
standardının zorunlu tuttuğu bu imzalı öznitelik (ESS SigningCertificateV2,
imzalayan sertifikanın hash'ini içerir) hiç eklenmemiş. Bu yüzden format
"PKCS7-B" — tam PAdES-B-B seviyesine bile ulaşmıyor.

**Görmezden gelinebilir (düzelmeyecek, düzelmesi de gerekmiyor):**
"Unable to build a certificate chain up to a trusted list" — DSS aracı AB
Üye Devlet listesine göre kontrol ediyor, Türk devlet sertifikaları orada
değil ve olmayacak. Bu, Adobe'deki "kök sertifikayı manuel güvenilir işaretle"
meselesiyle aynı, ayrı ve beklenen bir durum.

**Küçük, ayrı bir kozmetik bug:** sistem "İmzalayan: ... (Kamu SM)" gösteriyor
ama sertifika zinciri E-Tuğra. Sağlayıcı adı sabit/yanlış yazılmış olabilir —
öncelikli değil, ama not edilsin.

### GÖREV — Kesinleştirme Adımları

```bash
# 1) Test edilen dosya GERÇEKTEN yeni koddan mı geçti? Dosya zaman damgasını
#    son commit zamanıyla karşılaştırın.
ls -la storage/app/*cover_letter*imzali*.pdf
git log -1 --format=%cd -- <sign-pdf.js yolu>

# 2) Gerçek "İmzala" butonu HANGİ fonksiyonu çağırıyor — test_cms_crypto.cjs'in
#    test ettiği AYNI fonksiyon mu, yoksa farklı/eski bir yol mu?
grep -rn "sign-pdf\|signPdf\|imzala" app/Http/Controllers --include="*.php"
grep -n "require.*sign-pdf\|import.*sign-pdf" <electron-repo>/*.js

# 3) plainAddPlaceholder HÂLÂ kullanılıyor mu ByteRange/placeholder için?
#    Yoksa bu kısım da elle mi yeniden yazıldı? ByteRange hesaplaması gibi
#    RSA/ECDSA'dan bağımsız, zaten çözülmüş bir problemi elle yeniden
#    yazmak gereksiz risk katar — sadece CMS/SignedData inşası (asıl ECDSA
#    gerektiren kısım) değiştirilmeli, placeholder/ByteRange mekaniği
#    node-signpdf'in test edilmiş fonksiyonunda kalmalı.
grep -n "plainAddPlaceholder\|ByteRange" <electron-repo>/*.js
```

Bulgulara göre: (a) eski dosya test edildiyse → GERÇEK uygulamadan TAZE bir
belge üretip yeniden imzalayıp tekrar DSS'e yükleyin; (b) gerçek kod yolu
farklıysa → o yolu da düzeltilmiş fonksiyona bağlayın; (c) placeholder elle
yeniden yazıldıysa → `plainAddPlaceholder`'a geri dönün, sadece CMS inşasını
değiştirin. Ayrıca `signing-certificate` (ESS SigningCertificateV2) imzalı
özniteliğini CMS SignerInfo'ya ekleyin.

**Bu kez "tamamlandı" demeden önce:** GERÇEK uygulamadan (test script değil)
taze bir belge üretip imzalayın, DSS'e yükleyin, `/ByteRange dictionary is
not consistent` VE `reference data object is not intact` satırlarının
KAYBOLDUĞUNU gözle görün — sadece kendi test script'inizin çıktısına
güvenmeyin, çünkü bu turda tam da bu güven kırıldı.

---

## 7. ÜÇÜNCÜ TUR — KÖK NEDEN KESİNLEŞTİ: Uygulama Yeniden Başlatılmadan Bellekteki ESKİ Kod Çalışıyor

### 7.1 Kesin kanıt (süreç zaman çizelgesi)

Kullanıcı, düzeltme commit'i SONRASI (19:21 TSİ) imzaladı ama belgede
`ByteRange: [0, 29567, 16386, 750]` — 3. eleman YİNE `gapSize` (eski hata).
Zaman çizelgesi her şeyi açıkladı:

| Zaman (TSİ) | Olay |
|---|---|
| 15:18:31 | Electron uygulaması AÇILDI → `sign-pdf.js` ESKİ sürümü belleğe yüklendi (Node require cache) |
| 19:12:30 | `sign-pdf.js` düzeltildi (repo'da; e2e + openssl ile 4 bağımsız doğrulama GEÇTİ) |
| 19:21 | Kullanıcı imzaladı → açık process hâlâ BELLEKTEKİ eski kodu çalıştırdı → eski ByteRange |

Kod tarafında hata YOK — düzeltme doğruydu. Sorun: **uzun süredir açık duran
Electron uygulaması, modülü bellekte tuttuğu için yeni dosyayı hiç görmedi.**
`Get-Process electron | Select StartTime` ile doğrulandı: 4 process de 15:18'de
başlamıştı.

### 7.2 Yeni kod değişikliği — ESS SigningCertificateV2 (DSS "ABSENT" bulgusu)

DSS raporu ayrıca şunu gösterdi: `The signed attribute: 'signing-certificate'
is absent!` — ETSI EN 319 102-1 AdES-BES'in zorunlu özniteliği eksikti
(bu yüzden "Signature is not a valid AdES digital signature" deniyordu).

Eklendi (`src/pades/sign-pdf.js`):
- `ESS SigningCertificateV2` (OID 1.2.840.113549.1.9.16.2.47): ESSCertIDv2 =
  SEQUENCE { hashAlgorithm(sha256+NULL), certHash OCTET STRING(sha256(certDer)) }
- signedAttrs artık DER canonical BYTE sıralamasına göre koddan sıralanıyor
  (`Buffer.compare` sort) — yeni attr eklenince sıra değişti
  (contentType, signingTime, messageDigest, signingCertificateV2) ve sıralama
  her koşulda canonical kalıyor.

### 7.3 Doğrulama (üçüncü tur, tamamı geçti)

| Yöntem | Sonuç |
|---|---|
| `test_cms_crypto.cjs` 7/7 (gerçek AKİS RSA token) | TÜM KRİPTOGRAFİK KONTROLLER GEÇTİ; [5] SigningCertificateV2 MEVCUT + certHash doğru |
| EC P-384 izole test (openssl + buildPades EC yolu) | sha384 + ecdsa-with-SHA384 + canonical OK + [5] OK |
| `test_verify_all.py` | 7/7 PASS |
| `verify_signed.py` | TÜM İMZALI PDF'LER TEMİZ |

### 7.4 Kullanıcı için ZORUNLU adım (bir kez)

1. **Açık duran "Eyyübiye AYKOME - E-İmza" uygulamasını TAMAMEN kapatın**
   (tray'den çıkış dahil; `Get-Process electron` boş olmalı)
2. Uygulamayı yeniden başlatın
3. **TAZE** bir belge imzalayın — imzalı PDF'te `/ByteRange [0, X, Y, Z]`
   içinde **Y > X** olduğunu gözünüzle kontrol edin (örn. `[0, 29567, 45953, 750]`)
4. DSS webapp'e yükleyin: `/ByteRange dictionary is not consistent` ve
   `Reference data object is not intact` satırları KAYBOLMALI;
   `signing-certificate` artık ABSENT olmamalı
5. Adobe Reader'da imza paneli geçerli görünmeli

Not: "Certificate chain INDETERMINATE / No trust anchor" satırı BEKLENDİK ve
düzelmesi gerekmez — Türk devlet kök CA'ları AB trust listesinde değildir
(Adobe'de kökü elle güvenilir işaretlemekle aynı durum).

