# AYKOME — TEKNİK ALTYAPI ŞARTNAMESİ

**Hazırlayan:** HGB Bilişim Sistemleri Tic. Ltd. Şti.  
**Proje:** AYKOME (Altyapı Yönetim ve Koordinasyon Merkezi) v6 Ultra  
**Versiyon:** 5.0  

---

## 1. ÇERÇEVE VE ÇALIŞMA ORTAMI

- Sistem, Laravel 12.x framework'ü üzerine inşa edilecektir.
- PHP 8.2 veya üzeri bir sürüm ile çalıştırılacaktır.
- Tüm backend iş mantığı MVC (Model-View-Controller) mimarisine uygun şekilde geliştirilecektir.
- PSR-4 otoloading standardı ile sınıf yüklemesi yapılacaktır.
- Uygulama adı "AYKOME" olarak tanımlanmıştır.

## 2. VERİ TABANI YÖNETİM SİSTEMİ

- Uygulama veri tabanı olarak Oracle ve MySQL kullanılacaktır.
- Karakter seti utf8mb4, collation utf8mb4_unicode_ci olarak yapılandırılacaktır.
- Strict mod aktif tutulacak, hatalı sorgular anında bildirilecektir.
- Tüm veri tabanı işlemlerinde Eloquent ORM kullanılacak, ham SQL sorgularından kaçınılacaktır.
- Migration dosyaları ile sürüm kontrollü şema yönetimi zorunludur.
- Veri tabanı adı "aykome" olarak belirlenmiştir.

## 3. ÖNBELLEK KATMANI

- Varsayılan önbellekleme sürücüsü veri tabanı (database) olarak yapılandırılacaktır.
- Yüksek performans gerektiren senaryolarda Redis (phpredis istemcisi ile) önbellek sürücüsü devreye alınabilecektir.
- Redis Cluster desteği mevcuttur.
- Redis bağlantılarında decorrelated_jitter geri çekilme algoritması ile hata toleransı sağlanacaktır.
- Maksimum 3 yeniden deneme (max_retries) ile bağlantı dayanıklılığı artırılacaktır.
- Yetkilendirme önbelleği (Spatie Permission) 24 saat TTL ile çalışacak, rol/izin değişikliklerinde otomatik flush edilecektir.
- Memcached alternatif önbellek sürücüsü olarak tanımlanmıştır.
- Failover mekanizması ile (database → array) öncelikli önbellek düştüğünde yedek devreye girecektir.

## 4. OTURUM YÖNETİMİ

- Oturum (session) verileri veri tabanında saklanacaktır (sessions tablosu).
- Oturum ömrü 120 dakika olarak yapılandırılacaktır.
- Oturum çerezleri HttpOnly ve SameSite=Lax parametreleri ile işaretlenecektir.
- XSS ve CSRF saldırılarına karşı koruma sağlanacaktır.
- Oturum şifrelemesi (encryption) varsayılan olarak kapalıdır, gerekli durumlarda aktifleştirilebilecektir.
- Oturum çerezi adı "aykome-session" olarak belirlenmiştir.
- Oturum yol (path) varsayılanı "/" olarak tanımlanmıştır.

## 5. KUYRUK SİSTEMİ VE ARKA PLAN İŞLEMLERİ

- Uzun süreli ve eşzamansız işlemler için kuyruk (queue) altyapısı kullanılacaktır.
- Varsayılan kuyruk sürücüsü veri tabanı (database) olarak ayarlanmıştır.
- İş tekrar deneme süresi 90 saniye olarak yapılandırılmıştır.
- Gelişmiş senaryolarda Redis kuyruk sürücüsüne geçiş yapılabilecektir.
- Başarısız işlemler (failed jobs) UUID tabanlı olarak veri tabanında loglanacaktır.
- Job batching (toplu iş takibi) için job_batches tablosu kullanılacaktır.
- Kuyruk dinleyici (queue:listen) tek deneme (--tries=1) ve zaman aşımı olmadan (--timeout=0) çalıştırılacaktır.
- Deferred ve background sürücüleri alternatif kuyruk mekanizmaları olarak tanımlanmıştır.

## 6. GERÇEK ZAMANLI İLETİŞİM (WEB SOCKET)

- Gerçek zamanlı veri akışı ve canlı saha takibi için Laravel Reverb WebSocket sunucusu kullanılacaktır.
- İstemci tarafında Laravel Echo (Pusher protokolü) ile bağlantı kurulacaktır.
- WebSocket bağlantıları için 60 saniye ping aralığı tanımlanacaktır.
- 30 saniye aktivite zaman aşımı (activity timeout) uygulanacaktır.
- Rate limiting (hız sınırlama) desteği mevcut olup, dakikada maksimum 60 istek ile sınırlandırılabilecektir.
- Tüm originlerden (allowed_origins: *) bağlantı kabul edilecektir.
- Client event'leri sadece "members" rolündeki kullanıcılardan kabul edilecektir.

## 7. KİMLİK DOĞRULAMA VE YETKİLENDİRME

- Sisteme yetkisiz erişimleri engellemek adına, ileri düzey şifrelenmiş kimlik doğrulama ve güvenli oturum yönetimi standartları kullanılacaktır. Uygulama dışından gelecek veya dış servislerle yapılacak haberleşmeler güvenlik sertifikasyonlarından (şifreli geçiş izni/jeton mimarisinden) geçirilecektir.
- Uygulama bütününde tam bir Rol ve İzin Tabanlı Erişim (RBAC) hiyerarşisi kurulacaktır. "Tam Yetkili Sistem Yöneticisi" haricindeki hiçbir kullanıcı/personel; yetkisinde olmayan bir ekrana erişemeyecek, ilgili modülün butonlarını göremeyecektir.
- Kullanıcı ve kurum yetkilerine özel lisans denetim kalkanı konulacak, kuruma atanmamış ek hizmet (modül) erişimleri sistem tarafından otomatik engellenecektir.
- Kişisel verilerin gizliliği ve veri güvenliği kalkanı (Veri İzolasyonu) uygulanacaktır. Örneğin, sadece sahadaki görevlere tahsis edilmiş bir donanım/ekip personeli, kuruma ait dev veritabanı veya başvuruları tarayamayacak, sadece doğrudan kendi sorumluluğundaki aktif iş emrini/adresini görebilecektir.
- Yeni eklenen kullanıcı hesaplarının güvence altına alınması için e-posta güvenlik doğrulama adımı zorunlu kılınacaktır.
- Kullanıcı hesaplarına veya şifre kırmaya yönelik otomatik denemeleri (siber/bot saldırılarını) önlemek adına, parola sıfırlama taleplerine ardışık zaman bekleme kuralı konulacaktır. Sistem tarafından iletilen şifre sıfırlama onay bağlantıları en fazla 60 dakika içinde iptal edilerek kendini imha edecektir.

## 8. ŞİFRELEME VE GÜVENLİK

- Sisteme girilen uygulama ve kayıt anahtarları, endüstri standardı ve bankacılık düzeyinde veri güvenliği sunan çok gelişmiş kriptolama algoritmaları (256-bit teknoloji vb.) ile güvence altına alınacaktır.
- Kullanıcılara ve personellere ait şifreler, sistemin arka planı ve veritabanı dahil hiçbir platformda veya dosyada kesinlikle 'açık (okunabilir) metin' olarak tutulmayacak, çok güçlü ve kırılması imkansız tek yönlü karartma algoritmaları (Hash vb.) ile geri döndürülemez biçimde saklanacaktır.
- Kötü niyetli dış servislerin yetkisiz hareket başlatmasını (saldırısını) engellemek adına; sistem üzerindeki her türlü Veri Ekleme, Form Kaydetme, Güncelleme ve Silme hareketine özgün eşsiz jeton doğrulama kuralları (siteler arası sahtecilik bariyeri/koruması) zorunlu kılınacaktır.
- Uygulama ekranı ve ana veritabanı birbirleriyle veri iletişimini güvenli katman sertifikasyonuna uygun kapalı bağlantılar (Güvenli Sertifika Protokolleri) çerçevesinde gerçekleştirecek olup veri dinleme tehlikesine son verilecektir.
- Kullanıcı giriş (Oturum Çerezleri - Session Cookie) bağlantıları kullanıcının dış sitelerdeki tıklamalarından veya zararlı tarayıcı korsan komutlarından kopya veya etki alamayacak; güvenli web sınırlandırmasına tabii kılıp hırsızlıklara geçit verilmeyecektir.
- Olası istisnai hata ya da arızalarda "Güvenli Karartma ve Saklama Mimarisi" çalışacaktır: Hatalı işlem uyarılarında ya da izin aşım denemelerinde ekrana kesinlikle uygulamanın veritabanı hiyerarşisine (roller veya kurum izni tiplerine) yahut alt sunucu mekaniğine dair (sunucu sistem hatası satırı) hiçbir krtitik / teknik isim ifşa (baskı) ettirilmeyecek ve daima sade sistem mesajıyla idarenin uygulama iskelet mahremiyeti yüksek güvenlik altında dış çevreye kapatılacaktır.

