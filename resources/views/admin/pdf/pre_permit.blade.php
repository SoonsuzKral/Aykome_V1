@extends('admin.pdf.pdf_layout')

@section('title', 'Ön Kazı İzni Onayı')
@section('page_size', 'A4')
@section('page_margin', '15mm')

@section('extra_style')
<style>
body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; }

.header { text-align: center; margin-bottom: 20px; position: relative; }
.header-logo { position: absolute; left: 0; top: 0; }
.header-logo img { max-height: 85px; width: auto; }
.header .tc { font-size: 13pt; font-weight: bold; }
.header .belediye { font-size: 14pt; font-weight: bold; margin-top: 2px; }
.header .mudurluk { font-size: 12pt; margin-top: 2px; }

.info-row { display: flex; justify-content: space-between; margin-top: 25px; font-size: 11pt; }
.info-row .left { text-align: left; }
.info-row .right { text-align: right; }

.alici { margin-top: 25px; font-size: 11pt; }
.ilgi { margin-top: 15px; font-size: 11pt; }
.paragraf { margin-top: 20px; font-size: 11pt; text-align: justify; line-height: 1.5; }
.paragraf p { margin-bottom: 8px; }

.imza { margin-top: 40px; text-align: right; font-size: 11pt; }
.imza .ad { font-weight: bold; margin-top: 5px; }
.imza .unvan { margin-top: 2px; }
.imza .vekalet { margin-top: 2px; font-style: italic; }

.altbilgi { margin-top: 40px; font-size: 8pt; border-top: 1px solid #ccc; padding-top: 8px; }
.altbilgi .row { display: flex; justify-content: space-between; margin-bottom: 2px; }
.altbilgi .sol { text-align: left; }
.altbilgi .sag { text-align: right; }

.footer-sayfa { font-size: 9pt; text-align: right; margin-top: 20px; }
</style>
@endsection

@section('content')
<div class="header">
    <div class="header-logo">
        <img src="https://www.eyyubiye.bel.tr/images/logo.png" alt="Eyyübiye Belediyesi">
    </div>
    <div class="tc">T.C.</div>
    <div class="belediye">{{ $belediye ?? 'EYYÜBİYE BELEDİYE BAŞKANLIĞI' }}</div>
    <div class="mudurluk">{{ $mudurluk ?? 'Fen İşleri Müdürlüğü' }}</div>
</div>

<div class="info-row">
    <div class="left">
        Sayı : <b>{{ $sayi ?? 'E-18790261-755-555505' }}</b>
    </div>
    <div class="right">
        {{ $tarih ?? '09/06/2026' }}
    </div>
</div>
<div class="info-row" style="margin-top:2px;">
    <div class="left">
        Konu : <b>{{ $konu ?? 'Kazı İzni Hk.' }}</b>
    </div>
</div>

<div class="alici">
    <b>{{ $kurum ?? 'DİCLE ELEKTRİK DAĞITIM AŞ.' }}</b>'a
</div>

<div class="ilgi">
    İlgi : {{ $ilgi_tarih ?? '30.04.2026' }} tarih ve {{ $ilgi_sayi ?? '1176543' }} sayılı yazınız.
</div>

<div class="paragraf">
    {!! $metin ?? '' !!}
</div>

<div class="paragraf" style="margin-top:15px;">
    "ÖN İZNİ" verilmiştir.
</div>

<div class="paragraf" style="margin-top:5px;">
    Gereğini rica ederim.
</div>

<div class="imza">
    <div class="ad">{{ $imza_ad ?? 'Yetkili' }}</div>
    <div class="unvan">{{ $imza_unvan ?? 'Belediye Başkan Yardımcısı' }}</div>
    <div class="vekalet">V.</div>
    <div style="margin-top:2px;">Başkan a.</div>
</div>

<div class="altbilgi">
    <div class="row">
        <span class="sol">{{ $adres ?? 'Eyyüpnebi mh. 3554. Sk. Eski Ptt Binası Eyyübiye / Şanlıurfa' }}</span>
        <span class="sag">Bilgi için {{ $bilgi_kisi ?? 'Zeynelabidin AKTAŞOĞLU' }}</span>
    </div>
    <div class="row">
        <span class="sol">Telefon: {{ $telefon ?? '()' }} Fax: {{ $fax ?? '()' }}</span>
    </div>
    <div class="row">
        <span class="sol">E-Posta: {{ $eposta ?? '-' }} Web: {{ $web ?? '-' }}</span>
    </div>
    <div class="row">
        <span class="sol">{{ $kep_adresi ?? 'eyyubiye@hs03.kep.tr' }}</span>
    </div>
</div>

<div class="footer-sayfa">1/1</div>
@endsection
