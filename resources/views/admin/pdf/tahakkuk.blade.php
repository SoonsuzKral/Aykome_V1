@extends('admin.pdf.pdf_layout')

@section('title', 'Tahakkuk Fişi')
@section('page_width', '598.68pt')
@section('page_height', '843.12pt')
@section('page_padding', '20pt 25pt')

@section('extra_style')
<style>
body { font-family: 'Times New Roman', Times, serif; font-size: 9pt; color: #000; }

.baslik { text-align: center; margin-bottom: 12pt; }
.baslik .belediye { font-size: 13pt; font-weight: bold; }
.baslik .mudurluk { font-size: 10pt; }
.baslik .birim { font-size: 9pt; }
.baslik .altbaslik { font-size: 11pt; font-weight: bold; margin-top: 5pt; }

.bilgi-grid { margin-top: 8pt; font-size: 9pt; }
.bilgi-grid table { width: 100%; border-collapse: collapse; }
.bilgi-grid td { padding: 3pt 5pt; border: 1px solid #000; }
.bilgi-grid .label { font-weight: bold; width: 30%; }
.bilgi-grid .value { width: 70%; }

.tablo { margin-top: 8pt; width: 100%; border-collapse: collapse; font-size: 8pt; }
.tablo th, .tablo td { border: 1px solid #000; padding: 2pt 4pt; text-align: center; }
.tablo th { background: #eee; font-weight: bold; }
.tablo td.l { text-align: left; }
.tablo td.r { text-align: right; }
.tablo td.num { text-align: right; }

.toplamlar { margin-top: 8pt; font-size: 9pt; }
.toplamlar table { width: 100%; border-collapse: collapse; }
.toplamlar td { padding: 3pt 5pt; border: 1px solid #000; }
.toplamlar .label { width: 70%; }
.toplamlar .value { width: 30%; text-align: right; font-weight: bold; }

.onay { margin-top: 15pt; font-size: 8pt; }
.onay table { width: 100%; }
.onay td { text-align: center; padding: 5pt; }
.onay .cizgi { border-top: 1px solid #000; padding-top: 3pt; margin-top: 15pt; }

.footer { margin-top: 20pt; font-size: 7pt; }
.footer table { width: 100%; }
.footer td { padding: 1pt 3pt; }

.aciklama { margin-top: 8pt; font-size: 8pt; border: 1px solid #000; padding: 5pt; }
</style>
@endsection

@section('content')
<div class="baslik">
    <div class="belediye">{{ $belediye ?? 'EYYÜBİYE BELEDİYESİ' }}</div>
    <div class="mudurluk">{{ $mudurluk ?? 'FEN İŞLERİ MÜDÜRLÜĞÜ' }}</div>
    <div class="birim">{{ $birim ?? 'AYKOME BİRİMİ' }}</div>
    <div class="altbaslik">{{ $altbaslik ?? 'ALTYAPI TESİSİ AÇIM RUHSAT BEDELİ HESABI' }}</div>
</div>

<div class="bilgi-grid">
    <table>
        <tr>
            <td class="label">Talep sahibi</td>
            <td class="value">{{ $talep_sahibi ?? 'DİCLE ELEKTRİK DAĞITIM A.Ş. ŞANLIURFA İL MÜDÜRLÜĞÜ' }}</td>
        </tr>
        <tr>
            <td class="label">ilçe</td>
            <td class="value">{{ $ilce ?? 'EYYÜBİYE' }}</td>
        </tr>
        <tr>
            <td class="label">Adres / Proje Adı</td>
            <td class="value">{{ $adres ?? 'C-26-1100-1063-0019 EYYÜPNEBİ ADA: PARSEL: EV' }}</td>
        </tr>
        <tr>
            <td class="label">Firma</td>
            <td class="value">{{ $firma ?? 'DİCLE ELEKTRİK DAĞITIM A.Ş. ŞANLIURFA İL MÜDÜRLÜĞÜ' }}</td>
        </tr>
        <tr>
            <td class="label">İş Cinsi</td>
            <td class="value">{{ $is_cinsi ?? 'ENH TESİS YAPIM İŞİ' }}</td>
        </tr>
        <tr>
            <td class="label">V. No / Telefon no:</td>
            <td class="value">{{ $vergino ?? '2950368442-04742868630' }}</td>
        </tr>
    </table>
</div>

<table class="tablo">
    <thead>
        <tr>
            <th style="width:28%;">ZEMİN CİNSİ</th>
            <th style="width:8%;">BİRİM</th>
            <th style="width:12%;">MİKTAR</th>
            <th style="width:14%;">BİRİM FİYAT</th>
            <th style="width:16%;">TUTAR</th>
        </tr>
    </thead>
    <tbody>
        @foreach($metraj_satirlari ?? [] as $satir)
        <tr>
            <td class="l">{{ $satir['ad'] }}</td>
            <td>{{ $satir['birim'] ?? 'm2' }}</td>
            <td class="num">{{ $satir['miktar'] ?? '0,00' }}</td>
            <td class="num">{{ $satir['birim_fiyat'] ?? '0,00' }}</td>
            <td class="num">{{ $satir['tutar'] ?? '0,00' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="toplamlar">
    <table>
        <tr>
            <td class="label">Toplam Miktar</td>
            <td class="value">{{ $toplam_miktar ?? '545,80' }}</td>
        </tr>
        <tr>
            <td class="label">Toplam Tutar</td>
            <td class="value">{{ $genel_tutar ?? '493.403,20' }} TL</td>
        </tr>
    </table>
</div>

@if(!empty($tahakkuk_satirlari))
<div class="toplamlar" style="margin-top:5pt;">
    <table>
        @foreach($tahakkuk_satirlari as $satir)
        <tr>
            <td class="label">{{ $satir['ad'] }}</td>
            <td class="value">{{ $satir['tutar'] }} TL</td>
        </tr>
        @endforeach
    </table>
</div>
@else
<div class="toplamlar" style="margin-top:5pt;">
    <table>
        <tr><td class="label">Zemin Tahrip Bedeli</td><td class="value">{{ $tahrip_bedeli ?? '493.403,20' }} TL</td></tr>
        <tr><td class="label">K.D.V. (%20)</td><td class="value">{{ $kdv ?? '98.680,64' }} TL</td></tr>
        <tr><td class="label">Keşif Bedeli</td><td class="value">{{ $kesif_bedeli ?? '5.295,03' }} TL</td></tr>
        <tr><td class="label">ZTB Toplam</td><td class="value">{{ $ztb_toplam ?? '597.378,87' }} TL</td></tr>
        <tr><td class="label">Teminat</td><td class="value">{{ $teminat ?? '0,00' }} TL</td></tr>
        <tr style="font-weight:bold;"><td class="label">Genel Toplam</td><td class="value">{{ $genel_toplam ?? '597.378,87' }} TL</td></tr>
    </table>
</div>
@endif

@if(isset($arsiv_kodu))
<div class="footer">
    <table>
        <tr>
            <td>T.C.</td>
            <td>Arşiv Kodu: {{ $arsiv_kodu }}</td>
        </tr>
        <tr>
            <td>ŞANLIURFA İLİ EYYÜBİYE BELEDİYESİ</td>
            <td>GELİRLER MÜDÜRLÜĞÜ</td>
        </tr>
    </table>
</div>
@endif
@endsection
