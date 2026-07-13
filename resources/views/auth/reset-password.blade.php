@extends('layouts.auth')

@section('title', 'Yeni şifre')

@section('content')
    <h1 class="text-lg font-semibold text-slate-800">Yeni şifre belirle</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email', request('email')) }}" required autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Yeni şifre</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Şifre tekrar</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white shadow hover:bg-emerald-800">
            Şifreyi sıfırla
        </button>
    </form>
@endsection