## 9. ÖN YÜZ TEKNOLOJİSİ VE DERLEME

- Sistem, ekran tasarım kalitesini en üst düzeyde tutmak amacıyla güncel ve yüksek hızlı modern arayüz mimarileri (UI/UX) ile geliştirilmiş olacaktır. Tüm veri giriş formları, menüler ve tablolar çağın gereklerine uygun, standart, kurumsal ve şık bir görünüme sahip olacaktır.
- Kullanıcının yazılım içerisindeki sayfa veya modül geçişleri, ekranın ve sayfanın baştan yüklenmesini beklemeden arka planda (Single Page Application/kesintisiz deneyim mimarisi ile) anlık ve son derece hızlı gerçekleşecektir.
- Yazılım içinde bilgi kaydetme, veri silme, güncelleme gibi sorgular ve sunucu bağlantı istekleri (API) arka planda eş zamanlı ve gizli bir biçimde işletilecek, sistem kullanıcıyı ekran donması, beyaz sayfa veya sayfada asılı kalma (kilitlenme) sorunları ile karşı karşıya bırakmayacaktır.
- Oluşturulan yazılım kodları piyasada kullanılan modern güncel web tarayıcılarına (Google Chrome, Firefox, Microsoft Edge, Safari vb.) tamamen duyarlı hale getirilerek; yazılımların ve arayüz biçimlerinin (stillerinin) bilgisayar veya cihaz ayrımı yapmaksızın hiçbir "görsel bozulma (kayma vb.) olmadan" sorunsuz ve standart boyutta açılması güvence altına alınacaktır.
- Uygulamanın grafik ve arayüz kaynak altyapısı yeni teknoloji optimizasyon (derleme) araçları ile küçültülecek, bu da uygulamanın internet bant genişliğinde gecikmeye sebep olmadan inanılmaz hızlı bir ekran tepki süresiyle açılmasına imkan sağlayacaktır.

## 10. DOSYA DEPOLAMA VE MEDYA YÖNETİMİ

- Varsayılan dosya depolama sürücüsü yerel disk (local) olarak yapılandırılmıştır.
- Genel dosyalar için public diski ve sembolik link (public/storage) kullanılacaktır.
- Bulut depolama ihtiyacında AWS S3 sürücüsü hazır bulunmaktadır.
- Medya dosyalarının yönetimi Spatie Laravel Medialibrary kütüphanesi ile yapılacaktır.
- Dosyalar morfolojik olarak (morphMany) modellere iliştirilecektir.
- Yerel depolama yolu storage/app/private (private) ve storage/app/public (public) olarak tanımlanmıştır.

## 11. RAPORLAMA VE DOKÜMAN ÜRETİMİ

- PDF doküman üretimi barryvdh/laravel-dompdf kütüphanesi ile gerçekleştirilecektir.
- Rapor ihracatı PDF ve CSV formatlarında desteklenecektir.
- Server-side DataTables ile büyük veri kümeleri sayfalanarak işlenecektir.
- Gelişmiş Rapor (PRO) modülü ile özelleştirilmiş raporlama yapılabilecektir.
- Ödeme makbuzu (payment-receipt) PDF olarak oluşturulabilecektir.

## 12. HARİTA VE CBS ENTEGRASYONU

- Harita görselleştirme için Leaflet kullanılacaktır.
- WMS/WFS katmanları GeoServer üzerinden çekilecektir.
- WFS sorguları CORS kısıtlamasını aşmak için Laravel proxy (GET /maps/proxy) üzerinden yönlendirilecektir.
- Koordinat dönüşümleri EPSG:3857 (Web Mercator) ile EPSG:4326 (WGS84) arasında gerçekleştirilecektir.
- Google Maps API üçüncü parti harita sağlayıcısı olarak yapılandırılmıştır.
- İki ana WMS/WFS sunucusu tanımlanmıştır: geo4 (kadastro/bina) ve geo2 (altyapı şebekeleri).
- Parsel sorgulama, adres ve katman bilgisi WFS protokolü ile çekilecektir.
- Harita üzerinden başvuru noktası seçimi ve kaydı yapılabilecektir.
- Mevcut başvurular harita üzerinde GeoJSON formatında pin olarak gösterilecektir.

## 13. ZAMAN DİLİMİ VE LOKALİZASYON

- Sistem zaman dilimi Europe/Istanbul (UTC+3) olarak ayarlanacaktır.
- Varsayılan dil İngilizce (en) olarak yapılandırılmıştır.
- Geri dönüş dili (fallback locale) İngilizce (en) olarak belirlenmiştir.
- Faker locale değeri tr_TR olarak ayarlanmıştır.
- Uygulama genelinde tarih/saat gösterimleri Türkiye saat dilimine göre yapılacaktır.

## 14. HATA YÖNETİMİ VE LOGLAMA

- Log yönetimi Monolog kütüphanesi ile gerçekleştirilecektir.
- Varsayılan log kanalı stack (üst üste bindirme) olarak yapılandırılmıştır.
- Alt kanal olarak single (dosya) log kullanılacaktır.
- Geliştirme ortamında log seviyesi debug olarak ayarlanmıştır.
- Deprecation uyarıları null kanalına yönlendirilerek varsayılan olarak devre dışı bırakılmıştır.
- Log dosyası yolu storage/logs/laravel.log olarak belirlenmiştir.
- Daily log sürücüsü ile 14 günlük log rotasyonu yapılabilecektir.
- Slack, Papertrail, Syslog ve Errorlog alternatif log kanalları olarak tanımlanmıştır.
- Acil durum logları (emergency) storage/logs/laravel.log dosyasına yazılacaktır.

## 15. E-POSTA VE BİLDİRİM SİSTEMİ

- Varsayılan e-posta sürücüsü log (dosyaya yaz) olarak yapılandırılmıştır.
- SMTP, Mailgun, Postmark ve SES alternatif e-posta sürücüleri tanımlanmıştır.
- Bildirimler veri tabanında saklanacak, AJAX ile okundu/işaretlendi yapılabilecektir.
- Toplu okundu işaretleme (markAllRead) desteği mevcuttur.
- E-posta gönderimleri Laravel Notifications altyapısı ile yapılacaktır.

## 16. TEST VE KALİTE GÜVENCESİ

- Sistemin fonksiyon ve süreçlerinin hatasız çalışmasını teyit etmek için otomatik modül (birim) testleri uygulanacaktır.
- Kapasite ve doğruluk ölçümleri için, sisteme gerçeğe uygun şekilde üretilmiş rastgele sentetik (deneme) verileri entegre edilerek test edilecektir.
- Yazılım mimarisinin güvenliğini ve kararlılığını sağlamak amacıyla kod kalite ve standart denetim araçları kullanılacaktır.
- Yeni geliştirme, deneme ve konfigürasyon (ayar) temizleme işlemleri ana sistemden tamamen izole (kapalı) geliştirme ortamlarında yürütülüp hatasız olarak yayına alınacaktır.

---

## 17. UYGULAMA MODÜLLERİ VE FONKSİYONEL ÖZELLİKLER

