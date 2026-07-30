@props([
    'name',
    'previewId',
    'existing'    => null,
    'deleteName'  => null,
    'hint'        => 'PNG, JPG — Maks 2 MB',
    'accept'      => 'image/png,image/jpeg,image/jpg',
    'shape'       => 'rect',   // 'rect' | 'round'
])

@php
    $inputId    = 'upload_' . $name;
    $dropId     = 'drop_' . $name;
    $fileNameId = 'fn_' . $name;
    $existingUrl = ($existing && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing))
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($existing)
        : null;
    $shapeClass = $shape === 'round' ? 'rounded-full' : 'rounded-xl';
    $previewSize = $shape === 'round' ? 'h-20 w-20' : 'h-20 w-44';
@endphp

{{-- Preview box --}}
<div class="mt-1 flex items-center justify-center overflow-hidden border border-slate-300 bg-slate-50 {{ $previewSize }} {{ $shapeClass }}">
    @if($existingUrl)
        <img id="{{ $previewId }}"
             src="{{ $existingUrl }}"
             class="{{ $shape === 'round' ? 'h-full w-full rounded-full object-contain' : 'max-h-16 max-w-[160px] object-contain' }}"
             alt="Önizleme">
    @else
        <span id="{{ $previewId }}" class="text-[10px] text-slate-400 text-center px-2 leading-tight">Görsel yüklü değil</span>
    @endif
</div>

{{-- Drop zone --}}
<div id="{{ $dropId }}"
     class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center transition hover:border-cyan-400/60 hover:bg-cyan-50/50"
     onclick="document.getElementById('{{ $inputId }}').click()"
     ondragover="event.preventDefault();this.classList.add('border-cyan-400/70')"
     ondragleave="this.classList.remove('border-cyan-400/70')"
     ondrop="event.preventDefault();this.classList.remove('border-cyan-400/70');__piu_drop('{{ $inputId }}',event)">
    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
    </svg>
    <p id="{{ $fileNameId }}" class="text-xs text-slate-500">{{ $hint }}</p>
</div>

{{-- Hidden file input --}}
<input type="file"
       id="{{ $inputId }}"
       name="{{ $name }}"
       accept="{{ $accept }}"
       class="sr-only"
       onchange="__piu_change('{{ $inputId }}','{{ $previewId }}','{{ $fileNameId }}')">

{{-- Delete checkbox --}}
@if($deleteName && $existingUrl)
<label class="mt-1.5 flex cursor-pointer items-center gap-1.5 text-[11px] text-rose-500 hover:text-rose-600">
    <input type="checkbox" name="{{ $deleteName }}" value="1" class="rounded">
    Mevcut görseli sil
</label>
@endif

{{-- Shared JS helpers (idempotent, only injected once) --}}
@once
@push('scripts')
<script>
function __piu_change(inputId, previewId, fileNameId) {
    const input = document.getElementById(inputId);
    const file  = input && input.files[0];
    if (!file) return;
    const label = document.getElementById(fileNameId);
    if (label) label.textContent = file.name;
    const reader = new FileReader();
    reader.onload = function(e) {
        const prev = document.getElementById(previewId);
        if (!prev) return;
        if (prev.tagName === 'IMG') {
            prev.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = prev.className || 'max-h-16 max-w-[160px] object-contain';
            prev.replaceWith(img);
        }
    };
    reader.readAsDataURL(file);
}
function __piu_drop(inputId, evt) {
    const file = evt.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById(inputId);
    if (input) {
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    }
}
</script>
@endpush
@endonce
