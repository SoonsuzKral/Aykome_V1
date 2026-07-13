@extends('layouts.admin')
@section('page-heading', 'Kurumlar & Firmalar')

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Kurumlar & Firmalar</h1>
        <p class="mt-0.5 text-sm text-slate-500">Başvuru sahibi kurum ve firmaları yönetin.</p>
    </div>
    <div class="flex items-center gap-2">
        {{-- Search form --}}
        <form method="GET" action="{{ route('admin.institutions.index') }}" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}"
                placeholder="Kurum ara…"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30 w-48">
            <button type="submit"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 transition">
                Ara
            </button>
            @if($q)
            <a href="{{ route('admin.institutions.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm hover:bg-slate-50 transition">✕</a>
            @endif
        </form>
        <button type="button" onclick="openAddModal()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Yeni Kurum
        </button>
    </div>
</div>

{{-- ── Table ─────────────────────────────────────────────────────────────────── --}}
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 bg-white">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Kurum Adı</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Tip</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Yetkili</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Vergi No</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Telefon</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-center">Başvuru</th>
                    <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($institutions as $inst)
                    <tr class="border-b border-gray-50 hover:bg-gray-50/70 transition">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                @if($inst->color_code)
                                    <span class="inline-block h-3 w-3 flex-shrink-0 rounded-full" style="background:{{ $inst->color_code }}"></span>
                                @endif
                                <span class="font-semibold text-slate-800">{{ $inst->name }}</span>
                                @if($inst->is_municipality)
                                    <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">Belediye</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $inst->type ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $inst->authorized_person ?? '—' }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $inst->tax_number ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-slate-500">{{ $inst->phone ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ $inst->applications_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick="openEditModal({{ $inst->id }})"
                                    class="inline-flex items-center rounded-md bg-[#02E0FB] px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:opacity-90">
                                    Düzenle
                                </button>
                                @if($inst->applications_count === 0)
                                    <button type="button" onclick="deleteInstitution({{ $inst->id }}, '{{ addslashes($inst->name) }}')"
                                        class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100">
                                        Sil
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $q ? 'Arama sonucu bulunamadı.' : 'Henüz kurum eklenmedi.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($institutions->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">
            {{ $institutions->links() }}
        </div>
    @endif
</div>

{{-- ── Add Modal ────────────────────────────────────────────────────────────── --}}
<div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-bold text-slate-900">Yeni Kurum Ekle</h2>
            <button type="button" onclick="closeModal('addModal')" class="text-xl leading-none text-slate-400 hover:text-slate-700">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.institutions.store') }}" class="p-6">
            @csrf
            @include('admin.institutions._form')
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('addModal')"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">İptal</button>
                <button type="submit"
                    class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">Kaydet</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Modal ───────────────────────────────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-bold text-slate-900">Kurumu Düzenle</h2>
            <button type="button" onclick="closeModal('editModal')" class="text-xl leading-none text-slate-400 hover:text-slate-700">✕</button>
        </div>
        <form id="editForm" method="POST" action="" class="p-6">
            @csrf
            @method('PUT')
            @include('admin.institutions._form', ['editing' => true])
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('editModal')"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">İptal</button>
                <button type="submit"
                    class="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700">Güncelle</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete form (hidden) --}}
<form id="deleteForm" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function openAddModal() {
    document.getElementById('addModal').classList.replace('hidden', 'flex');
}

function openEditModal(id) {
    fetch(`/admin/institutions/${id}/edit-json`)
        .then(r => r.json())
        .then(data => {
            const form = document.getElementById('editForm');
            form.action = `/admin/institutions/${id}`;
            ['name','type','authorized_person','tax_number','phone','email','address','color_code'].forEach(k => {
                const el = form.querySelector(`[name="${k}"]`);
                if (el) el.value = data[k] ?? '';
            });
            const chk = form.querySelector('[name="is_municipality"]');
            if (chk) chk.checked = !!data.is_municipality;
            document.getElementById('editModal').classList.replace('hidden', 'flex');
        });
}

function closeModal(id) {
    document.getElementById(id).classList.replace('flex', 'hidden');
}

function deleteInstitution(id, name) {
    const msg = `"${name}" kurumunu silmek istediğinize emin misiniz?`;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Kurumu Sil',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
        }).then(r => { if (r.isConfirmed) _submitDelete(id); });
    } else {
        if (confirm(msg)) _submitDelete(id);
    }
}

function _submitDelete(id) {
    const form = document.getElementById('deleteForm');
    form.action = `/admin/institutions/${id}`;
    form.submit();
}
</script>
@endpush
