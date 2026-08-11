# Taşan blokların içeriğini göster
import fitz

for tip in ['pre_permit', 'cover_letter', 'tahakkuk', 'metraj', 'makbuz']:
    pdf = rf'C:\Aykome_V1\storage\app\test_{tip}_5070.pdf'
    doc = fitz.open(pdf)
    print(f"\n=== {tip} ({len(doc)} sayfa) ===")
    for i, page in enumerate(doc):
        pw = page.rect.width
        for block in page.get_text("dict")["blocks"]:
            b = block.get("bbox", [0,0,0,0])
            if b[2] > pw + 1:
                txt = page.get_textbox(b)[:60].replace("\n", " ")
                print(f"  S{i+1} sag-tasma x1={b[2]:.1f}> {pw:.0f}: '{txt}' x0={b[0]:.1f}")