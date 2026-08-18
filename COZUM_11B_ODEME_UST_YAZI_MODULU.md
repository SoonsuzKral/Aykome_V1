# ÇÖZÜM_11B — Ödeme Üst Yazı Modülü + Çoklu-Modül Belge Paketleme

---

## 1. Yeni Modül: Ödeme Üst Yazı

**Sıra:** Üst Yazı → Ön Kazı Onayı → Saha Metraj → **Ödeme Üst Yazı** →
Tahakkuk + Tahsilat Makbuzu → (Ruhsat, Taahhütname)

**Şablon:** Ön Kazı'nın AYNI Word şablon yapısı — kopyalayıp içeriğini
"ödenecek tutar" bilgisini açıklayacak şekilde düzenleyin. Yeni bir editör
mimarisi gerekmiyor, mevcut Word-import/WYSIWYG sistem doğrudan kullanılır.

**İmza Zinciri — önerim:** Ön Kazı'nın SON 2 adımıyla aynı: Fen İşleri
Müdürü E-İmza → Başkan Yardımcısı E-İmza (Büro Personeli/Birim Şefi YOK —
bu belge zaten Saha Metraj onaylandıktan SONRA üretiliyor, ön aşamalar
zaten geçilmiş sayılır). Bu, sizin "belki sadece Başkan Yrd." tereddüdünüze
karşı orta yol — ama kesin karar belediyeyle netleşene kadar İKİ imzalı
başlamanızı öneririm, tek imzaya düşürmek (bir adımı kaldırmak) sonradan
kaldırmaktan çok daha kolay bir değişiklik.

**Nasıl kurulur (bugün, bekleme gerekmez):** Bu, ÇÖZÜM_11C'deki büyük
"Üst Düzey Modül Yönetimi" birleştirmesini BEKLEMEDEN yapılabilir —
mevcut altyapıyla:
1. Modül Yönetimi → "+ Yeni Modül" ile "Ödeme Üst Yazı" modülünü tanımlayın
   (slug: `odeme_ust_yazi` gibi)
2. Süreç ve Onay Rotası'nda YENİ bir adım grubu (ya da mevcut sürece 2 yeni
   adım) ekleyin: "Ödeme Üst Yazı — Müdür E-İmza" ve "Ödeme Üst Yazı —
   Başkan Yrd. E-İmza", "Modül Görünürlüğü"nü `odeme_ust_yazi`'ye
   ayarlayın (Görsel 8'deki mekanizma zaten bunu destekliyor)
3. Modül Yönetimi → Sıralama sekmesinde bu modülü Saha Metraj ile Tahakkuk
   arasına yerleştirin (Görsel 6'daki "Tüm Modül Sıralaması" listesine
   ekleme)

---

## 2. Çoklu-Modül Belge Paketleme — Mimari Öneri

**Problem:** Ödeme Üst Yazı gönderilince, Saha Metraj (yer bilgi formu) ve
Tahakkuk fişi de AYNI paketle gidiyor — ama bu 3 belge sistemde 3 AYRI
modüle ait. Her biri KENDİ modülünde "gönderildi" görünmeli.

**Önerilen çözüm — "bundled_modules" (paketlenmiş modüller) alanı:**

Ödeme Üst Yazı modülünün tanımına yeni bir alan ekleyin:
```php
// migration: modules tablosuna
$table->json('bundled_modules')->nullable();
// Ödeme Üst Yazı için değeri: ["metraj", "tahakkuk"] (slug listesi)
```

**Gönderme anındaki mantık** (Ödeme Üst Yazı'nın "alt kuruma gönder"
aksiyonunu tetikleyen controller fonksiyonunda):
```php
// Ödeme Üst Yazı'yı gönderildi işaretle (mevcut akış aynen kalır)
$odemeModule->markAsSent($application);

// YENİ: bundled_modules'daki her modülü de AYNI ANDA gönderildi işaretle
foreach ($odemeModule->bundled_modules ?? [] as $bundledSlug) {
    $bundledModule = Module::where('slug', $bundledSlug)->first();
    $bundledModule?->markAsSent($application, [
        'sent_via' => 'odeme_ust_yazi_bundle', // denetim izi için
        'bundle_source_module' => 'odeme_ust_yazi',
    ]);
}
```

**Önemli:** Metraj/Tahakkuk'un KENDİ belgeleri (PDF içerikleri) zaten o
modüllerin kendi akışında ÖNCEDEN üretilmiş olmalı (Saha Metraj onaylandı,
Tahakkuk hesaplandı) — burada YENİ bir PDF üretmiyoruz, sadece "gönderildi"
DURUMUNU 3 modülde birden güncelliyoruz. `sent_via`/`bundle_source_module`
gibi bir denetim alanı, birisi ileride "Tahakkuk neden hiç ayrı
gönderilmeden gönderildi görünüyor" diye sorduğunda net bir cevap verir.

**Doğrulama adımı (Claude Code için):** Önce mevcut "gönderme" işleminin
GERÇEKTEN nasıl çalıştığını bulun:
```bash
grep -rn "markAsSent\|gonderildi\|alt.kuruma.gonder\|dispatchToInstitution" app/Http/Controllers/Admin/ --include="*.php"
```
Yukarıdaki `markAsSent()` ismi VARSAYIMSAL — gerçek fonksiyon adı/imzası
farklı olabilir, önce mevcut deseni bulup ona göre entegre edin.

---

## Sıra

1. Önce mevcut "gönderme" mekanizmasını bulun (grep yukarıda)
2. Ödeme Üst Yazı modülünü mevcut altyapıyla (Modül Yönetimi + Süreç Onay
   Rotası) kurun — §1'deki 3 adım
3. `bundled_modules` alanını ekleyip gönderme fonksiyonuna bağlayın — §2
4. Test: Dicle Elektrik test başvurusunda Ödeme Üst Yazı'yı gönderin,
   Metraj VE Tahakkuk modüllerinin KENDİ durum ekranlarında da
   "gönderildi" göründüğünü doğrulayın
