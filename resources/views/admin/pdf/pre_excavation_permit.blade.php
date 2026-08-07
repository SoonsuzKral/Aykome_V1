<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    @page { margin: 20mm 15mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #0e7490; padding-bottom: 15px; }
    .header img { max-height: 70px; margin-bottom: 8px; }
    .header h1 { font-size: 18px; font-weight: bold; color: #0e7490; margin: 5px 0; text-transform: uppercase; letter-spacing: 1px; }
    .header h2 { font-size: 13px; font-weight: normal; color: #334155; margin: 3px 0; }
    .header .doc-no { font-size: 10px; color: #64748b; margin-top: 5px; }

    .meta-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .meta-table td { padding: 4px 8px; border: 1px solid #cbd5e1; }
    .meta-table .label { font-weight: bold; background: #f0f9ff; width: 30%; color: #0c4a6e; font-size: 10px; }
    .meta-table .value { width: 70%; font-size: 10px; }

    .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .info-table th { background: #0e7490; color: white; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
    .info-table td { padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 10px; }
    .info-table tr:nth-child(even) { background: #f8fafc; }

    .section { margin: 15px 0; }
    .section-title { font-size: 12px; font-weight: bold; color: #0e7490; border-bottom: 2px solid #0e7490; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; }

    .footer { margin-top: 30px; border-top: 2px solid #cbd5e1; padding-top: 15px; }
    .signature-area { width: 100%; margin-top: 20px; }
    .signature-area td { width: 33%; text-align: center; padding: 10px; vertical-align: bottom; }
    .signature-area .sig-line { border-top: 1px solid #334155; margin-top: 35px; padding-top: 5px; }
    .signature-area .sig-title { font-size: 9px; color: #64748b; }
    .signature-area img { max-height: 40px; margin-bottom: 5px; }

    .footer-text { text-align: center; font-size: 9px; color: #64748b; margin-top: 15px; }
    .stamp-area { text-align: right; margin-top: 10px; }
    .stamp-area img { max-height: 80px; }

    .approval-box { border: 2px solid #0e7490; padding: 10px; margin: 15px 0; text-align: center; border-radius: 4px; }
    .approval-box .approved-text { font-size: 16px; font-weight: bold; color: #0e7490; }
    .approval-box .date-text { font-size: 10px; color: #475569; }

    @page { margin: 20mm 15mm; }

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

<div class="header">
    @if($settings->logo_path)
        <img src="{{ \App\Models\PreExcavationPermitSetting::toBase64DataUri($settings->logo_path) }}" alt="Logo" class="print-logo">
    @endif
    <h1 contenteditable="true">{{ $settings->title ?? 'ÖN KAZI İZİN BELGESİ' }}</h1>
    @if($settings->header_text)
        <h2>{{ $settings->header_text }}</h2>
    @endif
    <div class="doc-no">Belge No: {{ $application->application_no }} | Tarih: {{ now()->format('d.m.Y') }}</div>
</div>

{{-- Application Info --}}
<table class="meta-table">
    <tr>
        <td class="label">Başvuru No</td>
        <td class="value">{{ $application->application_no }}</td>
    </tr>
    <tr>
        <td class="label">Kurum</td>
        <td class="value">{{ $application->institution?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Başvuran</td>
        <td class="value">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</td>
    </tr>
    <tr>
        <td class="label">Kazı Sebebi</td>
        <td class="value">{{ $application->excavation_reason ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">İşin Adı (Cinsi)</td>
        <td class="value">
            @php
                $isParts = [];
                if ($application->project_code) $isParts[] = 'Kod: ' . $application->project_code;
                if ($application->work_type) $isParts[] = 'İş Cinsi: ' . $application->work_type;
            @endphp
            {{ $isParts ? implode(' / ', $isParts) : ($application->work_type ?? '—') }}
        </td>
    </tr>
    <tr>
        <td class="label">Adres</td>
        <td class="value">{{ $application->address_text ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Başlangıç Tarihi</td>
        <td class="value">{{ $application->start_date?->format('d.m.Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Bitiş Tarihi</td>
        <td class="value">{{ $application->end_date?->format('d.m.Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Alan (m²)</td>
        <td class="value">{{ number_format((float)$application->total_area_m2, 2, ',', '.') }} m²</td>
    </tr>
    @if($application->deposit_amount)
    <tr>
        <td class="label">Teminat Bedeli</td>
        <td class="value">{{ number_format((float)$application->deposit_amount, 2, ',', '.') }} ₺</td>
    </tr>
    @endif
    @if($application->excavation_amount)
    <tr>
        <td class="label">Kazı Bedeli</td>
        <td class="value">{{ number_format((float)$application->excavation_amount, 2, ',', '.') }} ₺</td>
    </tr>
    @endif
</table>

{{-- Custom Sections --}}
@if(!empty($settings->sections) && is_array($settings->sections))
    @foreach($settings->sections as $section)
        <div class="section">
            @if(!empty($section['title']))
                <div class="section-title">{{ $section['title'] }}</div>
            @endif
            @if(!empty($section['rows']) && is_array($section['rows']))
                <table class="info-table">
                    @foreach($section['rows'] as $row)
                        <tr>
                            <td style="width:35%; font-weight: bold;">{{ $row['label'] ?? '' }}</td>
                            <td>{{ $row['value'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
            @if(!empty($section['content']))
                <p style="font-size:10px; line-height:1.6;">{{ $section['content'] }}</p>
            @endif
        </div>
    @endforeach
@endif

{{-- Approval Box --}}
<div class="approval-box">
    <div class="approved-text">✓ ÖN KAZI İZNİ ONAYLANDI</div>
    <div class="date-text">Onay Tarihi: {{ now()->format('d.m.Y H:i') }}</div>
</div>

{{-- Footer / Conditions --}}
@if($settings->footer_text)
    <div class="section">
        <div class="section-title">Şartlar ve Açıklamalar</div>
        <p style="font-size:10px; line-height:1.6;">{{ $settings->footer_text }}</p>
    </div>
@endif

{{-- Signatures --}}
<div class="footer">
    <table class="signature-area">
        <tr>
            <td>
                @if($settings->signature_path)
                    <img src="{{ \App\Models\PreExcavationPermitSetting::toBase64DataUri($settings->signature_path) }}" alt="İmza">
                @endif
                <div class="sig-line">
                    <strong>{{ $settings->approver_name ?? '_______________' }}</strong>
                    <div class="sig-title">{{ $settings->approver_title ?? 'Yetkili' }}</div>
                </div>
            </td>
            <td>
                @if($settings->stamp_path)
                    <div class="stamp-area">
                        <img src="{{ \App\Models\PreExcavationPermitSetting::toBase64DataUri($settings->stamp_path) }}" alt="Mühür">
                    </div>
                @endif
            </td>
            <td>
                <div class="sig-line">
                    <strong>{{ $application->creator?->name ?? '_______________' }}</strong>
                    <div class="sig-title">Başvuru Sahibi</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-text">
        {{ config('app.name') }} - Otomatik oluşturulmuştur. Doğrulama için {{ url('/') }}
    </div>
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
