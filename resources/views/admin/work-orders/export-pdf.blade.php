<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #374151; background: #fff; }
    .header { padding: 16px 20px 12px; border-bottom: 2px solid #02E0FB; margin-bottom: 14px; }
    .header h1 { font-size: 15px; font-weight: 700; color: #111827; }
    .header .meta { font-size: 8px; color: #9ca3af; margin-top: 3px; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }
    .badge-pending    { background: #fef3c7; color: #b45309; }
    .badge-progress   { background: #e0f9fe; color: #0369a1; }
    .badge-completed  { background: #dcfce7; color: #15803d; }
    table { width: 100%; border-collapse: collapse; margin: 0 20px; width: calc(100% - 40px); }
    thead th { background: #f9fafb; color: #6b7280; font-size: 8px; text-transform: uppercase; letter-spacing: .05em; padding: 6px 8px; border: 1px solid #e5e7eb; text-align: left; font-weight: 600; }
    tbody td { padding: 5px 8px; border: 1px solid #f3f4f6; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #fafafa; }
    .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-right: 3px; }
    .dot-pending   { background: #f59e0b; }
    .dot-progress  { background: #02E0FB; }
    .dot-completed { background: #10b981; }
    .footer { margin-top: 14px; padding: 8px 20px; text-align: right; font-size: 7px; color: #d1d5db; border-top: 1px solid #f3f4f6; }
    .stage-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 1px; }
    .stage-done { background: #10b981; }
    .stage-pend { background: #e5e7eb; }
</style>
</head>
<body>

<div class="header">
    <h1>Görev Emri Listesi</h1>
    <div class="meta">HGB Bilişim  AYKOME &middot; Oluşturulma: {{ now()->format('d.m.Y H:i') }} &middot; Toplam {{ $tasks->count() }} kayıt</div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:35px">#</th>
            <th style="width:90px">Başvuru No</th>
            <th>Adres</th>
            <th style="width:100px">Atanan Kişi</th>
            <th style="width:70px">Durum</th>
            <th style="width:60px">Termin</th>
            <th style="width:65px">Aşamalar</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tasks as $task)
        @php
            $statusData = match($task->status) {
                'pending'     => ['Bekleyen',      'badge-pending',   'dot-pending'],
                'in_progress' => ['Devam Ediyor',  'badge-progress',  'dot-progress'],
                'completed'   => ['Tamamlandı',    'badge-completed', 'dot-completed'],
                default       => [$task->status,   '',                ''],
            };
            $doneCount = collect([1,2,3])
                ->filter(fn($n) => ($task->{"stage_{$n}_status"} ?? 'pending') === 'done')
                ->count();
        @endphp
        <tr>
            <td style="color:#9ca3af;font-family:monospace">{{ $task->id }}</td>
            <td style="font-weight:700;color:#111827">{{ $task->application?->application_no ?? '—' }}</td>
            <td style="color:#6b7280">{{ $task->application?->address_text ?? '—' }}</td>
            <td>{{ $task->assignee?->name ?? '—' }}</td>
            <td><span class="badge {{ $statusData[1] }}">{{ $statusData[0] }}</span></td>
            <td style="color:#6b7280">{{ $task->due_date?->format('d.m.Y') ?? '—' }}</td>
            <td>
                @foreach([1,2,3] as $n)
                    <span class="stage-dot {{ ($task->{"stage_{$n}_status"} ?? 'pending') === 'done' ? 'stage-done' : 'stage-pend' }}"></span>
                @endforeach
                <span style="color:#6b7280"> {{ $doneCount }}/3</span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">HGB Bilişim  AYKOME &copy; {{ date('Y') }}</div>

</body>
</html>
