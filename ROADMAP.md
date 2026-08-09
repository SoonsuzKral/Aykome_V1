# HGB Bilişim  AYKOME — Geliştirme Yol Haritası

## Tamamlananlar

### Saha Takip Job + Scrollbar Düzeltmeleri — 2026-04-09
- ✅ `CheckFieldStaffStatus` Job oluşturuldu: `last_seen_at < now()-2dk` olan is_on_field=1 kullanıcıları otomatik pasife düşürür
- ✅ `routes/console.php`'e `Schedule::job()->everyMinute()` ile dakikada bir çalışacak şekilde eklendi
- ✅ Controller'daki zombi temizliği artık yedek; asıl temizlik scheduler Job'ı üzerinden
- ✅ Panel listelerinde `scrollbar-hide` CSS ile webkit scrollbar tamamen gizlendi
- ✅ Personel kartları premium dashboard stiliyle güncellendi (gölge, renk kodlu badge, pill uygulama bloğu)

### Canlı Saha İzleme PRO (v4) — 2026-04-09
- ✅ Google Maps AdvancedMarkerElement entegrasyonu
- ✅ İki sekmeli panel: Canlı Aktifler / Son Görülenler
- ✅ Polling (30 sn aralıklı veri güncelleme)
- ✅ GLightbox galeri entegrasyonu (InfoWindow fotoğrafları)
- ✅ Başvuru numarasına tıklanabilir link (InfoWindow)
- ✅ Mobil responsive: harita üstte 2/3, panel altta 1/3

### CSS Flexbox Yükseklik Hatası Çözüldü — 2026-04-09
- **Sorun**: `main { flex:1 }` Tailwind class'ı, `height:calc(100vh-56px)` override'ını eziyordu.
  Harita altında footer boşluğu kalıyordu.
- **Çözüm**: Tüm parent zinciri (`html → body → div.flex → div.flex-col → main`) CSS `:has()`
  selektörleriyle `height:100%; overflow:hidden` kısıtlandı. `#live-map-wrap` artık
  `height:100%` ile parent'ını dolduruyor, `calc(100vh - 56px)` sabit değerine bağımlı değil.

### Zombi Mesai Durumu Çözüldü — 2026-04-09
- **Sorun**: Sahacı "Mesai Başla" deyip uygulamayı kapattığında günlerce Aktif listesinde kalıyordu.
  `whereNull('last_seen_at')` loophole'u eski check-in kayıtlarını filtrelemiyordu.
- **Çözüm (çok katmanlı)**:
  1. `liveData()` her çağrıldığında `is_on_field=true` ama 10 dk'dan eski olan kullanıcıları
     DB'de otomatik `is_on_field=false, current_lat/lng=null` yapan batch cleanup eklendi.
  2. Aktif kullanıcı filtresi sıkılaştırıldı: `last_seen_at >= 5 dk` VEYA
     `last_seen_at IS NULL AND field_started_at >= 5 dk` (taze check-in, ping henüz gelmedi).
  3. `updateLocation()` her GPS ping'inde `last_seen_at=now()` yazıyor.
  4. Checkout'ta `last_seen_at` her zaman yazılıyor (lat/lng yoksa bile).

### God-Mode Yetki Sistemi ve Mobil Kamera — 2026-04-09
- ✅ `AykomeSeeder` granüler yetki sistemi: `system.*`, `pro.*`, `field.*` gruplarıyla 8 yeni permission eklendi
- ✅ `municipality-admin` → `pro.live_map`, `pro.work_orders`, `pro.advanced_reports` eklendi
- ✅ `field-team` → `field.tasks_view`, `field.upload_media` eklendi (field.upload geriye dönük bırakıldı)
- ✅ `roles/index.blade.php` God-Mode panel: tüm rol/izin çapraz matrisi, kategori grupları, renk kodlu satırlar
- ✅ `roles/edit.blade.php` gruplu checkbox sistemi: kategori başlıkları, Tümünü Seç/Kaldır, Tümünü Seç/Temizle
- ✅ `sidebar.blade.php` PRO modüller artık `@can` (permission-based) görünürlük kullanıyor; `@hasrole('field-team')` kaldırıldı
- ✅ Saha personeline `admin/my-tasks` rotası: `FieldTeamScope` ALLOWED listesine eklendi, `field.tasks_view` permission guard
- ✅ `MyTasksController` + `my-tasks/index.blade.php`: "Bana Atanan Görevler" ve "Geçmiş İşlerim" iki sekmeli mobil görünüm
- ✅ `field-tasks/inspect.blade.php` upload butonu yeşil + büyük + `📸 Fotoğraf Çek ve Yükle` metni
- ✅ `docs/index.blade.php` "Roller ve Yetki Matrisi" bölümü: tüm permission'lar ve roller çapraz tablo

---

## Devam Eden / Planlanan

- [ ] Saha personeli mobil uygulaması GPS arka plan takip
- [ ] Webhook / push notification: personel sahadayken görev atandığında anlık bildirim
- [ ] Harita üzerinde bölge / ilçe bazlı filtreleme
