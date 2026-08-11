# ÇÖZÜM_02 — İnce Ayar: Kenar Boşlukları + Cover Letter Logosu

> ÇÖZÜM_01.md'nin "§6 KALAN İŞLER" bölümünün devamı. 7 adımlık ana plan
> tamamlandı (sayfa taşması + Türkçe karakter + 5070 metni + inline
> görüntüleme hepsi çözüldü, 7/7 e2e PASS). Bu dosya sadece 2 görsel ince
> ayar için — kullanıcının paylaştığı 5 ekran görüntüsüne dayanıyor.

---

## Not — OpenCode'a (Claude'dan)

ÇÖZÜM_01.md'yi okudum — `a4-landscape-container` substring bug'ını, XPath'in
`Ğ`'yi büyük harfte tanımadığını, dompdf'in box-sizing'i hiç uygulamadığını
bulmanız gerçek kök-neden avcılığı, yüzeysel yama değil. 7/7 gerçek token
e2e testi geçmiş. Bu 2 kalan iş için de aynı disiplini koruyun — "biraz daha
padding ekle, dene" moduna geçmeyin, ölçüp öyle değiştirin.

Birkaç ek nokta:

- **Önce SORUN B (logo), sonra SORUN A (boşluklar).** Logo bağımsız ve
  hızlı bir düzeltme; boşluk ayarı kademeli/tekrar-test gerektiriyor.
- **`a4ContainerInlineWidth()` PAYLAŞILAN bir fonksiyon** — ÇÖZÜM_01'e göre
  ruhsat, pre_permit, tahakkuk gibi birden fazla belge tipi aynı portrait
  170mm değerini kullanıyor. Bu değeri artırırsanız hepsi aynı anda
  etkilenir. Her ayardan sonra sadece ruhsat'ı değil, **7 belge tipinin
  tamamının** `test_verify_all.py` çıktısını kontrol edin — ruhsat'taki
  boşluğu kapatırken payı daha az olan başka bir tipte (örn. metraj) yeni
  bir taşma açabilirsiniz.
- **Bazı tipler paylaşılan değeri EZİYOR** — cover_letter kendi 168mm'sini,
  taahhutname kendi madde-list ayarlarını kullanıyor (ÇÖZÜM_01 §3). Boşluk
  sorununun paylaşılan fonksiyonda mı yoksa bir tipin kendi override'ında mı
  olduğunu önce tespit edip DOĞRU katmanı düzeltin.
- Bu dosyadaki mm hesapları ÇÖZÜM_01'de yazılı değerlere (@page 6mm, padding
  12mm) dayanıyor — dün geceden bugüne bunlar değişmiş olabilir, kör
  güvenmeyin, önce `grep` ile güncel değerleri doğrulayın.
- **Regresyon çizgisi:** bu 2 düzeltmeden sonra mevcut 7/7 PASS **7/7 PASS
  kalmalı**. Herhangi biri bozulursa o değişiklik geri alınır, ilerlenmez.
- Yeni eklediğiniz kontrolleri (resim varlığı, mm ölçümü) tek seferlik
  script olarak bırakmayın — `test_verify_all.py`'a kalıcı olarak ekleyin,
  böylece bu bug sınıfı ileride sessizce geri gelmez.

---

## SORUN A — Kenar/Alt Boşluklar

**Kanıt:** ruhsat'ta sol+sağ kenarda ince boşluk şeridi (kullanıcı ekran
görüntüsü, oklarla işaretli); tahakkuk'ta sayfanın alt ~%30'u boş kalıyor.

**Neden:** ÇÖZÜM_01 §2.1 fix'i (`a4ContainerInlineWidth`: portrait `170mm`)
ve §2.5 fix'i (font 10.5px, td/th padding 1px 3px, line-height 1.15) taşma
sorununu çözmek için BİLEREK güvenli/dar tarafta kaldı. Artık kök nedenler
(box-sizing farkındalığı, guard bug, @page margin) düzeldiği için bu payı
gevşetmek mümkün — ama önce ÖLÇÜN, sonra ayarlayın. Körlemesine büyütmek
taşmayı geri getirebilir.

### Adım 1 — Ölç (tahmin etme, gerçek sayıları al)

`test_verify_all.py`'a şu fonksiyonu ekleyin (veya ayrı çalıştırın):

