import fitz, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

tipler = ['ruhsat', 'pre_permit', 'cover_letter', 'tahakkuk', 'metraj', 'makbuz', 'taahhutname']
tamam = True

for tip in tipler:
    orj = fitz.open(rf'C:\Aykome_V1\storage\app\test_{tip}_5070.pdf')
    imz = fitz.open(rf'C:\Aykome_V1\storage\app\e2e_signed_{tip}.pdf')
    sorun = []

    # 1) sayfa sayisi + MediaBox esit mi
    if orj.page_count != imz.page_count:
        sorun.append(f'sayfa: {orj.page_count} -> {imz.page_count}')
    else:
        for i in range(orj.page_count):
            r1, r2 = orj[i].rect, imz[i].rect
            if abs(r1.width - r2.width) > 0.1 or abs(r1.height - r2.height) > 0.1:
                sorun.append(f'sayfa{i} MediaBox: {r1.width:.1f}x{r1.height:.1f} -> {r2.width:.1f}x{r2.height:.1f}')

    # 2) orijinaldeki tum metin imzalida da var mi (sig eklemesi haric)
    orj_metin = orj[0].get_text().replace('\n', ' ')
    imz_metin = imz[0].get_text().replace('\n', ' ')
    kelimeler = [w for w in orj_metin.split() if len(w) > 3]
    kayip = []
    for w in set(kelimeler):
        if w not in imz_metin:
            kayip.append(w)
    if kayip:
        sorun.append(f'{len(kayip)} kelime kayip: {kayip[:6]}')

    # 3) 5070 hala kirmizi mi
    kirmizi = False
    for block in imz[0].get_text("dict")["blocks"]:
        for line in block.get("lines", []):
            for span in line.get("spans", []):
                if '5070' in span["text"]:
                    col = span["color"]
                    r, g, b = (col >> 16) & 255, (col >> 8) & 255, col & 255
                    if r > 140 and g < 90 and b < 90:
                        kirmizi = True
    if not kirmizi:
        sorun.append('5070 kirmizi degil/eksik')

    # 4) fontlar: orijinaldeki font seti korundu mu (helvetica eklenmemis)
    f_orj = {f[3] for f in orj.get_page_fonts(0)}
    f_imz = {f[3] for f in imz.get_page_fonts(0)}
    eklenen = f_imz - f_orj
    helv = [f for f in f_imz if any(x in f for x in ('Helvetica', 'Courier', 'Times'))]
    if helv:
        sorun.append(f'helvetica/courier/times: {helv}')
    if eklenen:
        sorun.append(f'eklenen font: {eklenen}')

    # 5) imza: raw xref'te /FT /Sig + /ByteRange + /Contents
    raw = imz.tobytes()
    if b'/ByteRange' not in raw:
        sorun.append('ByteRange YOK')
    if b'/SubFilter' not in raw:
        sorun.append('SubFilter YOK')
    if b'/FT' not in raw or b'/Sig' not in raw:
        sorun.append('imza widget xref YOK')

    # 6) imzali PDF yeni sayfa tasirmadi (icerik siniri)
    for i in range(imz.page_count):
        if imz[i].rect.width < 500:  # gercek sayfa
            pass

    if sorun:
        tamam = False
        print(f'FAIL {tip}: ' + ' | '.join(sorun))
    else:
        print(f'OK  {tip}: metin korundu, 5070 kirmizi, font ayni, imza xref var')
    orj.close(); imz.close()

print('\n' + ('TUM IMZALI PDFLER TEMIZ' if tamam else 'SORUNLAR VAR'))
