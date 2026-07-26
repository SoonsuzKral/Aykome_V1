<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
@page { margin: 30px 40px 80px 40px; }
body {
    font-family: 'DejaVu Sans', sans-serif !important;
    font-size: 13px;
    line-height: 1.4;
    color: #000;
    margin: 0;
    padding: 0;
}
table { border-collapse: collapse; }

.header-table { width: 100%; }
.header-table td { vertical-align: middle; padding: 2px; }
.header-logo-box { width: 30%; text-align: left; }
.header-logo-box img { max-height: 80px; }
.header-title-box { width: 70%; text-align: center; }
.header-title-main { font-size: 15px; font-weight: bold; margin: 0; }
.header-title-sub { font-size: 12px; margin: 2px 0; }
.header-title-line { border: none; border-top: 1.5px solid #000; margin: 8px 0 6px 0; }

.ref-table { width: 100%; margin-bottom: 4px; }
.ref-table td { padding: 2px 4px; vertical-align: top; font-size: 13px; }
.ref-label { font-weight: bold; width: 42px; white-space: nowrap; }

.recipient-box { text-align: center; margin: 30px 0 10px; font-weight: bold; }
.recipient-name { font-size: 14px; }
.recipient-dept { font-size: 13px; margin-top: 2px; }

.body-text { margin: 10px 0; text-align: justify; }
.body-text p { margin: 6px 0; text-indent: 40px; }
.body-text .ilgi { text-indent: 0; }

.area-table { width: 100%; border: 1px solid #000; margin: 14px 0; }
.area-table th { border: 1px solid #000; padding: 4px; font-weight: bold; text-align: center; font-size: 11px; }
.area-table td { border: 1px solid #000; padding: 4px; vertical-align: top; font-size: 11px; }

.sig-table { width: 100%; margin-top: 24px; }
.sig-table td { vertical-align: top; padding: 4px; }
.sig-left { width: 50%; font-size: 12px; line-height: 1.6; }
.sig-right { width: 50%; text-align: right; vertical-align: bottom; }
.sig-line { border-top: 1px solid #000; width: 220px; margin-left: auto; padding-top: 4px; font-weight: bold; font-size: 12px; text-align: center; }
.sig-title { font-size: 11px; margin-top: 1px; text-align: center; width: 220px; margin-left: auto; }
.sig-org { font-size: 10px; margin-top: 1px; text-align: center; width: 220px; margin-left: auto; }

.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    font-size: 8px;
    text-align: center;
    border-top: 1px solid #ccc;
    padding-top: 3px;
    color: #555;
}
.footer-red { color: red; font-weight: bold; font-size: 9px; }
.footer-table { width: 100%; border-collapse: collapse; }
.footer-table td { padding: 1px 4px; font-size: 8px; text-align: center; vertical-align: top; }

.dummy-logo {
    width: 100px; height: 80px; border: 1px dashed #999; font-size: 9px;
    color: #999; text-align: center; vertical-align: middle; padding: 4px;
}
</style>
</head>
<body>

@php
    $setting = \App\Models\PreExcavationPermitSetting::getSingleton();
    $logoDataUri = \App\Models\PreExcavationPermitSetting::toBase64DataUri($setting->logo_path);
    $stampDataUri = \App\Models\PreExcavationPermitSetting::toBase64DataUri($setting->stamp_path);
    $instName = mb_strtoupper($application->institution?->name ?? 'BAŞVURU SAHİBİ', 'UTF-8');
    $surfaceLines = $application->surfaceLines ?? collect();
    $toplamMiktar = 0;
    foreach ($surfaceLines as $line) { $toplamMiktar += max((float)($line->quantity ?? 0), 0); }
    $excavationAreas = $application->excavationAreas ?? collect();
    $applicantFullName = trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? ''));
    $applicantPhone = $application->applicant_phone ?? '';
    $projectName = mb_strtoupper($application->project_code ?? 'ALTYAPI', 'UTF-8');
@endphp

<table class="header-table">
    <tr>
        <td class="header-logo-box">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Logo" style="max-height:80px;">
            @else
                <div class="dummy-logo">LOGO</div>
            @endif
        </td>
        <td class="header-title-box">
            <div class="header-title-main">{{ $instName }}</div>
            <div class="header-title-sub">Şanlıurfa Tesis Yöneticiliği</div>
        </td>
    </tr>
</table>

<hr class="header-title-line">

<table class="ref-table">
    <tr>
        <td class="ref-label">Sayı</td>
        <td>: E-50005665001100-100-{{ str_pad($application->id, 7, '0', STR_PAD_LEFT) }}</td>
        <td style="width:130px; text-align:right;">{{ $application->updated_at?->format('d.m.Y') ?? date('d.m.Y') }}</td>
    </tr>
    <tr>
        <td class="ref-label">Konu</td>
        <td colspan="2">: {{ $projectName }} PROJESİ KAZI ÖN İZNİ TALEP REVİZE</td>
    </tr>
</table>

<div class="recipient-box">
    <div class="recipient-name">EYYÜBİYE BELEDİYE BAŞKANLIĞI</div>
    <div class="recipient-dept">AYKOME ŞUBE MÜDÜRLÜĞÜ</div>
</div>

<div class="body-text">
    <p class="ilgi"><strong>İlgi :</strong> {{ $instName }} {{ $application->created_at?->format('d.m.Y') ?? '' }} tarihli ve {{ $application->application_no ?? '' }} sayılı yazısı.</p>
    <p>&nbsp;</p>
    <p>İlgi sayılı yazınız ile; Şirketimizden kazı izni sokaklarının mahalle isimleri güncellenmiştir.</p>
    <p>&nbsp;</p>
    <p>Şirketimiz {{ now()->format('Y') }} yılı yatırım programında <strong>{{ $application->project_code ?? '' }}</strong> pyp numarası ile yer alan <strong>ŞANLIURFA İLİ EYYÜBİYE İLÇESİ {{ $projectName }} MAHALLESİ</strong>
    @if($excavationAreas->isNotEmpty())
        @php $firstArea = $excavationAreas->first(); @endphp
        {{ $firstArea->address_text ?? '' }}
    @elseif($application->address_text)
        {{ $application->address_text }}
    @else
        belirtilen adres(ler)
    @endif
    adreslerinde gerçekleştirilecek olan altyapı çalışmaları için AYKOME sorumluluğunda bulunan cadde ve sokakların kazı izinleri belediyenizce verilmesi gerekmektedir.</p>
    <p>&nbsp;</p>
    <p>Elektrik şebekesi tesis çalışmaları yapılması planlanan cadde ve sokak isimleri aşağıdaki listede sunulmuştur.</p>
    <p>&nbsp;</p>
    <p>Gerekli kazı izninin verilmesi hususunda,</p>
    <p>Gereğini arz ederim.</p>
</div>

@if($excavationAreas->isNotEmpty())
    @php
        $grouped = $excavationAreas->groupBy(function($area) {
            return $area->address_text ?? 'Belirtilen';
        });
        $chunks = $surfaceLines->isNotEmpty() ? $surfaceLines->chunk(5) : collect();
    @endphp
    @foreach($grouped as $mahalle => $areas)
        <div style="font-weight:bold; font-size:12px; margin-top:10px; margin-bottom:4px;">{{ mb_strtoupper($mahalle, 'UTF-8') }}</div>
        <table class="area-table">
            <tr>
                <th style="width:30px;">#</th>
                <th>Cadde/Sokak</th>
            </tr>
            @foreach($areas as $idx => $area)
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td>{{ $area->address_text ?? '—' }}</td>
            </tr>
            @endforeach
        </table>
    @endforeach
@elseif($surfaceLines->isNotEmpty())
    <table class="area-table">
        <tr>
            <th style="width:30px;">#</th>
            <th>Zemin Türü</th>
            <th>Genişlik (m)</th>
            <th>Uzunluk (m)</th>
            <th>Miktar (m²)</th>
        </tr>
        @foreach($surfaceLines as $idx => $line)
        @php $q = max((float)($line->quantity ?? 0), 0); @endphp
        <tr>
            <td style="text-align:center;">{{ $idx + 1 }}</td>
            <td>{{ $line->surfaceType?->name ?? '—' }}</td>
            <td style="text-align:right;">{{ number_format((float)$line->width_m, 2, ',', '.') }}</td>
            <td style="text-align:right;">{{ number_format((float)$line->length_m, 2, ',', '.') }}</td>
            <td style="text-align:right;">{{ number_format($q, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>
@endif

<table class="sig-table">
    <tr>
        <td class="sig-left">
            <strong>Tesis Kontrol Mühendisi:</strong> {{ $applicantFullName ?: ($application->creator?->name ?? '—') }}<br>
            Tel: {{ $applicantPhone ?: '—' }}<br>
            @if($toplamMiktar > 0)
                <strong>Yaklaşık kazı miktarı:</strong> {{ number_format($toplamMiktar, 2, ',', '.') }} mt² dir.
            @endif
        </td>
        <td class="sig-right">
            @if($stampDataUri)
                <img src="{{ $stampDataUri }}" alt="Mühür" style="max-height:50px; margin-left:auto; display:block;">
            @endif
            <div class="sig-line">Fuat DEĞER</div>
            <div class="sig-title">Şanlıurfa İl Müdür Yardımcısı</div>
            <div class="sig-org">{{ $instName }}</div>
            <div class="sig-org">Şanlıurfa Tesis Yöneticiliği</div>
        </td>
    </tr>
</table>

<div class="footer">
    <table class="footer-table">
        <tr>
            <td>
                <span class="footer-red">Bu belge, güvenli elektronik imza ile imzalanmıştır.</span><br>
                Belge Doğrulama Kodu : {{ strtoupper(substr(md5($application->id . 'DEDAS'), 0, 12)) }} | Belge Doğrulama Adresi : https://ebyssorgu.dedas.com.tr<br>
                Adres: {{ $applicantFullName ?: ($application->creator?->name ?? '') }} | Tel: {{ $applicantPhone ?: '—' }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>