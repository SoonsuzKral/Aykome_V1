@extends('docs.layout')

@section('title', 'Kullanım Kılavuzu — HGB Bilişim AYKOME')

@section('content')

{{-- ═══════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════ --}}
<div class="py-10 sm:py-14 border-b border-gray-100">
    <div class="inline-flex items-center gap-2 rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/5 px-3 py-1 text-xs font-semibold text-[#02E0FB] mb-4">
        <span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] animate-pulse"></span>
        Güncel · v3 Ultra SaaS · 2026
    </div>
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
        HGB Bilişim AYKOME<br>
        <span class="bg-gradient-to-r from-[#02E0FB] to-[#FA6001] bg-clip-text text-transparent">Kullanım Kılavuzu</span>
    </h1>
    <p class="mt-4 max-w-2xl text-base text-gray-500 leading-relaxed">
        Bu kılavuz, HGB Bilişim AYKOME Altyapı Kazı İzin Yönetim Sistemi'ni kullanan
        <strong class="text-gray-700">belediye yöneticileri</strong>,
        <strong class="text-gray-700">kurum amirleri</strong> ve
        <strong class="text-gray-700">saha personeli</strong> için hazırlanmıştır.
        Her modül için adım adım talimatlar, önemli notlar ve teknik açıklamalar bulacaksınız.
    </p>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="#giris"    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 transition">Okumaya Başla <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
        <a href="#saha"     class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Saha Operasyonları</a>
        <a href="#ruhsat"   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Dijital Ruhsat</a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SECTION 1 — GİRİŞ VE İZOLASYON
═══════════════════════════════════════════════════════ --}}
<section id="giris" class="doc-section py-12 border-b border-gray-100">

    <div class="flex items-center gap-3 mb-1">
        <span class="step-badge">1</span>
        <span class="tag-badge bg-blue-50 text-blue-600">Temel</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mt-2">Sisteme Giriş ve Veri İzolasyonu</h2>
    <div class="section-title-bar"></div>

    <p class="text-gray-600 leading-relaxed mb-6">
        HGB Bilişim AYKOME, <strong>çok kiracılı (multi-tenant) bir SaaS mimarisinde</strong> çalışır.
        Her belediyenin altında birden fazla <em>kurum</em> (TEDAŞ, ŞUSKİ, AKSA, özel firmalar vb.) bulunabilir.
        Sistemde hiçbir kurumun verisi bir başkası tarafından görülemez —
        bu yalnızca bir tercih değil, yasal bir zorunluluktur.
    </p>

    {{-- Role Table --}}
    <h3 id="giris-rol-tablosu" class="text-lg font-semibold text-gray-900 mt-8 mb-4">Rol Tablosu ve Yetkiler</h3>

    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm mb-8">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-4 py-3 font-semibold text-gray-700 border-b border-gray-200">Rol</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 border-b border-gray-200">Kapsam</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 border-b border-gray-200">Başlıca Yetkiler</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 border-b border-gray-200">Göremeyeceği</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <span class="role-pill bg-violet-100 text-violet-700">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                            Super Admin
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">Tüm sistem</td>
                    <td class="px-4 py-3 text-gray-600">Lisans yönetimi, tüm belediyeler, tüm kurumlar, sistem logları, belge şablonları</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">—</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <span class="role-pill bg-blue-100 text-blue-700">Belediye Yöneticisi</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">Kendi belediyesi</td>
                    <td class="px-4 py-3 text-gray-600">Tüm başvurular, onay/ret, ruhsat, kullanıcı yönetimi, raporlama, fiyat kuralları</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">Diğer belediyeler, lisans ekranı</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <span class="role-pill bg-sky-100 text-sky-700">Kurum Yöneticisi</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">Kendi kurumu</td>
                    <td class="px-4 py-3 text-gray-600">Başvuru oluştur/düzenle, kurum personellerini yönet, durum takibi, raporlar</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">Diğer kurumların başvuruları, fiyat kuralları</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <span class="role-pill bg-cyan-100 text-cyan-700">Kurum Personeli</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">Kendi kurumu</td>
                    <td class="px-4 py-3 text-gray-600">Başvuru oluştur, harita çiz, evrak yükle, makbuz yükle</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">Fiyat detayları, diğer kurumlar, kullanıcı yönetimi</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3">
                        <span class="role-pill bg-amber-100 text-amber-700">Saha Ekibi (field-team)</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">Devredilen işler</td>
                    <td class="px-4 py-3 text-gray-600">Saha fotoğrafı çek, video yükle, kontrol notları ekle, durum güncelle</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">Silme/düzenleme, fiyat, kullanıcılar, kurumlar, raporlar</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- KVKK --}}
    <h3 id="giris-kvkk" class="text-lg font-semibold text-gray-900 mt-10 mb-4">KVKK Uyumu ve Veri İzolasyonu</h3>

    <div class="callout-danger rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            <div>
                <p class="font-semibold text-rose-700 text-sm">KVKK — Kişisel Veri Koruma Kanunu Uyarısı</p>
                <p class="mt-1 text-sm text-rose-600 leading-relaxed">
                    Sistemdeki tüm başvurular, vatandaş bilgileri ve adres verileri <strong>6698 sayılı KVKK</strong> kapsamında
                    kişisel veri niteliği taşır. Her kurum yalnızca kendi başvuru verilerine erişebilir;
                    çapraz kurum sorgusu sistem tarafından teknik olarak engellenir.
                    Ekran görüntüsü alınarak paylaşılması <strong>hukuki sorumluluk</strong> doğurabilir.
                    Audit logları tüm veri erişimlerini kayıt altına almaktadır.
                </p>
            </div>
        </div>
    </div>

    <div class="callout-info rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
            <div>
                <p class="font-semibold text-blue-700 text-sm">Teknik İzolasyon Nasıl Çalışır?</p>
                <p class="mt-1 text-sm text-blue-600 leading-relaxed">
                    Her sorgu arkasında <code class="bg-blue-100 px-1 rounded text-xs font-mono">institution_id</code>
                    filtresi otomatik eklenir. Belediye yöneticisi kendi kurumlarını görür;
                    kurum personeli yalnızca kendi kurumunun kayıtlarına ulaşabilir.
                    <code class="bg-blue-100 px-1 rounded text-xs font-mono">FieldTeamScope</code> middleware'i,
                    saha ekibinin erişebileceği rota listesini doğrudan kernel düzeyinde kısıtlar.
                </p>
            </div>
        </div>
    </div>

    <div class="doc-code mb-6">
