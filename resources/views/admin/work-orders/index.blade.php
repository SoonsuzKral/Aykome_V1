@extends('layouts.admin')

@section('page-heading', 'Görev Emri Yönetimi')

@push('styles')
<style>
/* ── Kanban kart hover ── */
.kanban-card {
    cursor: default;
    transition: box-shadow .18s, transform .18s;
}
.kanban-card:hover {
    box-shadow: 0 8px 24px -4px rgba(0,0,0,.10);
    transform: translateY(-2px);
}

/* ── DataTables light override ── */
#woTable_wrapper .dataTables_length select,
#woTable_wrapper .dataTables_filter input {
    background: #f9fafb !important;
    color: #374151;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    padding: 5px 1rem 5px 0.75rem !important;
    outline: none !important;
    font-size: 13px;
}
/* ★ Select kutu görünümünü tarayıcı native'ine döndür ── */
#woTable_wrapper .dataTables_length select {
    appearance: auto !important;
    -webkit-appearance: auto !important;
    -moz-appearance: auto !important;
    padding-right: 2rem !important;
    width: auto !important;
    min-width: 4.5rem;
    margin: 0 0.4rem;
    cursor: pointer;
    background-color: #f9fafb !important;
}
#woTable_wrapper .dataTables_length select:focus,
#woTable_wrapper .dataTables_filter input:focus {
    border-color: #02E0FB !important;
    box-shadow: 0 0 0 3px rgba(2,224,251,.12);
}
#woTable_wrapper .dataTables_length label,
#woTable_wrapper .dataTables_filter label { color: #6b7280; font-size: 13px; }
#woTable_wrapper .dataTables_info { color: #9ca3af; font-size: 12px; }
#woTable_wrapper .dataTables_paginate .paginate_button {
    background: transparent;
    color: #6b7280 !important;
    border: none;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 13px;
}
#woTable_wrapper .dataTables_paginate .paginate_button.current,
#woTable_wrapper .dataTables_paginate .paginate_button:hover {
    background: #f0fdfe !important;
    color: #02AFC6 !important;
    border: 1px solid #02E0FB55 !important;
}
table#woTable thead th {
    background: #f9fafb;
    color: #6b7280;
    font-size: 11px;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 11px 14px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
}
table#woTable tbody tr:hover td { background: #f0fdfe40; }
table#woTable tbody td {
    padding: 10px 14px;
    font-size: 13px;
    color: #374151;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

