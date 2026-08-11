# GÖREV 6-8 — Sayfa Taşması + Kayıp Yasal Metin + Sıkı Doğrulama

> Bu metni olduğu gibi Claude Code'a yapıştır. Bu, `EIMZA_PDF_SORUN_RAPORU.md`
> ve commit `3bc7d88`'deki GÖREV 1-5 sonrası, **gerçek PDF çıktısını açıp bakınca**
> ortaya çıkan 2 YENİ bulgudur. GÖREV 1-5 bunları kapsamıyordu.

---

Claude, GÖREV 1-5'i tamamladın ve checklist "✓ GEÇTİ" dedi, ama kullanıcı
gerçek imzalı PDF'i bir PDF görüntüleyicide açtığında 2 yeni sorun gördü.
Önce NEDEN checklist'in bunu yakalamadığını anla, sonra düzelt.

## NEDEN ÖNCEKİ CHECKLİST BUNU YAKALAMADI (önemli — aynı hatayı tekrarlama)

Rapor Bölüm 8'deki kontrol şuydu: `sayfa_sayisi(imzasiz) == sayfa_sayisi(imzali)`.
Bu bir **tutarlılık** kontrolüdür, **doğruluk** kontrolü değildir. Elindeki
"imzasız" referans PDF'in kendisi ZATEN 2 sayfaya taşıyorsa (ki öyleymiş),
imzalı hali de 2 sayfa çıkınca "2=2 eşit ✓" deyip geçmişsin — ama doğru
sayı 1 olmalıydı (bkz. ekteki orijinal ruhsat görseli, tek sayfa). Bundan
sonra HER sayfa-sayısı kontrolünü belge tipine göre **sabit beklenen değere**
karşı yap, "öncekiyle aynı mı"ya değil.

## GÖREV 6 — Sayfa Taşmasını Çöz (ruhsat 2 sayfaya bölünüyor)

**Belirti:** "ALTYAPI TESİSİ AÇIM RUHSATI" belgesi normalde tek sayfa; gerçek
dompdf çıktısında tablo sağdan taşıyor ve içerik 2. sayfaya kayıyor
(ADA NO / PAR NO / EV NO sütunları ve GENEL TOPLAM hücresi kırpılıyor).

**Muhtemel sebep:** ruhsat (ve muhtemelen tahakkuk — SESSION_SUMMARY.md'de
Sprint 13 notunda zaten kayıtlı) tablosu sabit piksel genişlikli sütunlarla
kurulu, Helvetica'nın dar metriklerine göre ayarlanmış. DejaVu Sans'a
geçildiğinde (Türkçe karakter desteği için, doğru bir değişiklik) aynı metin
daha fazla yer kaplıyor, tablo A4'ün basılabilir genişliğini (~760px) aşıyor.

**Doğrulama:**
```bash
grep -rln "AÇILACAK ZEMİN\|ALTYAPI TESİSİ AÇIM" resources/views --include="*.blade.php"
# bulunan dosyada <table>, <td width=..., style="width:...px" ara
```

```python
# Taşma tespiti — bir bloğun sağ kenarı sayfa genişliğini aşıyor mu?
import fitz
doc = fitz.open('ruhsat_test.pdf')
for i, page in enumerate(doc):
    pw = page.rect.width
    for block in page.get_text("dict")["blocks"]:
        x1 = block.get("bbox", [0,0,0,0])[2]
        if x1 > pw + 1:
            print(f"Sayfa {i+1}: TAŞMA — bbox={block['bbox']} (sayfa genişliği={pw})")
```

**Çözüm:** Tabloyu `table-layout: fixed` + yüzde bazlı sütun genişliklerine
çevir (toplam %100, sabit piksel değil). Gerekirse bu spesifik yoğun tablo
için font-size'ı 1-2pt küçült. Aynı taramayı **tahakkuk** şablonunda da yap —
aynı kalıp orada da mevcut olabilir.

**Kabul kriteri:** yukarıdaki Python scripti hem ruhsat hem tahakkuk test
PDF'lerinde "TAŞMA" satırı basmıyor VE sayfa sayısı ruhsat için tam olarak
`1`.

## GÖREV 7 — Kayıp Yasal Metin: "5070 sayılı..."

**Beklenen davranış (kullanıcının tam talebi):** belge imzalandığında, sayfanın
en altında, **BELGE DOĞRULAMA KODU satırının ÜSTÜNDE**, KIRMIZI renkte, sadece
şu cümle görünecek — başka HİÇBİR ŞEY eklenmeyecek (ayrı bir "İmzalayan: Ad
Soyad" satırı YOK, çünkü imzalayan bilgisi zaten imza sertifikasında ve
ilgili rol hücresinde mevcut):

```
Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan belgenin
[İMZA_TARİHİ] tarihli kağıt kopyasıdır. Bu belge güvenli elektronik imza
ile imzalanmıştır.
```

`[İMZA_TARİHİ]` = imzanın FİİLEN atıldığı an (`now()`), sabit/önceden
oluşturulmuş bir değer değil.

**Şu an olan:** bu metin hiç görünmüyor.

**Doğrulama:**
```bash
grep -rn "5070 sayılı\|güvenli elektronik imza ile imzalanmıştır" resources/views app/
```
- **Sonuç boşsa:** bu metin blade template'lerden GÖREV 4'teki damga temizliği
  sırasında yanlışlıkla silinmiş olabilir — git geçmişinde ara:
  `git log -p --all -S "5070 sayılı" -- resources/views` ile hangi commit'te
  kaldırıldığını bul, o commit'in diff'ine bak, yasal metni GERİ GETİR ama
  onunla birlikte silinen bozuk "İmzalayan: X Tarih: Y" kutusunu GERİ GETİRME.
