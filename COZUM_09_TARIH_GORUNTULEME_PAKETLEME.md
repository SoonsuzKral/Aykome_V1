# ÇÖZÜM_09 — Tarih Alanı + Görüntüleme Bozukluğu + E-İmza Paketleme

> Claude Code (Opus, limitli) için: aşağıdaki 3 madde kesin dosya/satır
> referanslarıyla verildi — keşif gerekmiyor, doğrudan düzeltmeye geçin.

---

## 1. Bilgi Alanları'nda Tarih Eksik — Kesin Konum, Küçük Ekleme

**Dosya:** `app/Services/DocumentTemplateService.php`

`fieldCatalog()` fonksiyonu (satır 665-710), 'Tarihler' grubunda SADECE
`baslangic_tarihi`, `bitis_tarihi`, `olusturulma_tarihi` var — "belgenin
tanzim edildiği/görüntülendiği gün" için bir alan YOK. Ayrıca dikkat: kod
tabanında zaten `TARIH_TOKEN = '{TARIH}'` (satır 514) diye ESKİ/farklı bir
sabit var (`created_at` formatlı) ama bu, YENİ Bilgi Alanları/fieldCatalog
sistemine hiç bağlı değil — karıştırmayın, yeni bir key ekleyin.

**Ekleme (satır 690 civarı, 'Tarihler' grubunun içine):**
```php
'Tarihler' => [
    ['key' => 'baslangic_tarihi', 'label' => 'Başlangıç Tarihi',     'tip' => 'tarih'],
    ['key' => 'bitis_tarihi',     'label' => 'Bitiş Tarihi',         'tip' => 'tarih'],
    ['key' => 'olusturulma_tarihi', 'label' => 'Oluşturulma Tarihi', 'tip' => 'tarih'],
    ['key' => 'belge_tarihi',     'label' => 'Belge Tarihi',         'tip' => 'tarih'], // YENİ
],
```

**`fieldValue()` fonksiyonuna karşılık gelen case (satır 743 civarına):**
```php
'belge_tarihi' => $d($app->created_at?->format('d.m.Y')),
```

**Karar noktası (siz seçin):** `created_at` kullanırsam tarih SABİT kalır
(belge ilk oluştuğunda ne ise hep o). Eğer "her görüntülemede/yazdırmada
BUGÜNÜN tarihi" isteniyorsa, `$app->created_at` yerine `now()` kullanın —
ama bu, aynı belgeyi 2 gün sonra tekrar açtığınızda FARKLI tarih gösterir.
Ruhsat/tahakkuk'taki "Tanzim Tarihi" ile tutarlı olması için `created_at`
öneriyorum, ama siz karar verin.

---

## 2. "Ön Kazı Görüntüle" Bozuk Geliyor — Kesin Şüpheli

