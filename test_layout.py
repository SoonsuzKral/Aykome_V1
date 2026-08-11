# Sayfa 1'deki blokların y koordinatları — hangi bölüm ne kadar yer kaplıyor
import fitz

pdf = r'C:\Aykome_V1\storage\app\test_ruhsat_5070.pdf'
doc = fitz.open(pdf)
page = doc[0]
blocks = sorted(page.get_text("dict")["blocks"], key=lambda b: b["bbox"][1])

print("A4 yüksekliği: 842pt | Sayfa 1:")
for b in blocks:
    x0, y0, x1, y1 = b["bbox"]
    txt = page.get_textbox(b["bbox"])[:55].replace("\n", " ")
    print(f"  y={y0:6.1f} → {y1:6.1f}  (h={y1-y0:5.1f})  {txt}")