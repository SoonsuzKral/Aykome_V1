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
    <div class="belediye" contenteditable="true">{{ $belediye ?? 'EYYÜBİYE BELEDİYESİ' }}</div>
    <div class="mudurluk" contenteditable="true">{{ $mudurluk ?? 'FEN İŞLERİ MÜDÜRLÜĞÜ' }}</div>
    <div class="birim" contenteditable="true">{{ $birim ?? 'AYKOME BİRİMİ' }}</div>
    <div class="altbaslik" contenteditable="true">{{ $altbaslik ?? 'ALTYAPI TESİSİ AÇIM RUHSAT BEDELİ HESABI' }}</div>
</div>

<div class="bilgi-grid">
    <table>
        <tr>
            <td class="label">Talep sahibi</td>
            <td class="value" contenteditable="true">{{ $talep_sahibi ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">ilçe</td>
            <td class="value" contenteditable="true">{{ $ilce ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Adres / Proje Adı</td>
            <td class="value" contenteditable="true">{{ $application->isMuhtelif() ? 'MUHTELİF CADDE VE SOKAK' : ($adres ?? '') }}</td>
        </tr>
        <tr>
            <td class="label">Firma</td>
            <td class="value" contenteditable="true">{{ $firma ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">İş Cinsi</td>
            <td class="value" contenteditable="true">{{ $is_cinsi ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">V. No / Telefon no:</td>
            <td class="value" contenteditable="true">{{ $vergino ?? '' }}</td>
        </tr>
    </table>
</div>

<table class="tablo">
    <thead>
        <tr>
            <th style="width:28%;" contenteditable="true">ZEMİN CİNSİ</th>
            <th style="width:8%;" contenteditable="true">BİRİM</th>
            <th style="width:12%;" contenteditable="true">MİKTAR</th>
            <th style="width:14%;" contenteditable="true">BİRİM FİYAT</th>
            <th style="width:16%;" contenteditable="true">TUTAR</th>
        </tr>
    </thead>
    <tbody>
        @php $loopZemin = (isset($metraj_satirlari) && ! empty($metraj_satirlari)) ? $metraj_satirlari : $application->surfaceLines; @endphp
        @forelse($loopZemin as $zeminSatir)
            @php
                $zeminAd  = trim((string)($zeminSatir['ad'] ?? ($zeminSatir->surfaceType->name ?? '')));
                $birim    = trim((string)($zeminSatir['birim'] ?? 'm2'));
                $miktar   = (string)($zeminSatir['miktar'] ?? (number_format((float)($zeminSatir->quantity ?? 0), 2, ',', '.')));
                $birimFiyat = (string)($zeminSatir['birim_fiyat'] ?? (number_format((float)($zeminSatir->surfaceType->price_per_m2 ?? 0), 2, ',', '.')));
                $tutar    = (string)($zeminSatir['tutar'] ?? (number_format((float)($zeminSatir->amount ?? 0), 2, ',', '.')));
            @endphp
            <tr>
                <td class="l" contenteditable="true">{{ $zeminAd }}</td>
                <td contenteditable="true">{{ $birim }}</td>
                <td class="num" contenteditable="true">{{ $miktar }}</td>
                <td class="num" contenteditable="true">{{ $birimFiyat }}</td>
                <td class="num" contenteditable="true">{{ $tutar }}</td>
            </tr>
        @empty
            <tr>
                <td class="l" contenteditable="true">—</td>
                <td contenteditable="true">—</td>
                <td class="num" contenteditable="true">0,00</td>
                <td class="num" contenteditable="true">0,00</td>
                <td class="num" contenteditable="true">0,00</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="toplamlar">
    <table>
        <tr>
            <td class="label">Toplam Miktar</td>
            <td class="value" contenteditable="true">{{ $application->toplam_miktar }}</td>
        </tr>
        <tr>
            <td class="label">Zemin Tahrip Bedeli</td>
            <td class="value" contenteditable="true">{{ $application->ztb_amount }} TL</td>
        </tr>
    </table>
</div>

<div class="toplamlar" style="margin-top:5pt;">
    <table>
        <tr><td class="label">Zemin Tahrip Bedeli</td><td class="value" contenteditable="true">{{ $application->ztb_amount }} TL</td></tr>
        <tr><td class="label">K.D.V. (%20)</td><td class="value" contenteditable="true">{{ $application->kdv_amount }} TL</td></tr>
        <tr><td class="label">Ruhsat Harcı</td><td class="value" contenteditable="true">{{ $application->license_fee }} TL</td></tr>
        <tr><td class="label">Keşif Bedeli</td><td class="value" contenteditable="true">{{ $application->discovery_fee }} TL</td></tr>
        <tr><td class="label">ZTB Toplam</td><td class="value" contenteditable="true">{{ $application->ztb_total }} TL</td></tr>
        <tr><td class="label">Teminat</td><td class="value" contenteditable="true">{{ $application->teminat_amount }} TL</td></tr>
        <tr style="font-weight:bold;"><td class="label">Genel Toplam</td><td class="value" contenteditable="true">{{ $application->general_total }} TL</td></tr>
    </table>
</div>

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