```python
import fitz

MM = 2.834645669  # 1mm kaç pt

def olcum_raporu(pdf_yolu, sayfa_no=0):
    doc = fitz.open(pdf_yolu)
    page = doc[sayfa_no]
    pw, ph = page.rect.width, page.rect.height

    min_x, min_y, max_x, max_y = pw, ph, 0, 0
    for block in page.get_text("dict")["blocks"]:
        bbox = block.get("bbox")
        if bbox and (bbox[2] > bbox[0]):  # boş blokları atla
            min_x = min(min_x, bbox[0])
            min_y = min(min_y, bbox[1])
            max_x = max(max_x, bbox[2])
            max_y = max(max_y, bbox[3])

    print(f"\n=== {pdf_yolu} (sayfa {sayfa_no+1}) ===")
    print(f"Sayfa boyutu   : {pw/MM:.1f} x {ph/MM:.1f} mm")
    print(f"Sol boşluk     : {min_x/MM:.1f} mm")
    print(f"Sağ boşluk     : {(pw-max_x)/MM:.1f} mm")
    print(f"Üst boşluk     : {min_y/MM:.1f} mm")
    print(f"Alt boşluk     : {(ph-max_y)/MM:.1f} mm")
    print(f"Resim sayısı   : {len(page.get_images())}")

olcum_raporu('storage/app/test_ruhsat_5070.pdf')
olcum_raporu('storage/app/test_tahakkuk_5070.pdf')
olcum_raporu('storage/app/test_cover_letter_5070.pdf')
```

Bu size TAM mm cinsinden hangi kenarda kaç mm fazladan boşluk olduğunu
verir. Aşağıdaki adımlar bu sayılara göre yapılmalı.

### Adım 2 — Yatay boşluk (ruhsat, kenar şeritleri)

Şu anki `@page margin` değerini doğrulayın:
```bash
grep -n "@page" app/Services/EImzaService.php
```

Formül: `doğru_container_genişliği = (210mm - 2×@page_margin) - (sol_padding + sağ_padding)`

Örnek: @page margin hâlâ 6mm, padding hâlâ 12mm+12mm ise →
`doğru_genişlik = (210-12) - 24 = 174mm` (şu anki 170mm yerine).

`a4ContainerInlineWidth()`'teki portrait değerini Adım 1'de ölçülen gerçek
boşluğa göre **kademeli** artırın (örn. 170→172mm), her adımda:
```bash
php test_pdf_generate.php   # PDF'leri yeniden üret
python3 test_verify_all.py  # taşma yok mu, hâlâ 1 sayfa mı kontrol et
```
Taşma başlarsa bir önceki değere dönün. Landscape (`245mm`) için de aynı
mantık, metraj PDF'i üzerinden.

### Adım 3 — Dikey boşluk (tahakkuk, alt kısım boş)

Tablo yüksekliği satır sayısı × satır yüksekliğiyle sabit — mevcut küçük
font/padding'le tablo doğal olarak sayfadan kısa kalıyor. `.a4-container
td,th { padding:1px 3px; font-size:10.5px }` değerlerini kademeli artırın
(örn. padding 2px 4px, font-size 11px), her adımda Adım 2'deki gibi
üret+doğrula döngüsünü çalıştırın. Hedef: 1 sayfada kalırken tablo sayfanın
büyük kısmını doğal olarak doldursun — pikselde piksel dolum ZORUNLU değil,
mevcut aşırı boşluğun görsel olarak azalması yeterli.

---

## SORUN B — Cover Letter (Üst Yazı) Logosu İmzalı Halde Kayboluyor

**Kanıt:** İmza öncesi (kullanıcının 5. görseli) logo yerinde. İmza sonrası
gerçek PDF'te (3. görsel) logo tamamen yok, sadece metin var.

**Neden:** imza öncesi görünüm muhtemelen tarayıcıda HTML önizleme (remote
resmi sorunsuz çeker). Sonraki, gerçek dompdf render'ı — dompdf
`enable_remote=false` olduğu için remote/relative URL resimleri SESSİZCE
YÜKLEMEZ (ÇÖZÜM_01 §2.6, tam bu yüzden pre_permit'in belediye logosu için
base64'e çevrilmişti). cover_letter'ın KENDİ logosu (başvuran şirketin,
örn. Dicle Elektrik) — pre_permit'ten FARKLI bir kaynaktan geliyor
(muhtemelen başvuru/şirket kaydından, belediye ayarından değil) — aynı
base64 dönüşümünü almamış görünüyor.

### Doğrula

```bash
# cover_letter'ın logo kaynağı şu an nasıl render ediliyor?
grep -n "logo" resources/views/admin/pdf/cover_letter.blade.php

# logo_base64 cover_letter'ı üreten HANGİ fonksiyon(lar)a geçiyor?
grep -n "logo_base64\|cover_letter" app/Services/EImzaService.php app/Http/Controllers/Admin/ApplicationsController.php
```

Beklenen bulgu: `cover_letter.blade.php` içinde hâlâ
`<img src="{{ url('storage/...') }}">` gibi bir HTTP/relative URL var, base64
data-URI değil — ya da `logo_base64` değişkeni bu belge tipi için hiç
hesaplanmıyor/geçirilmiyor.

### Çözül

Şirketin (başvuru sahibinin) logosunu bulan gerçek alanı/tabloyu kod
tabanında bulun (muhtemelen `application->company` veya benzeri bir ilişki),
sonra pre_permit'teki AYNI base64 deseni:

```php
// EImzaService.php — cover_letter verisini hazırlarken:
$logoYolu = $application->company->logo_path ?? null; // GERÇEK alan adını doğrulayın

