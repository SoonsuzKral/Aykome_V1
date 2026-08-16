# ÇÖZÜM_08 — Bugün: E-İmza Tam Bitirme Günü (Kamu SM + E-Tuğra Elde)

> Zed'de Claude Sonnet 5 ile çalışırken bunu ilk oku. Kod bizzat incelendi
> (`scanner.js`, `SignatoryEngine.php`, `SignerPlacementService.php`,
> `pkcs11-bridge.c`) — tahmin değil, gerçek bulgular.

---

## Model Önerisi (net cevap)

- **Bugünkü işlerin TAMAMI için Claude Sonnet 5, düşünme eforu YÜKSEK
  (High/Extra High).** Sebep: hepsi ya kripto/imza koduna dokunuyor ya da
  güvenlik-ilişkili (isim kontrolü). Bu kod tabanında ("çalıştı" denip
  gerçekte bozuk çıkan) çok tur yaşadık — bugün hız değil doğruluk öncelik.
- Sadece SEED/test verisi oluşturma gibi saf mekanik işler için isterseniz
  Haiku 4.5 kullanılabilir, zaman kazandırır — ama imza/sertifika mantığına
  yaklaşan her şeyde Sonnet 5'e dönün.
- GPT modelleri hakkında karşılaştırmalı bir iddiada bulunmuyorum
  (yeterli veri yok) — bu proje Claude ile bu kadar yol katetti, bugün
  değiştirmeye gerek yok.

---

## GÖREV 1 (Küçük, Hemen) — Token Sahibi Adını Göster

**Kesin bulgu:** `aykome-e-imza/src/pkcs11/scanner.js` içinde
`detectWithPkcs11()` fonksiyonu, `pkcs11js` kütüphanesinin bilinen bir
sınırlaması yüzünden (kod içi yorum: GitHub issue #114) sadece "AKIS Token"
placeholder'ı dönüyor, gerçek isim/sertifika bilgisini OKUYAMIYOR. AMA aynı
dosyada `detectViaWindowsCertStore()` fonksiyonu ZATEN YAZILMIŞ — PowerShell
ile Windows Sertifika Deposu'ndan (`Cert:\CurrentUser\My`) gerçek isim
(Subject CN), sağlayıcı (Issuer), geçerlilik tarihleri okuyor. **Bu fonksiyon
hiçbir yerden çağrılmıyor** — kodda arandı, sıfır kullanım bulundu.

**Yapılacak:** Ana uygulama akışının (muhtemelen `detectWithPkcs11()`'i
çağıran electron/main süreci) `detectViaWindowsCertStore()`'u da (ya
ÖNCELİKLİ olarak, ya da PKCS11 placeholder'ı yetersiz kaldığında fallback
olarak) çağırmasını sağlayın. UI'da "Token Algılandı" kutusuna gerçek ad/
sertifika bilgisini basın.

```bash
grep -rn "detectWithPkcs11\|scanner\." aykome-e-imza/src/*.js aykome-e-imza/main*.js 2>/dev/null
```
ile şu an UI'ı besleyen gerçek çağrı noktasını bulun, yanına
`detectViaWindowsCertStore()` çağrısını ekleyin.

---

## GÖREV 2 (Karar + Uygulama) — Token İsmi vs vice_mayor_name Kontrolü

**Kesin bulgu:** `vice_mayor_name` alanı (form girişi, kayıt, belgeye
basma) TAMAMEN ÇALIŞIYOR, bozuk değil. AMA imzalama anında "tokendeki isim
bu alanla eşleşiyor mu" diye AKTİF bir engelleme kontrolü bulunamadı —
sadece rol/kullanıcı bazlı yetki kontrolü (Süreç ve Onay Rotası'ndaki
"İmzalayacak Kullanıcılar/Roller") var.

**Önce karar verin:** Bu kontrolü GERÇEKTEN istiyor musunuz (yani, token
sahibinin adı `vice_mayor_name` ile TAM eşleşmezse imzalama engellensin
mi)? Eğer evet:

1. `detectViaWindowsCertStore()`'dan (GÖREV 1) gelen gerçek Subject CN'i
   kullanın — bu, karşılaştırma için GEREKLİ ön koşul (GÖREV 1 önce
   bitmeli).
2. İmzalama isteği sunucuya gitmeden HEMEN önce, `vice_mayor` adımı için:
   `trim(strtoupper(tokenCN)) === trim(strtoupper($application->vice_mayor_name))`
   benzeri bir kontrol ekleyin (Türkçe büyük/küçük harf — İ/I sorununu
   unutmayın, daha önce MapsController'da yazdığımız `trUpper()` benzeri
   bir helper kullanın).
3. Eşleşmezse net bir hata mesajı: "Bu belgede kayıtlı Başkan Yardımcısı
   adı ile token sahibinin adı uyuşmuyor. Lütfen doğru token'ı takın ya da
   belge üzerindeki ismi güncelleyin."

