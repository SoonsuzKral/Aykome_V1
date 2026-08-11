# E-İMZA PDF SORUNLARI — DURUM RAPORU (11 Ağustos 2026)

> Bu dosya, e-imza PDF sorunları üzerinde yapılan **TÜM çalışmayı**, teşhisleri,
> uygulanan çözümleri, doğrulama sonuçlarını ve **KALAN İŞLERİ** tek yerde toplar.
> Kaynak plan: `e_imza_sorun/TAM_COZUM_PLANI.md`. Bu rapor, o plan dosyalarının
> "_şu ana kadar ne oldu_" tarafıdır.

---

## 1. ORİJİNAL SORUNLAR (plan dosyalarından)

Plan (`TAM_COZUM_PLANI.md`, `GOREV_6_DEVAM_PROMPTU.md`, `GOREV_9_ACIL_ELECTRON_VE_5070.md`)
5 ana belirti listeliyordu:

| # | Belirti | Kaynak dosya |
|---|---|---|
| 1 | Damga metninde Türkçe mojibake (`KARATAİž`, `AAŸustos`) | `EIMZA_PDF_SORUN_RAPORU.md` §3.1 |
| 2 | Ruhsat 2 sayfaya taşıyor (tablo sağdan + GENEL TOPLAM kesiliyor) | `GOREV_6` §GÖREV 6 |
| 3 | "5070 sayılı..." yasal metni kayıp | `GOREV_6` §GÖREV 7, `GOREV_9` §GÖREV B |
| 4 | "Görüntüle" tıklayınca indiriyor, tarayıcıda açmıyor | `GOREV_9` §GÖREV A |
| 5 | "Gereksiz kopyalama" adımı ihtimali | `TAM_COZUM_PLANI` §0 |

---

## 2. MİMARİ ANATOMİ (kod tabanından öğrenilenler)

### 2.1 — Hangi dosyalar ne yapıyor

| Dosya | Rol |
|---|---|
| `app/Services/EImzaService.php` | E-imza akışı: `baslat()` → `pdfOlustur()` (PDF üretir) → `tamamla()` (imzalıyı kaydeder). **5070 enjeksiyonu buraya eklendi.** |
| `app/Http/Controllers/Api/EImzaController.php` | API: `baslat` / `pdf` / `tamamla` / `indir` / `durum`. "Görüntüle" **zaten** `response()->file()` + `inline` (sorun 4 çözülmüş halde). |
| `app/Services/DocumentTemplateService.php` | Şablon motoru: `pdfCssEnjekte()` (evrensel CSS), `a4ContainerInlineWidth()` (container genişliği), `renderFor()`. **Taşma çözümünün merkezi.** |
| `app/Services/DocumentRenderer.php` | Statik HTML şablonları (dompdf klasörü). E-imza akışında dolaylı. |
| `resources/views/admin/pdf/*.blade.php` | PDF blade'leri (ruhsat, pre_permit, cover_letter, tahakkuk, metraj, tahsilat_makbuzu). |
| `aykome-e-imza/` | Electron masaüstü uygulaması: `src/pades/sign-pdf.js` (PAdES), `src/pkcs11/signer.js` (token). **Zaten temiz** — invisible PAdES, görsel müdahale yok. |
| `claude_opus/` | Önceki Sonnet/Opus raporları (referans). |

### 2.2 — KULLANILAN KOD YOLLARI

```
E-imza akışı:
  UI "E-İmza ile İmzala" → POST /api/e-imza/baslat
    → EImzaService::baslat()
      → pdfOlustur(application, pdfType, imzaDamgasi, imzaTarihi)
          ├─ renderFor() şablonu varsa (DB override→global) → ŞABLON YOLU
          └─ yoksa blade view → BLADE YOLU
      → 5070 metni imzaYasalMetinEkle() (render öncesi, tek geçişli)
      → Pdf::loadHTML() → dompdf → orijinal.pdf (storage/app/public/e-imza/txn_...)
    → Electron: PDF'i indirir (api/e-imza/pdf/{tid}?token=), PAdES imzalar, geri yükler
    → EImzaService::tamamla() → imzali.pdf kaydedilir
```

**Uyarı (blade vs şablon):** 6 belge tipinin hangisi hangi yoldan geçiyor
test sonucu (`php test_renderfor.php`):

