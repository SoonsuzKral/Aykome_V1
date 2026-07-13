@extends('layouts.admin')

@section('page-heading', 'Görevlerim')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Bana Atanan Görevler</h1>
            <p class="text-sm text-slate-500 mt-0.5">Aktif ve geçmiş saha görevleriniz</p>
        </div>
        <div class="flex items-center gap-2">
            @php $total = $activeTasks->count(); @endphp
            @if($total > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 border border-amber-500/30 px-3 py-1 text-xs font-bold text-amber-600">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                    </span>
                    {{ $total }} aktif görev
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-medium text-slate-500">
                    Aktif görev yok
                </span>
            @endif
        </div>
    </div>

    {{-- Active Tasks --}}
    <section>
        <h2 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-[0.15em] text-slate-500">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
            Aktif Görevler
            <span class="ml-1 rounded-full bg-amber-500/15 px-1.5 py-0.5 text-amber-600 font-bold">{{ $activeTasks->count() }}</span>
        </h2>

        @if($activeTasks->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                <span class="text-3xl">✅</span>
                <p class="mt-2 text-sm font-medium text-slate-500">Tüm görevler tamamlandı!</p>
                <p class="text-xs text-slate-400 mt-1">Şu an bekleyen göreviniz bulunmuyor.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($activeTasks as $task)
                    @php
                        $isInProgress = $task->status === 'in_progress';
                        $app = $task->application;
                        $stageDone = collect([1,2,3])->filter(fn($n) => $task->{"stage_{$n}_status"} === 'done')->count();
                    @endphp
                    <div class="group rounded-2xl border {{ $isInProgress ? 'border-blue-500/30 bg-blue-500/5' : 'border-slate-200 bg-white' }} p-4 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    @if($isInProgress)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-500/15 border border-blue-500/30 px-2 py-0.5 text-[10px] font-bold text-blue-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            Devam Ediyor
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-600">
                                            ⏳ Beklemede
                                        </span>
                                    @endif
                                    @if($app)
                                        <span class="rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[10px] font-mono font-semibold text-slate-600">
                                            {{ $app->application_no }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-sm font-semibold text-slate-800 truncate">
                                    {{ $app?->address_text ?? '—' }}
                                </p>

                                @if($task->due_date)
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Termin: <span class="{{ $task->due_date->isPast() ? 'text-red-500 font-semibold' : '' }}">{{ $task->due_date->format('d.m.Y') }}</span>
                                    </p>
                                @endif

                                {{-- Stage progress --}}
                                <div class="mt-3 flex items-center gap-1.5">
                                    @foreach([1 => '🔍', 2 => '⛏️', 3 => '🏗️'] as $num => $icon)
                                        @php $done = $task->{"stage_{$num}_status"} === 'done'; @endphp
                                        <div class="flex items-center gap-1 rounded-lg {{ $done ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-600' : 'bg-slate-100 border-slate-200 text-slate-400' }} border px-2 py-1 text-[10px] font-semibold">
                                            <span>{{ $icon }}</span>
                                            <span>{{ $done ? 'Tamam' : 'Bekliyor' }}</span>
                                        </div>
                                    @endforeach
                                    <span class="ml-1 text-[10px] text-slate-400">{{ $stageDone }}/3 aşama</span>
                                </div>
                            </div>

                            <a href="{{ route('admin.field-tasks.inspect', $task) }}"
                               class="flex-shrink-0 rounded-xl {{ $isInProgress ? 'bg-blue-600 hover:bg-blue-700' : 'bg-slate-800 hover:bg-slate-700' }} px-4 py-2.5 text-xs font-bold text-white shadow transition active:scale-95">
                                {{ $isInProgress ? 'Devam Et' : 'Başla' }}
                                <svg class="ml-1 inline h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Completed Tasks --}}
    <section>
        <h2 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-[0.15em] text-slate-500">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Geçmiş İşlerim
            <span class="ml-1 rounded-full bg-emerald-500/15 px-1.5 py-0.5 text-emerald-600 font-bold">{{ $completedTasks->count() }}</span>
        </h2>

        @if($completedTasks->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-8 text-center">
                <p class="text-sm text-slate-400">Henüz tamamlanmış görev bulunmuyor.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm divide-y divide-slate-100">
                @foreach($completedTasks as $task)
                    @php $app = $task->application; @endphp
                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors">
                        <span class="flex-shrink-0 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700 truncate">{{ $app?->address_text ?? '—' }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                @if($app)
                                    <span class="font-mono">{{ $app->application_no }}</span>
                                    <span>·</span>
                                @endif
                                {{ $task->updated_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                        <a href="{{ route('admin.field-tasks.inspect', $task) }}" class="flex-shrink-0 text-xs font-medium text-cyan-600 hover:underline">
                            Görüntüle
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>
@endsection
