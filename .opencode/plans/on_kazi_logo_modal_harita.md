# Ön Kazı Modülü İyileştirmeleri — Uygulama Planı

## KÖK NEDENLER (keşif sonuçları)

### 1. Logo hâlâ kurum logosu
`downloadPrePermit` (ApplicationsController:1098-1143) — kullanıcının gördüğü Ön Kazı PDF'i **bu** route'tan çıkıyor:
```php
'logo_base64' => PreExcavationPermitSetting::toBase64DataUri(...) ?: $this->institutionLogoBase64($application)
```
PreExcavationPermitSetting logosu diskte yok → **başvuru kurumunun logosu** (Dicle) basılıyor. Ben önceki oturumda yalnızca `EImzaService::pdfOlustur` yolunu düzeltmiştim; bu (önizleme/indirme) yolu ayrık kaldı. Ek olarak EImzaService'te de institution fallback (392-394) "her zaman belediye" garantisini bozuyor.

### 2. Zemin modal adres + otomatik hesap
- Modal input'u `$line->address`'i basıyor (show 1841) ve Dicle verisinde DOLU ('BATIKENT, 8007'); adresi boş başvurularda boş kalıyor → kullanıcı kullanılabilir adres seçimi istiyor.
- Mevcut IIFE (show 2402-2441) yalnızca tek yön çalışıyor: Genişlik/Uzunluk → m². **Miktar girilince Genişlik/Uzunluk otomatik dolmuyor** (create mantığı yok).
- create mantığı (create.blade 1560-1564): `quantity=alan; width=length=√quantity` (karesel) — modal ile birebir uyumlu hale getirilecek.

### 3. Adres → haritada gösterme (detay)
- Detayda "Mahalle & Sokaklar" chips'leri `address_components`'tan (show 308-328), hiçbir etkileşimi yok.
- create/edit'te bu işi **maps-address.js** yapıyor (`showAddressOnMap`, `sokakAra`, `caddeKoordinat` — yerel `/maps/cadde-veri` GeoJSON motoru, internet gerektirmez). Detay sayfası bu JS'i dahil etmiyor.

### 4. Ön Kazı Düzenle butonu
- "✏️ Taslak" rozeti ZATEN var (show 528-530 → `edit-document/[on_kazi]` → DocumentTemplateController@editApplication, Word editörü). Ama Üst Yazıdaki gibi büyük "Taslağı Aç ve Başvuruya Özel Düzenle (Word)" flat butonu yok.
- Ön kazı **onay akışı zaten var** (approve-pre-excavation + Başkan Yrd. modal + $processCurrentStep rotası, show 1162-1194) — "önceki commit'lerde vardı" dediği şey mevcut; eksik olan görünür düzenle butonu.

## UYGULAMA ADIMLARI