Bu, GÖREV 1'den SONRA yapılmalı (gerçek isim okunmadan karşılaştırma
yapılamaz).

---

## GÖREV 3 (BUGÜNÜN ASIL SINAVI) — Kamu SM (ECDSA) Uçtan Uca Test

Geçen sefer sadece E-Tuğra (RSA) ile test edebildik, kod incelemesiyle
ECDSA yolunun (raw r||s → DER dönüşümü, `pkcs11-bridge.c`) doğru
yazıldığından emindim ama fiziksel testle KANITLANMADI. Bugün bu kanıtlanıyor.

**Adımlar:**
1. Kamu SM tokenini takın, uygulamayı GÖREV 1'in düzeltmesiyle açın —
   "Mustafa Kemal Karataş" adı doğru görünüyor mu? (bu, GÖREV 1'in de
   testi olur)
2. Test başvurusu (GÖREV 4'te seed ile) üzerinden Kamu SM ile bir belge
   imzalayın
3. **Aynı çift-doğrulama disiplinini uygulayın** (ÇÖZÜM_04/05'teki gibi):
   - EU DSS'e yükleyin (`ec.europa.eu/digital-building-blocks/DSS/webapp-demo/validation`)
     → `Cryptographic Verification: PASSED` olmalı
   - Adobe'de açın → sadece "kimlik doğrulanmadı" sarı uyarısı kalmalı
     (BELEDIYE_IT_GUVEN_KURULUMU.md'deki bilinen, beklenen durum),
     "Anahtar/İmza Algoritması: Geçersiz" ÇIKMAMALI
4. E-Tuğra ile AYNI belge tipini (örn. ruhsat) tekrar imzalayıp
   karşılaştırın — ikisi de temiz çıkmalı

**Eğer Kamu SM'de yeni bir hata çıkarsa** (RSA'da görmediğimiz), en olası
şüpheli `pkcs11-bridge.c`'deki `ecdsa_raw_to_der()` fonksiyonu — kartın
DÖNDÜĞÜ ham imza uzunluğu (P-384 için 96 bayt bekleniyor) farklı çıkıyorsa
sorun oradadır, önce onu loglayın.

---

## GÖREV 4 — Test Seed (Dicle Elektrik, Her Modülde İmza)

Bu SİZİN makinenizde, fiziksel tokenlerle yapılmalı — ben uzaktan
simüle edemem. Zed/Sonnet 5'e verilecek net talimat:

```
"Süreç ve Onay Rotası" ekranındaki "E-İmza Test Süreci (Komple)" sürecini
kullanan, Dicle Elektrik alt kurumu adına bir test başvurusu (seed) oluştur.
Başvuru sırayla 4 adımdan geçsin: Büro Personeli onayı → Birim Şefi paraf →
Fen İşleri Müdürü e-imza (Ön Kazı İzni PDF'i) → Başkan Yrd. e-imza.
Müdür adımında vice_mayor_name alanına gerçek bir isim gir (test için).
Her e-imza adımında hem Kamu SM hem E-Tuğra ile AYRI AYRI dene (aynı
başvurunun 2 kopyası ya da 2 ayrı test başvurusu), hangi belge tipini
(Ön Kazı İzni, Ruhsat, Tahakkuk...) hangi rol imzalıyorsa onu kapsa.
```

Her imzadan sonra GÖREV 3'teki çift-doğrulamayı (DSS + Adobe) tekrarlayın.

---

## GÖREV 5 (Unutmayın) — Sunucu Adresi Gerçek Ortam İçin

`aykome-e-imza` masaüstü uygulamasında "Sunucu Adresi: http://127.0.0.1:8001"
görünüyor — bu YEREL geliştirme adresi. Personel kendi bilgisayarında
kurduğunda bu, GERÇEK sunucu adresine (muhtemelen
`https://aykome.eyyubiye.bel.tr` veya API için ayrılmış bir alt yol)
işaret etmeli, HTTP değil **HTTPS** olmalı (imza isteği ağ üzerinden gidiyor,
şifrelenmeli). Kurulum/setup paketi hazırlanırken bu adres config dosyasında
DEĞİŞTİRİLEBİLİR olmalı (her PC'ye farklı hardcode edilmemeli) — GÖREV
listesine "EXE indirme" ile birlikte bunu da ekleyin.

---

## Sıra

1. GÖREV 1 (token adı gösterme) — hızlı, bağımsız
2. GÖREV 3 (Kamu SM testi) — GÖREV 1 bittikten hemen sonra, bugünün asıl
   önceliği
3. GÖREV 2 (isim kontrolü) — karar + uygulama, GÖREV 1'e bağımlı
4. GÖREV 4 (seed + her modül testi) — GÖREV 1-3 sonrası
5. GÖREV 5 — setup/paketleme çalışmasına not olarak eklenir, bugün
   zorunlu değil ama unutulmasın
