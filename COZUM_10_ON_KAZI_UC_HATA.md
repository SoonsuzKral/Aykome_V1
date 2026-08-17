# ÇÖZÜM_10 — Ön Kazı: Başkan Yrd. Adı + Doğrulama Kodu + Header Çakışması

> 3 bulgu da kodda doğrulandı, tahmin yok. Dosya/satır referanslı.

---

## 1. Başkan Yardımcısı Adı — Eksik {key}, Kesin Fix

**Kanıt:** `app/Services/DocumentTemplateService.php` içinde `bladeData()`
fonksiyonu (satır ~229-230), `SignerPlacementService::yerlesimHazirla()`'dan
gelen `belediye_baskan_yardimcisi` verisini ZATEN doğru okuyor — AMA bu
sadece ESKİ/legacy `bladeData()` yolu için (admin.pdf.pre_permit blade'i
besleyen). **YENİ Word-import sistemi (`fieldCatalog()`/`fieldValue()`,
satır 665-760) bu key'i hiç içermiyor** — bu yüzden editördeki Bilgi
Alanları panelinde "Başkan Yardımcısı Adı" diye tıklanabilir bir alan hiç
yok, Word şablonuna elle de eklenemiyor.

**Fix — `fieldCatalog()`'a ekleyin (İmza grubuna, ~satır 699 civarı):**
```php
['key' => 'baskan_yardimcisi_adi',   'label' => 'Başkan Yardımcısı Adı',   'tip' => 'text'],
['key' => 'baskan_yardimcisi_unvani','label' => 'Başkan Yardımcısı Unvanı','tip' => 'text'],
```

**`fieldValue()`'a ekleyin (~satır 745 civarı, aynı servisi tekrar kullanarak
— "tamamlanmadıysa boş kalsın" mantığı böylece korunur):**
```php
'baskan_yardimcisi_adi' => $d(
    app(\App\Services\SignerPlacementService::class)
        ->yerlesimHazirla($app, 'pre_permit')['belediye_baskan_yardimcisi']['ad_soyad'] ?? ''
),
'baskan_yardimcisi_unvani' => $d(
    app(\App\Services\SignerPlacementService::class)
        ->yerlesimHazirla($app, 'pre_permit')['belediye_baskan_yardimcisi']['unvan'] ?? ''
),
```

**Sonra kullanıcı tarafı:** editördeki Bilgi Alanları panelinde artık bu
alan görünecek — mevcut Word şablonundaki SABİT "Mustafa Kemal KARATAŞ"
metnini seçip silip, panelden `{baskan_yardimcisi_adi}` token'ını
tıklayarak yerine koymanız gerekiyor (kod bunu otomatik değiştiremez, çünkü
şu an orası düz metin, placeholder değil).

---

## 2. Doğrulama Kodu Yok — Muhtemelen Kod Değil, Şablon İçeriği

**Kanıt:** `{dogrulama_kodu}` key'i `fieldCatalog()`'da zaten VAR (Başvuru
grubunda) ve `fieldValue()`'da da doğru çalışıyor
(`'dogrulama_kodu' => $d($app->verification_code)`). Yani kod tarafı
SAĞLAM — sorun muhtemelen Word şablonunuzun içinde bu token'ın hiç
yazılmamış olması.

**Kesin ayrım testi:**
```bash
php artisan tinker --execute="echo App\Models\GlobalDocumentTemplate::where('document_type','on_kazi')->value('content_data');" | grep -i "dogrulama\|doğrulama"
```
- **Sonuç BOŞSA:** şablonunuzda gerçekten hiç yok — editörde Bilgi
  Alanları'ndan `{dogrulama_kodu}`'nu (5070 metniyle birlikte istediğiniz
  konuma) elle ekleyin, en hızlı çözüm bu.
- **Sonuç DOLU AMA yine de görünmüyor:** o zaman gerçek bir render bug'ı
  var, ayrıca bakılmalı — ama önce yukarıdaki komutu çalıştırıp hangisi
  olduğunu netleştirin.

**Not:** `EImzaService::imzaYasalMetinEkle()` (5070 kırmızı metni ekleyen
fonksiyon) sadece **imza atıldığında** çalışır (`$imzaTarihi === null` ise
hiçbir şey yapmaz) — yani Görsel 3/4'teki "ÖN KAZI İZNİ ONAYI" ekranı
imzalanmamış bir ÖNİZLEME ise, 5070 metninin o an hiç görünmemesi zaten
DOĞRU/beklenen davranış. Asıl kontrol edilmesi gereken, gerçekten
imzalanmış bir ön kazı belgesinde ikisinin de (5070 + doğrulama kodu)
doğru sırada çıkıp çıkmadığı.

---

## 3. Logo/Header Toolbar'a Çakışıyor — KESİN Sebep Bulundu

**Kanıt:** `app/Services/DocumentTemplateService.php` satır 104:
```css
.print-bar { position: fixed; top: 8px; left: 50%; transform: translateX(-50%); ... }
```
`position: fixed` demek, bu araç çubuğunun **belge akışından tamamen
çıktığı, sayfanın en üstünde YÜZDÜĞÜ** demek — kendi yeri için ALTINDAKİ
içeriğe boşluk BIRAKMIYOR. Bu yüzden A4 container'ın en üstündeki logo,
tam bu yüzen barın ALTINDA/İÇİNDE kalıyor — üst üste biniyor.

**Fix (tek satır, güvenli):** `.print-bar` kuralının hemen altına, aynı
`<style>` bloğu içine (satır 104 civarı):
```css
body { padding-top: 64px; }
```
Print-bar'ın gerçek yüksekliği (padding + font + border) yaklaşık 44-50px
+ 8px top offset — 64px güvenli bir pay bırakır, tüm belge tipleri
(cover_letter, on_kazi, ...) için AYNI stil bloğunu paylaştığından bu tek
satır hepsini düzeltir. `@media print` kuralı zaten `.print-bar { display:
none }` yaptığı için, YAZDIRIRKEN bu padding fazladan boşluk BIRAKMAZ (o
kuralda `body`'ye de `padding:0` zorlanıyor mu kontrol edin — satır 114'te
`.a4-container` için var, `body` için de eklemek gerekebilir, aynı yerde).

---

## Sıra

1. Madde 3 (CSS, tek satır) — en hızlı, en kesin, hemen yapın
2. Madde 2'deki tinker komutunu çalıştırıp hangi durumda olduğunuzu görün
3. Madde 1 (2 key ekleme + kullanıcının şablonda elle değiştirmesi)
4. Hepsi bitince aynı "ÖN KAZI İZNİ ONAYI" ekranını tekrar açıp gözle
   doğrulayın — bu sefer gerçekten imzalanmış bir belgeyle test edin ki
   5070 metni de dahil tüm sıralamayı bir arada görün
