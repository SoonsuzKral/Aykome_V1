@extends('layouts.admin')

@section('page-heading', 'Kull güncelle')

@section('content')
@php
    $roleLabels = [
        'super-admin'          => 'Super Admin',
        'municipality-admin'   => 'Belediye Yöneticisi',
        'municipality-staff'   => 'Belediye Personeli',
        'institution-manager'  => 'Kurum Yöneticisi',
        'institution-staff'    => 'Kurum Personeli',
        'field-team'           => 'Saha Personeli',
    ];
@endphp
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PATCH')
        <div>
            <label class="block text-sm font-medium text-slate-700">Ad Soyad</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">E-posta</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Yeni şifre (boş bırakılabilir)</label>
            <input type="password" name="password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Şifre tekrar</label>
            <input type="password" name="password_confirmation" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Kurum</label>
            <select name="institution_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                <option value="">—</option>
                @foreach($institutions as $i)
                    <option value="{{ $i->id }}" @selected(old('institution_id', $user->institution_id) == $i->id)>{{ $i->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-700">Roller</div>
            <div class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                @php $selected = old('roles', $user->roles->pluck('name')->all()); @endphp
                @foreach($roles as $role)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $selected, true))>
                        {{ $roleLabels[$role->name] ?? $role->name }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">İptal</a>
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Güncelle</button>
        </div>
    </form>
@endsection
