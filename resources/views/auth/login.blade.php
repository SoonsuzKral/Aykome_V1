@extends('layouts.auth')

@section('title', 'Giriş — '.config('app.name'))

@section('content')
    <h1 class="text-lg font-semibold text-slate-800">Giriş yap</h1>
    <p class="mt-1 text-sm text-slate-500">Kurumsal hesabınızla devam edin.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Şifre</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600">
                <span class="ms-2">Beni hatırla</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-emerald-700 hover:underline">Şifremi unuttum</a>
            @endif
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white shadow hover:bg-emerald-800">
            Giriş
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-600">
            Hesabınız yok mu?
            <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline">Kayıt ol</a>
        </p>
    @endif
@endsection
