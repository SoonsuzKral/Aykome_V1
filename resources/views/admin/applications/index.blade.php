@extends('layouts.admin')
@section('page-heading', 'Başvurular')

@section('content')
@php
    $badgeMap = [
        'draft'                  => ['Taslak',                'bg-slate-100 text-slate-700'],
        'submitted'              => ['Ön Kazı Bekliyor',      'bg-sky-100 text-sky-700'],
        'pre_excavation_approved'=> ['Ön Kazı Onaylı',        'bg-cyan-100 text-cyan-700'],
        'priced'                 => ['Fiyatlandı',            'bg-indigo-100 text-indigo-700'],
        'awaiting_payment'       => ['Ödeme Bekliyor',        'bg-amber-100 text-amber-700'],
        'receipt_pending'        => ['Makbuz Bekliyor',       'bg-orange-100 text-orange-700'],
        'approved'               => ['Onaylandı',             'bg-emerald-100 text-emerald-700'],
        'licensed'               => ['Ruhsatlandı',           'bg-teal-100 text-teal-700'],
        'field_work'             => ['Saha Çalışması',        'bg-violet-100 text-violet-700'],
        'completed'              => ['Tamamlandı',            'bg-green-100 text-green-700'],
        'rejected'               => ['Reddedildi',            'bg-red-100 text-red-700'],
        'archived'               => ['Arşivlendi',            'bg-gray-200 text-gray-600'],
    ];
    $filters = $filters ?? ['q' => '', 'status' => '', 'institution_id' => ''];
@endphp

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Kazı Başvuruları</h1>
        <p class="mt-0.5 text-sm text-slate-500">Filtreleme, durum takibi ve hızlı aksiyonlar.</p>
    </div>
    <div class="flex items-center gap-2">
        <button id="bulk-delete-btn" type="button" disabled
            class="hidden inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 shadow-sm transition hover:bg-red-100 disabled:opacity-40 disabled:cursor-not-allowed">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span id="bulk-delete-label">Seçilenleri Sil</span>
        </button>
        <button id="refresh-btn" type="button"
            class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
            </svg>
            Yenile
        </button>
        @can('create', App\Models\Application::class)
            <a href="{{ route('admin.applications.create') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                Yeni Başvuru
            </a>
        @endcan
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
<div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route('admin.applications.index') }}"
          class="flex flex-wrap items-end gap-3">

        {{-- Search --}}
        <div class="flex-1 min-w-[180px]">
            <label class="mb-1 block text-xs font-medium text-slate-500">Arama</label>
            <input type="text" name="q" value="{{ $filters['q'] }}"
                placeholder="No, ad soyad, TCKN, adres…"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30">
        </div>

        {{-- Status --}}
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-medium text-slate-500">Durum</label>
            <select name="status"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30">
                <option value="">Tüm Durumlar</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>
                        {{ $badgeMap[$s->value][0] ?? $s->value }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Institution (admin/municipality only) --}}
        @if($institutions->isNotEmpty())
        <div class="min-w-[160px]">
            <label class="mb-1 block text-xs font-medium text-slate-500">Kurum</label>
            <select name="institution_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30">
                <option value="">Tüm Kurumlar</option>
                @foreach($institutions as $inst)
                    <option value="{{ $inst->id }}" @selected((string)$filters['institution_id'] === (string)$inst->id)>{{ $inst->name }}</option>
                @endforeach
            </select>
        </div>
        @else
            <input type="hidden" name="institution_id" value="{{ $filters['institution_id'] }}">
        @endif

        {{-- Buttons --}}
        <div class="flex gap-2">
            <button type="submit"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-black">
                Filtrele
            </button>
            <a href="{{ route('admin.applications.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Temizle
            </a>
        </div>
    </form>
</div>

