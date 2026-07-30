@extends('layouts.admin')

@section('page-heading', 'Zemin Tipleri')

@section('content')
<div class="space-y-6">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Zemin / Yüzey Tipleri</h1>
            <p class="text-sm text-slate-500">Kazı başvurularında kullanılan yüzey tip ve birim fiyatlarını yönetin.</p>
        </div>
        <button
            type="button"
            onclick="openModal()"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#FA6001] to-[#e05500] px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-orange-500/20 hover:brightness-110 transition active:scale-95"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Yeni Zemin Tipi
        </button>
    </div>

    {{-- ── KPI Strip ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Toplam Tip</p>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ $surfaceTypes->count() }}</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Aktif</p>
            <p class="mt-1 text-2xl font-black text-emerald-700">{{ $surfaceTypes->where('active', true)->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pasif</p>
            <p class="mt-1 text-2xl font-black text-slate-700">{{ $surfaceTypes->where('active', false)->count() }}</p>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────── --}}
    <div class="w-full mt-8 overflow-x-auto shadow-xl rounded-2xl border border-gray-100 bg-white">
        {{-- Top accent --}}
        <div class="h-0.5 w-full bg-gradient-to-r from-[#02E0FB] via-[#FA6001] to-[#02E0FB]"></div>

        <table class="w-full table-fixed md:table-auto min-w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-100">
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Zemin Adı</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Birim Fiyat</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Renk</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Durum</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surfaceTypes as $st)
                    <tr class="border-b border-gray-50 hover:bg-slate-50/60 transition-colors {{ $st->active ? '' : 'opacity-55' }}">
                        <td class="px-5 py-3.5 font-semibold text-slate-800">
                            <div class="flex items-center gap-2">
                                @if($st->color_code)
                                    <span class="inline-block h-3.5 w-3.5 flex-shrink-0 rounded-full border border-slate-200 shadow-sm" style="background-color: {{ $st->color_code }}"></span>
                                @endif
                                {{ $st->name }}
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg">
                                {{ number_format((float) $st->price_per_m2, 2, ',', '.') }} ₺ / m²
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($st->color_code)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600">
                                    <span class="inline-block h-2.5 w-2.5 rounded-sm" style="background-color: {{ $st->color_code }}"></span>
                                    {{ $st->color_code }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($st->active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Pasif
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onclick="openModal({{ $st->id }}, @js($st->name), @js($st->price_per_m2), @js($st->color_code ?? ''), {{ $st->active ? 'true' : 'false' }})"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                                >
                                    Düzenle
                                </button>
                                <form method="POST" action="{{ route('admin.surface-types.destroy', $st) }}" class="inline"
                                      onsubmit="return confirm('{{ $st->name }} zemin tipini silmek istediğinize emin misiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg border border-red-100 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition">
                                        Sil
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">
                            Henüz zemin tipi tanımlanmamış. Yukarıdaki butona tıklayarak ekleyin.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
        </table>
    </div>

</div>

{{-- ── Add / Edit Modal ─────────────────────────────────────────────────── --}}
<div id="st-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm px-4" aria-modal="true">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 id="modal-title" class="text-base font-bold text-slate-900">Zemin Tipi Ekle</h2>
            <button type="button" onclick="closeModal()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        <form id="st-form" method="POST" action="{{ route('admin.surface-types.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div id="method-field"></div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="st-name">Zemin Adı <span class="text-red-500">*</span></label>
                <input id="st-name" type="text" name="name" required maxlength="100"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB]"
                    placeholder="Örn: Asfalt, Beton Parke, Ham Toprak">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="unit_price_mask">Birim Fiyat (TL / m²) <span class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <input id="unit_price_mask" type="text" inputmode="decimal" autocomplete="off" required
                        class="block w-full rounded-lg border-slate-300 pr-14 text-sm shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB]"
                        placeholder="0,00">
                    <input type="hidden" id="st-price-raw" name="price_per_m2">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm font-semibold text-slate-400">TL/m²</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="st-color">Renk Kodu <span class="text-slate-400 font-normal text-xs">(opsiyonel)</span></label>
                <div class="mt-1 flex gap-2">
                    <input id="st-color-picker" type="color" value="#02E0FB"
                        class="h-9 w-10 cursor-pointer rounded-lg border border-slate-300 p-0.5"
                        oninput="document.getElementById('st-color').value = this.value">
                    <input id="st-color" type="text" name="color_code" maxlength="7"
                        class="block flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB] font-mono"
                        placeholder="#RRGGBB"
                        oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('st-color-picker').value = this.value">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input id="st-active" type="checkbox" name="active" value="1" checked
                    class="h-4 w-4 rounded border-slate-300 accent-emerald-600">
                <label class="text-sm font-medium text-slate-700" for="st-active">Aktif (başvurularda görünsün)</label>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">
                    İptal
                </button>
                <button type="submit"
                    class="rounded-lg bg-gradient-to-r from-[#FA6001] to-[#e05500] px-5 py-2 text-sm font-semibold text-white hover:brightness-110 transition active:scale-95">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const stModal     = document.getElementById('st-modal');
const stForm      = document.getElementById('st-form');
const methodField = document.getElementById('method-field');
const storeUrl    = @json(route('admin.surface-types.store'));
const maskEl      = document.getElementById('unit_price_mask');
const rawEl       = document.getElementById('st-price-raw');

/* ── Para birimi maskeleme yardımcıları ─────────────────────── */
function formatTRL(val) {
    const n = parseFloat(String(val).replace(/\./g, '').replace(',', '.'));
    if (isNaN(n) || n < 0) return '';
    return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function rawFromMask(display) {
    return String(display).replace(/\./g, '').replace(',', '.');
}

maskEl.addEventListener('input', function () {
    /* Yalnızca rakam, virgül ve nokta kabul et */
    this.value  = this.value.replace(/[^\d,\.]/g, '');
    rawEl.value = rawFromMask(this.value);
});

maskEl.addEventListener('blur', function () {
    const n = parseFloat(rawFromMask(this.value));
    if (!isNaN(n) && n >= 0) {
        this.value  = formatTRL(n);
        rawEl.value = n.toFixed(2);
    } else {
        rawEl.value = '';
    }
});

stForm.addEventListener('submit', function (e) {
    const n = parseFloat(rawFromMask(maskEl.value));
    if (isNaN(n) || n < 0) {
        e.preventDefault();
        maskEl.classList.add('border-red-500');
        maskEl.focus();
        return;
    }
    rawEl.value  = n.toFixed(2);
    maskEl.value = formatTRL(n);
    maskEl.classList.remove('border-red-500');
});
/* ──────────────────────────────────────────────────────────── */

function openModal(id = null, name = '', price = '', color = '', active = true) {
    document.getElementById('st-name').value  = name;

    /* Fiyat maskeli göster */
    if (price !== '' && price !== null && price !== undefined && price !== false) {
        const n = parseFloat(price);
        maskEl.value  = isNaN(n) ? '' : formatTRL(n);
        rawEl.value   = isNaN(n) ? '' : n.toFixed(2);
    } else {
        maskEl.value  = '';
        rawEl.value   = '';
    }

    document.getElementById('st-color').value = color;
    const picker = document.getElementById('st-color-picker');
    if (color && /^#[0-9A-Fa-f]{6}$/.test(color)) picker.value = color;
    document.getElementById('st-active').checked = active;

    if (id) {
        document.getElementById('modal-title').textContent = 'Zemin Tipi Düzenle';
        stForm.action = storeUrl.replace('/surface-types', '/surface-types/' + id);
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
    } else {
        document.getElementById('modal-title').textContent = 'Zemin Tipi Ekle';
        stForm.action = storeUrl;
        methodField.innerHTML = '';
    }

    stModal.classList.remove('hidden');
    stModal.classList.add('flex');
    document.getElementById('st-name').focus();
}

function closeModal() {
    stModal.classList.add('hidden');
    stModal.classList.remove('flex');
}

stModal.addEventListener('click', (e) => {
    if (e.target === stModal) closeModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
</script>
@endpush