### 17.1. KAZI RUHSAT BAŞVURU MODÜLÜ

- Kurumların ve vatandaşların e-ortam üzerinden yapacakları tüm kazı ruhsat talepleri (yeni başvuru kaydı, bilgi düzenleme/güncelleme, detaylı evrak görüntüleme ve yetkiye dayalı silme/iptal işlemleri) sistem ekranları vasıtasıyla yürütülecektir.
- Evrak kayıt işlemlerinde mükemmeliyetin sağlanması amacıyla hazırlanan başvuru formlarında; ruhsat sahibinin/kurum yetkilisinin doğrulanmış iletişim bilgileri (TCKN, Ad-Soyad, GSM numarası), kazının yapılma sebebi, operasyon/çalışma türü, açık alan/adres verisi ile projelendirilen faaliyetin kesin başlangıç-bitiş tarihleri sisteme işlenecektir.
- Kaydı tamamlanan veya idareye iletilen her ruhsat talebi, evrak takip karmaşasının önüne geçebilmek adına "AYK-[BULUNULAN YIL]-XXXXXX" standart dizilimine ve kurumsal dosya sayısına haiz, kesinlikle taklit edilemeyen/tekrar etmeyen benzersiz (otomatik sıralı) Resmi Başvuru ve Sicil Numarası ile mühürlenecektir.
- Büro personelinin evrak yükünü hafifletmek adına sisteme girilen "T.C. Kimlik (TCKN) numarası" veya sistemdeki doğrulanmış firma kayıt verileri; yeni başvuru sayfasında sistem içi sorgudan geçerek kişi yahut sicil form alanlarını otomatik şekilde akıllı algoritmayla kendi doldurabilecektir.
- Gelişmiş bürokrasi takibi kuralları gereği, sisteme dahil edilen ruhsat işlem akışları aşağıdaki 9 (dokuz) basamaklı, yüksek iş aşama modellemesi ile (hiyerarşik olarak) evreler halinde statülenecek ve izlenecektir:
 1. Başlangıç / Evrak Taslak Evresi
 2. İdareye Resmen Sunulma (Kayıt Alma) Evresi
 3. Fiyatlandırma ve Keşif Bildirimi Ataması Evresi
 4. Ödeme Beklenen (Bedel Tahakkuk) Evresi
 5. Yatırılan Bedel/Makbuz İşleminin Kurum Onayına Girdiği Evre
 6. E-imza/Makbuz Onayı Verilmiş Resmi Ruhsatlandırılma Evresi
 7. Ekiplerin Adrese Sevk Edilmesiyle Çalışmaların Filli Başladığı Evre
 8. Saha Çalışmasının Tamamlanıp Geri Bildirim Geldiği (Görev Bitişi) Evresi
 9. Resmi Kapatmanın Yapılarak Pasif Veri/Arşive Kaldırılma Evresi
- İdari ve yasal soruşturma kalitesini sağlama prensipleri eşliğinde evrak üzerindeki onay geçişlerinin ("Resmi Zaman/Akış Çizelgesinde") her bir saniyesi kayıt altına alınacak; hangi kurum/büro elemanının dosyaya onay-ret kararını ya da işlem detayını tam (hangi IP adresi ve hangi tarih, gün, saniyeyle) kurguladığı doğrudan amir izlenebilirliğinde barındırılacaktır.
- İş hacminin arttığı durumları denetlemek amaçlı, idari merciler için zengin veri tablolarında detay filtre aramaları sunulacak; Yalnızca özel resmi belge No’su aranarak ya da 'Kurum' ismi, 'Tarih periyodu', "Arşiv veya Taslaktakiler gelsin" gibi dosya koşulu kıstaslamaları saniyeler bazında raporlaştırılarak kullanıcı ön ekran paneline dönecektir.

### 17.2. HARİTA TABANLI KAZI ALANI ÇİZİM MODÜLÜ

- Başvuru oluşturma ve düzenleme ekranında Leaflet haritası üzerinden kazı alanı çizilebilmelidir.
- Çizilen alanın polygon geometrisi GeoJSON formatında kaydedilmelidir.
- Polygon koordinatlarından WGS84 bazlı alan hesabı (Shoelace formülü ile metrekaresi) otomatik olarak hesaplanmalıdır.
- Çokgen (Polygon) ve çoklu çokgen (MultiPolygon) geometrileri desteklenmelidir.
- Harita çizim güncellemelerinde eski alan kaydı silinip yeni alan bilgisi senkronize edilmelidir.
- Kazı alanının merkez koordinatları (enlem/boylam) otomatik olarak kaydedilmelidir.

### 17.3. ZEMİN TİPİ VE BİRİM FİYATLANDIRMA MODÜLÜ

- Sistemde tanımlı zemin tipleri (asfalt, beton, parke, toprak vb.) yönetilebilmelidir.
- Her zemin tipi için metrekare başına birim fiyat (price_per_m2) tanımlanabilmelidir.
- Zemin tipleri aktif/pasif durumu ve renk kodu ile işaretlenebilmelidir.
- Bir kazı alanı için zemin tipi ataması yapılabilmeli, genişlik (metre), uzunluk (metre), adet ve çarpan bilgileri girilerek keşif bedeli otomatik hesaplanmalıdır.
- Keşif bedeli = (genişlik x uzunluk x adet x birim_fiyat x çarpan) formülü ile hesaplanmalı, genişlik/uzunluk girilmediğinde kazı alanı m²'si baz alınmalıdır.
- Toplam keşif bedeli ve genel toplam fiyat otomatik olarak güncellenmelidir.

### 17.4. ÜCRET ONAY VE MAKBUZ YÖNETİM MODÜLÜ

- Yetkili kullanıcı tarafından keşif bedeli onaylanabilmeli, başvuru ödeme bekleniyor (awaiting_payment) statüsüne geçirilmelidir.
- Başvuru sahibi tarafından ödeme makbuzu sisteme yüklenebilmelidir (JPEG/PNG/PDF formatında).
- Makbuz dosyaları "receipts" dizininde "receipt-{basvuru_no}-{tarih}" formatında depolanmalıdır.
- Yüklenen makbuz yetkili kullanıcı tarafından onaylanabilmeli veya reddedilebilmelidir.
- Makbuz onayı sonrası başvuru otomatik olarak ruhsatlı (licensed) statüsüne geçirilmelidir.
- Makbuz reddi durumunda başvuru ödeme bekleniyor statüsüne geri dönmeli, red gerekçesi not olarak eklenmelidir.
- İlk makbuz reddedildiğinde aynı kayıt güncellenerek yeniden kullanılabilmeli, yeni dosya yüklendiğinde kayıt yenilenmelidir.

### 17.5. RUHSAT BELGESİ ÜRETİM MODÜLÜ (PDF)

- Makbuz onayı sonrası kazı ruhsatı belgesi otomatik olarak PDF formatında üretilmelidir.
- Ruhsat PDF'i A4 dikey boyutunda, DomPDF kütüphanesi ile oluşturulmalıdır.
- Ruhsat belgesi; başvuru bilgileri, kurum bilgileri, kazı alanı, zemin tipi, keşif bedeli, onaylayan bilgilerini içermelidir.
- Ruhsat PDF'i "licenses/{basvuru_id}/" dizininde depolanmalıdır.
- Ruhsat belgesi web arayüzünden indirilebilmelidir.

### 17.6. KURUM YÖNETİM MODÜLÜ (MULTI-TENANCY)

- Sistemde birden fazla kurum tanımlanabilmeli, her kurumun kendine ait adı, slug'ı, yetkili kişisi, vergi numarası, telefon, e-posta ve adres bilgileri tutulabilmelidir.
- Kurumlar belediye (is_municipality) ve diğer kurumlar olarak sınıflandırılabilmelidir.
- Her kuruma özel renk kodu atanarak harita ve raporlarda görsel ayrım sağlanmalıdır.
- Kurumlar, kullanıcılar ve başvurular ile ilişkisel olarak bağlanmalıdır.
- Kurum silme işlemi, yalnızca başvurusu bulunmayan kurumlar için mümkün olmalıdır.
- Aktif kurumlar: Eyyübiye Belediyesi (belediye), AKSA, TEDAŞ, ŞUSKİ, Türk Telekom.

