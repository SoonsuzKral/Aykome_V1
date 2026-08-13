# ÇÖZÜM_07 — Genel Yol Haritası (CSS + E-İmza Kalanları + Yeni Büyük Özellikler)

> Bu, kod görevi değil, **strateji/öncelik dosyası**. Zed'de Claude Sonnet 5
> ile çalışırken bunu ilk oku, sonra hangi bölümden başlayacağına karar ver.
> Kodu incelediktan sonra yazıldı (`ProcessStep`, `DocumentTemplateService`
> gerçekten görüldü) — tahmin değil.

---

## Önce Gerçeği Görelim: Bu İstekler AYNI Büyüklükte Değil

Kullanıcı tek mesajda 8 farklı şeyi art arda saydı. Bunları AYNI günde,
AYNI ciddiyetle ele almaya çalışmak — hepsini yarım bırakır. Büyüklüğe göre
4 kategoriye ayırıyorum:

| Kategori | Öğeler | Tahmini Ölçek |
|---|---|---|
| **A — Bugün/bu hafta bitebilir** | CSS ince ayar (devam ediyor), EXE indirme linki | Saatler |
| **B — Netleşmiş, temeli var** | Çoklu imzalama, paraflama | 1-3 gün |
| **C — BÜYÜK, ayrı proje** | "Tam World Yapısı" şablon editörü | Haftalar |
| **D — Belirsiz, önce netleştir** | "Projede gezip hataları çöz" | Sınırsız — böyle bırakılmamalı |

**Kritik tavsiye:** C ve D'yi A/B ile aynı sprint'e koyma. C, kendi başına
ayrı bir proje planı hak ediyor — "bugünkü işler listesi"ne eklenirse hem
o hem diğerleri yarım kalır.

---

## KATEGORİ A — Hemen Devam

### CSS ince ayar
Görsel 1'deki Dicle Elektrik belgesindeki boş kutu, ÇÖZÜM_06'da zaten
kapsanan AYNI "kenar/alt boşluk" sınıfı hata — yeni bir dosya gerekmiyor,
OpenCode/Zed ÇÖZÜM_06'yı bitirsin, bu örnek de otomatik düzelecek.

### E-imza EXE indirme
```
Admin panel header'a bir link/buton ekleyin: "E-İmza Uygulamasını İndir"
→ storage/app/public/downloads/aykome-eimza-setup.exe (ya da nereye
konuluyorsa) dosyasına yönlendirsin. Route + basit bir Blade değişikliği,
yarım saatlik iş.
```
**Önce sorulması gereken soru:** e-imza masaüstü uygulamasının GERÇEK bir
Windows installer'ı (`.exe`/`.msi`, Inno Setup veya electron-builder ile
paketlenmiş) var mı, yoksa şu an sadece geliştirme klasöründe mi duruyor?
Yoksa önce PAKETLEME (Kategori B'nin altına, "setup hazırlama" olarak)
gerekiyor — sadece link eklemek yetmez.

---

## KATEGORİ B — Netleşmiş, Kod Temeli Zaten Var

### Çoklu İmzalama
`ProcessStep` modelinde ZATEN şu alanlar var:
`signature_config`, `approvable_modules`, `module_permissions`,
`personnel_ids`, `action_type`. Bu, "hangi adımda kim imzalayacak"
sorusunun BÜYÜK kısmının zaten modellendiği anlamına geliyor. Sıfırdan
tasarım değil, MEVCUT yapıyı kullanma/tamamlama işi:

1. `signature_config` şu an ne saklıyor, gerçekten kullanılıyor mu, yoksa
   kolon var ama boş mu — önce bunu inceleyin (`ProcessEngine.php`,
   `ProcessController.php`'ye bakın)
2. Örnek senaryo (ön kazı izni): üst yazı → Fen İşleri Müdürü imzalar →
   Belediye Başkan Yardımcısı imzalar. Bu, aynı PDF üzerinde İKİ AYRI
   kriptografik imza demek — burada ÇÖZÜM_04/05'teki "imza sonrası içerik
   değişmeyecek" kuralı ÖZELLİKLE önemli: 2. imza atılırken 1. imzanın
   bozulmaması gerekiyor (PDF incremental update ile, her imza kendi
   revizyonunu ekler — bu node-signpdf'in zaten desteklediği bir şey,
   `sign()` fonksiyonu var olan bir PDF'in üzerine tekrar çağrılabilir).
3. Belgede KİM'İN henüz imzalamadığını (SignerPlacementService'in zaten
   yaptığı "tamamlanmayan adım → boş alan" mantığı) çoklu imza senaryosunda
   da aynen çalışır — bu kısım muhtemelen ek iş gerektirmiyor.

### Paraflama
"Paraf" = imza değil, daha hafif bir "gördüm/onayladım" işareti. Öneri:
`ProcessStep.action_type` alanına (zaten var) `'paraf'` diye bir değer
eklenebilir, `'imzala'`'dan ayrı. Paraf atıldığında:
- Kullanıcının kimlik bilgisi + zaman kaydedilir (süreç takibinde görünür)
- PDF'e KRİPTOGRAFİK bir imza EKLENMEZ (ya da eklense bile, belgenin
  "resmi olarak imzalandı" durumunu TETİKLEMEZ)
- Asıl imzalayacak kişi imzalamadan belge "imzalı" sayılmaz

Bu, mevcut `action_type` alanı sayesinde muhtemelen büyük bir mimari
değişiklik değil, bir DALLANMA eklemek.

---

## KATEGORİ C — "Tam World Yapısı" — AYRI PROJE OLARAK ELE ALIN

Bu isteği olduğu gibi almadan önce dürüst olmam lazım: **"kullanıcı kendi
Word belgesini yüklesin, sistem içinde fareyle sürükleyip logoyu
büyütsün, İlgi/Sayı gibi her alanı serbestçe düzenlesin, Excel için de
aynısı olsun"** — bu, pratikte **kendi web tabanlı Word/Excel editörünüzü
inşa etmek** demek. `DocumentTemplateService.php` zaten 1774 satır — bu,
küçük bir modül değil, ÜZERİNE koyacağınız şey de küçük olmayacak.

**Önerim — küçültülmüş, gerçekçi bir ilk versiyon:**
1. Word yükleme: `.docx` dosyasını okuyup (mammoth.js veya benzeri ile)
   HTML'e çevirin — bu KABA bir çeviri olur (Word'ün TAM formatını
   piksel piksel korumaz), kullanıcıya bunu baştan söyleyin.
