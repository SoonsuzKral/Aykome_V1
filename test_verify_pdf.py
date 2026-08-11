# Test 1: 5070 metni + sayfa + taşma + font kontrolü
import fitz, sys

pdf = r'C:\Aykome_V1\storage\app\test_ruhsat_5070.pdf'
doc = fitz.open(pdf)

print(f"Sayfa sayısı: {len(doc)}")
hata = []
for i, page in enumerate(doc):
    pw = page.rect.width
    print(f"  Sayfa {i+1}: {page.rect.width:.0f}x{page.rect.height:.0f}")
    for block in page.get_text("dict")["blocks"]:
        x1 = block.get("bbox", [0,0,0,0])[2]
        if x1 > pw + 1:
            hata.append(f"TAŞMA Sayfa {i+1} bbox={block['bbox']}")
            print(f"  ⚠️ TAŞMA: bbox={block['bbox']}")

tam = "\n".join(p.get_text() for p in doc)

print("\n--- 5070 METNİ ---")
if "5070 sayılı" in tam:
    # satırı bul
    for line in tam.splitlines():
        if "5070 sayılı" in line:
            print(f"  ✓ {line.strip()}")
else:
    print("  ✗ '5070 sayılı' BULUNAMADI")
    hata.append("5070 yok")

if "güvenli elektronik imza" in tam.lower():
    print("  ✓ 'güvenli elektronik imza' ibaresi var")
else:
    print("  ✗ 'güvenli elektronik imza' YOK")
    hata.append("güvenli elektronik imza yok")

print("\n--- ESKİ DAMGA ---")
if "Aykome E-İmza ile imzalanmıştır" in tam:
    print("  ✗ ESKİ DAMGA KUTUSU HALA VAR!")
    hata.append("eski damga var")
else:
    print("  ✓ Eski damga yok")

print("\n--- FONTLAR ---")
fonts = set()
for page in doc:
    for f in page.get_fonts():
        fonts.add(f[3])
print(f"  {sorted(fonts)}")
if any('Helvetica' in f or 'Times' in f for f in fonts):
    print("  ✗ WinAnsi standart font bulundu!")
    hata.append("helvetica/times")
else:
    print("  ✓ Sadece DejaVu")

print("\n--- MOJIBAKE KONTROLÜ ---")
for bozuk in ["Ÿ", "ž", "Ã", "Â", "�"]:
    if bozuk in tam:
        print(f"  ✗ Mojibake izi: {bozuk}")
        hata.append(f"mojibake {bozuk}")
print("  ✓ Temiz" if not any(b in tam for b in ["Ÿ","ž","Ã","Â","�"]) else "")

# PNG çıktı
for i, page in enumerate(doc):
    page.get_pixmap(dpi=130).save(rf'C:\Aykome_V1\kontrol_ruhsat_sayfa{i+1}.png')
    print(f"PNG: kontrol_ruhsat_sayfa{i+1}.png")

print("\n" + ("❌ SORUNLAR VAR" if hata else "✅ TEST 1 GEÇTİ"))
for h in hata:
    print(f"   - {h}")