### 17.7. KULLANICI VE ROL YÖNETİM MODÜLÜ (RBAC)

- Sistemde roller ve izinler Spatie Laravel Permission kütüphanesi ile yönetilmelidir.
- Kullanıcılar; super-admin, municipality-admin, municipality-staff, institution-admin, institution-staff ve field-team olmak üzere 6 farklı role sahip olabilmelidir.
- Her rolün tanımlı izinleri (permissions) roller yönetim ekranından atanabilmelidir.
- Super-admin rolü tüm yetkilere sahip olmalı, bu rol yalnızca super-admin tarafından düzenlenebilmelidir.
- Kullanıcı yönetimi, kurum bazında veri izolasyonu ile yapılmalıdır: super-admin herkesi görebilir, municipality-admin diğer super-admin'ler dışındaki tüm kullanıcıları görebilir.
- Kullanıcı oluşturma ve düzenleme işlemlerinde rol, kurum, ad, e-posta ve şifre bilgileri girilebilmelidir.
- Kullanıcı silme işleminde kendini silme engellenmelidir.

### 17.8. ÖDEME MAKBUZU (TAHSİLAT MAKBUZU) ÜRETİM MODÜLÜ

- Başvuru bazında ödeme makbuzu PDF olarak üretilebilmelidir.
- Makbuz; başvuru numarası, başvuru sahibi bilgileri, keşif bedeli, tahsilat tarihi gibi bilgileri içermelidir.

### 17.9. SAHA GÖREVİ ATAMA VE YÖNETİM MODÜLÜ

- Belge onay süreci ile ruhsatlandırılma (evrak) safhası başarıyla tamamlanmış kazı alanları; doğrudan ilgili denetmen, kurum amiri yahut yönetici panellerinden resmî "Saha İş Emri"ne dönüştürülerek sorumlu teknik ekip personellerine atanabilecektir.
- Sistem içi yetki devri niteliğindeki "İş Görevlendirme Ataması" esnasında; görevi (emri) sevk eden merciinin kayıt bilgisi, tebliğ alan alt personelin yetki detayı, idarece hedeflenen tahmini çalışma bitiş (teslimat) vadesi ve operasyonu yönetecek olan ekibe dikte edilecek "Saha Harekat/Bilgi Notları" bütünüyle sisteme işlenecektir.
- Bir çalışmaya personel görevi tebliğ edildiği (kaydedildiği) o saniye, ilgili kazı dosyasının durumu otomatik biçimde uyanarak kurum ana ekranlarındaki takibini tamamen "Ekipler Adrese Sevk Edildi (Faal Çalışma Merkezindedir)" düzeyine dönüştürecektir.
- Sokakta yürütülen hizmet kalitesini tavizsiz kılarak çukurların denetim boşluğuna mahal vermemek amacıyla sahadaki faaliyet operasyonları en katı formda şu 3 (Üç) Fazlı Kritik Rapor ve Onay Süzgeci evresine alınarak yönetilecektir:
1. Safha Onayı: Fiziki çalışmanın sıfır (temel) noktası kanıtı olarak Kazı Öncesi Durum Tespit ve Yüzey Kontrol raporlaması.
2. Safha Onayı: Temel inşai / teknik işleminin icra edilişi olan Aktif Kazı Bitim Sırası Denetimi.
3. Safha Onayı: Sisteme veda edilen yer örtü, kapama Nihai Yüzey Yaması ve Çevre Tahribat Onarımı Kontrolü. 
- Evrede işlenen operasyonlarda alt adımlar yetkili cihazlarından tekli biçimde (Bölüm Devam Ediyor / Bir Safha Kesin Olarak Bitirildi şeklinde) etiketlenecek, girilen iş tekmilleri tarih/zaman format damgalarına sadık kalmakla yönetici izlencesine nakledilecektir.
- Entegreli (tablet vb. formunda kullanılan mobil) Saha ekran paneline donatılmış bu modellemelerde her personel onaylamak mecburiyetinde kılındığı 3 aşamanın hepsine teker teker Sahada Çalışılan Adres Mevkiisinin Güncel İmajlı Belgesini (Dijital Olay Fotoğrafı vb.) zorunlu sistem e-dosya deposuna, kayıt iletisi anında delillendirmek amacıyla aktarabilecektir.
- Evraktan gerçeğe taşınan ve sahada sıfır kusur kanıtlamayla III. fazı (Onarım / Düzeltiş kısmı) kapatılarak yansıtılan görev formülize olarak resmi kapanış işlemini merkeze (amirin ön panel takibine) taşıyarak, sistem altyapı iş süreç yönergelerini bir "Kurum ve İşleyiş Bilgilendirme Onayı" otomatik kapanış uyarısına geçirecek olup faaliyet dosyası hiyerarşik uyarıyla denetçilere bildirilecektir.


### 17.10. HARİTA İZLEME MODÜLÜ (MAP MONITOR)

- Başvuruların kazı alanları Google Haritalar üzerinde toplu olarak görüntülenebilmelidir.
- Harita üzerinde polygon ve marker çizim tiplerine göre filtreleme yapılabilmelidir.
- Başvuru durumuna ve kuruma göre harita filtrelemesi yapılabilmelidir.
- Her kurum harita üzerinde kendine özgü renk kodu ile işaretlenmelidir.
- Harita üzerinde başvuru çizimleri kaydedilebilmelidir.

### 17.11. CBS (COĞRAFİ BİLGİ SİSTEMİ) ENTEGRASYON MODÜLÜ — AYKOME MAPS

- Sistem, Leaflet 1.9.4 harita kütüphanesi ile WMS/WFS katmanlarını görüntüleyebilmelidir.
- İki ayrı GeoServer sunucusuna bağlanılabilmelidir: geo4 (kadastro, bina, ilçe, mahalle, ada, cadde/sokak, adres, pafta katmanları) ve geo2 (doğalgaz hatları/noktaları, elektrik hatları/noktaları, metrobüs CAD katmanları).
- WFS sorguları, CORS kısıtlamasını aşmak için Laravel proxy (GET /maps/proxy) üzerinden yönlendirilmelidir.
- Proxy sunucu domain doğrulaması yapmalı, yalnızca geo4.sanliurfa.bel.tr ve geo2.sanliurfa.bel.tr domain'lerine izin vermelidir.
- Haritaya tıklandığında WFS sorgusu ile parsel, ada, mahalle bilgileri çekilerek popup gösterilebilmelidir.
- Popup üzerinden "Başvuru Yap" veya "Ortak Kazı" butonları ile hızlı başvuru oluşturulabilmelidir.
- Harita üzerinde seçilen noktanın koordinatı, parsel, ada, ilçe ve mahalle bilgileri veri tabanına kaydedilebilmelidir.
- Mevcut başvurular harita üzerinde GeoJSON FeatureCollection formatında pin/marker olarak görüntülenebilmelidir.
- Koordinat sistemi EPSG:3857 (Web Mercator) ile EPSG:4326 (WGS84) arasında dönüşüm yapılabilmelidir.
- Katmanlar AKOS (kadastro/bina) ve MAKS+ (altyapı şebekeleri) olarak iki grupta toplanmalı, varsayılan katmanlar açık olmalıdır.

### 17.12. RAPORLAMA MODÜLÜ

- Sistemde başvuruların kurum bazında ve durum bazında sayısal özet raporu görüntülenebilmelidir.
- Gelişmiş raporlama (PRO) ile tarih aralığı, bölge, kurum ve durum kriterlerine göre filtrelenmiş başvuru listesi sunulabilmelidir.
- Rapor sonuçları PDF (yatay A4) ve CSV (UTF-8 BOM, noktalı virgül ayraçlı) formatlarında dışa aktarılabilmelidir.
- Rapor verileri server-side DataTables ile sayfalanarak sunulmalı, büyük veri kümelerinde performans sağlanmalıdır.

### 17.13. İZİN BELGESİ AYARLARI MODÜLÜ

