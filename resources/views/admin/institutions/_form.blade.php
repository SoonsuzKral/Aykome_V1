@php $editing = $editing ?? false; @endphp

<div class="grid gap-4 sm:grid-cols-2">

    {{-- Kurum Adı --}}
    <div class="sm:col-span-2">
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Kurum Adı *</label>
        <input type="text" name="name" required maxlength="255"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="Örn: TEDAŞ Şanlıurfa">
    </div>

    {{-- Tip --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Kurum Tipi</label>
        <select name="type"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40">
            <option value="">— Seçiniz —</option>
            @foreach($types as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Yetkili --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Yetkili Kişi</label>
        <input type="text" name="authorized_person" maxlength="255"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="Ad Soyad">
    </div>

    {{-- Vergi No --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Vergi No</label>
        <input type="text" name="tax_number" maxlength="20"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="0000000000">
    </div>

    {{-- Telefon --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Telefon</label>
        <input type="tel" name="phone" maxlength="30"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="0542 000 0000">
    </div>

    {{-- E-posta --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">E-posta</label>
        <input type="email" name="email" maxlength="255"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="info@kurum.gov.tr">
    </div>

    {{-- Renk --}}
    <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Renk Kodu</label>
        <div class="flex items-center gap-3">
            <input type="color" name="color_code" value="#6B7280"
                class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1 shadow-sm">
            <span class="text-xs text-slate-400">Harita ve etiket rengi</span>
        </div>
    </div>

    {{-- Belediye mi? --}}
    <div class="flex items-center gap-3 self-end pb-1">
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox" name="is_municipality" value="1" class="peer sr-only">
            <div class="peer h-5 w-9 rounded-full bg-slate-200 after:absolute after:start-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:bg-sky-500 peer-checked:after:translate-x-full peer-focus:ring-2 peer-focus:ring-sky-400/40"></div>
        </label>
        <span class="text-sm text-slate-600">Belediye birimi</span>
    </div>

    {{-- Adres --}}
    <div class="sm:col-span-2">
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Adres</label>
        <textarea name="address" rows="2" maxlength="1000"
            class="w-full resize-none rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/40"
            placeholder="Açık adres"></textarea>
    </div>

</div>
