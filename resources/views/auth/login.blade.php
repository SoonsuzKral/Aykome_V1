@extends('layouts.auth')

@section('title', 'Giriş — '.config('app.name'))

@section('content')
    <div class="text-center mb-8">
        <img src="https://www.eyyubiye.bel.tr/images/logo.png" alt="Eyyübiye Belediyesi" class="h-20 mx-auto">
    </div>

    <form method="POST" action="{{ route('login') }}" autocomplete="on" class="space-y-5">
        @csrf
        <div class="relative">
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            </div>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="E-posta adresiniz"
                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 pl-11 pr-4 py-3.5 text-sm placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500 focus:bg-white transition-all">
        </div>
        <div class="relative">
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="Şifreniz"
                   class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 pl-11 pr-4 py-3.5 text-sm placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500 focus:bg-white transition-all">
        </div>
        <div class="flex items-center pt-1">
            <label class="inline-flex items-center gap-2.5 text-sm text-slate-600 cursor-pointer select-none group">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span class="group-hover:text-slate-800 transition">Beni hatırla</span>
            </label>
        </div>
        <button type="submit" class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/25 hover:from-emerald-600 hover:to-emerald-500 transition-all duration-300 active:scale-[0.98]">
            <span class="flex items-center justify-center gap-3">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12L8 8m4 4l4-4M12 12v9"/></svg>
                <span>Giriş Yap</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17l8-8 8 8M4 21h16"/></svg>
            </span>
        </button>
    </form>

    @if(session('status'))
        <div class="mt-5 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 text-center">{{ session('status') }}</div>
    @endif
@endsection
