# Derin teşhis: 5070 metni nerede? metraj 2. sayfa ne? container ne genişlik?
import fitz

for tip in ['pre_permit', 'cover_letter', 'metraj', 'tahakkuk']:
    pdf = rf'C:\Aykome_V1\storage\app\test_{tip}_5070.pdf'
    doc = fitz.open(pdf)
    print(f'\n===== {tip} ({len(doc)} sayfa) =====')
    for i, page in enumerate(doc):
        txt = page.get_text()
        print(f'  S{i+1}: {len(txt)} karakter | 5070 var: {"5070 sayılı" in txt.replace(chr(10)," ")} | guvenli var: {"güvenli" in txt} | elektronik var: {"elektronik" in txt}')
        if i == 0:
            # ilk 300 karakter
            print('  METIN BASI:', ' '.join(txt.split())[:220])
    # Son sayfa metninin sonu
    last = doc[-1].get_text()
    print(f'  SON SAYFA SONU: {" ".join(last.split())[-160:]}')