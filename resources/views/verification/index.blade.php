<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Eyyübiye Belediyesi AYKOME — EBYS Elektronik Belge Doğrulama Portalı. Evrağınızın orijinalliğini güvenli şekilde doğrulayın.">
    <title>EBYS Elektronik Belge Doğrulama Portalı | Eyyübiye Belediyesi AYKOME</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-mesh {
            background:
                radial-gradient(at 20% 0%, rgba(250, 96, 1, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 20%, rgba(2, 224, 251, 0.10) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.07) 0px, transparent 55%),
                #f8fafc;
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex flex-col text-slate-800 antialiased">

    {{-- Üst Şerit --}}
    <header class="bg-slate-900 text-slate-300">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-3 text-xs">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-[#02E0FB]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                <span class="font-semibold tracking-wide">e-Devlet Kilitli Resmi Belge</span>
            </div>
            <span class="text-slate-400">T.C. Eyyübiye Belediyesi · AYKOME</span>
        </div>
    </header>

    <main class="flex flex-1 flex-col items-center justify-center px-4 py-14">
        {{-- Kurum Amblemi --}}
        <div class="mb-6 flex flex-col items-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-[#FA6001] to-[#02E0FB] p-[3px] shadow-lg shadow-orange-500/20">
                <div class="flex h-full w-full flex-col items-center justify-center rounded-[13px] bg-slate-950 text-[#02E0FB]">
                    <svg class="h-9 w-9" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 010 1.06l-8.689 8.69a.75.75 0 01-1.06 0l-8.69-8.69a.75.75 0 010-1.06l8.69-8.69zm-1.97 3.03a.75.75 0 00-.75.75v8.76a.75.75 0 001.5 0V7.62a.75.75 0 00-.75-.75zm4.5 0a.75.75 0 00-.75.75v8.76a.75.75 0 001.5 0V7.62a.75.75 0 00-.75-.75z" clip-rule="evenodd"/></svg>
                    <span class="mt-0.5 text-[9px] font-black tracking-[0.2em] text-white">AYKOME</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">T.C. Şanlıurfa Büyükşehir Belediyesi · Eyyübiye</p>
        </div>

        <h1 class="text-center text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
            EBYS Elektronik Belge<br class="sm:hidden"> Doğrulama Portalı
        </h1>
        <p class="mt-3 max-w-xl text-center text-sm text-slate-500">
            Elinizdeki evrakın üzerinde yer alan <b class="text-slate-700">BELGE DOĞRULAMA KODU</b>'nu girin.
            Belgenin sistemimiz tarafından orijinal olarak üretilip üretilmediğini anında görün.
        </p>

        {{-- Sorgu Kartı --}}
        <div class="mt-8 w-full max-w-xl rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-xl shadow-slate-200/60 backdrop-blur">
            <form method="POST" action="{{ route('verify.check') }}" class="flex flex-col items-center gap-4">
                @csrf
                <label for="verification_code" class="text-sm font-bold text-slate-700">12 Haneli Doğrulama Kodunu Giriniz</label>
                <input
                    id="verification_code"
                    name="verification_code"
                    type="text"
                    value="{{ old('verification_code') }}"
                    required
                    autocomplete="off"
                    maxlength="20"
                    spellcheck="false"
                    placeholder="EYYB-XXXXXXXXXX"
                    class="w-full rounded-2xl border-2 border-slate-200 px-6 py-4 text-center text-xl font-mono font-bold uppercase tracking-[0.2em] text-slate-800 outline-none transition focus:border-[#02E0FB] focus:ring-4 focus:ring-cyan-100"
                >
                @error('verification_code')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-full rounded-2xl bg-blue-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 active:scale-[0.99]">
                    🔍 Sorgula
                </button>
            </form>
            <div class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Bu doğrulama ile kişisel verileriniz (TC, telefon) maskelenir; gizliliğiniz korunur.
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-xs text-slate-400">
        © {{ date('Y') }} Eyyübiye Belediyesi AYKOME — Altyapı Koordinasyon Merkezi · HGB Bilişim Ultra SaaS
    </footer>
</body>
</html>
