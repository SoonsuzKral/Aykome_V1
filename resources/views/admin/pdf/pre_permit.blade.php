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
@php
    // CELL-BASED AUTH: Ön Kazı belgesi doğrudan belediyeden çıkar; altkurum düzenleyemez.
    $isMuni = auth()->check() && auth()->user()->isMunicipalityPersonel();
@endphp
<div class="header">
    <div class="header-logo">
        <img src="https://www.eyyubiye.bel.tr/images/logo.png" alt="Eyyübiye Belediyesi" class="print-logo">
    </div>
    <div class="tc" contenteditable="{{ $isMuni ? 'true' : 'false' }}">T.C.</div>
    <div class="belediye" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $belediye ?? 'EYYÜBİYE BELEDİYE BAŞKANLIĞI' }}</div>
    <div class="mudurluk" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $mudurluk ?? 'Fen İşleri Müdürlüğü' }}</div>
</div>

<div class="info-row">
    <div class="left">
        Sayı : <b contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $sayi ?? '' }}</b>
    </div>
    <div class="right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
        {{ $tarih ?? '' }}
    </div>
</div>
<div class="info-row" style="margin-top:2px;">
    <div class="left">
        Konu : <b contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $konu ?? '' }}</b>
    </div>
</div>

<div class="alici">
    <b contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $kurum ?? '' }}</b>'a
</div>

<div class="ilgi" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    İlgi : {{ $ilgi_tarih ?? '' }} tarih ve {{ $ilgi_sayi ?? '' }} sayılı yazınız.
</div>

<div class="paragraf" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    {!! $metin ?? '' !!}
</div>

<div class="paragraf" style="margin-top:15px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    "ÖN İZNİ" verilmiştir.
</div>

<div class="paragraf" style="margin-top:5px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
    Gereğini rica ederim.
</div>

<div class="imza">
    <div class="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $imza_ad ?? '' }}</div>
    <div class="unvan" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $imza_unvan ?? 'Belediye Başkan Yardımcısı' }}</div>
    <div class="vekalet">V.</div>
    <div style="margin-top:2px;">Başkan a.</div>
</div>

<div class="altbilgi">
    <div class="row">
        <span class="sol" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $adres ?? '' }}</span>
        <span class="sag">Bilgi için <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $bilgi_kisi ?? '' }}</span></span>
    </div>
    <div class="row">
        <span class="sol">Telefon: <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $telefon ?? '' }}</span> Fax: <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $fax ?? '' }}</span></span>
    </div>
    <div class="row">
        <span class="sol">E-Posta: <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $eposta ?? '' }}</span> Web: <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $web ?? '' }}</span></span>
    </div>
    <div class="row">
        <span class="sol" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $kep_adresi ?? '' }}</span>
    </div>
</div>

<div class="footer-sayfa">1/1</div>
@endsection
