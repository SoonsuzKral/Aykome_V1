@extends('layouts.admin')

@section('page-heading', 'Yeni lisans')

@section('content')
    <form method="POST" action="{{ route('admin.licenses.store') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700">Lisans anahtarı</label>
            <input type="text" name="license_key" value="{{ old('license_key') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm font-mono text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Sahip / firma</label>
            <input type="text" name="owner_name" value="{{ old('owner_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Kurum (opsiyonel)</label>
            <select name="institution_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                <option value="">—</option>
                @foreach($institutions as $i)
                    <option value="{{ $i->id }}" @selected(old('institution_id') == $i->id)>{{ $i->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Geçerlilik başlangıcı</label>
                <input type="date" name="valid_from" value="{{ old('valid_from') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Bitiş tarihi</label>
                <input type="date" name="valid_until" value="{{ old('valid_until') }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Kullanıcı limiti</label>
            <input type="number" name="user_limit" value="{{ old('user_limit') }}" min="0" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="Boş = sınırsız">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300">
            <label for="is_active" class="text-sm text-slate-700">Aktif</label>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.licenses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">İptal</a>
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Kaydet</button>
        </div>
    </form>
@endsection
