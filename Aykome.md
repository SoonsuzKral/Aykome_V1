# HGB Bilişim  AYKOME

## Proje Tanımı

HGB Bilişim  AYKOME; belediye merkezli çalışan, kurumların yetkileri doğrultusunda kazı / altyapı başvurusu açabildiği, harita üzerinden alan çizimi yapılan, ücret hesaplanan, makbuzla onaylanan, görev devri ve saha kontrolü bulunan, lisans bazlı kurumsal bir altyapı izin yönetim sistemidir.

Sistem hiyerarşisi:
**Belediye → Kurumlar (TEDAŞ, ŞUSKİ, AKSA vb.) → Vatandaş**

Temel prensip:

* Kurum personeli kendi başvurusunu açar.
* Vatandaş çoğu durumda doğrudan başvuru yapmaz; kuruma gider, başvuruyu yetkili personel açar.
* Sadece istisnai durumlarda vatandaş doğrudan belediye üzerinden işlem başlatabilir.
* Ödeme belediye veznesi / belediye yetkisi üzerinden ilerler.
* Onay süreci, ödeme ve evrak tamamlandıktan sonra verilir.

---

## İş Akışı

### 1) Başvuru Oluşturma

Kullanıcı giriş yaptıktan sonra yetkisine göre başvuru açabilir.

Başvuruyu açan roller:

* belediye personeli
* kurum personeli
* yönetici
* saha / kontrol personeli (yetkisine göre)

Başvuru sırasında şu bilgiler alınır:

* başvuru yapan kurum
* başvuru yapan kullanıcı
* vatandaş / kurum seçimi
* iletişim bilgileri
* açıklama
* kazı sebebi
* çalışma türü
* başlangıç / bitiş tarihi
* ek açıklamalar

### 2) Harita Üzerinden Kazı Alanı Belirleme

Harita ekranı **Google Maps** ile çalışır. API anahtarı `.env` içinde tutulur.

Kullanıcı:

* adres arar
* konumu bulur
* dikdörtgen / polygon / çizim aracı ile alan belirler
* alanın metrekaresini anlık görür
* kaydeder

Harita renkleri kuruma göre değişir:

* TEDAŞ: kırmızı
* ŞUSKİ: mavi
* AKSA: turuncu
* Belediye: yeşil
* diğer kurumlar: yönetim panelinden tanımlanır

Harita sol panelinde araçlar bulunur:

* adres arama
* çizim araçları
* silme / düzeltme
* alan ölçümü
* önceki çizimleri görme

### 3) Kazı Bilgileri ve Evraklar

Alan belirlendikten sonra şu veriler girilir:

* kazının amacı
* süre bilgileri
* kazı öncesi fotoğraf
* kazı sonrası fotoğraf
* makbuz yükleme
* evrak yükleme
* video yükleme
* notlar

### 4) Ücret Hesaplama

Ücret, yönetim panelinden tanımlanan kurallarla hesaplanır.

Örnek parametreler:

* kaplama türü: asfalt, beton parke, kilit taş, stabilize vb.
* m² birim fiyatı
* sabit bedel
* ek kalemler
* teminat

Kullanıcıya başvuru sırasında fiyat kırılımı gösterilmez. Yönetim ve yetkili kullanıcılar görebilir.

Örnek:

* alan: 450 m²
* kaplama türü: asfalt
* birim fiyat: 100 TL
* toplam: 45.000 TL

### 5) Ödeme ve Makbuz

Başvuru oluşturulur, ücret hesaplanır, ardından ödeme yapılır.

Akış:

* belediye veznesinde ödeme
* makbuzun sisteme yüklenmesi
* yetkili tarafından kontrol
* onay / red
* ruhsat üretimi

Makbuz yüklenmeden nihai onay verilmez.

### 6) Ruhsat / İzin Belgesi

Sistem PDF olarak kazı ruhsatı üretir.

Belge içinde yer alacak ana alanlar:

* belge numarası
* başvuru tarihi
* talebi yapan kurum
* talebi yapan kullanıcı
* alt yüklenici
* kazı sebebi
* çalışma türü
* kazı başlangıç tarihi
* kazı bitiş tarihi
* süre uzatma başlangıç / bitiş tarihleri
* kazı adresi
* açıklama

Tablo kalemleri:

