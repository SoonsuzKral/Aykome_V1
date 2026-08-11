# Sayfa bazında metin dökümü — her sayfada ne var
import fitz

pdf = r'C:\Aykome_V1\storage\app\test_ruhsat_5070.pdf'
doc = fitz.open(pdf)

for i, page in enumerate(doc):
    print(f"\n{'='*50}\nSAYFA {i+1}\n{'='*50}")
    lines = page.get_text().splitlines()
    # Sadece boş olmayan satırları bas
    seen = [l.strip() for l in lines if l.strip()]
    # 40 satır ile sınırla
    for l in seen[:35]:
        print(" ", l[:90])
    if len(seen) > 35:
        print(f"  ... (+{len(seen)-35} satır)")