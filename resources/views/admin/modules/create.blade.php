@extends('layouts.admin')

@section('title', 'Yeni Modül Oluştur')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <div class="flex items-center gap-3 text-sm text-slate-500 mb-2">
            <a href="{{ route('admin.modules.index') }}" class="hover:text-slate-700">Modül Yönetimi</a>
            <span>/</span>
            <span>Yeni Modül</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Yeni Modül Oluştur</h1>
    </div>

    <form action="{{ route('admin.modules.store') }}" method="POST" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Modül Adı <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB] @error('name') border-red-500 @enderror"
                    placeholder="Örn: Ön Kazı Onayı">
                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Slug <span class="text-red-500">*</span></label>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB] @error('slug') border-red-500 @enderror"
                    placeholder="örn: on_kazi_onayi">
                <p class="mt-1 text-xs text-slate-500">Benzersiz bir slug girin. Küçük harf ve alt çizgi kullanabilirsiniz.</p>
                @error('slug')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Açıklama</label>
                <textarea name="description" id="description" rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB] @error('description') border-red-500 @enderror"
                    placeholder="Modül hakkında kısa bir açıklama...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="icon" class="block text-sm font-medium text-slate-700 mb-1">İkon (Emoji)</label>
                    <input type="text" name="icon" id="icon" value="{{ old('icon', '📋') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-lg focus:border-[#02E0FB] focus:outline-none focus:ring-1 focus:ring-[#02E0FB]"
                        placeholder="📋">
                    <p class="mt-1 text-xs text-slate-500">Modül için bir emoji ikon seçin</p>
                </div>

                <div>
                    <label for="color" class="block text-sm font-medium text-slate-700 mb-1">Renk</label>
                    <input type="color" name="color" id="color" value="{{ old('color', '#02E0FB') }}"
                        class="h-10 w-full rounded-lg border border-slate-300 cursor-pointer">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-[#02E0FB] focus:ring-[#02E0FB]">
                <label for="is_active" class="text-sm font-medium text-slate-700">Aktif</label>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.modules.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                İptal
            </a>
            <button type="submit" class="rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] px-5 py-2 text-sm font-semibold text-white shadow transition hover:shadow-lg">
                Oluştur
            </button>
        </div>
    </form>
</div>
@endsection
