@extends('layouts.auth')

@section('title', 'Şifre sıfırlama')

@section('content')
    <h1 class="text-lg font-semibold text-slate-800">Şifre sıfırlama bağlantısı</h1>
    <p class="mt-1 text-sm text-slate-500">Kayıtlı e-posta adresinizi girin; bağlantı gönderelim.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-medium text-white shadow hover:bg-emerald-800">
            Bağlantı gönder
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-emerald-700 hover:underline">Girişe dön</a>
    </p>
@endsection
