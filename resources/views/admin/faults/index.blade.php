@extends('layouts.admin')
@section('page-heading', 'Toplu Arıza (Acil Kazı) Yönetimi')

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
        'cancelled'              => ['İptal Edildi',          'bg-rose-100 text-rose-700'],
    ];
    $filters = $filters ?? ['q' => '', 'status' => ''];
@endphp

<!-- Header -->
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Toplu Arıza (Acil Kazı) Yönetimi</h1>
        <p class="mt-0.5 text-sm text-slate-500">Sadece <span class="font-semibold text-red-600">Arıza / Acil Kazı</span> türündeki kayıtlar listelenir.</p>
    </div>
    <a href="{{ route('admin.applications.create') }}"
        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Yeni Arıza Kaydı Aç
    </a>
</div>

<!-- Aksiyon Barı (Toplu Tahakkuk İskeleti) -->
<form id="fault-bulk-form" method="POST" action="{{ route('admin.faults.bulk-tahakkuk') }}" class="mb-5">
    @csrf
    <div class="flex flex-wrap items-center gap-3 rounded-xl border-2 border-dashed border-red-200 bg-red-50/60 p-4 shadow-sm">
        <div class="flex-1 min-w-[220px]">
            <p class="text-sm font-semibold text-slate-800">📝 Seçili Arızaları Toplu Tahakkuka Dönüştür / Yer Formu Çıkar</p>
            <p class="text-xs text-slate-500 mt-0.5">Tablodan satırları işaretleyin; seçilen arızalar bu aksiyon ile toplu tahakkuka hazırlanır.</p>
        </div>
        <button type="submit" id="fault-bulk-submit"
            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-black">
            Toplu Tahakkuka Dönüştür
        </button>
    </div>

    <!-- Filtreler -->
    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="mb-1 block text-xs font-medium text-slate-500">Arama</label>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                    placeholder="No, ad soyad, adres…"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm placeholder-slate-400 focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/30">
            </div>
            <div class="min-w-[160px]">
                <label class="mb-1 block text-xs font-medium text-slate-500">Durum</label>
                <select name="status"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/30">
                    <option value="">Tüm Durumlar</option>
                    @foreach(\App\Enums\ApplicationStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>
                            {{ $badgeMap[$s->value][0] ?? $s->value }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($institutions->count() > 0)
            <div class="min-w-[180px]">
                <label class="mb-1 block text-xs font-medium text-slate-500">Kurum</label>
                <select name="institution_id"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/30">
                    <option value="">Tüm Kurumlar</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}" @selected($filters['institution_id'] === (string)$inst->id)>
                            {{ $inst->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-black">
                    Filtrele
                </button>
                <a href="{{ route('admin.faults.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                    Temizle
                </a>
            </div>
        </div>
    </div>
</form>

<!-- Tablo -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white">
            <thead>
                <tr>
                    <th class="bg-gray-50/50 px-4 py-4 text-left border-b border-gray-200 w-10">
                        <input type="checkbox" id="fault-select-all" class="rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-400/30">
                    </th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Başvuru No</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Kurum</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Başvuran</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">İşin Adı</th>
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
                            <input type="checkbox" name="ids[]" value="{{ $row->id }}"
                                class="fault-row-checkbox rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-400/30">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 font-mono font-semibold text-slate-700">{{ $row->application_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-600">{{\Illuminate\Support\Str::limit($row->institution?->name ?? '—', 20) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-700">{{\Illuminate\Support\Str::limit(trim($row->applicant_first_name . ' ' . $row->applicant_last_name), 20) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-700">{{\Illuminate\Support\Str::limit($row->work_type ?: '—', 24) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sclass }}">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                </span>
                                {{ $slabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-500">{{ $row->created_at?->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.applications.show', $row) }}"
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    Detay
                                </a>
                                @can('update', $row)
                                    <a href="{{ route('admin.applications.edit', $row) }}"
                                        class="inline-flex items-center rounded-md bg-[#FA6001] px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:opacity-90">
                                        Düzenle
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            Arıza / Acil Kazı kaydı bulunamadı.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applications->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">
            {{ $applications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
(() => {
    const selectAll = document.getElementById('fault-select-all');
    const checkboxes = document.querySelectorAll('.fault-row-checkbox');
    const submitBtn = document.getElementById('fault-bulk-submit');

    selectAll?.addEventListener('change', function () {
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    submitBtn?.addEventListener('click', function (e) {
        const checked = document.querySelectorAll('.fault-row-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Seçim Yapın',
                text: 'Toplu tahakkuka dönüştürmek için en az bir arıza kaydı seçmelisiniz.',
                icon: 'warning',
                confirmButtonColor: '#DC2626',
                background: '#0f172a',
                color: '#f1f5f9',
            });
            return;
        }
        const count = checked.length;
        Swal.fire({
            title: 'Toplu Tahakkuk',
            html: `<p style="color:#94a3b8;font-size:.875rem"><strong style="color:#f1f5f9">${count}</strong> arıza kaydı toplu tahakkuk motoruna gönderilecek.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, Gönder',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#475569',
            background: '#0f172a',
            color: '#f1f5f9',
        }).then(result => {
            if (!result.isConfirmed) e.preventDefault();
        });
    });
})();
</script>
@endpush

@endsection
