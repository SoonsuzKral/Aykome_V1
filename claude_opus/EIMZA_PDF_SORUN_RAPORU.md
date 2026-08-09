# AYKOME E-İmza & PDF Bozulma Sorunu — Kök Neden Analizi ve Çözüm Planı

> Bu rapor görsellerden ve konuşma özetinden yapılan bir **mimari teşhistir**. Gerçek
> kodu göremediğim için her hipotez "DOĞRULA" adımıyla birlikte verildi. Claude Code
> gerçek dosyalara erişebildiği için önce Bölüm 2'deki komutları çalıştırıp kaynağı
> kesinleştirmeli, sonra Bölüm 3-5'teki çözümü uygulamalı.

---

## 0. Özet — 3 Belirti

1. **Türkçe karakter bozuluyor**: imzalı PDF'te `İmzalayan: MUSTAFA KEMAL KARATAİž`,
   `Tarih: 09 AAŸustos 2026 17:25` gibi mojibake (bozuk karakter dizisi) çıkıyor.
2. **Başlık + logo kayboluyor, metin sayfa kenarından taşıyor**: imzasız halde
   "ORJİNAL BELGE" başlığı ve kurum logosu var; imzalı halde ikisi de yok, paragraf
   sağ kenardan kesiliyor, sayfa düzeni kayıyor.
3. **"Bu belge Aykome E-İmza ile imzalanmıştır..." kutusu** belgenin yanlış/rastgele
   bir yerinde beliriyor — oysa (belediye müdürüyle görüşmeden gelen bilgiye göre)
   imzalayanın adı zaten şablonda **önceden** yazılı olmalı, imza bunun üstüne
   ayrıca bir şey yazmamalı.

## 1. Tek Cümleyle Kök Neden (hipotez — Bölüm 2 ile doğrulanacak)

İmzalama adımı, dompdf'in ürettiği **temiz** PDF'i alıp üzerine **sonradan**
(muhtemelen pdf-lib ile) bir "damga" metni çiziyor ve PDF'i yeniden kaydediyor.
Bu tek adım üç belirtinin de kaynağı:

- Damga metni **standart bir font** (Helvetica/Times, WinAnsi kodlamalı) ile
  çiziliyor → Türkçe'ye özgü `ğ ş ı İ` karakterlerini o font kodlayamıyor →
  mojibake.
- PDF yeniden kaydedilirken sayfa boyutu / içerik akışı bozuluyor → başlık-logo
  kayboluyor, metin taşıyor.
- Damga, orijinal şablonun düzenini bilmeden **sabit X/Y koordinatına** çiziliyor
  → yanlış yerde beliriyor, üstelik zaten var olan bilgiyi tekrar ediyor.

**İmza (AKİS/PAdES) kendisi kriptografik bir ek olmalı — hiçbir görsel içerik
eklememeli.** Bunu tek cümlede özetlersek: *"İmza dosyaya bir mühür ekler, bir
kalem sokmaz."*

---

## 2. Doğrulama Adımları — ÖNCE bunları çalıştır

Hiçbir kodu değiştirmeden önce, kaynağı kesinleştir. Aşağıdaki 3 alt bölüm
bağımsız çalıştırılabilir.

### 2.1 — Mojibake'in tam olarak nerede üretildiğini bul

```bash
# İki farklı damga metni farklı yerlerde mi tanımlı, aynı yerde mi?
grep -rn "Bu belge Aykome E-İmza ile imzalanmıştır" --include="*.php" --include="*.js" --include="*.ts" --include="*.blade.php" .
grep -rn "5070 sayılı" --include="*.php" --include="*.blade.php" .

# Klasik PHP double-encoding hatası — EN YAYGIN sebep:
grep -rn "utf8_encode\|utf8_decode" app/ --include="*.php"
grep -rn "mb_convert_encoding" app/ --include="*.php"

# pdf-lib standart font kullanımı (Türkçe karakteri kesin kıracak):
grep -rn "StandardFonts\|Helvetica\|Times-Roman" --include="*.js" --include="*.ts" .

# Kaynak dosyanın kendisi yanlış encoding'de kaydedilmiş olabilir:
file -i app/Services/EImzaService.php
# beklenen çıktı: charset=utf-8
# iso-8859-9 / windows-1254 / unknown-8bit çıkarsa dosya YANLIŞ encoding'de
```

