@extends('layouts.admin')

@section('title', 'Modül Düzenle: ' . $module->name)

@section('content')
<div class="mx-auto max-w-7xl">
    {{-- Breadcrumb --}}
    <div class="mb-4 flex items-center gap-3 text-sm text-slate-500">
        <a href="{{ route('admin.modules.index') }}" class="hover:text-slate-700">Modül Yönetimi</a>
        <span>/</span>
        <span class="text-slate-900">{{ $module->name }}</span>
    </div>

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            @if($module->icon)
                <div class="flex h-14 w-14 items-center justify-center rounded-xl text-3xl" style="background-color: {{ $module->color ?? '#02E0FB' }}20;">
                    {{ $module->icon }}
                </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $module->name }}</h1>
                <p class="text-sm text-slate-500">
                    <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $module->slug }}</code>
                    @if($module->description) · {{ $module->description }} @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($module->is_active)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">Aktif</span>
            @else
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Pasif</span>
            @endif
            <a href="{{ route('admin.modules.show', $module->id) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <i class="fas fa-eye mr-1"></i> Önizle
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700" id="success-alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6">
        <div class="border-b border-slate-200">
            <nav class="flex gap-1" id="module-tabs">
                <button type="button" data-tab="general" class="tab-btn active px-4 py-2.5 text-sm font-medium border-b-2 border-[#02E0FB] text-[#02E0FB]">
                    <i class="fas fa-cog mr-1.5"></i>Genel
                </button>
                <button type="button" data-tab="fields" class="tab-btn px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                    <i class="fas fa-list-ul mr-1.5"></i>Alanlar
                    <span class="ml-1.5 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs">{{ $module->fields->count() }}</span>
                </button>
                <button type="button" data-tab="templates" class="tab-btn px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                    <i class="fas fa-file-alt mr-1.5"></i>Şablonlar
                    <span class="ml-1.5 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs">{{ $module->templates->count() }}</span>
                </button>
                <button type="button" data-tab="sequence" class="tab-btn px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                    <i class="fas fa-sort-numeric-up mr-1.5"></i>Sıralama
                </button>
                <button type="button" data-tab="settings" class="tab-btn px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                    <i class="fas fa-sliders-h mr-1.5"></i>Ayarlar
                </button>
            </nav>
        </div>
    </div>

    {{-- Tab Content --}}
    <div id="tab-content">
        {{-- TAB 1: GENERAL --}}
        <div id="tab-general" class="tab-panel">
            <form action="{{ route('admin.modules.update', $module->id) }}" method="POST" class="rounded-xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-700">Temel Bilgiler</h3>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Modül Adı <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $module->name) }}" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Slug <span class="text-red-500">*</span></label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $module->slug) }}" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                            <p class="mt-1 text-xs text-slate-500">Benzersiz tanımlayıcı, URL'de kullanılır</p>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Açıklama</label>
                        <textarea name="description" id="description" rows="2"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                            placeholder="Modül hakkında kısa açıklama...">{{ old('description', $module->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="icon" class="block text-sm font-medium text-slate-700 mb-1">İkon (Emoji)</label>
                            <input type="text" name="icon" id="icon" value="{{ old('icon', $module->icon) }}"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-2xl focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                                placeholder="📋">
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-medium text-slate-700 mb-1">Renk</label>
                            <div class="flex gap-2">
                                <input type="color" name="color" id="color" value="{{ old('color', $module->color ?? '#02E0FB') }}"
                                    class="h-11 w-14 rounded-lg border border-slate-300 cursor-pointer p-1">
                                <input type="text" id="color-text" value="{{ old('color', $module->color ?? '#02E0FB') }}"
                                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                                    placeholder="#02E0FB">
                            </div>
                        </div>

                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-1">Sıra</label>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $module->sort_order ?? 0) }}" min="0"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $module->is_active) ? 'checked' : '' }}
                                class="h-5 w-5 rounded border-slate-300 text-[#02E0FB] focus:ring-[#02E0FB]">
                            <label for="is_active" class="text-sm font-medium text-slate-700">Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-slate-100 px-6 py-4">
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>

        {{-- TAB 2: FIELDS --}}
        <div id="tab-fields" class="tab-panel hidden">
            <div class="grid grid-cols-4 gap-6">
                {{-- Fields List --}}
                <div class="col-span-3 rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">
                            <i class="fas fa-list-ul mr-2 text-slate-400"></i>Alan Listesi
                        </h3>
                        <span class="text-xs text-slate-500">{{ $module->fields->count() }} alan tanımlı</span>
                    </div>

                    @if($module->fields->isEmpty())
                        <div class="p-12 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                <i class="fas fa-plus text-xl text-slate-400"></i>
                            </div>
                            <p class="text-sm text-slate-500">Henüz alan eklenmemiş.</p>
                            <p class="mt-1 text-xs text-slate-400">Sağdaki formu kullanarak ilk alanı ekleyin.</p>
                        </div>
                    @else
                        <div class="p-4">
                            <ul id="fields-sortable" class="space-y-2">
                                @foreach($module->fields->sortBy('sort_order') as $field)
                                    <li class="group flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 hover:shadow-sm transition"
                                        data-id="{{ $field->id }}"
                                        data-field_name="{{ $field->field_name }}"
                                        data-field_type="{{ $field->field_type }}"
                                        data-label="{{ $field->label }}"
                                        data-placeholder="{{ $field->placeholder ?? '' }}"
                                        data-default_value="{{ $field->default_value ?? '' }}"
                                        data-help_text="{{ $field->help_text ?? '' }}"
                                        data-width="{{ $field->width ?? 'full' }}"
                                        data-is_required="{{ $field->is_required ? '1' : '0' }}"
                                        data-field_options="{{ json_encode($field->field_options ?? []) }}"
                                        data-validation_rules="{{ json_encode($field->validation_rules ?? []) }}">
                                        <div class="cursor-grab text-slate-400 hover:text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h3a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-semibold text-slate-900">{{ $field->label }}</span>
                                                @if($field->is_required)
                                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600">Zorunlu</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                                <span class="font-mono">{{ $field->field_name }}</span>
                                                <span>·</span>
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5">{{ $field->field_type }}</span>
                                                <span>·</span>
                                                <span>{{ $field->width ?? 'full' }}</span>
                                                @if($field->placeholder)
                                                    <span>·</span>
                                                    <span class="italic">"{{ $field->placeholder }}"</span>
                                                @endif
                                            </div>
                                            @if($field->field_options)
                                                <div class="mt-1.5 flex flex-wrap gap-1">
                                                    @foreach($field->field_options as $opt)
                                                        <span class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-600">{{ $opt }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                            <button type="button" class="edit-field-btn rounded-lg p-2 text-slate-400 hover:bg-blue-50 hover:text-blue-600" title="Düzenle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                            </button>
                                            <form action="{{ route('admin.modules.fields.destroy', [$module->id, $field->id]) }}" method="POST" class="inline" onsubmit="return confirm('Bu alanı silmek istediğinize emin misiniz?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-500" title="Sil">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Add Field Form --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-700">
                            <i class="fas fa-plus mr-2 text-emerald-500"></i>Alan Ekle
                        </h3>
                    </div>
                    <form action="{{ route('admin.modules.fields.store', $module->id) }}" method="POST" class="p-5 space-y-4" id="add-field-form">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Etiket (Label) <span class="text-red-500">*</span></label>
                            <input type="text" name="label" required placeholder="Kazı Miktarı"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Alan Adı (DB) <span class="text-red-500">*</span></label>
                            <input type="text" name="field_name" required placeholder="kazi_miktari"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                            <p class="mt-1 text-xs text-slate-400">Sadece küçük harf ve underscore</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tip <span class="text-red-500">*</span></label>
                            <select name="field_type" required id="field_type_select"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                                <option value="text">Metin</option>
                                <option value="textarea">Metin Alanı</option>
                                <option value="number">Sayı</option>
                                <option value="decimal">Ondalık Sayı</option>
                                <option value="select">Seçim Kutusu</option>
                                <option value="multiselect">Çoklu Seçim</option>
                                <option value="checkbox">Onay Kutusu</option>
                                <option value="radio">Radyo Buton</option>
                                <option value="file">Dosya Yükleme</option>
                                <option value="date">Tarih</option>
                                <option value="datetime">Tarih & Saat</option>
                                <option value="email">E-posta</option>
                                <option value="phone">Telefon</option>
                                <option value="address">Adres</option>
                            </select>
                        </div>

                        {{-- Options for select/radio types --}}
                        <div id="field_options_wrapper" class="hidden">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Seçenekler</label>
                            <textarea name="field_options_text" id="field_options_text" rows="3"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                                placeholder="Her satıra bir seçenek:&#10;Seçenek 1&#10;Seçenek 2&#10;Seçenek 3"></textarea>
                            <p class="mt-1 text-xs text-slate-400">Her satıra bir seçenek yazın</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Genişlik</label>
                            <select name="width" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                                <option value="full">Tam (100%)</option>
                                <option value="half">Yarım (50%)</option>
                                <option value="third">Üçüncü (33%)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Placeholder</label>
                            <input type="text" name="placeholder" placeholder="örn: 1250 m³"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Yardım Metni</label>
                            <input type="text" name="help_text" placeholder="Kullanıcıya yardımcı açıklama"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_required" id="field_is_required" value="1" class="h-4 w-4 rounded border-slate-300 text-[#02E0FB]">
                            <label for="field_is_required" class="text-xs font-medium text-slate-600">Zorunlu alan</label>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] py-2.5 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Alan Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- TAB 3: TEMPLATES --}}
        <div id="tab-templates" class="tab-panel hidden">
            <div class="grid grid-cols-3 gap-6">
                {{-- Templates List --}}
                <div class="col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">
                            <i class="fas fa-file-alt mr-2 text-slate-400"></i>Şablon Listesi
                        </h3>
                        <span class="text-xs text-slate-500">{{ $module->templates->count() }} şablon</span>
                    </div>

                    @if($module->templates->isEmpty())
                        <div class="p-12 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                <i class="fas fa-file text-xl text-slate-400"></i>
                            </div>
                            <p class="text-sm text-slate-500">Henüz şablon eklenmemiş.</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($module->templates as $template)
                                <div class="p-5">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="text-sm font-semibold text-slate-900">{{ $template->template_name }}</h4>
                                                @if($template->is_active)
                                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                                                @else
                                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-500">Pasif</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                                <span class="font-mono">{{ $template->document_type }}</span>
                                                <span>·</span>
                                                @if($template->editor_type === 'word')
                                                    <span class="text-blue-600"><i class="fab fa-microsoft-word mr-1"></i>Word</span>
                                                @elseif($template->editor_type === 'excel')
                                                    <span class="text-green-600"><i class="fas fa-file-excel mr-1"></i>Excel</span>
                                                @else
                                                    <span class="text-slate-600"><i class="fas fa-code mr-1"></i>HTML</span>
                                                @endif
                                            </div>
                                            @if($template->content_data)
                                                <div class="mt-2 rounded bg-slate-50 p-3 text-xs text-slate-600 max-h-32 overflow-hidden">
                                                    {{ Str::limit(strip_tags($template->content_data), 200) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 ml-4">
                                            <button type="button" class="preview-template-btn rounded-lg p-2 text-slate-400 hover:bg-blue-50 hover:text-blue-600" title="Önizle"
                                                data-content="{{ $template->content_data ?? '' }}"
                                                data-name="{{ $template->template_name }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form action="{{ route('admin.modules.templates.destroy', [$module->id, $template->id]) }}" method="POST" class="inline" onsubmit="return confirm('Bu şablonu silmek istediğinize emin misiniz?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-500" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Add Template Form --}}
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-700">
                            <i class="fas fa-plus mr-2 text-emerald-500"></i>Şablon Ekle
                        </h3>
                    </div>
                    <form action="{{ route('admin.modules.templates.store', $module->id) }}" method="POST" class="p-5 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Şablon Adı <span class="text-red-500">*</span></label>
                            <input type="text" name="template_name" required placeholder="Ruhsat Formu"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Belge Türü <span class="text-red-500">*</span></label>
                            <input type="text" name="document_type" required placeholder="ruhsat, tahakkuk, ..."
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                            <p class="mt-1 text-xs text-slate-400">Benzersiz tanımlayıcı</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Editor Türü</label>
                            <select name="editor_type" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                                <option value="contenteditable">HTML Editor</option>
                                <option value="word">Word Belgesi</option>
                                <option value="excel">Excel Belgesi</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">İçerik</label>
                            <textarea name="content_data" id="template_content" rows="8" placeholder="<div contenteditable>...</div>"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="template_is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-[#02E0FB]">
                            <label for="template_is_active" class="text-xs font-medium text-slate-600">Aktif</label>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] py-2.5 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Şablon Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- TAB 4: SEQUENCE --}}
        <div id="tab-sequence" class="tab-panel hidden">
            <div class="grid grid-cols-2 gap-6">
                @foreach(['basvuru' => 'Başvuru', 'ek_ruhsat' => 'Ek Ruhsat'] as $type => $typeName)
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-700">
                                <i class="fas fa-list-ol mr-2 text-slate-400"></i>{{ $typeName }}
                            </h3>
                            <span class="text-xs text-slate-500">
                                {{ $module->sequences->where('application_type', $type)->count() }} modül
                            </span>
                        </div>
                        <div class="p-5">
                            @php $sequences = $module->sequences->where('application_type', $type)->sortBy('sort_order'); @endphp

                            @if($sequences->isEmpty())
                                <p class="text-sm text-slate-500 text-center py-4">Bu başvuru tipi için sıralama yok</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach($sequences as $seq)
                                        <li class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#02E0FB] text-xs font-bold text-white">{{ $seq->sort_order + 1 }}</span>
                                                <span class="text-sm font-medium text-slate-700">{{ $seq->applicationModule->name ?? 'Modül #' . $seq->application_module_id }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <a href="{{ route('admin.modules.edit', $seq->application_module_id) }}"
                                                   class="rounded p-1.5 text-slate-400 hover:bg-white hover:text-blue-600" title="Düzenle">
                                                    <i class="fas fa-external-link-alt text-xs"></i>
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Add to this application type --}}
                            <form action="{{ route('admin.modules.sequences.store', $module->id) }}" method="POST" class="mt-4 flex gap-2">
                                @csrf
                                <input type="hidden" name="application_type" value="{{ $type }}">
                                <input type="number" name="sort_order" placeholder="Sıra" min="0"
                                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                                <button type="submit" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">
                                    <i class="fas fa-plus mr-1"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- All modules sequence overview --}}
            <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-700">
                        <i class="fas fa-sitemap mr-2 text-slate-400"></i>Tüm Modül Sıralaması (Başvuru)
                    </h3>
                </div>
                <div class="p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        @php $allSequences = \App\Models\ApplicationModuleSequence::where('application_type', 'basvuru')->with('applicationModule')->orderBy('sort_order')->get(); @endphp
                        @forelse($allSequences as $seq)
                            @if($seq->applicationModule)
                                <a href="{{ route('admin.modules.edit', $seq->applicationModule->id) }}"
                                   class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:border-[#02E0FB] hover:shadow-sm transition">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#02E0FB] text-xs font-bold text-white">{{ $seq->sort_order + 1 }}</span>
                                    <span>{{ $seq->applicationModule->icon ?? '' }}</span>
                                    <span>{{ $seq->applicationModule->name }}</span>
                                </a>
                                <i class="fas fa-arrow-right text-slate-300"></i>
                            @endif
                        @empty
                            <p class="text-sm text-slate-500">Henüz sıralama tanımlanmamış</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 5: SETTINGS --}}
        <div id="tab-settings" class="tab-panel hidden">
            <form action="{{ route('admin.modules.update', $module->id) }}" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="name" value="{{ $module->name }}">
                <input type="hidden" name="slug" value="{{ $module->slug }}">
                <input type="hidden" name="description" value="{{ $module->description }}">
                <input type="hidden" name="icon" value="{{ $module->icon }}">
                <input type="hidden" name="color" value="{{ $module->color }}">
                <input type="hidden" name="sort_order" value="{{ $module->sort_order }}">
                <input type="hidden" name="is_active" value="{{ $module->is_active ? 1 : 0 }}">

                <div class="space-y-6">
                    {{-- Approval Settings --}}
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-slate-700">
                                <i class="fas fa-check-circle mr-2 text-emerald-500"></i>Onay Ayarları
                            </h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">İşlem Tipi</label>
                                    <select name="config[approval_type]" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none">
                                        <option value="approve" {{ $module->getConfigValue('approval_type') === 'approve' ? 'selected' : '' }}>Onay</option>
                                        <option value="paraf" {{ $module->getConfigValue('approval_type') === 'paraf' ? 'selected' : '' }}>Paraf</option>
                                        <option value="e_imza" {{ $module->getConfigValue('approval_type') === 'e_imza' ? 'selected' : '' }}>E-İmza</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Sıradaki Modül</label>
                                    <select name="config[next_module]" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none">
                                        <option value="">-- Yok --</option>
                                        @foreach(\App\Models\ApplicationModule::where('id', '!=', $module->id)->get() as $m)
                                            <option value="{{ $m->slug }}" {{ $module->getConfigValue('next_module') === $m->slug ? 'selected' : '' }}>
                                                {{ $m->icon }} {{ $m->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-2">Görünüm Koşulu</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer {{ $module->getConfigValue('visibility_condition') === 'always' ? 'border-[#02E0FB] bg-[#02E0FB]/5' : '' }}">
                                        <input type="radio" name="config[visibility_condition]" value="always"
                                            {{ $module->getConfigValue('visibility_condition') === 'always' ? 'checked' : '' }}
                                            class="h-4 w-4 text-[#02E0FB]">
                                        <div>
                                            <div class="text-sm font-medium text-slate-700">Her zaman görünür</div>
                                            <div class="text-xs text-slate-500">Başvuru sürecinde her zaman aktif</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer {{ $module->getConfigValue('visibility_condition') === 'after_previous' ? 'border-[#02E0FB] bg-[#02E0FB]/5' : '' }}">
                                        <input type="radio" name="config[visibility_condition]" value="after_previous"
                                            {{ $module->getConfigValue('visibility_condition') === 'after_previous' ? 'checked' : '' }}
                                            class="h-4 w-4 text-[#02E0FB]">
                                        <div>
                                            <div class="text-sm font-medium text-slate-700">Önceki modül sonrası</div>
                                            <div class="text-xs text-slate-500">Önceki aşama tamamlanınca görünür</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Signature Settings --}}
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-slate-700">
                                <i class="fas fa-signature mr-2 text-blue-500"></i>İmza Ayarları
                                <span class="ml-2 text-xs font-normal text-slate-400">(E-İmza ve Paraf için)</span>
                            </h3>
                        </div>
                        <div class="p-5 space-y-5">
                            <div class="grid grid-cols-2 gap-5">
                                <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-4">
                                    <input type="hidden" name="config[e_imza_required]" value="0">
                                    <input type="checkbox" name="config[e_imza_required]" id="e_imza_required" value="1"
                                        {{ $module->getConfigValue('e_imza_required') ? 'checked' : '' }}
                                        class="h-5 w-5 rounded border-slate-300 text-[#02E0FB] focus:ring-[#02E0FB]">
                                    <div>
                                        <label for="e_imza_required" class="text-sm font-medium text-slate-700">E-İmza / Paraf Zorunlu</label>
                                        <p class="text-xs text-slate-500">E-imza veya paraf ile imzalanması gerekir</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">İmzalı Nüsha Sayısı</label>
                                    <select name="config[signature_copies]" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none">
                                        <option value="1" {{ $module->getConfigValue('signature_copies') == 1 ? 'selected' : '' }}>1 Nüsha</option>
                                        <option value="2" {{ $module->getConfigValue('signature_copies') == 2 ? 'selected' : '' }}>2 Nüsha</option>
                                        <option value="3" {{ $module->getConfigValue('signature_copies') == 3 ? 'selected' : '' }}>3 Nüsha</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">İmza Görünümü</label>
                                    <select name="config[signature_view_type]" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none">
                                        <option value="full" {{ $module->getConfigValue('signature_view_type') === 'full' ? 'selected' : '' }}>Tam İmza (E-İmza)</option>
                                        <option value="paraf" {{ $module->getConfigValue('signature_view_type') === 'paraf' ? 'selected' : '' }}>Paraf (İmza)</option>
                                    </select>
                                    <p class="mt-1 text-xs text-slate-400">E-İmza seçildiğinde tam imza, Paraf seçildiğinde paraf görünür</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">İmza Konumu</label>
                                    <select name="config[signature_position]" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none">
                                        <option value="bottom_right" {{ $module->getConfigValue('signature_position') === 'bottom_right' ? 'selected' : '' }}>Sağ Alt</option>
                                        <option value="bottom_left" {{ $module->getConfigValue('signature_position') === 'bottom_left' ? 'selected' : '' }}>Sol Alt</option>
                                        <option value="center" {{ $module->getConfigValue('signature_position') === 'center' ? 'selected' : '' }}>Orta</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">İmza Notu</label>
                                <input type="text" name="config[signature_note]" value="{{ $module->getConfigValue('signature_note', '') }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                                    placeholder="İmzalı nüsha için not...">
                            </div>
                        </div>
                    </div>

                    {{-- Field Validation Settings --}}
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-slate-700">
                                <i class="fas fa-check-double mr-2 text-amber-500"></i>Doğrulama Ayarları
                            </h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-4">
                                <input type="hidden" name="config[require_all_fields]" value="0">
                                <input type="checkbox" name="config[require_all_fields]" id="require_all_fields" value="1"
                                    {{ $module->getConfigValue('require_all_fields') ? 'checked' : '' }}
                                    class="h-5 w-5 rounded border-slate-300 text-[#02E0FB] focus:ring-[#02E0FB]">
                                <div>
                                    <label for="require_all_fields" class="text-sm font-medium text-slate-700">Tüm alanları zorunlu yap</label>
                                    <p class="text-xs text-slate-500">Alan bazında zorunlu ayarı ne olursa olsun tüm alanları zorunlu kıl</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-4">
                                <input type="hidden" name="config[auto_approve_on_complete]" value="0">
                                <input type="checkbox" name="config[auto_approve_on_complete]" id="auto_approve" value="1"
                                    {{ $module->getConfigValue('auto_approve_on_complete') ? 'checked' : '' }}
                                    class="h-5 w-5 rounded border-slate-300 text-[#02E0FB] focus:ring-[#02E0FB]">
                                <div>
                                    <label for="auto_approve" class="text-sm font-medium text-slate-700">Tamamlandığında otomatik onayla</label>
                                    <p class="text-xs text-slate-500">Tüm alanlar doldurulunca otomatik olarak onayla</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </form>

            {{-- ÇÖZÜM_11C: Süreç Onay Rotası — bu modüle bağlı süreç adımları (bağımsız formlar) --}}
            <div class="mt-6 space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">
                            <i class="fas fa-route mr-2 text-violet-500"></i>Süreç Onay Rotası
                            <span class="ml-2 text-xs font-normal text-slate-400">({{ $module->icon }} {{ $module->name }} modülüne bağlı e-imza / onay adımları)</span>
                        </h3>
                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-bold text-violet-600">{{ $moduleSteps->count() }} adım</span>
                    </div>
                    <div class="p-5 space-y-5">
                        @if($moduleSteps->isNotEmpty())
                            <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                                @foreach($moduleSteps as $ms)
                                    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $ms->process?->name }}</span>
                                                <span class="text-xs font-semibold text-slate-700">{{ $ms->step_order }}. {{ $ms->name }}</span>
                                                <span class="rounded-md bg-cyan-50 px-2 py-0.5 text-[10px] font-bold uppercase text-cyan-700">{{ $ms->role_key }}</span>
                                                @if(($ms->action_type ?? 'onay') === 'e_imza')
                                                    <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">🔏 E-İMZA</span>
                                                @elseif(($ms->action_type ?? 'onay') === 'paraf')
                                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">📝 PARAF</span>
                                                @else
                                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">✅ ONAY</span>
                                                @endif
                                                @if(!empty($ms->signature_config['allow_signed_copy_upload'] ?? null))
                                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">🗂 İmzalı Nüsha Yükleme</span>
                                                @endif
                                            </div>
                                            @if(($ms->signature_config['pdf_type'] ?? null))
                                                <p class="mt-0.5 text-[10px] text-slate-400">PDF: {{ $pdfTypeOptions[$ms->signature_config['pdf_type']] ?? $ms->signature_config['pdf_type'] }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.processes.blueprint', $ms->process) }}"
                                               class="rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[11px] font-bold text-cyan-700 hover:bg-cyan-100">
                                                🎨 Süreçte Aç
                                            </a>
                                            <form method="POST" action="{{ route('admin.processes.destroy-step', $ms) }}" onsubmit="return confirm('Bu adımı süreçten silmek istediğinize emin misiniz?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-50">Sil</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400">Bu modüle henüz süreç adımı bağlanmamış. Aşağıdan bir sürece e-imza / onay adımı ekleyin.</p>
                        @endif

                        <div class="border-t border-slate-100 pt-4">
                            <h4 class="mb-3 text-xs font-bold text-slate-600">＋ Sürece Adım Ekle</h4>
                            <div class="space-y-4">
                                @foreach($processes as $process)
                                    <details class="rounded-lg border border-slate-200 bg-slate-50">
                                        <summary class="cursor-pointer px-4 py-3 text-xs font-semibold text-slate-700 hover:text-slate-900">
                                            {{ $process->name }}
                                            <span class="ml-2 text-[10px] font-normal text-slate-400">
                                                ({{ $process->steps()->count() }} adım{{ $process->is_default ? ' · Varsayılan' : '' }}{{ $process->is_active ? '' : ' · Pasif' }})
                                            </span>
                                        </summary>
                                        <div class="px-4 pb-4">
                                            @include('admin.processes._step_form', [
                                                'formMode' => 'add',
                                                'formId' => 'module-' . $module->slug . '-proc-' . $process->id,
                                                'process' => $process,
                                                'moduleFilter' => $module->slug,
                                            ])
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Field Modal --}}
<div id="editFieldModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeFieldModal()"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700"><i class="fas fa-edit mr-2"></i>Alanı Düzenle</h3>
            <button type="button" onclick="closeFieldModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="editFieldForm" class="p-5 space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden" id="edit_field_id" name="field_id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Etiket <span class="text-red-500">*</span></label>
                    <input type="text" name="label" id="edit_label" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Alan Adı <span class="text-red-500">*</span></label>
                    <input type="text" name="field_name" id="edit_field_name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tip</label>
                    <select name="field_type" id="edit_field_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        <option value="text">Metin</option>
                        <option value="textarea">Metin Alanı</option>
                        <option value="number">Sayı</option>
                        <option value="decimal">Ondalık Sayı</option>
                        <option value="select">Seçim Kutusu</option>
                        <option value="multiselect">Çoklu Seçim</option>
                        <option value="checkbox">Onay Kutusu</option>
                        <option value="radio">Radyo Buton</option>
                        <option value="file">Dosya Yükleme</option>
                        <option value="date">Tarih</option>
                        <option value="datetime">Tarih & Saat</option>
                        <option value="email">E-posta</option>
                        <option value="phone">Telefon</option>
                        <option value="address">Adres</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Genişlik</label>
                    <select name="width" id="edit_width" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
                        <option value="full">Tam (100%)</option>
                        <option value="half">Yarım (50%)</option>
                        <option value="third">Üçüncü (33%)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Placeholder</label>
                <input type="text" name="placeholder" id="edit_placeholder" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
            </div>

            <div id="edit_field_options_wrapper" class="hidden">
                <label class="block text-xs font-medium text-slate-600 mb-1">Seçenekler (her satıra bir tane)</label>
                <textarea name="field_options_text" id="edit_field_options_text" rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Yardım Metni</label>
                <input type="text" name="help_text" id="edit_help_text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]">
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_required" id="edit_is_required" value="1" class="h-4 w-4 rounded border-slate-300 text-[#02E0FB]">
                <label for="edit_is_required" class="text-sm font-medium text-slate-600">Zorunlu alan</label>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeFieldModal()" class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    İptal
                </button>
                <button type="submit" class="flex-1 rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Template Preview Modal --}}
<div id="previewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closePreviewModal()"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-3xl bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700" id="previewModalTitle"><i class="fas fa-eye mr-2"></i>Şablon Önizleme</h3>
            <button type="button" onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-5 max-h-[60vh] overflow-auto" id="previewModalContent">
        </div>
        <div class="border-t border-slate-100 px-5 py-4 flex justify-end">
            <button type="button" onclick="closePreviewModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Kapat
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const tabId = this.dataset.tab;

            tabBtns.forEach(b => {
                b.classList.remove('active', 'border-[#02E0FB]', 'text-[#02E0FB]');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            this.classList.add('active', 'border-[#02E0FB]', 'text-[#02E0FB]');
            this.classList.remove('border-transparent', 'text-slate-500');

            tabPanels.forEach(p => p.classList.add('hidden'));
            document.getElementById('tab-' + tabId).classList.remove('hidden');

            // Update URL hash
            history.replaceState(null, '', '#' + tabId);
        });
    });

    // Handle hash for tab persistence
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        document.querySelector(`[data-tab="${hash}"]`)?.click();
    }

    // Auto-hide success alert
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 500);
        }, 3000);
    }

    // Color sync
    const colorInput = document.getElementById('color');
    const colorText = document.getElementById('color-text');
    if (colorInput && colorText) {
        colorInput.addEventListener('input', () => colorText.value = colorInput.value);
        colorText.addEventListener('input', () => colorInput.value = colorText.value);
    }

    // Fields sortable
    const fieldsSortable = document.getElementById('fields-sortable');
    if (fieldsSortable) {
        new Sortable(fieldsSortable, {
            animation: 150,
            handle: '.cursor-grab',
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                const ids = [...fieldsSortable.querySelectorAll('li')].map(li => li.dataset.id);
                fetch('{{ route('admin.modules.fields.reorder', $module->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: ids })
                }).then(r => r.json()).then(data => {
                    if (data.success) showToast('Sıralama kaydedildi');
                });
            }
        });
    }

    // Field type change - show/hide options
    const fieldTypeSelect = document.getElementById('field_type_select');
    const fieldOptionsWrapper = document.getElementById('field_options_wrapper');
    if (fieldTypeSelect && fieldOptionsWrapper) {
        fieldTypeSelect.addEventListener('change', function() {
            const showForTypes = ['select', 'multiselect', 'radio'];
            fieldOptionsWrapper.classList.toggle('hidden', !showForTypes.includes(this.value));
        });
    }

    // Edit field modal
    const editFieldBtns = document.querySelectorAll('.edit-field-btn');
    const editFieldModal = document.getElementById('editFieldModal');
    const editFieldForm = document.getElementById('editFieldForm');
    const editFieldId = document.getElementById('edit_field_id');

    editFieldBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const li = this.closest('li');
            const id = li.dataset.id;
            const fieldName = li.dataset.field_name;
            const fieldType = li.dataset.field_type;
            const label = li.dataset.label;
            const placeholder = li.dataset.placeholder;
            const helpText = li.dataset.help_text;
            const width = li.dataset.width;
            const isRequired = li.dataset.is_required === '1';
            const fieldOptions = JSON.parse(li.dataset.field_options || '[]');
            const validationRules = JSON.parse(li.dataset.validation_rules || '[]');

            editFieldId.value = id;
            document.getElementById('edit_label').value = label;
            document.getElementById('edit_field_name').value = fieldName;
            document.getElementById('edit_field_type').value = fieldType;
            document.getElementById('edit_width').value = width;
            document.getElementById('edit_placeholder').value = placeholder;
            document.getElementById('edit_help_text').value = helpText;
            document.getElementById('edit_is_required').checked = isRequired;

            const editOptionsWrapper = document.getElementById('edit_field_options_wrapper');
            const editOptionsText = document.getElementById('edit_field_options_text');
            const showForTypes = ['select', 'multiselect', 'radio'];
            editOptionsWrapper.classList.toggle('hidden', !showForTypes.includes(fieldType));
            editOptionsText.value = fieldOptions.join('\n');

            editFieldForm.action = `/admin/modules/{{ $module->id }}/fields/${id}`;
            editFieldModal.classList.remove('hidden');
        });
    });

    window.closeFieldModal = function() {
        editFieldModal.classList.add('hidden');
    };

    // Template preview modal
    const previewBtns = document.querySelectorAll('.preview-template-btn');
    const previewModal = document.getElementById('previewModal');

    previewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const content = this.dataset.content;
            const name = this.dataset.name;

            document.getElementById('previewModalTitle').textContent = name + ' - Önizleme';
            document.getElementById('previewModalContent').innerHTML = content || '<p class="text-slate-500 text-center">İçerik yok</p>';
            previewModal.classList.remove('hidden');
        });
    });

    window.closePreviewModal = function() {
        previewModal.classList.add('hidden');
    };

    // Toast notification
    window.showToast = function(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 rounded-lg bg-emerald-500 px-4 py-2 text-sm text-white shadow-lg';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 2000);
    };
});
</script>
@endpush
