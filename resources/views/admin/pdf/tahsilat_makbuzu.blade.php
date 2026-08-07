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
.print-bar { position: fixed; top: 1rem; left: 1rem; right: auto; z-index: 99999; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 8px 14px; border-radius: 10px; box-shadow: 0 5px 14px rgba(0,0,0,.35); }
.print-bar .btn-print { background: #2563eb; color: #fff; border: none; padding: 9px 22px; border-radius: 5px; font-weight: 700; font-size: 14px; cursor: pointer; }
.print-bar .btn-print:hover { background: #1d4ed8; }
.no-print { display: flex; }
@media print { 
    .no-print { display: none !important; }
    body { padding: 0; }
}
@page { size: A4; margin: 12mm; }

/* Ortak kurum logosu + Vanilla JS Mini Format Toolbar */
.print-logo { max-width: 140px !important; width: auto; height: auto; object-fit: contain; }
.toolbar {
    position: fixed; bottom: 14px; right: 14px; z-index: 99999;
    display: flex; gap: 5px; align-items: center;
    background: #1e3a8a; padding: 7px 9px; border-radius: 9px;
    box-shadow: 0 5px 14px rgba(0,0,0,.35);
}
.toolbar button {
    background: #2563eb; color: #fff; border: none;
    min-width: 32px; height: 32px; border-radius: 6px;
    font-weight: 700; cursor: pointer; font-size: 13px; line-height: 1;
}
.toolbar button:hover { background: #1d4ed8; }
.toolbar .sep { width: 1px; height: 20px; background: rgba(255,255,255,.25); margin: 0 2px; }
@media print { .toolbar { display: none !important; } }
</style>
</head>
<body>

<div class="print-bar no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Yazdır</button>
    <button onclick="window.print()" class="btn-print">💾 Şablonu Düzenle (Kaydet)</button>
</div>

<div class="doc-no">
    Belge No: <span contenteditable="true">{{ $application->application_no ?? '' }}</span>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Düzenleme Tarihi: <span contenteditable="true">{{ now()->format('d.m.Y H:i') }}</span>
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
        <th contenteditable="true">Başvuru No</th>
        <td><strong class="mono" contenteditable="true">{{ $application->application_no ?? '' }}</strong></td>
    </tr>
    <tr>
        <th contenteditable="true">Ad Soyad</th>
        <td contenteditable="true">{{ trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? '')) }}</td>
    </tr>
    <tr>
        <th contenteditable="true">T.C. Kimlik No</th>
        <td class="mono" contenteditable="true">{{ $application->applicant_national_id ?? '' }}</td>
    </tr>
    <tr>
        <th contenteditable="true">Kurum / Firma</th>
        <td contenteditable="true">{{ $application->institution?->name ?? '' }}</td>
    </tr>
    <tr>
        <th contenteditable="true">Kazı Adresi</th>
        <td contenteditable="true">{{ $application->isMuhtelif() ? 'MUHTELİF CADDE VE SOKAK' : ($application->address_text ?? '') }}</td>
    </tr>
    <tr>
        <th contenteditable="true">Ödeme Açıklaması</th>
        <td contenteditable="true">Altyapı Kazı Bedeli
            @if($application->project_code || $application->work_type)
                @php
                    $muzbIsParts = [];
                    if ($application->project_code) $muzbIsParts[] = 'Kod: ' . $application->project_code;
                    if ($application->work_type) $muzbIsParts[] = 'İş Cinsi: ' . $application->work_type;
                @endphp
                — {{ $muzbIsParts ? implode(' / ', $muzbIsParts) : ($application->excavation_reason ?? '') }}
            @endif
        </td>
    </tr>
    <tr>
        <th>Kazı Alanı</th>
        <td contenteditable="true">{{ number_format((float)($application->total_area_m2 ?? 0), 2, ',', '.') }} m²</td>
    </tr>
    <tr>
        <th>İzin Süresi</th>
        <td contenteditable="true">
            {{ $application->start_date?->format('d.m.Y') ?? '' }}
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
        <span class="amount" contenteditable="true">{{ number_format((float)$amount, 2, ',', '.') }}</span>
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

{{-- FOOTER — TEK SATIR + DAR MARGIN: A4 taşması engellenir --}}
<div class="footer-note" style="margin-top:10px; font-size:8px; line-height:1;">
    AYKOME Yazılımı ile otomatik üretilmiştir — {{ now()->format('d.m.Y H:i:s') }} · Vezne tahsilat belgesi olarak geçerlidir.
</div>

<!-- Vanilla JS Mini Format Toolbar -->
<div class="toolbar no-print">
    <button onclick="fmtCmd('bold')" title="Kalın (Ctrl+B)"><b>B</b></button>
    <button onclick="fmtCmd('italic')" title="İtalik (Ctrl+I)"><i>I</i></button>
    <button onclick="fmtCmd('underline')" title="Altı Çizili (Ctrl+U)"><u>U</u></button>
    <span class="sep"></span>
    <button onclick="fmtSize(1)" title="Yazıyı Büyüt">A+</button>
    <button onclick="fmtSize(-1)" title="Yazıyı Küçült">A−</button>
</div>

<script>
function fmtCmd(cmd) {
    document.execCommand(cmd, false, null);
}
function fmtSize(delta) {
    var sel = document.getSelection();
    var el = sel && sel.anchorNode
        ? (sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode : sel.anchorNode)
        : null;
    var size = 3;
    if (el && el.style && el.style.fontSize) {
        var px = parseFloat(el.style.fontSize);
        if (!isNaN(px)) size = Math.min(7, Math.max(1, Math.round(px / 2)));
    }
    size = Math.min(7, Math.max(1, size + delta));
    document.execCommand('fontSize', false, String(size));
    if (el && el.focus) el.focus();
}
</script>
</body>
</html>
