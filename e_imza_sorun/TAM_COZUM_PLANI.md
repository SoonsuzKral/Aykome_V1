# TAM ÇÖZÜM PLANI — Konsolide Rapor v2

> Bu dosya, önceki 3 dosyadaki (`EIMZA_PDF_SORUN_RAPORU.md`,
> `GOREV_6_DEVAM_PROMPTU.md`, `GOREV_9_ACIL_ELECTRON_VE_5070.md`) TÜM
> bulguları TEK bir öncelik sırasında toplar + 1 yeni kritik kontrol ekler.
> Claude Code'a: **bu dosyayı yol haritası olarak kullan**, diğer 3 dosyaya
> sadece "tam kod" gerektiğinde referans ver (her bölümde hangi dosyada
> olduğu yazılı).

---

## 0. YENİ KONTROL — Gereksiz "Kopyalama" Adımı Var mı?

5070 sayılı kanundaki "kağıt kopyasıdır" ibaresi, YAZILIMIN "taslak üret →
ayrı kopya çıkar → kopyayı imzala" şeklinde çalışması GEREKTİĞİ anlamına
gelmiyor — bu tamamen hukuki bir açıklama metni (elektronik asıl vs. onun
yazdırılmış/dışa aktarılmış hali arasındaki farkı anlatıyor). Doğru mimaride
"kopyalama" diye bir adım YOKTUR: tek PDF üretilir, kriptografik imza o
AYNI dosyaya eklenir (incremental update — mevcut byte'lar değişmez, sadece
imza bilgisi sona eklenir).

**Ama sizin sisteminizde gerçekten böyle bir adım olup olmadığı** — yani
imzalanacak PDF'in, önizleme/taslak PDF'ten FARKLI bir fonksiyon/kod yoluyla
"yeniden üretiliyor" veya "kopyalanıyor" olup olmadığı — henüz doğrulanmadı.
Eğer varsa, GÖREV 0'daki (aşağıda) Electron hipoteziyle birleştiğinde bu,
TÜM belirtilerin (sayfa taşması, kayıp 5070 metni, karakter sorunu) ortak
kaynağı olabilir, çünkü o ikinci yol muhtemelen orijinal dompdf render'ından
farklı ayarlar/kütüphane kullanıyor.

```bash
# İmzalama akışını uçtan uca izle:
grep -rn "function.*[İi]mzala\|imzalama.*hazirla\|pdfOlustur" app/Services app/Http/Controllers --include="*.php"

# "kopyala" kelimesi geçen herhangi bir PDF-ilişkili fonksiyon var mı?
grep -rni "kopyala\|duplicate\|clone.*pdf\|copy.*pdf" app/ --include="*.php"

# Aynı belge tipi (örn. ruhsat) için KAÇ FARKLI Blade view/controller
# fonksiyonu PDF üretiyor? Birden fazlaysa, hangisi önizleme hangisi
# imzalama için kullanılıyor, ikisi AYNI mı?
grep -rn "view('pdf\." resources/views app/ --include="*.php" --include="*.blade.php" | grep -i ruhsat
```

**Sonuç ne olursa olsun rapor et:** (a) tek bir üretim yolu varsa ve
Electron doğrudan onun çıktısını imzalıyorsa → bu kontrol temiz, GÖREV 1'e
geç; (b) iki ayrı yol varsa (biri önizleme, biri "imzalanacak sürüm" için)
→ bunları TEK yola indirmek, bu raporun geri kalanındaki HER bug'ı aynı anda
çözme ihtimali yüksek bir kısayol.

---

## 1. Bulgu Özeti (şu ana kadar)

| # | Belirti | Durum | Tam detay |
|---|---|---|---|
| 1 | Damga metninde Türkçe mojibake | Muhtemelen çözüldü (commit 3bc7d88) | `EIMZA_PDF_SORUN_RAPORU.md` §3.1 |
| 2 | Ruhsat 2 sayfaya taşıyor | **AÇIK** | `GOREV_6_DEVAM_PROMPTU.md` §GÖREV 6 |
| 3 | 5070 sayılı metni kayıp | **AÇIK** | `GOREV_9_ACIL_ELECTRON_VE_5070.md` §GÖREV B |
| 4 | Görüntüle → indiriyor (tarayıcıda açmıyor) | Çözüm hazır, uygulanmalı | `GOREV_9_ACIL_ELECTRON_VE_5070.md` §GÖREV A |
| 5 | Damga/taşma kodunun asıl kaynağı bulunamadı | Muhtemelen ayrı Electron reposu | `GOREV_9_ACIL_ELECTRON_VE_5070.md` §GÖREV 0 |
| 6 | Gereksiz "kopyalama" adımı ihtimali | **YENİ — kontrol edilmedi** | bu belge §0 |

