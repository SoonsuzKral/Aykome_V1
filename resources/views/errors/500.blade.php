@extends('layouts.app')

@section('title', 'Sunucu hatası')

@section('content')
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
        <p class="text-6xl font-semibold text-slate-300">500</p>
        <h1 class="mt-2 text-xl font-semibold text-slate-900">Bir sorun oluştu</h1>
        <p class="text-sm text-slate-600">Lütfen daha sonra tekrar deneyin. Sorun devam ederse destek ile iletişime geçin.</p>
        <a href="{{ route('home') }}" class="mt-6 text-sm font-medium text-emerald-700 hover:underline">Ana sayfa</a>
    </div>
@endsection
