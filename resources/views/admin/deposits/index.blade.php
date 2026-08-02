@extends('layouts.admin')
@section('page-heading', 'Teminat & İadeler')

@section('content')
@php
    $tab = $tab ?? 'pending';
@endphp

<!-- Header -->
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Teminat &amp; İadeler</h1>
        <p class="mt-0.5 text-sm text-slate-500">100 gün kuralı + 2 yıl zaman aşımı motoru — teminat yönetimi ve iade takibi.</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.deposits.index', ['tab' => 'pending']) }}"
        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'pending' ? 'bg-amber-500 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
        ⏳ Teminatı İçeride Bekleyenler
        @if($applications->total() > 0 && $tab === 'pending')
            <span class="rounded-full bg-white/25 px-2 py-px text-xs font-bold">{{ $applications->total() }}</span>
        @endif
    </a>
    <a href="{{ route('admin.deposits.index', ['tab' => 'refunded']) }}"
        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition {{ $tab === 'refunded' ? 'bg-emerald-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
        ✅ İadesi Yapılanlar
        @if($applications->total() > 0 && $tab === 'refunded')
            <span class="rounded-full bg-white/25 px-2 py-px text-xs font-bold">{{ $applications->total() }}</span>
        @endif
    </a>
</div>

<!-- Tablo -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white">
            <thead>
                <tr>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Başvuru No</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Talep Sahibi</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Gün Sayacı</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Yatan Teminat (₺)</th>
                    <th class="bg-gray-50/50 px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    @php
                        // Teminat 100 gün sayacı RUHSATLANDIRMA tarihinden itibaren işler.
                        // Ruhsat henüz çıkmadıysa sayaç henüz başlamadı (100 gün kaldı).
                        $baseDate = $app->licensed_at;
                        $passedDays = $baseDate ? max(0, (int) abs(now()->diffInDays($baseDate))) : 0;
                        $remaining = max(0, 100 - $passedDays);

                        if ($passedDays < 100) {
                            $badgeClass = 'bg-amber-100 text-amber-700';
                            $badgeText = 'İadeye ' . $remaining . ' Gün Kaldı';
                        } elseif ($passedDays <= 730) {
                            $badgeClass = 'bg-emerald-100 text-emerald-700';
                            $badgeText = '✅ İadeye Uygun (Süre Doldu)';
                        } else {
                            $badgeClass = 'bg-red-100 text-red-700';
                            $badgeText = '❌ 2 Yıl Aşımı (Kuruma İrat)';
                        }
                        if (!$baseDate) {
                            $badgeText = 'İadeye 100 Gün Kaldı (Ruhsat Bekleniyor)';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 font-mono font-semibold text-slate-700">{{ $app->application_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-700">{{\Illuminate\Support\Str::limit(trim($app->applicant_first_name . ' ' . $app->applicant_last_name), 24) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap border-b border-gray-100">
                            <span class="badge inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $badgeText }}</span>
                            @if($app->deposit_status === 'refunded' && $app->deposit_refunded_at)
                                <span class="mt-1 block text-[10px] text-emerald-600">İade: {{ $app->deposit_refunded_at->format('d.m.Y') }}</span>
                            @elseif($app->deposit_status === 'irat')
                                <span class="mt-1 block text-[10px] text-red-500">Kuruma İrat</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100 text-slate-700 font-semibold">{{ number_format((float) $app->deposit_amount, 2, ',', '.') }} ₺</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm border-b border-gray-100">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    data-action="{{ route('admin.deposits.update', $app) }}"
                                    data-no="{{ $app->application_no }}"
                                    data-status="{{ $app->deposit_status ?? 'pending' }}"
                                    data-notes="{{ $app->deposit_refund_notes ?? '' }}"
                                    class="deposit-edit-btn inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-blue-700">
                                    📝 İşlem Yap / Düzenle
                                </button>
                                @if($app->deposit_status === 'refunded' && $app->deposit_refund_doc)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($app->deposit_refund_doc) }}" target="_blank"
                                        class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                        Dekont
                                    </a>
                                @endif
                                <a href="{{ route('admin.applications.show', $app) }}" target="_blank" title="Başvuruyu incele"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    İncele
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ $tab === 'pending' ? 'Bekleyen teminat kaydı bulunamadı.' : 'İadesi yapılan teminat kaydı bulunamadı.' }}
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

<!-- Teminat İşlem Modalı -->
<div id="deposit-edit-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60 p-4" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-lg bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-900">Teminat İşlem / Düzenle</h3>
            <button type="button" onclick="document.getElementById('deposit-edit-modal').classList.add('hidden')" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="deposit-edit-form" method="POST" enctype="multipart/form-data">
            @csrf
            <p class="mb-4 text-xs text-slate-500">Başvuru No: <strong id="deposit-edit-no" class="text-slate-800 font-mono"></strong></p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Teminat Durumu</label>
                <select name="deposit_status" id="deposit-edit-status" required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-200">
                    <option value="pending">Bekliyor</option>
                    <option value="refunded">İade Edildi / Ödendi</option>
                    <option value="irat">Kuruma İrat Kaydedildi (Süre Aşımı)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">İade Dekontu Yükle</label>
                <input type="file" name="refund_document" accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-[11px] text-slate-400">İade bankadan yapılınca dekont PDF/görsel olarak buraya yüklenir.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">İade Notu</label>
                <textarea name="refund_notes" id="deposit-edit-notes" rows="3"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-200"
                    placeholder="Yönetici açıklaması…"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('deposit-edit-modal').classList.add('hidden')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">Kaydet</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.deposit-edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('deposit-edit-form').action = this.dataset.action;
        document.getElementById('deposit-edit-no').textContent = this.dataset.no;
        document.getElementById('deposit-edit-status').value = this.dataset.status || 'pending';
        document.getElementById('deposit-edit-notes').value = this.dataset.notes || '';
        document.getElementById('deposit-edit-modal').classList.remove('hidden');
    });
});
</script>
@endpush

@endsection