**Önemli dallanma:** `grep -rn "Bu belge Aykome E-İmza ile imzalanmıştır"` **hiçbir
sonuç vermezse**, bu metin sizin yazdığınız kodda değil, kullandığınız AKİS/PAdES
imzalama aracının (İmza EXE / üçüncü parti kütüphane) **kendi varsayılan imza
görünümü** demektir. Bu durumda çözüm kod değişikliği değil, o aracın
ayarlarından "görünür imza görünümünü kapat" ya da "özel görünüm şablonu"
seçeneğini bulmaktır — aracın dokümantasyonuna bakılmalı.

### 2.2 — Sayfa boyutu gerçekten değişiyor mu?

```bash
pip install PyMuPDF --break-system-packages
python3 << 'EOF'
import fitz
orig   = fitz.open('imzasiz_orjinal.pdf')
signed = fitz.open('imzali.pdf')

print("Orijinal  sayfa boyutu:", orig[0].rect)
print("İmzalı    sayfa boyutu:", signed[0].rect)
print("A4 olması gereken     : Rect(0, 0, 595.x, 842.x)")
print()
print("Orijinal font sayısı :", len(orig[0].get_fonts()))
print("İmzalı   font sayısı :", len(signed[0].get_fonts()))
print("Orijinal fontlar     :", [f[3] for f in orig[0].get_fonts()])
print("İmzalı   fontlar     :", [f[3] for f in signed[0].get_fonts()])
print()
print("Orijinal metin uzunluğu:", len(orig[0].get_text()))
print("İmzalı   metin uzunluğu:", len(signed[0].get_text()))
EOF
```

- `Page size` farklıysa (örn. A4 595x842 yerine Letter 612x792) → imzalama adımı
  sayfayı yeniden boyutlandırıyor. Bu **tam olarak** "sayfalar kayıyor" belirtisini
  açıklar (A4→Letter yükseklik farkı 842→792 = 50pt, tüm sayfa sonu kırılımları
  kayar).
- Font listesinde `Helvetica` / `Times` görünüyorsa ama orijinalde sadece
  `DejaVuSans` varsa → damga adımı farklı bir font kullanıyor, doğrulandı.

### 2.3 — Tam pipeline akışını haritalayın

```bash
grep -rln "node-signpdf\|pdf-lib\|PDFDocument" --include="*.js" --include="*.ts" .
grep -rln "AKIS\|akis\|PAdES\|pades" --include="*.js" --include="*.ts" --include="*.php" .
```

Şu akışı netleştirin: `dompdf → [PDF bytes]` → **???** → `[İmza EXE / Electron]`
→ `imzali_pdf`. Aradaki `???` adımında pdf-lib ile `drawText` + `save()` çağrısı
var mı? Varsa, o çağrı **imzadan önce mi sonra mı** çalışıyor? (Sonra çalışıyorsa
zaten imzayı geçersiz kılıyor olmalı — bu da ayrı bir ciddi hukuki/teknik sorun.)

---

## 3. Her Belirti İçin Kod Seviyesinde Çözüm

### 3.1 Türkçe karakter bozulması

**Çözüm A — tercih edilen:** damga çizme adımını komple kaldırın (bkz. Bölüm 4).
Bu, aşağıdaki B ve C'yi gereksiz kılar.

**Çözüm B — görsel bir imza kutusu şart koşuluyorsa**, pdf-lib'de Unicode font
gömülmeden Türkçe asla doğru basılmaz:

