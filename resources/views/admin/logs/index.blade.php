@extends('layouts.admin')

@section('page-heading', 'Sistem Denetim Logları')

@push('head')
<style>
/* ── DataTables wrapper (DT-generated inputs — can't reach with Tailwind) ── */
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
#audit-table tbody tr:hover td { background: #eff6ff !important; }
#audit-table thead th:after, #audit-table thead th:before { color: #9ca3af; }
table.dataTable.no-footer { border-bottom: 1px solid #f3f4f6 !important; }
</style>
{{-- Tailwind safelist — keeps dynamically-added classes in the build --}}
<div class="hidden
    px-3 py-2 mx-1 inline-flex items-center justify-center
    border border-gray-200 bg-white text-gray-600 rounded-lg text-sm font-medium cursor-pointer
    bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold
    bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed
    bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider border-b-2 border-gray-200
    px-6 py-4 border-b border-gray-100 transition duration-150
    bg-[#FA6001] border-[#FA6001]"></div>
@endpush

@section('content')
    {{-- ── Başlık + Meta ─────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Sistem Denetim Logları</h1>
            <p class="mt-1 text-sm text-gray-500">Kim ne zaman sisteme girmiş, hangi işlemi yapmış — saniye saniye.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-xs font-bold text-red-700 ring-1 ring-red-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
            SUPER ADMIN
        </span>
    </div>

    {{-- ── KPI Kartları ──────────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Toplam Kayıt</p>
                <p class="text-2xl font-bold tabular-nums text-gray-900">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-cyan-100 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-50">
                <svg class="h-5 w-5 text-cyan-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Bugün</p>
                <p class="text-2xl font-bold tabular-nums text-cyan-600">{{ number_format($stats['today']) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Bugün Giriş</p>
                <p class="text-2xl font-bold tabular-nums text-emerald-700">{{ number_format($stats['logins']) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-orange-100 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50">
                <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Bugün İşlem</p>
                <p class="text-2xl font-bold tabular-nums text-orange-500">{{ number_format($stats['actions']) }}</p>
            </div>
        </div>
    </div>

    {{-- ── İşlem Filtresi ────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">İşlem Filtresi:</p>
        <div class="flex flex-wrap gap-2" id="actionFilterBar">
            @foreach([
                ['' ,             'Tümü'],
                ['auth.',         'Giriş / Çıkış'],
                ['tckn.',         'TCKN'],
                ['application.',  'Başvuru'],
                ['price.',        'Fiyat'],
                ['receipt.',      'Makbuz'],
                ['task.',         'Görev'],
                ['license.',      'Lisans'],
                ['settings.',     'Ayarlar'],
            ] as [$filter, $label])
            <button
                type="button"
                data-filter="{{ $filter }}"
                class="action-filter-btn px-4 py-2 text-sm font-medium rounded-full border border-gray-300 text-gray-600 hover:text-white hover:bg-[#FA6001] hover:border-[#FA6001] transition duration-150 {{ $filter === '' ? 'active-filter' : '' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── DataTable ─────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Log Kayıtları</h2>
        </div>
        <div class="overflow-x-auto px-4 pb-5 pt-4">
            <table id="audit-table"
                class="min-w-full w-full text-left border-collapse text-sm text-gray-700 bg-white"
                style="min-width:960px">
                <thead>
                    <tr>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs" style="width:55px;">#</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Kullanıcı</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Rol</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">İşlem</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Açıklama</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Konu</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">IP Adresi</th>
                        <th class="bg-gray-100 text-gray-500 font-semibold uppercase tracking-wider px-6 py-4 border-b-2 border-gray-200 text-xs">Tarih & Saat</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
    let currentActionFilter = '';

    /* ── Active filter button style helper ── */
    function setActiveFilterBtn($btn) {
        $('#actionFilterBar .action-filter-btn')
            .removeClass('bg-[#FA6001] text-white border-[#FA6001] shadow-md')
            .addClass('border-gray-300 text-gray-600');
        $btn.removeClass('border-gray-300 text-gray-600')
            .addClass('bg-[#FA6001] text-white border-[#FA6001] shadow-md');
    }

    /* Set initial active state on "Tümü" */
    setActiveFilterBtn($('[data-filter=""]'));

    function stylePagination() {
        $('.dataTables_paginate .paginate_button')
            .addClass('px-3 py-2 border border-gray-200 bg-white text-gray-600 rounded-lg cursor-pointer mx-1 inline-flex items-center justify-center text-sm font-medium transition duration-150')
            .removeClass('bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold text-gray-300 bg-gray-50 border-gray-100 cursor-not-allowed');

        $('.dataTables_paginate .current')
            .removeClass('bg-white text-gray-600 border-gray-200')
            .addClass('bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold');

        $('.dataTables_paginate .disabled')
            .removeClass('cursor-pointer bg-white text-gray-600')
            .addClass('text-gray-300 bg-gray-50 border-gray-100 cursor-not-allowed');
    }

    const table = $('#audit-table').DataTable({
        processing:    true,
        serverSide:    true,
        ajax: {
            url:  '{{ route('admin.logs.data') }}',
            type: 'GET',
            data: function (d) {
                d.action_filter = currentActionFilter;
            },
        },
        dom: '<"flex flex-wrap items-center justify-between gap-3 mb-4"lf>rt<"flex flex-wrap items-center justify-between gap-3 mt-4"ip>',
        columns: [
            {
                data: 'id',
                width: '55px',
                orderable: false,
                render: function(data) {
                    return '<span class="font-mono text-xs text-gray-400 tabular-nums">' + data + '</span>';
                }
            },
            {
                data: 'user_name',
                render: function(data) {
                    return '<span class="font-medium text-gray-800">' + (data || '—') + '</span>';
                }
            },
            {
                data: 'user_role',
                orderable: false,
                render: function (data) {
                    if (!data) return '<span class="text-gray-400">&mdash;</span>';
                    return '<span class="inline-block rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">' + data + '</span>';
                }
            },
            {
                data: 'action_label',
                orderable: false,
                render: function (data, type, row) {
                    return '<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ' + row.badge_class + '">' + data + '</span>';
                }
            },
            {
                data: 'description',
                render: function (data) {
                    const safe = $('<div>').text(data).html();
                    return '<span style="display:block;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safe + '">' + safe + '</span>';
                }
            },
            {
                data: 'subject',
                orderable: false,
                render: function(data) {
                    return '<span class="font-mono text-xs text-gray-500">' + (data || '—') + '</span>';
                }
            },
            {
                data: 'ip_address',
                orderable: false,
                render: function(data) {
                    return '<span class="font-mono text-xs text-gray-500 tabular-nums">' + (data || '—') + '</span>';
                }
            },
            {
                data: 'created_at',
                render: function(data) {
                    return '<span class="font-mono text-xs tabular-nums text-gray-600 whitespace-nowrap">' + (data || '—') + '</span>';
                }
            },
        ],
        createdRow: function (row) {
            $(row).addClass('border-b border-gray-100 transition duration-150');
            $('td', row).addClass('px-6 py-4');
        },
        order:       [[7, 'desc']],
        pageLength:  25,
        lengthMenu:  [10, 25, 50, 100],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/tr.json',
        },
        drawCallback: function () {
            stylePagination();
        },
    });

    /* ── Action filter buttons ── */
    $('#actionFilterBar').on('click', '.action-filter-btn', function () {
        currentActionFilter = $(this).data('filter');
        setActiveFilterBtn($(this));
        table.ajax.reload();
    });
});
</script>
@endpush