- Sistemde ruhsat/izin belgesinde görünecek kurum bilgileri (kurum adı, adresi, birim adı) yapılandırılabilmelidir.
- İzin belgesinde kullanılacak kurum logosu, müdür adı/unvanı, hazırlayan adı/unvanı/imzası, onaylayan adı/unvanı, müdür imzası ve belediye kaşesi görselleri yüklenebilmelidir.
- İkinci onaylayıcı adı/unvanı ve geçerlilik anlaşma metni yapılandırılabilmelidir.
- Alt bilgi notu (footer note) eklenebilmelidir.
- Tüm değişiklikler denetim günlüğüne  kaydedilmelidir.

### 17.14. CANLI SAHA İZLEME MODÜLÜ (PRO)

- Saha personelinin mobil cihazından check-in/check-out yaparak sahada olduğunu bildirebilmelidir.
- Saha personelinin anlık GPS konumu (enlem/boylam) sisteme periyodik olarak iletilmelidir.
- Canlı harita üzerinde sahada aktif olan personel (son 2 dakika içinde ping atan) ve bugün sahaya çıkmış personel konumları görüntülenebilmelidir.
- Her personelin aktif görevi ve son yüklenen medyası harita üzerinde görüntülenebilmelidir.
- Saha personelinin zombi (canlılık sinyali kesilmiş) durumu scheduled task ile her dakika kontrol edilmelidir.

### 17.15. GÖREV EMRİ YÖNETİM MODÜLÜ (PRO - KANBAN)

- Saha görevleri Kanban tipi görünüm ile yönetilebilmelidir (pending/in_progress/completed sütunları).
- Görev emirleri başvuru numarası ve adrese göre aranabilmeli, duruma göre filtrelenebilmelidir.
- Görev emirleri CSV ve PDF formatlarında dışa aktarılabilmelidir.
- Görev emirleri aşama ilerleme durumu (stage 1/2/3) ile birlikte görüntülenebilmelidir.
- Toplam, bekleyen, devam eden, tamamlanan ve gecikmiş görev sayıları özet istatistik olarak sunulmalıdır.

### 17.16. SAHA PERFORMANS RAPORU MODÜLÜ (PRO)

- Saha personelinin performans raporu görüntülenebilmelidir.
- 6 aylık tamamlanma grafiği ve aşamalara göre tamamlanma oranları sunulmalıdır.
- Her personel için toplam görev sayısı, tamamlanan/bekleyen/aktif/gecikmiş görev istatistikleri, başarı oranı, gecikme oranı ve performans seviyesi rozeti (iyi/kötü/orta) hesaplanmalıdır.
- Performans raporu CSV ve PDF formatlarında dışa aktarılabilmelidir.


### 17.17. DASHBOARD MODÜLÜ

#### 17.17.1. Super-Admin Dashboard
- Toplam lisans sayısı, aktif lisans sayısı, süresi yaklaşan lisans sayısı, süresi dolan lisans sayısı ve kilitli lisans sayısı görüntülenebilmelidir.
- Toplam kullanıcı sayısı ve toplam gelir istatistikleri sunulmalıdır.
- Kritik ve süresi dolmuş lisanslar listelenmelidir.
- Son başvuru aktiviteleri görüntülenebilmelidir.

#### 17.17.2. Admin Dashboard (Belediye/Kurum)
- 6 aylık başvuru ve gelir grafiği (çizgi grafik) görüntülenmelidir.
- Toplam başvuru, bekleyen başvuru, aylık başvuru, ödenmiş ve ödenmemiş başvuru istatistikleri sunulmalıdır.
- Son aktiviteler ve son başvurular listelenmelidir.

#### 17.17.3. Saha Personeli Dashboard
- Saha personeline ait görevler durum bazında (beklemede/işleniyor/tamamlandı) gruplanarak listelenmelidir.
- Personelin kendi başvuruları ve görev sayıları görüntülenebilmelidir.

### 17.18. KULLANICI PROFİL YÖNETİM MODÜLÜ

- Kullanıcılar kendi profil bilgilerini (ad, soyad, e-posta) güncelleyebilmelidir.
- E-posta değişikliği durumunda e-posta doğrulama süreci yeniden başlatılmalıdır.
- Kullanıcı hesabını silebilmeli, silme işlemi öncesi mevcut şifre doğrulaması yapılmalıdır.

### 17.19. KİMLİK DOĞRULAMA VE ÜYELİK MODÜLÜ

- Kullanıcı kaydı (registration) yapılabilmelidir.
- E-posta adresi ve şifre ile oturum açılabilmelidir.
- E-posta doğrulama (email verification) zorunlu tutulmalıdır.
- Parola sıfırlama (forgot password) işlemi e-posta üzerinden yapılabilmelidir.
- Parola değiştirme (change password) işlemi mevcut parola doğrulaması ile yapılabilmelidir.
- Parola onaylama (confirm password) işlemi ile hassas işlemler için ek güvenlik katmanı sağlanmalıdır.
- Oturum kapatma (logout) sonrası oturum geçersiz kılınmalı ve CSRF token yenilenmelidir.

### 17.20. BİLDİRİM MODÜLÜ

- Sistemde yeni başvuru oluşturulduğunda ilgili yöneticilere bildirim gönderilmelidir.
- Makbuz yüklendiğinde ilgili yöneticilere bildirim gönderilmelidir.
- Saha görevi aşama tamamlandığında yöneticilere bildirim gönderilmelidir.
- Bildirimler sistem içi (veri tabanında) saklanmalı, son 15 bildirim web arayüzünde gösterilmelidir.
- Bildirimler tek tek veya topluca okundu olarak işaretlenebilmelidir.

### 17.21. SİSTEM DENETİM GÜNLÜĞÜ MODÜLÜ (AUDIT LOG)

- Sistemdeki tüm önemli işlemler denetim günlüğüne kaydedilmelidir.
- Denetim günlüğünde; işlem açıklaması, aksiyon türü, kullanıcı bilgisi, IP adresi, konu (subject) tipi/ID'si ve oluşturulma zamanı tutulmalıdır.
- Denetim günlükleri aksiyon türüne, kullanıcıya ve IP adresine göre aranabilmelidir.
- Toplam kayıt sayısı, bugünkü kayıt sayısı, bugünkü giriş sayısı ve bugünkü aksiyon sayısı istatistik olarak sunulmalıdır.
- Denetim günlüğü yalnızca super-admin rolündeki kullanıcılar tarafından görüntülenebilmelidir.


### 17.22. GERÇEK ZAMANLI OLAY YÖNETİMİ

- Yazılım mimarisi, gerçekleşen kritik bürokratik/operasyonel hamlelere gecikmeksizin reaksiyon veren "Canlı (Gerçek Zamanlı) İşlem İzleme ve Sinyal (Alarm) Yansıtma" kapasitesine sahip şekilde donatılacaktır.
- İdare masalarındaki evrak ya da iş takibi bekleme süreçlerini ortadan kaldırmak amacıyla, aşağıda ifade edilen temel hayati adımlar icra edildiği salise içinde sistem hafızasında "Otomatik İş Akışı Alarmı" oluşturacaktır:
- FieldTaskCompleted olayı saha görevi tamamlandığında tetiklenmelidir.
- Tüm olaylar isteğe bağlı olarak Reverb WebSocket üzerinden broadcast edilebilmelidir.

### 17.23. ZAMANLANMIŞ GÖREVLER (SCHEDULED TASKS)

- CheckFieldStaffStatus komutu her dakika çalışarak saha personelinin zombi durumunu kontrol etmelidir.
- Licenses:check-expiry komutu her dakika çalışarak lisans sürelerini kontrol etmelidir.

---

## 18. SİSTEM HARİTA (GIS) VE MEKANSAL SORGULAMA ÖZELLİKLERİ

### 18.1. CBS ENTEGRASYON MODÜLÜ (AYKOME MAPS)