```javascript
const { PDFDocument, rgb } = require('pdf-lib');
const fontkit = require('@pdf-lib/fontkit');
const fs = require('fs');

async function damgaEkle(pdfBytes, metin) {
  const pdfDoc = await PDFDocument.load(pdfBytes);
  pdfDoc.registerFontkit(fontkit); // ZORUNLU — yoksa embedFont TTF kabul etmez

  // dompdf'in kullandığı AYNI font dosyasını kullanın (tutarlılık için)
  const fontBytes = fs.readFileSync('./fonts/DejaVuSans.ttf');
  const turkceFont = await pdfDoc.embedFont(fontBytes, { subset: true });

  const [sayfa] = pdfDoc.getPages();
  sayfa.drawText(metin, {
    x: 50, y: 40, size: 8,
    font: turkceFont,           // StandardFonts.Helvetica KESİNLİKLE KULLANMAYIN
    color: rgb(0.3, 0.3, 0.3),
  });

  return pdfDoc.save();
}
```

⚠️ **Bu fonksiyon imzadan SONRA çağrılıyorsa imzayı geçersiz kılar.** Sıra her
zaman: içerik tamamla → imzala. Asla: imzala → içerik ekle.

**Çözüm C:** `utf8_encode()` zaten-UTF8 bir string'e uygulanıyorsa (2.1'deki grep
sonucu) o çağrıyı kaldırın — PHP 8.2+'ta zaten deprecated, kaldırılması güvenli.

### 3.2 Başlık/logo kayboluyor, metin taşıyor

**En olası sebep:** önizleme (`preview`) ve imzalanacak sürüm (`renderFor`) FARKLI
Blade view'lar kullanıyor ve ikisi birbirinden ayrışmış:

```php
// KÖTÜ — iki ayrı view, biri diğerinden geride kalmış
public function preview($id) {
    return view('pdf.ruhsat_preview', $data);  // logo + başlık VAR
}
public function renderFor($id) {
    return view('pdf.ruhsat_sign', $data);     // logo + başlık EKSİK ← BUG BURADA
}

// İYİ — TEK view, TEK header partial, sadece mod farklı
public function renderPdf($id, string $mode = 'preview') {
    $data['mode'] = $mode; // 'preview' | 'final'
    return view('pdf.ruhsat', $data);
    // Blade içinde: @include('pdf.partials.header') HER ZAMAN çalışır
}
```

**Logo kaybolması için ayrı ve çok yaygın bir sebep:** logo `<img>` etiketi HTTP(S)
URL kullanıyor ve o özel `renderFor` akışındaki dompdf `Options`'ında
`isRemoteEnabled` kapalı (varsayılan `false`, güvenlik için doğru ama resim
sessizce yüklenmiyor):

```php
// KÖTÜ
// Blade: <img src="{{ url('storage/logo.png') }}">  → HTTP URL, isRemoteEnabled=false ise sessizce başarısız

// İYİ — local dosya yolu veya base64 embed (isRemoteEnabled'a hiç ihtiyaç duymaz)
$logoPath = storage_path('app/public/logo.png');
if (file_exists($logoPath)) {
    $data['logo_src'] = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
} else {
    $data['logo_src'] = null;
    \Log::warning('PDF logo bulunamadı: ' . $logoPath);
}
// Blade: @if($logo_src)<img src="{{ $logo_src }}" style="height:60px">@endif
```

**Metin sağdan taşması için:** 2.2'deki MediaBox karşılaştırması A4≠Letter
farkı gösterirse, imzalama/normalize adımındaki kod bir yerde `PDFDocument.create()`
ile yeni sayfa oluşturuyor veya varsayılan sayfa boyutuna düşüyor olabilir —
grep: `PDFDocument.create\(\)|addPage\(\)` (parametresiz `addPage()` genelde
US Letter varsayılanına düşer, A4 değil).

