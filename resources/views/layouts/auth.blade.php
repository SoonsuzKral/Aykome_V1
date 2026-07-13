<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Giriş — '.config('app.name'))</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <span class="text-xl font-bold tracking-tight text-slate-800">{{ config('app.name') }}</span>
            <p class="mt-1 text-sm text-slate-500">Altyapı kazı izin yönetimi</p>
        </div>
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            @include('partials.flash-message')
            @yield('content')
        </div>
    </div>
</body>
</html>
