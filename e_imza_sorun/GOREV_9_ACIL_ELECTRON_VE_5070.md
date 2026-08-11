# GÖREV 9 (ACİL) — Electron Kaynağını Bul + 5070 Metni + Tarayıcıda Aç

> Claude Code'a yapıştır. Önce GÖREV 0'ı yap — geri kalan her şey buna bağlı.
> Önceki `GOREV_6_DEVAM_PROMPTU.md`'deki sayfa taşması sorunu hâlâ açık,
> bu dosyaya EK olarak çalışılmalı, yerine değil.

---

## GÖREV 0 — ÖNCE BUNU YAP: İmzalama Aracının Kaynak Kodu Nerede?

**Hipotez:** Önceki GÖREV 1-5, Laravel/Blade reposunda "damga metni" araştırdı
ve bulamadı — bunu "sorun yok" olarak yorumladık ama aslında bu, damganın
**başka bir kod tabanında** (imzalama işlemini yapan Electron/exe
uygulamasında) olduğunu gösteriyor. Bu yüzden yapılan tüm düzeltmeler Laravel
tarafında doğru ama asıl suçlu hâlâ dokunulmamış durumda.

**Yap:**
```bash
# Bu makinede (RDP sunucusunda) Electron/imza uygulamasının kaynağını ara
find / -iname "*.asar" 2>/dev/null
find / -iname "package.json" -exec grep -l "\"electron\"" {} \; 2>/dev/null
find / -iname "package.json" -exec grep -l "node-signpdf\|plainAddPlaceholder" {} \; 2>/dev/null
find / -iname "*imza*" \( -iname "*.exe" -o -iname "*.js" -o -iname "*.ts" \) 2>/dev/null | grep -v node_modules
find / -iname "main.js" -path "*electron*" 2>/dev/null

# Eğer proje bir monorepo değilse, ayrı git repoları için:
find / -maxdepth 4 -iname ".git" -type d 2>/dev/null
```

**Bulduğunda:**
- O repoda `drawText\|drawRectangle\|Helvetica\|Times-Roman` için grep at —
  görsel damga muhtemelen orada.
- `5070 sayılı` için de aynı repoda grep at — muhtemelen AYNI fonksiyonun
  içinde, kötü damgayla yan yana duruyor.
- Bulamıyorsan (gerçekten Laravel dışında hiçbir yerde yoksa, ve imzalama
  personelin kendi bilgisayarında ayrı bir masaüstü uygulamasıyla oluyorsa):
  bana/kullanıcıya o uygulamanın kaynak kodunun repo adresini veya bilgisayarda
  hangi klasörde durduğunu sor — bu olmadan devam edilemez.

## GÖREV A — Tarayıcıda Aç, İndirtme (kesin çözüm, hemen uygula)

İmzalı belge "Görüntüle" ile açıldığında indiriyor, tarayıcıda açılmıyor.
Saf HTTP header sorunu:

```php
// Bul: response()->download(...) kullanılan yer (imzalı belge görüntüleme route'u)
// ❌ bu ATTACHMENT disposition gönderir, indirtir:
return response()->download($pdfPath);

// ✅ bununla değiştir — INLINE disposition, tarayıcıda açar:
return response()->file($pdfPath, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"',
]);
```

```bash
grep -rn "response()->download" app/Http/Controllers/ --include="*.php"
```
Bulunan HER yerde, eğer o route "görüntüle/preview/pdf" amaçlıysa (indirme
butonu değilse) `response()->file(...)` + `inline` ile değiştir. Gerçek
"indir" butonu için `download()` kalabilir — sadece "görüntüle" akışı
düzeltilecek.

## GÖREV B — 5070 Metnini Doğru Yerde, Doğru Zamanlamayla Ekle

**Kesin istenen çıktı** (sadece bu, başka hiçbir ek satır yok), imza sonrası
sayfanın en altında, BELGE DOĞRULAMA KODU satırının ÜSTÜNDE, kırmızı:

```
Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan belgenin
{TARİH} tarihli kağıt kopyasıdır. Bu belge güvenli elektronik imza ile
imzalanmıştır.
```

**Mimari — bunu ASLA post-processing ile (Electron/pdf-lib ile PDF'e sonradan
çizerek) yapma. dompdf'in Blade render'ının İÇİNDE, imzalama eylemi
başladığı ANDA render tetiklenerek yapılmalı:**

