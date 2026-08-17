@extends('layouts.admin')

@section('title', 'E-İmza Sürüm Yönetimi')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-6">

    {{-- ── Başlık ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 ring-1 ring-cyan-200">
                        <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    </span>
                    E-İmza Sürüm Yönetimi
                </span>
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Masaüstü E-İmza uygulamasının kurulum paketi burada yayınlanır. Kullanıcılar üstteki
                <span class="font-semibold text-cyan-600">⬇️ E-İmza İndir</span> butonundan indirir; kurulu uygulamalar
                aynı klasördeki <code class="rounded bg-slate-100 px-1">latest.yml</code> ile <strong>otomatik güncellenir</strong>.
            </p>
        </div>
        <span class="w-fit rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-orange-600 ring-1 ring-orange-200">SA Yalnız</span>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('warning'))
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        {{ session('warning') }}
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- ── Yayın durumu ────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border {{ $health['ok'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }} p-5">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $health['ok'] ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                @if($health['ok'])
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                @else
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                @endif
            </span>
            <div class="min-w-0 flex-1 space-y-1">
                <p class="text-sm font-semibold {{ $health['ok'] ? 'text-emerald-800' : 'text-amber-900' }}">
                    Otomatik güncelleme: {{ $health['ok'] ? 'HAZIR' : 'EKSİK' }}
                    @if($manifest && $manifest['version'])
                        <span class="ml-1 rounded-full bg-white/70 px-2 py-0.5 text-[11px] font-bold tracking-wide text-slate-700 ring-1 ring-slate-200">v{{ $manifest['version'] }}</span>
                    @endif
                </p>
                @if($health['issues'])
                <ul class="list-inside list-disc space-y-0.5 text-xs text-amber-800">
                    @foreach($health['issues'] as $issue)<li>{{ $issue }}</li>@endforeach
                </ul>
                @endif
                <p class="pt-1 text-xs text-slate-600">
                    Feed adresi (masaüstü uygulaması bunu okur):
                    <code class="break-all rounded bg-white px-1.5 py-0.5 ring-1 ring-slate-200">{{ $feedUrl }}</code>
                </p>
                <p class="text-xs text-slate-500">
                    Uygulama bu adresi kurulum sihirbazında kayıtlı panel adresinden kendisi türetir —
                    her kurum kendi sunucusundan güncellenir, ayrıca yapılandırma gerekmez.
                </p>
            </div>
        </div>
    </div>

    {{-- ── Yükleme formu ───────────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('admin.e-imza-release.store') }}" enctype="multipart/form-data"
          class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
            <svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v9"/></svg>
            Yeni Sürüm Yükle
        </h2>

        <p class="rounded-lg bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-600">
            <strong>aykome-e-imza/dist/</strong> klasöründeki <em>aynı derlemeye ait</em> üç dosyayı seçin.
            Dosya adlarını değiştirmeyin — <code class="rounded bg-white px-1">latest.yml</code> içindeki ad ve sha512
            özeti kurulum dosyasıyla birebir eşleşmek zorundadır (yükleme sırasında denetlenir).
        </p>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Kurulum Dosyası (.exe) <span class="text-rose-500">*</span>
                </label>
                <input type="file" name="setup" accept=".exe" required
                    class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-cyan-700 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                <p class="mt-1 text-[11px] text-slate-400">Örn. AykomeEImzaSetup-1.0.0.exe</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Manifest (latest.yml)</label>
                <input type="file" name="manifest" accept=".yml,.yaml"
                    class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-cyan-700 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                <p class="mt-1 text-[11px] text-slate-400">Otomatik güncelleme için gerekli.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Blockmap (.exe.blockmap)</label>
                <input type="file" name="blockmap" accept=".blockmap"
                    class="w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-cyan-700 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/20">
                <p class="mt-1 text-[11px] text-slate-400">Fark indirmesi (daha hızlı güncelleme) için.</p>
            </div>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
            Bu sunucunun PHP yükleme sınırları: <strong>upload_max_filesize = {{ $phpLimits['upload_max_filesize'] }}</strong>,
            <strong>post_max_size = {{ $phpLimits['post_max_size'] }}</strong>. Kurulum paketi ~85 MB olduğundan bu değerler
            küçükse yükleme sessizce başarısız olur. Bu durumda dosyaları sunucuya kopyalayıp şu komutu çalıştırın:
            <code class="mt-1 block break-all rounded bg-white px-2 py-1 ring-1 ring-amber-200">php artisan eimza:publish</code>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-400/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                Sürümü Yayınla
            </button>
        </div>
    </form>

    {{-- ── Yayındaki dosyalar ──────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <svg class="h-4 w-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                Yayındaki Dosyalar
            </h2>
            @if($files)
            <a href="{{ route('admin.e-imza-release.download') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                ⬇️ Kurulum dosyasını indir
            </a>
            @endif
        </div>

        @if(! $files)
        <p class="px-6 py-10 text-center text-sm text-slate-400">Henüz sürüm yayınlanmamış.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3">Dosya</th>
                        <th class="px-6 py-3">Boyut</th>
                        <th class="px-6 py-3">Tarih</th>
                        <th class="px-6 py-3 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($files as $f)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-6 py-3">
                            <span class="font-mono text-xs text-slate-800">{{ $f['name'] }}</span>
                            @if($manifest && $f['name'] === $manifest['path'])
                                <span class="ml-2 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-200">Yayında</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-3 tabular-nums text-slate-600">
                            {{ $f['size'] >= 1048576 ? round($f['size'] / 1048576, 2) . ' MB' : round($f['size'] / 1024, 1) . ' KB' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-3 text-xs text-slate-500">
                            {{ \Carbon\Carbon::createFromTimestamp($f['modified'])->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            <form method="POST" action="{{ route('admin.e-imza-release.destroy') }}" class="inline"
                                  onsubmit="return confirm('{{ $f['name'] }} silinecek. Emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="name" value="{{ $f['name'] }}">
                                <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Sil</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Nasıl sürüm çıkarılır ───────────────────────────────────────────── --}}
    <details class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">
        <summary class="cursor-pointer text-sm font-semibold text-slate-700">Yeni sürüm nasıl çıkarılır?</summary>
        <ol class="mt-3 list-inside list-decimal space-y-1.5 text-xs leading-relaxed">
            <li><code class="rounded bg-slate-100 px-1">aykome-e-imza/package.json</code> içindeki <strong>version</strong> değerini yükseltin (örn. 1.0.0 → 1.0.1).</li>
            <li><code class="rounded bg-slate-100 px-1">cd aykome-e-imza &amp;&amp; npm run build:win</code></li>
            <li><code class="rounded bg-slate-100 px-1">dist/</code> klasöründeki <strong>.exe</strong>, <strong>latest.yml</strong> ve <strong>.exe.blockmap</strong> dosyalarını buradan yükleyin (veya sunucuda <code class="rounded bg-slate-100 px-1">php artisan eimza:publish</code>).</li>
            <li>Kurulu uygulamalar açılıştan ~20 sn sonra ve 6 saatte bir denetler; tepsi (tray) menüsünden elle de denetlenebilir.</li>
        </ol>
    </details>



</div>
@endsection