<span class="cm">// Örnek: Kurum bazlı izolasyon (ApplicationsController)</span>
<span class="kw">$query</span> = Application::query();

<span class="kw">if</span> (auth()->user()->hasRole(<span class="str">'kurum-yonetici'</span>) || auth()->user()->hasRole(<span class="str">'kurum-personeli'</span>)) {
    <span class="kw">$query</span>->where(<span class="str">'institution_id'</span>, auth()->user()->institution_id);
}

<span class="kw">if</span> (auth()->user()->hasRole(<span class="str">'field-team'</span>)) {
    <span class="cm">// Yalnızca devredilen işler görünür</span>
    <span class="kw">$query</span>->whereHas(<span class="str">'assignments'</span>, fn(<span class="kw">$q</span>) =>
        <span class="kw">$q</span>->where(<span class="str">'assigned_to'</span>, auth()->id())
    );
}
    </div>

    <div class="callout-tip rounded-xl p-4">
        <p class="text-sm font-semibold text-[#02E0FB]">İpucu</p>
        <p class="mt-1 text-sm text-gray-600">
            Sisteme her giriş yaptığınızda bir <strong>lisans kontrol döngüsü</strong> çalışır.
            Kurumunuzun lisansı süresi dolmuşsa veya pasif yapılmışsa, ilgili ekranlar otomatik kilitlenir.
            Lisans sorunu için Super Admin ile iletişime geçin.
        </p>
    </div>

</section>

{{-- ═══════════════════════════════════════════════════════
     SECTION 2 — BAŞVURU VE HARİTA
═══════════════════════════════════════════════════════ --}}
<section id="basvuru" class="doc-section py-12 border-b border-gray-100">

    <div class="flex items-center gap-3 mb-1">
        <span class="step-badge">2</span>
        <span class="tag-badge bg-emerald-50 text-emerald-600">Başvuru</span>
        <span class="tag-badge bg-orange-50 text-orange-500">GeoJSON</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mt-2">Başvuru ve Harita (GeoJSON)</h2>
    <div class="section-title-bar"></div>

    <p class="text-gray-600 leading-relaxed mb-8">
        Altyapı kazı başvurusu <strong>beş ana adımda</strong> tamamlanır.
        Süreç boyunca sayfa yenilenmez; tüm form adımları Inertia.js ile modal / step-wizard içinde ilerler.
        Başvuru tamamlanmadan kaydedilip daha sonra devam edilebilir.
    </p>

    {{-- Adım 1: Yeni başvuru --}}
    <h3 id="basvuru-olusturma" class="text-lg font-semibold text-gray-900 mb-4">Adım 1 — Yeni Başvuru Oluşturma</h3>

    <div class="space-y-4 mb-8">
        @php
        $steps = [
            ['num' => 1, 'title' => 'Başvurular → Yeni Başvuru', 'desc' => 'Sol menüden <strong>Başvurular</strong> bölümüne gidin. Sağ üstteki <strong>"+ Yeni Başvuru"</strong> butonuna tıklayın.'],
            ['num' => 2, 'title' => 'Başvuran Bilgileri', 'desc' => 'Kurum seçin, başvuru yapan kişiyi seçin. Vatandaş başvurusu ise TC Kimlik, ad-soyad, telefon bilgilerini doldurun. Kurumsal başvurularda kayıtlı kurum kodu otomatik gelir.'],
            ['num' => 3, 'title' => 'Çalışma Türü ve Süre', 'desc' => '<strong>Kazı Sebebi</strong> (elektrik hattı, su borusu, doğalgaz vb.) ve <strong>Çalışma Türü</strong> seçin. Başlangıç / bitiş tarihlerini belirtin. İzin süresi otomatik hesaplanır.'],
            ['num' => 4, 'title' => 'Ek Açıklamalar', 'desc' => 'Varsa alt yüklenici bilgisini girin. Özel notlarınızı <strong>Açıklama</strong> alanına yazın. Bu alan ruhsat belgesinde görünür.'],
            ['num' => 5, 'title' => 'İlerle → Harita', 'desc' => 'Form eksiksizse <strong>"Harita ile Devam Et"</strong> butonuna tıklayın. Google Maps çizim ekranı açılır.'],
        ];
        @endphp
        @foreach($steps as $step)
        <div class="flex items-start gap-4">
            <span class="step-badge mt-0.5 flex-shrink-0">{{ $step['num'] }}</span>
            <div>
                <p class="font-semibold text-gray-800 text-sm">{{ $step['title'] }}</p>
                <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{!! $step['desc'] !!}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Harita --}}
    <h3 id="basvuru-harita" class="text-lg font-semibold text-gray-900 mb-4">Adım 2 — Polygon Çizimi (Google Maps)</h3>

    <p class="text-gray-600 leading-relaxed mb-4">
        Harita ekranı <strong>Google Maps JavaScript API</strong> üzerinde çalışır.
        Kurumunuzun rengi harita çizimlerinize otomatik uygulanır
        (TEDAŞ → kırmızı, ŞUSKİ → mavi, AKSA → turuncu, Belediye → yeşil).
    </p>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 p-4">
            <p class="font-semibold text-gray-800 text-sm mb-3">Sol Panel Araçları</p>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Adres Arama:</strong> İlçe/mahalle/sokak yazarak haritayı konumlandırın</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Dikdörtgen:</strong> Kare veya dikdörtgen alan için</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Polygon:</strong> Düzensiz alanlar için çokgen çizimi</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Çizgi:</strong> Boru hattı / kablo güzergahı için</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Silme / Düzeltme:</strong> Çizilen noktaları sürükle-bırak ile düzenle</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] flex-shrink-0"></span> <strong>Geçmiş Çizimler:</strong> Aynı bölgedeki önceki kazıları katman olarak gör</li>
            </ul>
        </div>
        <div class="rounded-xl border border-gray-200 p-4">
            <p class="font-semibold text-gray-800 text-sm mb-3">Anlık m² Hesaplama</p>
            <p class="text-sm text-gray-600 leading-relaxed mb-3">
                Her çizim hamlesi sırasında sol altta <strong>m² değeri canlı olarak güncellenir</strong>.
                Bu değer fiyat hesaplama motorunun temel girdisidir.
                Birden fazla alan çizebilirsiniz; toplam metrekare otomatik toplanır.
            </p>
            <div class="callout-warn rounded-lg p-3 text-xs text-amber-700">
                <strong>Uyarı:</strong> Alan m² bilgisi onaylandıktan sonra değiştirilemez.
                Yanlış çizim durumunda başvuruyu revize talep etmeniz gerekir.
            </div>
        </div>
    </div>

    <div class="doc-code mb-6">
