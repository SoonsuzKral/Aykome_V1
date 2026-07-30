<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Giriş — '.config('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.svg">
    @vite(['resources/css/app.css'])
    <style>
        .login-card {
            border: 3px solid #EAB308;
            box-shadow: 0 0 30px rgba(234,179,8,0.15), 0 8px 32px rgba(0,0,0,0.15);
        }
        body {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://antalyamimarlik.com.tr/tema/genel/uploads/hizmetler/hafriyat.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="login-card w-full max-w-[420px] rounded-2xl bg-white/95 p-12 backdrop-blur-sm">
            @include('partials.flash-message')
            @yield('content')
        </div>
    </div>
</body>
</html>