2. Alan işaretleme: `{basvuru_no}` gibi placeholder'ları HTML'de OLDUĞU
   GİBİ bırakın (zaten mevcut `Bilgi Alanları` sisteminizle uyumlu).
3. Düzenleme: TAM Word deneyimi yerine, **sınırlı ama sağlam** bir
   zengin-metin editörü (örn. TipTap, Quill, ya da Laravel ekosisteminde
   yaygın kullanılan bir WYSIWYG kütüphanesi) — kalın/italik, hizalama,
   resim boyutlandırma (bu KISMEN mümkün, "fareyle her yeri istediğin gibi
   sürükle" tam anlamıyla Word/InDesign seviyesi bir hedef, gerçekçi
   olmayabilir).
4. Excel için AYNI mantıkla ayrı, daha sonraki bir aşama — birlikte
   başlamayın, ikisi paralel değil ardışık olsun.

**Bu kategoriye YARIN başlamayın.** Önce A ve B bitsin (gerçek, canlı
kullanılan e-imza sistemi tamamen otursun), sonra C'ye kendi başına,
ayrı planlı bir "sprint" olarak girin.

---

## KATEGORİ D — "Projede Gezip Hataları Çözsün" — Önce Sınırla

Bu haliyle sınırsız bir görev ("her şeye bak, her hatayı bul") — bir AI
ajanına bu şekilde verilirse ya çok yüzeysel kalır ya da kontrolsüz çok
büyük değişiklik yapar. Öneri: HER modül için AYRI, kısa bir tur açın —
örn. önce "Süreç Onay Rotası" modülü TEK BAŞINA ("ProcessController,
ProcessEngine, ProcessStep — bul, oku, hataları listele, ONAY ALMADAN
değiştirme"), sonuç görülüp onaylandıktan sonra bir sonraki modüle geçin.
"Projede gez" yerine "şu 3 dosyayı incele, bul, raporla" deyin.

---

## Önerilen Başlama Sırası

1. **Bugün:** Kategori A (CSS bitir + EXE paketleme durumunu netleştir)
2. **Bu hafta:** Kategori B (çoklu imza + paraf) — mevcut `signature_config`
   altyapısını KULLANARAK, sıfırdan tasarlamadan
3. **Ayrı, sonraki sprint:** Kategori C — küçültülmüş kapsamla başlayın
4. **Her zaman, tek tek:** Kategori D — bir modül, bir tur, onay, sonraki

## Zed / Model Seçimi İçin Pratik Tavsiye

- **Büyük mimari kararlar** (çoklu imza akışı, WYSIWYG editör tasarımı):
  en yüksek yetenekli model + yüksek düşünme eforu (High/Extra High) —
  hata maliyeti yüksek, aceleye getirmeyin
- **Mekanik, iyi tanımlı düzeltmeler** (CSS boşluğu, tekrarlayan bug
  deseni): daha hızlı/hafif bir model + düşük-orta efor yeterli, zaman
  kazandırır
- Kripto/imza koduna DOKUNAN her şey (Kategori B'nin imza kısmı) → yüksek
  efor + ÇÖZÜM_04/05'teki disiplin (izole test + bağımsız doğrulama,
  sadece "kod geçti" demeye güvenmeyin) devam etsin
