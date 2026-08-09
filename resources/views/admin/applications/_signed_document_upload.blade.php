@php
    $moduleDocs = $application->module_documents ?? [];
    $docData = $moduleDocs[$module] ?? [];

    // Partial içindeki MAVİ "E-İmza ile İmzala" butonunu gizlemek için çağıran taraf
    // 'showEImza' => false gönderir (ör. ruhsat İşlem Tabı'nda yeşil E-imza linki zaten var).
    $showEImza = $showEImza ?? true;

    // GÖREV 2: "Kurum İmzaladı / Belediye İmzaladı" Görüntüle satırları, modülün
    // eş anlamlısı olan *_signed anahtarına (alt kurum işlem tabı) ÇİFT YÖNLÜ bakar.
    // Örn: belediye 'metraj' anahtarına yükler, alt kurum 'metraj_signed' anahtarına yükler;
    // hangi tarafta açılırsa açılsın (metraj veya metraj_signed) diğer tarafın imzalı nüshası
    // Görüntüle linkiyle görünür kalmalıdır — tek yönlü değil, her iki anahtar taranır.
    $baseKey = str_replace(['_signed', '_imzali', '_teslim'], '', $module);
    // E-İmza normalize haritası: on_kazi_signed → pre_permit (ön kazı imzası pre_permit altına yazılır)
    $baseKey = $baseKey === 'on_kazi' ? 'pre_permit' : $baseKey;
    $signedKey = $module . '_signed';
    // Önce eş anahtarı bul; modül hem '_signed' hem düz isimle aranır.
    $syncKey = ($baseKey !== $module && isset($moduleDocs[$baseKey])) ? $baseKey
        : (isset($moduleDocs[$signedKey]) ? $signedKey : null);
    $syncData = $syncKey ? $moduleDocs[$syncKey] : null;

    $belediyePath = $docData['belediye_path'] ?? ($syncData['belediye_path'] ?? null);
    $kurumPath = $docData['kurum_path'] ?? ($syncData['kurum_path'] ?? null);
    $hasBelediye = !empty($belediyePath);
    $hasKurum = !empty($kurumPath);
    $eImzaDone = !empty($docData['e_imza']['durum'] ?? null) || !empty($syncData['e_imza']['durum'] ?? null);
    $eImzaTarih = $docData['e_imza']['tarih'] ?? ($syncData['e_imza']['tarih'] ?? '');
    $status = $docData['status'] ?? null;
    $uniqId = 'sdoc-' . $module . '-' . $application->id;

    // İmzalı dosya (file swap): imzalı varsa O GÖSTERİLİR, saf hali değil
    // Anahtar hangi tarafta açılırsa açılsın, imzalı/signed dosya ana modül anahtarında da aranır.
    $signedFile = $application->moduleSignedPath($module)
        ?? ($syncData && $syncKey ? $application->moduleSignedPath($syncKey) : null);
    $viewUrl = $signedFile
        ? route('admin.applications.module-document', [$application->id, $module])
        : null;

    $belediyeUrl = $docData['belediye_url'] ?? ($syncData['belediye_url'] ?? null);
    if (!$belediyeUrl && $belediyePath && preg_match('#e-imza/([^/]+)/#', $belediyePath, $m)) {
        $belediyeUrl = route('e-imza.indir', ['transactionId' => $m[1]], false);
    }
    $kurumUrl = $docData['kurum_url'] ?? ($syncData['kurum_url'] ?? null);
    if (!$kurumUrl && $kurumPath && preg_match('#e-imza/([^/]+)/#', $kurumPath, $m)) {
        $kurumUrl = route('e-imza.indir', ['transactionId' => $m[1]], false);
    }
@endphp
<div class="mt-2 rounded-lg border border-slate-200 bg-slate-50/50 p-2">
    <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>

    @if($eImzaDone || $signedFile)
    <div class="mb-1.5 flex items-center gap-2 rounded-lg px-2 py-2"
         style="background:repeating-linear-gradient(90deg,#047857 0 3px,#059669 3px 6px);">
        <span class="rounded-md bg-white px-2 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-700 shadow-sm">🟢 E-İmzalandı</span>
        @if($viewUrl)
        <a href="{{ $viewUrl }}" target="_blank" class="ml-auto rounded-md bg-white px-3 py-1 text-[11px] font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">Görüntüle</a>
        @endif
    </div>
    @endif

    @if($hasBelediye)
    <div class="mb-1 flex items-center gap-1.5 text-[10px] text-slate-600">
        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Belediye imzaladı</span>
        <a href="{{ $belediyeUrl ?: \Illuminate\Support\Facades\Storage::disk('public')->url($belediyePath) }}" target="_blank" class="ml-auto font-medium text-cyan-700 hover:underline">📄 Görüntüle</a>
    </div>
    @endif
    @if($hasKurum)
    <div class="mb-1 flex items-center gap-1.5 text-[10px] text-slate-600">
        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Kurum imzaladı</span>
        <a href="{{ $kurumUrl ?: \Illuminate\Support\Facades\Storage::disk('public')->url($kurumPath) }}" target="_blank" class="ml-auto font-medium text-cyan-700 hover:underline">📄 Görüntüle</a>
    </div>
    @endif

    @if($eImzaDone)
    <div class="mb-1 flex items-center gap-1.5 text-[10px] text-emerald-600">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>E-İmza tamamlandı</span>
        <span class="ml-auto text-[9px] text-slate-400">{{ $eImzaTarih }}</span>
    </div>
    @endif

    @if($can['update'] ?? false)
    <div class="signed-doc-upload mt-2" data-module="{{ $module }}" data-app-id="{{ $application->id }}">
        <div class="flex flex-col gap-2 w-full">
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="sdoc-file block w-full text-sm text-slate-600 border border-slate-300 rounded-lg cursor-pointer bg-slate-50 file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors shadow-sm" required>
            <button type="button" class="sdoc-submit w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm text-[13px] md:text-sm transition duration-200"
                    style="background-color:#2563eb !important;color:#ffffff !important;">
                ⬆️ İmzalı Nüshayı Yükle ve Gönder
            </button>
        </div>
        <div class="sdoc-status hidden mt-1.5 text-xs text-emerald-600 font-medium">Yükleniyor...</div>
    </div>
    @endif

    @if(config('e-imza.enabled') && !$eImzaDone && ($can['update'] ?? false) && $showEImza)
    <button type="button" class="e-imza-btn mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-[12px] transition duration-200 flex items-center justify-center gap-2"
            style="background-color:#2563eb !important;color:#ffffff !important;"
            data-app-id="{{ $application->id }}"
            data-pdf-type="{{ $module }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        E-İmza ile İmzala
    </button>
    @endif
</div>
