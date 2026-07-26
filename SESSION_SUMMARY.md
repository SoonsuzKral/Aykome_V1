# Oturum Özeti — 26 Temmuz 2026

## Görev
PDF Blade view'ların 5 ana kusurunu düzeltmek

## Yapılanlar

### 1. ARAYÜZ — Print Bar + Gölgeli A4 Preview
- `resources/views/admin/pdf/pdf_layout.blade.php` oluşturuldu — **tüm PDF view'ları saran master layout**
- Üstte sabit koyu bar (`#1e293b`): solda "Kapat" butonu, sağda "🖨️ Yazdır / PDF Kaydet" butonu
- `window.print()` butona bağlandı
- `@media print` ile bar tamamen gizleniyor
- Sayfa beyaz zemin üzerinde gölgeli (`box-shadow`) ortalanmış A4 preview
- Tüm 5 view (`pre_permit`, `cover_letter`, `ruhsat`, `metraj`, `tahakkuk`) layout'u kullanacak şekilde yeniden yazıldı

### 2. Cover Letter — Tablo Border
- `.mahalle-tablo td`'ye `border: 1px solid #000` eklendi
- Sokak/mahalle listesi hücre içinde nizami görünüyor

### 3. Statik Adres Verisi → Dinamik
- `buildCoverLetterStreets()` helper'ı tamamen yeniden yazıldı:
  - Artık **hiçbir hardcoded sokak/mahalle adı içermiyor**
  - `$application->gisCizimleri.yolIliskileri` üzerinden gerçek veri çekiyor
  - `$application->gisNoktalari` üzerinden mahalle/parsel bilgisi çekiyor
  - `$application->address_text` fallback olarak kullanılıyor
  - Hiçbir veri yoksa boş array dönüyor
- **Application modeline 2 yeni relationship eklendi:**
  - `gisNoktalari()` → `HasMany(GisBasvuruNokta, 'basvuru_id')`
  - `gisCizimleri()` → `HasMany(GisCizim, 'basvuru_id')`
- Controller'da `downloadCoverLetter` eager loading'i güncellendi

### 4. Ruhsat — Font/Kalınlık/Border
- Tüm tablo `th`'lere `font-weight: bold` ve `color: #000` eklendi
- Tablo border kalınlığı `1.5px solid #000` yapıldı (eski 1px)
- İmza kutuları border'ı da `1.5px` yapıldı
- Tüm metin siyahlığı belirginleştirildi

### 5. E-Devlet/Doğrulama İbareleri Temizliği
- `resources/views/admin/pdf/` altındaki **TÜM dosyalar** tarandı:
  - "Belge Doğrulama Kodu" ❌ silindi
  - "5070 sayılı elektronik imza" ❌ silindi
  - "güvenli elektronik imza" ❌ silindi
  - "Belge Takip Adresi" ❌ silindi
  - QR kod alanları zaten yoktu
- **Hiçbir view'da doğrulama/elektronik imza ibaresi kalmadı**
- Footer: sadece belediye iletişim adresi ve imza

## Dosya Değişiklikleri
| Dosya | İşlem |
|---|---|
| `resources/views/admin/pdf/pdf_layout.blade.php` | **YENİ** — Master layout (print bar + preview) |
| `resources/views/admin/pdf/pre_permit.blade.php` | Layout'a geçirildi, e-devlet metni silindi |
| `resources/views/admin/pdf/cover_letter.blade.php` | Layout'a geçirildi, tablo border eklendi |
| `resources/views/admin/pdf/ruhsat.blade.php` | Layout'a geçirildi, border/font kalınlaştırıldı |
| `resources/views/admin/pdf/metraj.blade.php` | Layout'a geçirildi |
| `resources/views/admin/pdf/tahakkuk.blade.php` | Layout'a geçirildi |
| `app/Models/Application.php` | `gisNoktalari()`, `gisCizimleri()` ilişkileri eklendi |
| `app/Http/Controllers/Admin/ApplicationsController.php` | `buildCoverLetterStreets` dinamik yapıldı; eager loading güncellendi |

## Sıradaki
1. Browser'da test: `/admin/applications/864/pdf/pre-permit` — print barı kontrol et
2. Sırayla cover-letter, ruhsat, metraj, tahakkuk
3. Görsel fark varsa düzelt
4. Logo havuzunu genişlet (kullanıcı tüm kurum logolarını sağlayacak)