/* ── Avatar renk döngüsü ── */
.av-0 { background:#dbeafe; color:#1d4ed8; }
.av-1 { background:#fef3c7; color:#b45309; }
.av-2 { background:#dcfce7; color:#15803d; }
.av-3 { background:#fce7f3; color:#be185d; }
.av-4 { background:#ede9fe; color:#6d28d9; }
.av-5 { background:#fee2e2; color:#b91c1c; }
.av-6 { background:#e0f2fe; color:#0369a1; }
.av-7 { background:#f0fdf4; color:#166534; }
</style>
@endpush

@section('content')

<div class="space-y-6">

    {{-- ── HEADER ── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-[#02E0FB] to-[#0ab8d0] text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-bold text-gray-900">Görev Emri Yönetimi</h1>
                        <span class="rounded-full bg-gradient-to-r from-[#FA6001]/20 to-[#02E0FB]/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-500">PRO</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">Saha görevlerini Kanban panonuzdan yönetin, personel atamalarını takip edin.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.applications.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 shadow-sm hover:bg-gray-50 hover:text-gray-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                Başvurulara Dön
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── STATS STRIP ── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        @php
        $statItems = [
            ['label' => 'Toplam Görev',  'value' => $stats['total'],       'icon_bg' => 'bg-gray-100',       'icon_color' => 'text-gray-500',   'val_color' => 'text-gray-900'],
            ['label' => 'Bekleyen',       'value' => $stats['pending'],     'icon_bg' => 'bg-amber-50',       'icon_color' => 'text-amber-500',   'val_color' => 'text-amber-600'],
            ['label' => 'Devam Eden',     'value' => $stats['in_progress'], 'icon_bg' => 'bg-[#02E0FB]/10',   'icon_color' => 'text-[#02AFC6]',   'val_color' => 'text-[#02AFC6]'],
            ['label' => 'Tamamlanan',     'value' => $stats['completed'],   'icon_bg' => 'bg-emerald-50',     'icon_color' => 'text-emerald-500', 'val_color' => 'text-emerald-600'],
            ['label' => 'Geciken',        'value' => $stats['overdue'],     'icon_bg' => 'bg-rose-50',        'icon_color' => 'text-rose-400',    'val_color' => 'text-rose-600'],
        ];
        @endphp
        @foreach($statItems as $s)
            <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ $s['label'] }}</p>
                <p class="mt-1 text-3xl font-extrabold {{ $s['val_color'] }}">{{ $s['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── KANBAN BOARD ── --}}
    <div class="grid gap-5 lg:grid-cols-3">
        @php
        $kanbanCols = [
            [
                'key'          => 'pending',
                'label'        => 'Bekleyen',
                'count_bg'     => 'bg-amber-100 text-amber-700',
                'dot'          => 'bg-amber-400',
                'card_border'  => 'border-l-[#FA6001]',
                'header_line'  => 'bg-amber-400',
            ],
            [
                'key'          => 'in_progress',
                'label'        => 'Devam Ediyor',
                'count_bg'     => 'bg-[#02E0FB]/15 text-[#02AFC6]',
                'dot'          => 'bg-[#02E0FB]',
                'card_border'  => 'border-l-[#02E0FB]',
                'header_line'  => 'bg-[#02E0FB]',
            ],
            [
                'key'          => 'completed',
                'label'        => 'Tamamlandı',
                'count_bg'     => 'bg-emerald-100 text-emerald-700',
                'dot'          => 'bg-emerald-400',
                'card_border'  => 'border-l-emerald-400',
                'header_line'  => 'bg-emerald-400',
            ],
        ];
        @endphp

        @foreach($kanbanCols as $col)
            <div class="kanban-col flex flex-col gap-3">

                {{-- Kolon Başlığı --}}
                <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="h-2.5 w-2.5 rounded-full {{ $col['dot'] }}"></span>
                        <span class="text-sm font-bold text-gray-700">{{ $col['label'] }}</span>
                    </div>
                    <span class="rounded-full {{ $col['count_bg'] }} px-2.5 py-0.5 text-xs font-bold tabular-nums">
                        {{ $kanban[$col['key']]->count() }}
                    </span>
                </div>

                {{-- Kartlar --}}
                @forelse($kanban[$col['key']] as $idx => $task)
                    @php
                        $doneStages = collect([1,2,3])->filter(fn($n) => ($task->{"stage_{$n}_status"} ?? 'pending') === 'done')->count();
                        $isOverdue  = $task->due_date && $task->due_date->isPast() && $col['key'] !== 'completed';
                        $avClass    = 'av-' . ($task->assignee ? ($task->assignee->id % 8) : 0);
                        $progress   = intval($doneStages / 3 * 100);
                    @endphp
                    <div class="kanban-card rounded-2xl border border-gray-200 border-l-4 {{ $col['card_border'] }} bg-white p-4 shadow-sm">

                        {{-- Üst satır: No + Gecikme badge --}}
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="inline-flex items-center gap-1 text-xs font-extrabold text-gray-800 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                {{ $task->application?->application_no ?? "Görev #{$task->id}" }}
                            </span>
                            @if($isOverdue)
                                <span class="flex-shrink-0 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-[9px] font-bold text-rose-600">
                                    ⚠ Gecikti
                                </span>
                            @endif
                        </div>

                        {{-- Adres --}}
                        <p class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3 w-3 mr-0.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            {{ $task->application?->address_text ?? 'Adres bilgisi yok' }}
                        </p>

                        {{-- Hedeflenen Tarih --}}
                        @if($task->due_date)
                        <div class="mb-3 flex items-center gap-1.5 text-xs {{ $isOverdue ? 'text-rose-500 font-semibold' : 'text-gray-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                            Termin: {{ $task->due_date->format('d.m.Y') }}
                        </div>
                        @endif

                        {{-- Görevli Avatar + İsim --}}
                        <div class="mb-3 flex items-center gap-2">
                            @if($task->assignee)
                                <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $avClass }}">
                                    {{ strtoupper(mb_substr($task->assignee->name, 0, 2)) }}
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700 leading-none">{{ $task->assignee->name }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Saha Personeli</p>
                                </div>
                            @else
                                <span class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border-2 border-dashed border-gray-200 text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                </span>
                                <p class="text-xs text-gray-400 italic">Atanmamış</p>
                            @endif
                        </div>

                        {{-- Aşama Progress + Detay --}}
                        <div class="border-t border-gray-100 pt-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1">
                                    @foreach([1,2,3] as $sn)
                                        @php $sd = $task->{"stage_{$sn}_status"} ?? 'pending'; @endphp
                                        <span class="h-2 w-2 rounded-full transition {{ $sd === 'done' ? 'bg-emerald-400 shadow-sm' : 'bg-gray-200' }}"></span>
                                    @endforeach
                                    <span class="ml-1.5 text-[10px] font-semibold text-gray-400">{{ $doneStages }}/3 Aşama</span>
                                </div>
                                <a href="{{ route('admin.field-tasks.show', $task) }}"
                                   class="inline-flex items-center gap-1 text-[10px] font-bold text-[#02AFC6] hover:text-[#02E0FB] transition">
                                    Detay
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                            {{-- Progress bar --}}
                            <div class="h-1 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-1 rounded-full transition-all duration-500
                                    {{ $doneStages === 3 ? 'bg-emerald-400' : ($doneStages > 0 ? 'bg-[#02E0FB]' : 'bg-gray-200') }}"
                                    style="width: {{ $progress }}%">
                                </div>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-white py-10 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-xs text-gray-400">Bu sütunda görev yok.</p>
                    </div>
                @endforelse

            </div>
        @endforeach
    </div>

    {{-- ── TÜM GÖREV EMİRLERİ TABLOSU ── --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-bold text-gray-900">Tüm Görev Emirleri</h2>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Filtre butonları --}}
                <div class="flex flex-wrap gap-1.5">
                    @php
                    $filterBtns = ['' => 'Tümü', 'pending' => 'Bekleyen', 'in_progress' => 'Devam', 'completed' => 'Tamamlandı'];
                    @endphp
                    @foreach($filterBtns as $val => $lbl)
                        <button type="button"
                            id="fbtn-{{ $val === '' ? 'all' : $val }}"
                            onclick="filterTable('{{ $val }}')"
                            class="filter-btn rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-500 transition hover:border-[#02E0FB]/60 hover:text-[#02AFC6]">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
                {{-- Export butonları --}}
                <div class="flex items-center gap-1.5 border-l border-gray-100 pl-2">
                    <a href="{{ route('admin.work-orders.export-csv') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Excel Al
                    </a>
                    <a href="{{ route('admin.work-orders.export-pdf') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                        PDF İndir
                    </a>
                </div>
            </div>
        </div>
        <div class="p-5">
            <table id="woTable" class="w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Başvuru No</th>
                        <th>Adres</th>
                        <th>Atanan Kişi</th>
                        <th>Durum</th>
                        <th>Termin</th>
                        <th>Aşamalar</th>
                        <th class="no-sort">İşlem</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
const WO_URL = '{{ route('admin.work-orders.data') }}';
let currentFilter = '';

const dt = $('#woTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: WO_URL, type: 'POST',
        data: d => { d._token = '{{ csrf_token() }}'; d.filter_status = currentFilter; }
    },
    columns: [
        { data: 0, width: '50px', className: 'text-gray-400 text-xs font-mono' },
        { data: 1, render: v => `<span class="font-bold text-gray-800 text-xs">${v}</span>` },
        { data: 2, render: v => `<span class="text-gray-500 text-xs">${v}</span>` },
        { data: 3, render: (name, _, row) => {
            if (!name) return `<span class="text-xs text-gray-400 italic">Atanmamış</span>`;
            const colors = ['bg-blue-100 text-blue-700','bg-amber-100 text-amber-700','bg-emerald-100 text-emerald-700','bg-pink-100 text-pink-700','bg-violet-100 text-violet-700','bg-rose-100 text-rose-700'];
            const cls = colors[Math.abs(name.charCodeAt(0)) % colors.length];
            const init = name.substring(0,2).toUpperCase();
            return `<div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-bold flex-shrink-0 ${cls}">${init}</span>
                <span class="text-xs font-medium text-gray-700">${name}</span>
            </div>`;
        }},
        { data: 4, orderable: false, render: s => {
            const map = {
                pending:     ['Bekleyen',    'bg-amber-50 border border-amber-200 text-amber-700'],
                in_progress: ['Devam',       'bg-[#02E0FB]/10 border border-[#02E0FB]/30 text-[#02AFC6]'],
                completed:   ['Tamamlandı',  'bg-emerald-50 border border-emerald-200 text-emerald-700'],
            };
            const [label, cls] = map[s] || [s, 'bg-gray-100 text-gray-500'];
            return `<span class="rounded-full ${cls} px-2.5 py-0.5 text-[10px] font-bold">${label}</span>`;
        }},
        { data: 5, render: v => v ? `<span class="text-xs text-gray-600">${v}</span>` : `<span class="text-xs text-gray-300">—</span>` },
        { data: 6, orderable: false, render: n => {
            const dots = [1,2,3].map(i =>
                `<span class="inline-block h-2 w-2 rounded-full ${i <= n ? 'bg-emerald-400' : 'bg-gray-200'}"></span>`
            ).join('');
            return `<div class="flex items-center gap-1">${dots}<span class="ml-1.5 text-[10px] font-semibold text-gray-400">${n}/3</span></div>`;
        }},
        { data: 7, orderable: false, render: id =>
            `<a href="/admin/field-tasks/${id}" class="inline-flex items-center gap-1 rounded-lg bg-[#02E0FB]/10 border border-[#02E0FB]/25 px-3 py-1.5 text-xs font-semibold text-[#02AFC6] hover:bg-[#02E0FB]/20 transition">
                Detay
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>`
        },
    ],
    language: {
        search: '',
        searchPlaceholder: 'Ara…',
        lengthMenu: '_MENU_ / sayfa',
        info: '<span class="text-gray-400 text-xs">_TOTAL_ kayıt</span>',
        paginate: { previous: '‹', next: '›' },
        processing: '<span class="text-[#02AFC6] text-xs">Yükleniyor…</span>',
        zeroRecords: '<span class="text-gray-400 text-xs">Kayıt bulunamadı.</span>',
    },
    order: [[0, 'desc']],
    pageLength: 15,
    drawCallback: function() {
        /* Tailwind tablo satır rengi */
        $('#woTable tbody tr').addClass('hover:bg-gray-50/60 transition');
    }
});

function filterTable(status) {
    currentFilter = status;
    dt.ajax.reload();
    /* Aktif buton stilini güncelle */
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('border-[#02E0FB]','text-[#02AFC6]','bg-[#02E0FB]/5');
    });
    const activeId = status === '' ? 'fbtn-all' : `fbtn-${status}`;
    const activeBtn = document.getElementById(activeId);
    if (activeBtn) activeBtn.classList.add('border-[#02E0FB]','text-[#02AFC6]','bg-[#02E0FB]/5');
}
</script>
@endpush
