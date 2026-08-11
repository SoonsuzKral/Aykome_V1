# ÇÖZÜM_05 — Son 2 Adım (Kripto Sorunu Çözüldü ✓)

> ÇÖZÜM_04'teki asıl kriptografik sorun (attrsDigest 0xA0→0x31, signature
> BIT STRING→OCTET STRING) ÇÖZÜLDÜ ve DSS + Adobe'de bağımsız olarak
> doğrulandı. Kalan 2 madde: biri küçük bir kod düzeltmesi, diğeri kod
> DEĞİL — kurumsal kurulum. Bunlar bitince e-imza tamamlanmış olacak, CSS
> ince ayarına (ÇÖZÜM_02/03) geçilebilir.

---

## Durum — Kanıtlanmış Başarı

DSS detaylı raporu: `Format Checking: PASSED` (ByteRange, sayfa sayısı,
örtüşme, hepsi yeşil) — `Cryptographic Verification: PASSED` (mesaj özeti
sağlam VE imza sağlam). Adobe: kırmızı X gitti, imzalayan adı artık doğru
gösteriliyor (önceden gösteremiyordu), "belge imzalandığından beri
değişmedi" onayı var. Bu iki bağımsız araç aynı sonuca varıyor — kod artık
matematiksel olarak doğru bir imza üretiyor.

---

## KALAN İŞ 1 (KOD) — SigningCertificateV2 Yapısında Eksik Sarmalayıcı

DSS hâlâ diyor ki: *"The signed attribute: 'signing-certificate' is
absent!"* — ama kod ESS SigningCertificateV2'yi zaten ekliyordu. Sebep
muhtemelen ASN.1 yapısında bir eksik seviye:

```
SigningCertificateV2 ::= SEQUENCE {
    certs        SEQUENCE OF ESSCertIDv2,
    policies     SEQUENCE OF PolicyInformation OPTIONAL }
```

`sign-pdf.js`'teki mevcut kod `essCertId`'yi (tek bir ESSCertIDv2 değeri)
DOĞRUDAN attrValue olarak kullanıyor — ama yapı, onu ÖNCE bir "SEQUENCE OF"
(certs alanı) İÇİNE, sonra o alanı da dış SigningCertificateV2 SEQUENCE'ına
sarmalı. Muhtemelen 2 seviye `derSeq` eksik.

**Düzeltme:**
```diff
  const essCertId = derSeq(
    derSeq(derOid(OID_SHA256), derNull()),
    derOct(digestBytes(certDer, 'sha256')),
  );
+ // SigningCertificateV2 ::= SEQUENCE { certs SEQUENCE OF ESSCertIDv2 }
+ const signingCertificateV2 = derSeq(derSeq(essCertId));

  const attrs = [
    ...
-   derAttr(OID_ESS_SIGNING_CERT_V2, essCertId),
+   derAttr(OID_ESS_SIGNING_CERT_V2, signingCertificateV2),
  ];
```

**Doğrula:** değişiklikten sonra taze imzala, DSS'e yükle, "Signature
Acceptance Validation" altında *"Is the signed attribute:
'signing-certificate' present?"* artık ✓ (yeşil) olmalı, sarı uyarı işareti
kaybolmalı. Bu, imzanın GEÇERLİLİĞİNİ etkilemiyor (Cryptographic
Verification zaten PASSED) — sadece PAdES-B-B tam uyumluluğu için.

---

## KALAN İŞ 2 (KOD DEĞİL) — Adobe'de Kurum Çapında Güven Kurulumu

Bu bir bug değil. DSS'in dediği `NO_CERTIFICATE_CHAIN_FOUND` ve Adobe'nin
sarı uyarısı ("güvenilir sertifikalar listenizde yer almadığı için")
**beklenen** durum — E-Tuğra ve Kamu SM kökleri AB/Adobe'nin varsayılan
güven listesinde değil (Türk devlet CA'ları genelde değil). Bunu HER
bilgisayarda tek tek "Güvenilir Sertifikalara Ekle" ile çözmek yerine,
Adobe'nin resmi kurumsal aracıyla TÜM Eyyübiye Belediyesi bilgisayarlarına
tek seferde dağıtılabilir.

**Resmi yol (Adobe'nin kendi dokümantasyonu):**

1. Bir "referans" bilgisayarda, imzalı bir AYKOME belgesini açıp E-Tuğra
   VE Kamu SM köklerini "Güvenilir Sertifikalara Ekle" ile manuel ekleyin
   (şu ana kadar test için yaptığınız gibi) — "Belgeleri veya verileri
   imzalayın" ve "Belgeleri onaylayın" kutularını işaretleyin.
2. Adobe'nin ücretsiz **Acrobat Customization Wizard** aracını indirin
   (Adobe'nin resmi kurumsal dağıtım aracı — Acrobat DC/Reader ile birlikte
   veya ayrı indirilebilir).
3. Wizard ile bu referans bilgisayardaki güven ayarlarını bir "Security
   Settings" paketi olarak dışa aktarın.
4. Bu paketi GPO (Group Policy) veya MSI transform ile diğer tüm
   bilgisayarlara dağıtın.
5. Alternatif/tamamlayıcı: Adobe "Windows Sertifika Deposundaki tüm kök
   sertifikalara güven" ayarını açıp, E-Tuğra + Kamu SM köklerini GPO ile
   doğrudan Windows'un "Trusted Root Certification Authorities" deposuna
   ekleyebilirsiniz — bu, hem Adobe hem başka uygulamalar için tek seferde
   çözer.

Ayrıntılı resmi kılavuz: `adobe.com/devnet-docs/acrobatetk/tools/AppSec/trust.html`
("Acrobat Desktop Application Security Guide" — Trust Methods bölümü) ve
Acrobat Customization Wizard dokümantasyonu. Bu adım BT/sistem yöneticisi
işi — OpenCode'un kod tarafında yapacağı bir şey yok, bu maddeyi Belediye
IT ekibine iletmeniz gerekebilir.

---

## Sıra

1. KALAN İŞ 1'i uygulayın (OpenCode) — küçük, hızlı
2. Taze imzalayıp DSS'te "signing-certificate" uyarısının gittiğini
   doğrulayın
3. KALAN İŞ 2'yi (kurulum) belediye IT ekibiyle planlayın — bu bittiğinde
   HİÇBİR bilgisayarda sarı uyarı çıkmayacak
4. Her ikisi de tamamlanınca e-imza kısmı BİTMİŞ sayılır — ÇÖZÜM_02/03'teki
   CSS ince ayarlarına (kenar boşlukları, ön kazı/metraj sığdırma) dönün
