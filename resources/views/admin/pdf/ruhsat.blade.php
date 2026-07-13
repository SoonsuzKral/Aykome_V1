<!DOCTYPE html>
<html lang="tr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta charset="UTF-8">
<title>Altyapi Kazi Izni &mdash; {{ $application->application_no }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; }

body {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 9.5px;
    color: #000;
    background: #fff;
}

/* A4 outer frame */
.page {
    width: 100%;
    border: 2px solid #1a1a1a;
    padding: 0;
}

/* ── Header ──────────────────────────────────────────────────── */
.hdr {
    display: table;
    width: 100%;
    border-bottom: 2px solid #000;
}
.hdr-logo {
    display: table-cell;
    width: 90px;
    padding: 10px 8px 10px 12px;
    vertical-align: middle;
    border-right: 1px solid #ccc;
}
.hdr-logo img {
    max-width: 72px;
    max-height: 64px;
    display: block;
    margin: 0 auto;
}
.hdr-logo-ph {
    width: 64px;
    height: 64px;
    border: 2px solid #ccc;
    border-radius: 6px;
    font-size: 22px;
    font-weight: 900;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    color: #999;
    margin: 0 auto;
    text-align: center;
    line-height: 64px;
}
.hdr-title {
    display: table-cell;
    vertical-align: middle;
    text-align: center;
    padding: 10px 16px;
}
.hdr-title .t1 {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #1a1a1a;
    line-height: 1.5;
}
.hdr-title .t2 {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 9px;
    color: #444;
    margin-top: 1px;
    line-height: 1.5;
}
.hdr-title .t3 {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #000;
    margin-top: 5px;
    border-top: 1px solid #ccc;
    padding-top: 4px;
}
.hdr-meta {
    display: table-cell;
    width: 110px;
    vertical-align: middle;
    padding: 8px 10px;
    border-left: 1px solid #ccc;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8px;
    color: #333;
    text-align: right;
}
.hdr-meta .doc-no {
    font-size: 10px;
    font-weight: 700;
    color: #000;
    word-break: break-all;
}

