<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erişim Kısıtlandı — HGB Bilişim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-blob { 0%, 100% { transform: scale(1); opacity: .25; } 50% { transform: scale(1.1); opacity: .15; } }
        .blob-cyan { animation: pulse-blob 8s ease-in-out infinite; }
        .blob-orange { animation: pulse-blob 8s ease-in-out infinite 4s; }
        @keyframes lock-glow { 0%, 100% { filter: drop-shadow(0 0 10px #FA600177); } 50% { filter: drop-shadow(0 0 25px #FA6001); } }
        .lock-icon { animation: lock-glow 3s ease-in-out infinite; }
        body { background-color: #030712; }
    </style>
</head>
<body class="h-screen w-full m-0 flex items-center justify-center font-sans overflow-hidden antialiased">

    {{-- ── Background Glows ────────────────────────────────────── --}}
    <div class="blob-cyan absolute -top-20 -left-20 w-[400px] h-[400px] rounded-full bg-[#02E0FB] blur-[110px] opacity-20 pointer-events-none"></div>
    <div class="blob-orange absolute -bottom-20 -right-20 w-[400px] h-[400px] rounded-full bg-[#FA6001] blur-[110px] opacity-20 pointer-events-none"></div>

    {{-- ── Compact Glass Card ─────────────────────────────────── --}}
    <div class="relative z-10 w-full max-w-[420px] mx-4
                bg-white/[0.04] backdrop-blur-3xl
                border border-white/10 rounded-[2.5rem] p-8 text-center
                shadow-[0_25px_60px_rgba(0,0,0,0.7)]">

        {{-- ── Lock Section ── --}}
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center
                    rounded-2xl bg-[#FA6001]/10 border border-[#FA6001]/20">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="lock-icon h-8 w-8 text-[#FA6001]">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd"/>
            </svg>
        </div>

        <p class="text-[9px] font-bold uppercase tracking-[0.4em] text-[#02E0FB]/70 mb-2">HGB Bilişim AYKOME</p>
        <h1 class="text-white font-black text-xl md:text-2xl uppercase tracking-tighter leading-tight mb-4">SİSTEM ERİŞİMİ <span class="text-[#FA6001]">KILITLENDI</span></h1>

        <p class="text-slate-400 font-medium text-xs leading-relaxed mb-6 px-4">
            Lisans paketiniz sona erdi veya erişim kısıtlandı. 
            <br>Lütfen <span class="text-slate-200">HGB Bilişim</span> ile iletişime geçin.
        </p>

        {{-- ── Tiny Contact Info ── --}}
        <div class="rounded-2xl bg-black/30 border border-white/[0.05] p-4 text-left space-y-3 mb-6">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5"><svg class="h-4 w-4 text-[#02E0FB]" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884 10 9.882l7.997-3.998A2 2 0 0 0 16 4H4a2 2 0 0 0-1.997 1.884Z"/><path d="m18 8.118-8 4-8-4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.118Z"/></svg></div>
                <div class="text-xs text-white">destek@hgbilisim.com</div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5"><svg class="h-4 w-4 text-[#FA6001]" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 0 1 1-1h2.153a1 1 0 0 1 .986.836l.74 4.435a1 1 0 0 1-.54 1.06l-1.548.773a11.037 11.037 0 0 0 6.105 6.105l.774-1.548a1 1 0 0 1 1.059-.54l4.435.74a1 1 0 0 1 .836.986V17a1 1 0 0 1-1 1h-2C7.82 18 2 12.18 2 5V3Z"/></svg></div>
                <div class="text-[10px] text-white uppercase font-bold tracking-tight">HGB BİLİŞİM SİSTEMLERİ TİC. LTD. ŞTİ.</div>
            </div>
        </div>

        {{-- ── Buttons ── --}}
        <div class="space-y-3">
            <a href="mailto:destek@hgbilisim.com" class="w-full block bg-[#FA6001] text-white py-3.5 rounded-xl font-bold text-xs tracking-wider shadow-lg shadow-orange-950/20 hover:brightness-110 transition active:scale-95">LİSANS YENİLE</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-slate-500 py-2 text-[10px] font-bold uppercase hover:text-white transition">GÜVENLİ ÇIKIŞ YAP</button>
            </form>
        </div>

    </div>

    {{-- ── Footer Note ── --}}
    <p class="absolute bottom-6 text-[10px] text-slate-700 tracking-widest font-bold">HGB BİLİŞİM ULTRA SAAS v3.2</p>

</body>
</html>