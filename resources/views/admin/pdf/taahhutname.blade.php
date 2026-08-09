@extends('admin.pdf.pdf_layout')

@section('title', 'TAAHHÜTNAME')
@section('page_size', 'A4')
@section('page_margin', '15mm')

@section('extra_style')
<style>
body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

.taahhut-baslik { text-align: center; margin-bottom: 18pt; }
.taahhut-baslik h1 { font-size: 20pt; font-weight: bold; letter-spacing: 1px; margin: 0 0 4pt 0; }
.taahhut-baslik .alt-baslik { font-size: 10pt; font-style: italic; }

.madde-list { margin-top: 14pt; text-align: justify; line-height: 1.6; }
.madde-list p { margin-bottom: 7pt; }
.madde-list .madde-no { font-weight: bold; }

.beyan { margin-top: 14pt; text-align: justify; line-height: 1.6; font-weight: bold; }
.not { margin-top: 12pt; text-align: justify; line-height: 1.5; font-size: 9.5pt; font-style: italic; }

.imza-alani { margin-top: 30pt; width: 100%; border-collapse: collapse; }
.imza-alani td { vertical-align: top; padding: 6pt; }
.imza-alani .baslik { font-weight: bold; font-size: 11pt; }
.imza-alani .bilgi { margin-top: 6pt; font-size: 10.5pt; line-height: 1.6; }
.imza-cizgi { margin-top: 28pt; border-top: 1px solid #000; width: 80%; padding-top: 4pt; font-size: 10pt; }
</style>
@endsection

@section('content')
@php
    // CELL-BASED AUTH: Taahhütname metinleri + 20 madde belediye (isMuni) için düzenlenebilir,
    // alt kurumda salt-okunur. En alttaki "TALEP SAHİBİ" ve "RUHSATI TESLİM ALAN" bilgi
    // hücreleri her iki taraf için de düzenlenebilir (sabit true); alt kurum yalnızca
    // RUHSATI TESLİM ALAN hücresini (data-sign-editable) sunucu güvenliğiyle kaydedebilir.
    // $forceMuni === true => belgelerin "belediye tarafı" görünümü üretilir (kayıt üssü için).
    $isMuni = ($forceMuni ?? false) || (auth()->check() && auth()->user()->isMunicipalityPersonel());
@endphp
<div class="taahhut-baslik">
    <h1 contenteditable="{{ $isMuni ? 'true' : 'false' }}">TAAHHÜTNAME</h1>
    <div class="alt-baslik" contenteditable="{{ $isMuni ? 'true' : 'false' }}">Altyapı Tesisleri Açım Ruhsatı Kapsamında Beyan ve Taahhüt</div>
</div>

<div class="madde-list">
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">1.</span> Kazıya başlamadan önce, 187 AKSA Gaz, 186 Elektrik Arıza, 185 ŞUSKİ Çağrı Merkezlerini arayarak yapılacak kazı hakkında bilgi vereceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">2.</span> Kazıdan çıkan hafriyat geri dolgu malzemesi olarak kullanmayacağımı ve yolda bırakmayarak anında döküm sahasına nakledeceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">3.</span> Tranşe çalışmalarında gece gündüz olmak üzere can ve mal emniyeti ile ilgili tüm önlemleri eksiksiz alacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">4.</span> Kurumca hazırlanan tranşenin başına ve sonuna "Ruhsat Sahibi", "İşin Yüklenicisi", "Ruhsatın Başlangıç ve Bitiş Tarihleri" ile "Ruhsatı Veren Kurumun Tam İsmini" gösteren tanıtıcı levha koyacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">5.</span> Tranşenin sağında, solunda, yaya ve araç trafiğini aksatmayacak şekilde bariyer koyacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">6.</span> Ana arterlerde gece çalışması esnasında ışıklı ikaz fenerleri ile akülü flaşör koyarak her türlü tedbiri alacağımı, reflektif olan işaret levhalarını Karayolları İşaret Tekniğine göre uygulayacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">7.</span> Trafiği etkileyecek altyapı tesis çalışmalarına başlamadan önce UKOME (Ulaşım Koordinasyon Merkezi)'nden gerekli izni alarak trafik işaretlerini kazı yerine uygun bir şekilde koyacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">8.</span> Diğer yeraltı tesislerine zarar verilmemesi için her türlü tedbiri alacağımı ve hatlı bulunan kurumlara uyarı yapacağımı, tesislere zarar verdiğim takdirde çıkarılacak hasar bedelini belirtilen süreler içerisinde ödeyeceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">9.</span> Elektrik kazılarında 200 m, telekomünikasyon kazılarında iki menhol arası, yağmur suyu ve atıksu kazılarında 2 baca arası, içme suyu için 50 m, AKSA (Doğalgaz) için 100 m'den fazla tranşe açmayacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">10.</span> Açacağım tranşelerde yapılan kazının teknik özelliğine göre kum veya stabilize olarak dolgu yapacağımı ve 30 cm'lik tabakalar halinde sererek tekniğine uygun olarak sıkıştırma yapacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">11.</span> Asfalt kaplamalı yollarda çalışma yapılırken asfalt kesme makinesi kullanarak çalışma yapacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">12.</span> Ruhsat ve projede belirtilen yerler haricinde ilave bir kazı yapmak gerekirse tekrar ruhsat alacağımı, ruhsat almadığım takdirde cezayı kabul edeceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">13.</span> T.C. Karayolları, T.C. Devlet Demiryolları vb. kuruluşların bakım ve sorumluluğu altında bulunan yollarda yapılacak altyapı çalışmaları için ilgili kurumlardan ayrıca izin alacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">14.</span> Altyapı çalışması yaptığım andan itibaren çalışma yapılan yerin içerisinde herhangi bir çökme, bozukluk ve hasar meydana gelirse bakım ve onarımdan ilgili kurumlardan ayrıca izin alacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">15.</span> Yukarıdaki maddelere uymadığım takdirde, ayrıca yaptığım tranşenin hasarlı bırakılması ve çökmesi halinde, hesaplanacak hasar bedelini ödeyeceğimi, hasar bedeli ödenmediği takdirde teminatımdan kesilmesine itiraz etmeyeceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">16.</span> Kazı yapan kurum tarafından kazı esnasında kurum ya da şirket personelinden birinin kazı bitene kadar kazı ruhsatıyla beraber kazının başında kalmasını sağlayacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">17.</span> Kaldırım ve asfalt kaplama olmayan yollarda yapacağım tranşede malzeme cinsi ne olursa olsun kırık ve eski malzemeyi kullanmayacağımı ve tamamını söküp yeniden yapacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">18.</span> Hangi kurum adına iş yapılıyorsa o kurumun amblemi ve ismini belirten fosforlu iş elbisesi giydireceğimi,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">19.</span> Kazı ruhsatında belirtilen kaplama cinsi ve metrajlarına uyacağımı,</p>
    <p contenteditable="{{ $isMuni ? 'true' : 'false' }}"><span class="madde-no">20.</span> Altyapı tesisi açım ruhsatında ödenen teminat tutarının 2 (iki) yıl içinde tarafımdan alınmaması durumunda gelir kaydedileceğini,</p>
</div>

<div class="beyan" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    Yukarıda belirtilen kurallara uyacağımı, uymadığım takdirde Eyyübiye Belediyesi tarafından uygulanacak yaptırımları kabul edeceğimi beyan ederim.
</div>

<div class="not" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    NOT: Altyapı çalışması yapılırken Trafik Müdürlüğü tarafından yayımlanmış olan "Şehiriçi Yollarda Yapım Bakım ve Onarım Çalışmalarında Alınması Gereken Emniyet Tedbirleri" yayını esas alınarak çalışmalar yürütülecektir.
</div>

@php
    $isVatandas = !$application->institution_id;
    $tamAd = trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? ''));
    $tamAd = $tamAd !== '' ? $tamAd : ($application->institution?->name ?? '');
    $tckn = $application->applicant_national_id ?? $application->tc_no ?? $application->identity_no ?? '-';
    $telefon = $application->applicant_phone ?? $application->tesis_sorumlusu_telefonu ?? $application->institution?->phone ?? '-';
@endphp

<table class="imza-alani">
    <tr>
        <td style="width:50%; text-align:left;">
            <div class="baslik" >TALEP SAHİBİ{{ $isVatandas ? ' / BAŞVURU YAPAN' : '' }}</div>
            <div class="bilgi" contenteditable="true">
                ADI SOYADI: <b>{{ mb_strtoupper($tamAd, 'UTF-8') }}</b><br>
                T.C. NO: {{ $tckn }}<br>
                TELEFON: {{ $telefon }}<br><br>
            </div>
        </td>
        <td style="width:50%; text-align:right;">
            <div class="baslik">RUHSATI TESLİM ALAN{{ $isVatandas ? ' / TALEP EDEN' : '' }}</div>
            <div class="bilgi" contenteditable="true" data-sign-editable="1">
                AD SOYAD: <b>{{ mb_strtoupper($tamAd, 'UTF-8') }}</b><br>
                T.C. NO: {{ $tckn }}<br>
                TELEFON: {{ $telefon }}<br><br>
            </div>
        </td>
    </tr>
</table>
@endsection
