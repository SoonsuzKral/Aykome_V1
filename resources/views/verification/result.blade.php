<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Doğrulama Sonucu | EBYS Elektronik Belge Doğrulama Portalı</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } };
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-mesh {
            background:
                radial-gradient(at 20% 0%, rgba(250, 96, 1, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 20%, rgba(2, 224, 251, 0.10) 0px, transparent 50%),
                #f8fafc;
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex flex-col text-slate-800 antialiased">

    <header class="bg-slate-900 text-slate-300">
        <div class="mx-auto flex max-w-2xl items-center justify-between px-6 py-3 text-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-[#02E0FB]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                <span class="font-semibold tracking-wide">EBYS Doğrulama Sonucu</span>
            </div>
            <a href="{{ route('verify.index') }}" class="font-semibold text-[#02E0FB] hover:text-white transition">← Yeni Sorgu</a>
        </div>
    </header>

    <main class="flex flex-1 items-center justify-center px-4 py-14">
        <div class="w-full max-w-xl">

            @if(empty($success))
                {{-- ❌ HATA / BULUNAMADI --}}
                <div class="rounded-3xl border border-rose-200 bg-white/95 p-8 text-center shadow-xl shadow-rose-100">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-3xl">❌</div>
                    <h1 class="mt-4 text-xl font-black text-rose-700">Doğrulama Başarısız</h1>
                    <p class="mt-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message ?? 'Belge doğrulanamadı.' }}</p>
                    <a href="{{ route('verify.index') }}" class="mt-6 inline-block w-full rounded-2xl bg-slate-900 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800">
                        Tekrar Dene
                    </a>
                </div>
            @else
                {{-- ✅ ORİJİNAL BELGE --}}
                <div class="rounded-3xl border border-emerald-300 bg-white/95 p-8 text-center shadow-xl shadow-emerald-100">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl">✅</div>
                    <h1 class="mt-4 text-2xl font-black text-emerald-700">{{ $message }}</h1>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-400">Bu belge sistemimizde kayıtlıdır</p>

                    {{-- Doğrulama Kodu --}}
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Belge Doğrulama Kodu</span>
                        <p class="mt-1 font-mono text-lg font-black tracking-widest text-emerald-800">{{ $application['verification_code'] }}</p>
                    </div>

                    {{-- Evrak Bilgileri (KVKK maskeli) --}}
                    <div class="mt-4 space-y-2.5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-left text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Evrak / Başvuru No</span>
                            <b class="text-slate-800">{{ $application['application_no'] }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">İzin / Başvuru Statüsü</span>
                            <b class="rounded-full {{ $application['is_cancelled'] ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }} px-3 py-1 text-xs">
                                {{ $application['status'] }}
                            </b>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Evrak Başlama Tarihi</span>
                            <b class="text-slate-800">{{ $application['start_date'] }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Evrak Bitiş Tarihi</span>
                            <b class="text-slate-800">{{ $application['end_date'] }}</b>
                        </div>
                        @if(!empty($application['excavation_reason']))
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Konu</span>
                            <b class="text-right text-slate-800">{{ $application['excavation_reason'] }}</b>
                        </div>
                        @endif
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Başvuru Sahibi</span>
                            <b class="text-slate-800">
                                @if(!empty($application['institution']) && $application['is_institution_application'])
                                    🏢 {{ $application['institution'] }}
                                @else
                                    {{ $application['applicant'] }}
                                @endif
                            </b>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">TC Kimlik No</span>
                            <b class="font-mono text-slate-800">{{ $application['national_id'] }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">İletişim</span>
                            <b class="font-mono text-slate-800">{{ $application['phone'] }}</b>
                        </div>
                    </div>

                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-700">
                        ⚠️ Bu ekran yalnızca <b>doğrulama</b> amaçlıdır; belge içeriği ve dosya indirme sunulmaz.
                        Kişisel veriler KVKK gereği maskelenmiş, kurumsal ad açık gösterilmiştir.
                    </p>
                </div>
            @endif

        </div>
    </main>

    <footer class="py-6 text-center text-xs text-slate-400">
        © {{ date('Y') }} Eyyübiye Belediyesi AYKOME — Altyapı Koordinasyon Merkezi
    </footer>
</body>
</html>
