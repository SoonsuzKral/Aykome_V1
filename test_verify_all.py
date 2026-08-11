# Test 3: TÜM belge tipleri için sıkı doğrulama
# BEKLENEN_SAYFA: her belgenin kaç sayfa olması gerektiği
# metraj landscape A4, diğerleri portrait
import fitz, os, sys

BEKLENEN_SAYFA = {'ruhsat': 1, 'pre_permit': 1, 'cover_letter': 1, 'tahakkuk': 1, 'metraj': 1, 'makbuz': 1, 'taahhutname': 1}
SABITLIK = 1  # PyMuPDF blok kenarı +1pt tolerans (border eklentisi)

def dogrula(pdf_yolu, belge_tipi):
    doc = fitz.open(pdf_yolu)
    hata = []
    beklenen = BEKLENEN_SAYFA.get(belge_tipi)
    if beklenen and len(doc) != beklenen:
        hata.append(f"Sayfa sayısı {len(doc)}, beklenen {beklenen}")

    for i, page in enumerate(doc):
        pw = page.rect.width
        ph = page.rect.height
        for block in page.get_text("dict")["blocks"]:
            b = block.get("bbox", [0,0,0,0])
            if b[2] > pw + SABITLIK:
                hata.append(f"Sayfa {i+1}: saga tasma x1={b[2]:.1f} > {pw:.0f} bbox={b}")
            if b[3] > ph + SABITLIK:
                hata.append(f"Sayfa {i+1}: alta tasma y1={b[3]:.1f} > {ph:.0f}")

    # PDF metin çıkarımında cümle ortasından satır sonu gelebilir
    # ("güvenli elektronik imza\nile imzalanmıştır") ve PyMuPDF Türkçe
    # kelimelerde harf bölmesi yapabilir ("g" + "venli").
    # fold: ascii (ü→u, ş→s, ı→i...) + TÜM boşlukları kaldır → "guvenlielektronikimza".
    tam = "\n".join(p.get_text() for p in doc)
    tam_fold = (tam.replace("ü", "u").replace("ö", "o").replace("ş", "s")
                .replace("ç", "c").replace("ğ", "g").replace("ı", "i")
                .replace("Ü", "U").replace("Ö", "O").replace("Ş", "S")
                .replace("Ç", "C").replace("Ğ", "G").replace("İ", "I"))
    # TÜM whitespace kaldırılır: satır sonu, yeni satır, boşluk, sekme...
    import re
    tam_yapistir = re.sub(r"\s+", "", tam_fold)
    if "5070sayili" not in tam_yapistir:
        hata.append("5070 sayılı yasal metni YOK")
    if "guvenlielektronikimza" not in tam_yapistir:
        hata.append("'güvenli elektronik imza' YOK")
    if "Aykome E-İmza ile imzalanmıştır" in tam:
        hata.append("ESKİ damga kutusu HALA VAR")

    fontlar = set()
    for page in doc:
        for f in page.get_fonts():
            fontlar.add(f[3])
    if any('Helvetica' in f or 'Times' in f or 'Courier' in f for f in fontlar):
        hata.append(f"WinAnsi standart font: {fontlar}")

    for bozuk in ["Ÿ", "ž", "Ã", "Â", "�"]:
        if bozuk in tam:
            hata.append(f"Mojibake: {bozuk}")

    # PNG
    for i, page in enumerate(doc):
        page.get_pixmap(dpi=120).save(rf"C:\Aykome_V1\kontrol_{belge_tipi}_sayfa{i+1}.png")

    if hata:
        print(f"FAIL {belge_tipi}: {len(hata)} sorun | {len(doc)} sayfa")
        for h in hata[:6]:
            print(f"   - {h}")
        return False
    print(f"PASS {belge_tipi}: 1 sayfa, tasma yok, 5070 var, font tamam")
    return True

os.chdir(r"C:\Aykome_V1")
sonuc = True
for tip, pdf in BEKLENEN_SAYFA.items():
    p = os.path.join("storage", "app", f"test_{tip}_5070.pdf")
    if os.path.exists(p):
        sonuc &= dogrula(p, tip)
    else:
        print(f"⚠️ {tip}: dosya yok")
        sonuc = False

print("\n" + ("BASARI: TUM BELGELER GECTI" if sonuc else "HATA: BAZI BELGELERDE SORUN VAR"))