* alan cinsi
* birim fiyatı (TL)
* genişlik
* uzunluk
* miktarı
* katı / katman bilgisi
* tutar (TL)

Toplamlar:

* keşif bedeli
* alan tahrip tutarı
* altyapı kazı izin harcı
* genel toplam
* teminat bedeli

Alt kısım:

* tanzim eden
* ad soyad / unvan
* tanzim tarihi
* onay
* daire başkanı imza alanı

Gönderdiğin örnek form, PDF şablonunun temel yerleşimi olarak kullanılabilir.

### 7) Görev Devri ve Saha Kontrolü

Onaylanmış başvuru saha personeline devredilebilir.

Saha aşamaları:

1. kazı öncesi kontrol ve fotoğraf
2. kazı tamamlandıktan sonra kontrol ve fotoğraf
3. zemin onarım sonrası kontrol ve fotoğraf

Her aşamada:

* fotoğraf yükleme
* video yükleme
* kamera ile çekim
* yorum / not ekleme

Görev devri yapılan kullanıcıda düzenleme ve silme yetkisi olmaz; sadece kontrol, yükleme ve durum güncelleme olur.

### 8) Bildirim Sistemi

İlk sürümde:

* uygulama içi bildirim
* SweetAlert tarzı uyarılar
* Laravel notification altyapısı

Sonraki sürümlerde:

* e-posta
* SMS
* push

Bildirim tetiklenen durumlar:

* yeni başvuru
* eksik evrak
* ödeme bekleniyor
* makbuz yüklendi
* onaylandı
* reddedildi
* görev devredildi
* süre doluyor
* saha kontrolü tamamlandı

---

## Rol ve Yetki Yapısı

### Roller

* Super Admin
* Belediye Yöneticisi
* Kurum Yöneticisi
* Belediye Personeli
* Kurum Personeli
* Saha Ekibi
* Denetçi / Görüntüleyici
* Raporlama Kullanıcısı

### Yetkiler

* kullanıcı oluştur / düzenle / sil
* rol ata
* başvuru aç
* başvuru düzenle
* başvuru sil
* başvuru incele
* başvuru onayla / reddet
* görev devret
* saha kontrol ekle
* harita çizimi yap
* fiyatlandırma tanımla
* belge üret
* arşiv görüntüle
* rapor al

Yetkilendirme için Spatie Permission mantığı önerilir.

---

## Lisanslama Yapısı

Sistem domain-IP bağımlı olmamalıdır. Bu nedenle lisans kontrolü **veritabanı tabanlı** olmalıdır.

### Lisans Mantığı

* lisans kodu
* kurum adı
* lisans başlangıç / bitiş
* aktif modüller
* kullanıcı limiti
* kurum durumu
* kullanım hakkı

### Lisans Modülü

Yönetim panelinde sadece kodlayan kişinin erişebileceği özel bir lisans sayfası olmalıdır.

Bu sayfa üzerinden:

* lisans ekleme
* lisans güncelleme
* lisans pasif etme
* kurum aktivasyon takibi
* modül açma / kapama

Lisans doğrulaması:

* sistem girişinde
* belirli aralıklarla
* kritik modüllere girişte
  kontrol edilir.

---

## Yönetim Paneli

### 1) Dashboard

* toplam başvuru sayısı
* kurum bazlı dağılım
* onay bekleyenler
* reddedilenler
* aktif kazılar
* biten kazılar
* süresi yaklaşan işler
* saha devri bekleyen işler
* aylık / haftalık grafikler

### 2) Harita İzleme

Harita ekranında:

* başvuru yapılan kazılar
* devam eden kazılar
* bitmiş kazılar
* iptal edilen kazılar
* süresi yaklaşan kazılar

Haritadaki her kazı, kurum renginde gösterilir. Tıklanınca:

* tüm form bilgileri
* görseller
* dosyalar
* görev geçmişi
* kontrol kayıtları
* ödeme durumu
* ruhsat bilgisi

### 3) Başvuru İnceleme / Onaylama

* başvuru detay ekranı
* onay / ret butonları
* ret sebebi yazma alanı
* bildirim gönderme
* geçmiş kararların kaydı

### 4) Kullanıcı Yönetimi

* kullanıcı ekle
* düzenle
* sil
* rol ata
* kurum ata
* aktif / pasif yap

