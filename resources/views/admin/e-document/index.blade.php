@extends('layouts.admin')

@section('page-heading', 'Evrak ve Tevdi (E-Belge)')

@section('content')
    <div class="mb-8 flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Evrak ve Tevdi Modülü</h1>
            <p class="mt-1 text-sm text-slate-500">Dijital evrak arşivi, imzalı belge yönetimi ve otomatik tevdi sistemi.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-100 px-3 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-violet-200">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
            PRO Modül
        </span>
    </div>

    {{-- Coming Soon Banner --}}
    <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-purple-50 p-12 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-100">
            <svg class="h-8 w-8 text-violet-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm4.875 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Evrak ve Tevdi (E-Belge) PRO</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
            Başvurulara ait tüm evrakları dijital arşivleyin, e-imzalı belgeler oluşturun ve yetkili kurumlara otomatik tevdi edin.
        </p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3 text-left">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50">
                    <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-800">E-İmza Entegrasyonu</p>
                <p class="mt-1 text-xs text-slate-500">Nitelikli elektronik imza desteği</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50">
                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-800">Dijital Arşiv</p>
                <p class="mt-1 text-xs text-slate-500">Tüm belgeler güvenli bulutta saklanır</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-800">Otomatik Tevdi</p>
                <p class="mt-1 text-xs text-slate-500">İlgili kurumlara otomatik belge iletimi</p>
            </div>
        </div>

        <p class="mt-8 text-xs text-slate-400">Bu modül yakında hizmetinize sunulacak. HGB Bilişim  ekibiyle iletişime geçin.</p>
    </div>
@endsection