<span class="cm">// GeoJSON formatında kaydedilen geometri örneği (excavation_areas tablosu)</span>
{
  <span class="str">"type"</span>: <span class="str">"Polygon"</span>,
  <span class="str">"coordinates"</span>: [[
    [<span class="kw">37.9235</span>, <span class="kw">40.2308</span>],
    [<span class="kw">37.9241</span>, <span class="kw">40.2308</span>],
    [<span class="kw">37.9241</span>, <span class="kw">40.2312</span>],
    [<span class="kw">37.9235</span>, <span class="kw">40.2312</span>],
    [<span class="kw">37.9235</span>, <span class="kw">40.2308</span>]  <span class="cm">// kapatma noktası</span>
  ]]
}
    </div>

    {{-- Kaydet --}}
    <h3 id="basvuru-kaydet" class="text-lg font-semibold text-gray-900 mb-4">Adım 3 — Çizimi Kaydet ve İlerle</h3>

    <div class="callout-success rounded-xl p-4 mb-4">
        <p class="text-sm font-semibold text-green-700">Çizim onaylandıktan sonra neler olur?</p>
        <ul class="mt-2 space-y-1 text-sm text-green-700 list-disc list-inside">
            <li>Alan verisi GeoJSON olarak <code class="bg-green-100 px-1 rounded text-xs font-mono">excavation_areas</code> tablosuna kaydedilir.</li>
            <li>Merkez koordinat otomatik hesaplanır (<code class="bg-green-100 px-1 rounded text-xs font-mono">center_lat</code>, <code class="bg-green-100 px-1 rounded text-xs font-mono">center_lng</code>).</li>
            <li>Fiyat hesaplama ekranına otomatik yönlendirilirsiniz.</li>
            <li>Harita izleme ekranında başvurunuz anlık olarak görünür hale gelir.</li>
        </ul>
    </div>

    <div class="callout-warn rounded-xl p-4">
        <p class="text-sm font-semibold text-amber-700">Google Maps API Key</p>
        <p class="mt-1 text-sm text-amber-600">
            API anahtarı <code class="bg-amber-100 px-1 rounded text-xs font-mono">.env</code> dosyasındaki
            <code class="bg-amber-100 px-1 rounded text-xs font-mono">GOOGLE_MAPS_API_KEY</code> değişkeninden okunur.
            Key'in <strong>Maps JavaScript API</strong> ve <strong>Geocoding API</strong> izinleri açık olmalıdır.
            Key limitiniz aşılırsa harita yüklenmez — Google Cloud Console'dan kota artırabilirsiniz.
        </p>
    </div>

</section>

