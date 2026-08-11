# ÇÖZÜM_03 — Doğrulama Kodu Kuralı + Ön Kazı/Metraj + Adobe İmza Kontrolü

> ÇÖZÜM_01 (7/7 ana plan) ve ÇÖZÜM_02 (SORUN A/B ince ayar) sonrası, kullanıcının
> 6 yeni ekran görüntüsüne dayanan bulgular. Ruhsat artık mükemmel — referans
> olarak kullanın.

---

## 0. YENİ EVRENSEL KURAL — Doğrulama Kodu, 5070 Metninin ALTINDA

Kullanıcının istediği kesin sıra (5 belge tipinin TAMAMINDA): önce kırmızı 5070
yasal metni, hemen altında doğrulama kodu satırı.

**Zaten doğru olanlar (dokunmayın):**
- ruhsat — `BELGE DOĞRULAMA KODU: EYYB-...` 5070'in altında ✓
- cover_letter (Dicle Elektrik örneği) — aynı sıra ✓

**Eksik olanlar (aşağıda ayrı ayrı ele alınıyor):**
- tahakkuk — doğrulama kodu satırı yok, yerine boş alan var
- metraj (saha_metraj) — doğrulama kodu satırı yok

```bash
# Hangi belge tiplerinde doğrulama kodu satırı hiç render edilmiyor?
grep -L "DOĞRULAMA KODU\|dogrulama_kodu\|doğrulama_kodu" resources/views/admin/pdf/*.blade.php
```

---

## 1. Tahakkuk — Boş Alanı Kaldır, Doğrulama Kodu Ekle

**Kanıt:** 5070 metninden sonra büyük boş/gri bir alan var, doğrulama kodu hiç
basılmıyor.

**Yap:**
1. `tahakkuk.blade.php`'de 5070 metninden sonraki boş `<div>`/spacer'ı bulup
   kaldırın: `grep -n "a4-footer\|footer-note\|<div.*height" resources/views/admin/pdf/tahakkuk.blade.php`
2. Onun yerine ruhsat'takiyle AYNI doğrulama kodu bloğunu ekleyin. İsimlendirme
   kararı: kullanıcı "MAKBUZ DOĞRULAMA" dedi ama tahakkuk aslında bir bedel
   hesabı — tutarlılık için diğer belgelerle AYNI "BELGE DOĞRULAMA KODU"
   metnini kullanmanızı öneririm (kod/adres mekanizması zaten aynı). Kullanıcı
   özellikle "MAKBUZ DOĞRULAMA" yazısını istiyorsa sadece metni değiştirin,
   mekanizma (kod üretimi, link) aynı kalsın.
3. `EImzaService.php`'deki `imzaYasalMetinEkle()` fonksiyonunda tahakkuk zaten
   "Grup B" (belgenin en altına) olarak tanımlı — doğrulama kodu bloğunu bu
   grubun render sırasına, 5070 metninden HEMEN SONRA ekleyin.

**Doğrula:** `test_verify_all.py`'a tahakkuk için de `"DOĞRULAMA KODU" in tam_metin`
assert'i ekleyin (şu an muhtemelen sadece cover_letter/pre_permit için resim
assert'i var, metin assert'i tüm tiplere genelleştirilmeli).

---

## 2. Metraj (saha_metraj) — Kenar Boşluğu + Doğrulama Kodu Eksik

**Kanıt:** Sağ/sol kenarlarda boşluk, alt kısımda büyük boş alan, doğrulama
kodu satırı yok.

**Önemli:** OpenCode'un bu turki raporunda **bilerek atlanmış**: *"metraj
27.6/30.1 değişmedi (onaylı görünüm korundu)"*. Yani metraj'ın kenar boşluğu
düzeltmesi bu tura dahil değildi, şimdi ele alınmalı — SORUN A'daki AYNI
ölçüm-formül-doğrula döngüsünü **landscape** için uygulayın:

```python
# Ölçüm (COZUM_02'deki script, landscape sayfa boyutuyla)
olcum_raporu('storage/app/test_metraj_5070.pdf')
```