| Tip | Yol |
|---|---|
| ruhsat | BLADE (şablon yok) |
| pre_permit / on_kazi | BLADE |
| cover_letter | **ŞABLON** (kendi CSS'i var) |
| tahakkuk | BLADE |
| metraj | BLADE |
| makbuz | BLADE |
| taahhutname | BLADE |

---

## 3. YAPILAN DEĞİŞİKLİKLER (uygulandı, doğrulandı)

### ✅ A. 5070 yasal metni — `app/Services/EImzaService.php` (FINAL: Türkçe Ğ bug'ı çözüldü)

- `baslat()` artık `$imzaTarihi = now()` sabitliyor ve `pdfOlustur(..., $imzaTarihi)`'ye geçiyor.
- `pdfOlustur()` imzasına `?\DateTimeInterface $imzaTarihi = null` + `?string $pdfType = null` eklendi
  (BC: null ise enjeksiyon yok → normal önizleme/indirme etkilenmez).
- Yeni metod: `imzaYasalMetinEkle(string $html, ?DateTimeInterface $imzaTarihi, ?string $pdfType): string`
  - **Grup A** (ruhsat, pre_permit, cover_letter): BELGE DOĞRULAMA KODU satırının **ÜSTÜNE**,
    aynı font boyutu (font-size verilmez → inherit; squeeze sonrası 10.5px).
  - **Grup B** (metraj, makbuz, tahakkuk, taahhutname): belgenin **EN ALTINA** (container sonu → footer-note
    sonrası → body sonu sırasıyla).
  - **Kritik XPath bug'ı:** `translate()` sadece ASCII küçültür → "DOĞRULAMA"daki Ğ büyük kalır, eşleşme HİÇ
    olmazdı (5070 hep fallback'e düşüyordu). Çözüm: XPath hızlı filtre
    `//*[contains(.,"DOĞRULAMA") or contains(.,"doğrulama") or contains(.,"Doğrulama")]` + PHP
    `mb_stripos($el->textContent,'belge doğrulama kodu')`; son (en derin) eşleşme seçilir.
  - Metin: *"Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan belgenin {d.m.Y H:i} tarihli
    kağıt kopyasıdır. Bu belge güvenli elektronik imza ile imzalanmıştır."* — `color:#c0392b`,
    `text-align:center`, `font-weight:bold`, `margin:6px 0 0`, `line-height:1.15` (font-size YOK).
  - `coverLetterSabitle()` yerine **`pdfTipineGoreEkCss($html, $pdfType)`** — hem şablon hem blade yolunda
    `pdfCssEnjekte → pdfTipineGoreEkCss → imzaYasalMetinEkle` sırasıyla çağrılır.
  - Taahhutname ek CSS: madde-list p 9px/lh 1.25/mb 3px, beyan/not lh 1.3, imza-alani mt 12pt,
    imza-cizgi mt 16pt.
- **Logo:** `pdfOlustur` artık cover_letter + pre_permit için `logo_base64` üretir;
  `pre_permit.blade.php` remote URL yerine base64 + fallback metin kullanır (dompdf `isRemoteEnabled=false`
  → remote logo hiç yüklenmiyordu).

### ✅ B. Sayfa taşması — `app/Services/DocumentTemplateService.php` (FINAL: tümü çözüldü)

#### Kök neden 1 — dompdf `@media print` UYGULAMAZ
- Blade'ler `.a4-container`'ı ekranda tek A4 gibi sınırlar (`width:210mm; min-height:297mm`) ve `@media print`'te
  `width:100%` yapar. dompdf `@media print`'i çalıştırmaz → container ekrandaki 210mm sabitinde kalır,
  sayfa iç alanından (~198mm) sağa taşar.
- **Çözüm:** `pdfCssEnjekte()` başta `a4ContainerInlineWidth()` çağırır (inline style ile deterministik genişlik).

#### Kök neden 2 (FINAL) — dompdf box-sizing UYGULAMAZ + guard bug'ı
- dompdf `box-sizing`'ı görmezden gelir → `width + padding` toplamı sayfayı taşırıyordu (190mm+20mm padding ≈ taşma).
- **Guard bug'ı:** `str_contains($html,'a4-container')` — `'a4-landscape-container'` bu alt dizgeyi İÇERMİYOR
  → metraj'a inline genişlik HİÇ uygulanmıyordu (2 sayfa + taşmanın gerçek nedeni).
- **Çözüm:** guard iki sınıfı da arıyor; portrait `170mm`, landscape `245mm`; `min-height: auto → 0`.
  squeeze'deki `width:100% !important` KALDIRILDI (inline mm tek genişlik kaynağı).

#### Kök neden 3 — YÜKSEKLİK taşması (ruhsat 3 sayfaydı)
- DejaVu Sans Arial'dan ~%20 daha geniş; yoğun tablolar (ruhsat/tahakkuk) tek sayfaya sığmıyordu.
- **Çözüm:** `pdfCssEnjekte()` squeeze CSS + `@page { margin: 6mm !important; }` (blade'lerdeki geçersiz
  `@media print @page` kurallarını ezer → iç alan portrait 198×285mm, landscape 285×198mm):
  ```css
  .a4-container, .a4-landscape-container { font-size:10.5px; line-height:1.15; min-height:0 !important;
      padding:8mm 12mm; position:relative }
  .a4-container table { width:98.5% !important; border-collapse:collapse !important }
  .a4-container td,th { padding:1px 3px; font-size:10.5px }
  ```

#### Kök neden 4 — cover_letter footer sayfa 2'ye düşüyordu (dompdf `bottom`'ı yok sayar)
- dompdf absolute elemanlarda `bottom` UYGULAMAZ → `.a4-footer` statik akış konumuna düşer; container
  `min-height:285mm` zorlayınca footer 2. sayfaya taşıyordu.
- **Çözüm:** `EImzaService::pdfTipineGoreEkCss()` — cover_letter'da `.a4-footer{position:static !important;
  margin-top:14mm}`, `.a4-container{width:168mm}`, p 10px/lh 1.3, `.sayi-konu-tablo` margin 25px,
  `.text-center.font-bold` margin-bottom 20px (5070 bloğu üstte kalır).

### ✅ C. Eski görsel damga — SİLİNMİŞ (önceki sprint)
- `DocumentTemplateService::imzaDamgaEnjekte()` ve `applyEImzaStamp()` boş (`return $html`).
- Electron `sign-pdf.js` `buildPades()` **invisible PAdES** kullanıyor — `/Rect` yok, `/AP` yok, çizim yok.

### ✅ D. "Görüntüle" tarayıcıda açıyor
- `EImzaController::pdf()` ve `indir()` **zaten** `response()->file()` + `Content-Disposition: inline`.
- `ApplicationsController::downloadLicense()` de inline.

---

## 4. TEST ARAÇLARI (oluşturuldu)

| Script | Amacı |
|---|---|
| `test_pdf_generate.php` | 7 belge tipini `EImzaService::pdfOlustur()` ile üretir (`storage/app/test_{tip}_5070.pdf`) |
| `test_verify_all.py` | Sıkı doğrulama: **sabit beklenen sayfa sayısı** + taşma bbox + 5070 metni + eski damga yok + Helvetica/Times yok + mojibake yok + PNG üretir. Fold artık TÜM whitespace'i siler (`"5070sayili"` + `"guvenlielektronikimza"` arar) |
| `test_dump_pages.py`, `test_layout.py`, `test_overflow.py`, `test_debug_all.py`, `test_fold.py` | Teşhis amaçlı |
| `test_renderfor.php` | Hangi belgenin şablon/blade yolundan geçtiğini söyler |
| `e2e_sign.cjs` + `verify_signed.py` | GERÇEK token e2e: 7 PDF'i Pkcs11Bridge.exe ile imzalar (PIN 062954) + imzalı vs orijinal karşılaştırması |

**Kontrol PNG'leri:** `kontrol_{tip}_sayfa{N}.png` (çalıştırıldığı klasörde).

---

## 5. GÜNCEL TEST SONUÇLARI (son `test_verify_all.py` — 11.08.2026 FINAL)

```
PASS ruhsat:       1 sayfa, taşma yok, 5070 var, font tamam
PASS pre_permit:   1 sayfa, taşma yok, 5070 var, font tamam
PASS cover_letter: 1 sayfa, taşma yok, 5070 var, font tamam
PASS tahakkuk:     1 sayfa, taşma yok, 5070 var, font tamam
PASS metraj:       1 sayfa (landscape), taşma yok, 5070 var, font tamam
PASS makbuz:       1 sayfa, taşma yok, 5070 var, font tamam
PASS taahhutname:  1 sayfa, taşma yok, 5070 var, font tamam
```

**5070 yerleşim doğrulaması (programatik):** Grup A — ruhsat y=760 (doğrulama y=793 ÜSTÜNDE),
pre_permit 517<549, cover_letter 488<514 ✓; Grup B — metraj 232/253, makbuz 619/641, tahakkuk 369/390,
taahhutname 735/755 (belge EN ALTINDA) ✓.

**Görsel kalite (karakter çakışması + kenar kontrolü):** 7/7 belgede 5070 metni KIRMIZI,
kenar boşlukları temiz (8.5pt içinde blok yok), **0 gerçek karakter çakışması** (rawdict karakter bbox).

### 5.1 — GERÇEK TOKEN E2E SONUCU (tamamlandı)

```
Sertifika OK | keyType = RSA | cert 1374 bayt
OK ruhsat:       34593 B -> 51876 B
OK pre_permit:   32140 B -> 49423 B
OK cover_letter: 29176 B -> 46459 B
OK tahakkuk:     27724 B -> 45007 B
OK metraj:       25716 B -> 42999 B
OK makbuz:       29206 B -> 46489 B
OK taahhutname:  40645 B -> 57928 B
```

İmzalı PDF doğrulaması (`verify_signed.py`): **7/7 OK** — sayfa sayısı + MediaBox orijinalle birebir,
tüm orijinal kelimeler korundu, 5070 kırmızı, font seti aynı (Helvetica/Courier/Times YOK),
imza xref (`/FT /Sig`) + ByteRange + SubFilter mevcut. İmzalı dosyalar: `storage/app/e2e_signed_{tip}.pdf`.

---

## 6. KALAN İŞLER (tümü kapatıldı — 11.08.2026)

### ✅ 6.1 — Taşmalar (ÇÖZÜLDÜ — kök neden: box-sizing + guard bug'ı)
- dompdf `box-sizing` uygulamıyor → `width + padding` taşıyordu. Guard `'a4-container'` alt dizgesi
  landscape sınıfında yoktu → metraj hiç inline genişlik almıyordu.
- **Çözüm:** `a4ContainerInlineWidth` iki sınıfı da arıyor; `170mm`/`245mm` (squeeze'daki `width:100%`
  kaldırıldı), `min-height:0`, `@page { margin:6mm !important }`. Sonuç: tüm taşmalar 0.

### ✅ 6.2 — metraj 5070 2 sayfa (ÇÖZÜLDÜ)
- Kök neden 6.1'deki guard bug'ıydı — landscape inline genişliği hiç uygulanmıyordu. Guard düzeltilince
  metraj 1 sayfaya indi, 5070 en altta (y=232/253).

### ✅ 6.3 — Test script fold (ÇÖZÜLDÜ)
- `test_verify_all.py` fold'u artık tüm whitespace'i siliyor (`tam_yapistir`), `"5070sayili"` +
  `"guvenlielektronikimza"` arıyor. Yanlış pozitif yok.

### ✅ 6.4 — GERÇEK TOKEN İLE UÇTAN UCA TEST (TAMAMLANDI — bkz. §5.1)
- Token takılı, PIN **062954**, `C:\Windows\System32\akisp11.dll`. 7 belge de gerçek sertifika ile
  PAdES imzalandı (imza tipi bu sertifikada **RSA**), imzalı PDF'lerin tümü doğrulamayı geçti.
- İmzalı örnekler: `storage/app/e2e_signed_*.pdf`; imzasızlar: `storage/app/test_*_5070.pdf`.
- Not: HTTP katmanı (baslat/tamamla) oturumda DEĞİŞMEDİ; sadece PDF üretim CSS'i değiştiği için
  e2e, imzalama öncesi/sonrası PDF karşılaştırmasıyla tam kapsanıyor. Tarayıcı E2E'si isteğe bağlı.

---

## 7. KABUL KRİTERLERİ (hedef — TAM_COZUM_PLANI §3)

- [x] Tüm belgeler **1 sayfa** (ruhsat, pre_permit, cover_letter, tahakkuk, metraj, makbuz, taahhutname)
- [x] Hiçbir blok sayfa genişliğini aşmıyor (PyMuPDF bbox ≤ sayfa + 1pt)
- [x] Sayfa altında, doğrulama kodu satırının ÜSTÜNDE, KIRMIZI 5070 metni (Grup A) / belge en altında (Grup B)
- [x] Bundan başka HİÇBİR "İmzalayan: ... Tarih: ..." kutusu yok (G6 damga satırı hariç, kabul)
- [x] Türkçe karakterli gerçek isimle mojibake yok
- [x] "Görüntüle" tarayıcıda açıyor (✓ zaten)
- [x] İmzasız/imzalı MediaBox + font set birebir aynı
- [x] Gerçek token ile imzalı (PAdES) PDF tüm kontrolleri geçiyor

---

## 8. SONRAKİ ADIM (tekrarlanabilir komutlar)

```bash
# 1) Kod değişikliği sonrası belgeleri yeniden üret
php test_pdf_generate.php

# 2) Sıkı doğrulama (7 tip)
python test_verify_all.py

# 3) Görsel kontrol (kontrol_*.png — otomatik kontrollerin kaçırdığını gözle yakala)

# 4) Gerçek token e2e (imzalama + karşılaştırma)
node e2e_sign.cjs          # storage/app/e2e_signed_{tip}.pdf üretir (ESM yüzünden .cjs!)
python verify_signed.py
```

### İsteğe bağlı — tarayıcı E2E (HTTP katmanı değişmedi, gerekmiyor ama istenirse)
1. Tarayıcıda başvuru 1254 → "E-İmza ile İmzala" → `POST /api/e-imza/baslat` (web oturumu gerekli)
2. Electron PIN penceresi (57898/58210) → PIN 062954 → `POST /api/e-imza/tamamla` (API key)
3. `storage/app/public/e-imza/txn_.../imzali.pdf` indir → `verify_signed.py` ile aynı kontroller

*Rapor güncelleme tarihi: 11.08.2026 — TÜM işler tamamlandı (7 tip 1 sayfa, taşma 0, 5070 doğru, gerçek token e2e geçti).*