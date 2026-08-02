@extends('layouts.admin')

@section('page-heading', 'Taslak / Şablon Yönetimi')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">📝 Taslak / Şablon Yönetimi</h1>
        <p class="mt-1 text-sm text-slate-500">Belge şablonlarını Microsoft Word (A4) veya Excel (hücre tablosu) editörüyle düzenleyin. Kaydedilen global şablon, başvuruya özel taslak bulunmayan tüm PDF çıktılarında kullanılır.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($types as $t)
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-cyan-300 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-50 to-orange-50 text-2xl">{{ $t['icon'] }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-800">{{ $t['label'] }}</h2>
                            <span class="rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $t['editor'] === 'word' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $t['editor'] === 'word' ? 'Word (A4)' : 'Excel (Tablo)' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $t['desc'] }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            @if($t['hasTemplate'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    ● Global şablon kayıtlı
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200">
                                    ○ Blade varsayılanı kullanılıyor
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ $t['editUrl'] }}"
                   class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    <span>{{ $t['editor'] === 'word' ? '📄' : '📊' }}</span>
                    {{ $t['hasTemplate'] ? 'Şablonu Düzenle' : 'Şablon Oluştur' }}
                </a>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-2 text-sm font-bold text-slate-800">ℹ️ Nasıl çalışır?</h2>
        <ul class="space-y-1.5 text-xs text-slate-500">
            <li>• <b>Global şablon</b>: Tüm başvuruların PDF'inde, o başvuruya özel taslak yoksa buradaki içerik basılır.</li>
            <li>• <b>Başvuruya özel taslak</b>: Başvuru detayındaki "✏️ Taslak" butonuyla yalnızca o başvuru için düzenlenir ve öncelikli kullanılır.</li>
            <li>• <b>Word editör</b>: Üst Yazı ve Ön Kazı belgeleri, ortada A4 kağıt ve üstte Word şeridiyle düzenlenir.</li>
            <li>• <b>Excel editör</b>: Ruhsat ve Tahakkuk tablolarında her hücreye tıklanıp içerik değiştirilebilir.</li>
            <li>• Düzenleme yapılmamışsa sistem, mevcut Blade şablonlarını (varsayılan) kullanmaya devam eder.</li>
        </ul>
    </div>
@endsection
