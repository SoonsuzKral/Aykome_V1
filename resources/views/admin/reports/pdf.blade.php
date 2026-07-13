<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HGB Bilişim  AYKOME — Başvuru Raporu</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 9px;
        color: #1e293b;
        background: #fff;
    }

    /* Header */
    .page-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        padding: 18px 24px 14px;
        margin-bottom: 0;
    }
    .brand-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand-name {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -0.3px;
        color: #f8fafc;
    }
    .brand-sub {
        font-size: 8px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-top: 2px;
    }
    .report-meta {
        text-align: right;
        font-size: 8px;
        color: #94a3b8;
    }
    .report-title {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #334155;
    }
    .report-title h1 {
        font-size: 13px;
        font-weight: 700;
        color: #e2e8f0;
    }

    /* Filter summary */
    .filter-bar {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 8px 24px;
        font-size: 8px;
        color: #475569;
    }
    .filter-label {
        font-weight: 700;
        color: #334155;
        margin-right: 6px;
    }
    .filter-chip {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 3px;
        padding: 1px 5px;
        margin: 0 2px;
        font-size: 7.5px;
        font-weight: 600;
    }

    /* Stats bar */
    .stats-bar {
        display: flex;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        padding: 6px 24px;
    }
    .stat-item {
        margin-right: 24px;
        font-size: 8px;
    }
    .stat-label { color: #64748b; }
    .stat-value { font-weight: 700; color: #0f172a; font-size: 10px; }

    /* Table */
    .content { padding: 0 24px 20px; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        font-size: 8px;
    }
    thead tr {
        background: #0f172a;
        color: white;
    }
    thead th {
        padding: 6px 8px;
        text-align: left;
        font-weight: 600;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    thead th.text-right { text-align: right; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:nth-child(odd)  { background: #ffffff; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody td {
        padding: 5px 8px;
        color: #334155;
        vertical-align: top;
    }
    tbody td.text-right { text-align: right; }
    tbody td.no { color: #0369a1; font-weight: 600; }

    /* Status badges */
    .badge {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 7px;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-draft    { background:#f1f5f9; color:#475569; }
    .badge-submitted{ background:#e0f2fe; color:#0369a1; }
    .badge-priced   { background:#ede9fe; color:#6d28d9; }
    .badge-awaiting { background:#fef3c7; color:#b45309; }
    .badge-receipt  { background:#ffedd5; color:#c2410c; }
    .badge-approved { background:#d1fae5; color:#065f46; }
    .badge-rejected { background:#fee2e2; color:#991b1b; }
    .badge-licensed { background:#ccfbf1; color:#0f766e; }
    .badge-fieldwork{ background:#ede9fe; color:#5b21b6; }
    .badge-completed{ background:#dcfce7; color:#15803d; }
    .badge-archived { background:#f3f4f6; color:#6b7280; }

    /* Footer */
    .page-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 5px 24px;
        font-size: 7.5px;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
    }
</style>
</head>
<body>

{{-- Header --}}
<div class="page-header">
    <div class="brand-row">
        <div>
            <div class="brand-name">HGB Bilişim  AYKOME</div>
            <div class="brand-sub">Kazı İzin & Ruhsat Yönetim Sistemi</div>
        </div>
        <div class="report-meta">
            <div>Oluşturulma: {{ now()->format('d.m.Y H:i') }}</div>
            <div>Toplam Kayıt: {{ count($applications) }}</div>
        </div>
    </div>
    <div class="report-title">
        <h1>Başvuru Analiz Raporu</h1>
    </div>
</div>

{{-- Filter summary --}}
@if(count($filterSummary))
<div class="filter-bar">
    <span class="filter-label">Filtreler:</span>
    @foreach($filterSummary as $item)
        <span class="filter-chip">{{ $item }}</span>
    @endforeach
</div>
@endif

{{-- Stats --}}
@php
    $completed = $applications->filter(fn($a) => $a->status?->value === 'completed')->count();
    $rejected  = $applications->filter(fn($a) => $a->status?->value === 'rejected')->count();
    $licensed  = $applications->filter(fn($a) => $a->status?->value === 'licensed')->count();
    $totalArea = $applications->sum(fn($a) => (float)$a->total_area_m2);
    $totalPrice= $applications->sum(fn($a) => (float)$a->total_price);
@endphp
<div class="stats-bar">
    <div class="stat-item"><div class="stat-label">Toplam</div><div class="stat-value">{{ count($applications) }}</div></div>
    <div class="stat-item"><div class="stat-label">Tamamlandı</div><div class="stat-value">{{ $completed }}</div></div>
    <div class="stat-item"><div class="stat-label">Ruhsatlandı</div><div class="stat-value">{{ $licensed }}</div></div>
    <div class="stat-item"><div class="stat-label">Reddedildi</div><div class="stat-value">{{ $rejected }}</div></div>
    <div class="stat-item"><div class="stat-label">Toplam Alan</div><div class="stat-value">{{ number_format($totalArea, 2) }} m²</div></div>
    <div class="stat-item"><div class="stat-label">Toplam Tutar</div><div class="stat-value">₺ {{ number_format($totalPrice, 2) }}</div></div>
</div>

{{-- Table --}}
<div class="content">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Başvuru No</th>
                <th>Ad Soyad</th>
                <th>Kurum</th>
                <th>Durum</th>
                <th>Adres</th>
                <th>Tarih</th>
                <th class="text-right">Alan (m²)</th>
                <th class="text-right">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $i => $app)
            @php
                $statusVal = $app->status instanceof \App\Enums\ApplicationStatus ? $app->status->value : (string)$app->status;
                $badgeMap = [
                    'draft'            => ['Taslak',          'badge-draft'],
                    'submitted'        => ['Gönderildi',      'badge-submitted'],
                    'priced'           => ['Fiyatlandı',      'badge-priced'],
                    'awaiting_payment' => ['Ödeme Bekliyor',  'badge-awaiting'],
                    'receipt_pending'  => ['Makbuz Bekliyor', 'badge-receipt'],
                    'approved'         => ['Onaylandı',       'badge-approved'],
                    'rejected'         => ['Reddedildi',      'badge-rejected'],
                    'licensed'         => ['Ruhsatlandı',     'badge-licensed'],
                    'field_work'       => ['Saha Çalışması',  'badge-fieldwork'],
                    'completed'        => ['Tamamlandı',      'badge-completed'],
                    'archived'         => ['Arşivlendi',      'badge-archived'],
                ];
                [$sLabel, $sBadge] = $badgeMap[$statusVal] ?? [$statusVal, 'badge-draft'];
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="no">{{ $app->application_no }}</td>
                <td>{{ trim($app->applicant_first_name . ' ' . $app->applicant_last_name) }}</td>
                <td>{{ $app->institution?->name ?? '—' }}</td>
                <td><span class="badge {{ $sBadge }}">{{ $sLabel }}</span></td>
                <td>{{ Str::limit($app->address_text ?? '—', 40) }}</td>
                <td>{{ $app->created_at?->format('d.m.Y') ?? '—' }}</td>
                <td class="text-right">{{ $app->total_area_m2 ? number_format((float)$app->total_area_m2, 2) : '—' }}</td>
                <td class="text-right">{{ $app->total_price ? '₺ ' . number_format((float)$app->total_price, 2) : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:20px; color:#94a3b8; font-style:italic;">
                    Seçili filtrelere ait başvuru bulunamadı.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Footer --}}
<div class="page-footer">
    <span>HGB Bilişim  © {{ date('Y') }} — AYKOME Kazı İzin Yönetim Sistemi</span>
    <span>Bu belge otomatik olarak oluşturulmuştur.</span>
</div>

</body>
</html>