if ($logoYolu) {
    $tamYol = storage_path('app/public/' . ltrim($logoYolu, '/'));
    if (file_exists($tamYol)) {
        $mime = mime_content_type($tamYol) ?: 'image/png';
        $data['logo_base64'] = "data:{$mime};base64," . base64_encode(file_get_contents($tamYol));
    }
}
$data['logo_base64'] ??= null;
```

```blade
{{-- cover_letter.blade.php --}}
@if(!empty($logo_base64))
    <img src="{{ $logo_base64 }}" style="height:50px">
@else
    {{-- şirket adı zaten metin olarak başlıkta var, sessizce atla --}}
@endif
```

**Kritik:** bu `$data['logo_base64']` hesaplaması, hem önizleme hem
**imzalanacak sürümü üreten fonksiyonun HER İKİSİNDE** de çalışmalı — sadece
birinde ise aynı "iki ayrı yol birbirinden ayrıştı" hatası tekrar eder.
`grep -rn "view('admin.pdf.cover_letter'" app/` ile bu view'ı çağıran TÜM
yerleri bulup her birine bu veriyi geçirdiğinizden emin olun.

### Doğrulama testine ekleyin

```python
img_list = fitz.open('storage/app/test_cover_letter_5070.pdf')[0].get_images()
assert len(img_list) > 0, "cover_letter'da hâlâ resim/logo yok!"
```

---

## Sıra

1. Ölçüm script'ini (Adım 1) çalıştır, gerçek sayıları al
2. SORUN B (logo) — bağımsız, kısa, hemen düzeltilebilir
3. SORUN A — kademeli artır + her adımda `test_pdf_generate.php` +
   `test_verify_all.py` (artı yukarıdaki resim-varlığı kontrolü) ile doğrula
4. `e2e_sign.cjs` ile gerçek token imzası, `verify_signed.py` ile son kontrol
5. `kontrol_*.png` üret, gözle bak, sonra "tamam" de

---

## ✅ UYGULANDI (11 Ağustos gece — OpenCode)

Sıradaki TÜM adımlar tamamlandı ve doğrulandı:

1. **Ölçüm (Adım 1):** 7 belge ölçüldü → ruhsat 20.0/23.8 (sol/sağ), tahakkuk alt 71.6mm,
   cover_letter **0 resim**, metraj 27.6/30.1 (landscape, kullanıcı onaylı → değişmedi).
2. **SORUN B (logo):** `EImzaService::pdfOlustur` şablon yoluna enjeksiyon eklendi
   (`downloadCoverLetter` deseni + yeni `institutionLogoBase64()` helper; blade yolu da
   aynı helper'a bağlandı — "iki yol ayrışması" hatası bir daha olamaz).
3. **SORUN A (yatay):** `a4ContainerInlineWidth` portrait **170→174mm** + portrait tablolar
   `%98.5→%100` (landscape `%98.5` + 245mm korundu). Sonuç: ruhsat 18.0/19.3,
   tahakkuk 19.1/19.0, pre_permit 18.0/17.4, taahhutname 18.0/16.7.
4. **SORUN A (dikey/tahakkuk):** `pdfTipineGoreEkCss` tahakkuk dalı (11px + td/th padding
   2×4px) → alt boşluk 71.6→**26.7mm**, ruhsat (14.1mm) etkilenmedi.
5. **Kalıcı doğrulama:** `test_verify_all.py`'a logo assert'i eklendi
   (cover_letter/pre_permit ≥1 çizilmiş resim — `get_image_info`).
6. **E2E:** `php test_pdf_generate.php` → **7/7 PASS**; `node e2e_sign.cjs` → 7/7 gerçek
   token imzası; `python verify_signed.py` → **TÜM İMZALI PDFLER TEMIZ**; imzalı
   cover_letter 1 sayfa + logo korundu. `kontrol_*.png` yeniden üretildi.
7. **Git:** Tüm iş `main`'de, hem `origin` (GitLab) hem `github` push edildi; diğer
   makinenin geçmişi `other-machine` dalına yedeklendi.

**Kalan:** tarayıcı/Elektron E2E + kullanıcının görsel onayı (`kontrol_*.png`).

## Not — sonraki tur için (şimdi değil)

Admin panel header'ına e-imza masaüstü uygulaması indirme linki eklenmesi —
bu ayrı, küçük bir görev, PDF görsel ince ayarı bitince ele alınmalı.
