@extends('layouts.admin')
@section('page-heading', 'Kullanıcılar')

@section('content')

{{-- Tailwind safelist (JIT purge koruması) --}}
<div class="hidden">
    <span class="bg-violet-100 text-violet-700 bg-blue-100 text-blue-700 bg-sky-100 text-sky-700 bg-indigo-100 text-indigo-700 bg-cyan-100 text-cyan-700 bg-amber-100 text-amber-700 bg-emerald-100 text-emerald-700 bg-red-100 text-red-600 bg-slate-100 text-slate-600"></span>
    <span class="px-3 py-2 mx-1 inline-flex border border-gray-200 bg-white text-gray-600 rounded-lg bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold text-gray-300 border-gray-100 cursor-not-allowed"></span>
</div>

{{-- Header --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Kullanıcılar</h1>
        <p class="mt-0.5 text-sm text-slate-500">Sistem kullanıcılarını ve rollerini yönetin.</p>
    </div>
    <div class="flex items-center gap-2">
        <button id="btn-refresh" type="button"
            class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
            </svg>
            Yenile
        </button>
        <a href="{{ route('admin.users.create') }}"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Yeni Kullanıcı
        </a>
    </div>
</div>

{{-- DataTable --}}
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-auto max-h-screen">
    <table id="usersTable" class="w-full border-collapse bg-white table-fixed">
        <thead class="sticky top-0 z-10">
            <tr>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-12">#</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-40">Ad Soyad</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-48 hidden md:table-cell">E-posta</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-32">Kurum</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-36">Roller</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-16 hidden lg:table-cell">Durum</th>
                <th class="bg-gray-100 px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide border-b border-gray-200 w-28">İşlem</th>
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
const USERS_DATA_URL = '{{ route('admin.users.data') }}';
const CSRF_TOKEN     = '{{ csrf_token() }}';
let usersTable;

function stylePagination() {
    $('#usersTable_wrapper .dataTables_paginate .paginate_button').each(function () {
        $(this).removeClass().addClass('px-3 py-2 mx-1 inline-flex border border-gray-200 bg-white text-gray-600 rounded-lg text-sm cursor-pointer select-none transition hover:bg-gray-50');
        if ($(this).hasClass('current')) {
            $(this).addClass('bg-[#02E0FB] text-white border-[#02E0FB] shadow-md font-bold').removeClass('bg-white text-gray-600 border-gray-200');
        }
        if ($(this).hasClass('disabled')) {
            $(this).addClass('text-gray-300 border-gray-100 cursor-not-allowed').removeClass('cursor-pointer hover:bg-gray-50');
        }
    });
    $('#usersTable_wrapper .dataTables_length select, #usersTable_wrapper .dataTables_filter input')
        .addClass('rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:border-gray-400');
    $('#usersTable_wrapper .dataTables_length label, #usersTable_wrapper .dataTables_filter label').addClass('text-sm text-gray-500');
    $('#usersTable_wrapper .dataTables_info').addClass('text-sm text-gray-500');
}

$(function () {
    usersTable = $('#usersTable').DataTable({
        processing : true,
        serverSide : true,
        responsive : false,
        searchDelay: 400,
        scrollX: false,
        autoWidth: false,
        ajax: {
            url : USERS_DATA_URL,
            type: 'POST',
            data: function (d) {
                d._token      = CSRF_TOKEN;
                d.role_filter = activeRole;
            },
        },
        columns: [
            { data: 0, title: '#',       className: 'font-mono text-xs text-gray-400' },
            { data: 1, title: 'Ad',      className: 'font-semibold text-slate-800 text-sm' },
            { data: 2, title: 'E-posta', className: 'text-slate-500 text-xs hidden md:table-cell' },
            { data: 3, title: 'Kurum',   className: 'text-slate-600 text-xs' },
            { data: 4, title: 'Roller',  orderable: false, className: 'text-xs' },
            { data: 5, title: 'Durum',   orderable: false, className: 'hidden lg:table-cell' },
            {
                data: 6, title: 'İşlem', orderable: false,
                render: function (id) {
                    return `<div class="flex items-center gap-1.5">
                        <a href="/admin/users/${id}" class="inline-flex items-center rounded border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50 whitespace-nowrap">Görüntüle</a>
                        <a href="/admin/users/${id}/edit" class="inline-flex items-center rounded bg-[#02E0FB] px-2 py-1 text-xs font-medium text-white transition hover:opacity-90 whitespace-nowrap">Düzenle</a>
                        <button onclick="deleteUser(${id})" class="text-red-600 hover:text-red-900 font-medium text-xs transition whitespace-nowrap">Sil</button>
                    </div>`;
                },
            },
        ],
        createdRow: function (row) {
            $(row).addClass('hover:bg-gray-50/70 transition');
            $('td', row).addClass('px-3 py-2.5 text-sm border-b border-gray-100 align-middle');
        },
        headerCallback: function (thead) {
            $(thead).find('th').addClass('bg-gray-50/50');
        },
        drawCallback : function () { stylePagination(); },
        dom          : '<"flex flex-wrap items-center justify-between gap-3 mb-3 px-1 pt-3"lf><"rounded-xl border border-slate-200"rt><"flex flex-wrap items-center justify-between gap-3 mt-3 px-1 pb-3"ip>',
        language     : { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/tr.json' },
        order        : [[0, 'asc']],
        pageLength   : 15,
    });

    $('#roleFilterBar').on('click', '.role-btn', function () {
        activeRole = $(this).data('role');
        $('.role-btn').removeClass('bg-[#FA6001] text-white border-[#FA6001] shadow-md').addClass('border-gray-300 text-gray-600');
        $(this).addClass('bg-[#FA6001] text-white border-[#FA6001] shadow-md').removeClass('border-gray-300 text-gray-600');
        usersTable.ajax.reload();
    });

    $('#btn-refresh').on('click', function () {
        usersTable.ajax.reload(null, false);
    });
});

function deleteUser(id) {
    if (typeof Swal === 'undefined') {
        if (!confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) return;
        _doDeleteUser(id);
        return;
    }
    Swal.fire({
        title: 'Kullanıcıyı Sil',
        text: 'Bu kullanıcıyı kalıcı olarak silmek istediğinize emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'Vazgeç',
    }).then(r => { if (r.isConfirmed) _doDeleteUser(id); });
}

function _doDeleteUser(id) {
    fetch(`/admin/users/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            usersTable.ajax.reload(null, false);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Kullanıcı silindi.', showConfirmButton: false, timer: 2500 });
        } else {
            Swal.fire({ icon: 'error', title: 'Hata', text: data.error ?? 'Silme işlemi başarısız.' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Hata', text: 'Bağlantı hatası.' }));
}
</script>
@endpush
