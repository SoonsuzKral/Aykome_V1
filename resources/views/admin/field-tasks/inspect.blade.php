@extends('layouts.admin')

@section('page-heading', 'Saha Kontrol Paneli')

@push('head')
<style>
/* Mobile-first touch targets */
.stage-btn { min-height: 72px; touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
.stage-card { transition: all .2s ease; }
.stage-card.active { box-shadow: 0 0 0 2px #02E0FB, 0 20px 40px -10px rgba(2,224,251,0.3); }
.upload-zone { border: 2px dashed #334155; transition: border-color .2s; }
.upload-zone:hover, .upload-zone.drag-over { border-color: #02E0FB; background: rgba(2,224,251,0.05); }
.photo-thumb { object-fit: cover; aspect-ratio: 1; }
</style>
@endpush

@section('content')
<div class="mx-auto max-w-2xl space-y-5">

    {{-- Başvuru Bilgi Şeridi --}}
    <div class="rounded-2xl border border-cyan-400/20 bg-slate-900 px-4 py-3 shadow-md">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-cyan-300/70">Saha Görevi</p>
                <p class="mt-0.5 text-lg font-black text-white">{{ $application?->application_no ?? '—' }}</p>
                <p class="text-xs text-slate-400">{{ $application?->address_text ?? '—' }}</p>
            </div>
            <div class="flex flex-col items-end gap-1">
                @php
                    $ts = $fieldTask->status;
                    $tsBadge = match($ts) {
                        'in_progress' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                        'completed'   => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                        default       => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                    };
                    $tsLabel = match($ts) {
                        'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı', default => 'Beklemede',
                    };
                @endphp
                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $tsBadge }}">{{ $tsLabel }}</span>
                @if($fieldTask->due_date)
                    <span class="text-xs text-slate-500">Termin: {{ $fieldTask->due_date->format('d.m.Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- 3 Aşama Seçici --}}
    <div class="grid grid-cols-3 gap-3">
        @php
            $stageConfig = [
                1 => ['label' => 'Kazı Öncesi',     'icon' => '🔍', 'color' => 'from-sky-600 to-sky-700',     'ring' => 'ring-sky-400',     'key' => 'pre_dig'],
                2 => ['label' => 'Kazı Sonrası',    'icon' => '⛏️',  'color' => 'from-amber-600 to-amber-700', 'ring' => 'ring-amber-400',   'key' => 'post_dig'],
                3 => ['label' => 'Zemin Onarım',    'icon' => '🏗️',  'color' => 'from-emerald-600 to-emerald-700', 'ring' => 'ring-emerald-400', 'key' => 'post_repair'],
            ];
        @endphp
        @foreach($stageConfig as $num => $cfg)
            @php
                $isDone   = $fieldTask->{"stage_{$num}_status"} === 'done';
                $isActive = request()->get('stage', 1) == $num;
            @endphp
            <a href="{{ route('admin.field-tasks.inspect', ['fieldTask' => $fieldTask->id, 'stage' => $num]) }}"
               class="stage-btn stage-card flex flex-col items-center justify-center rounded-2xl border p-3 text-center transition
                      {{ $isDone
                            ? 'border-emerald-500/50 bg-emerald-500/10'
                            : ($isActive ? 'border-[#02E0FB]/50 bg-[#02E0FB]/10 active' : 'border-slate-700 bg-slate-800/60 hover:border-slate-500') }}">
                <span class="text-2xl leading-none">{{ $isDone ? '✅' : $cfg['icon'] }}</span>
                <span class="mt-1.5 text-xs font-bold leading-tight {{ $isDone ? 'text-emerald-300' : ($isActive ? 'text-[#02E0FB]' : 'text-slate-300') }}">
                    {{ $cfg['label'] }}
                </span>
                <span class="mt-1 text-[10px] {{ $isDone ? 'text-emerald-400' : 'text-slate-500' }}">
                    {{ $isDone ? 'Tamam' : 'Bekliyor' }}
                </span>
            </a>
        @endforeach
    </div>

    @php
        $activeStage = (int) request()->get('stage', 1);
        $stageCfg    = $stageConfig[$activeStage];
        $stageStatus = $fieldTask->{"stage_{$activeStage}_status"};
        $stageNotes  = $fieldTask->{"stage_{$activeStage}_notes"};
        $stageTime   = $fieldTask->{"stage_{$activeStage}_inspected_at"};
        $stageMedia  = $mediaByStep[$stageCfg['key']] ?? collect();
    @endphp

    {{-- Aktif Aşama Kartı --}}
    <div class="rounded-3xl border border-slate-700/60 bg-slate-900/90 p-5 shadow-xl backdrop-blur-xl">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">{{ $stageCfg['icon'] }}</span>
                <div>
                    <h2 class="text-base font-bold text-white">Aşama {{ $activeStage }} — {{ $stageCfg['label'] }}</h2>
                    @if($stageTime)
                        <p class="text-xs text-slate-500">Tamamlandı: {{ $stageTime->format('d.m.Y H:i') }}</p>
                    @endif
                </div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold
                {{ $stageStatus === 'done' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-700 text-slate-400' }}">
                {{ $stageStatus === 'done' ? 'Tamamlandı' : 'Beklemede' }}
            </span>
        </div>

        {{-- Durum Güncelleme Formu --}}
        @if($isAssignee || auth()->user()->hasRole(['super-admin','municipality-admin','municipality-staff']))
        <form method="POST" action="{{ route('admin.field-tasks.stage.update', $fieldTask) }}">
            @csrf
            <input type="hidden" name="stage" value="{{ $activeStage }}">
            <input type="hidden" name="status" id="stageStatusInput" value="{{ $stageStatus }}">

            <div class="mb-4">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">Kontrol Notu</label>
                <textarea name="notes" rows="3" maxlength="2000"
                    class="w-full resize-none rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]/30"
                    placeholder="Bu aşama için gözlem ve notlarınızı yazın…">{{ $stageNotes }}</textarea>
            </div>

            {{-- Büyük Durum Butonları --}}
            <div class="grid grid-cols-2 gap-3">
                <button type="submit" onclick="document.getElementById('stageStatusInput').value='pending'"
                    class="stage-btn flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-amber-500/40 bg-amber-500/10 py-4 text-sm font-bold text-amber-300 transition hover:bg-amber-500/20 active:scale-95
                        {{ $stageStatus === 'pending' ? 'ring-2 ring-amber-400' : '' }}">
                    <span class="text-3xl">⏳</span>
                    <span>Beklemede</span>
                </button>
                <button type="submit" onclick="document.getElementById('stageStatusInput').value='done'"
                    class="stage-btn flex flex-col items-center justify-center gap-1 rounded-2xl border-2 border-emerald-500/40 bg-emerald-500/10 py-4 text-sm font-bold text-emerald-300 transition hover:bg-emerald-500/20 active:scale-95
                        {{ $stageStatus === 'done' ? 'ring-2 ring-emerald-400' : '' }}">
                    <span class="text-3xl">✅</span>
                    <span>Tamamlandı</span>
                </button>
            </div>
        </form>
        @endif

        {{-- Fotoğraf Yükleme --}}
        <div class="mt-6">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Fotoğraflar ({{ $stageMedia->count() }})</h3>

            @if($stageMedia->isNotEmpty())
                <div class="mb-4 grid grid-cols-3 gap-2">
                    @foreach($stageMedia as $media)
                        <div class="group relative rounded-xl overflow-hidden border border-slate-700">
                            <img src="{{ Storage::disk('public')->url($media->image_path) }}"
                                 alt="{{ $media->caption ?? '' }}"
                                 class="photo-thumb w-full rounded-xl object-cover">
                            @if($media->caption)
                                <div class="absolute inset-x-0 bottom-0 bg-black/60 px-2 py-1 text-[10px] text-white opacity-0 group-hover:opacity-100 transition">
                                    {{ $media->caption }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Fotoğraf Yükleme Formu --}}
            <form method="POST" action="{{ route('admin.field-tasks.media.store', $fieldTask) }}"
                  enctype="multipart/form-data" id="photoForm">
                @csrf
                <input type="hidden" name="step" value="{{ $stageCfg['key'] }}">

                <div id="uploadZone" class="upload-zone cursor-pointer rounded-2xl p-5 text-center"
                     onclick="document.getElementById('photoInput').click()"
                     ondragover="event.preventDefault();this.classList.add('drag-over')"
                     ondragleave="this.classList.remove('drag-over')"
                     ondrop="handleDrop(event)">
                    <input type="file" id="photoInput" name="photo" accept="image/*" capture="environment"
                           class="hidden" onchange="previewPhoto(this)">
                    <div id="uploadPlaceholder">
                        <span class="text-4xl">📷</span>
                        <p class="mt-2 text-sm font-medium text-slate-300">Fotoğraf ekle veya kameradan çek</p>
                        <p class="text-xs text-slate-500">PNG, JPG, WebP — Maks. 10 MB</p>
                    </div>
                    <img id="photoPreview" src="" alt="" class="hidden mx-auto max-h-48 rounded-xl object-contain mt-2">
                </div>

                <input type="text" name="caption" maxlength="500"
                    class="mt-3 w-full rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-[#02E0FB] focus:outline-none"
                    placeholder="Fotoğraf açıklaması (isteğe bağlı)">

                <button type="submit" id="uploadBtn"
                    class="mt-3 hidden w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 py-4 text-base font-bold text-white shadow-lg transition hover:from-emerald-400 hover:to-emerald-500 active:scale-[0.98]">
                    📸 Fotoğraf Çek ve Yükle
                </button>
            </form>
        </div>
    </div>

    {{-- Görev Özet Kartı --}}
    <div class="rounded-2xl border border-slate-700/50 bg-slate-800/50 p-4 text-sm">
        <h3 class="mb-3 text-xs font-bold uppercase tracking-widest text-slate-500">Genel Görev Durumu</h3>
        <div class="space-y-2">
            @foreach($stageConfig as $num => $cfg)
                @php $isDone = $fieldTask->{"stage_{$num}_status"} === 'done'; @endphp
                <div class="flex items-center justify-between rounded-xl {{ $isDone ? 'bg-emerald-500/10' : 'bg-slate-900/60' }} px-4 py-2.5">
                    <span class="flex items-center gap-2 text-sm {{ $isDone ? 'text-emerald-300' : 'text-slate-400' }}">
                        <span>{{ $cfg['icon'] }}</span>
                        {{ $cfg['label'] }}
                    </span>
                    <span class="{{ $isDone ? 'text-emerald-400' : 'text-slate-600' }} text-xs font-bold">
                        {{ $isDone ? '✓ Tamam' : '○ Bekliyor' }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end">
            <a href="{{ route('admin.field-tasks.show', $fieldTask) }}"
               class="text-xs font-medium text-cyan-400 hover:underline">
                ← Tam görev detayına git
            </a>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('photoPreview').src = e.target.result;
        document.getElementById('photoPreview').classList.remove('hidden');
        document.getElementById('uploadPlaceholder').classList.add('hidden');
        document.getElementById('uploadBtn').classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const inp = document.getElementById('photoInput');
    inp.files = dt.files;
    previewPhoto(inp);
}
</script>
@endpush
