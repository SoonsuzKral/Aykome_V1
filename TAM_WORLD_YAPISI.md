# TAM_WORLD_YAPISI — Şablon Yönetimi Yeniden İnşası (Ayrı Proje Planı)

> Bu, ÇÖZÜM_07'deki "Kategori C" bölümünün genişletilmiş, ayrı ve tam
> hâli. E-imza/CSS işleri (ÇÖZÜM_01-08) tamamlandıktan/oturduktan sonra
> kendi başına bir sprint olarak ele alınmalı — günlük görev listesine
> karıştırılmamalı.

---

## 1. İstenen Şey — Kullanıcının Kendi Cümleleriyle

- Kullanıcı kendi PC'sinde Windows Word'de hazır, düzenlenmiş, şık bir
  belge oluşturmuş — içinde `{basvuru_no}` gibi değişken yer tutucular var
- Bu Word dosyasını sisteme yükleyip "bunu ön kazı için kullan" diyebilmeli
- Sistem içinde belgeyi TAM Word deneyimiyle düzenleyebilmeli: logoyu
  fareyle tutup büyütme/küçültme, "İlgi" metnini serbestçe değiştirme,
  "Sayı" alanını taşıma — her yeri istediği gibi
- AYNI yetenek Excel için de olmalı
- TÜM belge tipleri (üst yazı, ön kazı izni, ruhsat, ...) ve TÜM alt
  kurumlar bu düzenleme gücüne sahip olmalı

---

## 2. Şu An Ne Var, Ne Yok (kod incelendi, kesin)

**VAR — üzerine inşa edilebilir:**
- `GlobalDocumentTemplate` → `InstitutionDocumentTemplate` →
  `ApplicationModuleTemplate` üç katmanlı şablon hiyerarşisi (merkez →
  kurum → başvuru-modülü özel override) — bu, "her alt kurum kendi
  şablonunu düzenlesin" isteğinin ALTYAPISI zaten kurulu demek
- `editor_type` alanı (`word` / `excel` / `contenteditable`) — veri
  modeli bu 3 modu ZATEN öngörüyor, yeni migration gerekmeyebilir
- `{değişken}` placeholder sistemi ve "Bilgi Alanları" paneli (Görsel:
  BAŞVURU/KİŞİ/TARİHLER/ALANLAR/İMZA gruplu, tıkla-ekle) — çalışıyor
- `DocumentTemplateService.php` (1774 satır) — render/CSS enjeksiyon
  mantığı zaten sağlam (ÇÖZÜM_01-06'da bizzat düzelttiğimiz kod)

**YOK — sıfırdan yapılacak:**
- `.docx` dosyası yükleme + okuma (composer.json'da hiçbir Word
  kütüphanesi kurulu değil, doğrulandı)
- Gerçek zengin-metin (WYSIWYG) editör — şu an `editor_type=word` seçmek
  sadece bir İKON değiştiriyor, editör deneyimi HTML editor'den farksız
- Excel benzeri hücre/tablo editörü
- "Fareyle sürükle-büyüt" seviyesinde serbest konumlandırma

---

## 3. Gerçekçi Hedef — Neyi "Tam Word" Sayalım

Dürüst olmak gerekirse: **piksel-piksel Word/InDesign seviyesinde
"her şeyi her yere sürükle" bir deneyim, HTML/CSS tabanlı bir web
uygulamasında pratik değil** — bu, Word'ün kendi dosya formatını ve
render motorunu yeniden yazmak demek. Gerçekçi ve GERÇEKTEN kullanışlı
bir hedef:

- Word'den yükle → makul bir HTML'e çevir (biçim %90 korunur, %100 değil)
- Kalın/italik/hizalama/yazı tipi/boyut — tam kontrol
- Resim (logo) — seç, sürükle ile boyutlandır, hizala (bu, modern editör
  kütüphaneleriyle GERÇEKTEN mümkün)
- Metin kutuları/tablolar — ekle, düzenle, taşı (satır/sütun seviyesinde,
  piksel-serbest DEĞİL)
- Değişkenleri ({basvuru_no} gibi) metin içine sürükle-bırak ya da
  tıkla-ekle ile yerleştir

Bu, kullanıcının istediğinin **%85-90'ını** verir, geri kalan %10-15
("Word'ün TAM kendisi") teknik olarak web'de anlamlı bir maliyetle
yapılamaz — bunu baştan netleştirmek, ileride hayal kırıklığını önler.

---

## 4. Aşamalı Plan (sırayla, her biri bağımsız test edilebilir)

### Aşama 1 — Word İçe Aktarma (Backend)
**Kütüphane önerisi:** `phpoffice/phpword` (Composer, aktif geliştirilen,
PHP native — Node.js'e geçiş gerektirmez, Laravel'de doğrudan kullanılır).
`.docx` → HTML dönüşümü yapar. Alternatif: dönüşüm kalitesi yetersiz
kalırsa `mammoth.js` (Node tarafında, e-imza için zaten Node altyapınız
var, bir mikro-servis olarak eklenebilir) — ama önce PhpWord'ü deneyin,
ek karmaşıklık getirmeden.

```bash
composer require phpoffice/phpword
```

Test: birkaç GERÇEK kullanıcı Word dosyası (kullanıcının bahsettiği,
`{basvuru_no}` içeren) yükleyip çıkan HTML'i gözle karşılaştırın.

### Aşama 2 — Zengin Metin Editörü (Frontend)
**Kütüphane önerisi:** `TipTap` (ProseMirror tabanlı, resmi Vue 3
entegrasyonu var — projeniz zaten Vue 3 + Inertia.js kullanıyor, doğal
uyum). Alternatifler: Quill, CKEditor 5 — ama TipTap'in Vue desteği ve
özel "node" (değişken yer tutucu gibi) ekleme esnekliği bu iş için en
uygunu.

```bash
npm install @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-image
```

Bu aşamada: Aşama 1'den gelen HTML, TipTap editörüne yüklenip
düzenlenebilir hale gelir. Kalın/italik/hizalama/resim ekleme çalışır.

### Aşama 3 — Değişken (Placeholder) Entegrasyonu
Mevcut "Bilgi Alanları" panelini TipTap'e bağlayın — bir alana tıklanınca
imleç konumuna `{basvuru_no}` gibi metni ekleyen custom bir TipTap
extension yazın (ya özel renkli/arka planlı bir "chip" görünümüyle, ya da
düz metin olarak — mevcut render mantığınızla (`str_replace` tabanlı)
uyumlu olsun diye düz `{...}` metni önerilir, karmaşıklaştırmayın).

### Aşama 4 — Resim/Logo Sürükle-Boyutlandır
TipTap'in `Image` extension'ı + bir "resizable image" eklentisi (topluluk
paketleri mevcut, örn. `tiptap-extension-resize-image`) ile logo
seç-sürükle-büyüt çalışır hale gelir.

### Aşama 5 — Excel Benzeri Editör (AYRI, SONRAKI aşama — Word ile
paralel başlamayın)
**Kütüphane önerisi:** `x-spreadsheet` (açık kaynak, hafif) ya da daha
güçlü ihtiyaç için `Luckysheet`. Formül desteği GEREKMİYORSA (muhtemelen
gerekmiyor — bunlar veri girişi şablonu, hesap tablosu değil), basit bir
düzenlenebilir HTML `<table>` + hücre birleştirme/genişlik ayarı bile
yeterli olabilir — Luckysheet/x-spreadsheet'in TAM gücüne muhtemelen
gerek yok, önce gerçek ihtiyacı netleştirin.