/* ── Info table ──────────────────────────────────────────────── */
.info-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid #000;
}
.info-table tr { border-bottom: 1px solid #ccc; }
.info-table td {
    padding: 4px 10px;
    vertical-align: top;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
}
.info-table .lbl {
    width: 22%;
    font-weight: 700;
    font-size: 8.5px;
    color: #333;
    background: #f5f5f5;
    border-right: 1px solid #ccc;
    white-space: nowrap;
}
.info-table .val {
    font-size: 9px;
    color: #000;
    background: #fff;
}
.info-table .lbl2 {
    width: 18%;
    font-weight: 700;
    font-size: 8.5px;
    color: #333;
    background: #f5f5f5;
    border-right: 1px solid #ccc;
    border-left: 1px solid #e0e0e0;
    white-space: nowrap;
    padding-left: 14px;
}

/* ── Section heading ─────────────────────────────────────────── */
.sec-head {
    background: #1a1a1a;
    color: white;
    padding: 4px 10px;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: none;
}

/* ── Cost table ──────────────────────────────────────────────── */
.cost-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid #000;
}
.cost-table thead tr {
    background: #2d2d2d;
    color: white;
}
.cost-table thead th {
    padding: 5px 8px;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    text-align: left;
    border-right: 1px solid #444;
}
.cost-table thead th:last-child { border-right: none; }
.cost-table thead th.r, .cost-table tbody td.r, .cost-table tfoot td.r { text-align: right; }
.cost-table tbody tr { border-bottom: 1px solid #e0e0e0; }
.cost-table tbody tr:nth-child(even) { background: #fafafa; }
.cost-table tbody td { padding: 4.5px 8px; font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; font-size: 9px; color: #111; border-right: 1px solid #e8e8e8; }
.cost-table tbody td:last-child { border-right: none; }
.cost-table tfoot tr { border-top: 1.5px solid #000; background: #f0f0f0; }
.cost-table tfoot td { padding: 5px 8px; font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; font-size: 8.5px; font-weight: 700; color: #111; }

/* ── Totals section ──────────────────────────────────────────── */
.totals-wrap {
    border-bottom: 1.5px solid #000;
    display: table;
    width: 100%;
}
.totals-left {
    display: table-cell;
    vertical-align: top;
    width: 50%;
    border-right: 1px solid #ccc;
    padding: 8px 10px;
}
.totals-right {
    display: table-cell;
    vertical-align: top;
    width: 50%;
    padding: 8px 10px;
}
.total-row {
    display: table;
    width: 100%;
    margin-bottom: 4px;
}
.total-lbl {
    display: table-cell;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8.5px;
    font-weight: 700;
    color: #333;
    text-transform: uppercase;
    letter-spacing: .3px;
    width: 65%;
    vertical-align: middle;
}
.total-val {
    display: table-cell;
    text-align: right;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 11px;
    font-weight: 900;
    color: #000;
    vertical-align: middle;
}
.total-row.grand .total-lbl { color: #000; font-size: 9px; }
.total-row.grand .total-val { font-size: 13px; color: #000; border-top: 2px solid #000; padding-top: 2px; }

/* ── Special conditions ──────────────────────────────────────── */
.conditions {
    border-bottom: 1.5px solid #000;
    padding: 7px 10px;
    min-height: 50px;
}
.conditions-text {
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8px;
    color: #333;
    line-height: 1.75;
    white-space: pre-wrap;
}

/* ── Signature rows ──────────────────────────────────────────── */
.sig-wrap {
    display: table;
    width: 100%;
}
.sig-block {
    display: table-cell;
    text-align: center;
    padding: 10px 12px;
    vertical-align: bottom;
    border-right: 1px solid #ccc;
}
.sig-block:last-child { border-right: none; }
.sig-img {
    max-height: 50px;
    max-width: 140px;
    display: block;
    margin: 0 auto 6px;
}
.sig-stamp {
    height: 55px;
    width: 55px;
    border-radius: 50%;
    display: block;
    margin: 0 auto 6px;
}
.sig-empty { height: 50px; }
.sig-line  { border-top: 1px solid #999; margin-bottom: 4px; }
.sig-name  { font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; font-size: 9px; font-weight: 700; color: #000; }
.sig-title { font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; font-size: 8px; color: #555; margin-top: 2px; }
.sig-date  { font-family: 'DejaVu Sans', DejaVu Sans, sans-serif; font-size: 7.5px; color: #888; margin-top: 2px; }

/* ── ONAY bottom section ─────────────────────────────────────── */
.onay-wrap {
    display: table;
    width: 100%;
    background: #f9f9f9;
    border-top: 1.5px solid #000;
}
.onay-head {
    background: #1a1a1a;
    color: white;
    padding: 3px 10px;
    font-family: 'DejaVu Sans', DejaVu Sans, sans-serif;
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    text-align: center;
}
</style>
</head>
<body>

@php
    $settings  = \App\Models\PermitSetting::getSingleton();
    $logoUri   = \App\Models\PermitSetting::toBase64DataUri($settings->institution_logo_path);
    $signUri   = \App\Models\PermitSetting::toBase64DataUri($settings->director_signature_path);
    $stampUri  = \App\Models\PermitSetting::toBase64DataUri($settings->municipality_stamp_path);
    $prepUri   = \App\Models\PermitSetting::toBase64DataUri($settings->preparer_signature_path);

    $instName    = $settings->institution_name    ?? 'BELEDIYE BASKANLIGI';
    $instAddress = $settings->institution_address ?? '';
    $deptName    = $settings->department_name     ?: 'Fen Isleri Dairesi Baskanligi';
    $dirName     = $settings->director_name       ?? '';
    $dirTitle    = $settings->director_title      ?? 'Daire Baskani';
    $prepName    = $settings->preparer_name       ?? '';
    $prepTitle   = $settings->preparer_title      ?? 'Tanzim Eden';
    $apprName    = $settings->approver_name       ?? '';
    $apprTitle   = $settings->approver_title      ?? 'Onaylayan';
    $secApprName  = $settings->secondary_approver_name  ?? '';
    $secApprTitle = $settings->secondary_approver_title ?? '';

    $totalPrice    = (float) ($application->discovery_amount ?? $application->total_price ?? 0);
    $areaTotal     = (float) $application->total_area_m2;
    $surfaceLines  = $application->surfaceLines ?? collect();
    $areas         = $application->excavationAreas ?? collect();

    $kesifBedeli   = $surfaceLines->sum(fn($l) => (float)$l->amount);
    $alanTahrip    = (float)($application->discovery_amount ?? 0);
    $genelToplam   = $kesifBedeli + $alanTahrip;
    if ($genelToplam == 0) $genelToplam = $totalPrice;
@endphp

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────── --}}
    <div class="hdr">
        <div class="hdr-logo">
            @if($logoUri)
                <img src="{{ $logoUri }}" alt="{{ $instName }}">
            @else
                <div class="hdr-logo-ph">K</div>
            @endif
        </div>
        <div class="hdr-title">
            <div class="t1">T.C.<br>{{ mb_strtoupper($instName) }}</div>
            <div class="t2">{{ $deptName }}<br>Altyapi Koordinasyon (AYKOME) Sube Mudurlugu</div>
            <div class="t3">ALTYAPI KAZI IZNI BELGESI</div>
        </div>
        <div class="hdr-meta">
            <div class="doc-no">{{ $application->application_no }}</div>
            <div style="margin-top:4px;">Tarih:<br>{{ now()->format('d.m.Y') }}</div>
        </div>
    </div>

    {{-- ── BASVURU BILGILERI ─────────────────────────────────── --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Belge Numarasi</td>
            <td class="val">{{ $application->application_no }}</td>
            <td class="lbl2">Basvuru Tarihi</td>
            <td class="val">{{ $application->created_at?->format('d.m.Y') ?? '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Talebi Yapan Kurum</td>
            <td class="val">{{ $application->institution?->name ?? '&mdash;' }}</td>
            <td class="lbl2">Alt Yuklenici</td>
            <td class="val">{{ $application->creator?->name ?? '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kazi Sebebi</td>
            <td class="val">{{ $application->excavation_reason ?? '&mdash;' }}</td>
            <td class="lbl2">Calisma Turu</td>
            <td class="val">{{ $application->work_type ?? '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kazi Baslangic</td>
            <td class="val">{{ $application->start_date?->format('d.m.Y') ?? '&mdash;' }}</td>
            <td class="lbl2">Kazi Bitis</td>
            <td class="val">{{ $application->end_date?->format('d.m.Y') ?? '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Sure Uzatim Baslangic</td>
            <td class="val">{{ isset($application->extension_start_date) && $application->extension_start_date ? \Carbon\Carbon::parse($application->extension_start_date)->format('d.m.Y') : '&mdash;' }}</td>
            <td class="lbl2">Sure Uzatim Bitis</td>
            <td class="val">{{ isset($application->extension_end_date) && $application->extension_end_date ? \Carbon\Carbon::parse($application->extension_end_date)->format('d.m.Y') : '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Vatandas / Ilgili</td>
            <td class="val">{{ trim($application->applicant_first_name . ' ' . $application->applicant_last_name) }} &nbsp; TC: {{ $application->applicant_national_id ?? $application->tc_no ?? '&mdash;' }}</td>
            <td class="lbl2">Telefon</td>
            <td class="val">{{ $application->applicant_phone ?? '&mdash;' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kazi Adresi</td>
            <td class="val" colspan="3">{{ $application->address_text ?? '&mdash;' }}</td>
        </tr>
    </table>

    {{-- ── ALAN CINSI VE MALIYET TABLOSU ────────────────────── --}}
    <div class="sec-head">ALAN CINSI VE MALIYET TABLOSU</div>
    <table class="cost-table">
        <thead>
            <tr>
                <th style="width:26%;">Alan Cinsi</th>
                <th class="r" style="width:12%;">Birim Fiyati (TL)</th>
                <th class="r" style="width:10%;">Genislik (m)</th>
                <th class="r" style="width:10%;">Uzunluk (m)</th>
                <th class="r" style="width:10%;">Miktari</th>
                <th class="r" style="width:8%;">Kati</th>
                <th class="r" style="width:14%;">Tutar (TL)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($surfaceLines as $i => $line)
            <tr>
                <td>{{ $line->surfaceType?->name ?? '&mdash;' }}</td>
                <td class="r">{{ number_format((float)($line->surfaceType?->price_per_m2 ?? 0), 2, ',', '.') }}</td>
                <td class="r">{{ $line->width_m  !== null ? number_format((float)$line->width_m,  2, ',', '.') : '&mdash;' }}</td>
                <td class="r">{{ $line->length_m !== null ? number_format((float)$line->length_m, 2, ',', '.') : '&mdash;' }}</td>
                <td class="r">{{ number_format((float)$line->quantity,   2, ',', '.') }}</td>
                <td class="r">{{ number_format((float)$line->multiplier, 2, ',', '.') }}</td>
                <td class="r"><strong>{{ number_format((float)$line->amount, 2, ',', '.') }}</strong></td>
            </tr>
            @empty
            @for($r = 0; $r < 4; $r++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"></td>
                <td>TOPLAM ALAN:</td>
                <td class="r">{{ number_format($areaTotal, 2, ',', '.') }} m2</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── TOPLAMLAR ─────────────────────────────────────────── --}}
    <div class="totals-wrap">
        <div class="totals-left">
            @if($areas->isNotEmpty())
            <div style="font-family:'DejaVu Sans',DejaVu Sans,sans-serif;font-size:8px;font-weight:700;color:#333;text-transform:uppercase;margin-bottom:5px;letter-spacing:.5px;">Kazi Koordinatlari</div>
            @foreach($areas->take(5) as $i => $area)
            <div style="font-family:'DejaVu Sans',DejaVu Sans,sans-serif;font-size:8px;color:#555;margin-bottom:2px;">
                {{ $i+1 }}. Enlem: {{ $area->center_lat ?? '&mdash;' }} &nbsp; Boylam: {{ $area->center_lng ?? '&mdash;' }}
                @if($area->total_area_m2 ?? $area->area_m2 ?? null) &nbsp; Alan: {{ number_format((float)($area->total_area_m2 ?? $area->area_m2), 2, ',', '.') }} m2 @endif
            </div>
            @endforeach
            @else
            <div style="font-family:'DejaVu Sans',DejaVu Sans,sans-serif;font-size:8px;color:#aaa;">Koordinat bilgisi girilmemis.</div>
            @endif
        </div>
        <div class="totals-right">
            <div class="total-row">
                <div class="total-lbl">Kesif Bedeli (TL)</div>
                <div class="total-val">{{ number_format($kesifBedeli ?: $totalPrice, 2, ',', '.') }}</div>
            </div>
            <div class="total-row">
                <div class="total-lbl">Alan Tahrip Tutari (TL)</div>
                <div class="total-val">{{ number_format($alanTahrip, 2, ',', '.') }}</div>
            </div>
            <div class="total-row grand" style="margin-top:4px;">
                <div class="total-lbl">GENEL TOPLAM (TL)</div>
                <div class="total-val">{{ number_format($genelToplam, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- ── OZEL SARTLAR ──────────────────────────────────────── --}}
    <div class="sec-head">OZEL SARTLAR</div>
    <div class="conditions">
        <div class="conditions-text">{{ $settings->validity_agreement
            ?: "1. Bu ruhsat belgesinde belirtilen kazi alani disina cikilmaz.\n2. Kazi alani belirtilen tarihler icinde acik tutulabilir; sure uzatimi icin yeniden basvuru zorunludur.\n3. Yol ve kaldirimlar orijinal haliyle restore edilecektir.\n4. Kazi bitiminde alan 48 saat icinde teslim edilecektir.\n5. Bu izin belgesi kazi alaninda bulundurulmasi zorunludur." }}</div>
    </div>

    {{-- ── IMZALAR - UST SIRA (Tanzim Eden / Onaylayan / Alt Onay) ──────── --}}
    <div class="sig-wrap" style="border-top:1.5px solid #000;border-bottom:1px solid #ccc;">
        {{-- Tanzim Eden --}}
        <div class="sig-block" style="width:33.33%;">
            @if($prepUri)
                <img src="{{ $prepUri }}" class="sig-img" alt="Tanzim Eden Imzasi">
            @else
                <div class="sig-empty"></div>
            @endif
            <div class="sig-line"></div>
            <div class="sig-name">{{ $prepName ?: 'Tanzim Eden' }}</div>
            <div class="sig-title">{{ $prepTitle }}</div>
            <div class="sig-date">Tanzim Tarihi: {{ now()->format('d.m.Y') }}</div>
        </div>

        {{-- Onaylayan --}}
        <div class="sig-block" style="width:33.33%;">
            <div class="sig-empty"></div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $apprName ?: 'Onaylayan' }}</div>
            <div class="sig-title">{{ $apprTitle }}</div>
            <div class="sig-date">&nbsp;</div>
        </div>

        {{-- Alt Onay Yetkilisi --}}
        <div class="sig-block" style="width:33.33%;border-right:none;">
            <div class="sig-empty"></div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $secApprName ?: 'Alt Onay Yetkilisi' }}</div>
            <div class="sig-title">{{ $secApprTitle ?: '&nbsp;' }}</div>
            <div class="sig-date">&nbsp;</div>
        </div>
    </div>

    {{-- ── ONAY - ALT BOLUM ───────────────────────────────────── --}}
    <div class="onay-head">O N A Y</div>
    <div class="onay-wrap">
        {{-- Daire Baskani imzasi --}}
        <div class="sig-block" style="width:50%;">
            @if($signUri)
                <img src="{{ $signUri }}" class="sig-img" alt="Imza">
            @else
                <div class="sig-empty"></div>
            @endif
            <div class="sig-line"></div>
            <div class="sig-name">{{ $dirName ?: 'Daire Baskani' }}</div>
            <div class="sig-title">{{ $dirTitle }}</div>
            <div class="sig-date">Tarih: {{ now()->format('d.m.Y') }}</div>
        </div>

        {{-- Muhur --}}
        <div class="sig-block" style="width:50%;border-right:none;">
            @if($stampUri)
                <img src="{{ $stampUri }}" class="sig-stamp" alt="Muhur">
            @else
                <div class="sig-empty" style="border:2px dashed #ccc;border-radius:50%;width:55px;height:55px;margin:0 auto 6px;"></div>
            @endif
            <div class="sig-line"></div>
            <div class="sig-name">Resmi Muhur / Kase</div>
            <div class="sig-title">{{ $instName }}</div>
            <div class="sig-date">Tarih: ___.___.________</div>
        </div>
    </div>

</div>
</body>
</html>