Landscape için doğru genişlik formülü aynı mantıkla:
`(297mm - 2×@page_margin) - (sol_padding + sağ_padding)` — mevcut `245mm`
değerinin bu formüle göre ne kadar sapması olduğunu ölçüp kademeli düzeltin
(COZUM_02 §Adım 2'deki gibi, "onaylı görünüm" kaygısı varsa her adımda görsel
PNG'yi de üretip kontrol edin, sadece sayıya güvenmeyin).

Doğrulama kodu eksikliği için §0'daki genel çözümü uygulayın.

---

## 3. "Ön Kazı İzni" (ŞUSKİ Örneği) — Önce Şablonu Netleştirin

**Kanıt:** Logo yok, sağ/sol/alt boşluk var. Görsel yapı `cover_letter` ile
neredeyse birebir aynı (Sayı/Konu/İlgi formatı) ama kullanıcı bunu "ön kazı
izni" olarak adlandırıyor.

**Önce netleştirin (varsayımla ilerlemeyin):**
```bash
# Bu spesifik başvuru/belge hangi Blade view + hangi controller fonksiyonundan geçti?
grep -rn "view('admin.pdf.cover_letter'\|view('admin.pdf.pre_permit'" app/Http/Controllers app/Services --include="*.php"
```

**İki olası durum:**

**(A) Bu aslında `cover_letter` (sadece farklı şirket — ŞUSKİ):**
Bu durumda logo eksikliği bir BUG olmayabilir — ŞUSKİ'nin şirket kaydında
logo hiç yüklenmemiş olabilir:
```bash
php artisan tinker --execute="echo App\Models\Company::where('name','LIKE','%ŞUSKİ%')->value('logo_path') ?? 'NULL'"
```
Eğer gerçekten NULL ise, bu bir veri eksikliği — kod bug'ı değil. Ama
**tutarlılık için** öneri: `pre_permit`'in zaten sahip olduğu "şirket logosu
yoksa belediye logosuna düş" mantığını (ÇÖZÜM_01 §"akşam ekleri") `cover_letter`'a
da ekleyin — böylece hangi şirketin logosu olursa olsun belge boş kalmaz.

**(B) Bu aslında `pre_permit` (SORUN B bugünkü fix'in KAPSAMADIĞI tip):**
Bugünkü SORUN B raporu açıkça sadece `cover_letter` diyor — `pre_permit`
bahsedilmiyor. Eğer bu belge gerçekten pre_permit ise, AYNI base64 logo
enjeksiyonunu (bugün cover_letter'a yapılan) pre_permit'in KENDİ şirket logosu
için de uygulayın (ÇÖZÜM_01'deki "belediye logosu fallback" farklı bir
senaryoydu — bu, şirketin KENDİ logosu).

**Kenar boşluğu (her iki durumda da):** `pdfTipineGoreEkCss()` içinde bu tipin
KENDİ genişlik override'ı var mı kontrol edin (`grep -n "168mm\|cover_letter"
app/Services/EImzaService.php`). Varsa, bu değer SORUN A'nın bugünkü
170→174mm formül düzeltmesinden **etkilenmemiş olabilir** çünkü ayrı bir
override — §4'teki aynı riski burada da kontrol edin.

---

## 4. cover_letter'ın KENDİ Margin Override'ı — Bugün Gerçekten Düzeldi mi?

2 tur önce şu uyarıyı vermiştim: *"cover_letter kendi 168mm'sini kullanıyor —
paylaşılan fonksiyon değişince bu ETKİLENMEYEBİLİR."* Bugünkü SORUN A raporu
tabloda `pre_permit`'i gösteriyor ama `cover_letter`'ı GÖSTERMİYOR — bu,
cover_letter'ın ayrı override'ının bu turda ölçülüp düzeltilmediğine dair bir
işaret olabilir.

```bash
grep -n "168mm\|cover_letter.*width\|a4-footer" app/Services/EImzaService.php
```

Eğer hâlâ sabit `168mm` (ya da her ne ise) duruyorsa, §2'deki formülle
(portrait: `(210-2×@page_margin) - padding_toplam`) yeniden hesaplayıp
ölçüm+doğrula döngüsüyle düzeltin — bu muhtemelen §3'teki "ön kazı" kenar
boşluğu sorununu da AYNI ANDA çözer (eğer §3'ün cevabı (A) ise).

---

## 5. Adobe "İmza Geçersiz" — Kod Tarafında Kontrol Edilecekler

Kullanıcı tarayıcıda (Adobe'de) bir test yapıyor (kök sertifikayı güvenilir
işaretleyip tekrar açma) — o local/manuel bir adım. Kod tarafında ayrıca şunlar
doğrulanmalı:

```bash
# İmza (sign/plainAddPlaceholder) çağrısından SONRA PDF byte'larına dokunan
# BAŞKA bir adım var mı? (DSS ekleme, ikinci imza, herhangi bir save/write)
grep -n "signpdf.sign\|plainAddPlaceholder" -A 20 <electron-repo-yolu>/*.js
```
Eğer `sign()` çağrısından SONRA ayrı bir DSS/OCSP/zaman damgası ekleme adımı
varsa VE bu adım ayrı bir dosya yazma/save işlemiyse, bunun Adobe'nin
"çeşitli değişiklik" uyarısını tetikleyebileceğini biliyoruz (bu, imzanın
GEÇERSİZ olduğu anlamına gelmez, ama Adobe'ye özgü aşırı hassas bir davranış
olabilir). Bu adım varsa, aynı incremental-update işleminin İÇİNDE
(tek save, imza + DSS birlikte) yapılıp yapılamayacağını araştırın.

```bash
# Base PDF'in (dompdf çıktısının) xref tablosu standart mı?
qpdf --check storage/app/test_ruhsat_5070.pdf
```
"warnings" kısmında xref/cross-reference ile ilgili bir şey çıkarsa, bu da
Adobe'nin (sadece Adobe'nin, diğer araçlar önemsemez) daha katı davranmasına
yol açabilecek bilinen bir desen — çıkarsa ayrıca rapor edin, ben bakayım.

**Not:** Bu bölüm ARAŞTIRMA amaçlı — kullanıcının Adobe'de kök sertifikayı
güvenilir işaretleme testi SONUÇ VERİRSE (yani sorun sadece güven listesiyse),
bu bölümdeki kontroller opsiyonel hale gelir.

---

## Sıra

1. §0 + §1 (tahakkuk) — bağımsız, hızlı
2. §3'teki netleştirmeyi yap (A mı B mi), sonra ilgili logo fix'i uygula
3. §4 (cover_letter override) — muhtemelen §3'ü de çözer
4. §2 (metraj) — landscape ölçüm+düzeltme
5. §5 — kod tarafı kontrolleri, sonucu bana/kullanıcıya raporla
6. Her adımdan sonra `test_pdf_generate.php` + `test_verify_all.py` (7/7
   PASS + doğrulama kodu metni assert'i genelleştirilmiş) + `kontrol_*.png`

Kullanıcı ayrıca Adobe'de kök sertifika güven testini KENDİSİ yapacak —
sonucunu bekleyin, "1 çeşitli değişiklik" gerçekten kod kaynaklıysa §5 devreye
girer.