{{-- ═══════════════════════════════════════════════════════
     SECTION 3 — ÖDEME VE MAKBUZ
═══════════════════════════════════════════════════════ --}}
<section id="odeme" class="doc-section py-12 border-b border-gray-100">

    <div class="flex items-center gap-3 mb-1">
        <span class="step-badge">3</span>
        <span class="tag-badge bg-yellow-50 text-yellow-600">Ödeme</span>
        <span class="tag-badge bg-gray-100 text-gray-600">Vezne</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mt-2">Fiyat Onayı ve Vezne / Makbuz</h2>
    <div class="section-title-bar"></div>

    <p class="text-gray-600 leading-relaxed mb-8">
        Harita çizimi tamamlandıktan sonra sistem otomatik olarak fiyat hesaplar.
        Ücretin ödenmesi <strong>belediye veznesinde fiziksel olarak</strong> yapılır;
        ardından tahsilat dekontu sisteme yüklenerek onay süreci tamamlanır.
    </p>

    {{-- Hesaplama --}}
    <h3 id="odeme-hesaplama" class="text-lg font-semibold text-gray-900 mb-4">Ücret Hesaplama Motoru</h3>

    <p class="text-gray-600 text-sm leading-relaxed mb-4">
        Ücret, <strong>Yönetim Paneli → Fiyatlandırma</strong> ekranında tanımlanan kurallarla hesaplanır.
        Personel bu kırılımı <em>görmez</em>; yalnızca belediye yöneticisi ve yetkililer detayları inceleyebilir.
    </p>

    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">Parametre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">Açıklama</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">Örnek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-600">
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-mono text-xs">surface_type</td>
                    <td class="px-4 py-3">Kaplama türü</td>
                    <td class="px-4 py-3">Asfalt, Beton Parke, Kilit Taş, Stabilize</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-mono text-xs">price_per_m2</td>
                    <td class="px-4 py-3">m² birim fiyatı</td>
                    <td class="px-4 py-3">100 TL/m²</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-mono text-xs">fixed_fee</td>
                    <td class="px-4 py-3">Sabit harç bedeli</td>
                    <td class="px-4 py-3">500 TL (her başvuruya eklenir)</td>
                </tr>
                <tr class="hover:bg-gray-50/60">
                    <td class="px-4 py-3 font-mono text-xs">teminat</td>
                    <td class="px-4 py-3">İş bitimine kadar bloke teminat</td>
                    <td class="px-4 py-3">Toplam ücretin %20'si</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="rounded-xl bg-gray-900 text-gray-100 p-5 mb-8 text-sm font-mono leading-relaxed">
        <p class="text-[#02E0FB] font-semibold mb-2">// Örnek Hesaplama</p>
        <p>Alan        : <span class="text-[#86efac]">450 m²</span></p>
        <p>Kaplama      : <span class="text-[#86efac]">Asfalt</span></p>
        <p>Birim Fiyat  : <span class="text-[#86efac]">100 TL / m²</span></p>
        <p>Sabit Harç   : <span class="text-[#86efac]">500 TL</span></p>
        <p class="mt-2 border-t border-gray-700 pt-2">Alan Tahrip  : <span class="text-[#fbbf24]">45.000 TL</span></p>
        <p>Altyapı Harcı: <span class="text-[#fbbf24]">500 TL</span></p>
        <p class="font-bold text-white mt-2">Genel Toplam : 45.500 TL</p>
        <p>Teminat (%20): <span class="text-[#FA6001]">9.100 TL</span></p>
    </div>

    {{-- Vezne --}}
    <h3 id="odeme-vezne" class="text-lg font-semibold text-gray-900 mb-4">Vezne Ödemesi Süreci</h3>

    <div class="relative pl-6 border-l-2 border-gray-200 space-y-5 mb-8">
        @php
        $veznesteps = [
            ['color' => 'bg-blue-500',   'title' => 'Fiyat Onayı Bekleniyor',    'desc' => 'Belediye yöneticisi fiyatı inceler ve onaylar. Başvuru durumu <strong>"Ödeme Bekliyor"</strong> olarak güncellenir.'],
            ['color' => 'bg-yellow-500', 'title' => 'Tahsilat Makbuzu Oluştur', 'desc' => 'Başvuru detay ekranında <strong>"Tahsilat Makbuzu İndir"</strong> butonu aktif olur. Bu PDF belediye veznesine götürülür.'],
            ['color' => 'bg-orange-500', 'title' => 'Fiziksel Ödeme',            'desc' => 'Kurum yetkilisi veya vatandaş, belirlenen tutarı <strong>belediye veznesine</strong> nakit/kredi kartı ile öder.'],
            ['color' => 'bg-green-500',  'title' => 'Dekont Yükleme',            'desc' => 'Ödeme sonrası alınan <strong>vezne dekontu / makbuz</strong> sisteme yüklenir. Format: JPEG, PNG veya PDF (max 10 MB).'],
            ['color' => 'bg-[#02E0FB]',  'title' => 'Onay ve Ruhsat',           'desc' => 'Makbuz kontrol edilip onaylandığında başvuru <strong>"Onaylandı"</strong> statüsüne geçer ve ruhsat belgesi üretilir.'],
        ];
        @endphp
        @foreach($veznesteps as $vs)
        <div class="relative">
            <span class="absolute -left-[1.375rem] top-1 h-3 w-3 rounded-full {{ $vs['color'] }} border-2 border-white"></span>
            <p class="font-semibold text-gray-800 text-sm">{{ $vs['title'] }}</p>
            <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{!! $vs['desc'] !!}</p>
        </div>
        @endforeach
    </div>

    {{-- Makbuz yükleme --}}
    <h3 id="odeme-makbuz" class="text-lg font-semibold text-gray-900 mb-4">Makbuz Yükleme — Sık Sorulan Sorular</h3>

    <div class="space-y-3 mb-6">
        <details class="group rounded-xl border border-gray-200 bg-white">
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50 rounded-xl">
                Hangi dosya formatları kabul edilir?
                <svg class="h-4 w-4 text-gray-400 group-open:rotate-180 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                <p>Sistem <strong>JPEG, JPG, PNG ve PDF</strong> formatlarını kabul eder. Maksimum dosya boyutu <strong>10 MB</strong>'tır.</p>
                <div class="doc-code mt-3">
