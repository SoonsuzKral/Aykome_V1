# Oturum Özeti — 7 Ağustos 2026

## Sprint 8b — CADDE/SOKAK INPUT DÜZELTMESİ (geri getirildi, fazlalıklar kaldırıldı)

### 🎯 Öz
Önceki sprint'te (8a) cadde/sokak input yapısı yanlışlıkla tamamen kaldırılmıştı. Kullanıcı düzeltmesi: **cadde/sokak inputlarını geri getir** (üst yazı tablosu için gerekli), ama **yeşil div arama bağlantılarını** (cadde yazınca harita üstünde çıkan `renderStreetJumpBar`) ve **adres inputundaki "Konum Bul" butonunu** kaldır. Yeni adres bulma algoritması henüz kurulmayacak.

### ✅ Geri Getirilenler (create + edit)
- **HTML:** "Mahalle & Sokak Listesi" bloğu (`address-components-container`, `+ Mahalle & Sokak Ekle`, hidden `address_components`)
- **JS:** `esc()`, `initAddressComponents()` — sadece veri girişi (mahalle + cadde/sokak listesini `address_components` JSON'a serileştirir). Cadde yazınca haritaya otomatik gitme YOK.
- Submit: hidden `address_components` form'da otomatik gider

### ✅ Kaldırılanlar (kullanıcının istemedikleri)
- `#btn-find-location` "📍 Konum Bul" butonu + locSpin/locPulse animasyon CSS + `flyToAnimated` JS
- `renderStreetJumpBar` yeşil div arama bağlantıları (cadde yazınca harita üstünde çıkan) + `#street-jump-bar`
- `executeSmartGeocode`, `initAddressAutocomplete`, `btn-search-address` (eski geocode/arama zinciri kalıntıları)

### ✅ Doğrulama
- `php artisan view:cache` OK
- create/edit: cadde/sokak referansı 10/10; kaldırılacaklar 0/0
- Not: Animasyon kodu maps/index'te duruyor — WMS konum bulma sistemi kurulurken oradan taşınacak

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki (BÜYÜK GÖREV)
- **WMS tabanlı nokta atışı konum bulma sistemi** — mahalle/sokak/kapı numarasına kadar. Kullanıcı algoritmayı anlatacak.

---

## Sprint 8 — KONUM YAPISI TEMİZLİĞİ + ANİMASYONLU KONUM BUL

### 🎯 Öz
Başvuru oluştur/düzenle sayfalarında eski, hatalı konum bulma yapısı (mahalle/cadde/sokak DOM'u, yeşil sokak jump butonları, eski geocode zinciri, adres autocomplete) tamamen kaldırıldı. Yerine **animasyonlu "📍 Konum Bul"** butonu + pulse marker + animasyonlu flyTo taşındı (maps/index deseni). Bu, yeni WMS konum bulma sisteminin altyapısını hazırlar.

### ✅ Kaldırılanlar (create + edit, ~668 satır)
- **HTML:** `#btn-search-address` ("🔍 Haritada Bul"), Mahalle & Sokak Listesi bloğu (`#address_components` hidden, `#address-components-container`, `+ Mahalle & Sokak Ekle`), `#street-jump-bar`, `#address-autocomplete-list`, `#map-search-input`
- **JS fonksiyonları:** `flyToSuggestion`, `parseAddressForGeocode`, `executeSmartGeocode`, `renderStreetJumpBar`, `initAddressAutocomplete` (Nominatim), `prepareAddressComponents`, `initAddressComponents`, `esc`
- **Event bağları:** autocomplete, btn-search, map-search Enter, nested cadde/sokak debounce-geocode
- `show.blade.php` readonly görüntüleme + `maps/index` original animasyonuna **dokunulmadı**

### ✅ Eklenenler (animasyon forma taşındı)
- "📍 Konum Bul" butonu + `locSpin` spinner animasyonu (click'te döner)
- `flyToAnimated(lat,lon)` — iki haritaya animasyonlu `flyTo({animate:true,duration:1})` + `locPulse` pulse turuncu marker (`loc-marker`)
- S2S `/admin/api/geocode`'i çağırır (WMS sistemi aynı endpoint'i kullanacak)

### ✅ Doğrulama
- `php artisan view:cache` OK (tüm blade derlenir)
- create/edit'te eski referanslar 0; yeni animasyon referansı 8/8
- `git diff`: toplam -668 satır, +113 satır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`
- `SESSION_SUMMARY.md`

### Sıradaki (BÜYÜK GÖREV)
- **WMS tabanlı nokta atışı konum bulma sistemi** — mahalle/sokak/kapı numarasına kadar. `maps/index`'teki ada/parsel sorgulama mantığının aynısı mahalle/cadde/sokak/kapı sorgulamasına uyarlanacak; `_harita` partial + `ApplicationsController::geocodeProxy`/proxy S2S altyapısı kullanılacak.

---

## Sprint 7 — MERKEZ BELEDİYE (VATANDAŞ) MODÜL SERBEST ERİŞİMİ

### 🎯 Öz
Alt kurum başvurularında çalışan modül PDF'leri (Tahakkuk/Metraj/Taahhütname/Ruhsat) + Düzenle butonları merkez belediye (vatandaş, `institution.is_municipality=true`) başvurularında görünmüyordu. Kök nedenler bulundu ve tek dosyada (show.blade.php) çözüldü — alt kurum akışına DOKUNULMADI.

### ✅ Kök Nedenler (Explore doğrulandı)
1. `$passedMetraj` (`show.blade.php:31`) `pre_approved` statüsünü dışlıyordu; merkez başvuruda metraj aşaması tam `pre_approved`'la geliyor (`ApplicationService::approveReceipt` is_municipality dalı → `PreApproved`) → **Metraj PDF + Düzenle gizli**.
2. Merkez Step 1 (Tahakkuk) kartında `pdf.tahakkuk` linki HİÇ yoktu — sadece tahsilat fişi/makbuz vardı.
3. PDF route'ları sunucuda çalışıyor (sadece `authorize('view')`) — sorun tamamen UI buton görünürlüğü.
4. Makbuz no inputları (`ztb_receipt_info`/`deposit_receipt_info`) merkezde de görünüyordu.
5. "Kuruma Gönder / Devret" butonu merkezde anlamsızdı.

### ✅ Değişiklikler (hepsi `show.blade.php`)
- **`$isMuniApp`** değişkeni erken tanımlandı: `(bool) ($application->institution?->is_municipality ?? false)`.
- **`$passedMetraj`** başvuru tipine duyarlı: muni'de `['draft','submitted','pending','rejected']` dışı hepsi geçer (pre_approved dahil); alt kurum eski listesini korur.
- **Step 1 (Tahakkuk):** muni'de `@if($isMuniApp || in_array(...))` ile Tahsilat Fişi & Makbuz bloğu her aşamada açık + **📄 Tahakkuk Fişi (PDF) Görüntüle/Yazdır** linki + **✏️ Tahakkuk Belgesini Düzenle (Kaydet)** (belediye personeli) eklendi. "Tahakkuk Bilgileri" formu (makbuz no) `$isInstitutionApplication()` şartına bağlandı → merkezde GİZLİ, alt kurumda kalır.
- **Step 2 (Metraj):** `@if($isMuniApp ? true : $passedMetraj)` — muni'de her aşamada PDF + Düzenle açık.
- **Step 3 (Taahhütname):** `@if($isMuniApp ? true : ($isCurrent || $isPast))` — muni'de her zaman açık.
- **Step 4 (Ruhsat):** aynı gevşetme + makbuz no formu `@if(!$isMuniApp && ...)` — merkezde GİZLİ.
- **Üst Yazı (Dilekçe)** Belge Arşivi'nde muni'de de görünür (`$isMuniApp ||`).
- **"Kuruma Gönder / Devret"** butonu `$application->isInstitutionApplication()` şartıyla merkezde GİZLİ.

### ✅ Doğrulama
- `php artisan view:cache` OK (tüm blade derlenir)
- `workflowStep('pre_approved', true)` → step 2 (Kazı Metraj) — merkez akışı doğrulandı
- Alt kurum akışına hiç dokunulmadı (`isInstitutionApplication()` şartları korundu)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/show.blade.php` (tek dosya, +40/-12)
- `SESSION_SUMMARY.md`

### Sıradaki
- Tarayıcıda merkez belediye başvurusu: Step 1→4 modül PDF'leri + Düzenle butonları, makbuz formları gizli, Kuruma Gönder butonu yok
- Alt kurum başvurusu: akış değişmediğini doğrula

---

## Sprint 6 — KAZI METRAJ TAHMİNİ (PRO) — İstatistiksel Tahmin Motoru

### 🎯 Öz
BETA→PRO: Başvuru formuna gömülü, **sıfır maliyet / offline / LLM'siz** istatistiksel metraj tahmin motoru. Kurum+mahalle adaptif hiyerarşi; veri azsa global/varsayılan düşüş.

### ✅ 1. `app/Services/ProjectForecastService.php` (YENİ)
- `predict(institutionId, mahalle, totalM2, excludeAppId)` → tam paket: toplam m² + zemin bazlı yüzde/m²/fiyat satırları + `forecast_total` + güven etiketi
- Adaptif seviyeler: **L1 kurum+mahalle** (≥3 örnek) → **L2 kurum** (≥5) → **L3 global** (≥5) → **L4 varsayılan dağılım**
- `defaultDistribution()` — SurfaceTypeSeeder gerçek adlarıyla eşleşen akıllı varsayılan (%55 asfalt, %18 parke, %12 beton, %6 stabilize, %6 toprak, %3 çim; DB'de yoksa ilk aktif tipe bakiye)
- Oracle uyumlu: ham JSON SQL fonksiyonu YOK — mahalle filtresi PHP tarafında `address_components` array'inde (yavaş ama güvenli)
- Fiyat öngörüsü AykomeMath çekirdeğiyle uyumlu (amount = m² × birim fiyat; recalculateTotals ile bağlanır)

### ✅ 2. Controller + Route
- `ApplicationsController::metrajTahmin(Request)` — validasyon (`institution_id/mahalle/total_area_m2/exclude_application_id`) + `ProjectForecastService::predict` + `AuditLogger::log('metraj.forecast', ...)` + JSON
- `POST admin/applications/metraj-tahmin` → `admin.applications.metraj-tahmin` (check-applicant yanına, resource'dan önce)

### ✅ 3. View — `partials/_metraj_tahmin.blade.php` (YENİ)
- create + edit'e `@include` (zemin kartı ile Kurum & İmza Yetkili arasına)
- "🎯 Metraj Tahmini Al" butonu → AJAX POST → sonuç kartı: güven + seviye etiketi + zemin tablosu (pay/m²/birim fiyat/öngörü) + toplam öngörü
- "♻️ Zemin Satırlarına Uygula" → global `addSurfaceLine()` satır ekler + `recalculateAll()` (mevcut çizim/satır API'sine bağlanır, yeni mimari yok)

### ✅ Doğrulama
- `php -l` service + controller + routes OK; `php artisan route:list --name=metraj-tahmin` → 1 route
- `php artisan view:cache` OK (tüm blade derlenir); partial JS `node --check` OK
- Saf istatistik mantığı birim testi: %55/%45 dengeli, tek tip %100, boş → [] — hepsi geçti
- Not: host CLI OCI8'siz olduğundan DB'li tinker çalışmadı (bilinen çevresel kısıt) — servis Eloquent sorgusu sadece; Docker Oracle'da `./oracle.sh tinker` ile `app(ProjectForecastService::class)->predict(1,'EYYÜBİYE',180)` test edilebilir

### 📁 Değişen Dosyalar
- `app/Services/ProjectForecastService.php` (yeni), `resources/views/admin/applications/partials/_metraj_tahmin.blade.php` (yeni)
- `app/Http/Controllers/Admin/ApplicationsController.php` (+`metrajTahmin`), `routes/admin.php` (+1 route)
- `resources/views/admin/applications/create.blade.php` + `edit.blade.php` (partial include)
- `SESSION_SUMMARY.md` (bu özet)

### Sıradaki
- Docker'da DB'li predict testi; tarayıcıda create/edit "Tahmini Al" → uygula akışı
- Varsayılan dağılım gerçek veriyle doldukça otomatik isabet kazanır

---

## Önceki — 2 Ağustos 2026

## Sprint 5 — EBYS 5 MADDELİK İŞ PAKETİ (Backend + Migration + View)

### ✅ 1. "Başkan Yrd. adı zorunludur" onay kilidi KALDIRILDI
- `ApplicationsController::approvePreExcavation()`: `if ($stage==='vice_mayor' && $viceMayorName==='')` → `withErrors` bloğu silindi
- Yeni akış: ad boşsa `SignatoryEngine::resolve('pre_permit', institution_id, 'belediye_baskan_yardimcisi')?->ad_soyad` (global makam ayarı) çekilir; ayar yoksa boş string → **onay asla bloke olmaz**
- `show.blade.php`: `vice_mayor_name` input'undan `required` kaldırıldı; value global ayardan prefill edilir (`$application->vice_mayor_name ?: SignatoryEngine::resolve(...)?->ad_soyad ?? ''`)
- `ProcessEngine::approve()` zaten `!empty($name)` guard'lı — boş string ile de son adım (Ön Kazı İzni) verilir

### ✅ 2. Görevi Devret listesi → tüm Eyyübiye merkez kullanıcıları
- `ApplicationsController::show()` `$fieldUsers` sorgusu: `->role('field-team')` yerine **merkez rol seti**:
  `super-admin, municipality-admin, municipality-staff, municipality-buro, municipality-sef, municipality-mudur, municipality-makam, field-team`
- Alt kurum hariç tutma: `whereNull('institution_id') OR orWhereHas('institution', is_municipality=true)` — AKSA/TEDAŞ/ŞUSKİ/TT kullanıcıları listeye girmez

### ✅ 3. Belge Arşivi'nde Ruhsat butonları gizleme (admin hariç)
- `show.blade.php` arşivde Ruhsat / Canlı Ruhsat / Eski Ruhsat kartları `@if($ruhsatVisible)` ile sarıldı
- `$ruhsatVisible = hasAnyRole(['super-admin','municipality-admin']) || in_array(status_value, ['licensed','completed'])`
- Non-admin kullanıcılar ruhsat çıktılarını yalnızca ruhsatlanmış/tamamlanmış başvuruda görür

### ✅ 4. Process/Workflow seçimi + process_id + süreç tabanlı onay rotası
- **Migration:** `2026_08_02_000001_add_process_id_to_applications_table` → `process_id` FK → `process_definitions`, `nullOnDelete`
- `Application` model: `process_id` fillable + `process()` belongsTo + `documentOverrides()` hasMany
- `create.blade.php`: süreç seçici (indigo kutu, `name="process_id"`, "— Varsayılan Süreç —" opsiyonu); `ApplicationsController::create()` → `$processes` geçiyor
- `StoreApplicationRequest`: `process_id => nullable|integer|exists:process_definitions,id`
- `ApplicationService::createDraft` → `process_id` persist; `submit()` → `steps(null, $application)` (başvurunun süreci)
- `ProcessEngine`: yeni `processFor(Application)` (process_id varsa onu, yoksa aktif süreç); `steps()` ikinci parametre olarak Application alıyor; `currentStep/nextStep/approve` başvurunun sürecini kullanır → **seçilen süreç adımlarına göre ilerler**

### ✅ 5. EBYS Taslak Motoru — Global + Başvuru Bazlı Şablon Yönetimi (REBUILD)
> 🔄 **Revizyon:** Önceki textarea/iframe WYSIWYG (`custom_document_templates`, `CustomDocumentTemplate`, `customTemplate*`, "Taslağı Aç & Word Gibi Düzenle" butonu) **tamamen silindi** — yönetici onayıyla üretim EBYS mimarisine geçildi.
> **Migration:** `2026_08_02_000010_create_global_document_templates_table` (document_type unique, content_data LONGTEXT, editor_type) + `2026_08_02_000011_create_application_document_overrides_table` (application_id FK cascade, document_type, unique[application_id,document_type])
> **Model:** `GlobalDocumentTemplate`, `ApplicationDocumentOverride` (+ `Application::documentOverrides()` hasMany)
> **Service:** `DocumentTemplateService` — TYPES kaydı (cover_letter/on_kazi = word, ruhsat/tahakkuk = excel), kaynak hiyerarşisi **override → global → blade**, blade compile + `.a4-container` DOM çıkarımı, ruhsat/tahakkuk hücre matrisi (Jexcel JSON), standalone PDF sarmalayıcı (`renderFor()`)
> **Controller/Route:** `DocumentTemplateController` — `admin/document-templates` (index/edit/update) + `admin/applications/{application}/edit-document/{documentType}` (edit/save/destroy override)
> **PDF çizim:** `downloadPrePermit`('on_kazi'), `downloadCoverLetter`, `downloadRuhsat`, `downloadTahakkuk` → önce `renderFor()` (override/global HTML dönerse `response()`), yoksa normal blade akışı (sıfır hata hedefi)
> **UI:** Sidebar "📝 Taslak / Şablon Yönetimi" (4 kutu: Üst Yazı / Ön Kazı = Word, Ruhsat / Tahakkuk = Excel) + tam ekran editör: **CKEditor 5** (A4 kağıt + Word şeridi, CDN cdn.ckeditor.com — anahtar gerektirmez) / **Jspreadsheet (jExcel)** (hücre hücre tıklanabilir, satır/sütun ekle-sil, jsDelivr CDN). Arşivde her belgeye "✏️ Taslak" (başvuruya özel override) + editörde "↺ Varsayılana Dön" (override silme)

### Doğrulama
- `php -l` OK (service + controller + models + routes + ApplicationsController); `php artisan view:cache` OK (tüm blade derleniyor)
- `php artisan route:list --name=document-templates` → 3 route (index/edit/update); `--name=edit-document` → 3 route (edit/save/destroy)
- Service'te DB'siz yol test edildi: word blade → fragment/css çıkarımı, excel grid → standalone HTML
- Migration'lar server'da çalıştırılmalı: `php artisan migrate` (OCI8 eklentisi olmayan host CLI'de çalışmaz, Docker Oracle container'ında koş)
- **Deploy notu:** Editörler CDN bağımlı (CKEditor 5 `cdn.ckeditor.com`, Jspreadsheet/jsuites + jQuery `jsDelivr`/`code.jquery.com`) — sunucuda dış ağ/CDN erişimi gerekir

### 📁 Değişen Dosyalar
- `app/Http/Controllers/Admin/ApplicationsController.php`, `app/Http/Requests/StoreApplicationRequest.php`, `app/Services/ApplicationService.php`, `app/Services/ProcessEngine.php`, `app/Models/Application.php`
- `app/Http/Controllers/Admin/DocumentTemplateController.php` (yeni), `app/Services/DocumentTemplateService.php` (yeni)
- `app/Models/GlobalDocumentTemplate.php`, `app/Models/ApplicationDocumentOverride.php` (yeni)
- `routes/admin.php`
- `database/migrations/2026_08_02_000001_add_process_id_to_applications_table.php` (yeni), `2026_08_02_000010_create_global_document_templates_table.php` (yeni), `2026_08_02_000011_create_application_document_overrides_table.php` (yeni)
- `resources/views/admin/applications/show.blade.php`, `create.blade.php`, `partials/sidebar.blade.php`
- `resources/views/admin/document-templates/index.blade.php` + `editor.blade.php` (yeni)

---

## Sprint 4 — BACKEND GEOCODING PROXY (S2S) — Tarayıcı CORS/403 Bypass

### 🎯 Mimarî Karar
- Harici sitelere (Yandex/Nominatim) **Javascript'ten sorgu ATILMIYOR** → tüm geocoding artık Laravel backend'in içinden `Illuminate\Support\Facades\Http` ile gidiyor (server-to-server)

### ✅ 1. Route (`routes/admin.php`)
- `Route::get('api/geocode', [ApplicationsController::class, 'geocodeProxy'])->name('api.geocode');` — `license:applications` grubu içinde (create/edit sayfalarıyla aynı yetki)
- URL: `GET /admin/api/geocode?q=...` · Name: `admin.api.geocode`

### ✅ 2. `ApplicationsController::geocodeProxy()` (app/Http/Controllers/Admin/ApplicationsController.php)
- `use Illuminate\Support\Facades\Http;` eklendi
- Aşama 1 — **S2S Yandex**: `Http::get("https://geocode-maps.yandex.ru/1.x/?apikey=b7500431-...&geocode=...&results=1")` → `response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos` → `explode(' ')` → `lat=coords[1]`, `lon=coords[0]`
- Aşama 2 — **Nominatim fallback**: `Http::withHeaders(['User-Agent' => 'Aykome-Eyyubiye-GIS-Backend/1.0'])` + `str_ireplace([' sokak',' sok.',' cadde'],'',$query)` (OSM ban yememek için)
- **Autocomplete LIST modu**: `?list=1&limit=6` → Nominatim çoklu sonuç `{success:true, list:[...]}` (adres öneri dropdown'ı da proxy'den)
- Her iki aşama try/catch; başarısızsa `{success:false}`

### ✅ 3. Frontend Temizliği (create + edit.blade.php)
- `executeSmartGeocode` komple sadeleştirildi: yalnızca `fetch('/admin/api/geocode?q=' + encodeURIComponent(fetchStr))` → `data.success` ise `setView([lat,lon], 19)` (appDrawMap + appCbsMap); değilse alert **"Merkezi sunucu veritabanımız / api havuzumuz adresi isabetli bulamadı."**
- `initAddressAutocomplete` fetch'i de proxy'ye bağlandı: `fetch('/admin/api/geocode?list=1&limit=6&q=...')`
- create/edit içinde **harici URL / eski Yandex key KALMADI** (`https://geocode-maps.yandex`, `https://nominatim.openstreetmap`, `3818fa95` → 0 eşleşme)

### ✅ Doğrulama
- `php -l` controller + routes OK; `php artisan route:list` → `admin/api/geocode admin.api.geocode` ✓
- Tinker S2S testi: Yandex HTTP **200** (403 yok!) — `3213 Sokak, Şıh Maksut Mahallesi, Eyyübiye` → `lat=37.14024 lon=38.787637` ✓
- Nominatim list modu: HTTP 200 + `display_name/lat/lon` önerileri ✓
- `node --check` her iki JS OK; `php artisan view:cache` OK
- Not: CLI PHP'de OCI8 eklentisi yok (DB default=oracle) → tinker'da `response()->json()` Oracle connection dener; bu çevresel, `geocodeProxy` DB'ye hiç dokunmuyor

### 📁 Değişen Dosyalar
- `routes/admin.php`, `app/Http/Controllers/Admin/ApplicationsController.php`
- `resources/views/admin/applications/create.blade.php`, `edit.blade.php`

---



## Sprint 3 — UI ROLLBACK + JITTER FIX + KEY MÜHÜRLEME (Frontend Only)

### ❌→✅ METRAJ BİLGİ KARTLARI UCUBESİ SİLİNDİ (UI Rollback)
- `#drawing-calculations` div'i (`calc_length`/`calc_width`/`calc_area`) her iki dosyadan KOMPLE silindi → sayfa orijinal stabil düzene döndü (GeoJSON + Alan m²)
- Genişlik ihtiyacı için orijinal `total_area_m2` (Alan m²) inputunun altına aynı Tailwind sınıfında ufacık `<input id="poly_width" type="number" step="0.01" min="0.01" value="1">` eklendi; `@if($isInstitutionUser) disabled readonly @endif` ile kurum kilidi korundu
- `syncCalcDisplay`/`calcWidthEl` JS'i tamamen kaldırıldı → yerine temiz `syncArea(areaM2, lineLenM)` + `poly_width` input listener'ı: `poly_width` değerini oku → length × width çarp → `input#total_area_m2`'ye PUSH (dispatchEvent('input') + recalculateAll) — ekranda custom id/kargaşa yok

### ✅ JITTER FIX — flyTo KÖKÜNDEN setView'e ÇEVRİLDİ
- `create.blade.php` + `edit.blade.php` içindeki TÜM `.flyTo(...)` çağrıları silindi → `.setView([lat, lon], zoom)` (anlık ışınlanma, titreme yok):
  - `flyToSuggestion()` → `targetMainMap.setView` + `targetCbsMap.setView`
  - `executeSmartGeocode` → `mD.setView(...)` + `mC.setView(...)`
- Diğer modüller (`maps/index`, `admin/map`, `live-map-pro`) kendi `map.flyTo`/`mapsMap.flyTo`'larını kullanıyor — dokunulmadı (ayrı modül, jitter kaynağı değil)

### ✅ YANDEX KEY MÜHÜRÜ (%100 tek key)
- `3818fa95...` eski key kod tabanında SIFIR kaldı (yalnızca dokümanlarda geçmiş kayıt)
- `executeSmartGeocode` içinde hardcoded `b7500431-b7c9-4c6b-bcb3-fcd91b3a7339` doğrulandı (create:1591, edit:1787)
- `php artisan view:clear` + `view:cache` çalıştırıldı — derlenmiş view cache'teki eski key sızıntısı temizlendi

### Doğrulama
- `node --check` create/edit extracted JS OK; `php artisan view:cache` OK
- create/edit içinde `.flyTo(` kalıntısı yok (yalnızca fonksiyon adı `flyToSuggestion` + içi setView)

---

## Sprint 2 — Yandex Nihai Geocoder Key + Echo/WS Sessizleme (Frontend Only)

### ✅ Yandex Geocoder Nihai Key Enjektesi (`executeSmartGeocode` yeniden yazıldı)
- `create.blade.php` + `edit.blade.php` içindeki `executeSmartGeocode` kullanıcının verdiği yeni zeki düzenle değiştirildi; eski hibrit çöpe atıldı
- **NİHAİ KEY:** `b7500431-b7c9-4c6b-bcb3-fcd91b3a7339` (fonksiyon içinde `const yandexApiKey`, artık global const yok — `3818fa95...` kalıntısı her iki dosyadan silindi)
- Regex zekası: `(\d+)\.(?![\s\w])` → rakam+noktayı temizler (`123.`→`123`), `SK\.|SK|SOK\.|SOK` → `" Sokak"`, `\d+` içeriyorsa "sokak/cadde" yoksa `" Sokak"` ekler
- 3 aşamalı fallback: ① Yandex (`yndxData && !yndxData.statusCode` ile 403 bypass) → ② OSM (`osmQuery` = Sokak kelimesi silinmiş rakam) → ③ mahalleye çakıl (zoom 17, `console.warn("Cadde iska, mahalleye düştü!")`)
- `mC = window.appCbsMap` da setView alıyor (Sprint 3 itibarıyla); final alert: **"Açık kaynak sistemleri cadde/mahalle lokasyonunu eşleyemedi..."**
- Key Yandex döngüsünde lat/lon tersi düzeltmesi korundu (`{lat: +spt[1], lon: +spt[0]}`)

### ✅ Pusher/Reverb Connection Error Sessizleme (`resources/js/echo.js`)
- `Pusher.logToConsole = false` (kırmızı WS spam'i kapalı), Echo `debug: false`
- `VITE_BROADCAST_CONNECTION` yoksa/`'false'` ise ya da `VITE_REVERB_APP_KEY` boşsa → `window.Echo = null` (hiç başlatma)
- `new Echo(...)` try/catch içine alındı — WS sunucusu offline/crash olursa catch atıp `window.Echo = null`
- Error mute: `window.Echo.connector.pusher.connection.bind('error', () => console.warn('WSC kapalı - Mute'))` + `state_change` sessiz
- `admin-notifications` channel dinleyicileri `if (window.Echo) { ... }` bloğuna sarıldı (null güvenli)

### Doğrulama
- `node --check` create/edit extracted JS OK; `php artisan view:cache` OK
- `npm run build` OK (echo-C22ew5T4.js 76.08 kB)

---


## Bu Oturum — Yandex Mahalle Fallback + Metraj Bilgi Kartları DOM (Frontend Only)

### 🎯 Neden
- Jump butonları Yandex'e **mahallesiz** sokak metni yolluyordu → "Şıh Maksut Mahallesindeki 3213. Sokağı" fail veriyordu
- "Genişlik (m)" input'u haritada yoktu, sadece Alt Zemin tablosundaydı → memur çarpımı göremiyordu

### ✅ Değişiklik 1 — Sokak Butonlarına MAHALLE Yedirme + Fallback
- `renderStreetJumpBar()`: her yeşil butona ana parent wrapper'ın mahalle adı gömülüyor:
  `<button class="..." data-mahalle="Şıh Maksut Mahallesi">3213. Sokak</button>` (`btn.setAttribute('data-mahalle', mahalle)`)
- Click handler artık sokak + mahalle birlikte gönderiyor (birebir kullanıcının istediği query):
  `qs = (street + " " + mahalleVal).trim() + " Eyyübiye, Şanlıurfa"`
- **Fail ise Fallback:** `fb_qs = mahalleVal + ", Eyyübiye, Şanlıurfa"` ile salt mahalle geocode → flyTo(17) + alert **"Tam sokak bulunamadı, mahallenin tam orta lokasyonuna odaklanılmıştır"**
- `searchYandexAPI()` güçlendirildi: tam sanitize zinciri (`.SK`/`.SOK`/`123SK`→ Sokak, `123.`→`123. `, whitespace collapse) + **Eyyübiye string'i query'de yoksa otomatik `"Şanlıurfa, Eyyübiye, "` prefix** (Adres Bul için de korur)

### ✅ Değişiklik 2 — METRAJ BİLGİ KARTLARI (GeoJSON textarea'nın TAM YANINA)
- `#drawing-calculations` kutusu harita altından kaldırılıp GeoJSON grid'inin SAĞ kolonuna taşındı (create:341, edit:342) → Draw Box bittiği yerde görünür
- Kartlar (dikey `space-y-3`):
  1. **Uzunluk (m)** → `#calc_length` readonly (JS polyline metrajı)
  2. **Genişlik (m)** → `#calc_width` default `1.0`, **kurum rolünde `disabled readonly`** (`@if($isInstitutionUser)`) — belediye/memurda düzenlenebilir yetki
  3. **Toplam Alan (m²)** → `#calc_area` `name="alan_m2"` readonly kalın kırmızı — Uzunluk × Genişlik reaktivitesi
- `draw:created` → `syncCalcDisplay()`: polyline LatLng distance toplamı → `calc_length`; `#calc_width` float değerini çek → **ÇARP → `.toFixed(2)` → `calc_area` (id + `name="alan_m2"`) anında push**; `#calc_width` input listener'ı aynı çarpımı yeniden yazar + `recalculateAll()`
- readonly mantığı artık `$application->institution->name` yerine `$isInstitutionUser` (create'te de doğru çalışır; "merkez Bld değilse kilit")

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`
- Backend/API/DB'ye dokunulmadı (show.blade.php'de çizim haritası/metraj DOM'u yok, kapsam dışı)

### Doğrulama
- `node --check` her iki JS OK; `php artisan view:cache` OK; duplike ID yok
- `$isInstitutionUser` Blade değişkeni her iki controller'da view'a geçiyor (create:142, edit:375)

### Sıradaki
- Manuel test: 3213. Sokak butonu → Şıh Maksut Mahallesi ile Yandex isabeti; kasıtlı hatalı sokak → mahalle merkezine flyTo + fallback alert
- Belediye: genişliği değiştir → Toplam Alan anında yeniden çarpılır; kurum: genişlik kilitli 1.0

---

## Önceki — Nominatim → Yandex Maps Geocoder MİGRASYONU (Frontend Only)

### 🎯 Neden
- OpenStreetMap (Nominatim) açık kaynak zafiyeti: Eyyübiye Batıkent arandığında Karaköprü Batıkent'e atlayabiliyor, `.SK` sokak noktaları çözülemiyor
- Sektör standardı **Yandex Maps Geocoder API**'ye kökten geçildi — API key: `3818fa95-9ec2-4532-97b3-bd4dbadbff78`

### ✅ Değişiklik 1 — `searchYandexAPI(addressText)` (create:1620, edit:1816)
- Eski `searchOSM` helper'ı tamamen kaldırıldı (her iki dosyada da 0 referans kaldı)
- Yeni fonksiyon (kullanıcının verdiği algoritma aynen):
  - `.SK` → ` Sokak`, `123SK` → `123 Sokak` temizliği
  - **Eyyübiye demir atma:** `searchString = "Şanlıurfa, Eyyübiye, " + cleanAddress` (JS içinde zorla enjekte — ikiz adres/karışıklık imkansız)
  - `https://geocode-maps.yandex.ru/1.x/?apikey=...&format=json&geocode=...&results=1`
  - Yandex `Point.pos` **"Longitude Latitude"** boşluklu string → `split(' ')` → `lon=coords[0]`, `lat=coords[1]` → Leaflet `[lat, lon]` döner
  - `data.response?.GeoObjectCollection?.featureMember?.[0]?.GeoObject` optional-chaining guard; bulunamazsa `null`

### ✅ Değişiklik 2 — Yeşil Sokak Jump Butonları → Yandex
- `renderStreetJumpBar` buton click: `await searchYandexAPI(street)` → bulunursa `flyTo([lat, lon], 18, {animate:true, duration:1.5})`, bulunamazsa alert
- Sadece sokak metni geçilir; Eyyübiye prefix'i fonksiyon içinde zorlanır

### ✅ Değişiklik 3 — "🔍 Haritada Bul" Butonu → Yandex
- `btn-search-address` click: eski 3-aşamalı Nominatim fallback zinciri (searchOSM) silindi
- Yerine: `await searchYandexAPI(rawQuery)` → her iki haritaya (appDrawMap + appCbsMap) `flyTo([lat, lon], 18, {animate:true, duration:1.5})`
- `#map-status`'a `📍 {adres} bulundu.` yazılır; bulunamazsa alert

### 🧩 Korunan (Kapsam dışı)
- `geocodeStreetSmart` + `geocodeStreetFlyTo` (Nestead Cadde/Sokak keyup debounce) Nominatim ile kaldı — sadece Jump Butonu ve Adres Bul event'leri Yandex'e bağlandı (kullanıcının istediği kapsam)
- Controller/Route/Backend/API key dosyalarına dokunulmadı

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `node --check /tmp/create_extracted.js` + `node --check /tmp/edit_extracted.js` OK
- `rg searchOSM` → 0 sonuç; `php artisan view:cache` OK

### Sıradaki
- Manuel test: `3087.SK` / `3213SK` sokak butonu → Yandex ile Eyyübiye'de nokta atışı; "Eyyübiye Batıkent" yazıp 🔍 → Karaköprü'ye DEĞİL Eyyübiye'ye gitmeli

---

## Önceki — Harita Altı Hesaplama Paneli + Yandex Geospacing v2 (Frontend Only)

### ✅ Değişiklik 1 — `#drawing-calculations` (3 Görünür Input)
- Harita altı aksiyon satırından hemen sonra eklendi (create:332, edit:333)
- Kalıp: `<div id="drawing-calculations" class="mt-4 p-4 border rounded bg-slate-50 flex gap-4">`
  - `#calc_length` → `readonly`, `type=number`, m
  - `#calc_width` → `value="1.0"`, kurumda `readonly` (`@if(isset($application) && $application->institution && !str_contains(strtolower($application->institution->name), 'merkez'))` create'te; edit'te `isset`'siz)
  - `#calc_area` → `readonly`, `style="font-weight:bold; color:red;"`, m²
- Edit'te `$application->institution` `loadMissing` ile yüklü; kurumsuz başvuruda guard null-safe

### ✅ Değişiklik 2 — `draw:created` Hesaplama Senkronu
- `L.GeometryUtil.geodesicArea()` için `leaflet-geometryutil@0.10.3` CDN script'i eklendi (jsDelivr path doğrulandı: `src/leaflet.geometryutil.js`)
- `geodesicArea(layer)`: plugin yüklüyse `geodesicArea`, yoksa `polyArea`/`rectArea` fallback (try/catch)
- `syncCalcDisplay(area, lineLen, layer)`:
  - polyline → `calc_length.value = lineLen` + `calc_area = lineLen × calc_width.value` (boşsa 1)
  - polygon/rectangle → `calc_length.value = "Çokgen"` + `calc_area.value = area`
  - Zorla `calc_area`'ya yazılır + `dispatchEvent(new Event('input'))` + `setTimeout(() => recalculateAll(), 200)`
- `#calc_width` input listener: `calc_area = calc_length × calc_width` + `recalculateAll()`

### ✅ Değişiklik 3 — Yandex Benzeri Agresif Geospacing v2
- `sanitizeStreetQuery()` regex zinciri (aynen):
  ```
  q.replace(/[\.]\s*SK/gi,' SOKAK')
   .replace(/[\.]\s*SOK/gi,' SOKAK')
   .replace(/(\d+)SK/gi,'$1 SOKAK')
   .replace(/(\d+)\.(?![\s\w])/g,'$1. ')
   .replace(/\s+/g,' ').trim()
  q.replace(/MAHALLESİ|MAH\.|MAH/gi,'Mahallesi')
  ```
- `'3213. SK'` → `'3213 SOKAK'`, `'3213SK'` → `'3213 SOKAK'`, `'3213.'` → `'3213. '`
- `geocodeStreetSmart()`: `fetchStr = q + ", Şanlıurfa, Türkiye"` (Eyyübiye kısıtlaması KALKTI), `countrycodes=tr&limit=1`; fallback zinciri korunur (mahalleli → mahallesiz)
- Sokak butonları `geocodeStreetSmart(...,18)` → yeni mantıkla çalışır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `node --check /tmp/create_extracted.js` + `node --check /tmp/edit_extracted.js` OK (jscheck.js yeniden koşuldu)
- `php artisan view:cache` OK (tüm blade derleniyor)
- Backend'e/route'a/API key'e dokunulmadı

### Sıradaki
- Manuel test: `3087.SOKAK`/`3213SK` butonu → flyTo(18); polyline çiz → Uzunluk/Genişlik/Alan kutuları anında dolu; polygon çiz → Uzunluk="Çokgen" + kırmızı Alan m²

---

## Önceki — UI Rendering Cerrahi Operasyon (3 Değişiklik — Frontend Only)

### ✅ Değişiklik 1 — Yeşil Sokak Butonları
- `renderStreetJumpBar` butonları artık: `bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-3 rounded shadow-lg border border-emerald-800 text-[11px] uppercase tracking-wide cursor-pointer` (yemyeşil, harita tavanından kopuyor)

### ✅ Değişiklik 2 — Yandex Benzeri Akıllı Geospacing
- `sanitizeStreetQuery()`: `'3087.SOKAK'` → `'3087. SOKAK'` (`(\d+)\.` → `$1. `), `sokak`→`Sokak`, `cadde.*`→`Cadde`, boşluk temizliği
- `geocodeStreetSmart()`: önce `"[Sokak], [Mahalle] Eyyübiye Şanlıurfa"` ara → bulamazsa **mahalleyi çıkarıp** `"[Sokak] Eyyübiye"` ile tekrar ara (fallback zinciri)
- Sokak butonları (`geocodeStreetSmart(...,18)`) ve debounce'lu cadde araması (`geocodeStreetFlyTo`) bu mantığı kullanıyor

### ✅ Değişiklik 3 — Polyline Matematiği HTML'e ÇAKILIYOR (EN KRİTİK)
- Polyline branch'inde genişlik: kurum `1` / belediye input değeri / boşsa **varsayılan 1** (artık 0 kalmaz)
- Çizim sonrası **Defansif DOM yazımı** (renderTable + serializeAndSync sonrası):
  - `.row-width` → kurumda `value=1.00` + `readOnly=true`, belediyede `readOnly=false` + değer
  - `.row-length` → `.value = length_m`
  - `.row-quantity` → `.value = quantity` (Genişlik x Uzunluk)
  - `#total_area_m2` (Alan m²) → `.value = quantity` ZORLA
  - `.dispatchEvent(new Event('input'))` → reaktif tetik
- `recalculateAll()` artık "Hesaplanan tutar" panelini (`#surface-total-display`) de güncelliyor (M²*Fiyat anında)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK; JS syntax check (node --check) her iki dosya için OK
- Backend'e/route'a/API key'e dokunulmadı

### Sıradaki
- Manuel test: 3087.SOKAK butonu → flyTo; polyline çiz → width/length/miktar/alan kutuları anında dolu

---

## Önceki — Harita Arayüz Devrimi (3 Efsane Adım — UI/JS)

### ✅ Adım 1 — Dinamik Sokak Uçuş Butonları (Quick-Jump)
- `#street-jump-bar` (absolute top-2 left-10 right-2 z-[1000] flex gap-2) haritanın TAM tepesine eklendi
- `renderStreetJumpBar()`: Adres JSON bloğundaki her sokak → şeffaf/kaydırılabilir chip buton (keyup'da + cadde ekle/sil'de güncellenir)
- Tıklayınca gizli Nominatim sorgusu `"[Sokak], [Mahalle] Eyyübiye, Şanlıurfa, Türkiye"` → `flyToSuggestion(.., 18)`

### ✅ Adım 2 — Polyline Renk Seçici
- `#draw_color_picker` (input type=color) Leaflet `L.Control` olarak sağ üste eklendi
- `buildDrawControl()` + `refreshDrawControl()` refactor: renk değişince draw toolbar yeniden kurulur → yeni çizimler seçilen renkle çizilir
- Mevcut overlay'ler `repaintOverlays()` ile boyanır; kurum değişince picker kurum rengiyle senkronize edilir

### ✅ Adım 3 — Uzunluk x Genişlik = Miktar (m²) Tam Senkron
- Polyline çizilince `row.length_m = lineLen` (gerçek metre), kurumda `width=1` / belediyede input değeri
- `row.quantity = length × width` → `renderTable()` + `recalculateAll()` (fatura anında M²*Fiyat)
- **`serializeAndSync` poliline branch'i**: `layer._rowId` ile satır eşleşip `totalArea += row.quantity` → **Alan (m²) inputu `total_area_m2` artık polyline metrajını yansıtıyor** (düzenleme/silme dahil)

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK; JS syntax check (node --check) her iki dosya için OK
- Backend'e dokunulmadı

### Sıradaki
- Tarayıcıda kurum + belediye kullanıcısı ile manuel test: sokak butonları, renk seçici, polyline → length/width/area senkronu

---

## Önceki (2 Ağustos sabah) — Başvuru Formu UX (5 Maddelik İş Paketi)

### ✅ Tamamlanan
1. **Adres zorunluluğu kaldırıldı** — Doğrulandı: `StoreApplicationRequest`'te `address_text` zaten `nullable`, migration kolonu zaten `nullable`, HTML'de `required` yoktu. Ek değişiklik gerekmedi.
2. **Polyline uzunluğu → Length_m enjeksiyonu** (create + edit `draw:created`)
   - `lineLen = polyLen(layer.getLatLngs())` hesaplanır, aktif satırın `length_m`'ine yazılır
   - `rowDrawings[rowId]` artık `LineString` GeoJSON'u da saklıyor (`shape:'polyline'`)
   - Polyline için `quantity = length × width` reaktif hesap
3. **Rol bazlı genişlik kilidi (kurum = 1m readonly)**
   - `renderTable()`: `isInstitutionUser` ise width input `readonly` + `value="1.00"`
   - `addSurfaceLine()`: kurum için `width_m = 1` varsayılan
   - `row-quantity` handler: kurumda width hep 1 kalır, `length = qty/1`
   - Polygon çizimi: kurumda `width=1, length=area`; belediyede `sqrt(area)` (eski davranış)
   - `prepareSurfaceLinesForSubmit()`: kurumda width submit'te `1` olarak zorlanır
   - `initInstitutionWatcher` → kurum değişince `renderTable()` (readonly senkron)
4. **Google-tarzı autocomplete arama** (`initAddressAutocomplete`)
   - `#address_text` 500ms debounce + Nominatim (`countrycodes=tr&limit=6`)
   - `< 4` karakterde istek atılmaz; dropdown `#address-autocomplete-list`; seçimde `flyToSuggestion(…, 18)`
   - Escape / dış tıklama ile kapanır; stale istek `_seq` guard ile engellenir
5. **Mahalle/Cadde bloğunda haritaya gösterme**
   - `.comp-street` input'larına 700ms debounce → `geocodeStreetFlyTo()` Nominatim → her iki haritaya flyTo (17)
   - `flyToSuggestion()`: `window.appDrawMap` (çizim) + `window.appCbsMap` (CBS referans) hedef alır

### 📁 Değişen Dosyalar
- `resources/views/admin/applications/create.blade.php`
- `resources/views/admin/applications/edit.blade.php`

### Doğrulama
- `php artisan view:cache` OK (tüm blade derleniyor)
- Backend matematiği / fiyat mantığı / UI dokunulmadı — sadece frontend JS + HTML

### Sıradaki
- Tarayıcıda kurum (Dicle/ŞUSKİ…) ve belediye kullanıcısı ile manuel test: polyline → length, width kilidi, autocomplete, cadde flyTo
- `/maps` (CBS Entegrasyon) modülüne devam edilebilir

---

## Önceki (31 Temmuz)
- ECDSA Kamu SM Token ile E-İmza (PKCS#11, Apple Silicon/Rosetta 2)
- Electron + Laravel entegrasyonu, x86_64 bridge, PAdES imzalama

## Bu Oturum — Docker + Oracle + Web Sunucu Ayağa Kaldırma

### ✅ Tamamlanan
1. **Görev 1 — Oracle + E-İmza Migration Yaması**
   - SQLite tamamen terk edildi, default bağlantı `oracle`
   - `2026_07_31_000001_eimza_oracle_yamasi` migration Oracle'da Ran (batch 1014)
   - E-imza tabloları Oracle'da mevcut
2. **Görev 2 — CSS Fix'leri**
   - `layouts/admin.blade.php` → Leaflet z-index override
   - İptal modalına `cancel_document` input + `enctype="multipart/form-data"`
   - Butonlar `bg-blue-600 text-white font-bold` + inline style (kullanıcı zorunluluğu)
3. **Görev 3 — Çoklu Cadde Kaybı Araştırması**
   - Sonuç: mevcut kod zaten doğru — `prepareAddressComponents()` create+edit'te `querySelectorAll('.comp-street')` ile TÜM sokakları topluyor; backend (ApplicationsController 171-178, 443-450) JSON decode ediyor; show + cover_letter tüm sokakları render ediyor
4. **Görev 5 — Docker Compose + Web Sunucu**
   - `docker-compose.yml` güncellendi: oracle, redis, php, serve, reverb, adminer
   - Ortak env YAML anchor `&php-env`: DB_HOST=oracle, DB_DATABASE=FREEPDB1, DB_USERNAME=aykome_user, DB_PASSWORD=aykome123, REDIS_HOST=redis
   - serve: host `8001` → container `8000` (`php artisan serve --host=0.0.0.0 --port=8000`)
   - reverb: `8090`, adminer: `8080`, php: `tail -f /dev/null`
   - Tüm container'lar Up: `aykome-v6-oracle` (healthy), redis, php, serve, reverb, adminer

### 🐛 Kritik Çözüm — HTTP 500 / ORA-12541
- **Belirti:** `GET http://127.0.0.1:8001/login` → HTTP 500; log'da `oci_connect(): ORA-12541: TNS:no listener` (Oci8.php:145)
- **Kafa karışıklığı:** serve container'ı İÇİNDE CLI çalışıyordu (`migrate:status` OK, manuel `oci_connect` OK), hatta Http\Kernel ile simüle `/login` 200 döndü — ama gerçek HTTP isteği 500 veriyordu
- **Kök neden:** `php artisan serve` süreci başladığında eski config cache (host=127.0.0.1) kullanıyordu; `config:clear` sonrası serve süreci config'i yeniden okumuyordu
- **Çözüm:**
  1. Host'ta eski OCI8'siz `php artisan serve --port=8001` (PID 1774) → `kill 1774` (8001'i OrbStack'e bıraktı)
  2. `docker exec aykome-v6-serve php artisan config:cache` (host=oracle doğrulandı)
  3. `docker restart aykome-v6-serve`
- **Sonuç:** `login:200`, `admin:302` (auth redirect normal), `migrate --force` → "Nothing to migrate"

### Dosya Yapısı
| Dosya | İşlem |
|---|---|
| `docker-compose.yml` | Oracle + Redis + PHP + serve + reverb + adminer servisleri |
| `database/migrations/2026_07_31_000001_eimza_oracle_yamasi.php` | Oracle yaması (Ran) |
| `resources/views/layouts/admin.blade.php` | Leaflet z-index fix |
| `resources/views/admin/applications/show.blade.php` | İptal modalı + buton fix'leri |

### Sıradaki (Görev 4)
- ~~"Kuruma Gönder (Devret)" vs "Saha Personeline Ata" ayrımı~~ **TAMAMLANDI** (aşağıda)
- E-imza akışını 8001 üzerinden Electron ile test et (Electron main.js `server_url: http://127.0.0.1:8001`)

### ✅ Ek — Görev 4: Kuruma Devret (transferToInstitution)
- **Akış:** Başvuruyu kullanıcıya değil bir kuruma (institution) devreder — `institution_id` güncellenir
- **Route:** `POST admin/applications/{application}/transfer-institution`
- **Controller:** `transferToInstitution()` — authorize + validate + `institution_id` güncelle + timeline log (`institution.transferred`) + AuditLogger (`application.transfer_institution`)
- **Policy:** `transferToInstitution` — belediye yönetimi her başvuruyu, kurum personeli sadece kendi kurumunun başvurusunu devredebilir
- **View:** show.blade.php'ye "Kuruma Devret" butonu + `transfer-institution-modal` (kurum seçici, `bg-sky-600`)
- **Doğrulama:** başvuru 930 kurum 1→2→1 devri başarılı; timeline + audit kayıtları oluşuyor

### 🐛 Bonus Fix — AuditLogger Oracle'da sessizce çöküyordu
- **Belirti:** AuditLog hiç kaydedilmiyordu (`application.transfer_institution` görünmüyordu)
- **Kök neden:** `AuditLog::$timestamps = false` → Laravel `created_at` göndermiyordu → Oracle `ORA-01400: cannot insert NULL into AUDIT_LOGS.CREATED_AT` → AuditLogger `catch (Throwable)` ile sessizce yutuyordu
- **Çözüm:** `$timestamps = false` → `public const UPDATED_AT = null` (Eloquent `created_at`'i kendisi yönetir)
- **Etki:** Tüm audit log akışları (giriş, onaylar, transferler, vs.) artık gerçekten yazılıyor

## Ortam
- **Geliştirme:** macOS (Apple Silicon) + OrbStack; hedef sunucu Windows Server 2025 + Docker
- **Oracle:** `aykome_user/aykome123@FREEPDB1` (port 1521), ağ `aykome_default`
- **Kullanıcı tercihleri:** butonlar `bg-blue-600 text-white font-bold` + inline style; modallar `z-[99999] fixed`; tüm metinler Türkçe