### 3.3 Damga kutusu yanlış yerde / gereksiz

İsim zaten şablonda var olduğu için (bkz. Bölüm 1) bu kutunun **hiç çizilmemesi
gerekiyor**. Bölüm 4'teki mimariyi uygularsanız bu belirti otomatik ortadan
kalkar — ayrıca bir "doğru konum" bulmaya gerek yok, çünkü zaten doğru konum
şablonun kendisinde.

---

## 4. Önerilen Mimari: Tek-Geçişli Render + Saf Kriptografik İmza

```
ŞU AN (bozuk):
Blade → dompdf → PDF_v1 → pdf-lib(damga çiz, YENİDEN KAYDET) → PDF_v2 → AKİS imzala → PDF_final  ❌

ÖNERİLEN (sağlam):
Blade (tüm imza hücreleri + boş alanlar dahil) → dompdf → PDF_final_content → AKİS imzala
(sadece kriptografik ekleme, sıfır görsel değişiklik) → PDF_final  ✓
```

Bu tek değişiklik üç belirtiyi birden çözer:

| Belirti | Neden çözülür |
|---|---|
| TR karakter bozulması | Tek render zaten DejaVu Sans + doğru UTF-8 kullanıyor (7 noktada test edilmiş, çalışıyor) |
| Logo/başlık kayboluyor | Artık tek şablon var, ayrışacak ikinci bir view yok |
| Metin taşıyor / sayfa kayıyor | İmza adımı içerik akışına dokunmuyor, MediaBox aynı kalıyor |
| Damga yanlış yerde | Damga hiç çizilmiyor — isim zaten doğru hücrede |

