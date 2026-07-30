@extends('admin.pdf.pdf_layout')

@section('title', 'Tahsilat Fişi')
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
.baslik .fis-no { font-size: 10pt; margin-top: 3pt; }

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

.odeme-bilgi { margin-top: 10pt; font-size: 8pt; border: 1px solid #d00; padding: 5pt; background: #fff5f5; }
.odeme-bilgi strong { color: #c00; }
</style>
@endsection

@section('content')
<div class="baslik">
    <div class="belediye">{{ $belediye ?? 'EYYÜBİYE BELEDİYESİ' }}</div>
    <div class="mudurluk">{{ $mudurluk ?? 'FEN İŞLERİ MÜDÜRLÜĞÜ' }}</div>
    <div class="birim">{{ $birim ?? 'AYKOME BİRİMİ' }}</div>
    <div class="altbaslik">{{ $altbaslik ?? 'KAZI İZNİ TAHSİLAT FİŞİ' }}</div>
    <div class="fis-no">Fiş No: {{ $fis_no ?? '' }} | Tarih: {{ $tarih ?? now()->format('d.m.Y') }}</div>
</div>

<div class="bilgi-grid">
    <table>
        <tr>
            <td class="label">Mükellef / Talep Sahibi</td>
            <td class="value">{{ $talep_sahibi ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Başvuru No</td>
            <td class="value">{{ $basvuru_no ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Adres / Proje</td>
            <td class="value">{{ $adres ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">İlçe</td>
            <td class="value">{{ $ilce ?? 'EYYÜBİYE' }}</td>
        </tr>
        <tr>
            <td class="label">İşin Adı</td>
            <td class="value">{{ $is_adi ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Vergi No / TCKN</td>
            <td class="value">{{ $vergino ?? '—' }}</td>
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
            <td class="label">Zemin Tahrip Bedeli (ZTB)</td>
            <td class="value">{{ $tahrip_bedeli ?? '0,00' }} TL</td>
        </tr>
        <tr>
            <td class="label">K.D.V. (%20)</td>
            <td class="value">{{ $kdv ?? '0,00' }} TL</td>
        </tr>
        <tr>
            <td class="label">Ruhsat Harcı</td>
            <td class="value">{{ $ruhsat_harci ?? '0,00' }} TL</td>
        </tr>
        <tr>
            <td class="label">Keşif Bedeli</td>
            <td class="value">{{ $kesif_bedeli ?? '0,00' }} TL</td>
        </tr>
        <tr>
            <td class="label">ZTB Toplam</td>
            <td class="value">{{ $ztb_toplam ?? '0,00' }} TL</td>
        </tr>
        <tr>
            <td class="label">Teminat</td>
            <td class="value">{{ $teminat ?? '0,00' }} TL</td>
        </tr>
        <tr style="font-weight:bold;">
            <td class="label">Genel Toplam (Ödenecek Tutar)</td>
            <td class="value">{{ $genel_toplam ?? '0,00' }} TL</td>
        </tr>
    </table>
</div>

<div class="odeme-bilgi">
    <strong>ÖNEMLİ:</strong> Bu fişte belirtilen tutarı belediye veznesine veya banka hesabımıza yatırdıktan sonra
    dekontu sisteme yükleyiniz. Ödeme yapılmadan ruhsat belgesi düzenlenmez.
</div>

<div class="onay">
    <table>
        <tr>
            <td>
                <div style="margin-top: 20pt;"></div>
                <div class="cizgi">DÜZENLEYEN</div>
                <div>{{ $duzenleyen ?? '' }}</div>
            </td>
            <td>
                <div style="margin-top: 20pt;"></div>
                <div class="cizgi">YETKİLİ İMZA / MÜHÜR</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <table>
        <tr>
            <td>T.C. {{ $belediye ?? 'EYYÜBİYE BELEDİYESİ' }}</td>
            <td style="text-align: right;">{{ $belediye ?? 'EYYÜBİYE BELEDİYESİ' }} | Gelirler Müdürlüğü</td>
        </tr>
    </table>
</div>
@endsection
