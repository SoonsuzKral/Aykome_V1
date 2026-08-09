@extends('layouts.admin')

@section('page-heading', 'Makam Masası')

@section('content')
    @php
        $user = auth()->user();
        $roleNames = $user->roles->pluck('name')->map(function ($r) {
            return ucwords(str_replace(['-', '_'], ' ', $r));
        })->join(', ');
    @endphp

    <div class="mb-6 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">🏛️ Başkan Modu / Makam Masası</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Sayın <b class="text-slate-800">{{ $user->name }}</b> — sistemde en yetkili makamsınız.
                    Aşağıdaki bekleyen imzalar onayınızı bekliyor.
                </p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach($user->roles as $role)
                        <span class="rounded-full bg-slate-900 px-2.5 py-0.5 text-[11px] font-bold text-white">{{ $role->name }}</span>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3">
                <div class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center shadow-sm">
                    <p class="text-2xl font-black text-amber-600">{{ $pending->count() }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Bekleyen İmza</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center shadow-sm">
                    <p class="text-2xl font-black text-emerald-600">{{ $recentApprovals->count() }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Son Onayım</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ÖNÜMDEKİ BEKLEYEN İMZALAR --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-800">📥 Önümdeki Bekleyen İmzalar</h2>
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">{{ $pending->count() }} başvuru</span>
        </div>

        @if($pending->isEmpty())
            <div class="px-5 py-14 text-center">
                <p class="text-3xl">🎉</p>
                <p class="mt-2 text-sm font-semibold text-slate-600">Harika, onay bekleyen imzanız yok.</p>
                <p class="text-xs text-slate-400">Yeni bir alt kurum başvurusu gönderildiğinde burada belirir.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Başvuru</th>
                        <th class="px-3 py-3">Kurum</th>
                        <th class="px-3 py-3">Adres</th>
                        <th class="px-3 py-3">Şu Anki Adım</th>
                        <th class="px-3 py-3">Bekleme</th>
                        <th class="px-5 py-3 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($pending as $app)
                        <tr class="align-top hover:bg-amber-50/40">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.makam.show', $app) }}" class="font-bold text-slate-800 hover:text-cyan-700">
                                    #{{ $app->application_no }}
                                </a>
                                <p class="text-[11px] text-slate-400">{{ $app->creator?->name }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-bold"
                                      style="background: {{ $app->institution?->color_code }}18; color: {{ $app->institution?->color_code }}">
                                    {{ $app->institution?->name }}
                                </span>
                            </td>
                            <td class="max-w-[220px] truncate px-3 py-3 text-xs text-slate-600" title="{{ $app->address_text }}">{{ $app->address_text }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-md bg-cyan-50 px-2 py-1 text-[11px] font-bold text-cyan-700">
                                    {{ $engine->stageLabel($app->approval_stage) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-500">
                                {{ optional($app->created_at)->diffForHumans() }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-col items-end gap-1.5">
                                    <form method="POST" action="{{ route('admin.makam.onayla', $app) }}"
                                          onsubmit="return confirm('#{{ $app->application_no }} başvurusunu ONAYLIYOR, e-imzalayıp gönderiyorsunuz. Emin misiniz?')">
                                        @csrf
                                        <button type="submit"
                                                class="flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-700">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            ONAYLIYORUM E-İMZAYLA &amp; GÖNDER
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.makam.show', $app) }}"
                                       class="text-[11px] font-semibold text-cyan-700 hover:text-cyan-800">Dosyayı Aç</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- PDF ERİŞİMİ — hiyerarşiden geçer, makama açıktır --}}
    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 px-5 py-4">
        <p class="text-xs text-emerald-800">
            📜 <b>PDF erişimi:</b> Makam rolü hiyerarşide en üstte olduğu için tüm evraklar (Ön Kazı İzin Belgesi,
            Ruhsat, Metraj, Tahakkuk) indirmeye açıktır. Belgeler dosya detayından (Dosyayı Aç) aşamasına göre listelenir.
        </p>
    </div>

    {{-- Son Onayladıklarım --}}
    @if($recentApprovals->isNotEmpty())
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-800">✍️ Son Onayladıklarım</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($recentApprovals as $log)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <a href="{{ route('admin.makam.show', $log->application_id) }}" class="font-semibold text-slate-700 hover:text-cyan-700">#{{ $log->application?->application_no }}</a>
                        <span class="ml-2 text-xs text-slate-500">{{ $log->message }}</span>
                    </div>
                    <span class="text-xs text-slate-400">{{ $log->created_at?->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
@endsection
