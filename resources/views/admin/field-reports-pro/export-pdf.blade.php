<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #374151; background: #fff; }
    .header { padding: 16px 20px 12px; border-bottom: 2px solid #FA6001; margin-bottom: 14px; }
    .header h1 { font-size: 15px; font-weight: 700; color: #111827; }
    .header .meta { font-size: 8px; color: #9ca3af; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 0 20px; width: calc(100% - 40px); }
    thead th { background: #f9fafb; color: #6b7280; font-size: 8px; text-transform: uppercase; letter-spacing: .05em; padding: 6px 8px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; }
    tbody td { padding: 5px 8px; border: 1px solid #f3f4f6; vertical-align: middle; }
    tbody tr:nth-child(even) td { background: #fafafa; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }
    .badge-high { background: #dcfce7; color: #15803d; }
    .badge-mid  { background: #fef3c7; color: #b45309; }
    .badge-low  { background: #fee2e2; color: #b91c1c; }
    .bar-wrap { background: #f3f4f6; border-radius: 4px; height: 6px; width: 80px; display: inline-block; }
    .bar-fill { height: 6px; border-radius: 4px; display: block; }
    .bar-green  { background: #10b981; }
    .bar-amber  { background: #f59e0b; }
    .bar-red    { background: #ef4444; }
    .footer { margin-top: 14px; padding: 8px 20px; text-align: right; font-size: 7px; color: #d1d5db; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>

<div class="header">
    <h1>Saha Personeli Performans Raporu</h1>
    <div class="meta">HGB Bilişim  AYKOME &middot; Oluşturulma: {{ now()->format('d.m.Y H:i') }} &middot; {{ $personnel->count() }} personel</div>
</div>

<table>
    <thead>
        <tr>
            <th>Personel</th>
            <th style="width:140px">E-posta</th>
            <th style="width:50px;text-align:center">Toplam</th>
            <th style="width:60px;text-align:center">Tamamlanan</th>
            <th style="width:50px;text-align:center">Geciken</th>
            <th style="width:110px">Başarı Oranı</th>
            <th style="width:110px">Gecikme Oranı</th>
            <th style="width:65px;text-align:center">Performans</th>
        </tr>
    </thead>
    <tbody>
        @foreach($personnel as $p)
        @php
            $perfBadge = match($p->perf_level) {
                'high'  => ['Yüksek', 'badge-high'],
                'mid'   => ['Orta',   'badge-mid'],
                default => ['Düşük',  'badge-low'],
            };
            $successColor = match($p->perf_level) {
                'high'  => 'bar-green',
                'mid'   => 'bar-amber',
                default => 'bar-red',
            };
            $delayColor = $p->delay_rate > 30 ? 'bar-red' : ($p->delay_rate > 10 ? 'bar-amber' : 'bar-green');
        @endphp
        <tr>
            <td style="font-weight:700;color:#111827">{{ $p->name }}</td>
            <td style="color:#6b7280;font-size:8px">{{ $p->email }}</td>
            <td style="text-align:center;font-weight:700">{{ $p->total_tasks }}</td>
            <td style="text-align:center;color:#15803d;font-weight:700">{{ $p->completed_tasks }}</td>
            <td style="text-align:center;color:{{ $p->overdue_tasks > 0 ? '#b91c1c' : '#9ca3af' }};font-weight:700">
                {{ $p->overdue_tasks > 0 ? $p->overdue_tasks : '—' }}
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:5px">
                    <div class="bar-wrap"><span class="bar-fill {{ $successColor }}" style="width:{{ $p->success_rate }}%"></span></div>
                    <span style="font-size:9px;font-weight:700">{{ $p->success_rate }}%</span>
                </div>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:5px">
                    <div class="bar-wrap"><span class="bar-fill {{ $delayColor }}" style="width:{{ min($p->delay_rate, 100) }}%"></span></div>
                    <span style="font-size:9px;font-weight:700">{{ $p->delay_rate }}%</span>
                </div>
            </td>
            <td style="text-align:center"><span class="badge {{ $perfBadge[1] }}">{{ $perfBadge[0] }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">HGB Bilişim  AYKOME &copy; {{ date('Y') }}</div>

</body>
</html>