### 5) Görev Devretme

* başvuruyu saha personeline devret
* personel seç
* kontrol tarihleri ata
* iş akışı izle

### 6) Raporlar

* kurum bazlı
* başvuru türü bazlı
* ödeme durumu bazlı
* onay / ret oranı
* arşiv
* saha performansı

### 7) Lisans Modülü

* kurum lisanslarını göster
* lisans süresi kontrol et
* aktif / pasif yap
* modül bazlı erişim yönet

---

## Personel Paneli

### Ana İşler

* başvuru oluşturma
* alan çizme
* evrak yükleme
* makbuz yükleme
* ücret hesaplama
* kaydet / bitir
* düzenle

### Akış Mantığı

Kullanıcı tüm adımları tamamlayana kadar aynı başvuru üzerinde geri dönüp düzenleme yapabilir. Son aşamada “Kaydet ve Bitir” ile süreç kapanır.

---

## Önerilen Teknoloji Stack

Bu proje için en modern ve işlevsel Laravel mimarisi:

* Laravel 11+
* PHP 8.3+
* MySQL / PostgreSQL
* Redis
* Queue / Scheduler
* TailwindCSS
* **Inertia.js + Vue 3**
* Alpine.js (küçük yardımcı etkileşimler için)
* AJAX / Fetch / Axios
* Google Maps API
* Spatie Permission
* Spatie Media Library
* Laravel Sanctum veya session auth

Neden Inertia.js + Vue 3:

* sayfa yenilemeden akıcı kullanım
* harita, modal, form, tablo ve filtre yapıları için güçlü
* Laravel ile backend sade kalır
* mobil / tablet uyumu daha rahat olur

---

## Veri Tabanı Tasarımı

### Kullanıcı ve Yetki

* `users`
* `roles`
* `permissions`
* `model_has_roles`
* `model_has_permissions`
* `role_has_permissions`
* `municipalities`
* `departments`
* `institutions`
* `institution_users`
* `user_profiles`

### Başvuru Süreci

* `applications`
* `application_parties`
* `application_contacts`
* `application_addresses`
* `application_status_histories`
* `application_notes`
* `application_timeline_logs`
* `application_transfers`
* `application_deadlines`
* `application_assignments`

### Harita

* `map_locations`
* `map_drawings`
* `map_geometry_vertices`
* `map_overlays`
* `map_layers`
* `map_markers`
* `map_colored_regions`

### Kazı ve Saha

* `excavation_areas`
* `excavation_measurements`
* `excavation_phases`
* `field_controls`
* `control_photos`
* `control_videos`
* `control_comments`

### Dosya ve Belge

* `documents`
* `media_files`
* `application_media`
* `receipts`
* `signatures`
* `license_documents`

### Fiyatlandırma ve Ödeme

* `pricing_rules`
* `pricing_categories`
* `pricing_items`
* `payment_invoices`
* `payment_transactions`
* `payment_receipts`
* `cashier_payments`
* `treasury_receipts`

### Bildirim ve Log

* `notifications`
* `notification_logs`
* `audit_logs`
* `archives`
* `system_settings`
* `licenses`
* `license_modules`

---

## Örnek Alanlar

### applications

* id
* institution_id
* applicant_type
* applicant_id
* created_by
* application_no
* title
* description
* work_type
* status
* start_date
* end_date
* total_area_m2
* total_price
* payment_status
* approval_status
* current_step
* is_archived
* created_at
* updated_at

### excavation_areas

* id
* application_id
* geometry_type
* geometry_data
* area_m2
* center_lat
* center_lng
* address_text
* map_provider
* created_at

### pricing_rules

* id
* institution_id
* category_name
* surface_type
* price_per_m2
* fixed_fee
* active
* effective_date

### task_assignments

* id
* application_id
* assigned_to
* assigned_by
* transfer_type
* status
* due_date
* notes
* created_at

### field_controls

* id
* application_id
* control_stage
* controlled_by
* control_result
* notes
* created_at

---

## Laravel Mimari Yapısı

### Controller Önerisi

* `Admin/DashboardController`

* `Admin/ApplicationController`

* `Admin/UserController`

* `Admin/RoleController`

* `Admin/MapMonitorController`

* `Admin/TaskTransferController`

* `Admin/ReportController`

