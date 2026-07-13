@extends('layouts.admin')

@section('title', 'Ön Kazı Belge Ayarları')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 ring-1 ring-cyan-200">
                        <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    Ön Kazı Belge Ayarları
                </span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">Ön kazı izin PDF'sinin tasarımını oluşturun. Bölümler ekleyin, sıralayın, içerikleri belirleyin.</p>
        </div>
        <span class="w-fit rounded-full bg-cyan-50 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-cyan-600 ring-1 ring-cyan-200">SA Yalnız</span>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.pre-excavation-permit.update') }}" enctype="multipart/form-data" id="settings-form">
        @csrf
        @method('PUT')

        {{-- General Settings --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Genel Ayarlar
            </h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Belge Başlığı</label>
                    <input type="text" name="title" value="{{ old('title', $settings->title) }}"
                        placeholder="Örn: ÖN KAZI İZİN BELGESİ"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Üst Başlık Metni</label>
                    <input type="text" name="header_text" value="{{ old('header_text', $settings->header_text) }}"
                        placeholder="Örn: T.C. Merkez Belediye Başkanlığı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Alt Bilgi / Şartlar Metni</label>
                    <textarea name="footer_text" rows="4"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20 font-mono"
                        placeholder="Ön kazı izni ile ilgili şartlar ve açıklamalar...">{{ old('footer_text', $settings->footer_text) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Logo, Signature, Stamp --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Logo, İmza & Mühür
            </h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Logo</label>
                    <x-permit-image-upload name="logo" preview-id="logoPreview"
                        :existing="$settings->logo_path" delete-name="delete_logo"
                        hint="PNG, SVG — Maks 2 MB" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">İmza</label>
                    <x-permit-image-upload name="signature" preview-id="signPreview"
                        :existing="$settings->signature_path" delete-name="delete_signature"
                        hint="PNG (şeffaf) — Maks 2 MB" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Mühür / Kaşe</label>
                    <x-permit-image-upload name="stamp" preview-id="stampPreview"
                        :existing="$settings->stamp_path" delete-name="delete_stamp" shape="round"
                        hint="PNG yuvarlak — Maks 2 MB" />
                </div>
            </div>
        </div>

        {{-- Approver Info --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Onaylayan Yetkili
            </h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</label>
                    <input type="text" name="approver_name" value="{{ old('approver_name', $settings->approver_name) }}"
                        placeholder="Örn: Ahmet Kaya"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Unvan / Görev</label>
                    <input type="text" name="approver_title" value="{{ old('approver_title', $settings->approver_title) }}"
                        placeholder="Örn: Daire Başkanı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/20">
                </div>
            </div>
        </div>

        {{-- PDF Section Designer --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    PDF Bölüm Tasarımcısı
                </h2>
                <button type="button" id="add-section-btn"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700 transition">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                    Bölüm Ekle
                </button>
            </div>
            <p class="mb-4 text-xs text-slate-400">PDF içinde göstermek istediğiniz bölümleri aşağıda düzenleyin. Her bölüm başlık, satırlar (label + value) ve/veya serbest metin içerebilir.</p>

            <div id="sections-container" class="space-y-4">
                {{-- Sections will be rendered here by JS --}}
            </div>

            <textarea name="sections" id="sections-json" class="sr-only">{{ old('sections', json_encode($settings->sections ?? [])) }}</textarea>

            <div id="no-sections-placeholder" class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">Henüz bölüm eklenmedi</p>
                <p class="mt-1 text-xs text-slate-400">"Bölüm Ekle" butonuna tıklayarak PDF tasarımını oluşturun.</p>
            </div>
        </div>

        {{-- Save --}}
        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-xs text-slate-500">Ayarlar kaydedildiğinde yeni PDF'ler otomatik bu tasarıma göre oluşur.</p>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:from-cyan-500 hover:to-sky-500 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                Ayarları Kaydet
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const container = document.getElementById('sections-container');
    const jsonInput = document.getElementById('sections-json');
    const placeholder = document.getElementById('no-sections-placeholder');
    const addBtn = document.getElementById('add-section-btn');

    let sections = [];

    try {
        const raw = jsonInput.value.trim();
        if (raw) sections = JSON.parse(raw);
    } catch (e) { sections = []; }

    if (!Array.isArray(sections)) sections = [];

    const fieldOptions = [
        { value: '', label: '— Metin Gir —' },
        { value: '{basvuru_no}', label: 'Başvuru No' },
        { value: '{kurum}', label: 'Kurum Adı' },
        { value: '{basyuran}', label: 'Başvuran Adı' },
        { value: '{kazı_sebebi}', label: 'Kazı Sebebi' },
        { value: '{calisma_turu}', label: 'Çalışma Türü' },
        { value: '{adres}', label: 'Adres' },
        { value: '{baslangic}', label: 'Başlangıç Tarihi' },
        { value: '{bitis}', label: 'Bitiş Tarihi' },
        { value: '{alan}', label: 'Alan (m²)' },
        { value: '{onay_tarihi}', label: 'Onay Tarihi' },
        { value: '{aciklama}', label: 'Açıklama' },
    ];

    function render() {
        container.innerHTML = '';
        placeholder.classList.toggle('hidden', sections.length > 0);

        sections.forEach((section, idx) => {
            const div = document.createElement('div');
            div.className = 'rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden';
            div.dataset.index = idx;

            div.innerHTML = `
                <div class="flex items-center justify-between bg-slate-50 px-4 py-3 border-b border-slate-200 cursor-move section-header">
                    <div class="flex items-center gap-3">
                        <span class="text-slate-400 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                        </span>
                        <span class="text-sm font-semibold text-slate-700 section-title-display">${section.title || 'Başlıksız Bölüm'}</span>
                        <span class="text-[10px] text-slate-400 bg-slate-100 rounded-full px-2 py-0.5">#${idx + 1}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" class="remove-section p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition" title="Bölümü Sil">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Bölüm Başlığı</label>
                            <input type="text" class="section-title w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-violet-400 focus:ring-1 focus:ring-violet-200"
                                value="${escapeHtml(section.title || '')}" placeholder="Örn: Kazı Bilgileri">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="button" class="add-row-btn inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                                Satır Ekle
                            </button>
                            <button type="button" class="toggle-content-btn inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                                ${section.content !== undefined ? 'Metni Kaldır' : 'Metin Ekle'}
                            </button>
                        </div>
                    </div>

                    <div class="content-area ${section.content !== undefined ? '' : 'hidden'}">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Serbest Metin</label>
                        <textarea class="section-content w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-violet-400 focus:ring-1 focus:ring-violet-200" rows="2"
                            placeholder="Örn: Bu belge ... amaçlı düzenlenmiştir.">${escapeHtml(section.content || '')}</textarea>
                    </div>

                    <div class="rows-area space-y-2">
                        ${(section.rows || []).map((row, ri) => `
                            <div class="flex items-center gap-2 row-item">
                                <span class="text-slate-400 cursor-move">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                </span>
                                <input type="text" class="row-label flex-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-violet-400 focus:ring-1 focus:ring-violet-200"
                                    value="${escapeHtml(row.label || '')}" placeholder="Etiket (örn: Kazı Derinliği)">
                                <div class="relative flex-1">
                                    <input type="text" class="row-value w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-violet-400 focus:ring-1 focus:ring-violet-200"
                                        value="${escapeHtml(row.value || '')}" placeholder="Değer veya alan seçin">
                                    <div class="absolute right-1 top-1/2 -translate-y-1/2">
                                        <select class="field-picker text-[10px] border-0 bg-transparent text-slate-400 cursor-pointer focus:outline-none">
                                            ${fieldOptions.map(o => `<option value="${o.value}" ${row.value === o.value ? 'selected' : ''}>${o.label}</option>`).join('')}
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="remove-row p-1 rounded text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            container.appendChild(div);
        });

        syncJson();
        bindEvents();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function syncJson() {
        const items = [];
        container.querySelectorAll('[data-index]').forEach(el => {
            const idx = parseInt(el.dataset.index);
            const title = el.querySelector('.section-title')?.value || '';
            const contentEl = el.querySelector('.section-content');
            const content = contentEl ? contentEl.value : undefined;
            const rows = [];
            el.querySelectorAll('.row-item').forEach(rowEl => {
                const label = rowEl.querySelector('.row-label')?.value || '';
                const value = rowEl.querySelector('.row-value')?.value || '';
                if (label || value) rows.push({ label, value });
            });

            const item = { title };
            if (rows.length > 0) item.rows = rows;
            if (content !== undefined && content !== '') item.content = content;
            items.push(item);
        });
        sections = items;
        jsonInput.value = JSON.stringify(sections);
    }

    function bindEvents() {
        // Section title change
        container.querySelectorAll('.section-title').forEach(el => {
            el.addEventListener('input', function () {
                const header = this.closest('[data-index]').querySelector('.section-title-display');
                header.textContent = this.value || 'Başlıksız Bölüm';
                syncJson();
            });
        });

        // Remove section
        container.querySelectorAll('.remove-section').forEach(el => {
            el.addEventListener('click', function () {
                if (!confirm('Bu bölümü silmek istediğinize emin misiniz?')) return;
                const parent = this.closest('[data-index]');
                parent.remove();
                reindex();
                syncJson();
            });
        });

        // Add row
        container.querySelectorAll('.add-row-btn').forEach(el => {
            el.addEventListener('click', function () {
                const rowsArea = this.closest('[data-index]').querySelector('.rows-area');
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2 row-item';
                row.innerHTML = `
                    <span class="text-slate-400 cursor-move">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                    </span>
                    <input type="text" class="row-label flex-1 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-violet-400 focus:ring-1 focus:ring-violet-200" placeholder="Etiket">
                    <div class="relative flex-1">
                        <input type="text" class="row-value w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs focus:border-violet-400 focus:ring-1 focus:ring-violet-200" placeholder="Değer veya alan seçin">
                        <div class="absolute right-1 top-1/2 -translate-y-1/2">
                            <select class="field-picker text-[10px] border-0 bg-transparent text-slate-400 cursor-pointer focus:outline-none">
                                ${fieldOptions.map(o => `<option value="${o.value}">${o.label}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <button type="button" class="remove-row p-1 rounded text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                `;
                rowsArea.appendChild(row);
                bindRowEvents(row);
                syncJson();
            });
        });

        // Toggle content textarea
        container.querySelectorAll('.toggle-content-btn').forEach(el => {
            el.addEventListener('click', function () {
                const contentArea = this.closest('[data-index]').querySelector('.content-area');
                const isHidden = contentArea.classList.contains('hidden');
                contentArea.classList.toggle('hidden');
                this.innerHTML = isHidden
                    ? '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg> Metni Kaldır'
                    : '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg> Metin Ekle';

                // If showing, ensure the section data has content field
                if (!isHidden) {
                    const textarea = contentArea.querySelector('.section-content');
                    if (!textarea.value) textarea.value = ' ';
                }
                syncJson();
            });
        });

        // Content textarea change
        container.querySelectorAll('.section-content').forEach(el => {
            el.addEventListener('input', syncJson);
        });

        // Bind row events for existing rows
        container.querySelectorAll('.row-item').forEach(el => bindRowEvents(el));
    }

    function bindRowEvents(row) {
        row.querySelectorAll('.row-label, .row-value').forEach(el => {
            el.addEventListener('input', syncJson);
        });

        row.querySelector('.field-picker')?.addEventListener('change', function () {
            const valueInput = this.closest('.relative').querySelector('.row-value');
            valueInput.value = this.value;
            syncJson();
        });

        row.querySelector('.remove-row')?.addEventListener('click', function () {
            this.closest('.row-item').remove();
            syncJson();
        });
    }

    function reindex() {
        container.querySelectorAll('[data-index]').forEach((el, i) => {
            el.dataset.index = i;
            const num = el.querySelector('.section-header .bg-slate-100');
            if (num) num.textContent = '#' + (i + 1);
        });
    }

    // Add new section
    addBtn.addEventListener('click', function () {
        placeholder.classList.add('hidden');
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden';
        const idx = container.children.length;
        div.dataset.index = idx;

        div.innerHTML = `
            <div class="flex items-center justify-between bg-slate-50 px-4 py-3 border-b border-slate-200 cursor-move section-header">
                <div class="flex items-center gap-3">
                    <span class="text-slate-400 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                    </span>
                    <span class="text-sm font-semibold text-slate-700 section-title-display">Yeni Bölüm</span>
                    <span class="text-[10px] text-slate-400 bg-slate-100 rounded-full px-2 py-0.5">#${idx + 1}</span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="remove-section p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition" title="Bölümü Sil">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Bölüm Başlığı</label>
                        <input type="text" class="section-title w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-violet-400 focus:ring-1 focus:ring-violet-200"
                            placeholder="Örn: Kazı Bilgileri">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" class="add-row-btn inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                            Satır Ekle
                        </button>
                        <button type="button" class="toggle-content-btn inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                            Metin Ekle
                        </button>
                    </div>
                </div>
                <div class="content-area hidden">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Serbest Metin</label>
                    <textarea class="section-content w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-violet-400 focus:ring-1 focus:ring-violet-200" rows="2"
                        placeholder="Örn: Bu belge ... amaçlı düzenlenmiştir."></textarea>
                </div>
                <div class="rows-area space-y-2"></div>
            </div>
        `;

        container.appendChild(div);
        syncJson();
        bindEvents();
    });

    // Initial render
    render();
})();
</script>
@endpush