- Sistem, Şanlıurfa Büyükşehir Belediyesi'ne ait iki ayrı GeoServer sunucusuna (geo4 ve geo2) bağlanarak WMS (Web Map Service) ve WFS (Web Feature Service) protokolleri ile mekansal verileri görüntüleyebilmelidir.
- WMS tile katmanları, Leaflet 1.9.4 kütüphanesi ile client tarafından doğrudan çekilmeli, CORS kısıtlaması olmaksızın harita üzerinde gösterilebilmelidir.
- WFS sorguları, CORS kısıtlamasını aşmak amacıyla Laravel proxy (GET /maps/proxy) üzerinden yönlendirilmeli, proxy yalnızca geo4.sanliurfa.bel.tr ve geo2.sanliurfa.bel.tr domain'lerine izin vermelidir.
- Koordinat sistemi olarak WMS/WFS katmanlarında EPSG:3857 (Web Mercator), Leaflet harita motorunda EPSG:4326 (WGS84) kullanılmalı, BBOX sorguları EPSG:3857 formatında iletilmelidir.

### 18.2. WMS KATMAN SUNUCULARI VE KATMAN LİSTESİ

1. Kadastro ve Yerleşim Verileri Grubu (AKOS):
 - İlçe ve Mahalle sınırları
 - Kadastro parsel, ada ve pafta bilgileri
 - Binalar ve bağımsız kullanım alanları
 - Cadde, sokak ve kapı/adres noktaları

2. Altyapı Şebekeleri Veri Grubu (MAKS+):
 - Doğalgaz Hatları ve Bağlantı Noktaları 
 - Elektrik Hatları
 - İçme Suyu Şebekeleri
 - Kanalizasyon Şebekeleri
 - Yağmur Suyu Hatları
 - Telekomünikasyon (Telekom/Fiber) Hatları
 - Karayolu ağları ve Metrobüs güzergah (CAD) katmanları


### 18.3. SOL KATMAN PANELİ (LAYER MANAGEMENT)

- Harita sayfasının sol tarafında 280px genişliğinde koyu temalı (dark theme) bir panel bulunmalı, katmanlar AKOS (geo4) ve MAKS+ (geo2) olarak iki grupta akordiyon (accordion) şeklinde listelenmelidir.
- Her katman için checkbox ile aç/kapat ve opacity kaydırıcısı (range slider) bulunmalıdır.
- Üç farklı altlık harita (basemap) seçeneği sunulmalıdır: Google Uydu, OpenStreetMap, Topoğrafya.
- Aktif katman sayısı durum çubuğunda (statusbar) gösterilmelidir.

### 18.4. HARİTA ÜZERİNDE ÇİZİM ARAÇLARI (LEAFLET.DRAW)

- Kullanıcı, harita üzerinde Leaflet.Draw kütüphanesi ile aşağıdaki geometrik şekilleri çizebilmelidir:
  - **Nokta (Marker):** Tek bir koordinat noktası işaretlenebilmelidir.
  - **Çizgi (Polyline):** Serbest çizgi/kazı güzergahı çizilebilmelidir.
  - **Alan (Polygon):** Kapalı çokgen alan çizilebilmelidir.
  - **Dikdörtgen (Rectangle):** Dikdörtgen alan çizilebilmelidir.
  - **Daire (Circle):** Yarıçapı belirlenebilir daire alanı çizilebilmelidir.
  - **İşaret (CircleMarker):** Sabit yarıçaplı noktasal işaret konulabilmelidir.
- Çizim işlemi ESC tuşu ile iptal edilebilmelidir.
- Çizim alanı otomatik olarak metrekare/dönüm cinsinden hesaplanarak gösterilmelidir.
- Çizim temizleme (clear) butonu ile tüm çizimler tek seferde silinebilmelidir.

### 18.5. KAZI ALANI POLYGON ÇİZİMİ (BAŞVURU FORMU)

- Başvuru oluşturma ve evrak düzenleme ekranlarında etkileşimli (interaktif) bir harita modülü bulunacak olup, planlanan kazı sahaları doğrudan harita üzerine çizilebilecektir.
- Çizilen kazı bölgesinin harita üzerindeki evrensel geometrik verileri arka planda sisteme otomatik olarak kaydedilecektir.
- Sistem; kullanıcının haritada işaretlediği kapalı alanın (kazı bölgesinin) merkez koordinatını (enlem/boylam) bulacak ve metrekare (m²) cinsinden yüzölçüm büyüklüğünü anında otomatik hesaplayarak başvuru evrakına işleyecektir.
- Daha önceden başvurusu yapılmış kayıtlara girildiğinde; geçmişte haritaya çizilmiş sahalar otomatik görüntülenebilecek ve yetkili personel tarafından şekli kolayca güncellenebilecektir.


### 18.6. HARİTA ÜZERİNDEN HIZLI BAŞVURU (POPUP PANEL)

- Haritaya tıklandığında açılan popup'ta "Kazı Ruhsatı" ve "Ortak Kazı" butonları bulunmalıdır.
- Popup butonuna tıklandığında sürüklenebilir (draggable) bir başvuru paneli açılmalı, tıklanan noktanın koordinatı, ilçesi, mahallesi ve adresi otomatik doldurulmalıdır.
- Başvuru panelinde kazı ruhsatı veya ortak kazı tipi seçilebilmeli, ortak kazı tipinde AKSA, TEDAŞ, ŞUSKİ ve Türk Telekom kurumları işaretlenebilmelidir.
- Kullanıcı kazı açıklaması, kazı derinliği (metre) ve tahmini süre (gün) bilgilerini girebilmelidir.
- Başvuru kaydedildiğinde AJAX ile MapsController::noktaKaydet metoduna POST isteği gönderilmeli, başvuru haritada anında pin olarak görüntülenmelidir.

### 18.7. BAŞVURULARIN HARİTA ÜZERİNDE GÖSTERİMİ (GEOJSON MARKER)

- Sistemde kayıtlı tüm başvurular (GIS noktaları + uygulama kazı alanı merkezleri) GeoJSON FeatureCollection formatında MapsController::basvurularGeoJson endpoint'i ile sunulmalıdır.
- Her başvuru, durumuna göre renklendirilmiş özel pin ikonları ile haritada gösterilmelidir (saha çalışması: turuncu, onaylandı: yeşil, ödeme bekliyor: mavi, beklemede: gri, reddedildi: kırmızı, tamamlandı: koyu gri).
- Saha çalışması (field_work) durumundaki başvurular için pin ikonu pulse animasyonu ile vurgulanmalıdır.
- Başvuru pin'ine tıklandığında başvuru numarası, kurum adı, durum etiketi ve tarih bilgilerini içeren popup gösterilmeli, "Detay →" bağlantısı ile başvuru detay sayfasına yönlendirme yapılmalıdır.
- Başvuru filtreleri (durum bazında checkbox) ile istenen durumdaki başvurular haritada gösterilip gizlenebilmelidir.

### 18.8. ADRES ARAMA (GEOCODING)

- Harita üzerinde Nominatim OpenStreetMap arama servisi ile adres/yer sorgulaması yapılabilmelidir.
- Arama sonuçları Şanlıurfa ili ile sınırlandırılmalı, harita üzerinde sonuçlar listelenmeli ve seçilen konuma harita otomatik zoom yapmalıdır.
- Arama sonuçları maksimum 6 adetle sınırlandırılmalıdır.

### 18.9. KULLANICI KONUMU (GEOLOCATION)

- Kullanıcının tarayıcı tabanlı GPS konumu alınarak harita üzerinde mavi renkli pulse animasyonlu bir marker ile gösterilebilmelidir.
- "Konumum" butonu ile kullanıcı konumuna otomatik zoom yapılabilmelidir.
- GPS konumu başvuru formunda otomatik doldurma için kullanılabilmelidir.

### 18.10. GOOGLE STREET VIEW ENTEGRASYONU

- Harita üzerinde sağ tıklandığında (contextmenu) ilgili koordinatta Google Street View panoramik görüntüsü açılabilmelidir.
- Harita İzleme (admin) sayfasında çift tıklandığında Google Haritalar embed iframe ile Street View ve harita görüntüsü popup olarak gösterilebilmelidir.

### 18.11. ADMIN HARİTA İZLEME (MAP MONITOR)

