<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
@page { margin: 30px 35px 70px; }
body {
    font-family: 'DejaVu Sans', sans-serif !important;
    font-size: 13px;
    line-height: 1.4;
    color: #000;
    margin: 0;
    padding: 0;
}
table { border-collapse: collapse; }

.header-3col { width: 100%; }
.header-3col td { vertical-align: middle; padding: 2px; }
.header-left { width: 20%; text-align: center; }
.header-center { width: 60%; text-align: center; }
.header-right { width: 20%; text-align: center; }
.header-logo-img { max-height: 65px; max-width: 70px; }
.header-title-line { border: none; border-top: 1.2px solid #000; margin: 6px 0 8px; }

.ref-table { width: 100%; margin-bottom: 4px; }
.ref-table td { padding: 2px 4px; vertical-align: top; font-size: 13px; }
.ref-label { font-weight: bold; width: 42px; white-space: nowrap; }

.recipient-box { text-align: center; margin: 24px 0 16px; font-weight: bold; font-size: 14px; }

.body-text { margin: 10px 0; text-align: justify; }
.body-text p { margin: 6px 0; text-indent: 40px; }

.permit-box {
    margin: 14px 0;
    border-left: 2.5px solid #000;
    padding: 8px 12px;
    text-align: justify;
    font-size: 13px;
    line-height: 1.5;
    background: #fafafa;
}

.signature-table { width: 100%; margin-top: 28px; }
.signature-table td { vertical-align: bottom; padding: 4px; }
.signature-right { width: 50%; text-align: right; }
.signature-left-empty { width: 50%; }
.signature-line { border-top: 1.5px solid #000; width: 220px; margin-left: auto; padding-top: 4px; font-weight: bold; font-size: 13px; text-align: center; }
.signature-title { font-size: 11px; margin-top: 1px; text-align: center; width: 220px; margin-left: auto; }

.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    text-align: center;
    border-top: 1px solid #ccc;
    padding-top: 4px;
}
.footer-red { color: red; font-size: 9px; font-weight: bold; }
.footer-addr { font-size: 8px; color: #555; margin-top: 2px; }

.dummy-logo {
    width: 65px;
    height: 55px;
    border: 1px dashed #bbb;
    font-size: 7px;
    color: #999;
    text-align: center;
    vertical-align: middle;
    padding: 4px;
    margin: 0 auto;
}
</style>
</head>
<body>

@php
    $setting = \App\Models\PreExcavationPermitSetting::getSingleton();
    $permitSetting = \App\Models\PermitSetting::getSingleton();
    $logoB64 = \App\Models\PreExcavationPermitSetting::toBase64DataUri($permitSetting->institution_logo_path ?? null);
    $stampB64 = \App\Models\PreExcavationPermitSetting::toBase64DataUri($permitSetting->municipality_stamp_path ?? null);
    $signB64  = \App\Models\PreExcavationPermitSetting::toBase64DataUri($setting->signature_path);
    $stampSigB64 = \App\Models\PreExcavationPermitSetting::toBase64DataUri($setting->stamp_path);

    $recipientName = $application->institution
        ? mb_strtoupper($application->institution->name, 'UTF-8')
        : (trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? ''))
            ? mb_strtoupper(trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? '')), 'UTF-8')
            : 'VATANDAŞ/FİRMA BAŞVURUSU');
@endphp

<table class="header-3col">
    <tr>
        <td class="header-left">
            @if($logoB64)
                <img src="{{ $logoB64 }}" class="header-logo-img" alt="Logo">
            @else
                <div class="dummy-logo">LOGO</div>
            @endif
        </td>
        <td class="header-center">
            <div style="font-weight:bold;">T.C.</div>
            <div style="font-weight:bold; font-size:14px;">EYYÜBİYE BELEDİYE BAŞKANLIĞI</div>
            <div style="font-weight:bold; font-size:12px;">Fen İşleri Müdürlüğü</div>
        </td>
        <td class="header-right">
            @if($stampB64)
                <img src="{{ $stampB64 }}" class="header-logo-img" alt="Amblem">
            @else
                <div class="dummy-logo">AMBLEM</div>
            @endif
        </td>
    </tr>
</table>

<hr class="header-title-line">

<table class="ref-table">
    <tr>
        <td class="ref-label">Sayı</td>
        <td>: E-18790261-755-{{ str_pad($application->id, 5, '0', STR_PAD_LEFT) }}</td>
        <td style="width:130px; text-align:right;">{{ date('d.m.Y') }}</td>
    </tr>
    <tr>
        <td class="ref-label">Konu</td>
        <td colspan="2">: Kazı İzni Hk.</td>
    </tr>
</table>

<div class="recipient-box">
    {{ $recipientName }}'na
</div>

<div class="body-text">
    <p><strong>İlgi :</strong> {{ $application->application_no }} sayılı yazınız.</p>
    <p>&nbsp;</p>
    <p>İlgi sayılı yazınız ile; Eyyübiye İlçesi {{ $application->address_text ?? 'Lokasyonlarda' }} belirtilen alanlarda altyapı çalışmalarınız için kazı izni talep edilmektedir.</p>
    <p>&nbsp;</p>
</div>

<div class="permit-box">
    <p>"Altyapı Tesisi Açım Ruhsatı" iş ve işlemlerinin kazı kesin metrajlarının tespit edilmesinden sonra tamamlanması, Yapılacak çalışmanın AYKOME Çalışma Usul ve Esasları Uygulama yönetmeliğine uygun olarak yapılması, çalışma yapılacak cadde ve sokakların kazı öncesinde AYKOME birimimize haber verilmesi ve diğer altyapı kuruluşlarının mevcut tesislerine zarar verilmesinin önlenmesi için bu kuruluşlara da yapılacak çalışma hakkında bilgi verilmesi koşulu ile kazı <b>"ÖN İZNİ"</b> verilmiştir.</p>
</div>

<div class="body-text">
    <p>Gereğini rica ederim.</p>
</div>

<table class="signature-table">
    <tr>
        <td class="signature-left-empty">&nbsp;</td>
        <td class="signature-right">
            @if($signB64)
                <img src="{{ $signB64 }}" style="max-height:45px;max-width:200px;" alt="İmza"><br>
            @endif
            <div class="signature-line">Mehmet ELĞÜN</div>
            <div class="signature-title">Belediye Başkan Yardımcısı V.</div>
            <div class="signature-title" style="font-size:10px;">Başkan a.</div>
        </td>
    </tr>
</table>

<div class="footer">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="text-align:center;padding:2px 0;">
                @if($stampSigB64)
                    <img src="{{ $stampSigB64 }}" style="max-height:35px;max-width:35px;" alt="Mühür">
                @else
                    <span style="display:inline-block;width:35px;height:35px;border:1px solid #999;vertical-align:middle;"></span>
                @endif
            </td>
        </tr>
    </table>
    <div class="footer-red">Bu çıktı, 5070 sayılı elektronik imza kanununa göre imzalanan belgenin {{ date('d.m.Y') }} tarihli kağıt kopyasıdır.</div>
    <div class="footer-addr">
        Eyyüpnebi Mah. 3554 Sk. Eski Ptt Binası, Haliliye / Şanlıurfa | Tel: 0(414) 123 45 67 | KEP: eyyubiye@hs01.kep.tr
    </div>
</div>

</body>
</html>