<span class="cm">// Validation kuralı (StoreReceiptRequest)</span>
<span class="str">'receipt_file'</span> => [<span class="str">'required'</span>, <span class="str">'file'</span>,
    <span class="kw">Rule</span>::dimensions()->maxWidth(<span class="kw">10000</span>)->maxHeight(<span class="kw">10000</span>),
    <span class="str">'mimes:jpeg,jpg,png,pdf'</span>,
    <span class="str">'max:10240'</span>, <span class="cm">// 10 MB</span>
]
                </div>
            </div>
        </details>

        <details class="group rounded-xl border border-gray-200 bg-white">
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50 rounded-xl">
                "mimes validation" hatası alıyorum, ne yapmalıyım?
                <svg class="h-4 w-4 text-gray-400 group-open:rotate-180 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                <p class="mb-2">En yaygın nedenler:</p>
                <ul class="space-y-1 list-disc list-inside text-gray-600">
                    <li>Telefon galerisinden dönüştürülmüş <strong>.heic</strong> formatı (iOS) → JPEG'e çevirin.</li>
                    <li>Uzantısı <strong>.jpeg</strong> olan dosya (sistem <code class="font-mono text-xs bg-gray-100 px-1 rounded">jpeg</code> ve <code class="font-mono text-xs bg-gray-100 px-1 rounded">jpg</code> her ikisini destekler, kontrol edin).</li>
                    <li>Tarayıcı üretimi <strong>.tiff</strong> → PDF olarak taratın.</li>
                    <li>Dosya boyutu <strong>10 MB'ı aşıyor</strong> → PDF sıkıştırma aracı kullanın.</li>
                </ul>
            </div>
        </details>

        <details class="group rounded-xl border border-gray-200 bg-white">
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50 rounded-xl">
                Makbuz yüklendikten sonra bildirim gider mi?
                <svg class="h-4 w-4 text-gray-400 group-open:rotate-180 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                Evet. <code class="font-mono text-xs bg-gray-100 px-1 rounded">ReceiptUploaded</code> event'i tetiklenir.
                Belediye yöneticisinin açık olan tüm tarayıcı sekmelerine
                <strong>WebSocket (Laravel Reverb)</strong> üzerinden anlık SweetAlert bildirimi gider.
                Ses bildirimi de çalar. Ayrıca sistem içi <strong>notifications</strong> tablosuna kayıt düşer.
            </div>
        </details>
    </div>

    <div class="callout-danger rounded-xl p-4">
        <p class="text-sm font-semibold text-rose-700">Kritik Kural</p>
        <p class="mt-1 text-sm text-rose-600">
            Makbuz sisteme yüklenmeden <strong>nihai onay verilemez</strong>.
            Sistem bu kuralı teknik olarak zorlar —
            makbuz dosyası veya medya bağlantısı yoksa <code class="bg-rose-100 px-1 rounded text-xs font-mono">ValidationException</code> fırlatılır
            ve onay butonu devre dışı kalır.
        </p>
    </div>

</section>

{{-- ═══════════════════════════════════════════════════════
     SECTION 4 — SAHA OPERASYONLARI
═══════════════════════════════════════════════════════ --}}
<section id="saha" class="doc-section py-12 border-b border-gray-100">

    <div class="flex items-center gap-3 mb-1">
        <span class="step-badge">4</span>
        <span class="tag-badge bg-amber-50 text-amber-600">Saha</span>
        <span class="tag-badge bg-rose-50 text-rose-500">Kritik</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mt-2">Saha Operasyonları — Kalbin Attığı Yer</h2>
    <div class="section-title-bar"></div>

    <p class="text-gray-600 leading-relaxed mb-8">
        Başvuru onaylandıktan sonra saha denetim süreci başlar. Bu süreç,
        bir belediye veya kurum yöneticisinin işi <strong>saha personeline devretmesiyle</strong> tetiklenir.
        Sahacı <em>düzenleyemez, silemez</em> — yalnızca fotoğraf/video yükler ve durum günceller.
    </p>

    {{-- Devir --}}
    <h3 id="saha-devir" class="text-lg font-semibold text-gray-900 mb-4">Görev Devri</h3>

    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            </div>
            <p class="font-semibold text-gray-800 text-sm">1. Devreden</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Belediye veya kurum yöneticisi, onaylanan başvuruyu saha personeline atar. Görev tarihi ve notları ekler.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
            </div>
            <p class="font-semibold text-gray-800 text-sm">2. Sahacı</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Panel girişinde yalnızca devredilen işlerini görür. Her iş için 3 aşamayı sırasıyla tamamlamak zorundadır.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            </div>
            <p class="font-semibold text-gray-800 text-sm">3. Kapanış</p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">3. aşama tamamlandığında iş "Tamamlandı" statüsüne geçer, yönetici bilgilendirilir, teminat serbest bırakılabilir.</p>
        </div>
    </div>

    {{-- 3 Aşama --}}
    <h3 id="saha-asamalar" class="text-lg font-semibold text-gray-900 mb-6">3 Zorunlu Saha Kontrol Aşaması</h3>

    <div class="space-y-5 mb-8">
        {{-- Aşama 1 --}}
        <div class="rounded-2xl border-2 border-blue-200 bg-blue-50/50 p-5">
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white font-bold text-sm">1</span>
                <div>
                    <p class="font-bold text-blue-900">Kazı Öncesi Kontrol</p>
                    <p class="text-xs text-blue-600">Kazı başlamadan önce <em>mutlaka</em> tamamlanmalı</p>
                </div>
                <span class="ms-auto tag-badge bg-blue-200 text-blue-800">ZORUNLU</span>
            </div>
            <ul class="space-y-1.5 text-sm text-blue-800">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg> Zemin ve çevre fotoğrafı (min. 2 fotoğraf)</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/></svg> İsteğe bağlı video kaydı</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/></svg> Not / açıklama (zemin durumu, özel koşullar)</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg> GPS konumu otomatik eklenir (haritada doğrulama)</li>
            </ul>
        </div>

        {{-- Aşama 2 --}}
        <div class="rounded-2xl border-2 border-orange-200 bg-orange-50/50 p-5">
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#FA6001] text-white font-bold text-sm">2</span>
                <div>
                    <p class="font-bold text-orange-900">Kazı Tamamlanma Kontrolü</p>
                    <p class="text-xs text-orange-600">Kazı bittiğinde, zemin onarımı başlamadan önce</p>
                </div>
                <span class="ms-auto tag-badge bg-orange-200 text-orange-800">ZORUNLU</span>
            </div>
            <ul class="space-y-1.5 text-sm text-orange-800">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg> Kazı derinliği ve boyutlarını gösteren fotoğraflar</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/></svg> Video kaydı (önerilen)</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/></svg> Kazı notu (uygulama sürecindeki özel durumlar)</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-orange-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> Tarih/saat damgası otomatik</li>
            </ul>
        </div>

        {{-- Aşama 3 --}}
        <div class="rounded-2xl border-2 border-green-200 bg-green-50/50 p-5">
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-600 text-white font-bold text-sm">3</span>
                <div>
                    <p class="font-bold text-green-900">Zemin Onarımı Sonrası Kontrol</p>
                    <p class="text-xs text-green-600">Asfalt / parke döşendikten ve iş tamamen bittikten sonra</p>
                </div>
                <span class="ms-auto tag-badge bg-green-200 text-green-800">ZORUNLU</span>
            </div>
            <ul class="space-y-1.5 text-sm text-green-800">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg> Onarılmış zemin / kaplama fotoğrafı (min. 3)</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Final notlar ve kapanış yorumu</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg> GPS konum doğrulaması</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zm0 16a3 3 0 01-2.83-2h5.66A3 3 0 0110 18z"/></svg> <strong>FieldTaskCompleted</strong> event → yöneticiye anlık WebSocket bildirimi</li>
            </ul>
        </div>
    </div>

    {{-- Bildirim --}}
    <h3 id="saha-bildirim" class="text-lg font-semibold text-gray-900 mb-4">Gerçek Zamanlı Bildirim ve Sesli Uyarı</h3>

    <p class="text-gray-600 text-sm leading-relaxed mb-4">
        Saha aşaması tamamlandığında <strong>Laravel Reverb (WebSocket)</strong> üzerinden
        yetkili panellerine anlık bildirim gönderilir. Kullanıcı tarayıcıda aktif ise
        SweetAlert2 bildirimi + <strong>sesli uyarı</strong> çalar.
    </p>

    <div class="doc-code mb-6">
