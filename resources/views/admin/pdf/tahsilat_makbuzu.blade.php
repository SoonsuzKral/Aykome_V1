<!DOCTYPE html>
<html lang="tr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #ffffff;
    padding: 28px 36px;
}
.doc-no {
    font-size: 9px;
    color: #94a3b8;
    text-align: right;
    margin-bottom: 8px;
    font-family: 'DejaVu Sans Mono', monospace;
}
.header-band {
    background: #1e293b;
    color: #ffffff;
    padding: 14px 20px;
    text-align: center;
    margin-bottom: 0;
}
.header-band .subtitle {
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 5px;
}
.header-band .title {
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 1px;
}
.blue-bar {
    background: #0284c7;
    color: #ffffff;
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    padding: 9px 0;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 22px;
}
.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}
.info-table tr th {
    background-color: #f8fafc;
    font-weight: bold;
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    padding: 9px 13px;
    border: 1px solid #e2e8f0;
    width: 36%;
}
.info-table tr td {
    font-size: 11px;
    color: #1e293b;
    padding: 9px 13px;
    border: 1px solid #e2e8f0;
}
.mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 10px; }
.amount-wrapper {
    border: 2px solid #0284c7;
    margin-bottom: 22px;
    background: #f0f9ff;
}
.amount-label-bar {
    background: #0284c7;
    color: #fff;
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 6px 14px;
}
.amount-value-row {
    padding: 14px;
    text-align: center;
}
.amount-value-row .amount {
    font-size: 28px;
    font-weight: bold;
    color: #0c4a6e;
}
.amount-value-row .currency {
    font-size: 14px;
    color: #0369a1;
}
.sig-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 32px;
}
.sig-table td {
    width: 50%;
    text-align: center;
    padding: 0 24px;
    vertical-align: top;
    border: none;
}
.sig-spacer { height: 44px; }
.sig-line {
    border-top: 1px solid #64748b;
    padding-top: 7px;
    font-size: 9px;
    font-weight: bold;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.notice-box {
    background: #fefce8;
    border: 1px solid #fde047;
    padding: 10px 14px;
    font-size: 9px;
    color: #713f12;
    margin-bottom: 20px;
    line-height: 1.7;
}
.notice-box strong { font-weight: bold; }
.footer-note {
    font-size: 8px;
    color: #94a3b8;
    text-align: center;
    border-top: 1px solid #e2e8f0;
    padding-top: 10px;
    margin-top: 28px;
}
</style>
</head>
<body>

<div class="doc-no">
    Belge No: {{ $application->application_no }}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Düzenleme Tarihi: {{ now()->format('d.m.Y H:i') }}
</div>

{{-- Header --}}
<div class="header-band">
    <div class="subtitle">T.C. Belediyesi &mdash; Fen İşleri Dairesi Başkanlığı</div>
    <div class="title">AYKOME Şube Müdürlüğü</div>
</div>
<div class="blue-bar">Altyapı Kazı Harcı Tahsilat Belgesi</div>

{{-- Info Table --}}
@php
    $amount = $application->discovery_amount ?? $application->total_price ?? 0;
@endphp

<table class="info-table">
    <tr>
        <th>Başvuru No</th>
        <td><strong class="mono">{{ $application->application_no }}</strong></td>
    </tr>
    <tr>
        <th>Ad Soyad</th>
        <td>{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</td>
    </tr>
    <tr>
        <th>T.C. Kimlik No</th>
        <td class="mono">{{ $application->applicant_national_id ?? '—' }}</td>
    </tr>
    <tr>
        <th>Kurum / Firma</th>
        <td>{{ $application->institution?->name ?? '—' }}</td>
    </tr>
    <tr>
        <th>Kazı Adresi</th>
        <td>{{ $application->address_text ?? '—' }}</td>
    </tr>
    <tr>
        <th>Ödeme Açıklaması</th>
        <td>Altyapı Kazı Bedeli
            @if($application->work_type || $application->excavation_reason)
                — {{ $application->work_type ?? $application->excavation_reason }}
            @endif
        </td>
    </tr>
    <tr>
        <th>Kazı Alanı</th>
        <td>{{ number_format((float)($application->total_area_m2 ?? 0), 2, ',', '.') }} m²</td>
    </tr>
    <tr>
        <th>İzin Süresi</th>
        <td>
            {{ $application->start_date?->format('d.m.Y') ?? '—' }}
            @if($application->end_date)
                &nbsp;&mdash;&nbsp;{{ $application->end_date->format('d.m.Y') }}
            @endif
        </td>
    </tr>
</table>

{{-- Amount Box --}}
<div class="amount-wrapper">
    <div class="amount-label-bar">Ödenecek Toplam Tutar</div>
    <div class="amount-value-row">
        <span class="amount">{{ number_format((float)$amount, 2, ',', '.') }}</span>
        <span class="currency">&nbsp;TL</span>
    </div>
</div>

{{-- Notice --}}
<div class="notice-box">
    <strong>Önemli:</strong> Bu belgeyi belediye veznesine ibraz ederek ödemenizi gerçekleştiriniz.
    Ödeme yapıldıktan sonra <strong>banka dekontunu veya vezne makbuzunu</strong> sisteme yükleyiniz.
    Yükleme yapılmadan ruhsat belgesi düzenlenmez.
</div>

{{-- Signatures --}}
<table class="sig-table">
    <tr>
        <td>
            <div class="sig-spacer"></div>
            <div class="sig-line">Düzenleyen</div>
        </td>
        <td>
            <div class="sig-spacer"></div>
            <div class="sig-line">Yetkili İmza / Mühür</div>
        </td>
    </tr>
</table>

<div class="footer-note">
    Bu belge HGB Bilişim  AYKOME Yazılımı tarafından {{ now()->format('d.m.Y H:i:s') }} tarihinde otomatik üretilmiştir.
    Vezne tahsilat belgesi olarak geçerlidir.
</div>

</body>
</html>