* `Admin/LicenseController`

* `Panel/ApplicationController`

* `Panel/AreaController`

* `Panel/CalculationController`

* `Panel/ReceiptController`

* `Panel/DocumentController`

### Service Önerisi

* `ApplicationService`
* `PricingService`
* `MapDrawingService`
* `NotificationService`
* `LicenseService`
* `TaskTransferService`
* `ArchiveService`
* `ReportService`

### Form Request Önerisi

* `StoreApplicationRequest`
* `UpdateApplicationRequest`
* `StoreAreaRequest`
* `StorePricingRuleRequest`
* `StoreReceiptRequest`
* `TransferTaskRequest`

### Event / Listener Önerisi

* `ApplicationCreated`
* `ApplicationSubmitted`
* `ApplicationApproved`
* `ApplicationRejected`
* `PaymentConfirmed`
* `TaskAssigned`
* `TaskTransferred`
* `DeadlineApproaching`
* `ControlCompleted`

### Job / Queue Önerisi

* bildirim gönderme
* e-posta gönderme
* SMS gönderme
* PDF üretimi
* harita veri işleme
* zamanlanmış kontrol uyarıları

---

## AJAX / Inertia Prensibi

Tüm temel işlemler sayfa yenilenmeden yapılır.

Kurallar:

* create / read / update / delete işlemleri modal veya drawer ile yapılır
* form doğrulama anlık çalışır
* işlemler JSON / Inertia response ile döner
* hatalar satır bazlı gösterilir
* başarılar toast / alert ile gösterilir
* dosya yükleme progress bar ile izlenir

---

## Mobil ve Tablet Uyumu

Arayüz responsive olmalıdır.

### Mobil

* tek kolon yapı
* hızlı butonlar
* tam ekran harita modu
* kamera ile doğrudan fotoğraf çekme
* kısa formlar

### Tablet

* split layout
* harita + form yan yana
* saha için büyük kontrol butonları
* hızlı görev kartları

---

## Güvenlik

* CSRF koruması
* policy / gate
* rol ve izin kontrolü
* dosya yükleme validasyonu
* audit log
* soft delete
* yetkisiz erişim engeli
* oturum güvenliği

---

## Seed Verileri

İlk kurulumda seed edilmesi önerilenler:

* belediye
* kurumlar
* roller
* yetkiler
* fiyat kategorileri
* kaplama türleri
* örnek kullanıcılar
* sistem ayarları
* demo lisans

---

## Geliştirme Sırası

1. Auth ve rol sistemi
2. Belediye / kurum / lisans altyapısı
3. Başvuru ana modülü
4. Harita çizim modülü
5. Fiyat hesaplama motoru
6. Evrak ve medya yükleme
7. Ödeme ve makbuz süreci
8. Onay / ret akışı
9. Ruhsat PDF üretimi
10. Görev devri ve saha kontrolü
11. Bildirim sistemi
12. Arşiv ve raporlama
13. Dashboard ve harita izleme
14. Mobil optimizasyon
15. Testler ve loglama

---

## Proje Notları

* Sistem kurumlara satılabilir lisans modelinde olmalı.
* Google Maps API key `.env` üzerinden kullanılmalı.
* Bildirim sağlayıcıları daha sonra değiştirilebilir şekilde soyutlanmalı.
* PDF ruhsat şablonu kurum bazlı özelleştirilebilir olmalı.
* Harita renkleri ve kurum eşlemesi yönetim panelinden yönetilmeli.
* Controller içinde iş mantığı tutulmamalı; servis katmanı kullanılmalı.

---

## Claude Code / Cursor İçin Uygulama Notu

Bu projeyi oluştururken:

* modüler mimari kullan
* controller’ları sade tut
* tüm iş kurallarını service katmanına taşı
* Inertia.js + Vue 3 ile ekranları kur
* AJAX akışını bozmadan modal tabanlı CRUD oluştur
* harita çizimlerini ayrı servis ve component yapısında yönet
* lisans kontrolünü middleware ile uygula
* PDF üretimini şablon bazlı yap
* tüm önemli işlemleri logla
* kurum bazlı veri izolasyonunu gözet

Bu README, projeyi doğrudan geliştirmeye başlayacak ekip veya AI kodlayıcı için ana referans dokümandır.