### A. Logo — "Ön Kazı her zaman Merkez Belediye logosu"
1. `ApplicationsController::downloadPrePermit` (1136-1137): `institutionLogoBase64` fallback'ini kaldır →
   `PreExcavationPermitSetting ?? EImzaService::belediyeLogoBase64() ?? null` (blade zaten "Eyyübiye Belediyesi" yazı fallback'i var; `belediyeLogoBase64` public static yapılır).
2. `EImzaService::pdfOlustur` (392-394): pre_permit için institution fallback'i kaldır (aynı zincir, null bitiş).
3. `EImzaService` GRUP A override akışı (318-338): 3. adımdaki `institutionLogoBase64`'ü de kaldır.
4. EImzaService `belediyeLogoBase64()` `private static` → `public static` (controller'dan çağrı için).

### B. Zemin Satırları Düzenle modalı
show.blade.php:
1. **İki yönlü hesap** (mevcut IIFE genişlet):
   - `quantity` değişince: `width_m = √q`, `length_m = √q` (create 1562-1564 ile birebir — "başvurudaki gibi").
   - `width_m`/`length_m` değişince: `quantity = w × l` (mevcut mantık korunur).
   - Yeni satır ekleme sonrası da yeniden hesapla (mevcut).
2. **Adres iyileştirme**:
   - Modal içine adres kısayol chips'leri (blade'den `$application->address_components`): tıklayınca formdaki **aktif/ilk satırın** adres input'una yazarı.
   - Adres input yanına 📍 buton (tek satır): `showAddressOnMap(input.value)` — bu adresi haritada göster.
3. maps-address.js'i show.blade'e dahil et (create/edit gibi `<script src="/js/maps-address.js">`).

### C. Detay sayfası — adres chips → harita
show.blade.php (308-328 bölümü):
1. Chips'ler `<button>` olur (`data-query="BATIKENT 8013"` gibi → tam street + mahalle).
2. Yeni JS: tıkla → (a) maps-address `sokakAra`/`parseAdres`/`caddeKoordinat` ile koordinat bul → `map.flyTo` + geçici marker; (b) bulunamazsa başvurunun çizilmış polygon'larından (`haritaAreas`) en yakın/ilk bounding-box'a `fitBounds`; (c) hiçbiri yoksa toast "Bu adres için konum bulunamadı".
3. Haritaya zoomlanınca çizimler zaten haritada → kullanıcı "ne çizilmişse görür" hedefine ulaşılır.

### D. Ön Kazı Düzenle butonu (Üst Yazı tarzı)
show.blade.php:
1. Ön Kazı kartının (518-532) rozetini kaldırmadan, aynı görünümde Üst Yazı'nın büyük buton desenini (1154-1158: mavi `edit-document` butonu) Ön Kazı için ekle:
   `@if($kullaniciBelediyeMi && $canEditTemplate)` → "✏️ Taslağı Aç ve Başvuruya Özel Düzenle (Word)" → `route('admin.applications.edit-document', [$application, 'on_kazi'])` (route mevcut, editör on_kazi destekli — DocumentTemplateController@editApplication).
2. `$duzenlemeAcik` bloğundaki (1153-1159) Üst Yazı butonunun altına aynı koşulda Ön Kazı butonu (koşul: `$passedOnKazi` de false ise gösterilmez — plan: `$passedOnKazi || $duzenlemeAcik` + belediye + canEditTemplate).
   Not: Küçük "✏️ Taslak" rozeti zaten var; kullanıcı görsün diye büyük flat buton eklenecek.

### E. Doğrulama
1. `php -l` değişen PHP dosyaları; `php artisan view:cache` blade derleme.
2. `downloadPrePermit` çıktısını tinker ile üret: `data:image/jpeg` İÇERMEMELİ, belediye PNG `iVBOR` olmalı; 1274 + 1254 ikisinde de.
3. `EImzaService::pdfOlustur(1274,'pre_permit')` → JPEG XObject 0 (önceki oturumda zaten doğrulanmış; 3. adım kaldırınca tekrar bak).
4. Tinker: 1274 surfaceLines (4 satır, adres dolu) + address_components + gisCizimler → detay chip verisi hazır.
5. Manuel: show sayfası → "Ön Kazı" kartı → PDF (belediye logosu) + "Taslağı Aç" düzenleme; modal → m² yaz (w=l=√q), w/l yaz (q=w×l), adres chips + 📍.

### F. Commit + push (CLAUDE.md kuralı)
- Tek commit: "Ön Kazı: belediye logosu garanti (downloadPrePermit+EImza), modal çift yönlü m² hesabı + adres kısayolları, detayda adres→harita, Üst Yazı tarzı Ön Kazı düzenle butonu"
- `git push github main` + `git push origin main` + rev-parse doğrulama.

## Değişecek Dosyalar
- `app/Http/Controllers/Admin/ApplicationsController.php` (downloadPrePermit logo zinciri)
- `app/Services/EImzaService.php` (institution fallback kaldırma, belediyeLogoBase64 public)
- `resources/views/admin/applications/show.blade.php` (modal IIFE iki yönlü, adres chips+📍, detay adres tıklama, Ön Kazı düzenle butonu, maps-address.js dahil)