### Aşama 6 — Kurum Bazlı Yayılım
`InstitutionDocumentTemplate` katmanı zaten var — her alt kurumun kendi
şablonunu Aşama 1-4'teki editörle düzenleyebilmesi, YETKİ kontrolü
(hangi kurum kendi şablonunu görebilir/düzenleyebilir) dışında BÜYÜK bir
ek iş gerektirmemeli, altyapı hazır.

---

## 5. Bilinen, İlişkili Hata — "Osman Zaman" Taşması

Şablon editöründe gördüğünüz "Osman Zaman yazan yerde belgeyi taşırıyor"
hatası, ÇÖZÜM_02/06'da düzelttiğimiz AYNI sınıf CSS taşma sorunu —
muhtemelen editör önizlemesinin kendi CSS'i, asıl PDF render'ının aldığı
`@page`/box-sizing düzeltmelerini almıyor. Aşama 1-2'ye başlamadan önce,
hızlı bir kontrol: editör önizlemesi (`editor.blade.php`) asıl PDF
üretiminde kullanılan (`DocumentTemplateService::pdfCssEnjekte()`) AYNI
CSS enjeksiyonunu kullanıyor mu? Kullanmıyorsa, bu TEK satırlık bir
düzeltme olabilir — büyük yeniden yapıya hiç gerek kalmadan bu spesifik
hata biter.

---

## 6. Önce Karar Verilmesi Gerekenler

1. Excel editörüne GERÇEKTEN formül/hesaplama gerekiyor mu, yoksa sadece
   biçimlendirilmiş bir tablo mu yeterli? (Kütüphane seçimini değiştirir)
2. Word'den içe aktarılan biçim %100 korunmayacak — kullanıcıya bu
   sınırlama nasıl anlatılacak / kabul edilebilir mi?
3. "Fareyle her yere sürükle" beklentisi, Aşama 4'teki (sadece resim
   boyutlandırma) ile mi karşılanacak, yoksa daha fazlası mı bekleniyor?
   Bu netleşmezse geliştirme ortasında kapsam kayması olur.

---

## Sıra

1. Önce §5'teki hızlı CSS kontrolünü yapın (belki tek satır düzeltme)
2. §6'daki kararları netleştirin
3. Aşama 1 (Word içe aktarma) — bağımsız, test edilebilir, ilk somut
   kazanım
4. Aşama 2-4 sırayla, her biri ayrı test/onay ile
5. Aşama 5-6 — Word tarafı oturduktan SONRA
