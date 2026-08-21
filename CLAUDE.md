# CLAUDE.md — AYKOME (Eyyübiye Belediyesi AYKOME ERP)

> Bu dosyayı HER oturumda önce oku. Farklı modeller (Claude Code, OpenCode,
> MiniMax, Laguna, MiMo) bu projede sırayla çalışıyor — burada yazılanlar
> hangi model olursa olsun geçerli, ortak kurallardır.

---

## Proje Nedir

Laravel 12 (PHP 8.2) + Vue 3 + Inertia.js + TailwindCSS + Vite. Ek: Leaflet
(harita), Google Maps API, Spatie Permission, DOMPDF. Eyyübiye Belediyesi'nin
AYKOME (Altyapı Koordinasyon) sürecini yöneten ERP — başvurular, e-imza,
harita/CBS, süreç onay rotaları.

## KESİNLİKLE OKUMA / GITHUB'A YÜKLEME

```
node_modules/
LLM/          ← kullanıcının kendi deneysel Claude Code/API klasörleri, projeyle ilgisi yok
vendor/
pdf_evrak
Microsoft
e_imza_sorun
claude_opus
```
Bu klasörleri grep/view/exploration'a dahil etmeyin, commit'e eklemeyin.
`.gitignore`'da yoksa ekleyin.

## Kritik Mimari — Önce Bunları Anlayın

**Belge üretimi:** `app/Services/DocumentTemplateService.php` (2500+ satır) —
`fieldCatalog()` (Bilgi Alanları panelindeki tüm `{key}` token'ları
tanımlar) + `hydrateTemplateTokens()` (bu key'leri gerçek veriyle
doldurur) + `fieldValue()` (her key'in nereden okunacağı). **Yeni bir
placeholder eklerken HER ZAMAN bu 3 fonksiyona birlikte dokunun** — sadece
birini eklemek eksik/tutarsız davranışa yol açar.

**İmza yerleşimi:** `app/Services/SignerPlacementService.php` +
`SignatoryEngine.php` — "bu süreç adımı tamamlandı mı, tamamlandıysa kim
imzaladı, tamamlanmadıysa alan BOŞ kalsın" mantığını buradan çekin,
YENİDEN YAZMAYIN.

**Süreç/onay:** `app/Services/ProcessEngine.php` + `ProcessStep`/
`ProcessDefinition` modelleri + görsel editör
`resources/js/Pages/Admin/Processes/BlueprintCanvas.vue`. Her `ProcessStep`
`signature_config`, `approvable_modules`, `role_key` gibi JSON alanlar
taşır — **yeni bir süreç için MUTLAKA yeni bir `ProcessDefinition` kaydı
açın, var olan bir sürecin (örn. `on_kazi`) İÇİNE farklı bir modülün
(örn. `odeme_ust_yazi`) adımlarını EKLEMEYİN** — bu, geçmişte tam olarak
bu hataya düşülmüş bir nokta, tekrar etmeyin.

**E-imza:** `aykome-e-imza/` (Electron) — imza SADECE kriptografik ekleme
yapar, PDF'e görsel hiçbir şey ÇİZMEZ. Tüm görsel içerik (isim, tarih,
5070 yasal metni, doğrulama kodu) dompdf render'ı SIRASINDA, imzadan ÖNCE
tamamlanmış olmalı. Bu kural aylarca süren hata ayıklamayla netleşti,
bozmayın.

## Geçmiş Bağlam — Nerede Ne Var

- `docs/day/*.md` — günlük çalışma logları (13.08, 14.08, 16.08)
- `COZUM_01` ... `COZUM_11D` (proje kökü) — Claude.ai ile yapılan analiz/
  çözüm dosyaları, kronolojik sırayla okuyun, en son numaralı olan en
  güncel durumu yansıtır. **Bir şeyi "sıfırdan yapmadan önce bu
  dosyalarda daha önce çözülmüş mü diye MUTLAKA arayın** — örn.
  `AdresAyristirici.php` (ÇÖZÜM_11A §6) zaten yapılmış bir işi tekrar
  yazmayın.

## Test Disiplini (ZORUNLU, atlanmaz)

Bu projede defalarca "kod geçti, test 7/7 PASS dedi" ama GERÇEK belgede
hâlâ bozukluk çıktı. Sebep genelde: kendi yazdığınız test, kendi
(yanlış) varsayımınızı doğruluyordu. Kural:
1. Bir düzeltmeden sonra GERÇEK bir başvuru/belge ile taze test edin
   (önceden üretilmiş/eski dosyaya güvenmeyin)
2. Mümkünse BAĞIMSIZ bir araçla da doğrulayın (örn. imza için EU DSS
   validator, `openssl`; PDF için PyMuPDF ile ölçüm)
3. "Tamamlandı" demeden önce PNG/ekran görüntüsü üretip GÖZLE kontrol edin

## Zayıf/Farklı Modellerle Çalışırken

Bu projede birden fazla model (bazı oturumlarda ücretsiz/daha zayıf
modeller) art arda çalışıyor. Kesin OLMAYAN bir varsayımla geniş kapsamlı
değişiklik YAPMAYIN — önce grep ile mevcut deseni bulun, küçük bir alanda
deneyin, doğrulayın, sonra yayın. Emin olmadığınız bir mimari karar
noktasında DURUN, tahmin etmeyin, kullanıcıya sorun.

## Bilinen, Hâlâ Açık Bug'lar (COZUM_11D'den, unutulmasın)

> NOT: Bu 4 bug **d2d0441** commit'inde ÇÖZÜLDÜ — hiçbiri aktif değil.
> Aşağıda arşiv amaçlı tutuluyor; tekrar açılmaları durumunda ilgili
> satırları yeniden kontrol edin.

- `ProcessController.php:346-350` — `storeStep()` içinde ikinci `return`
  ölü kod, başarı mesajı gösterilmiyor → **d2d0441** — ikinci return silindi,
  başarı mesajı aktif.
- `ProcessEngine.php:354-358` — `LEGACY_FIELDS` map'inde olmayan
  `role_key`'ler sessizce atlanıyor → **d2d0441** + GÖREV 6 — LEGACY_FIELDS
  `buro_personeli`/`baskan_yardimcisi` güncellendi, tüm role_key'ler eşleniyor.
- `BlueprintCanvas.vue:575-578` — `closeAddStep()` düzenleme modunu siliyor →
  **d2d0441** — sadece yeni-ekleme formunu kapatıyor.
- Süreç SİLME özelliği yok → **d2d0441** — `ProcessController::destroyDefinition()`
  (satır 143) + `routes/admin.php:324`. DELETE route + controller + blade view.