- **Sonuç varsa ama koşullu bir `@if` içindeyse:** o koşulun (örn.
  `$belge->imzalandi`) render anında doğru set edilip edilmediğini kontrol et.

**Kritik mimari nokta — zamanlama:** Bölüm 4'teki "tek geçişli render" mimarisi
doğru çalışması için, PDF'in dompdf render'ı **imzalama eyleminin TAM O ANINDA**
tetiklenmeli (`imza_tarihi = now()` o an render'a geçilerek), önceden
oluşturulup cache'lenmiş bir PDF'in imzalanması DEĞİL. Eğer mevcut akış "önce
taslak PDF oluştur (kaydet) → sonra ayrı bir adımda o kaydedilmiş dosyayı
imzala" şeklindeyse, taslak oluşturma anında `imzalandi=false` olduğu için bu
metin hiçbir zaman doğru basılamaz. Gerekirse imzalama controller'ını "render
+ imzala" tek fonksiyon/transaction haline getir.

**Kabul kriteri:** imzalı PDF'in tam metninde `"5070 sayılı"` VE
`"güvenli elektronik imza"` alt dizeleri bulunuyor, metnin İÇİNDEKİ tarih
gerçek imza anına eşit, ve belge gövdesinde bundan başka hiçbir
"İmzalayan:"/"Bu belge Aykome E-İmza ile..." türü ek satır YOK.

## GÖREV 8 — Sıkı Doğrulama (checklist'i düzelt)

Rapor Bölüm 8'deki script'i şununla DEĞİŞTİR (eskisi yetersizdi):

```python
import fitz

BEKLENEN_SAYFA = {'ruhsat': 1, 'on_kazi': 1, 'ust_yazi': 1, 'tahsilat_fisi': 1}
# ^ gerçek beklenen değerleri kendi belge tiplerinize göre doğrulayıp güncelleyin

def dogrula(pdf_yolu, belge_tipi):
    doc = fitz.open(pdf_yolu)
    hata = []

    beklenen = BEKLENEN_SAYFA.get(belge_tipi)
    if beklenen and len(doc) != beklenen:
        hata.append(f"Sayfa sayısı {len(doc)}, beklenen {beklenen}")

    for i, page in enumerate(doc):
        pw = page.rect.width
        for block in page.get_text("dict")["blocks"]:
            x1 = block.get("bbox", [0,0,0,0])[2]
            if x1 > pw + 1:
                hata.append(f"Sayfa {i+1}: içerik taşıyor (bbox={block['bbox']})")

    tam_metin = "\n".join(p.get_text() for p in doc)
    if "5070 sayılı" not in tam_metin:
        hata.append("5070 sayılı yasal metni YOK")
    if "güvenli elektronik imza" not in tam_metin.lower():
        hata.append("'güvenli elektronik imza' ibaresi YOK")
    if "Aykome E-İmza ile imzalanmıştır" in tam_metin:
        hata.append("Eski/fazladan damga kutusu HALA VAR")

    fontlar = {f[3] for f in page.get_fonts()}
    if any('Helvetica' in f or 'Times' in f for f in fontlar):
        hata.append(f"WinAnsi standart font bulundu: {fontlar}")

    # Her sayfayı PNG olarak kaydet — SADECE bu scripte güvenme, gözle de bak
    for i, page in enumerate(doc):
        page.get_pixmap(dpi=150).save(f"kontrol_{belge_tipi}_sayfa{i+1}.png")

    if hata:
        print(f"❌ {belge_tipi}: {len(hata)} sorun")
        for h in hata: print("   -", h)
    else:
        print(f"✅ {belge_tipi}: tüm kontroller geçti")
    return len(hata) == 0

# En az ruhsat ve tahakkuk için, GERÇEK Türkçe karakterli isimle test edilmiş
# bir imzalı PDF üzerinde çalıştır:
dogrula('ruhsat_imzali_test.pdf', 'ruhsat')
dogrula('tahakkuk_imzali_test.pdf', 'tahakkuk')
```

**Bundan sonra "✓ tamamlandı" derken:** ürettiğin `kontrol_*.png`
dosyalarını da paylaş / kaydet — otomatik script'in yakalayamadığı bir görsel
sorun kalmadığından emin olmak için kullanıcı (veya sonraki turda ben) bu
PNG'lere gözle bakmalı. Sadece "checklist geçti" demek bu tur için yeterli
kabul edilmeyecek.

---

## Sıra

1. GÖREV 6 → 7 → 8 sırasıyla uygula, her birinden sonra kısa özet ver
2. GÖREV 8'in ürettiği PNG'leri `/tmp` yerine kalıcı bir klasöre kaydet, yolunu belirt
3. Ruhsat + tahakkuk dışında üst_yazı ve tahsilat_fişi şablonlarını da aynı
   taşma deseni için tara (henüz raporlanmadı ama aynı DejaVu-genişlik
   sorununa sahip olabilirler) — bulursan aynı fixed-layout düzeltmesini uygula
4. Commit mesajı: `fix: ruhsat/tahakkuk tablo tasmasi (dejavu genislik) ve
   kayip 5070 sayili yasal metin duzeltildi, checklist sabit sayfa sayisina
   gore siki hale getirildi`
