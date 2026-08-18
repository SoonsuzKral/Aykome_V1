# ÇÖZÜM_11A — Küçük/Orta Hatalar

---

## 1. Belge Doğrulama Konumu — Kolay, Görsel Ayar

Görsel 1'de işaretlediğin gibi doğrulama kodu satırı biraz yukarıda, 5070
metni (e-imza sonrası) onun HEMEN üstünde olmalı. Bu, editörde şablonu
açıp doğrulama kodu bloğunu 1-2 satır aşağı, e-imza/5070 metninin
konumlanacağı boşluğu biraz daraltarak elle taşımanız gereken bir düzen
işi — kod değişikliği gerekmiyor, `EImzaService::imzaYasalMetinEkle()`
zaten doğrulama kodu satırının HEMEN üstüne ekliyor (ÇÖZÜM_10 §2'de
doğrulanmıştı), sadece aradaki boşluk fazla.

---

## 2. Boş Alan → Ham "{proje_kodu}" Yazıyor — KESİN SEBEP BULUNDU

**Kanıt:** `DocumentTemplateService::hydrateTemplateTokens()` (satır 955-961):
```php
$val = self::fieldValue($app, $key, $documentType);
if ($val === '') {
    return in_array($key, self::BOS_BIRAKILABILIR_ANAHTARLAR, true) ? '' : $m[0];
}
```
Yani: değer boşsa, SADECE `BOS_BIRAKILABILIR_ANAHTARLAR` listesindeki
key'ler boş basılıyor — listede OLMAYAN her key (örn. `proje_kodu`), boşsa
ham `{proje_kodu}` metnini AYNEN geri basıyor. Bu, editör içi önizlemede
"hangi alan hâlâ boş" diye görmek için bilinçli bir tasarım — ama GERÇEK
görüntüleme/yazdırma çıktısında istenmeyen bir davranış.

**Fix — iki seçenekten birini seçin:**

**Seçenek A (hızlı, hedefli):** `proje_kodu`'yu (ve muhtemelen tüm
Bilgi Alanları key'lerini) `BOS_BIRAKILABILIR_ANAHTARLAR` listesine ekleyin
(satır ~... bu sabitin tanımlandığı yer).

**Seçenek B (daha doğru, önerilen):** Editör kendi önizlemesinde (raw
token görünsün, hangi alan boş anlaşılsın) ayrı bir `hydrate` çağrısı
kullanıyorsa, SADECE gerçek "Görüntüle"/"Yazdır"/"PDF Kaydet" çıktısı için
`hydrateTemplateTokens()`'a bir `$strictEmpty = true` parametresi ekleyip,
o modda TÜM boş key'leri (allowlist'e bakmadan) `''` döndürün. Böylece
editördeki debug-faydası korunur, son kullanıcı hiçbir zaman ham `{...}`
görmez.

```bash
grep -n "BOS_BIRAKILABILIR_ANAHTARLAR\s*=" app/Services/DocumentTemplateService.php
```
ile mevcut listeyi bulup hangi yaklaşımı seçtiğinize karar verin.

---

## 3. EK-1 Adres Sistemi — Zaten Yapılmış, Sadece Doğrulayın

**Kontrol edildi, iyi haber:** İstediğiniz "tek adres direkt yazsın, çok
adres olursa EK-1 olsun" mantığı **zaten tam olarak kodlanmış**:
- `hydrateTemplateTokens()` içinde `{muhtelif_adres_tablosu}` shortcode'u
- Tek/adressiz → sessizce silinir (satır 944-947)
- 6'dan fazla sokak (`MUHTELIF_OVERFLOW_THRESHOLD = 6`, satır 523) →
  gövdede "ADRESLER EK.1 İÇERİSİNDE BULUNMAKTADIR" + `appendEk1Cizelge()`
  ile belge sonuna `page-break-before` ile YENİ bir "EK-1" sayfası eklenir
- 6 veya daha az → tablo doğrudan gövde içine yazılır

