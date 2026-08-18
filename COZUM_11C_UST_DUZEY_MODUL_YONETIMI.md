# ÇÖZÜM_11C — Üst Düzey Modül Yönetimi (Ayarlar ↔ Süreç Onay Rotası Birleştirme)

---

## Önce Netleştirme: Zaten Var Olanlar

Görsel 2-7'yi inceledim — "üst düzey mödül yönetimi" olarak istediğiniz
şeylerin ÇOĞU zaten mevcut, tekrar yapmaya gerek yok:
- ✅ Yeni modül oluşturma (Görsel 2, "+ Yeni Modül")
- ✅ Alan tanımlama (Görsel 4, tip/genişlik/zorunluluk hepsi var)
- ✅ Şablon yönetimi (Görsel 5)
- ✅ Sıralama — hangi modül hangi sırada (Görsel 6, "Tüm Modül Sıralaması")
- ✅ Editör tipi seçimi (word/excel — ruhsat/tahakkuk gibi tablo-ağırlıklı
  modüller için excel, üst yazı/ön kazı gibi metin-ağırlıklı olanlar için
  word — bu, `editor_type` alanı olarak ÇÖZÜM_09'da doğrulandı)

**Gerçek eksik, tam olarak şu:** "Ayarlar" sekmesindeki imza/onay
yapılandırması (Görsel 7) TEK, DÜZ bir form — sadece BİR "İşlem Tipi" ve
BİR "Sıradaki Modül" seçilebiliyor. Oysa Süreç ve Onay Rotası (Görsel 8),
AYNI modül için ÇOK ADIMLI, her adımı farklı rol/kişi/aksiyon tipine sahip,
görsel bir yapı sunuyor — istediğiniz zenginlik zaten BAŞKA bir ekranda
var, sadece Modül Yönetimi'nin içine taşınmamış.

---

## Önerilen Yön: Kopyalama Değil, Yeniden Kullanma

**Yapmayın:** Süreç Onay Rotası'nın görsel node-editörünü SIFIRDAN,
ikinci bir kopya olarak Modül Yönetimi içine yeniden yazmak — bu, iki ayrı
"doğru kaynak" (source of truth) yaratır, ileride birbirinden sapar,
bakımı ikiye katlanır.

**Yapın:** Modül Yönetimi'nin "Ayarlar" sekmesini, Süreç Onay Rotası'nın
AYNI görsel bileşenini (aynı Vue component/JS modülü) **bu modüle
FİLTRELENMİŞ** halde GÖMEREK değiştirin. Teknik olarak:

1. Süreç Onay Rotası zaten her adımda "Modül Görünürlüğü" (Görsel 8, sağ
   panel) diye bir çoklu-seçim alanı içeriyor — yani ZATEN "bu adım hangi
   modül(ler)de geçerli" bilgisini tutuyor.
2. Modül Yönetimi'nin bir modülüne girdiğinizde ("Taahhütname" gibi),
   "Ayarlar" sekmesi artık düz formu DEĞİL, Süreç Onay Rotası'nın AYNI
   görsel builder'ını, sadece `Modül Görünürlüğü = 'taahhutname'` olan
   adımları gösterecek/düzenleyecek şekilde AÇSIN.
3. "+ Adım Ekle" ile burada yeni bir adım eklerseniz, o adım otomatik
   olarak bu modülün görünürlüğüyle işaretlenir — global Süreç Onay
   Rotası ekranına da AYNI adım (aynı veri, aynı ProcessStep kaydı)
   yansır, çünkü ikisi de AYNI tabloyu okuyup yazıyor.

```bash
# Süreç Onay Rotası'nın Vue/Blade component'ini bulun
grep -rln "SüreçAdım\|ProcessStepEditor\|surec-adim\|process-step-editor" resources/js resources/views --include="*.vue" --include="*.blade.php" 2>/dev/null

# Modül Yönetimi'nin Ayarlar tab'ını bulun
grep -rln "Onay Ayarları\|İmza Ayarları.*E-İmza ve Paraf" resources/views/admin/modules --include="*.blade.php"
```

Bu ikisini bulduktan sonra, ikinci dosyayı BİRİNCİDEKİ component'i
`:module-filter="'taahhutname'"` gibi bir prop ile çağıracak şekilde
değiştirmek, en düşük riskli, en az kod tekrarı olan yol.

---

## Ek Özellik: "İmzalı Nüsha Yükleme İzni" (bahsettiğiniz eksik parça)

Görsel 8'de bahsettiğiniz "imzalı nüsha yükleme bölümü" — kullanıcının
e-imzası yoksa fiziksel imzalı kopyayı tarayıp yükleyebilmesi — bunu her
adımın kendi ayarına ekleyin. `ProcessStep` tablosuna (zaten
`signature_config` JSON alanı var, ÇÖZÜM_08'de doğrulandı) yeni bir bayrak:
```php
// signature_config JSON içine:
'allow_signed_copy_upload' => true/false,
```
Adım düzenleme panelinde (Görsel 8 sağ taraf), "E-İmza Yetkisi" bloğunun
yanına bir checkbox: **"E-imza yoksa imzalı nüsha yüklemeye izin ver"**.

---

## Sıra

1. Önce Süreç Onay Rotası'nın component yapısını bulun (grep yukarıda)
2. Modül Yönetimi → Ayarlar sekmesini, aynı component'i filtreli
   çağıracak şekilde değiştirin — TEK modülle (örn. Taahhütname) başlayıp
   test edin, sonra diğerlerine yayın
3. "İmzalı nüsha yükleme izni" checkbox'ını ekleyin
4. Test: Taahhütname'nin Ayarlar sekmesinde artık çok adımlı, görsel bir
   yapı görünmeli; burada eklenen bir adım, global Süreç Onay Rotası
   ekranında da aynı şekilde görünmeli (ikisi aynı veriyi paylaşıyor)
