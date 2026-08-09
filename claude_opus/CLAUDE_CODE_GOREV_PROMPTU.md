# AYKOME E-İmza PDF Sorunu — Claude Code Görev Promptu

> Bu metni olduğu gibi Claude Code'a yapıştır. `EIMZA_PDF_SORUN_RAPORU.md` ve
> `SignerPlacementService.php` dosyalarını proje köküne (veya `docs/` gibi bir
> klasöre) koy ki Claude Code bulup okuyabilsin — yoksa ilk adımda bulamaz.

---

Claude, sen şu anda Laravel + Vanilla JS/Electron mimarisiyle çalışan
"Eyyübiye Belediyesi AYKOME (ERP)" uygulamasının Baş Mimarısın. Bu kamu
kurumuna zaten teslim edilmiş, canlı kullanılan bir sistem — bu yüzden hem
hızlı hem de **temkinli** ilerlemen gerekiyor: önce teşhis, sonra tedavi.

## ZORUNLU İLK ADIM — kod tabanına dokunmadan önce

Proje içinde şu 2 dosyayı bul ve **baştan sona** oku:

1. `EIMZA_PDF_SORUN_RAPORU.md` — kök neden analizi, doğrulama komutları,
   kod seviyesinde çözümler ve GÖREV 1-5 görev listesi
2. `SignerPlacementService.php` — istenen imza-yerleşim modülünün referans
   iskeleti (senin gerçek kod tabanına göre uyarlaman gereken TODO'lar var)

Bu rapor, gerçek kodunu görmeden yapılmış bir **mimari teşhistir**. Senin
görevin önce raporun Bölüm 2'sindeki tanı komutlarıyla bu teşhisi KENDİ kod
tabanında doğrulamak, sonra Bölüm 3-5'teki çözümü uygulamak. Rapordaki hiçbir
hipotezi doğrulamadan koda geçme.

## SORUNUN ÖZETİ (raporda tam detay var)

E-imza (AKİS/PAdES) atıldıktan sonra PDF bozuluyor:
1. Türkçe karakterler mojibake oluyor ("KARATAİž", "AAŸustos" gibi)
2. Başlık + kurum logosu kayboluyor, metin sayfa kenarından taşıyor, sayfalar kayıyor
3. "Bu belge Aykome E-İmza ile imzalanmıştır..." kutusu yanlış/rastgele yerde çıkıyor

Bu belgeler: **ruhsat, ön kazı izni, üst yazı, tahsilat fişi** — hepsinde
kişi adları ve imza yerleri var, hepsi aynı bug'dan etkileniyor.

Kök neden hipotezi: imzalama adımı, dompdf'in ürettiği temiz PDF'in üzerine
SONRADAN (muhtemelen pdf-lib ile) bir damga metni çiziyor ve PDF'i yeniden
kaydediyor — hem yanlış fontla hem sayfa geometrisini bozarak.

## GÖREV SIRASI (raporda tam komutlarıyla var, burada özet)

**GÖREV 1 — TANI (önce bunu yap, kod değiştirme):**
Raporun Bölüm 2'sindeki tüm komutları çalıştır (grep'ler + PyMuPDF
karşılaştırması + `file -i` encoding kontrolü). Bulguları bana özetle:
damga metni bizim kodumuzda mı yoksa üçüncü parti imza aracında mı tanımlı,
MediaBox imza öncesi/sonrası aynı mı, hangi font(lar) kullanılıyor,
`utf8_encode`/`mb_convert_encoding` çağrısı var mı. Raporun Bölüm 6'sındaki
soruyu da (her süreç adımında gerçek AKİS imzası mı atılıyor, yoksa sadece
belirli adım(lar)da mı) kod tabanından çıkarabiliyorsan çıkar, çıkaramıyorsan
bana sor — bu, GÖREV 5'in tasarımını etkiliyor.

**GÖREV 2 — Türkçe karakter sorununu kökten çöz:**
GÖREV 1 bulgusuna göre Bölüm 3.1'deki A/B/C çözümlerinden uygun olanı uygula.

**GÖREV 3 — Logo/başlık/taşma sorununu çöz:**
Bölüm 3.2'deki gibi preview ve renderFor (veya eşdeğer) fonksiyonlarını tek
şablona indir, logo `<img>` kaynağını base64 data-URI'ye çevir, MediaBox farkı
varsa sayfa boyutu sabitlemesini düzelt.

**GÖREV 4 — Tek-geçişli mimariyi kur:**
Bölüm 4'teki mimari: imzalama fonksiyonundan tüm drawText/damga çizme kodunu
çıkar. İmza adımı artık sadece PDF byte'larını alıp kriptografik imza ekleyip
döndürsün — sıfır görsel değişiklik.

**GÖREV 5 — Modülü entegre et + regresyon testi:**
Ekteki `SignerPlacementService.php`'yi gerçek `EImzaService`, `User` modeli
ve süreç-adımı veri yapına göre uyarla (roller: Büro Personeli, Birim Şefi,
Fen İşleri Müdürü, Belediye Başkan Yardımcısı — mevcut Spatie rolleriyle
eşleştir). Her belge tipini render eden controller'da dompdf render'ından
hemen önce bu servisi çağır.

## KESİNLİKLE KIRMA — daha önce düzeltilmiş, çalışan kısımlar

- `.no-print` CSS enjeksiyonu (mavi print-bar/toolbar PDF'e sızmasın diye)
- DejaVu Sans font enjeksiyonu (7 noktada yapılmış: EImzaService 2 akış + 5 controller)
- `auth()->user()`'dan otomatik ad/unvan çekme (İmzalayan Bilgisi Swal formu kaldırılmış durumda kalmalı, geri gelmemeli)

Bu üçünü GÖREV 5 sonunda tek tek doğrula.

## TEST KURALI

Mutlaka gerçek Türkçe karakterli bir isimle test et (örn. "Ayşe Öztürk Ağır").
"Test Personeli" gibi aksansız isimler bu sınıf hataları YAKALAMAZ — önceki
turda bu yüzden sorun fark edilmeden "✓ çalışıyor" diye işaretlenmişti.
Raporun Bölüm 8'indeki PyMuPDF checklist script'ini çalıştır, sonucu göster.

## TAMAMLANMA

Her GÖREV'den sonra ne değiştirdiğini kısaca özetle, bir sonrakine öyle geç.
Tüm görevler bitip test checklist'i geçtiğinde, raporun Bölüm 9'undaki commit
mesajıyla pushla.
