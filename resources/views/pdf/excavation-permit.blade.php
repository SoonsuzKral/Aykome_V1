<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Kazı İzin Belgesi — {{ $application->application_no }}</title>
<style>
/* ── Reset ──────────────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9.5px;
    color: #1a1a2e;
    background: #ffffff;
}

/* ── Header band ────────────────────────────────────────────────── */
.header-band { background: #0f172a; color: white; }
.header-inner {
    display: table;
    width: 100%;
    padding: 14px 24px 10px;
}
.header-left  { display: table-cell; vertical-align: middle; width: 70%; }
.header-right { display: table-cell; vertical-align: middle; text-align: right; }
.logo-row { display: table; }
.logo-cell { display: table-cell; vertical-align: middle; }
.header-logo {
    max-height: 50px;
    max-width: 110px;
    margin-right: 12px;
}
.header-logo-placeholder {
    display: inline-block;
    width: 46px; height: 46px;
    border-radius: 8px;
    background: rgba(2,175,198,0.22);
    border: 1px solid rgba(2,175,198,0.4);
    text-align: center;
    line-height: 46px;
    font-size: 18px;
    font-weight: 900;
    color: #02AFC6;
    margin-right: 12px;
    vertical-align: middle;
}
.institution-name { font-size: 13px; font-weight: 700; color: #f8fafc; }
.institution-address { font-size: 7.5px; color: #94a3b8; margin-top: 2px; }
.doc-no { font-size: 12px; font-weight: 700; color: #02AFC6; }
.doc-meta { font-size: 7.5px; color: #94a3b8; margin-top: 2px; }

/* ── Title bar ──────────────────────────────────────────────────── */
.title-bar {
    background: #02AFC6;
    color: white;
    text-align: center;
    padding: 6px 24px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}

/* ── Content ────────────────────────────────────────────────────── */
.content { padding: 14px 24px 80px; }

/* ── Section headers ────────────────────────────────────────────── */
.section-head {
    font-size: 7.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #02AFC6;
    border-bottom: 1.5px solid #02AFC6;
    padding-bottom: 3px;
    margin-bottom: 7px;
    margin-top: 13px;
}
.first { margin-top: 0; }

/* ── Info table ─────────────────────────────────────────────────── */
table.info {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
}
table.info tr { border-bottom: 1px solid #e2e8f0; }
table.info tr:last-child { border-bottom: none; }
table.info th {
    width: 28%;
    background: #f8fafc;
    padding: 5px 9px;
    font-size: 8px;
    font-weight: 700;
    color: #475569;
    text-align: left;
    border-right: 1px solid #e2e8f0;
    vertical-align: top;
}
table.info td {
    padding: 5px 9px;
    font-size: 9px;
    font-weight: 600;
    color: #0f172a;
}

/* ── Surface lines table ────────────────────────────────────────── */
table.lines {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}
table.lines thead tr { background: #0f172a; color: white; }
table.lines thead th {
    padding: 5px 7px;
    text-align: left;
    font-size: 7.5px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
table.lines thead th.r, table.lines tbody td.r, table.lines tfoot td.r { text-align: right; }
table.lines tbody tr:nth-child(even) { background: #f8fafc; }
table.lines tbody tr { border-bottom: 1px solid #eef0f4; }
table.lines tbody td { padding: 4px 7px; color: #334155; }
table.lines tfoot tr { background: #0f172a; color: white; font-weight: 700; }
table.lines tfoot td { padding: 5px 7px; font-size: 8px; }

/* ── Coord table ────────────────────────────────────────────────── */
table.areas {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}
table.areas thead tr { background: #334155; color: white; }
table.areas thead th { padding: 4px 8px; font-size: 7.5px; text-transform: uppercase; }
table.areas tbody tr:nth-child(even) { background: #f1f5f9; }
table.areas tbody tr { border-bottom: 1px solid #e2e8f0; }
table.areas tbody td { padding: 4px 8px; color: #334155; }

/* ── Total box ──────────────────────────────────────────────────── */
.total-box {
    background: #0f172a;
    color: white;
    padding: 8px 14px;
    border-radius: 5px;
    margin-top: 9px;
    display: table;
    width: 100%;
}
.total-left  { display: table-cell; vertical-align: middle; }
.total-right { display: table-cell; text-align: right; vertical-align: middle; }
.total-label { font-size: 7.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; }
.total-amount { font-size: 15px; font-weight: 900; color: #02AFC6; }

/* ── Validity text ──────────────────────────────────────────────── */
.validity-box {
    margin-top: 13px;
    padding: 8px 11px;
    background: #f8fafc;
    border-left: 3px solid #02AFC6;
    font-size: 7.5px;
    color: #475569;
    line-height: 1.7;
}

/* ── Signature row ──────────────────────────────────────────────── */
.sig-row {
    display: table;
    width: 100%;
    margin-top: 16px;
    padding-top: 11px;
    border-top: 1px dashed #cbd5e1;
}
.sig-block { display: table-cell; text-align: center; width: 33.3%; vertical-align: bottom; padding: 0 8px; }
.sig-image  { max-height: 46px; max-width: 130px; object-fit: contain; display: block; margin: 0 auto 5px; }
.sig-stamp  { height: 52px; width: 52px; object-fit: contain; border-radius: 50%; display: block; margin: 0 auto 5px; }
.sig-line-el { border-top: 1px solid #cbd5e1; margin-bottom: 4px; }
.sig-name  { font-weight: 700; font-size: 9px; color: #1e293b; }
.sig-title { font-size: 7.5px; color: #64748b; margin-top: 1px; }
.sig-date  { font-size: 7px; color: #94a3b8; margin-top: 1px; }

/* ── Verification box ───────────────────────────────────────────── */
.verify-box {
    position: fixed;
    bottom: 32px;
    right: 24px;
    width: 95px;
    text-align: center;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    padding: 7px 5px 5px;
    background: white;
}
.verify-label   { font-size: 6.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #cbd5e1; }
.verify-code    { font-family: monospace; font-size: 8.5px; font-weight: 700; color: #0f172a; letter-spacing: 1px; word-break: break-all; margin: 3px 0 2px; }
.verify-badge   { font-size: 7px; color: #02AFC6; font-weight: 700; }

/* ── Page footer ────────────────────────────────────────────────── */
.page-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 22px;
    background: #0f172a;
    color: #64748b;
    font-size: 7px;
    padding: 0 24px;
    display: table;
    width: 100%;
}
.footer-left  { display: table-cell; vertical-align: middle; }
.footer-right { display: table-cell; text-align: right; vertical-align: middle; }
</style>
</head>
<body>

@php
    $settings  = \App\Models\PermitSetting::getSingleton();
    $logoUri   = \App\Models\PermitSetting::toBase64DataUri($settings->institution_logo_path);
    $signUri   = \App\Models\PermitSetting::toBase64DataUri($settings->director_signature_path);
    $stampUri  = \App\Models\PermitSetting::toBase64DataUri($settings->municipality_stamp_path);

    $instName    = $settings->institution_name  ?? config('app.name', 'AYKOME');
    $instAddress = $settings->institution_address ?? '';
    $dirName     = $settings->director_name  ?? '';
    $dirTitle    = $settings->director_title ?? '';

    $seed        = $application->application_no . $application->id . ($application->receipt_approved_at?->timestamp ?? '');
    $verifyCode  = strtoupper(substr(md5($seed), 0, 16));
    $verifyFmt   = implode('-', str_split($verifyCode, 4));

    $totalPrice   = (float) ($application->discovery_amount ?? $application->total_price ?? 0);
    $surfaceLines = $application->surfaceLines ?? collect();
    $areas        = $application->excavationAreas ?? collect();
@endphp

{{-- ── HEADER ──────────────────────────────────────────────────────── --}}
<div class="header-band">
    <div class="header-inner">
        <div class="header-left">
            <div class="logo-row">
                <div class="logo-cell">
                    @if($logoUri)
                        <img src="{{ $logoUri }}" class="header-logo" alt="{{ $instName }}">
                    @else
                        <span class="header-logo-placeholder">K</span>
                    @endif
                </div>
                <div class="logo-cell">
                    <div class="institution-name">{{ $instName }}</div>
                    @if($instAddress)
                        <div class="institution-address">{{ $instAddress }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="doc-no">{{ $application->application_no }}</div>
            <div class="doc-meta">
                Tanzim: {{ now()->format('d.m.Y') }}<br>
                Onay: {{ $application->receipt_approved_at?->format('d.m.Y') ?? '—' }}
            </div>
        </div>
    </div>
</div>

<div class="title-bar">T.C. &nbsp;—&nbsp; ALTYAPI KAZI İZİN BELGESİ</div>

{{-- ── CONTENT ──────────────────────────────────────────────────────── --}}
<div class="content">

    {{-- Başvuru Bilgileri --}}
    <div class="section-head first">Başvuru Sahibi &amp; Kurum Bilgileri</div>
    <table class="info">
        <tr>
            <th>Ad Soyad</th>
            <td>{{ trim($application->applicant_first_name . ' ' . $application->applicant_last_name) }}</td>
            <th>TC Kimlik No</th>
            <td>{{ $application->applicant_national_id ?? $application->tc_no ?? '—' }}</td>
        </tr>
        <tr>
            <th>Talep Kurumu</th>
            <td>{{ $application->institution?->name ?? '—' }}</td>
            <th>Telefon</th>
            <td>{{ $application->applicant_phone ?? '—' }}</td>
        </tr>
        <tr>
            <th>Kazı Nedeni</th>
            <td>{{ $application->excavation_reason ?? '—' }}</td>
            <th>Çalışma Türü</th>
            <td>{{ $application->work_type ?? '—' }}</td>
        </tr>
        <tr>
            <th>Başlangıç Tarihi</th>
            <td>{{ $application->start_date?->format('d.m.Y') ?? '—' }}</td>
            <th>Bitiş Tarihi</th>
            <td>{{ $application->end_date?->format('d.m.Y') ?? '—' }}</td>
        </tr>
        <tr>
            <th>Kazı Adresi</th>
            <td colspan="3">{{ $application->address_text ?? '—' }}</td>
        </tr>
        @if($application->description)
        <tr>
            <th>Açıklama</th>
            <td colspan="3">{{ $application->description }}</td>
        </tr>
        @endif
    </table>

    {{-- Kaplama Satırları --}}
    <div class="section-head">Kaplama &amp; Alan Satırları</div>
    <table class="lines">
        <thead>
            <tr>
                <th>Kaplama Tipi</th>
                <th class="r">Birim (₺)</th>
                <th class="r">Gen. (m)</th>
                <th class="r">Uzn. (m)</th>
                <th class="r">Miktar</th>
                <th class="r">Katı</th>
                <th class="r">Tutar (₺)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($surfaceLines as $line)
            <tr>
                <td>{{ $line->surfaceType?->name ?? '—' }}</td>
                <td class="r">{{ number_format((float)($line->surfaceType?->price_per_m2 ?? 0), 2, ',', '.') }}</td>
                <td class="r">{{ $line->width_m  !== null ? number_format((float)$line->width_m,  2, ',', '.') : '—' }}</td>
                <td class="r">{{ $line->length_m !== null ? number_format((float)$line->length_m, 2, ',', '.') : '—' }}</td>
                <td class="r">{{ number_format((float)$line->quantity,   2, ',', '.') }}</td>
                <td class="r">{{ number_format((float)$line->multiplier, 2, ',', '.') }}</td>
                <td class="r">{{ number_format((float)$line->amount,     2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:9px;color:#94a3b8;">Alan kalemi bulunamadı.</td></tr>
            @endforelse
        </tbody>
        @if($surfaceLines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">&nbsp;</td>
                <td>Toplam Alan:</td>
                <td class="r">{{ number_format((float)$application->total_area_m2, 2, ',', '.') }} m²</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- Koordinatlar --}}
    @if($areas->isNotEmpty())
    <div class="section-head">Kazı Alanı GPS Koordinatları</div>
    <table class="areas">
        <thead>
            <tr>
                <th>#</th>
                <th>Merkez Enlem (Lat)</th>
                <th>Merkez Boylam (Lng)</th>
                <th>Çizim Alanı (m²)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($areas as $i => $area)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $area->center_lat ?? '—' }}</td>
                <td>{{ $area->center_lng ?? '—' }}</td>
                <td>{{ $area->area_m2 !== null ? number_format((float)$area->area_m2, 2, ',', '.') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Toplam --}}
    <div class="total-box">
        <div class="total-left">
            <div class="total-label">Toplam Keşif / Tahakkuk Tutarı</div>
        </div>
        <div class="total-right">
            <span class="total-amount">₺ {{ number_format($totalPrice, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- Geçerlilik --}}
    <div class="validity-box">
        {{ $settings->validity_agreement ?: 'Bu ruhsat belgesi ilgili yasa ve yönetmelikler çerçevesinde düzenlenmiş olup kazı alanında bulundurulması zorunludur. Ruhsatta belirtilen süre ve koşullar dışında çalışma yapılamaz. İhlal halinde yasal işlem yapılacaktır.' }}
    </div>

    {{-- İmzalar --}}
    <div class="sig-row">
        <div class="sig-block">
            @if($signUri)
                <img src="{{ $signUri }}" class="sig-image" alt="İmza">
            @else
                <div style="height:46px;"></div>
            @endif
            <div class="sig-line-el"></div>
            <div class="sig-name">{{ $dirName ?: 'İmzalayan Yetkili' }}</div>
            <div class="sig-title">{{ $dirTitle ?: 'Yetkili' }}</div>
            <div class="sig-date">{{ now()->format('d.m.Y') }}</div>
        </div>

        <div class="sig-block">
            @if($stampUri)
                <img src="{{ $stampUri }}" class="sig-stamp" alt="Mühür">
            @else
                <div style="width:52px;height:52px;border:2px dashed #cbd5e1;border-radius:50%;margin:0 auto 5px;"></div>
            @endif
            <div class="sig-line-el"></div>
            <div class="sig-name">Resmi Mühür</div>
            <div class="sig-title">{{ $instName }}</div>
        </div>

        <div class="sig-block">
            <div style="height:46px;"></div>
            <div class="sig-line-el"></div>
            <div class="sig-name">Başvuru Sahibi</div>
            <div class="sig-title">{{ trim($application->applicant_first_name . ' ' . $application->applicant_last_name) }}</div>
            <div class="sig-date">İmza: _______________</div>
        </div>
    </div>

</div>{{-- /content --}}

{{-- ── VERIFICATION BOX ─────────────────────────────────────────────── --}}
<div class="verify-box">
    <div class="verify-label">e-Doğrulama</div>
    <div class="verify-code">{{ $verifyFmt }}</div>
    <div class="verify-badge">AYKOME</div>
    <div class="verify-label">{{ now()->format('d.m.Y') }}</div>
</div>

{{-- ── PAGE FOOTER ──────────────────────────────────────────────────── --}}
<div class="page-footer">
    <div class="footer-left">{{ $settings->footer_note ?: ($instName . ' — HGB Bilişim  AYKOME Kazı İzin Yönetim Sistemi') }}</div>
    <div class="footer-right">Belge No: {{ $application->application_no }} | {{ now()->format('d.m.Y H:i') }}</div>
</div>

</body>
</html>
