@extends('layouts.admin')

@section('title', 'Ruhsat Belgesi Ayarları')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-6">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-100 ring-1 ring-orange-200">
                        <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    Ruhsat Belgesi Ayarları
                </span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">Yalnızca <span class="font-semibold text-orange-500">Süper Admin</span> bu ekranı görür. Logo, imzalar ve mühür burada yüklenir; PDF'lere otomatik yansır.</p>
        </div>
        <span class="w-fit rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-orange-600 ring-1 ring-orange-200">SA Yalnız</span>
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

    <form method="POST" action="{{ route('admin.settings.permit.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- ── Kurum Bilgileri ──────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Kurum Kimliği
            </h2>

            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Kurum Adı --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Kurum / Belediye Adı</label>
                    <input type="text" name="institution_name" value="{{ old('institution_name', $settings->institution_name) }}"
                        placeholder="Örn: Kadıköy Belediyesi"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                </div>
                {{-- Kurum Adresi --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Kurum Adresi</label>
                    <input type="text" name="institution_address" value="{{ old('institution_address', $settings->institution_address) }}"
                        placeholder="Örn: Mühürdar Cad. No:1 Kadıköy/İstanbul"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                </div>
                {{-- Ana Daire Başkanlığı --}}
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ana Daire Başkanlığı Adı</label>
                    <input type="text" name="department_name" value="{{ old('department_name', $settings->department_name) }}"
                        placeholder="Örn: Fen İşleri Dairesi Başkanlığı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                    <p class="mt-1 text-[11px] text-slate-400">PDF başlığında "Fen İşleri Dairesi Başkanlığı" yerine kullanılır.</p>
                </div>

                {{-- Logo Upload --}}
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Kurum Logosu</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        {{-- Preview --}}
                        <div class="flex h-24 w-32 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-300 bg-slate-50" id="logoPreviewBox">
                            @if($settings->institution_logo_path && Storage::disk('public')->exists($settings->institution_logo_path))
                                <img id="logoPreview" src="{{ Storage::disk('public')->url($settings->institution_logo_path) }}"
                                    class="max-h-20 max-w-[120px] object-contain" alt="Logo">
                            @else
                                <span id="logoPreview" class="text-xs text-slate-400">Yüklü logo yok</span>
                            @endif
                        </div>
                        {{-- Drop Zone --}}
                        <div class="flex-1">
                            <div id="logoDropZone"
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-cyan-400/60 hover:bg-cyan-50/50"
                                onclick="document.getElementById('institution_logo').click()">
                                <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                <p class="text-xs text-slate-500">PNG, JPG, SVG — Maks 2 MB</p>
                                <p id="logoFileName" class="text-[11px] font-medium text-cyan-600 hidden"></p>
                            </div>
                            <input type="file" id="institution_logo" name="institution_logo"
                                accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="sr-only">
                            @if($settings->institution_logo_path)
                            <label class="mt-2 flex cursor-pointer items-center gap-1.5 text-[11px] text-rose-500 hover:text-rose-600">
                                <input type="checkbox" name="delete_logo" value="1" class="rounded"> Mevcut logoyu sil
                            </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Yetkili Müdür ────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Yetkili Müdür / Daire Başkanı
            </h2>
            <p class="mb-4 text-xs text-slate-400">PDF'de sağ altta ONAY imzası olarak görünür.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</label>
                    <input type="text" name="director_name" value="{{ old('director_name', $settings->director_name) }}"
                        placeholder="Örn: Ahmet Kaya"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Unvan / Görev</label>
                    <input type="text" name="director_title" value="{{ old('director_title', $settings->director_title) }}"
                        placeholder="Örn: Daire Başkanı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/20">
                </div>

                {{-- Signature --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">İmza Görseli</label>
                    <x-permit-image-upload
                        name="director_signature"
                        preview-id="signPreview"
                        :existing="$settings->director_signature_path"
                        delete-name="delete_signature"
                        hint="PNG (şeffaf arka plan önerilir) — Maks 2 MB" />
                </div>

                {{-- Stamp --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Mühür / Kaşe Görseli</label>
                    <x-permit-image-upload
                        name="municipality_stamp"
                        preview-id="stampPreview"
                        :existing="$settings->municipality_stamp_path"
                        delete-name="delete_stamp"
                        shape="round"
                        hint="PNG yuvarlak kaşe — Maks 2 MB" />
                </div>
            </div>
        </div>

        {{-- ── Tanzim Eden ──────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Tanzim Eden (Belgeyi Hazırlayan)
            </h2>
            <p class="mb-4 text-xs text-slate-400">PDF'de sol imza bloğunda gösterilir.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</label>
                    <input type="text" name="preparer_name" value="{{ old('preparer_name', $settings->preparer_name) }}"
                        placeholder="Örn: Mehmet Yılmaz"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Unvan / Görev</label>
                    <input type="text" name="preparer_title" value="{{ old('preparer_title', $settings->preparer_title) }}"
                        placeholder="Örn: Şube Müdürü"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-400/20">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">İmza Görseli</label>
                    <x-permit-image-upload
                        name="preparer_signature"
                        preview-id="preparerSignPreview"
                        :existing="$settings->preparer_signature_path"
                        delete-name="delete_preparer_signature"
                        hint="PNG (şeffaf arka plan önerilir) — Maks 2 MB" />
                </div>
            </div>
        </div>

        {{-- ── Onaylayan Yetkili ────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Onaylayan Yetkili
            </h2>
            <p class="mb-4 text-xs text-slate-400">PDF'de orta imza bloğunda gösterilir.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</label>
                    <input type="text" name="approver_name" value="{{ old('approver_name', $settings->approver_name) }}"
                        placeholder="Örn: Fatma Demir"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Unvan / Görev</label>
                    <input type="text" name="approver_title" value="{{ old('approver_title', $settings->approver_title) }}"
                        placeholder="Örn: Müdür Yardımcısı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/20">
                </div>
            </div>
        </div>

        {{-- ── Alt Onay Yetkilisi ────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Alt Onay Yetkilisi (İkinci İmza)
            </h2>
            <p class="mb-4 text-xs text-slate-400">PDF'de üçüncü imza bloğunda gösterilir. Boş bırakılabilir.</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Ad Soyad</label>
                    <input type="text" name="secondary_approver_name" value="{{ old('secondary_approver_name', $settings->secondary_approver_name) }}"
                        placeholder="İsteğe bağlı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Unvan / Görev</label>
                    <input type="text" name="secondary_approver_title" value="{{ old('secondary_approver_title', $settings->secondary_approver_title) }}"
                        placeholder="İsteğe bağlı"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20">
                </div>
            </div>
        </div>

        {{-- ── Belge Metinleri ──────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Belge Metinleri
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Geçerlilik Şartları Metni</label>
                    <textarea name="validity_agreement" rows="6"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400/20 font-mono"
                        placeholder="Bu ruhsat belgesi, ilgili yasa ve yönetmelikler çerçevesinde düzenlenmiştir...">{{ old('validity_agreement', $settings->validity_agreement) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400">PDF ruhsatın alt kısmında basılır.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Alt Bilgi Notu</label>
                    <input type="text" name="footer_note" value="{{ old('footer_note', $settings->footer_note) }}"
                        placeholder="Örn: Bu belge aslına uygundur. T.C. Kadıköy Belediyesi"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400/20">
                </div>
            </div>
        </div>

        {{-- ── Save ─────────────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-xs text-slate-500">Kaydedilen ayarlar tüm yeni PDF üretimlerine anında yansır.</p>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#FA6001] to-[#e65500] px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:from-[#ff6b0a] hover:to-[#FA6001] active:scale-95">
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
    function wireUpload(inputId, previewId, fileNameId, dropZoneId) {
        const input    = document.getElementById(inputId);
        const preview  = document.getElementById(previewId);
        const fileNameEl = document.getElementById(fileNameId);
        const dropZone = document.getElementById(dropZoneId);

        if (!input) return;

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (fileNameEl) {
                fileNameEl.textContent = file.name;
                fileNameEl.classList.remove('hidden');
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                if (preview && preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else if (preview) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'max-h-20 max-w-[120px] object-contain';
                    preview.replaceWith(img);
                }
            };
            reader.readAsDataURL(file);
        });

        if (dropZone) {
            dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-cyan-400/60'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-cyan-400/60'));
            dropZone.addEventListener('drop', e => {
                e.preventDefault();
                dropZone.classList.remove('border-cyan-400/60');
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    }

    wireUpload('institution_logo', 'logoPreview', 'logoFileName', 'logoDropZone');
})();
</script>
@endpush
