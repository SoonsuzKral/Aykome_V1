@extends('layouts.auth')

@section('title', 'Kayıt — '.config('app.name'))

@section('content')
    <h1 class="text-lg font-semibold text-slate-800">Kayıt ol</h1>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Ad Soyad</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Şifre</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Şifre tekrar</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white shadow hover:bg-emerald-800">
            Kayıt ol
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Zaten hesabınız var mı?
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">Giriş</a>
    </p>
@endsection