**Pratikte değişmesi gereken tek şey:** "Başkan Yardımcısı" gibi *kim imzalarsa o*
tipi alanların adı, dompdf'e gitmeden **önce**, Blade'e geçilen `$data` içinde
doldurulmalı — mevcut `kullanicidanImzalayan()` servisi zaten bunu doğru
yapıyor, sadece çağrıldığı YER değişiyor (imza-sonrası pdf-lib yerine,
render-öncesi Blade controller'ı).

---

## 5. İmza Yerleşim Modülü

İstenen "modül" ekte ayrı dosya olarak var: **`SignerPlacementService.php`**

Bu servis:

- Her belge tipi + o anki süreç adımı için, hangi Blade placeholder'ının kiminle
  (ya da **boş**) doldurulacağını **tek noktadan** yönetir.
- Mevcut `kullanicidanImzalayan()`'ı tekrar yazmaz, **yeniden kullanır**.
- dompdf render'ından **önce** çalışır — post-processing değil.
- Görsel 4'teki 4 adımlı süreçle birebir eşleşir: `buro_personeli`,
  `birim_sefi`, `fen_isleri_muduru`, `baskan_yardimcisi`.
- Hangi adımların **statik** (pozisyon sahibi hep aynı kişi, DB'den lookup) ve
  hangilerinin **dinamik** (kim imzalarsa o, örn. Başkan Yrd.) olduğunu ayrı
  tanımlar — bu ayrımı belediyeyle teyit etmeniz gerekiyor, dosyada `TODO`
  olarak işaretli.

Kullanım noktası (controller içinde, dompdf render'ından hemen önce):

```php
$yerlesim = $signerPlacementService->yerlesimHazirla(
    documentType: 'on_kazi',
    tamamlananAdimlar: $basvuru->tamamlanan_adimlar, // ['buro_personeli', 'birim_sefi']
    suAnkiKullanici: auth()->user(),
);

$data = array_merge($data, $yerlesim);
// $data['imza_fen_isleri_muduru'] artık ya isim ya da '' — Blade'de doğrudan basılır
```

---

## 6. Önemli Tasarım Sorusu — Çok Adımlı Onay Süreci

Görsel 4'teki süreçte 4 farklı rol var (Büro Personeli, Birim Şefi, Fen İşleri
Müdürü, Başkan Yrd.), her biri farklı yetkilerle (Ön Kazı Onayı, Tahakkuk,
Ruhsat İzni...). Burada netleştirilmesi gereken kritik bir nokta var:

**Soru:** Bu 4 adımın **hepsinde** PDF üzerine gerçek bir AKİS/PAdES kriptografik
imzası mı atılıyor, yoksa sadece **belirli adım(lar)** (örn. sadece Fen İşleri
Müdürü ve Başkan Yrd. — Ruhsat İzni yetkisi olanlar) belgeyi kriptografik
imzalıyor da diğerleri (Büro Personeli, Birim Şefi) sistemde yalnızca "onaylandı"
durumu mu bırakıyor?

**Neden önemli:** Bir PDF'e kriptografik imza atıldıktan sonra, o PDF'in içerik
akışı **teknik olarak dondurulur** — sonraki bir adımda sayfaya yeni bir isim
eklemek (form alanı değil, düz metin olarak) önceki imzayı geçersiz kılar ve
herhangi bir PDF görüntüleyicide "imza geçersiz / belge değiştirildi" uyarısı
çıkar. Bu, kamu kurumu için ciddi bir hukuki risktir.

- **Eğer sadece SON adım(lar) gerçek imza atıyorsa** (muhtemel senaryo): sorun
  yok, Bölüm 4'teki mimari doğrudan uygulanabilir — belge her adımda yeniden
  render edilir (isimler o ana kadar tamamlanan adımlara göre dolu/boş), en
  son gerçek imza en son render'ın üzerine atılır.
- **Eğer HER adım gerçek imza atıyorsa**: düz metin yerine PDF **form alanları**
  (AcroForm) kullanılması gerekir — form alanı doldurma, önceki imzaları
  geçersiz kılmadan yapılabilen tek değişikliktir (ISO 32000 / PAdES'in izin
  verdiği "DocMDP seviye 2" değişiklik). Bu, mevcut mimariden daha büyük bir
  değişiklik ister; gerekirse ayrıca ele alınmalı.

Bunu belediye müdürüyle veya kendi süreç dokümantasyonunuzla teyit edip Claude
Code'a GÖREV 1'in bir parçası olarak doğrulatın.

---

## 7. Claude Code'a Görev Talimatı

Aşağıyı olduğu gibi Claude Code'a yapıştırabilirsiniz.

```
GÖREV 1: TANI — HİÇBİR KOD DEĞİŞTİRMEDEN ÖNCE
EIMZA_PDF_SORUN_RAPORU.md dosyasındaki Bölüm 2'deki TÜM komutları çalıştır
(grep'ler + PyMuPDF karşılaştırması + `file -i` encoding kontrolü). Sonuçları
bana özet olarak raporla: (a) damga metni sende mi yoksa üçüncü parti araçta mı
tanımlı, (b) MediaBox imza öncesi/sonrası aynı mı, (c) hangi font(lar)
kullanılıyor, (d) utf8_encode/mb_convert_encoding çağrısı var mı. Bölüm 6'daki
soruyu da (her adımda gerçek imza mı, yoksa sadece belirli adımlarda mı) bana
sor ya da süreç kodundan çıkarabiliyorsan çıkar.

GÖREV 2: TÜRKÇE KARAKTER SORUNUNU KÖKTEN ÇÖZ
GÖREV 1'in bulgusuna göre: (a) damga sizin kodunuzdaysa ve pdf-lib
kullanıyorsa, o çizim adımını KALDIR (GÖREV 4'e bağlı); kaldırılamıyorsa
Bölüm 3.1 Çözüm B'deki fontkit+DejaVu embed düzenini uygula. (b) utf8_encode
çağrısı bulunduysa kaldır. (c) kaynak dosya yanlış encoding'deyse UTF-8
(BOM'suz) olarak yeniden kaydet.

GÖREV 3: BAŞLIK/LOGO/TAŞMA SORUNUNU ÇÖZ
preview ve renderFor (veya eşdeğer) fonksiyonlarının aynı Blade view + aynı
header partial'ı kullandığından emin ol (Bölüm 3.2). Logo <img> kaynağını HTTP
URL yerine base64 data-URI'ye çevir. MediaBox farkı bulunduysa (A4 vs Letter),
imzalama/normalize kodundaki sayfa oluşturma çağrısını (`addPage()`,
`PDFDocument.create()`) bul ve A4 (595x842pt) olarak sabitle ya da orijinal
sayfayı hiç yeniden oluşturmadan kullan.

GÖREV 4: TEK-GEÇİŞLİ MİMARİYİ KUR
Bölüm 4'teki mimariyi uygula: imzalama fonksiyonundan her türlü drawText/damga
çizme kodunu çıkar (üçüncü parti araç varsayılan davranışıysa, o aracın
"görünmez imza" / "görünümü kapat" ayarını bul ve etkinleştir). İmza
fonksiyonu artık sadece PDF byte'larını alıp kriptografik imza ekleyip
döndürsün — hiçbir içerik değişikliği yapmasın.

GÖREV 5: MODÜLÜ ENTEGRE ET + REGRESYON TESTİ
Ekteki SignerPlacementService.php'yi app/Services/'e uyarla (gerçek
EImzaService, User modeli, ve süreç-adımı veri yapınıza göre TODO'ları
doldur). Her belge tipini render eden controller'da, dompdf render'ından hemen
önce bu servisi çağır. Sonrasında ŞUNLARI KIRMADIĞINI doğrula: .no-print CSS
enjeksiyonu (print-bar hala gizli), DejaVu font enjeksiyonu (7 nokta hala
çalışıyor), auth()->user()'dan otomatik isim/unvan çekme (Swal formu hâlâ
kaldırılmış durumda). Bölüm 8'deki test checklist'ini uygula.
```

---

## 8. Test Checklist (düzeltme sonrası)

```python
import fitz
orig   = fitz.open('imzasiz.pdf')
signed = fitz.open('imzali.pdf')

assert orig[0].rect == signed[0].rect, "MediaBox değişmiş!"
assert len(orig) == len(signed), "Sayfa sayısı değişmiş!"

orig_fonts   = {f[3] for f in orig[0].get_fonts()}
signed_fonts = {f[3] for f in signed[0].get_fonts()}
assert signed_fonts.issubset(orig_fonts | {'DejaVuSans'}), \
    f"Beklenmeyen font eklenmiş: {signed_fonts - orig_fonts}"

# Türkçe karakter içeren gerçek bir isimle test edin — "Test Personeli" gibi
# aksansız isimler bu sınıf hataları YAKALAMAZ.
metin = signed[0].get_text()
assert "İmzalayan" in metin
for bozuk in ["Ÿ", "ž", "Ã", "Â"]:  # mojibake tipik izleri
    assert bozuk not in metin, f"Mojibake izi bulundu: {bozuk}"

print("✓ Tüm kontroller geçti")
```

Gerçek test için mutlaka Türkçe karakterli bir isimle (örn. "Ayşe Öztürk Ağır")
imzalayın — "Test Personeli" gibi aksansız isimler bu sınıf hataları yakalamaz
(nitekim önceki tur bu yüzden "✓ çalışıyor" göründü).

---

## 9. Git Commit Mesajı

```
fix: eimza pipeline'ini saf kriptografik imzaya indirgeyerek PDF bozulmasini
onledi - onceden cizilen damga katmani (TR karakter + sayfa kaymasi
kaynagi) kaldirildi, imzalayan bilgisi artik dompdf render oncesi Blade
seviyesinde tek gecis olarak basiliyor
```