```php
// İmzalama controller'ı — Electron'a göndermeden HEMEN önce:
public function imzalamayaHazirla(Basvuru $basvuru, string $belgeTipi)
{
    $imzaTarihi = now(); // imza anının tarihi burada sabitlenir

    $data = array_merge(
        $this->mevcutBelgeVerisi($basvuru, $belgeTipi),
        $signerPlacementService->yerlesimHazirla(/* ... */),
        [
            'imza_tarihi_metni' => sprintf(
                'Bu çıktı, 5070 sayılı elektronik imza kanununa göre '
                . 'imzalanan belgenin %s tarihli kağıt kopyasıdır. Bu belge '
                . 'güvenli elektronik imza ile imzalanmıştır.',
                $imzaTarihi->format('d.m.Y H:i')
            ),
        ]
    );

    $html = view("pdf.{$belgeTipi}", $data)->render();
    $pdfBytes = $this->dompdfIleUret($html);

    // BURADAN SONRA $pdfBytes'a TEK BİR ÇİZİM/DÜZENLEME YAPILMAYACAK.
    // Doğrudan Electron'a / kriptografik imza adımına gönder.
    return $this->electronaGonderVeImzala($pdfBytes);
}
```

```blade
{{-- pdf/ruhsat.blade.php (ve on_kazi, ust_yazi, tahsilat_fisi) — sayfa sonu --}}
@if(isset($imza_tarihi_metni))
<p style="color:#c0392b; font-size:9px; text-align:center; margin-top:16px;">
    {{ $imza_tarihi_metni }}
</p>
@endif
<p style="text-align:center; font-size:8px; color:#888;">
    BELGE DOĞRULAMA KODU: {{ $dogrulama_kodu }} | KONTROL ADRESİ: {{ $kontrol_adresi }}
</p>
```

`imza_tarihi_metni` sadece imzalama akışında set edilir — normal önizlemede
set edilmez, bu yüzden ayrı bir `@if($imzalandi)` bayrağına gerek yok, değişkenin
varlığı/yokluğu yeterli.

## GÖREV C — Electron Tarafını Temizle (GÖREV 0'da bulunca)

Orada bulacağın kötü damga kodunu SİL. Yerine (eğer o script PDF'i alıp
sadece imzalıyorsa) şöyle bir akış olmalı — çizim YOK, sadece kriptografik
ekleme:

```javascript
const signpdf = require('node-signpdf').default;
const { plainAddPlaceholder } = require('node-signpdf/dist/helpers');
const fs = require('fs');

function pdfiKriptografikOlarakImzala(girisPdfYolu, cikisPdfYolu, /* mevcut AKİS parametreleriniz */) {
  // Bu PDF Laravel'den TAMAMEN BİTMİŞ geliyor (5070 metni dahil).
  // Burada TEK SATIR bile çizilmeyecek.
  let pdfBuffer = fs.readFileSync(girisPdfYolu);

  pdfBuffer = plainAddPlaceholder({
    pdfBuffer,
    reason: 'AYKOME E-İmza',
    contactInfo: '',
    name: '',
    location: 'Şanlıurfa',
  });

  // ⚠️ Buradan sonrası MEVCUT AKİS/PKCS11 imzalama kodunuz — aynen kalsın,
  // sadece öncesinde/sonrasında herhangi bir drawText/drawRectangle/
  // damga çizme çağrısı OLMADIĞINDAN emin olun.
  const imzalanmisPdf = signpdf.sign(pdfBuffer, /* mevcut sertifika/parametreler */);

  fs.writeFileSync(cikisPdfYolu, imzalanmisPdf);
}
```

## Not: "Ana Şablon vs Başvuruya Özel PDF" Mimarisi (kullanıcının belirttiği)

Sistemde ana taslak şablonlar var, başvuru yapıldığında oradan kopyalanıyor,
başvuru içinde düzenleme yapılınca sadece o kopyaya özel hale geliyor. Bu,
GÖREV 6'daki (önceki dosya) sayfa taşması aramasında ek bir yer:
"başvuruya özel" render yolunun, ana şablon önizlemesinden FARKLI bir CSS/genişlik
kullanıp kullanmadığını da kontrol et — aynı "iki ayrı render yolu birbirinden
ayrışmış" deseni burada da tekrarlanıyor olabilir.

## Sıra

1. **GÖREV 0 önce** — Electron kaynağını bul, bulamazsan kullanıcıya sor
2. GÖREV A — hemen uygula, bağımsız, garanti çalışır
3. GÖREV B — Laravel/Blade tarafı
4. GÖREV C — GÖREV 0'da bulunan Electron kodunu temizle
5. Önceki `GOREV_6_DEVAM_PROMPTU.md`'deki sayfa taşması hâlâ AYRI ve AÇIK —
   onu da uygula
6. Test: gerçek Türkçe isimle imzala, PDF'i indir, PyMuPDF ile Bölüm 8
   (önceki rapor) + bu dosyadaki 5070-metni-var-mı kontrolünü çalıştır,
   PNG'leri kaydet, paylaş
