@php
    // ÇÖZÜM_11C: Süreç adım ekle/düzenle formu — processes/index + modules/edit içinde ortak.
    // Parametreler:
    //   $formMode    : 'add' | 'edit'
    //   $formId      : JS benzersizliği için id eki (örn. step-12 / add-44 / module-5)
    //   $process     : add modunda bağlanacak süreç (ProcessDefinition)
    //   $step        : edit modunda düzenlenen adım
    //   $moduleFilter: verilirse "Karışabildiği Modüller" yalnızca bu modüle sabitlenir (hidden)
    $sig = $step->signature_config ?? [];
    $sigBoxId = 'signature-config-' . $formId;
    $isEdit = ($formMode ?? 'add') === 'edit';
    $filtered = $moduleFilter ?? null;
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.processes.update-step', $step) : route('admin.processes.store-step') }}"
      class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @else
        <input type="hidden" name="process_definition_id" value="{{ $process->id }}">
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Adı</label>
            <input type="text" name="name" value="{{ $step->name ?? '' }}" required maxlength="190" placeholder="{{ $isEdit ? '' : 'örn. Fen İşleri Müdürü Onayı' }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
        </div>
        <div>
            <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Anahtarı (role_key)</label>
            <input type="text" name="role_key" value="{{ $step->role_key ?? '' }}" required maxlength="50" pattern="[a-zA-Z0-9_-]+" placeholder="{{ $isEdit ? '' : 'örn. mudur' }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-400">
        </div>
    </div>
    <div class="mt-3">
        <label class="mb-1 block text-[11px] font-bold text-slate-500">Adım Yetkilisi (Rol / Roller)</label>
        <div class="flex flex-wrap gap-2">
            @foreach($roleOptions as $key => $label)
                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium {{ in_array($key, $step->roles ?? [], true) ? 'border-violet-300 bg-violet-50 text-violet-800' : 'border-slate-200 bg-white text-slate-600' }}">
                    <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $step->roles ?? [], true)) class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="mt-3">
        @if($filtered)
            <label class="mb-1 block text-[11px] font-bold text-slate-500">Karışabildiği Modüller</label>
            <input type="hidden" name="approvable_modules[]" value="{{ $filtered }}">
            <p class="text-xs text-slate-500">
                🔒 Bu adım <b class="text-emerald-700">{{ $moduleOptions[$filtered] ?? $filtered }}</b> modülüne bağlıdır
                (modül ayarlarından eklenen adımlar yalnızca bu modüle karışabilir).
            </p>
        @else
            <label class="mb-1 block text-[11px] font-bold text-slate-500">Karışabildiği Modüller (Ne Onaylar?)</label>
            <div class="flex flex-wrap gap-2">
                @foreach($moduleOptions as $key => $label)
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium {{ in_array($key, $step->approvable_modules ?? [], true) ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600' }}">
                        <input type="checkbox" name="approvable_modules[]" value="{{ $key }}" @checked(in_array($key, $step->approvable_modules ?? [], true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Aksiyon Tipi --}}
    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-3">
        <label class="mb-2 block text-[11px] font-bold text-slate-700">Aksiyon Tipi</label>
        <div class="flex flex-wrap gap-3">
            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                <input type="radio" name="action_type" value="onay" @checked(($step->action_type ?? 'onay') === 'onay') class="border-slate-300 text-cyan-600 focus:ring-cyan-500" onchange="document.getElementById('{{ $sigBoxId }}').style.display = 'none'">
                <span>✅ Onay</span>
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                <input type="radio" name="action_type" value="paraf" @checked(($step->action_type ?? 'onay') === 'paraf') class="border-slate-300 text-cyan-600 focus:ring-cyan-500" onchange="document.getElementById('{{ $sigBoxId }}').style.display = 'none'">
                <span>📝 Paraf</span>
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                <input type="radio" name="action_type" value="e_imza" @checked(($step->action_type ?? 'onay') === 'e_imza') class="border-slate-300 text-cyan-600 focus:ring-cyan-500" onchange="document.getElementById('{{ $sigBoxId }}').style.display = this.checked ? 'block' : 'none'">
                <span>🔏 E-İmza</span>
            </label>
        </div>
    </div>

    {{-- E-İmza Ayarları (e_imza seçilince göster) --}}
    <div id="{{ $sigBoxId }}" class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3" style="display: {{ ($step->action_type ?? 'onay') === 'e_imza' ? 'block' : 'none' }};">
        <h4 class="mb-2 text-xs font-bold text-blue-800">🔏 E-İmza Ayarları</h4>
        <div class="space-y-3">
            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-700">
                <input type="checkbox" name="signature_config[enabled]" value="1" @checked(!empty($sig['enabled'])) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                E-İmza Gerekli
            </label>
            {{-- ÇÖZÜM_11C: Bu e-imza adımında "imzalı nüsha yükleme" bölümü gösterilir mi? --}}
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-medium text-slate-700">
                <input type="checkbox" name="signature_config[allow_signed_copy_upload]" value="1" @checked(!empty($sig['allow_signed_copy_upload'])) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                🗂 İmzalı Nüsha Yükleme Bölümünü Göster
                <span class="text-[9px] font-normal text-slate-400">(bu adımdaki yetkililer imzalı PDF/işlenmiş nüshayı yükleyebilir)</span>
            </label>
            <div>
                <label class="mb-1 block text-[10px] font-bold text-slate-600">İmzalayacak Kullanıcılar</label>
                <select name="signature_config[signer_ids][]" multiple class="block w-full rounded-lg border-slate-300 text-xs focus:border-blue-400" size="4">
                    @foreach($municipalityUsers as $user)
                    <option value="{{ $user['id'] }}" @selected(in_array($user['id'], $sig['signer_ids'] ?? [], true))>{{ $user['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-0.5 text-[9px] text-slate-500">Ctrl/Cmd tuşuyla çoklu seçim yapabilirsiniz</p>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold text-slate-600">İmzalayacak Roller</label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($roleOptions as $key => $label)
                    <label class="flex cursor-pointer items-center gap-1 rounded border border-blue-200 bg-white px-2 py-1 text-[10px] font-medium text-slate-600">
                        <input type="checkbox" name="signature_config[signer_roles][]" value="{{ $key }}" @checked(in_array($key, $sig['signer_roles'] ?? [], true)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-400">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold text-slate-600">Hangi PDF İmzalanacak?</label>
                <select name="signature_config[pdf_type]" class="block w-full rounded-lg border-slate-300 text-xs focus:border-blue-400">
                    <option value="">— Seçiniz —</option>
                    @foreach($pdfTypeOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($sig['pdf_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($isEdit)
        <div class="mt-3 flex items-center gap-3">
            <label class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-slate-600">
                <input type="checkbox" name="is_active" value="1" @checked($step->is_active) class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"> Aktif adım
            </label>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700">Güncelle</button>
        </div>
    @else
        <button type="submit" class="mt-4 w-full rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Adımı Ekle</button>
    @endif
</form>