**Bulgu:** Editördeki "Taşı Modu" (görsel 1'de toolbar'da görünen), sürüklenen
elemanları `position:absolute` + piksel koordinatıyla kaydediyor (bkz.
`editor.blade.php` satır 69, 115 civarı yorumlar — "px koordinatı CSS'e göre
konteynerin..."). Bu koordinatlar, EDİTÖRÜN kendi önizleme konteyner
genişliğine göre doğru. `DocumentTemplateService.php`'deki yorumlarda
("16.08 5-16. tur") Word-içe-aktarma sırasındaki OTOMATİK absolute-konum
enjeksiyonunun tam da bu sebeple ("metinle çakışma") KALDIRILDIĞI yazıyor —
ama bu, sadece OTOMATİK enjeksiyonu kapsıyor, kullanıcının "Taşı Modu" ile
ELLE sürüklediği elemanları kapsamıyor.

**`ApplicationsController::downloadPrePermit()` (satır 1213) incelendi:**
Eğer `on_kazi` için özel bir şablon varsa (sizin durumunuz — Word'den
içe aktardığınız için VAR), fonksiyon `DocumentTemplateService::renderFor(
'on_kazi', $application)` çağırıp SONUCU `lockForAltKurum()`'dan geçirip
DOĞRUDAN döndürüyor — editör önizlemesinin sardığı özel A4-konteyner CSS'ini
KULLANMIYOR OLABİLİR.

**Kesin doğrulama adımı (deneme-yanılma yerine):**
```bash
grep -n "class=\"a4\|width:\s*210mm\|max-width" resources/views/admin/document-templates/editor.blade.php | head -5
grep -n "renderFor\s*(" app/Services/DocumentTemplateService.php | head -5
```
Editörün önizleme konteynerinin genişlik/padding değerleriyle,
`renderFor()`'un ürettiği/sardığı konteynerin değerlerini KARŞILAŞTIRIN.
Farklıysa (yüksek ihtimalle farklı), **aynı A4-konteyner CSS class'ını**
(genişlik, padding, font-size) her iki yerde de kullanacak şekilde
birleştirin — muhtemelen `renderFor()`'un çıktısını sarmalayan bir
`<div class="...">` eksik ya da editörünkinden farklı.

**Daha kalıcı, önerilen yön:** "Taşı Modu"nun ürettiği `position:absolute`
piksel-koordinat yaklaşımı KIRILGAN — editör, görüntüleme sayfası, VE
ilerideki dompdf/PDF çıktısı olmak üzere 3 farklı bağlamda AYNI davranması
gerekiyor. Orta-uzun vadede: sürüklenen elemanların konumunu piksel yerine
YÜZDE (%) ya da konteyner-göreli birim (örn. `left: 12%` değil `left: 45px`)
olarak kaydetmek, bağlamlar arası tutarlılığı garantiler. Bugün için önce
konteyner CSS'ini eşitleyin (hızlı), bu kalıcı çözümü ayrı not edin.

---

## 3. E-İmza Paketleme — İYİ HABER: Zaten Hazır, Hiç Çalıştırılmamış

`aykome-e-imza/package.json` incelendi — **electron-builder TAM
yapılandırılmış:**
- `assets/icon.ico` mevcut (özel logo zaten var)
- NSIS kurulum ayarları TAM: masaüstü kısayolu, başlat menüsü kısayolu,
  kurulum sonrası otomatik başlatma (`autoLaunch: true`), özel kurulum
  ikonu — hepsi "Eyyübiye AYKOME E-İmza" adıyla
- `main.js`'de **Tray (sistem tepsisi) kodu TAM YAZILMIŞ** (satır 1, 19,
  225-240) — `createTray()`, tooltip "Eyyübiye AYKOME - E-İmza Köprüsü",
  çift-tıkla açılan kurulum penceresi. Yani "Electron JS görünmesin, arka
  planda çalışsın" istediğiniz davranış ZATEN KODLANMIŞ.

**Görsel 3'teki çıplak Electron ekranının sebebi:** muhtemelen sadece
`npm start` (geliştirme modu, `electron .`) ile çalıştırılmış — hiç `npm
run build:win` çalıştırılmamış. Yapılacak:

```bash
cd aykome-e-imza
npm run build:win
```

Bu, `dist/` klasörüne gerçek, markalı, tray-destekli bir `.exe` kurulum
dosyası üretecek — sıfırdan kod yazmaya gerek YOK, sadece ÇALIŞTIRIN ve
çıkan `.exe`'yi test edin.

**Eksik olan TEK gerçek şey — otomatik güncelleme:** `electron-updater`
paketi kurulu değil (kontrol edildi, yok). Eklenmesi gerekiyor:
```bash
npm install electron-updater
```
`main.js`'e (mevcut `createTray()` fonksiyonunun yanına) update-kontrol
mantığı eklenmeli — GitHub Releases, ya da kendi sunucunuzda basit bir
`latest.yml` + dosya barındırma (electron-builder bu formatı otomatik
üretir, `publish` config'i eklemeniz yeterli).

**Admin panel "E-İmza İndir" + yükleme modülü:** Bunun için:
1. Admin panelde bir route + basit bir dosya yükleme formu (yeni `.exe`
   yüklenince `storage/app/public/downloads/` altına kaydedilir, eski
   sürüm üzerine yazılır)
2. Header'a "⬇️ E-İmza İndir" butonu, bu dosyaya `Content-Disposition:
   attachment` ile bağlanır
3. Auto-update için: `electron-builder`'ın `publish` config'ini bu AYNI
   `storage/app/public/downloads/` klasörüne (ya da bir S3/genel URL'e)
   işaret edecek şekilde ayarlayın — `electron-updater` her açılışta
   oradaki `latest.yml`'i kontrol edip yeni sürüm varsa indirir.

---

## Sıra (bütçeye göre öncelik)

1. **Madde 1** (tarih alanı) — en hızlı, en kesin, hemen yapın
2. **Madde 3'ün ilk kısmı** (`npm run build:win` çalıştırıp test etmek) —
   kod yazmıyorsunuz, sadece build alıp deniyorsunuz, çok hızlı olmalı
3. **Madde 2** (görüntüleme bozukluğu) — biraz araştırma gerektiriyor ama
   yönü net, konteyner CSS karşılaştırmasından başlayın
4. **Madde 3'ün geri kalanı** (electron-updater + admin panel modülü) —
   en büyük iş, zaman kalırsa veya ayrı bir turda
