# ÇÖZÜM_06 — CSS İnce Ayar: Güncel Durum ve Kalan İşler

> E-imza kripto sorunu bitti (ÇÖZÜM_04/05). Şimdi sıra layout/görsel ince
> ayarlarda. **ÇÖZÜM_03'ün yerine geçer** — o dosya e-imza krizi yüzünden
> hiç OpenCode'a verilmedi, hâlâ geçerli olan her şey buraya taşındı. Sadece
> BU dosyayı kullanın.

---

## Önce Doğrulayın (muhtemelen zaten düzeldi, ama kesinleştirin)

Bu ikisi, ÇÖZÜM_02'de tanımlandı ve sonraki turlarda muhtemelen düzeltildi
("SORUN A yatay/dikey", "SORUN B logo" todo'ları geçti olarak
işaretlenmişti) — ama e-imza turlarında ayrıca doğrulanmadı:

```bash
# 1) Tahakkuk + metraj'da doğrulama kodu satırı gerçekten var mı?
grep -c "DOĞRULAMA KODU" storage/app/e2e_signed_tahakkuk.pdf storage/app/e2e_signed_metraj.pdf 2>/dev/null
# yoksa PyMuPDF ile: "5070 sayılı" + "DOĞRULAMA KODU" ikisi de metinde olmalı

# 2) cover_letter'ın KENDİ margin override'ı (168mm gibi sabit bir değer)
#    hala mı duruyor, yoksa SORUN A'daki formülle mi güncellendi?
grep -n "168mm\|cover_letter" app/Services/EImzaService.php
```
Sonuç neyse rapor edin — muhtemelen "zaten dolu/güncel" çıkacak, hızlı bir
kontrol.

### Eğer tahakkuk'ta doğrulama kodu GERÇEKTEN eksikse — tam düzeltme

**Kesin istenen sıra (5 belge tipinin TAMAMINDA):** kırmızı 5070 yasal
metni, hemen ALTINDA doğrulama kodu satırı. Ruhsat + cover_letter'da bu
doğru; tahakkuk'ta önceden bir boşluk vardı, doğrulama kodu hiç
basılmıyordu.

```php
// EImzaService.php'deki imzaYasalMetinEkle() — tahakkuk (Grup B) için,
// 5070 metninden HEMEN SONRA:
$html = str_replace(
    '{{DOGRULAMA_YER_TUTUCU}}',  // veya mevcut boş alanın gerçek işareti
    sprintf(
        '<p style="text-align:center;font-size:8px;color:#888;">BELGE DOĞRULAMA KODU: %s | KONTROL ADRESİ: %s</p>',
        $dogrulamaKodu, $kontrolAdresi
    ),
    $html
);
```
Gerçek değişken adlarını/yerini kod tabanınıza göre uyarlayın — mantık:
tahakkuk'un mevcut boş `<div>`/spacer'ını bulup (`grep -n "a4-footer\|footer-note\|<div.*height" resources/views/admin/pdf/tahakkuk.blade.php`)
onun yerine bu satırı koyun.

---

## KESİN AÇIK — Bunlar Düzeltilmedi

### 1. Metraj (saha_metraj) hâlâ A4'e sığmıyor

