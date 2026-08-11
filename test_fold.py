import fitz
doc = fitz.open(r'C:\Aykome_V1\storage\app\test_pre_permit_5070.pdf')
tam = doc[0].get_text()
fold = (tam.replace("\n"," ").replace("\r"," ")
        .replace("ü","u").replace("ö","o").replace("ş","s")
        .replace("ç","c").replace("ğ","g").replace("ı","i"))
print("RAW 5070 sayılı:", "5070 sayılı" in tam)
print("RAW güvenli    :", "güvenli" in tam)
print("FOLD 5070 sayili:", "5070 sayili" in fold)
print("FOLD guvenli    :", "guvenli" in fold)
# son 400 karakter
print("\nSON:", " ".join(tam.split())[-400:])