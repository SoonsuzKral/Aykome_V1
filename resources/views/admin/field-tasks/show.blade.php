@extends('layouts.admin')

@section('page-heading', 'Saha Görevi — ' . $application->application_no)

@section('content')
    @php
        $statusLabels = [
            'pending'     => ['label' => 'Beklemede',   'class' => 'bg-amber-100 text-amber-700'],
            'in_progress' => ['label' => 'Devam ediyor','class' => 'bg-blue-100 text-blue-700'],
            'completed'   => ['label' => 'Tamamlandı',  'class' => 'bg-emerald-100 text-emerald-700'],
        ];
        $badge = $statusLabels[$fieldTask->status] ?? ['label' => $fieldTask->status, 'class' => 'bg-slate-100 text-slate-700'];
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Saha Görevi #{{ $fieldTask->id }}</h1>
            <p class="text-sm text-slate-500">
                Başvuru:
                <a href="{{ route('admin.applications.show', $application) }}" class="text-emerald-700 hover:underline font-medium">{{ $application->application_no }}</a>
                · {{ $application->institution?->name }}
            </p>
        </div>
        <a href="{{ route('admin.applications.show', $application) }}" class="text-sm text-emerald-700 hover:underline">← Başvuruya dön</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Sol: adım fotoğrafları --}}
        <div class="space-y-6 lg:col-span-2">
            @foreach($steps as $stepKey => $stepLabel)
                @php
                    $stepPhotos = $mediaByStep->get($stepKey, collect());
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">{{ $stepLabel }}</h2>
                        <span class="text-xs text-slate-400">{{ $stepPhotos->count() }} fotoğraf</span>
                    </div>

                    @if($stepPhotos->isNotEmpty())
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach($stepPhotos as $media)
                                <div class="group relative">
                                    <a href="{{ Storage::url($media->image_path) }}" target="_blank" rel="noopener">
                                        <img src="{{ Storage::url($media->image_path) }}"
                                             alt="{{ $media->caption ?? $stepLabel }}"
                                             class="h-32 w-full rounded-lg object-cover border border-slate-200 group-hover:opacity-90 transition">
                                    </a>
                                    @if($media->caption)
                                        <p class="mt-1 text-xs text-slate-500 truncate">{{ $media->caption }}</p>
                                    @endif
                                    <p class="text-xs text-slate-400">{{ $media->created_at->format('d.m.Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-400 italic">Bu adım için henüz fotoğraf yüklenmedi.</p>
                    @endif

                    @if($fieldTask->status !== 'completed')
                        <form method="POST"
                              action="{{ route('admin.field-tasks.media.store', $fieldTask) }}"
                              enctype="multipart/form-data"
                              class="mt-4 border-t border-slate-100 pt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="step" value="{{ $stepKey }}">
                            <div>
                                <label class="block text-xs font-medium text-slate-600">Fotoğraf ekle (JPG/PNG/WebP, max 10 MB)</label>
                                <input type="file"
                                       name="photo"
                                       required
                                       accept="image/jpeg,image/png,image/webp"
                                       class="mt-1 block w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600">Açıklama (opsiyonel)</label>
                                <input type="text"
                                       name="caption"
                                       maxlength="500"
                                       placeholder="Kısa not..."
                                       class="mt-1 block w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            </div>
                            <button type="submit"
                                    class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                Yükle
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Sağ: görev bilgisi + durum güncelle --}}
        <div class="space-y-6">

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Görev Bilgisi</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <dt class="text-slate-500 text-xs">Durum</dt>
                    <dd>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge['class'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </dd>
                    <dt class="text-slate-500 text-xs mt-2">Atanan Personel</dt>
                    <dd class="font-medium">{{ $fieldTask->assignee?->name ?? '—' }}</dd>
                    <dt class="text-slate-500 text-xs mt-2">Atayan</dt>
                    <dd>{{ $fieldTask->assigner?->name ?? '—' }}</dd>
                    <dt class="text-slate-500 text-xs mt-2">Termin</dt>
                    <dd>{{ $fieldTask->due_date?->format('d.m.Y') ?? '—' }}</dd>
                    <dt class="text-slate-500 text-xs mt-2">Atanma Tarihi</dt>
                    <dd>{{ $fieldTask->created_at->format('d.m.Y H:i') }}</dd>
                    @if($fieldTask->notes)
                        <dt class="text-slate-500 text-xs mt-2">Not</dt>
                        <dd class="text-slate-700">{{ $fieldTask->notes }}</dd>
                    @endif
                </dl>
            </div>

            @if($fieldTask->status !== 'completed')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-800">Durum Güncelle</h2>
                    <form method="POST"
                          action="{{ route('admin.field-tasks.status.update', $fieldTask) }}"
                          class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-600">Yeni durum</label>
                            <select name="status"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                <option value="pending"     {{ $fieldTask->status === 'pending'     ? 'selected' : '' }}>Beklemede</option>
                                <option value="in_progress" {{ $fieldTask->status === 'in_progress' ? 'selected' : '' }}>Devam ediyor</option>
                                <option value="completed"   {{ $fieldTask->status === 'completed'   ? 'selected' : '' }}>Tamamlandı</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full rounded-lg bg-emerald-700 py-2 text-sm text-white hover:bg-emerald-800">
                            Güncelle
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    Görev tamamlandı. Düzenleme kapalı.
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">Adım Özeti</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($steps as $stepKey => $stepLabel)
                        @php $count = $mediaByStep->get($stepKey, collect())->count(); @endphp
                        <li class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $stepLabel }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $count > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $count }} fotoğraf
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
@endsection