**Önemli netleştirme:** "4 sayfa oldu, EK-1 EK-2 EK-3 diye artsın"
isteğiniz için — Türkçe resmi yazışma geleneğinde bir "Ek" tek bir EKİN
kendisidir, o EK kaç FİZİKSEL SAYFA tutarsa tutsun (adres listesi uzunsa
4 sayfa da sürebilir) hep "EK-1" olarak kalır — "EK-2" ancak İKİNCİ, farklı
türde bir ek belge olduğunda kullanılır (örn. Ek-1: Adresler, Ek-2: Kroki).
Şu anki `appendEk1Cizelge()` bunu zaten doğru yapıyor — uzun tablo otomatik
sayfalara bölünür ama tek "EK-1" başlığı altında kalır. **Ek bir iş
GEREKMİYOR**, sadece Görsel 10'daki şablonunuzdaki elle yazılmış statik
"EK1" metnini `{muhtelif_adres_tablosu}` token'ının ürettiği dinamik
metinle uyumlu hale getirin (statik metni silip token'ı doğru yere koyun).

---

## 4. Ruhsat — Taslak "361,00 TL" Kalıntısı

Kod içinde aratıldı, doğrudan `ruhsat.blade.php` veya
`DocumentTemplateService.php` içinde bu tam değer bulunamadı — bu,
`sampleApp()`'ın (önizleme/örnek veri üreten fonksiyon) döndürdüğü örnek
`keşif_bedeli`/`ztb` değerinin GERÇEK bir başvuruya (o başvurunun kendi
verisi eksik olduğunda) fallback olarak sızmış olabileceğini düşündürüyor.
```bash
grep -rn "361" app/Services/DocumentTemplateService.php app/Http/Controllers/Admin/*.php
```
ile tam kaynağı bulup, gerçek başvuru verisi yoksa fallback'in `0,00`'a
(sample veriye değil) düşmesini sağlayın.

---

## 5. Ruhsata Başvuru Bilgileri Eksik Geliyor

Bu daha genel bir bulgu — hangi ALANLARIN eksik geldiğini (örn. adres mi,
kurum adı mı, proje kodu mu) netleştirmeden kesin bir yer gösteremem.
Claude Code'a: ruhsat şablonundaki TÜM `{...}` token'larını
`fieldCatalog()`'daki karşılıklarıyla tek tek eşleştirip, hangilerinin
`fieldValue()`'da eksik/yanlış eşlendiğini bulmasını isteyin — muhtemelen
Madde 2'deki aynı boş-değer sorunuyla karışık algılanıyor olabilir, önce
Madde 2'yi düzeltip tekrar test edin.

---

## 6. Kazı Metraj — Mahalle/Cadde Sütun Ayrımı (hipotez, doğrulanmalı)

Görsel 9'da "MAHALLE" sütununa TÜM adres ("KADIKENDİ, 4151. SK, 41 FG,
63000 ŞANLIURFA M") yazılmış, "CADDE VE SOKAK" sütunu boş kalmış — ama
tablo başlıkları doğru ayrılmış durumda (`metraj.blade.php` satır 97).
**Muhtemel sebep:** bu başvuru, YAPILANDIRILMIŞ "+ Mahalle & Sokak Ekle"
akışı yerine TEK SERBEST METİN "Adres" kutusundan (Görsel 11'de
işaretlediğiniz "buraya yazılan tek adres") girilmiş olabilir — serbest
metin girişi mahalle/cadde olarak ayrıştırılamadığı için ham haliyle
mahalle sütununa düşüyor olabilir.
```bash
grep -n "function.*[Mm]etraj.*[Rr]ow\|kazi_metraj_rows\|buildMetraj" app/Models/Application.php app/Services/DocumentTemplateService.php
```
ile metraj satırlarını üreten fonksiyonu bulup, TEK serbest-metin adresi
mi yoksa YAPILANDIRILMIŞ mahalle/sokak kayıtlarını mı okuduğunu kontrol
edin — muhtemelen serbest metin girişinde mahalle/cadde ayrıştırma hiç
yapılmıyor, bu durumda cadde sütununa boş yerine "-" ya da adresin
tamamını iki sütuna makul şekilde bölen basit bir ayraç eklenebilir.