- Admin panelinde tüm başvurular harita üzerinde toplu olarak görüntülenebilmelidir.
- Başvurular; durum, kurum ve çizim tipine (polygon/marker/yok) göre filtrelenebilmelidir.
- Her kurum kendine özgü renk kodu ile polygon olarak haritada gösterilmeli, kurum renkleri lejantta listelenmelidir (TEDAŞ: kırmızı, ŞUSKİ: mavi, AKSA: turuncu, Belediye: yeşil).
- Polygon çizimine tıklandığında polygon sınırlarına zoom yapılmalı ve detay popup'ı açılmalıdır.
- Harita üzerinde marker (noktasal) ve polygon (alansal) çizim istatistikleri özet panelinde gösterilmelidir.
- Başvuru tablosu client-side search, status filter ve çok sütunlu sıralama (sort) ile yönetilebilmelidir.

### 18.12. CANLI SAHA İZLEME (LIVE MAP PRO)

- Saha personelinin anlık GPS konumu Leaflet haritası üzerinde gerçek zamanlı olarak izlenebilmelidir.
- Her personel için renk kodlu baş harf marker'ları (42x42px) kullanılmalı, marker etrafında ping-ring animasyonu ile aktif sinyal gösterilmelidir.
- 30 saniyede bir polling ile personel konumları güncellenmeli (GET /admin/live-map-pro/data).
- Panelde iki sekme bulunmalıdır: "Canlı Aktifler" (sahada olan, son 2 dk içinde ping atan) ve "Son Görülenler" (bugün çıkış yapan, son konum bilgisiyle).
- Her personel için; ad, sahada geçirdiği süre, aktif görev numarası ve sahada çekilen son fotoğrafları (gallery thumbnails) gösterilmelidir.
- Personel kardına tıklandığında harita ilgili personele pan+zoom yapmalı, popup açılmalıdır.
- Fotoğraflar GLightbox kütüphanesi ile galeri görünümünde izlenebilmelidir.
- Üç farklı harita görünüm stili sunulmalıdır: Standart, Sadece Yollar, Yeşil Alan.
- Adres arama kutusu ile sahada konum sorgulaması yapılabilmelidir.


### 18.13. HARİTA KULLICI ARAYÜZÜ ÖZELLİKLERİ

- Harita tam ekran moduna alınabilmelidir (fullscreen toggle).
- Durum çubuğunda (statusbar) anlık koordinat (enlem/boylam) ve zoom seviyesi gösterilmelidir.
- Mobil uyumlu (responsive) tasarım ile küçük ekranlarda sol panel drawer şeklinde açılmalı, mobil toggle butonu ile kontrol edilebilmelidir.
- Harita canvas'ı, sidebar genişliğine göre otomatik boyutlandırılmalı, resize olayında yeniden boyutlandırılmalıdır (invalidateSize).
- Tüm harita sayfalarında Leaflet  kullanılmalıdır.
- Başvuru paneli sürüklenebilir (draggable) olmalı, fare ile hareket ettirilebilmelidir.
- Katman kontrolü, Google Uydu altlık haritası ile başlatılmalı, uydu ve OSM arasında geçiş yapılabilmelidir.

---


### 19.2. SÜPER-ADMIN YETKİ MUAFİYETİ (GATE BEFORE)

- İdareye sunulan altyapı üzerinde en yüksek sistem erişim sınıfına haiz olarak kurgulanan "Süper/Baş Yönetici", diğer personeller ve dış kurumlar için tanımlanmış olan hiçbir erişim kısıtlamasına (alt hiyerarşi engeline, bölgesel izolasyona vb.) tabi tutulmayacak; sistemin mimari kalbi nezdinde özel ayrıcalık (mutlak ve sınırsız izin) esasıyla tüm veri kurallarından bağışık (muaf) çalıştırılacaktır.
- Tam yetkili bu sistem yöneticisi; uygulamanın karşısına çıkarabileceği evrak onama kilitleri, hiyerarşi denetimi, modül erişimi ve güvenlik sorgusu pencerelerinin tümüne hiçbir onaya ihtiyaç duymadan mutlak giriş yetkisi ile sınırsız %100 tahakküm ve müdahale gücüne kavuşacaktır.
- Yüksek idare yetkilisi makamını koruyan bu dev yetki kapsamı sayesinde; kuruma/taşeron ve yan idarelere sunulan kullanım geçerlik limit süreleri aşılmış bile olsa, amir pozisyonlu personel kurumların ya da kişilerin yaşadığı hiçbir genel lisans/hak kesintisi cezalarına yahut bloklama (erişim dondurulma) yasaklama uyarılarına (bariyerlere) katiyen takılmayarak durmaksızın yönetim, kurtarma veya denetleme (lisans-dışı/istisnai) erişimine doğrudan ulaşabilecektir.



### 19.4. LOG GÖRÜNTÜLEME VE DENETİM

- Yazılım içerisinde gerçekleşen devasa kayıt izlerinin tutulduğu "Sistem Denetim (İz) Arşivi / Olay Geçmişi", mahremiyet standartları gereğince yetkisi sınırlanmış normal/standart veya alt kademe kurumsal üye (Kullanıcılara vb.) kilitlenecek olup bu mahrem listeler kati suretle yalnızca "Süper Amir / Tam Yetkili Yönetici" makamı kademesindeki kurmayların doğrudan erişebildiği ve korumalı ayrı bir panele dönüştürülecektir.
- Söz konusu Denetim İnceleme Arayüzüne geçiş sağlayan kurum amirinin önüne anlık hareket zemin istatistik tablosu konumlandırılacaktır: Uygulamada açılmış total eylem geçmiş dökümünün net vaka adedi grafiği, içinden sadece gün içini barındıracak vaka eylemi ölçümü ve sisteme giren mesai anlık kullanıcı yoğunlaşmaları giriş giriş deneme frekansı vs. şık bir analiz panosu olarak görsel formlara dizayn edilecektir.
- Kargaşalığı dindirerek olası "Geçmiş Eylem İdari İhtilaf Analizlerini", yani hata anını tekli aramalarda tespiti mükemmel şıktaki kurgu üzerinden kural/filtre süzgecinde sunmalıdır: Denetleyenlerce yapılan detay döküm filtre işine özel olarak; arama sekmesinde izler: Evrak üzerindeki gerçekleştiriliş iş tipi/faaliyeti hiyerarşisi üzerinden aranarak ya da sistemi kurcalayan şahıs ismi, bağlantının çıktığı ofis cihaz ağı iletişim ibaresi konumu / elektron cihaz adresi izini bulgulamadan, son hamlede onay / silinmelerde geçen operasyon başlığı alt bilgisine (text özete vb.) uzanıp dökerek tek ve direkt evraka veya işleyenine müfettişi yahut yetkilisini hedeften yansıtılma özelliğinde (derin sorgu) yapıda olabilecektir.
- İleri yıllardaki milyarlara yaklaşabilme hacmi ile operasyon hantallıklarının/zayıflığının tamamen sıfır düzeyini garantilemiş olarak (donmaları bertaraf üzerine kurulu, performansta pürüz çıkarmayan kütüphane teknolojisi yardımı ve desteği sayesinde); Geçmiş Arşiv Çizelgesi "Gelişmiş-Sunucu Taraflı Kesitsel / Hiyerarşik Ekran Okuyucu Yükleyici" marifeti ve zeki çok boyutlu dinamik sayfa katsayı veri ayırıcı parçalama özelliği entegresi kurumlara kazandırılmış kalibrasyon formuna sunulup takdire göre izlenme performanlarına akım devrilecektir.
- Kâtip veya resmi amirce süren inceleyen binlerde devrilmiş (ve evrak akını ile inilecek tablolarda evren hızı sağlayıp); Göz taramasındaki dikkate ivme verebilmesini kolaylatma bazında Geçmiş iz Sütunu Listesini - Faaliyet Özel Ayırıcı Görsel Ön İşaretli Etiket/Sembolleri İbareler Sistemindeki Kararlar ile Süsleyerek Dizilmesine Fırsata Ayırma Uygulaması Barındıracaktır. Örneğin Uygulama oturumu hareket log izinden -> Yeni başvuru mimarisi yansıyan kayıt/satırına yahut İdari Tahakkuk, Makbuz işlenme onay, Alt Dış yüklemeciye Adres / görev yansıma gibi vb duruşlardaki bambaşka form işleri için döküntülere kurşuni renkten amirlere göze ilişerek çakan kırmızı/göklere özel, iz işaret ayrışık bröveleri sınıfına koduna dayandığı ayrı hiyerarşisi forması olacaktır.