Kullanıcının kendi bulgusu: "KAZI METRAJ KAGIDA TAM SIGMIYOR". Bu, önceki
turda **bilerek atlandı** ("metraj 27.6/30.1 değişmedi — onaylı görünüm
korundu"). Şimdi ele alınmalı, VE aynı belgede doğrulama kodu da eksikti —
ikisi birlikte çözülmeli:

```python
# Ölçüm (COZUM_02'deki script, ama landscape sayfa boyutuyla — metraj yatay)
olcum_raporu('storage/app/test_metraj_5070.pdf')
```
Landscape formül: `(297mm - 2×@page_margin) - (sol_padding+sağ_padding)`.
Mevcut `245mm` değerini ölçülen gerçek boşluğa göre kademeli artırın, her
adımda `test_verify_all.py` ile hâlâ doğru sayfa sayısında mı / taşma yok
mu doğrulayın (metraj için "1 sayfa" varsayımını kontrol edin — satır
sayısına göre birden fazla sayfa da doğru olabilir, önce gerçek beklenen
sayfa sayısını netleştirin). Doğrulama kodu için yukarıdaki "tahakkuk"
düzeltmesinin aynısını metraj'a da uygulayın.

### 2. "Ön Kazı" = cover_letter (KESİNLEŞTİ) — Logo Kod Sorunu DEĞİL, Margin Sorunu VAR

**Netleşti:** "ön kazı" dediğimiz belge `cover_letter` şablonunun kendisi
(Dicle Elektrik, ŞUSKİ, Vodafone — hepsi aynı "Sayı/Konu/İlgi" şablonunu
kullanıyor). Kod incelendi (`EImzaService.php` satır 270-279): logo, PDF
üretilmeden ÖNCE HTML içine base64 `<img>` olarak enjekte ediliyor —
imzalama adımı bu tamamlanmış PDF'e SONRADAN dokunmuyor. Mimari doğru.

**Logo "eksik" görünmesi bir KOD hatası DEĞİL — veri eksikliği:**
`institutionLogoBase64($application)` → `$application->institution->logo_path`
boşsa fonksiyon `null` döner, `if ($logoBase64)` koşulu false olur, hiç
logo basılmaz. Vodafone'un logosu ÇIKIYOR (kurum kaydında logo var), ŞUSKİ'
ninki ÇIKMIYOR (muhtemelen kurum kaydında logo yok). **Bunu düzeltmek kod
değişikliği değil, veri kontrolü:**
```bash
php artisan tinker --execute="App\Models\Institution::whereNull('logo_path')->orWhere('logo_path','')->pluck('name')"
```
Bu listede çıkan kurumlar için logo eksik — ya kullanıcı yüklesin ya da
(tutarlılık için) pre_permit'in zaten yaptığı gibi "kurum logosu yoksa
belediye logosuna düş" mantığı cover_letter'a da eklenebilir (opsiyonel
iyileştirme, zorunlu değil).

**Gerçek açık sorun — Margin:** Vodafone örneğinde (logo doğru çıkmasına
rağmen) sağ/sol kenarda hâlâ ince boşluk şeridi görünüyor. Bu, cover_letter'ın
KENDİ margin override'ının (ÇÖZÜM_02'de bahsedilen `168mm` gibi sabit bir
değer) SORUN A'daki formülle güncellenmediğine işaret ediyor — "Önce
Doğrulayın" bölümündeki 2. kontrolü MUTLAKA çalıştırıp bu değeri düzeltin:
```python
olcum_raporu('storage/app/test_cover_letter_5070.pdf')
```
Ölçülen gerçek boşluğa göre `pdfTipineGoreEkCss()` içindeki cover_letter
genişlik/padding değerlerini SORUN A'daki formülle (`(210mm-2×@page_margin)
- padding_toplam`) kademeli düzeltin.

---

## Sıra

1. "Önce Doğrulayın" bölümünü çalıştırın, sonucu rapor edin (hızlı)
2. Madde 1 (metraj: sığdırma + doğrulama kodu) — ölçüm + kademeli düzeltme
3. Madde 2 (ön kazı) — önce netleştirme, sonra logo + margin
4. Her düzeltmeden sonra `test_pdf_generate.php` + `test_verify_all.py` +
   ilgili `kontrol_*.png`'leri yeniden üretip PAYLAŞIN — bu turda "kod
   doğru ama gerçek belgede hâlâ bozuk" deneyimini bir daha yaşamayalım,
   PNG'lere gözle bakmadan "tamamlandı" demeyin
5. Bunlar bitince AYKOME'nin PDF/e-imza tarafı gerçek anlamda tamamlanmış
   olacak