{{-- ── Table ─────────────────────────────────────────────────────────────────── --}}
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white">
            <thead>
                <tr>
                        <th class="bg-gray-50/50 px-4 py-4 text-left border-b border-gray-200 w-10">
                            <input type="checkbox" id="select-all" class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-400/30">
                        </th>
                        <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Başvuru No</th>
                        <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Kurum</th>
                        <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Başvuran</th>
                        <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Durum</th>
                        <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Tarih</th>
                        <th class="bg-gray-50/50 px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">İşlem</th>
                    </tr>
            </thead>
            <tbody>
                @forelse($applications as $row)
                    @php
                        $sv = $row->status instanceof \BackedEnum ? $row->status->value : (string)$row->status;
                        [$slabel, $sclass] = $badgeMap[$sv] ?? [str_replace('_',' ',$sv), 'bg-slate-100 text-slate-700'];
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="px-4 py-4 whitespace-nowrap border-b border-gray-100">
                            <input type="checkbox" class="row-checkbox rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-400/30" value="{{ $row->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 font-mono font-semibold text-slate-700">{{ $row->application_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-600">{{ $row->institution?->name ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-700">{{ $row->applicant_first_name }} {{ $row->applicant_last_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sclass }}">{{ $slabel }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-500">{{ $row->created_at?->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.applications.show', $row) }}"
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    Detay
                                </a>
                                @can('update', $row)
                                    <a href="{{ route('admin.applications.edit', $row) }}"
                                        class="inline-flex items-center rounded-md bg-[#02E0FB] px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:opacity-90">
                                        Düzenle
                                    </a>
                                @endcan
                                @can('delete', $row)
                                    <button type="button"
                                        data-delete-url="{{ route('admin.applications.destroy', $row) }}"
                                        data-application-no="{{ $row->application_no }}"
                                        class="app-delete-btn inline-flex items-center rounded-md border border-red-100 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm transition hover:bg-red-100">
                                        Sil
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Kayıt bulunamadı.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($applications->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">
            {{ $applications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('refresh-btn')?.addEventListener('click', function () {
    window.location.reload();
});

document.querySelectorAll('.app-delete-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const url = this.dataset.deleteUrl;
        const no  = this.dataset.applicationNo;
        const row = this.closest('tr');

        Swal.fire({
            title: 'Başvuruyu Sil',
            html: `<p style="color:#94a3b8;font-size:.875rem"><strong style="color:#f1f5f9">${no}</strong> numaralı başvuruyu silmek istediğinize emin misiniz?<br><br>Bu işlem geri alınamaz.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#475569',
            background: '#0f172a',
            color: '#f1f5f9',
        }).then(result => {
            if (!result.isConfirmed) return;

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(res => res.ok ? res.json() : Promise.reject(res))
            .then(() => {
                row?.remove();
                Swal.fire({ title: 'Silindi!', text: `${no} başarıyla silindi.`, icon: 'success', confirmButtonColor: '#02AFC6', background: '#0f172a', color: '#f1f5f9' });
            })
            .catch(() => {
                Swal.fire({ title: 'Hata', text: 'Başvuru silinemedi. Lütfen tekrar deneyin.', icon: 'error', confirmButtonColor: '#DC2626', background: '#0f172a', color: '#f1f5f9' });
            });
        });
    });
});

// ── Toplu Silme ──
(() => {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const bulkBtn = document.getElementById('bulk-delete-btn');
    const bulkLabel = document.getElementById('bulk-delete-label');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function updateBulkBtn() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length > 0) {
            bulkBtn.classList.remove('hidden');
            bulkBtn.disabled = false;
            bulkLabel.textContent = `${checked.length} başvuruyu sil`;
        } else {
            bulkBtn.classList.add('hidden');
            bulkBtn.disabled = true;
        }
    }

    selectAll?.addEventListener('change', function () {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkBtn();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkBtn);
    });

    bulkBtn?.addEventListener('click', function () {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        const count = ids.length;

        Swal.fire({
            title: 'Toplu Silme',
            html: `<p style="color:#94a3b8;font-size:.875rem"><strong style="color:#f1f5f9">${count}</strong> başvuruyu silmek istediğinize emin misiniz?<br><br>Bu işlem geri alınamaz.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Hepsini Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#475569',
            background: '#0f172a',
            color: '#f1f5f9',
        }).then(result => {
            if (!result.isConfirmed) return;

            fetch('{{ route("admin.applications.bulk-destroy") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids }),
            })
            .then(res => res.ok ? res.json() : Promise.reject(res))
            .then(data => {
                checked.forEach(cb => cb.closest('tr')?.remove());
                selectAll.checked = false;
                updateBulkBtn();
                Swal.fire({ title: 'Silindi!', text: data.message || 'Başvurular silindi.', icon: 'success', confirmButtonColor: '#02AFC6', background: '#0f172a', color: '#f1f5f9' });
            })
            .catch(() => {
                Swal.fire({ title: 'Hata', text: 'Başvurular silinemedi.', icon: 'error', confirmButtonColor: '#DC2626', background: '#0f172a', color: '#f1f5f9' });
            });
        });
    });
})();
</script>
@endpush

@endsection