### 19.5. OLAY BAZLI LOGLAMA (EVENT LİSTENERS)

- Kurumsal sorumluluk ve idari şeffaflığın temel güvencesi olarak sisteme gömülü olan akıllı "Otomatik İşlem/Olay Algılayıcıları" aktif çalışacak olup; her türlü kullanıcının ya da yetkilinin (kurum iç/kurum dışı paydaş) uygulama ekranlarında başlattığı ilk başarılı erişim, güvenlik şifresi aşımları ve kapı çıkışı / iş bitim terk eylemlerinin tüm sekansları derhal algılanarak saniyesinde Eyyübiye Belediyesinin yalıtımlı Yüksek Disiplin / Olay Geçmiş Dosyası klasörüne silinemez surette zerk/kayıt edilecektir.
- Bilhassa sisteme dahil olma / güvenli bölge girişi raporu, müfettiş birimi araştırmaları/suistimal analiz durumlarında kanıt belgesi niteliğine de sayılabilmesi namına sadece tarih saatle kısıtlı kalmaksızın formülize eklere bölüştürülecektir. Ekranına düşürüldüğü loglarda kullanıcının güncel yetkili bağlantısı olduğu unvan adı ve geçerli bir sistem giriş cihaz elektronik (hesap-kimlik veya elektronik-mail) belgesi detayında olduğu ve geçiş gerçekleştirdiği katmanın hiyerarşi tür kanıt tipleri gibi delillendirmeler kapalı mimari ile ek/saklı nitelikte sistem güvenlik arka hafızasına kalıcı kazınacaktır.
- İş hacminin kalbi olan tüm operasyon form akışlarıyla entegresi kurulması amacıyla örneğin, Bir Evrak Ruhsat Dosyası beyanat evrak ve dosyaları eki/bildirgesi resmen idare onay sırasına/havuzuna, Gönder tuşundan veya onanma/şutlanma yetkilisinden iletilen salisenin aynı ve de çok net algılanım eylemsel farkındalığı; Akıllı Olay denetimi gözcülerince merkeze kıskıvrak derhal şutlanacak, o dosyanın da log (olay evveliyat siciline) hiçbir gecikmeli zaman fire boşluğu bırakmayacak net kesin uyarıyla raptedilecektir.


### 19.6. ROL VE İZİN YÖNETİM ARAYÜZÜ

- Sistem üzerinde organizasyon şemalarını veya paydaş firma unvan türlerini temsil edecek olan "Makam, Rol ve Unvan Yöneticisi" merkezi ekranında; tanımlanmış olan tüm mevcut sorumluluk seviyeleri (işçi, formen, büro memuru, alt-amir, vb. rütbe statüleri) yetki gücü sıralamasıyla ekrana taşınacak ve hangi statünün sayıca (kütlesel istatistik formuyla) kaç farklı "yapabilirlik (ekran / işlem buton kullanım yetki vb.) özgürlük anahtarına" erişim hakkına barındırıldığı şeffaf özet çizelgesine dökülecektir.
- Bir kurumsal evre değişimi ihtiyacı veya sistemde yepyeni hiyerarşik personel üniform/birim rol statüsü tesis edilmeye / inşa atamaya niyet edildiğinde oluşturulan işlem masasında yetki işaretlemesi büyük akıllı sisteme emanetle yönetilecektir. Yeni rütbeye, serpilecek veya bahşedilecek onay erişim yahut kısıt limit özellik adedi bütünü (rastgele dağınık düzende boğmadan) modüler şema-katalog disiplini (örn: Yalnız Ödemecilik paket erişimi kutusu altında on-off (ver/al), Belge basımlarında kilit grup düğmesi onay seç/çek vs.) sekmeler hiyerarşisinin çok klas kategorilerde ve basit göz/mouse işaret denetimi vasıtası kontrol yeteneği mimarisine yer açtırılacaktır.
- "Alt mertebenin / Bir personelin kendi idaresini atlayıp gizli kurnazlıklara kendi pozisyonundan yahut kendinin mesai personelince bir tepe kuruma yönetici seviyesi transfer etmesi (Kurumsal Hak İşgali Eylemleri) ve sistem kurallarını zapt edilmesinin" önünün kökten bloklama amacıyla mimari demir zırh çalışacaktır; Sistemin En Yüksek "Tepe İmtiyaz Makamı / Kurumsal Süper Hakim Yöneticilik Düzeyi Haklarına" dokunup bu rütbeyi sarsma-güncelleştirme düzen yetkisi kati surette sadece yetkin ve kendi ligi ayarında Tepe Süper Amirliklerce açılabilir ve muameleye onay bulunabilir yalıtıma kavuşturulmuştur.
- Dev idarenin arşivlenerek mühürlü bir kenara durmuş yahut bir personelin ayrılması ve kurum sicili sonlansalar da evraklarında onanan rütbeleri/unvan kökenlerine izi sürülebilsin (Arşiv Güvence Nizam Prensibi Korunsun, Geçmiş belgeler silinen boşta havaya (kim imzaladı hatasına) yetkisize dönmesin mantık formülünde); Sistemin bel kemiği makam kademe başlıklarından (Ünvanlar Ağacında Kök / Roller yapısı nevi) olan hiç kimlik yahut statü sıfat grubu menüsü kökünden yok edim işlemlerine tamamen teknik engel, kapatılma sistemi getirilmiştir. Unvanların veritabanından sökülerek silinmesi, olası arıza doğurucu vaka hasarlarının kesimini kitleme suretiyle idarenin daima bekâ ve garantör formata tabi kalması kesin yasaktır.



### 19.7. KULLANICI ARAYÜZÜ VE YÖNETİM İŞLEMLERİ


- Kullanıcı listesi, oturum açmış kullanıcının yetki kapsamına göre filtrelenmelidir (tenant scope).
- Kullanıcı oluşturma sayfasında; ad, e-posta, telefon, kurum (yetki kapsamındaki kurumlar), rol (yetki seviyesinin altındaki roller) ve şifre alanları bulunmalıdır.
- Kullanıcı düzenleme sayfasında; kullanıcı bilgileri güncellenebilmeli, şifre isteğe bağlı olarak değiştirilebilmeli, rol ataması yapılabilmelidir.
- Kullanıcı silme işleminde, kullanıcının kendisini silmesi engellenmelidir.
- Kullanıcı aktif/pasif durumu is_active alanı ile yönetilmelidir.

### 19.8. SİDEBAR MENÜ YETKİLENDİRMESİ

- Sidebar menü öğeleri, kullanıcının rolüne ve izinlerine göre dinamik olarak gösterilmelidir:
  - "Firmalar & Lisanslar", "Belge Ayarları" ve "Sistem Logları" menüleri yalnızca super-admin rolüne sahip kullanıcılara gösterilmelidir.
  - PRO modül menüleri (Canlı Saha İzleme, Görev Emri Yönetimi, Gelişmiş Saha Raporlama, Evrak ve Tevdi) ilgili permission iznine sahip kullanıcılara gösterilmelidir.
  - CBS Entegrasyon menüsü  iznine sahip tüm kullanıcılara gösterilmelidir.
  - Saha personeline (field-team) yalnızca Dashboard, Görevlerim ve Profil menüleri gösterilmelidir.
  - "Kullanım Kılavuzu" bağlantısı saha personeli dışındaki tüm kullanıcılara gösterilmelidir.

---

**Son Güncelleme:** Temmuz 2026  
**Hazırlayan Firma:** HGB Bilişim Sistemleri Tic. Ltd. Şti.  
**İletişim:** destek@hgbilisim.com | https://hgbilisim.com
