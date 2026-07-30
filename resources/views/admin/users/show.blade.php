@extends('layouts.admin')

@section('page-heading', $user->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $user->name }}</h1>
            <p class="text-sm text-slate-600">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Düzenle</a>
    </div>

    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-sm space-y-2">
        <div><span class="text-slate-500">Kurum:</span> {{ $user->institution?->name ?? '—' }}</div>
        <div><span class="text-slate-500">Roller:</span> {{ $user->roles->map(fn($r) => ['super-admin' => 'Super Admin', 'municipality-admin' => 'Belediye Yöneticisi', 'municipality-staff' => 'Belediye Personeli', 'institution-manager' => 'Kurum Yöneticisi', 'institution-staff' => 'Kurum Personeli', 'field-team' => 'Saha Personeli'][$r->name] ?? $r->name)->join(', ') }}</div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-emerald-700 hover:underline">← Listeye dön</a>
    </div>
@endsection