<span class="cm">// FieldTaskCompleted Event payload (broadcastWith)</span>
[
    <span class="str">'application_id'</span>   => <span class="kw">$this</span>->task->application_id,
    <span class="str">'application_no'</span>   => <span class="kw">$this</span>->task->application->application_no,
    <span class="str">'stage'</span>           => <span class="kw">$this</span>->task->control_stage, <span class="cm">// 1 | 2 | 3</span>
    <span class="str">'controlled_by'</span>   => <span class="kw">$this</span>->task->controlledBy->name,
    <span class="str">'message'</span>         => <span class="str">'Saha kontrolü tamamlandı.'</span>,
    <span class="str">'detail_url'</span>      => route(<span class="str">'admin.applications.show'</span>, <span class="kw">$this</span>->task->application_id),
]

<span class="cm">// Frontend listener (echo.js)</span>
window.Echo.channel(<span class="str">'admin-notifications'</span>)
    .listen(<span class="str">'.field.task.completed'</span>, (data) => {
        _toast(<span class="str">'success'</span>, <span class="str">'Saha Tamamlandı'</span>, data.message, data.detail_url);
        _playBeep(); <span class="cm">// Web Audio API ile ses</span>
    });
    </div>

    <div class="callout-tip rounded-xl p-4">
        <p class="text-sm font-semibold text-[#02E0FB]">Mobil ve Tablet Uyumu</p>
        <p class="mt-1 text-sm text-gray-600">
            Saha paneli telefonda tek kolon düzenine geçer. Fotoğraf çekimi için
            doğrudan kamera entegrasyonu (<code class="font-mono text-xs bg-gray-100 px-1 rounded">capture="environment"</code>) kullanılır.
            Tablet görünümünde harita ve form yan yana gösterilir.
        </p>
    </div>

</section>

