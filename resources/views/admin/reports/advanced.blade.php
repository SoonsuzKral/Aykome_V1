@extends('layouts.admin')

@section('title', 'Gelişmiş Rapor Motoru')

@push('head')
<style>
/* ── DataTables wrapper layout (input/select — created by DT, can't use Tailwind) ── */
div.dataTables_wrapper div.dataTables_length label,
div.dataTables_wrapper div.dataTables_filter label {
    display: flex; align-items: center; gap: 8px;
    color: #6b7280; font-size: 13px; font-weight: 500;
}
div.dataTables_wrapper div.dataTables_filter input {
    border: 1px solid #d1d5db; border-radius: 8px;
    padding: 7px 14px; font-size: 13px; color: #374151;
    background: #fff; outline: none; min-width: 210px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    transition: border-color .15s, box-shadow .15s;
}
div.dataTables_wrapper div.dataTables_filter input:focus {
    border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,.18);
}
div.dataTables_wrapper div.dataTables_length select {
    border: 1px solid #d1d5db; border-radius: 8px;
    padding: 6px 10px; font-size: 13px; color: #6b7280;
    background: #fff; outline: none; cursor: pointer;
}
div.dataTables_wrapper div.dataTables_info {
    font-size: 12px; color: #9ca3af; padding: 0; line-height: 2;
}
/* Row hover — must be CSS because Tailwind can't target DT-created rows via addClass */
#reportTable tbody tr:hover td { background: #eff6ff !important; }
/* Sort arrows color */
#reportTable thead th:after, #reportTable thead th:before { color: #9ca3af; }
/* Remove DT's default border-bottom on table */
table.dataTable.no-footer { border-bottom: 1px solid #f3f4f6 !important; }
/* Processing overlay */
div.dataTables_processing { border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
</style>
{{-- Tailwind safelist — ensures dynamically-added classes survive the build purge --}}
<div class="hidden
    px-3 py-2 mx-1 inline-flex items-center justify-center
    border border-gray-200 bg-white text-gray-600 rounded-lg text-sm font-medium cursor-pointer
    bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold
    bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed
    bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider border-b-2 border-gray-200
    px-6 py-4 border-b border-gray-100 transition duration-150"></div>
@endpush

@section('content')
<div class="mx-auto max-w-screen-2xl space-y-5">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-slate-800">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
                Gelişmiş Rapor Motoru
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">Filtreleyin, seçin, PDF veya Excel olarak alın.</p>
        </div>
        <span class="w-fit rounded-full bg-orange-100 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-orange-600 ring-1 ring-orange-200">PRO</span>
    </div>

    {{-- ── Filter Panel ──────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
            <svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Filtre Motoru
        </div>

        <div class="space-y-4">
            {{-- Date / region / institution grid --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Başlangıç Tarihi</label>
                    <input type="date" id="date_from"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Bitiş Tarihi</label>
                    <input type="date" id="date_to"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">İlçe / Bölge</label>
                    <input type="text" id="region" placeholder="Örn: Kadıköy, Merkez..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm placeholder-gray-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Kurum</label>
                    <select id="institution_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                        <option value="">— Tümü —</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Status badge buttons ─────────────────────────────────────── --}}
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-500">
                    Durum <span class="font-normal normal-case text-gray-400">(birden fazla seçilebilir; boş = tümü)</span>
                </label>
                <div class="flex flex-wrap gap-2" id="statusBadges">
                    @php
                    $statCfg = [
                        'draft'            => 'Taslak',
                        'submitted'        => 'Gönderildi',
                        'priced'           => 'Fiyatlandı',
                        'awaiting_payment' => 'Ödeme Bekliyor',
                        'receipt_pending'  => 'Makbuz Bekliyor',
                        'approved'         => 'Onaylandı',
                        'rejected'         => 'Reddedildi',
                        'licensed'         => 'Ruhsatlandı',
                        'field_work'       => 'Saha Çalışması',
                        'completed'        => 'Tamamlandı',
                        'archived'         => 'Arşivlendi',
                    ];
                    @endphp
                    @foreach($statCfg as $val => $label)
                    <button type="button"
                        class="status-btn px-4 py-2 text-sm font-medium rounded-full border border-gray-300 text-gray-600 hover:text-white hover:bg-[#FA6001] hover:border-[#FA6001] transition duration-150"
                        data-value="{{ $val }}"
                        data-active="false">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Action Buttons ────────────────────────────────────────────── --}}
            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4">
                <button type="button" id="applyFilters"
                    class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    Filtrele
                </button>
                <button type="button" id="clearFilters"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    Temizle
                </button>

                <div class="flex-1"></div>

                <span id="selectionBadge" class="hidden rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-700 ring-1 ring-cyan-200"></span>

                <button type="button" id="exportPdf"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-100 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v13a2 2 0 01-2 2h-2M9 17H15M9 13H15M13 3v5h5"/></svg>
                    PDF İndir
                </button>
                <button type="button" id="exportCsv"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Excel / CSV
                </button>
            </div>
        </div>
    </div>

    {{-- ── DataTable Card ────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-700">Başvuru Listesi</h2>
            <span id="resultBadge" class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500"></span>
        </div>
        <div class="overflow-x-auto px-4 pb-5 pt-4">
            <table id="reportTable"
                class="min-w-full w-full text-left border-collapse text-sm text-gray-700 bg-white shadow-sm rounded-lg overflow-hidden">
                <thead>
                    <tr>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs"
                            style="width:44px;text-align:center;">
                            <input type="checkbox" id="selectAll"
                                class="h-3.5 w-3.5 cursor-pointer rounded border-gray-300 accent-cyan-600 align-middle"
                                title="Tümünü seç">
                        </th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs" style="width:55px;">#ID</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Başvuru No</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Ad Soyad</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Kurum</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Durum</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Adres / Bölge</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Tarih</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs text-right">Alan (m²)</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs text-right">Tutar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

{{-- Hidden export form --}}
<form id="exportForm" method="POST" style="display:none">
    @csrf
</form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
(function () {
    'use strict';

    const selectedIds = new Set();

    /* ── Status badge toggle ── */
    document.querySelectorAll('.status-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const active = this.getAttribute('data-active') === 'true';
            this.setAttribute('data-active', active ? 'false' : 'true');
            if (active) {
                this.classList.remove('bg-[#FA6001]', 'text-white', 'border-[#FA6001]', 'shadow-md');
                this.classList.add('border-gray-300', 'text-gray-600');
            } else {
                this.classList.remove('border-gray-300', 'text-gray-600');
                this.classList.add('bg-[#FA6001]', 'text-white', 'border-[#FA6001]', 'shadow-md');
            }
        });
    });

    function getActiveStatuses() {
        return [...document.querySelectorAll('.status-btn[data-active="true"]')]
            .map(b => b.getAttribute('data-value'));
    }

    function getFilters() {
        const f = {};
        const v = id => document.getElementById(id)?.value ?? '';
        if (v('date_from'))      f.date_from      = v('date_from');
        if (v('date_to'))        f.date_to        = v('date_to');
        if (v('region'))         f.region         = v('region');
        if (v('institution_id')) f.institution_id = v('institution_id');
        const st = getActiveStatuses();
        if (st.length) f['statuses[]'] = st;
        return f;
    }

    function updateSelectionBadge() {
        const badge = document.getElementById('selectionBadge');
        if (!badge) return;
        if (selectedIds.size > 0) {
            badge.textContent = selectedIds.size + ' satır seçili';
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function stylePagination() {
        /* Reset all paginate buttons to base style */
        $('.dataTables_paginate .paginate_button')
            .addClass('px-3 py-2 border border-gray-200 bg-white text-gray-600 rounded-lg cursor-pointer mx-1 inline-flex items-center justify-center text-sm font-medium transition duration-150')
            .removeClass('bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold text-gray-300 bg-gray-50 border-gray-100 cursor-not-allowed');

        /* Active (current) page */
        $('.dataTables_paginate .current')
            .removeClass('bg-white text-gray-600 border-gray-200 hover\\:bg-gray-100')
            .addClass('bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold');

        /* Disabled buttons */
        $('.dataTables_paginate .disabled')
            .removeClass('cursor-pointer bg-white text-gray-600')
            .addClass('text-gray-300 bg-gray-50 border-gray-100 cursor-not-allowed');
    }

    let table = null;

    function buildTable() {
        if (table) { table.ajax.reload(); return; }

        table = $('#reportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url:     '{{ route("admin.reports.data") }}',
                type:    'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data:    function (d) { Object.assign(d, getFilters()); },
            },
            dom: '<"flex flex-wrap items-center justify-between gap-3 mb-4"lf>rt<"flex flex-wrap items-center justify-between gap-3 mt-4"ip>',
            columnDefs: [
                {
                    targets: 0, orderable: false, data: null,
                    render: function (data, type, row) {
                        const id  = row[0];
                        const chk = selectedIds.has(String(id)) ? ' checked' : '';
                        return '<input type="checkbox" class="row-cb h-3.5 w-3.5 cursor-pointer rounded border-gray-300 accent-cyan-600 align-middle" value="' + id + '"' + chk + '>';
                    },
                    className: 'text-center',
                    width: '44px',
                },
                { targets: 1, data: 0, width: '55px' },
                { targets: 2, data: 1 },
                { targets: 3, data: 2 },
                { targets: 4, data: 3 },
                { targets: 5, data: 4, orderable: false },
                { targets: 6, data: 5 },
                { targets: 7, data: 6 },
                { targets: 8, data: 7, className: 'dt-right' },
                { targets: 9, data: 8, className: 'dt-right' },
            ],
            createdRow: function (row) {
                $(row).addClass('border-b border-gray-100 transition duration-150');
                $('td', row).addClass('px-6 py-4');
            },
            order:      [[7, 'desc']],
            pageLength: 25,
            language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/tr.json' },
            drawCallback: function (settings) {
                const total = settings.json?.recordsFiltered ?? 0;
                const badge = document.getElementById('resultBadge');
                if (badge) badge.textContent = total + ' kayıt';

                document.querySelectorAll('.row-cb').forEach(function (cb) {
                    cb.checked = selectedIds.has(cb.value);
                });
                const all = document.querySelectorAll('.row-cb');
                const selAll = document.getElementById('selectAll');
                if (selAll) selAll.checked = all.length > 0 && [...all].every(c => c.checked);

                stylePagination();
            },
        });

        $('#reportTable').on('change', '.row-cb', function () {
            if (this.checked) selectedIds.add(this.value);
            else selectedIds.delete(this.value);
            updateSelectionBadge();
        });
    }

    document.getElementById('selectAll')?.addEventListener('change', function () {
        const checked = this.checked;
        document.querySelectorAll('.row-cb').forEach(function (cb) {
            cb.checked = checked;
            if (checked) selectedIds.add(cb.value); else selectedIds.delete(cb.value);
        });
        updateSelectionBadge();
    });

    document.getElementById('applyFilters')?.addEventListener('click', buildTable);

    document.getElementById('clearFilters')?.addEventListener('click', function () {
        ['date_from','date_to','region'].forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
        document.getElementById('institution_id').value = '';
        document.querySelectorAll('.status-btn[data-active="true"]').forEach(function (btn) {
            btn.setAttribute('data-active', 'false');
            btn.classList.remove('bg-[#FA6001]', 'text-white', 'border-[#FA6001]', 'shadow-md');
            btn.classList.add('border-gray-300', 'text-gray-600');
        });
        selectedIds.clear();
        updateSelectionBadge();
        if (table) table.ajax.reload();
    });

    function submitExport(action) {
        const form = document.getElementById('exportForm');
        form.action = action;
        form.querySelectorAll('.dyn-input').forEach(el => el.remove());
        const add = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val; inp.className = 'dyn-input';
            form.appendChild(inp);
        };
        const f = getFilters();
        Object.entries(f).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(item => add(k, item)); else add(k, v);
        });
        selectedIds.forEach(id => add('ids[]', id));
        form.submit();
    }

    document.getElementById('exportPdf')?.addEventListener('click', () => submitExport('{{ route("admin.reports.export-pdf") }}'));
    document.getElementById('exportCsv')?.addEventListener('click', () => submitExport('{{ route("admin.reports.export-csv") }}'));

    document.addEventListener('DOMContentLoaded', buildTable);
})();
</script>
@endpush
