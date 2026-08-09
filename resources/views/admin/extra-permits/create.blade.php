@extends('layouts.app')

@section('title', 'Ek Ruhsat Oluştur - ' . $application->application_no)

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.applications.show', $application) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Başvuruya Dön
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Ek Ruhsat Oluştur</h1>
            <p class="mt-1 text-sm text-slate-500">Başvuru: {{ $application->application_no }}</p>
        </div>

        <form method="POST" action="{{ route('admin.extra-permits.store', $application) }}">
            @csrf

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ek Kazı Metrajı (metre)</label>
                    <input type="number" step="0.01" min="0.01" name="ek_metraj_m" required
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                           placeholder="Örn: 3.50">
                    <p class="mt-1 text-xs text-slate-500">Mevcut kazıya ek olarak yapılacak kazı miktarı (metre cinsinden).</p>
                    @error('ek_metraj_m') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-sm font-medium text-slate-700">Zemin Satırları</label>
                        <button type="button" onclick="addSurfaceLine()"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            + Satır Ekle
                        </button>
                    </div>
                    <p class="mb-3 text-xs text-slate-500">Ek kazı için zemin tipi, genişlik, uzunluk ve miktarı girin.</p>
                    <div id="surface-lines-container">
                        <div class="surface-line-row mb-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="grid grid-cols-5 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Zemin Tipi</label>
                                    <select name="surface_lines[0][surface_type_id]" required
                                            class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                                            onchange="updateLineAmount(this)">
                                        <option value="">Seçiniz</option>
                                        @foreach($surfaceTypes as $st)
                                        <option value="{{ $st->id }}" data-price="{{ $st->price_per_m2 }}">{{ $st->name }} ({{ number_format($st->price_per_m2, 2) }} ₺/m²)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Genişlik (m)</label>
                                    <input type="number" step="0.01" min="0.01" name="surface_lines[0][width_m]" required
                                           class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                                           oninput="updateLineAmount(this)">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Uzunluk (m)</label>
                                    <input type="number" step="0.01" min="0.01" name="surface_lines[0][length_m]" required
                                           class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                                           oninput="updateLineAmount(this)">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Miktar (m²)</label>
                                    <input type="number" step="0.01" min="0" name="surface_lines[0][quantity]" required
                                           class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                                           oninput="updateLineAmount(this)">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Tutar (₺)</label>
                                    <input type="number" step="0.01" name="surface_lines[0][amount]" readonly
                                           class="block w-full rounded-lg border border-slate-200 bg-slate-100 px-2 py-2 text-xs text-slate-500">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Adres (opsiyonel)</label>
                                <input type="text" name="surface_lines[0][address]"
                                       class="block w-full rounded-lg border border-slate-300 px-2 py-2 text-xs focus:border-cyan-400 focus:ring-1 focus:ring-cyan-200"
                                       placeholder="Kazı yapılacak adres">
                            </div>
                            <button type="button" onclick="this.parentElement.remove()"
                                    class="mt-2 text-xs text-red-500 hover:text-red-700">Bu satırı kaldır</button>
                        </div>
                    </div>
                    @error('surface_lines') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <a href="{{ route('admin.applications.show', $application) }}"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">İptal</a>
                    <button type="submit" class="rounded-lg bg-cyan-700 px-6 py-2 text-sm font-medium text-white hover:bg-cyan-800">Ek Ruhsat Oluştur</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let lineIndex = 1;
function addSurfaceLine() {
    const container = document.getElementById('surface-lines-container');
    const template = container.children[0].cloneNode(true);
    const inputs = template.querySelectorAll('[name]');
    inputs.forEach(el => {
        const name = el.getAttribute('name').replace(/\[\d+\]/, `[${lineIndex}]`);
        el.setAttribute('name', name);
        if (el.tagName === 'INPUT') {
            if (el.hasAttribute('readonly')) {
                el.value = '';
            } else {
                el.value = '';
            }
        } else if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        }
    });
    container.appendChild(template);
    lineIndex++;
}

function updateLineAmount(el) {
    const row = el.closest('.surface-line-row');
    const select = row.querySelector('[name$="[surface_type_id]"]');
    const width = parseFloat(row.querySelector('[name$="[width_m]"]').value) || 0;
    const length = parseFloat(row.querySelector('[name$="[length_m]"]').value) || 0;
    const quantity = parseFloat(row.querySelector('[name$="[quantity]"]').value) || 0;
    const amountField = row.querySelector('[name$="[amount]"]');
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option?.dataset?.price) || 0;
    amountField.value = (quantity * price).toFixed(2);
}
</script>
@endpush
@endsection