---

## 2. Öncelik Sırası — TEK Yol Haritası

**Adım 1 — §0 (bu belge):** kopyalama adımı var mı kontrol et. Varsa,
onu ortadan kaldırmak öncelik. Yoksa devam.

**Adım 2 — Electron kaynağını bul:** `GOREV_9_ACIL_ELECTRON_VE_5070.md`
§GÖREV 0'daki `find` komutlarını çalıştır. Bu, §3 (5070 metni) ve muhtemelen
§2'nin (sayfa taşması, eğer Electron tarafında bir "flatten/rasterize" işlemi
varsa) kaynağını gösterecek.

**Adım 3 — Hemen, bağımsız uygulanabilir düzeltme:** `GOREV_9...` §GÖREV A
(inline görüntüleme, `response()->file()` + `Content-Disposition: inline`).
Bunun diğer bulgularla ilgisi yok, şimdi yapılabilir.

**Adım 4 — 5070 metni mimarisi:** `GOREV_9...` §GÖREV B — imza anında
(`now()`) Blade render'ına `imza_tarihi_metni` geçirilmesi, dompdf'in bunu
sayfanın altına, doğrulama kodu satırının ÜSTÜNE basması. Bu render SONRASI
hiçbir ek çizim yapılmamalı.

**Adım 5 — Electron temizliği:** Adım 2'de bulunan kodda `drawText`/
`Helvetica`/damga çizme neyse temizle, `plainAddPlaceholder` + saf
kriptografik imzalama ile değiştir (`GOREV_9...` §GÖREV C'deki referans
koda bakın).

**Adım 6 — Sayfa taşması:** `GOREV_6_DEVAM_PROMPTU.md` §GÖREV 6 — ruhsat
(ve tahakkuk) tablosunu `table-layout:fixed` + yüzde genişliklere çevir.
**Not:** eğer Adım 1'de bir "kopyalama" adımı bulunup kaldırıldıysa, bu
sorunun kendiliğinden çözülüp çözülmediğini ÖNCE test edin — belki ayrıca
CSS değişikliği bile gerekmez.

**Adım 7 — Sıkı doğrulama:** `GOREV_6_DEVAM_PROMPTU.md` §GÖREV 8'deki
PyMuPDF script'i (sabit beklenen sayfa sayısı + taşma tespiti + 5070 metni
varlığı + eski damga yokluğu + PNG çıktı). **Gerçek Türkçe karakterli bir
isimle** test edin.

---

## 3. Kabul Kriterleri (hepsi birden sağlanmalı)

- [ ] Ruhsat imzalı PDF'i **tam olarak 1 sayfa**
- [ ] Hiçbir metin/tablo bloğu sayfa sınırını aşmıyor (PyMuPDF bbox kontrolü)
- [ ] Sayfanın altında, doğrulama kodu satırının üstünde, TAM OLARAK:
      *"Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan
      belgenin [gerçek imza tarihi] tarihli kağıt kopyasıdır. Bu belge
      güvenli elektronik imza ile imzalanmıştır."* — kırmızı renkte
- [ ] Bundan başka HİÇBİR "İmzalayan: ... Tarih: ..." türü ek kutu/satır yok
- [ ] Türkçe karakterli gerçek bir isimle (örn. "Ayşe Öztürk Ağır") test
      edildiğinde mojibake yok
- [ ] "Görüntüle" tıklanınca tarayıcıda açılıyor, indirme başlamıyor
- [ ] Orijinal (imzasız) ve imzalı PDF'in MediaBox'ı ve font seti birebir aynı

## 4. Test Sonrası

Her adımdan sonra `kontrol_*.png` dosyalarını (GÖREV 8 script'i üretiyor)
kaydedin ve paylaşın — otomatik kontrollerin kaçırdığı bir şey kalmadığından
emin olmak için gözle de bakılmalı. Tüm kabul kriterleri geçmeden
"tamamlandı" denmeyecek.