{{-- ═══════════════════════════════════════════════════════
     SECTION 5 — RUHSAT / E-BELGE
═══════════════════════════════════════════════════════ --}}
<section id="ruhsat" class="doc-section py-12">

    <div class="flex items-center gap-3 mb-1">
        <span class="step-badge">5</span>
        <span class="tag-badge bg-purple-50 text-purple-600">E-Belge</span>
        <span class="tag-badge bg-gray-100 text-gray-600">PDF</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mt-2">Dijital Ruhsat E-Belge Çıktısı</h2>
    <div class="section-title-bar"></div>

    <p class="text-gray-600 leading-relaxed mb-8">
        Ödeme onaylandıktan ve tüm evraklar sisteme yüklendikten sonra
        sistem otomatik olarak <strong>resmi kazı ruhsatını PDF olarak üretir</strong>.
        Şablon <strong>Super Admin → Belge Ayarları</strong> ekranından kuruma göre özelleştirilebilir.
    </p>

    {{-- Şablon --}}
    <h3 id="ruhsat-sablonu" class="text-lg font-semibold text-gray-900 mb-4">PDF Şablon İçeriği</h3>

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Üst Bilgi Alanları</p>
            <ul class="space-y-1.5 text-sm text-gray-600">
                @php
                $fields = [
                    'Belge Numarası (otomatik sıralı)',
                    'Başvuru Tarihi',
                    'Talebi Yapan Kurum',
                    'Talebi Yapan Kullanıcı',
                    'Alt Yüklenici (varsa)',
                    'Kazı Sebebi',
                    'Çalışma Türü',
                    'Kazı Başlangıç / Bitiş Tarihi',
                    'Süre Uzatma Tarihleri (varsa)',
                    'Kazı Adresi',
                    'Açıklama',
                ];
                @endphp
                @foreach($fields as $f)
                <li class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 text-[#02E0FB] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">Maliyet Tablosu</p>
            <div class="overflow-x-auto rounded-lg border border-gray-200 text-xs">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold border-b">Alan Cinsi</th>
                            <th class="px-3 py-2 text-right text-gray-600 font-semibold border-b">Birim</th>
                            <th class="px-3 py-2 text-right text-gray-600 font-semibold border-b">Miktar</th>
                            <th class="px-3 py-2 text-right text-gray-600 font-semibold border-b">Tutar (TL)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr><td class="px-3 py-1.5">Asfalt</td><td class="px-3 py-1.5 text-right">m²</td><td class="px-3 py-1.5 text-right">450</td><td class="px-3 py-1.5 text-right">45.000</td></tr>
                        <tr class="bg-gray-50/50"><td class="px-3 py-1.5 font-semibold" colspan="3">Altyapı Kazı İzin Harcı</td><td class="px-3 py-1.5 text-right">500</td></tr>
                        <tr><td class="px-3 py-1.5 font-bold text-gray-900" colspan="3">Genel Toplam</td><td class="px-3 py-1.5 text-right font-bold text-gray-900">45.500</td></tr>
                        <tr class="bg-orange-50/50"><td class="px-3 py-1.5 text-[#FA6001]" colspan="3">Teminat Bedeli</td><td class="px-3 py-1.5 text-right text-[#FA6001]">9.100</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Alt İmza Alanları</p>
            <ul class="space-y-1 text-xs text-gray-600">
                <li>• Tanzim Eden — Ad Soyad / Unvan</li>
                <li>• Tanzim Tarihi</li>
                <li>• Daire Başkanı Onay / İmza Alanı</li>
            </ul>
        </div>
    </div>

    {{-- İndirme --}}
    <h3 id="ruhsat-indir" class="text-lg font-semibold text-gray-900 mb-4">Ruhsatı İndirme ve Arşivleme</h3>

    <div class="relative pl-6 border-l-2 border-purple-200 space-y-4 mb-8">
        @php
        $ruhsatsteps = [
            'Başvuru onaylandı (makbuz doğrulandı, evraklar tam).',
            'Sistem <code class="font-mono text-xs bg-gray-100 px-1 rounded">PermitDocumentGenerated</code> event\'ini tetikler.',
            'DomPDF ile <code class="font-mono text-xs bg-gray-100 px-1 rounded">resources/views/pdf/permit.blade.php</code> şablonu render edilir.',
            'Üretilen PDF <code class="font-mono text-xs bg-gray-100 px-1 rounded">Spatie Media Library</code> aracılığıyla başvuruya bağlanır ve arşivlenir.',
            'Başvuru detay ekranında <strong>"Ruhsatı İndir"</strong> butonu aktif olur.',
            'Kurum yöneticisi PDF\'i indirip kuruma iletir veya sistem doğrudan e-posta ile gönderir (gelecek sürüm).',
        ];
        @endphp
        @foreach($ruhsatsteps as $idx => $rs)
        <div class="relative">
            <span class="absolute -left-[1.375rem] top-1 h-3 w-3 rounded-full bg-purple-400 border-2 border-white"></span>
            <p class="text-sm text-gray-600 leading-relaxed">{!! $rs !!}</p>
        </div>
        @endforeach
    </div>

    <div class="doc-code mb-6">
<span class="cm">// Ruhsat üretme (LicenseDocumentService)</span>
<span class="kw">public function</span> generate(Application <span class="kw">$application</span>): <span class="kw">string</span>
{
    <span class="kw">$pdf</span> = Pdf::loadView(<span class="str">'pdf.permit'</span>, [
        <span class="str">'application'</span>  => <span class="kw">$application</span>->load(<span class="str">'excavationAreas'</span>, <span class="str">'pricingItems'</span>),
        <span class="str">'institution'</span>  => <span class="kw">$application</span>->institution,
        <span class="str">'settings'</span>     => PermitSettings::forInstitution(<span class="kw">$application</span>->institution_id),
    ])->setPaper(<span class="str">'a4'</span>, <span class="str">'portrait'</span>);

    <span class="kw">$filename</span> = <span class="str">'ruhsat-'</span> . <span class="kw">$application</span>->application_no . <span class="str">'.pdf'</span>;
    <span class="kw">$application</span>->addMediaFromString(<span class="kw">$pdf</span>->output())
        ->usingFileName(<span class="kw">$filename</span>)
        ->toMediaCollection(<span class="str">'permit_documents'</span>);

    <span class="kw">return</span> <span class="kw">$filename</span>;
}
    </div>

    <div class="callout-info rounded-xl p-4 mb-4">
        <p class="text-sm font-semibold text-blue-700">Şablon Özelleştirme</p>
        <p class="mt-1 text-sm text-blue-600">
            <strong>Super Admin → Belge Ayarları</strong> ekranından kuruma özel
            logo, başlık metni, imza blokları ve renk şeması tanımlanabilir.
            Bu ayarlar <code class="bg-blue-100 px-1 rounded text-xs font-mono">permit_settings</code> tablosunda tutulur
            ve PDF şablonu her üretimde güncel ayarları çeker.
        </p>
    </div>

    <div class="callout-warn rounded-xl p-4">
        <p class="text-sm font-semibold text-amber-700">Arşiv Politikası</p>
        <p class="mt-1 text-sm text-amber-600">
            Üretilen tüm ruhsat PDF'leri <strong>soft-delete korumalı</strong> olarak arşivlenir.
            Silme işlemi yalnızca Super Admin yetkisiyle yapılabilir.
            Arşivdeki belgeler <code class="bg-amber-100 px-1 rounded text-xs font-mono">admin.applications.show</code>
            ekranından indirilebilir ve <code class="bg-amber-100 px-1 rounded text-xs font-mono">admin.reports</code>
            raporlama modülünden filtrelenebilir.
        </p>
    </div>

    {{-- ─── Roller ve Yetki Matrisi ─────────────────────────────────────── --}}
    <div id="yetki-matrisi" class="mt-10 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500/15 to-cyan-500/10 border border-orange-400/30 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-orange-600">⚡ God-Mode</span>
            Roller ve Yetki Matrisi
        </h3>
        <p class="text-sm text-gray-500 mb-5">Sistemdeki her role atanmış izinlerin tam listesi. Yeşil ✓ o role tanımlı yetkiyi gösterir.</p>

        @php
            $docPermGroups = [
                'Sistem' => [
                    'system.license'  => 'Lisans Yönetimi',
                    'system.logs'     => 'Sistem Logları',
                    'system.settings' => 'Belge Ayarları',
                ],
                'PRO Modüller' => [
                    'pro.live_map'         => 'Canlı Saha İzleme',
                    'pro.work_orders'      => 'Görev Emri Yönetimi',
                    'pro.advanced_reports' => 'Gelişmiş Raporlama',
                ],
                'Başvurular' => [
                    'applications.view'            => 'Görüntüle',
                    'applications.create'          => 'Oluştur',
                    'applications.edit'            => 'Düzenle',
                    'applications.delete'          => 'Sil',
                    'applications.approve_pre_excavation' => 'Ön Kazı Onayla',
                    'applications.approve_price'   => 'Fiyat Onayla',
                    'applications.approve_receipt' => 'Makbuz Onayla',
                    'applications.issue_license'   => 'Ruhsat Düzenle',
                    'tasks.transfer'               => 'Göreve Aktar',
                    'licenses.manage'              => 'Lisans Kayıtları',
                    'surface_types.manage'         => 'Zemin Türleri',
                ],
                'Kurumlar & Kullanıcılar' => [
                    'users.manage'        => 'Kullanıcı Yönetimi',
                    'institutions.manage' => 'Kurum Yönetimi',
                ],
                'Saha' => [
                    'field.tasks_view'   => 'Görevleri Gör',
                    'field.upload_media' => 'Fotoğraf Yükle',
                ],
            ];
            $docRoles = [
                'super-admin'         => 'Super Admin',
                'municipality-admin'  => 'Bel. Yöneticisi',
                'municipality-staff'  => 'Bel. Personeli',
                'institution-manager' => 'Kurum Yöneticisi',
                'institution-staff'   => 'Kurum Personeli',
                'field-team'          => 'Saha Personeli',
            ];
            // Static matrix — canonical source of truth (seeder ile senkron)
            $docMatrix = [
                'system.license'               => ['super-admin' => 1],
                'system.logs'                  => ['super-admin' => 1],
                'system.settings'              => ['super-admin' => 1],
                'pro.live_map'                 => ['super-admin' => 1, 'municipality-admin' => 1],
                'pro.work_orders'              => ['super-admin' => 1, 'municipality-admin' => 1],
                'pro.advanced_reports'         => ['super-admin' => 1, 'municipality-admin' => 1],
                'applications.view'            => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1, 'institution-manager' => 1, 'institution-staff' => 1, 'field-team' => 1],
                'applications.create'          => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1, 'institution-manager' => 1, 'institution-staff' => 1],
                'applications.edit'            => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1, 'institution-manager' => 1, 'institution-staff' => 1],
                'applications.delete'          => ['super-admin' => 1, 'institution-manager' => 1],
                'applications.approve_pre_excavation' => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1],
                'applications.approve_price'   => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1],
                'applications.approve_receipt' => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1],
                'applications.issue_license'   => ['super-admin' => 1, 'municipality-admin' => 1],
                'tasks.transfer'               => ['super-admin' => 1, 'municipality-admin' => 1, 'municipality-staff' => 1],
                'licenses.manage'              => ['super-admin' => 1],
                'surface_types.manage'         => ['super-admin' => 1, 'municipality-admin' => 1],
                'users.manage'                 => ['super-admin' => 1, 'municipality-admin' => 1],
                'institutions.manage'          => ['super-admin' => 1, 'municipality-admin' => 1],
                'field.tasks_view'             => ['super-admin' => 1, 'field-team' => 1],
                'field.upload_media'           => ['super-admin' => 1, 'field-team' => 1],
            ];
        @endphp

        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-500 w-44">İzin</th>
                        @foreach($docRoles as $roleKey => $roleLabel)
                            <th class="px-2 py-3 text-center text-[9px] font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">{{ $roleLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($docPermGroups as $groupName => $perms)
                        <tr class="bg-gradient-to-r from-gray-50 to-white border-t-2 border-gray-200">
                            <td colspan="{{ count($docRoles) + 1 }}" class="px-4 py-2">
                                <span class="text-[10px] font-black uppercase tracking-[0.15em] text-gray-400">{{ $groupName }}</span>
                            </td>
                        </tr>
                        @foreach($perms as $permKey => $permLabel)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-4 py-2.5">
                                    <span class="block text-xs font-semibold text-gray-700">{{ $permLabel }}</span>
                                    <span class="block font-mono text-[9px] text-gray-400">{{ $permKey }}</span>
                                </td>
                                @foreach($docRoles as $roleKey => $roleLabel)
                                    <td class="px-2 py-2.5 text-center">
                                        @if(!empty($docMatrix[$permKey][$roleKey]))
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                        @else
                                            <span class="text-gray-200 text-sm">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-[11px] text-gray-400">* Super Admin tüm izinleri kapsar. Matris yalnızca super-admin dışındaki canonical atamayı göstermektedir.</p>
    </div>

    {{-- Final CTA --}}
    <div class="mt-10 rounded-2xl bg-gradient-to-br from-gray-900 to-slate-800 p-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex-1">
                <p class="font-bold text-lg">Daha fazlasına mı ihtiyacınız var?</p>
                <p class="text-sm text-gray-400 mt-1 leading-relaxed">
                    Bu kılavuzda bulamadığınız teknik detaylar, entegrasyon soruları
                    veya özelleştirme talepleri için HGB Bilişim  destek ekibine ulaşın.
                </p>
            </div>
            <a href="mailto:destek@HGB Bilişim .com"
               class="flex-shrink-0 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#02E0FB] to-[#0ab8d0] px-5 py-2.5 text-sm font-semibold text-white shadow-lg hover:opacity-90 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                destek@HGB Bilişim .com
            </a>
        </div>
    </div>

</section>

@endsection
