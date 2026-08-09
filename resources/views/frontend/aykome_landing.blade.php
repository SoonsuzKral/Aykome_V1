<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AYKOME — Altyapı Kazı İzin ve Yönetim Sistemi. Harita çizimi, anlık bildirimler, e-ruhsat ve saha denetimi tek platformda.">
    <title>AYKOME — Altyapı Kazı Yönetimini Yeniden Keşfedin | HGB Bilişim</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Tailwind CDN (standalone, no build needed for public landing) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        cyan: { brand: '#02E0FB' },
                        orange: { brand: '#FA6001' },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.6s ease forwards',
                        'fade-in': 'fadeIn 0.8s ease forwards',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'scroll': 'scroll 30s linear infinite',
                    },
                    keyframes: {
                        fadeUp: { '0%': { opacity: '0', transform: 'translateY(30px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        float: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-12px)' } },
                        scroll: { '0%': { transform: 'translateX(0)' }, '100%': { transform: 'translateX(-50%)' } },
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text { background: linear-gradient(135deg, #02E0FB 0%, #FA6001 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .gradient-border { background: linear-gradient(135deg, #02E0FB20, #FA600120); border: 1px solid rgba(2,224,251,0.2); }
        .glow-cyan { box-shadow: 0 0 40px rgba(2,224,251,0.15), 0 0 80px rgba(2,224,251,0.05); }
        .glow-orange { box-shadow: 0 0 40px rgba(250,96,1,0.15), 0 0 80px rgba(250,96,1,0.05); }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
        .orb-cyan { background: radial-gradient(circle, rgba(2,224,251,0.25) 0%, transparent 70%); }
        .orb-orange { background: radial-gradient(circle, rgba(250,96,1,0.2) 0%, transparent 70%); }
        .hero-grid { background-image: linear-gradient(rgba(2,224,251,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(2,224,251,0.04) 1px, transparent 1px); background-size: 50px 50px; }
        .stat-card:hover { transform: translateY(-4px); transition: all 0.3s ease; }
        .feature-card:hover { border-color: rgba(2,224,251,0.4); transform: translateY(-2px); transition: all 0.3s ease; }
        .logo-track { display: flex; gap: 3rem; animation: scroll 30s linear infinite; width: max-content; }
        .scroll-hidden::-webkit-scrollbar { display: none; }
        [data-aos] { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
        [data-aos].aos-animate { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 antialiased overflow-x-hidden">

{{-- ═══════════════════════════════════════════════════════════════
     NAVİGASYON
═══════════════════════════════════════════════════════════════ --}}
<nav class="fixed top-0 left-0 right-0 z-50 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-[#02E0FB] to-[#FA6001] text-sm font-black text-white shadow-lg">M</span>
                <div>
                    <span class="font-black text-white tracking-tight">AYKOME</span>
                    <span class="hidden sm:inline text-slate-500 text-xs ml-2">by HGB Bilişim</span>
                </div>
            </div>

            {{-- Desktop Nav --}}
            <div class="hidden md:flex items-center gap-8 text-sm text-slate-400">
                <a href="#ozellikler" class="hover:text-white transition">Özellikler</a>
                <a href="#nasil-calisir" class="hover:text-white transition">Nasıl Çalışır?</a>
                <a href="#moduller" class="hover:text-white transition">Modüller</a>
                <a href="#pro-moduller" class="hover:text-[#FA6001] transition font-semibold text-[#FA6001]/80">PRO</a>
                <a href="#istatistikler" class="hover:text-white transition">Rakamlar</a>
                <a href="#iletisim" class="hover:text-white transition">İletişim</a>
            </div>

            {{-- CTA --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="hidden sm:inline-flex text-sm text-slate-400 hover:text-white transition px-3 py-2">Giriş Yap</a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#02E0FB] to-[#02AFC6] px-4 py-2 text-sm font-semibold text-slate-900 shadow-lg hover:shadow-[0_0_20px_rgba(2,224,251,0.4)] transition-all duration-300">
                    Ücretsiz Dene
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- ═══════════════════════════════════════════════════════════════
     A. HERO SECTION
═══════════════════════════════════════════════════════════════ --}}
<section class="relative min-h-screen pt-16 flex items-center bg-slate-950 hero-grid overflow-hidden">
    {{-- Orbs --}}
    <div class="orb orb-cyan w-[600px] h-[600px] -top-32 -left-32 opacity-60"></div>
    <div class="orb orb-orange w-[500px] h-[500px] top-1/2 -right-48 opacity-40"></div>
    <div class="orb orb-cyan w-[300px] h-[300px] bottom-0 left-1/3 opacity-30"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            {{-- Sol: Metin --}}
            <div class="text-center lg:text-left">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/10 px-4 py-1.5 text-xs font-semibold text-[#02E0FB] mb-6">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#02E0FB] animate-pulse"></span>
                    Ultra SaaS v3 — Canlı Sistem
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-white mb-6">
                    Altyapı Kazı<br>
                    Yönetimini<br>
                    <span class="gradient-text">Yeniden Keşfedin.</span>
                </h1>

                <p class="text-lg text-slate-400 leading-relaxed mb-8 max-w-xl mx-auto lg:mx-0">
                    Harita vektör çizimi, anlık bildirimler, makbuz ve saha denetimi
                    <strong class="text-slate-200">tek bir akıllı bulut platformunda.</strong>
                    Belediyeler ve kurumlar için tasarlandı.
                </p>

                {{-- Butonlar --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-10">
                    <a href="{{ route('login') }}"
                       class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#02E0FB] to-[#02AFC6] px-8 py-4 text-base font-bold text-slate-900 shadow-2xl hover:shadow-[0_0_40px_rgba(2,224,251,0.5)] transition-all duration-300 hover:-translate-y-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Hemen Başla
                    </a>
                    <a href="#nasil-calisir"
                       class="group inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-700 bg-slate-900/60 px-8 py-4 text-base font-semibold text-slate-300 hover:border-[#02E0FB]/40 hover:text-white hover:bg-slate-800/60 transition-all duration-300">
                        <svg class="h-5 w-5 text-[#FA6001]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        Demoyu İzle
                    </a>
                </div>

                {{-- Trust badges --}}
                <div class="flex flex-wrap items-center gap-4 justify-center lg:justify-start">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        SOC 2 Uyumlu
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        KVKK Uyumlu
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        %99.9 Uptime SLA
                    </div>
                </div>
            </div>

            {{-- Sağ: Mockup --}}
            <div class="relative flex items-center justify-center animate-float">
                {{-- Dış glow --}}
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-[#02E0FB]/20 to-[#FA6001]/10 blur-3xl"></div>

                {{-- Dashboard mockup kartı --}}
                <div class="relative w-full max-w-lg rounded-2xl border border-slate-700/60 bg-slate-900/90 shadow-2xl backdrop-blur-sm overflow-hidden">
                    {{-- Mockup top bar --}}
                    <div class="flex items-center gap-2 border-b border-slate-700/50 px-4 py-3 bg-slate-950/80">
                        <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-yellow-500/70"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-emerald-500/70"></div>
                        <div class="ml-4 flex-1 rounded-md bg-slate-800/60 px-3 py-1 text-xs text-slate-500">aykome.belediye.gov.tr/admin/map</div>
                    </div>

                    {{-- Harita Mockup --}}
                    <div class="relative h-72 bg-gradient-to-br from-slate-900 to-slate-800 overflow-hidden">
                        {{-- Fake harita grid --}}
                        <svg class="absolute inset-0 w-full h-full opacity-20" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                                    <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#02E0FB" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid)" />
                        </svg>

                        {{-- Sokak çizgileri --}}
                        <svg class="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
                            {{-- Ana yollar --}}
                            <line x1="0" y1="80" x2="100%" y2="80" stroke="#334155" stroke-width="8"/>
                            <line x1="0" y1="160" x2="100%" y2="160" stroke="#334155" stroke-width="6"/>
                            <line x1="0" y1="230" x2="100%" y2="230" stroke="#334155" stroke-width="8"/>
                            <line x1="80" y1="0" x2="80" y2="100%" stroke="#334155" stroke-width="6"/>
                            <line x1="200" y1="0" x2="200" y2="100%" stroke="#334155" stroke-width="8"/>
                            <line x1="320" y1="0" x2="320" y2="100%" stroke="#334155" stroke-width="5"/>
                            <line x1="420" y1="0" x2="420" y2="100%" stroke="#334155" stroke-width="6"/>
                            {{-- Yol içleri (açık) --}}
                            <line x1="0" y1="80" x2="100%" y2="80" stroke="#475569" stroke-width="6"/>
                            <line x1="0" y1="160" x2="100%" y2="160" stroke="#475569" stroke-width="4"/>
                            <line x1="200" y1="0" x2="200" y2="100%" stroke="#475569" stroke-width="6"/>
                            {{-- Bloklar --}}
                            <rect x="90" y="90" width="100" height="60" fill="#1e293b" rx="2"/>
                            <rect x="210" y="90" width="100" height="60" fill="#1e293b" rx="2"/>
                            <rect x="330" y="90" width="80" height="60" fill="#1e293b" rx="2"/>
                            <rect x="90" y="170" width="100" height="50" fill="#1e293b" rx="2"/>
                            <rect x="210" y="170" width="100" height="50" fill="#1e293b" rx="2"/>
                            <rect x="90" y="10" width="100" height="60" fill="#1e293b" rx="2"/>
                            <rect x="210" y="10" width="100" height="60" fill="#1e293b" rx="2"/>

                            {{-- Kazı alanı Polygon (Cyan) --}}
                            <polygon points="120,100 220,95 240,130 210,155 110,148" fill="rgba(2,224,251,0.25)" stroke="#02E0FB" stroke-width="2.5" stroke-linejoin="round"/>

                            {{-- Polygon köşe noktaları --}}
                            <circle cx="120" cy="100" r="5" fill="#02E0FB" opacity="0.9"/>
                            <circle cx="220" cy="95" r="5" fill="#02E0FB" opacity="0.9"/>
                            <circle cx="240" cy="130" r="5" fill="#02E0FB" opacity="0.9"/>
                            <circle cx="210" cy="155" r="5" fill="#02E0FB" opacity="0.9"/>
                            <circle cx="110" cy="148" r="5" fill="#02E0FB" opacity="0.9"/>

                            {{-- İkinci polygon (Orange - başka kurum) --}}
                            <polygon points="340,100 415,105 420,150 345,148" fill="rgba(250,96,1,0.2)" stroke="#FA6001" stroke-width="2" stroke-linejoin="round"/>

                            {{-- Marker 1 --}}
                            <circle cx="175" cy="127" r="10" fill="#02E0FB" opacity="0.9"/>
                            <text x="175" y="131" text-anchor="middle" fill="white" font-size="9" font-weight="bold">✓</text>

                            {{-- Marker 2 --}}
                            <circle cx="380" cy="127" r="10" fill="#FA6001" opacity="0.9"/>
                            <text x="380" y="131" text-anchor="middle" fill="white" font-size="9" font-weight="bold">!</text>

                            {{-- Ölçü çizgisi --}}
                            <line x1="120" y1="165" x2="240" y2="165" stroke="#02E0FB" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.7"/>
                            <text x="180" y="178" text-anchor="middle" fill="#02E0FB" font-size="9" opacity="0.9">120 m</text>
                        </svg>

                        {{-- Sol panel mini --}}
                        <div class="absolute top-3 left-3 rounded-lg bg-slate-950/90 border border-slate-700/60 px-3 py-2 backdrop-blur-sm shadow-xl">
                            <p class="text-[9px] text-slate-400 uppercase tracking-wider mb-1">Aktif Kazı Alanları</p>
                            <p class="text-lg font-black text-[#02E0FB]">247</p>
                        </div>

                        {{-- SweetAlert notification mockup --}}
                        <div class="absolute top-3 right-3 rounded-xl bg-slate-950/95 border border-[#02E0FB]/40 px-3 py-2.5 backdrop-blur-sm shadow-2xl w-52 animate-pulse-slow">
                            <div class="flex items-start gap-2">
                                <div class="mt-0.5 h-4 w-4 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-2.5 w-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-white">Makbuz Yüklendi!</p>
                                    <p class="text-[9px] text-slate-400">AYK-2024-03847 onay bekleniyor</p>
                                </div>
                            </div>
                        </div>

                        {{-- Alt araç çubuğu --}}
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2 rounded-2xl bg-slate-950/95 border border-slate-700/60 px-4 py-2 backdrop-blur-sm shadow-xl">
                            <button class="h-6 w-6 rounded-md bg-[#02E0FB]/20 flex items-center justify-center text-[#02E0FB]">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                            <div class="h-4 w-px bg-slate-700"></div>
                            <span class="text-[9px] text-slate-400">Polygon</span>
                            <span class="text-[9px] text-slate-400">Çizgi</span>
                            <span class="text-[9px] text-slate-400">Nokta</span>
                            <div class="h-4 w-px bg-slate-700"></div>
                            <button class="h-6 w-6 rounded-md bg-[#FA6001]/20 flex items-center justify-center text-[#FA6001]">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Alt stat bar --}}
                    <div class="grid grid-cols-3 divide-x divide-slate-700/50 bg-slate-950/60 border-t border-slate-700/40">
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wide">Aktif İzin</p>
                            <p class="text-base font-black text-[#02E0FB]">1.284</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wide">Bugün</p>
                            <p class="text-base font-black text-[#FA6001]">+23</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wide">Saha Ekibi</p>
                            <p class="text-base font-black text-emerald-400">47</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scroll indicator --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 animate-bounce opacity-40">
        <span class="text-xs text-slate-500">Keşfet</span>
        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     B. LOGO SLIDER — Bize güvenen kurumlar
═══════════════════════════════════════════════════════════════ --}}
<section class="border-y border-slate-800/60 bg-slate-900/40 py-10 overflow-hidden">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">50'den Fazla Kurum ve Belediye Güveniyor</p>
    </div>
    <div class="overflow-hidden">
        <div class="logo-track">
            @php
                $logos = [
                    ['name' => 'Büyükşehir Bel.', 'color' => '#02E0FB'],
                    ['name' => 'TEDAŞ', 'color' => '#FA6001'],
                    ['name' => 'Türk Telekom', 'color' => '#02E0FB'],
                    ['name' => 'ŞUSKİ', 'color' => '#FA6001'],
                    ['name' => 'İGDAŞ', 'color' => '#02E0FB'],
                    ['name' => 'Çayırova Bel.', 'color' => '#FA6001'],
                    ['name' => 'Gebze Bel.', 'color' => '#02E0FB'],
                    ['name' => 'Kartepe Bel.', 'color' => '#FA6001'],
                    ['name' => 'Dilovası Bel.', 'color' => '#02E0FB'],
                    ['name' => 'Kandıra Bel.', 'color' => '#FA6001'],
                    ['name' => 'Körfez Bel.', 'color' => '#02E0FB'],
                    ['name' => 'İzmit Bel.', 'color' => '#FA6001'],
                    ['name' => 'Türkiye Finans', 'color' => '#02E0FB'],
                    ['name' => 'Boru Hattı A.Ş.', 'color' => '#FA6001'],
                    ['name' => 'Fiber İnternet', 'color' => '#02E0FB'],
                    ['name' => 'DoğalGaz Dağ.', 'color' => '#FA6001'],
                ];
            @endphp
            {{-- İlk set --}}
            @foreach($logos as $logo)
            <div class="flex items-center gap-3 rounded-xl border border-slate-700/40 bg-slate-900/60 px-5 py-3 min-w-max">
                <div class="h-6 w-6 rounded-md flex items-center justify-center" style="background: {{ $logo['color'] }}20">
                    <span class="text-xs font-black" style="color: {{ $logo['color'] }}">{{ substr($logo['name'], 0, 1) }}</span>
                </div>
                <span class="text-sm text-slate-400 whitespace-nowrap">{{ $logo['name'] }}</span>
            </div>
            @endforeach
            {{-- Kopyası (sonsuz kaydırma) --}}
            @foreach($logos as $logo)
            <div class="flex items-center gap-3 rounded-xl border border-slate-700/40 bg-slate-900/60 px-5 py-3 min-w-max">
                <div class="h-6 w-6 rounded-md flex items-center justify-center" style="background: {{ $logo['color'] }}20">
                    <span class="text-xs font-black" style="color: {{ $logo['color'] }}">{{ substr($logo['name'], 0, 1) }}</span>
                </div>
                <span class="text-sm text-slate-400 whitespace-nowrap">{{ $logo['name'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     C. 4 ADIMDA HAZIRSINIZ
═══════════════════════════════════════════════════════════════ --}}
<section id="nasil-calisir" class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block rounded-full bg-[#02E0FB]/10 border border-[#02E0FB]/30 px-4 py-1.5 text-xs font-semibold text-[#02AFC6] mb-4 uppercase tracking-widest">Süreç</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4">4 Adımda Hazırsınız</h2>
            <p class="text-slate-500 max-w-xl mx-auto">Onboarding'den sahaya çıkışa kadar tüm süreç dijital, hızlı ve izlenebilir.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $steps = [
                    [
                        'step' => '01',
                        'title' => 'Kurumunu Tanımla',
                        'desc' => 'Belediyeni veya kurumunu sisteme ekle. Kullanıcı rolleri, izinler ve kurum profili dakikalar içinde hazır.',
                        'color' => '#02E0FB',
                        'bg' => 'from-[#02E0FB]/10 to-transparent',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>',
                    ],
                    [
                        'step' => '02',
                        'title' => 'Haritada Çiz & Keşif Çıkar',
                        'desc' => 'Google Maps üzerinde polygon çiz, kazı alanını belirle. Sistem otomatik olarak metraj hesaplar ve keşif çıkarır.',
                        'color' => '#FA6001',
                        'bg' => 'from-[#FA6001]/10 to-transparent',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>',
                    ],
                    [
                        'step' => '03',
                        'title' => 'Ödeme & Makbuz Yükle',
                        'desc' => 'Hesaplanan bedeli öde, tahsilat makbuzunu sisteme yükle. Otomatik doğrulama ve anlık bildirimler devreye girer.',
                        'color' => '#02E0FB',
                        'bg' => 'from-[#02E0FB]/10 to-transparent',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 01-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>',
                    ],
                    [
                        'step' => '04',
                        'title' => 'E-Ruhsat Üret, Sahaya Çık',
                        'desc' => 'Dijital kazı ruhsatını PDF olarak indir. Saha ekibine görev ata, aşama takibini başlat ve denetimi tamamla.',
                        'color' => '#FA6001',
                        'bg' => 'from-[#FA6001]/10 to-transparent',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
                    ],
                ];
            @endphp

            @foreach($steps as $i => $step)
            <div class="relative group">
                {{-- Connector line --}}
                @if($i < 3)
                <div class="hidden lg:block absolute top-10 left-full w-full h-px bg-gradient-to-r from-slate-200 to-transparent z-10 -translate-y-px" style="width: calc(100% - 3rem); left: calc(100% - 1.5rem);"></div>
                @endif

                <div class="relative rounded-2xl border border-slate-100 bg-gradient-to-b {{ $step['bg'] }} p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                    {{-- Step number --}}
                    <div class="absolute -top-3 -right-3 h-8 w-8 rounded-full border-2 flex items-center justify-center text-xs font-black text-white" style="background: {{ $step['color'] }}; border-color: white;">{{ $step['step'] }}</div>

                    {{-- Icon --}}
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl" style="background: {{ $step['color'] }}15; color: {{ $step['color'] }}">
                        {!! $step['icon'] !!}
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     D. MODÜL / ÖZELLİK GRID
═══════════════════════════════════════════════════════════════ --}}
<section id="ozellikler" class="bg-slate-950 py-24 relative overflow-hidden">
    <div class="orb orb-cyan w-[500px] h-[500px] -top-32 right-0 opacity-30"></div>
    <div class="orb orb-orange w-[400px] h-[400px] bottom-0 left-0 opacity-20"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block rounded-full border border-[#FA6001]/30 bg-[#FA6001]/10 px-4 py-1.5 text-xs font-semibold text-[#FA6001] mb-4 uppercase tracking-widest">Modüller</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-4">Her İhtiyacın İçin Güçlü Araç</h2>
            <p class="text-slate-400 max-w-xl mx-auto">İzin başvurusundan saha denetimine, raporlamadan e-belgeye kadar eksiksiz altyapı.</p>
        </div>

        @php
            $features = [
                [
                    'title' => 'Harita Yönetimi',
                    'desc' => 'Google Maps tabanlı gerçek zamanlı kazı alan takibi. Polygon çizim, GeoJSON ihracat, kurum renk kodlaması ve canlı durum göstergeleri.',
                    'color' => '#02E0FB',
                    'badge' => 'Core',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>',
                    'tags' => ['Google Maps', 'GeoJSON', 'Polygon'],
                ],
                [
                    'title' => 'Saha Ekipleri Uygulaması',
                    'desc' => 'Mobil-first saha kontrol paneli. 3 aşamalı denetim (Kazı Öncesi / Sonrası / Zemin Onarım), fotoğraf yükleme ve anlık durum güncellemeleri.',
                    'color' => '#FA6001',
                    'badge' => 'Mobil',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>',
                    'tags' => ['3 Aşama', 'Fotoğraf', 'GPS'],
                ],
                [
                    'title' => 'E-Belge & Ruhsat',
                    'desc' => 'DomPDF ile resmi T.C. formatında kazı ruhsatı üretimi. Logo, imza, mühür, koordinat tablosu ve alan cinsi hesaplamaları dahil.',
                    'color' => '#02E0FB',
                    'badge' => 'PDF',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
                    'tags' => ['DomPDF', 'E-İmza', 'Resmi Format'],
                ],
                [
                    'title' => 'Dinamik Raporlama',
                    'desc' => 'Gerçek zamanlı dashboard KPI\'ları, DataTables server-side, PDF/CSV export, gelişmiş filtreler ve Chart.js görselleştirmeleri.',
                    'color' => '#FA6001',
                    'badge' => 'PRO',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
                    'tags' => ['Chart.js', 'PDF Export', 'CSV'],
                ],
                [
                    'title' => 'WebSocket Bildirimler',
                    'desc' => 'Laravel Reverb ile gerçek zamanlı push bildirimleri. Makbuz yükleme, görev tamamlama, durum değişimleri — sıfır gecikme.',
                    'color' => '#02E0FB',
                    'badge' => 'Realtime',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>',
                    'tags' => ['Reverb', 'Laravel Echo', 'Push'],
                ],
                [
                    'title' => 'Kurum İzolasyonu',
                    'desc' => 'Multi-tenant mimarisi. Her kurum yalnızca kendi verilerini görür. Rol bazlı erişim kontrolü, audit log ve KVKK uyumlu veri yönetimi.',
                    'color' => '#FA6001',
                    'badge' => 'Güvenlik',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
                    'tags' => ['KVKK', 'Rol Bazlı', 'Audit Log'],
                ],
            ];
        @endphp

        <div id="moduller" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($features as $feature)
            <div class="feature-card group relative rounded-2xl border border-slate-800/80 bg-slate-900/60 p-6 backdrop-blur-sm cursor-default" style="--feature-color: {{ $feature['color'] }}">
                {{-- Glow on hover --}}
                <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: radial-gradient(ellipse at top left, {{ $feature['color'] }}08, transparent 60%);"></div>

                <div class="relative">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $feature['color'] }}15; color: {{ $feature['color'] }}">
                            {!! $feature['icon'] !!}
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" style="background: {{ $feature['color'] }}20; color: {{ $feature['color'] }}">{{ $feature['badge'] }}</span>
                    </div>

                    <h3 class="text-base font-bold text-white mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed mb-4">{{ $feature['desc'] }}</p>

                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-2">
                        @foreach($feature['tags'] as $tag)
                        <span class="rounded-lg border border-slate-700/60 bg-slate-800/50 px-2.5 py-1 text-[11px] text-slate-500">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     D2. FEATURE DEEP DIVE 1 — Saha Ekiplerinin Sahadaki Gücü
         Layout: Sol Metin · Sağ Mobile Mockup
═══════════════════════════════════════════════════════════════ --}}
<section class="relative bg-slate-950 py-24 overflow-hidden">
    <div class="orb orb-orange w-[500px] h-[500px] -top-20 -right-40 opacity-25"></div>
    <div class="orb orb-cyan w-[300px] h-[300px] bottom-10 left-10 opacity-15"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            {{-- SOL: Metin --}}
            <div class="order-2 lg:order-1">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#FA6001]/30 bg-[#FA6001]/10 px-4 py-1.5 text-xs font-semibold text-[#FA6001] mb-6 uppercase tracking-widest">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    Saha Yönetimi
                </span>

                <h2 class="text-3xl sm:text-4xl lg:text-[2.6rem] font-black text-white leading-tight mb-5">
                    Sahayı Asla<br>
                    <span class="relative inline-block">
                        <span class="relative z-10" style="color: #FA6001">Gözden Kaçırmayın</span>
                        <span class="absolute bottom-1 left-0 w-full h-2 rounded-sm opacity-25" style="background:#FA6001"></span>
                    </span>
                </h2>

                <p class="text-slate-400 text-base leading-relaxed mb-8 max-w-lg">
                    Kazı alanlarını yerinde, anlık olarak denetleyin. Sahadan direkt fotoğraf çekin,
                    <strong class="text-slate-200">3 aşamalı iş emri doğrulamasını</strong> mobil tablet üzerinden tık tık yükleyin.
                    Sahacılar karmaşık panelleri değil, kendi <strong class="text-[#FA6001]">GodMode Saha Panelini</strong> kullanır.
                </p>

                {{-- Aşama pill'leri --}}
                <div class="flex flex-wrap gap-2 mb-8">
                    @foreach(['1. Kazı Öncesi', '2. Kazı Sonrası', '3. Zemin Onarım'] as $i => $stage)
                    <span class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold {{ $i === 0 ? 'border-[#02E0FB]/40 bg-[#02E0FB]/10 text-[#02E0FB]' : ($i === 1 ? 'border-[#FA6001]/40 bg-[#FA6001]/10 text-[#FA6001]' : 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400') }}">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ $stage }}
                    </span>
                    @endforeach
                </div>

                {{-- Checklist --}}
                <ul class="space-y-3.5">
                    @foreach([
                        ['Anlık Konum Alma', 'GPS koordinatları otomatik kaydedilir, alan dışına çıkılamaz.', '#02E0FB'],
                        ['Sahadan Fotoğraf Yükleme', 'Kamera açma + her aşamaya bağımlı medya akışı, belgeler otomatik arşivlenir.', '#FA6001'],
                        ['Süreç Onayı & Kilitleme', 'Aşama tamamlanmadan bir sonrakine geçilemez. Hiyerarşik onay zinciri aktif.', '#10b981'],
                    ] as $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 h-5 w-5 rounded-full flex items-center justify-center" style="background: {{ $item[2] }}20; color: {{ $item[2] }}">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <span class="text-sm font-semibold text-white">{{ $item[0] }}</span>
                            <span class="text-sm text-slate-500 ml-1.5">{{ $item[1] }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- SAĞ: Mobile Mockup --}}
            <div class="order-1 lg:order-2 flex justify-center">
                <div class="relative">
                    {{-- Glow arka plan --}}
                    <div class="absolute -inset-8 rounded-full bg-gradient-to-br from-[#FA6001]/20 to-[#02E0FB]/10 blur-3xl"></div>

                    {{-- Telefon çerçevesi --}}
                    <div class="relative w-64 sm:w-72 rounded-[2.5rem] border-4 border-slate-700/80 bg-slate-950 shadow-2xl overflow-hidden" style="box-shadow: 0 0 60px rgba(250,96,1,0.2), 0 25px 60px rgba(0,0,0,0.6)">
                        {{-- Telefon notch --}}
                        <div class="relative h-8 bg-slate-950 flex items-center justify-center">
                            <div class="w-20 h-5 rounded-b-2xl bg-slate-900 border border-slate-800/50 flex items-center justify-center gap-1">
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-700"></div>
                                <div class="h-2 w-8 rounded-full bg-slate-700"></div>
                            </div>
                        </div>

                        {{-- Telefon ekranı --}}
                        <div class="bg-slate-950 min-h-[500px] px-3 py-3">
                            {{-- App header --}}
                            <div class="flex items-center justify-between mb-3 px-1">
                                <div>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-widest">Görev</p>
                                    <p class="text-xs font-bold text-white">#GRV-2847 — Çayırova</p>
                                </div>
                                <span class="rounded-full bg-[#FA6001]/15 border border-[#FA6001]/40 px-2 py-0.5 text-[9px] font-bold text-[#FA6001] uppercase">Devam</span>
                            </div>

                            {{-- Harita mini --}}
                            <div class="rounded-xl overflow-hidden mb-3 h-28 bg-slate-800 relative">
                                <svg class="w-full h-full" viewBox="0 0 260 112" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="260" height="112" fill="#1e293b"/>
                                    <line x1="0" y1="40" x2="260" y2="40" stroke="#334155" stroke-width="6"/>
                                    <line x1="0" y1="80" x2="260" y2="80" stroke="#334155" stroke-width="5"/>
                                    <line x1="70" y1="0" x2="70" y2="112" stroke="#334155" stroke-width="5"/>
                                    <line x1="160" y1="0" x2="160" y2="112" stroke="#334155" stroke-width="5"/>
                                    <rect x="80" y="48" width="70" height="24" fill="#0f172a" rx="2"/>
                                    <rect x="170" y="48" width="60" height="24" fill="#0f172a" rx="2"/>
                                    <polygon points="85,50 150,47 155,68 82,70" fill="rgba(250,96,1,0.3)" stroke="#FA6001" stroke-width="2"/>
                                    <circle cx="118" cy="59" r="8" fill="#FA6001" opacity="0.9"/>
                                    <text x="118" y="63" text-anchor="middle" fill="white" font-size="8" font-weight="bold">★</text>
                                    <text x="118" y="88" text-anchor="middle" fill="#FA6001" font-size="8">Konum Aktif</text>
                                </svg>
                                <div class="absolute top-2 right-2 rounded-md bg-slate-950/80 px-1.5 py-0.5 text-[8px] text-emerald-400 font-semibold flex items-center gap-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>GPS
                                </div>
                            </div>

                            {{-- 3 Aşama listesi --}}
                            <div class="space-y-2 mb-3">
                                @php $stages = [
                                    ['Kazı Öncesi Denetim', 'Tamamlandı', 'emerald', '✓', true],
                                    ['Kazı Sonrası Denetim', 'Devam Ediyor', 'orange', '●', true],
                                    ['Zemin Onarım Onayı', 'Bekliyor', 'slate', '○', false],
                                ]; @endphp
                                @foreach($stages as $stage)
                                <div class="flex items-center gap-2.5 rounded-xl {{ $stage[4] ? 'border border-' . $stage[2] . '-500/30 bg-' . $stage[2] . '-500/10' : 'border border-slate-700/40 bg-slate-800/30' }} px-3 py-2">
                                    <span class="flex-shrink-0 text-xs font-bold {{ $stage[4] ? 'text-' . $stage[2] . '-400' : 'text-slate-600' }}">{{ $stage[3] }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-semibold {{ $stage[4] ? 'text-white' : 'text-slate-600' }} truncate">{{ $stage[0] }}</p>
                                        <p class="text-[9px] {{ $stage[2] === 'orange' ? 'text-[#FA6001]' : ($stage[2] === 'emerald' ? 'text-emerald-400' : 'text-slate-600') }}">{{ $stage[1] }}</p>
                                    </div>
                                    @if($stage[2] === 'emerald')
                                    <svg class="h-4 w-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    @elseif($stage[2] === 'orange')
                                    <div class="h-4 w-4 rounded-full border-2 border-[#FA6001] border-t-transparent animate-spin flex-shrink-0"></div>
                                    @endif
                                </div>
                                @endforeach
                            </div>

                            {{-- Fotoğraf yükle butonu --}}
                            <button class="w-full rounded-2xl bg-gradient-to-r from-[#FA6001] to-[#e55500] py-3 text-xs font-bold text-white shadow-lg flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/></svg>
                                Fotoğraf Çek & Yükle
                            </button>

                            {{-- Alt bar --}}
                            <div class="mt-3 flex justify-around">
                                @foreach(['Ana Sayfa', 'Görevler', 'Profil'] as $tab)
                                <button class="flex flex-col items-center gap-0.5 text-[9px] {{ $tab === 'Görevler' ? 'text-[#FA6001]' : 'text-slate-600' }}">
                                    <div class="h-4 w-4 rounded bg-current opacity-50"></div>
                                    {{ $tab }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Telefon home bar --}}
                        <div class="h-6 bg-slate-950 flex items-center justify-center">
                            <div class="w-24 h-1 rounded-full bg-slate-700"></div>
                        </div>
                    </div>

                    {{-- Floating badge --}}
                    <div class="absolute -bottom-4 -left-4 rounded-xl border border-emerald-500/30 bg-slate-900 px-3 py-2 shadow-xl">
                        <p class="text-[10px] text-slate-500 uppercase tracking-wide">Aktif Sahacı</p>
                        <p class="text-sm font-black text-emerald-400">47 Personel</p>
                    </div>
                    <div class="absolute -top-4 -right-4 rounded-xl border border-[#FA6001]/30 bg-slate-900 px-3 py-2 shadow-xl">
                        <p class="text-[10px] text-slate-500 uppercase tracking-wide">Bugün Tamamlanan</p>
                        <p class="text-sm font-black text-[#FA6001]">12 Aşama</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     D3. FEATURE DEEP DIVE 2 — Canlı ve Anlık Operasyon Bildirimleri
         Layout: Sol Notification Mockup · Sağ Metin
═══════════════════════════════════════════════════════════════ --}}
<section class="relative py-24 overflow-hidden" style="background: linear-gradient(180deg, #0f172a 0%, #030712 100%)">
    <div class="orb orb-cyan w-[600px] h-[600px] top-0 left-1/2 -translate-x-1/2 opacity-15"></div>
    {{-- Scan line animasyonu --}}
    <div class="absolute inset-0 pointer-events-none" style="background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(2,224,251,0.015) 2px, rgba(2,224,251,0.015) 4px);"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            {{-- SOL: Bildirim Mockup (Tarayıcı/Toast UI) --}}
            <div class="flex justify-center order-1">
                <div class="relative w-full max-w-md">
                    {{-- Glow --}}
                    <div class="absolute -inset-6 rounded-3xl bg-gradient-to-br from-[#02E0FB]/15 to-transparent blur-2xl"></div>

                    {{-- Tarayıcı penceresi --}}
                    <div class="relative rounded-2xl border border-slate-700/60 bg-slate-900 overflow-hidden shadow-2xl" style="box-shadow: 0 0 60px rgba(2,224,251,0.12)">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-2 border-b border-slate-700/50 px-4 py-3 bg-slate-950">
                            <div class="h-2.5 w-2.5 rounded-full bg-red-500/60"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-yellow-500/60"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-emerald-500/60"></div>
                            <div class="ml-3 flex-1 rounded-md bg-slate-800/70 px-3 py-1 text-xs text-slate-500 flex items-center gap-1.5">
                                <svg class="h-3 w-3 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                aykome.belediye.gov.tr/admin/dashboard
                            </div>
                            {{-- Notification bell with badge --}}
                            <div class="relative ml-2">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                <span class="absolute -top-1 -right-1 h-3.5 w-3.5 rounded-full bg-red-500 text-[8px] font-black text-white flex items-center justify-center">3</span>
                            </div>
                        </div>

                        {{-- Sayfa içeriği (blur/dim) --}}
                        <div class="relative h-64 bg-slate-900/80 p-4 overflow-hidden">
                            {{-- Blur background (fake dashboard) --}}
                            <div class="opacity-20 space-y-2">
                                <div class="h-3 rounded bg-slate-700 w-3/4"></div>
                                <div class="h-3 rounded bg-slate-700 w-1/2"></div>
                                <div class="grid grid-cols-3 gap-2 mt-4">
                                    @for($i = 0; $i < 6; $i++)
                                    <div class="h-12 rounded-lg bg-slate-800"></div>
                                    @endfor
                                </div>
                                <div class="h-24 rounded-lg bg-slate-800 mt-2"></div>
                            </div>

                            {{-- Toast 1 — Makbuz Onaylandı (Yeşil) --}}
                            <div class="absolute top-3 right-3 w-64 rounded-xl border border-emerald-500/40 bg-slate-950/95 p-3 shadow-2xl backdrop-blur-sm" style="box-shadow: 0 0 20px rgba(16,185,129,0.2)">
                                <div class="flex items-start gap-2.5">
                                    <div class="mt-0.5 flex-shrink-0 h-7 w-7 rounded-lg bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-white">Makbuz Onaylandı!</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">AYK-2024-03847 · TEDAŞ başvurusu ödeme aşamasına geçti.</p>
                                        <p class="text-[9px] text-emerald-400 mt-1 font-medium">● Az önce · WebSocket</p>
                                    </div>
                                </div>
                                <div class="mt-2 h-0.5 w-full rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: 75%; animation: shrink 4s linear infinite"></div>
                                </div>
                            </div>

                            {{-- Toast 2 — Saha 2. Aşama (Cyan) --}}
                            <div class="absolute top-28 right-3 w-64 rounded-xl border border-[#02E0FB]/30 bg-slate-950/95 p-3 shadow-2xl backdrop-blur-sm">
                                <div class="flex items-start gap-2.5">
                                    <div class="mt-0.5 flex-shrink-0 h-7 w-7 rounded-lg bg-[#02E0FB]/15 border border-[#02E0FB]/30 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-[#02E0FB]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-white">Saha 2. Aşama Yüklendi</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Mehmet K. — Kazı Sonrası fotoğraflar eklendi. Onay bekliyor.</p>
                                        <p class="text-[9px] text-[#02E0FB] mt-1 font-medium">● 2 dk önce · Reverb</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Toast 3 — Yeni Başvuru (Orange) --}}
                            <div class="absolute bottom-3 right-3 w-64 rounded-xl border border-[#FA6001]/30 bg-slate-950/95 p-3 shadow-xl backdrop-blur-sm">
                                <div class="flex items-start gap-2.5">
                                    <div class="mt-0.5 flex-shrink-0 h-7 w-7 rounded-lg bg-[#FA6001]/15 border border-[#FA6001]/30 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-[#FA6001]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-white">Yeni Başvuru Geldi</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">ŞUSKİ — Doğalgaz Hattı · Kocaeli/Gebze. İnceleme bekliyor.</p>
                                        <p class="text-[9px] text-[#FA6001] mt-1 font-medium">● 5 dk önce · DB Notification</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating WebSocket badge --}}
                    <div class="absolute -bottom-5 left-4 flex items-center gap-2 rounded-xl border border-[#02E0FB]/30 bg-slate-900 px-3 py-2 shadow-xl">
                        <span class="h-2 w-2 rounded-full bg-[#02E0FB] animate-pulse"></span>
                        <span class="text-xs font-semibold text-[#02E0FB]">WebSocket Aktif</span>
                        <span class="text-[10px] text-slate-500">Laravel Reverb</span>
                    </div>
                </div>
            </div>

            {{-- SAĞ: Metin --}}
            <div class="order-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/10 px-4 py-1.5 text-xs font-semibold text-[#02E0FB] mb-6 uppercase tracking-widest">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
                    Gerçek Zamanlı
                </span>

                <h2 class="text-3xl sm:text-4xl lg:text-[2.6rem] font-black text-white leading-tight mb-5">
                    Ekran Yenileme Derdine Son:<br>
                    <span style="color: #02E0FB; text-shadow: 0 0 30px rgba(2,224,251,0.5)">Real-Time</span>
                    <span class="text-white"> İletişim</span>
                </h2>

                <p class="text-slate-400 text-base leading-relaxed mb-8 max-w-lg">
                    Sistemde her şey <strong class="text-slate-200">saniye saniye canlıdır.</strong>
                    Alt kurum ödemeyi yatırdığı veya sahacı tutanağı girdiği an,
                    <strong class="text-[#02E0FB]">WebSocket ve Reverb teknolojimiz</strong> admin yöneticilerine
                    canlı ses ve popup bildirimi düşürür. Süreçlerde tıkanıklığa izin vermeyin,
                    sahada taş oynamadan haberiniz olsun!
                </p>

                {{-- Checklist --}}
                <ul class="space-y-3.5 mb-8">
                    @foreach([
                        ['Sıfır Gecikme Push Bildirimi', 'Laravel Reverb (WebSocket) altyapısı ile anlık event broadcasting. F5 yok, polling yok.', '#02E0FB'],
                        ['Sesli Uyarı Sistemi', 'Kritik olaylarda tarayıcı ses bildirimi; AudioContext + özel notification.mp3 ile çalışır.', '#FA6001'],
                        ['DB + Broadcast Çift Katman', 'Bildirimler hem veritabanına hem WebSocket kanalına düşer. Kaçırmak imkânsız.', '#10b981'],
                    ] as $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 h-5 w-5 rounded-full flex items-center justify-center" style="background: {{ $item[2] }}20; color: {{ $item[2] }}">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <span class="text-sm font-semibold text-white">{{ $item[0] }}</span>
                            <span class="text-sm text-slate-500 ml-1.5">{{ $item[1] }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>

                {{-- Tech stack pills --}}
                <div class="flex flex-wrap gap-2">
                    @foreach(['Laravel Reverb', 'Laravel Echo', 'Pusher Protocol', 'SweetAlert2 Toast', 'AudioContext API'] as $tech)
                    <span class="rounded-lg border border-slate-700/60 bg-slate-800/50 px-3 py-1.5 text-xs font-medium text-slate-400">{{ $tech }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes shrink { from { width: 100%; } to { width: 0%; } }
    </style>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     D4. FEATURE DEEP DIVE 3 — Google Maps Vektör & Kurum İzolasyonu
         Layout: Sol Metin · Sağ Dark Map Mockup
═══════════════════════════════════════════════════════════════ --}}
<section class="relative bg-slate-950 py-24 overflow-hidden">
    <div class="orb orb-cyan w-[400px] h-[400px] top-10 -left-20 opacity-20"></div>
    <div class="orb orb-orange w-[350px] h-[350px] bottom-10 right-0 opacity-15"></div>
    <div class="absolute inset-0 hero-grid opacity-20"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            {{-- SOL: Metin --}}
            <div class="order-2 lg:order-1">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/10 px-4 py-1.5 text-xs font-semibold text-[#02E0FB] mb-6 uppercase tracking-widest">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c-.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/></svg>
                    Harita & Güvenlik
                </span>

                <h2 class="text-3xl sm:text-4xl lg:text-[2.6rem] font-black text-white leading-tight mb-5">
                    Harita Kontrolü ve<br>
                    Yüksek Veri Güvenliği<br>
                    <span class="gradient-text">(İzolasyon)</span>
                </h2>

                <p class="text-slate-400 text-base leading-relaxed mb-8 max-w-lg">
                    Sistemdeki binlerce başvuru, cadde sokak koordinatları
                    <strong class="text-slate-200">Google Maps Drawing Manager</strong> algoritması ile işlenir.
                    <strong class="text-[#02E0FB]">Multi-tenant yapımız</strong> ile ŞUSKİ'nin başvurusunu TEDAŞ izleyemez;
                    her alt taşeron, her saha sadece kendi şifreli izole adasında haritasına ve başvurusuna ulaşır.
                    Güvenlik ve koordinasyon bir arada!
                </p>

                {{-- Güvenlik checklist --}}
                <ul class="space-y-3.5 mb-8">
                    @foreach([
                        ['shield', 'Kurum İzolasyonu (Multi-Tenant)', 'Her kurum kendi veri adasında. WHERE institution_id filtresi tüm sorgularda zorunlu.', '#02E0FB'],
                        ['lock', 'Rol Bazlı Erişim Kontrolü', 'Super Admin / Belediye Yöneticisi / Kurum Personeli / Saha Ekibi — 4 farklı yetki seviyesi.', '#FA6001'],
                        ['eye', 'Tanrı Gözü Audit Log', 'Her kritik işlem zaman damgalı olarak kayıt altına alınır. Kimin, ne zaman, ne yaptığı izlenir.', '#10b981'],
                    ] as $item)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 h-5 w-5 rounded-full flex items-center justify-center" style="background: {{ $item[3] }}20; color: {{ $item[3] }}">
                            @if($item[0] === 'shield')
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @elseif($item[0] === 'lock')
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            @else
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </span>
                        <div>
                            <span class="text-sm font-semibold text-white">{{ $item[1] }}</span>
                            <p class="text-sm text-slate-500 mt-0.5">{{ $item[2] }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>

                {{-- Kurum izolasyon görselleştirmesi --}}
                <div class="rounded-2xl border border-slate-700/50 bg-slate-900/60 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500 mb-3">Kurum Veri İzolasyonu</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            ['ŞUSKİ', '#02E0FB', '23 Başvuru'],
                            ['TEDAŞ', '#FA6001', '41 Başvuru'],
                            ['Türk Telekom', '#a855f7', '18 Başvuru'],
                        ] as $inst)
                        <div class="rounded-xl border p-2.5 text-center" style="border-color: {{ $inst[1] }}25; background: {{ $inst[1] }}08">
                            <div class="h-7 w-7 rounded-lg mx-auto mb-1.5 flex items-center justify-center" style="background: {{ $inst[1] }}20">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="{{ $inst[1] }}"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            </div>
                            <p class="text-[10px] font-bold text-white">{{ $inst[0] }}</p>
                            <p class="text-[9px]" style="color: {{ $inst[1] }}">{{ $inst[2] }}</p>
                        </div>
                        @endforeach
                    </div>
                    <p class="mt-2.5 text-center text-[10px] text-slate-600 flex items-center justify-center gap-1">
                        <svg class="h-3 w-3 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        Her kurum yalnızca kendi şifreli adasını görür
                    </p>
                </div>
            </div>

            {{-- SAĞ: Dark Map Mockup --}}
            <div class="order-1 lg:order-2 flex justify-center">
                <div class="relative w-full max-w-lg">
                    {{-- Glow --}}
                    <div class="absolute -inset-8 rounded-3xl bg-gradient-to-br from-[#02E0FB]/15 to-[#FA6001]/10 blur-3xl"></div>

                    {{-- Map container --}}
                    <div class="relative rounded-2xl border border-slate-700/60 overflow-hidden shadow-2xl" style="box-shadow: 0 0 80px rgba(2,224,251,0.1)">
                        {{-- Map chrome --}}
                        <div class="flex items-center justify-between border-b border-slate-700/50 px-4 py-3 bg-slate-950">
                            <div class="flex items-center gap-2">
                                <div class="h-2.5 w-2.5 rounded-full bg-red-500/60"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-yellow-500/60"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-emerald-500/60"></div>
                                <span class="ml-2 text-[10px] text-slate-500">Harita İzleme — Tüm Kurumlar</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-emerald-400 font-semibold">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Canlı
                            </div>
                        </div>

                        {{-- Filter bar --}}
                        <div class="flex items-center gap-2 bg-slate-900/80 border-b border-slate-700/30 px-4 py-2">
                            @foreach([['ŞUSKİ', '#02E0FB', true], ['TEDAŞ', '#FA6001', true], ['Türk Telekom', '#a855f7', false]] as $f)
                            <span class="flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-semibold {{ $f[2] ? 'border' : 'opacity-40' }}" style="{{ $f[2] ? 'border-color:' . $f[0] . '40; background:' . $f[0] . '15; color:' . $f[0] : 'color: #64748b' }}">
                                <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $f[1] }}"></span>
                                {{ $f[0] }}
                            </span>
                            @endforeach
                            <span class="ml-auto text-[9px] text-slate-600">Drawing Manager Aktif</span>
                        </div>

                        {{-- Map SVG --}}
                        <div class="relative bg-slate-900 overflow-hidden" style="height: 320px">
                            {{-- Grid overlay --}}
                            <svg class="absolute inset-0 w-full h-full opacity-15" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern id="mapgrid2" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#02E0FB" stroke-width="0.4"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#mapgrid2)"/>
                            </svg>

                            {{-- Harita içeriği --}}
                            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 480 320" xmlns="http://www.w3.org/2000/svg">
                                {{-- Yollar --}}
                                <line x1="0" y1="70" x2="480" y2="70" stroke="#334155" stroke-width="10"/>
                                <line x1="0" y1="150" x2="480" y2="150" stroke="#334155" stroke-width="8"/>
                                <line x1="0" y1="240" x2="480" y2="240" stroke="#334155" stroke-width="10"/>
                                <line x1="80" y1="0" x2="80" y2="320" stroke="#334155" stroke-width="8"/>
                                <line x1="190" y1="0" x2="190" y2="320" stroke="#334155" stroke-width="10"/>
                                <line x1="310" y1="0" x2="310" y2="320" stroke="#334155" stroke-width="8"/>
                                <line x1="410" y1="0" x2="410" y2="320" stroke="#334155" stroke-width="6"/>
                                {{-- Yol yüzey --}}
                                <line x1="0" y1="70" x2="480" y2="70" stroke="#475569" stroke-width="7"/>
                                <line x1="0" y1="150" x2="480" y2="150" stroke="#475569" stroke-width="6"/>
                                <line x1="190" y1="0" x2="190" y2="320" stroke="#475569" stroke-width="7"/>
                                {{-- Bloklar --}}
                                <rect x="90" y="80" width="90" height="60" fill="#1e293b" rx="3"/>
                                <rect x="200" y="80" width="100" height="60" fill="#1e293b" rx="3"/>
                                <rect x="320" y="80" width="80" height="60" fill="#1e293b" rx="3"/>
                                <rect x="90" y="160" width="90" height="70" fill="#1e293b" rx="3"/>
                                <rect x="200" y="160" width="100" height="70" fill="#1e293b" rx="3"/>
                                <rect x="420" y="80" width="50" height="60" fill="#1e293b" rx="3"/>
                                <rect x="90" y="10" width="90" height="50" fill="#1e293b" rx="3"/>
                                <rect x="200" y="10" width="100" height="50" fill="#1e293b" rx="3"/>
                                <rect x="320" y="250" width="80" height="60" fill="#1e293b" rx="3"/>

                                {{-- ŞUSKİ Polygon (Cyan) — büyük --}}
                                <polygon points="95,85 180,82 185,108 175,135 90,138" fill="rgba(2,224,251,0.2)" stroke="#02E0FB" stroke-width="2.5" stroke-linejoin="round"/>
                                <circle cx="96" cy="85" r="4.5" fill="#02E0FB"/>
                                <circle cx="180" cy="82" r="4.5" fill="#02E0FB"/>
                                <circle cx="185" cy="108" r="4.5" fill="#02E0FB"/>
                                <circle cx="175" cy="135" r="4.5" fill="#02E0FB"/>
                                <circle cx="90" cy="138" r="4.5" fill="#02E0FB"/>
                                {{-- ŞUSKİ label --}}
                                <rect x="95" y="100" width="44" height="14" rx="3" fill="#02E0FB" opacity="0.9"/>
                                <text x="117" y="111" text-anchor="middle" fill="#0f172a" font-size="8" font-weight="bold">ŞUSKİ</text>

                                {{-- TEDAŞ Polygon (Orange) --}}
                                <polygon points="325,85 395,82 400,135 330,138" fill="rgba(250,96,1,0.18)" stroke="#FA6001" stroke-width="2.5" stroke-linejoin="round"/>
                                <circle cx="325" cy="85" r="4.5" fill="#FA6001"/>
                                <circle cx="395" cy="82" r="4.5" fill="#FA6001"/>
                                <circle cx="400" cy="135" r="4.5" fill="#FA6001"/>
                                <circle cx="330" cy="138" r="4.5" fill="#FA6001"/>
                                {{-- TEDAŞ label --}}
                                <rect x="338" y="100" width="40" height="14" rx="3" fill="#FA6001" opacity="0.9"/>
                                <text x="358" y="111" text-anchor="middle" fill="white" font-size="8" font-weight="bold">TEDAŞ</text>

                                {{-- Türk Telekom Polygon (Purple) --}}
                                <polygon points="205,165 295,162 300,220 210,222" fill="rgba(168,85,247,0.15)" stroke="#a855f7" stroke-width="2" stroke-linejoin="round" stroke-dasharray="6 3"/>
                                <circle cx="205" cy="165" r="4" fill="#a855f7"/>
                                <circle cx="295" cy="162" r="4" fill="#a855f7"/>
                                <circle cx="300" cy="220" r="4" fill="#a855f7"/>
                                <circle cx="210" cy="222" r="4" fill="#a855f7"/>

                                {{-- Ölçüm çizgisi --}}
                                <line x1="95" y1="148" x2="185" y2="148" stroke="#02E0FB" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.7"/>
                                <text x="140" y="163" text-anchor="middle" fill="#02E0FB" font-size="9" opacity="0.9">~90m</text>

                                {{-- Drawing Manager imleç --}}
                                <circle cx="375" cy="195" r="5" fill="none" stroke="#02E0FB" stroke-width="1.5" stroke-dasharray="3 2" opacity="0.8"/>
                                <line x1="368" y1="195" x2="382" y2="195" stroke="#02E0FB" stroke-width="1" opacity="0.8"/>
                                <line x1="375" y1="188" x2="375" y2="202" stroke="#02E0FB" stroke-width="1" opacity="0.8"/>

                                {{-- Koordinat popup --}}
                                <rect x="310" y="248" width="130" height="38" rx="6" fill="#0f172a" stroke="#02E0FB" stroke-width="1" stroke-opacity="0.4"/>
                                <text x="318" y="262" fill="#94a3b8" font-size="8">Lat: 40.7828° N</text>
                                <text x="318" y="278" fill="#94a3b8" font-size="8">Lng: 29.9144° E</text>
                            </svg>

                            {{-- Zoom controls --}}
                            <div class="absolute top-3 right-3 flex flex-col rounded-xl border border-slate-700/60 bg-slate-950/90 overflow-hidden shadow-xl">
                                <button class="px-3 py-2 text-slate-400 hover:text-white border-b border-slate-700/40 text-sm">+</button>
                                <button class="px-3 py-2 text-slate-400 hover:text-white text-sm">−</button>
                            </div>

                            {{-- Legend --}}
                            <div class="absolute bottom-3 left-3 rounded-xl border border-slate-700/60 bg-slate-950/95 px-3 py-2.5 backdrop-blur-sm shadow-xl">
                                <p class="text-[9px] uppercase tracking-wider text-slate-500 mb-1.5">Kurum Renk Kodu</p>
                                @foreach([['#02E0FB','ŞUSKİ'],['#FA6001','TEDAŞ'],['#a855f7','Türk Telekom']] as $leg)
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="h-2.5 w-2.5 rounded-sm flex-shrink-0" style="background: {{ $leg[0] }}60; border: 1.5px solid {{ $leg[0] }}"></span>
                                    <span class="text-[10px] text-slate-400">{{ $leg[1] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Map footer stats --}}
                        <div class="grid grid-cols-4 divide-x divide-slate-700/40 bg-slate-950/80 border-t border-slate-700/30">
                            @foreach([['82', 'Aktif Alan', '#02E0FB'],['247', 'Toplam Kayıt', '#FA6001'],['3', 'Kurum', '#a855f7'],['12', 'Çiziliyor', '#10b981']] as $s)
                            <div class="px-3 py-2.5 text-center">
                                <p class="text-sm font-black" style="color: {{ $s[2] }}">{{ $s[0] }}</p>
                                <p class="text-[9px] text-slate-600 uppercase tracking-wide">{{ $s[1] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Floating security badge --}}
                    <div class="absolute -bottom-4 -right-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-slate-900 px-3 py-2 shadow-xl">
                        <svg class="h-4 w-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <div>
                            <p class="text-[10px] font-bold text-white">KVKK Uyumlu</p>
                            <p class="text-[9px] text-slate-500">Veri izolasyonu aktif</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     E-PRO. ULTRA SAAS PRO ÖZELLİKLER
═══════════════════════════════════════════════════════════════ --}}
<section id="pro-moduller" class="relative bg-slate-950 py-28 overflow-hidden">

    {{-- Ambient orbs --}}
    <div class="orb orb-cyan  w-[700px] h-[700px] -top-40  -left-40  opacity-25"></div>
    <div class="orb orb-orange w-[500px] h-[500px] bottom-0 -right-32  opacity-20"></div>
    <div class="orb orb-cyan  w-[400px] h-[400px]  top-1/2  right-1/4  opacity-10"></div>
    <div class="absolute inset-0 hero-grid opacity-20"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- ── Başlık ── --}}
        <div class="text-center mb-20" data-aos>
            <div class="inline-flex items-center gap-2 rounded-full border border-[#FA6001]/40 bg-[#FA6001]/10 px-5 py-2 text-xs font-bold text-[#FA6001] mb-6 uppercase tracking-widest">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-[#FA6001] opacity-75 animate-ping"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-[#FA6001]"></span>
                </span>
                Ultra SaaS Pro — Operasyonel Güç
            </div>
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
                Geleceğin Altyapı Yönetimi:<br>
                <span class="gradient-text">HGB</span>
                <span style="-webkit-text-fill-color:#FA6001;background:none;color:#FA6001;" class="font-black"> PRO</span>
            </h2>
            <p class="mx-auto max-w-2xl text-lg text-slate-400 leading-relaxed">
                Sıradan bir yönetimden fazlası. Operasyonel gücünüzü 3 yeni Pro modülü ile
                <span class="text-[#02E0FB] font-semibold">Tanrı Moduna</span> taşıyın.
            </p>
        </div>

        {{-- ── 3 PRO Kart ── --}}
        <div class="grid gap-8 lg:grid-cols-3">

            {{-- ─── KART 1: Canlı Saha İzleme ─── --}}
            <div class="pro-card group relative rounded-3xl border border-[#02E0FB]/20 bg-slate-900/60 backdrop-blur-xl p-8 flex flex-col
                        hover:border-[#02E0FB]/60 hover:-translate-y-2 transition-all duration-500
                        shadow-[0_0_0_1px_rgba(2,224,251,0.05)] hover:shadow-[0_0_60px_rgba(2,224,251,0.18),0_0_120px_rgba(2,224,251,0.06)]"
                 data-aos style="transition-delay:0ms">

                {{-- Glow top bar --}}
                <div class="absolute top-0 left-8 right-8 h-px bg-gradient-to-r from-transparent via-[#02E0FB]/60 to-transparent rounded-full"></div>

                {{-- PRO Badge --}}
                <div class="absolute -top-3 right-6 flex items-center gap-1.5 rounded-full bg-gradient-to-r from-[#02E0FB] to-[#02AFC6] px-3.5 py-1 text-[10px] font-black text-slate-900 shadow-lg shadow-[#02E0FB]/30">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    GOD MODE
                </div>

                {{-- İkon --}}
                <div class="mb-7 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#02E0FB]/10 border border-[#02E0FB]/20 group-hover:bg-[#02E0FB]/20 transition-colors duration-300">
                    <svg class="h-8 w-8 text-[#02E0FB]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3" fill="currentColor" opacity=".4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z" opacity=".25"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6a6 6 0 016 6M6 12a6 6 0 016-6" opacity=".5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 014 4M8 12a4 4 0 014-4"/>
                    </svg>
                </div>

                {{-- Başlık --}}
                <div class="mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#02E0FB]/70">Modül 01</span>
                    <h3 class="mt-1.5 text-2xl font-black text-white leading-tight">Canlı Saha<br>İzleme</h3>
                </div>

                {{-- Açıklama --}}
                <p class="text-sm text-slate-400 leading-relaxed flex-1">
                    Personellerinizi harita üzerinde <span class="text-slate-200 font-medium">canlı izleyin</span>.
                    30 saniyede bir GPS pingi ile kimin hangi kazıda olduğunu,
                    sahadaki aktiflik durumunu saniye saniye takip edin.
                    İnterneti kesilen personeli <span class="text-[#02E0FB] font-medium">otomatik geçmişe düşüren</span> zeki algoritma.
                </p>

                {{-- Özellik pills --}}
                <div class="mt-6 flex flex-wrap gap-2">
                    <span class="rounded-full bg-[#02E0FB]/10 border border-[#02E0FB]/20 px-3 py-1 text-[11px] font-semibold text-[#02E0FB]">30sn GPS Ping</span>
                    <span class="rounded-full bg-[#02E0FB]/10 border border-[#02E0FB]/20 px-3 py-1 text-[11px] font-semibold text-[#02E0FB]">Zombi Tespiti</span>
                    <span class="rounded-full bg-[#02E0FB]/10 border border-[#02E0FB]/20 px-3 py-1 text-[11px] font-semibold text-[#02E0FB]">Canlı Harita</span>
                </div>

                {{-- Bottom stat --}}
                <div class="mt-7 pt-5 border-t border-slate-800/80 flex items-center justify-between">
                    <div>
                        <p class="text-2xl font-black text-white tabular-nums">≤ 30<span class="text-sm font-semibold text-slate-400 ml-1">sn</span></p>
                        <p class="text-xs text-slate-500 mt-0.5">Konum güncelleme</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-[#02E0FB] font-semibold">
                        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full rounded-full bg-[#02E0FB] opacity-75 animate-ping"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-[#02E0FB]"></span></span>
                        Canlı
                    </div>
                </div>
            </div>

            {{-- ─── KART 2: Kanban Görev Emri ─── --}}
            <div class="pro-card group relative rounded-3xl border border-[#FA6001]/20 bg-slate-900/60 backdrop-blur-xl p-8 flex flex-col
                        hover:border-[#FA6001]/60 hover:-translate-y-2 transition-all duration-500
                        shadow-[0_0_0_1px_rgba(250,96,1,0.05)] hover:shadow-[0_0_60px_rgba(250,96,1,0.18),0_0_120px_rgba(250,96,1,0.06)]"
                 data-aos style="transition-delay:100ms">

                <div class="absolute top-0 left-8 right-8 h-px bg-gradient-to-r from-transparent via-[#FA6001]/60 to-transparent rounded-full"></div>

                <div class="absolute -top-3 right-6 flex items-center gap-1.5 rounded-full bg-gradient-to-r from-[#FA6001] to-[#f97316] px-3.5 py-1 text-[10px] font-black text-white shadow-lg shadow-[#FA6001]/30">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    PRO
                </div>

                <div class="mb-7 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#FA6001]/10 border border-[#FA6001]/20 group-hover:bg-[#FA6001]/20 transition-colors duration-300">
                    <svg class="h-8 w-8 text-[#FA6001]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                </div>

                <div class="mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#FA6001]/70">Modül 02</span>
                    <h3 class="mt-1.5 text-2xl font-black text-white leading-tight">Görev Emri<br>Yönetimi</h3>
                </div>

                <p class="text-sm text-slate-400 leading-relaxed flex-1">
                    Saha görevlerini <span class="text-slate-200 font-medium">profesyonel Kanban panosuyla</span> yönetin.
                    Görev atama, termin süreleri ve gecikme uyarıları tek ekranda.
                    Atanmış ve atanmamış işlerinizi
                    <span class="text-[#FA6001] font-medium">sürükle-bırak</span> kolaylığıyla organize edin.
                </p>

                <div class="mt-6 flex flex-wrap gap-2">
                    <span class="rounded-full bg-[#FA6001]/10 border border-[#FA6001]/20 px-3 py-1 text-[11px] font-semibold text-[#FA6001]">Kanban Board</span>
                    <span class="rounded-full bg-[#FA6001]/10 border border-[#FA6001]/20 px-3 py-1 text-[11px] font-semibold text-[#FA6001]">Gecikme Alarmı</span>
                    <span class="rounded-full bg-[#FA6001]/10 border border-[#FA6001]/20 px-3 py-1 text-[11px] font-semibold text-[#FA6001]">Drag & Drop</span>
                </div>

                <div class="mt-7 pt-5 border-t border-slate-800/80 flex items-center justify-between">
                    <div>
                        <p class="text-2xl font-black text-white tabular-nums">4<span class="text-sm font-semibold text-slate-400 ml-1">Sütun</span></p>
                        <p class="text-xs text-slate-500 mt-0.5">Bekleyen → Tamamlandı</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-[#FA6001] font-semibold">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Aktif
                    </div>
                </div>
            </div>

            {{-- ─── KART 3: Gelişmiş Raporlama ─── --}}
            <div class="pro-card group relative rounded-3xl border border-purple-500/20 bg-slate-900/60 backdrop-blur-xl p-8 flex flex-col
                        hover:border-purple-400/50 hover:-translate-y-2 transition-all duration-500
                        shadow-[0_0_0_1px_rgba(168,85,247,0.05)] hover:shadow-[0_0_60px_rgba(168,85,247,0.15),0_0_120px_rgba(168,85,247,0.05)]"
                 data-aos style="transition-delay:200ms">

                <div class="absolute top-0 left-8 right-8 h-px bg-gradient-to-r from-transparent via-purple-500/60 to-transparent rounded-full"></div>

                <div class="absolute -top-3 right-6 flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-3.5 py-1 text-[10px] font-black text-white shadow-lg shadow-purple-500/30">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                    ANALİTİK
                </div>

                <div class="mb-7 flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-500/10 border border-purple-500/20 group-hover:bg-purple-500/20 transition-colors duration-300">
                    <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4v16"/>
                        <circle cx="7" cy="12" r="1" fill="currentColor"/>
                        <circle cx="10" cy="9" r="1" fill="currentColor"/>
                        <circle cx="13" cy="12" r="1" fill="currentColor"/>
                        <circle cx="17" cy="8" r="1" fill="currentColor"/>
                    </svg>
                </div>

                <div class="mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-purple-400/70">Modül 03</span>
                    <h3 class="mt-1.5 text-2xl font-black text-white leading-tight">Gelişmiş Saha<br>Raporlama</h3>
                </div>

                <p class="text-sm text-slate-400 leading-relaxed flex-1">
                    <span class="text-slate-200 font-medium">Aylık performans trendleri</span>, personel başarı karneleri ve pivot veriler.
                    Tüm saha operasyonlarınızı tek tıkla
                    <span class="text-purple-400 font-medium">PDF veya Excel</span> olarak dökün.
                    Sunumlarınız için profesyonel analitik tablolar anında hazır.
                </p>

                <div class="mt-6 flex flex-wrap gap-2">
                    <span class="rounded-full bg-purple-500/10 border border-purple-500/20 px-3 py-1 text-[11px] font-semibold text-purple-400">Pivot Tablo</span>
                    <span class="rounded-full bg-purple-500/10 border border-purple-500/20 px-3 py-1 text-[11px] font-semibold text-purple-400">PDF / Excel</span>
                    <span class="rounded-full bg-purple-500/10 border border-purple-500/20 px-3 py-1 text-[11px] font-semibold text-purple-400">Personel Karnesi</span>
                </div>

                <div class="mt-7 pt-5 border-t border-slate-800/80 flex items-center justify-between">
                    <div>
                        <p class="text-2xl font-black text-white tabular-nums">1<span class="text-sm font-semibold text-slate-400 ml-1">Tık</span></p>
                        <p class="text-xs text-slate-500 mt-0.5">Rapor dışa aktarma</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-purple-400 font-semibold">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        Dışa Aktar
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Alt CTA şeridi ── --}}
        <div class="mt-20 relative rounded-3xl border border-slate-700/60 bg-gradient-to-r from-slate-900 via-slate-800/80 to-slate-900 p-8 sm:p-12 overflow-hidden" data-aos>
            <div class="orb orb-cyan  w-[400px] h-[400px] -right-20 top-1/2 -translate-y-1/2 opacity-20 pointer-events-none"></div>
            <div class="orb orb-orange w-[300px] h-[300px] -left-20  top-1/2 -translate-y-1/2 opacity-15 pointer-events-none"></div>
            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="rounded-full bg-gradient-to-r from-[#02E0FB] to-[#FA6001] px-3 py-1 text-[10px] font-black text-slate-900 uppercase tracking-widest">Pro Suite</span>
                        <span class="text-slate-500 text-sm">3 Modül · Tek Platform</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white mb-2">Pro gücü bugün deneyin.</h3>
                    <p class="text-slate-400 text-sm">Kurulum gerektirmez. Demo hesabıyla anında başlayın.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 flex-shrink-0">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#02E0FB] to-[#02AFC6] px-7 py-3.5 text-sm font-bold text-slate-900 shadow-lg shadow-[#02E0FB]/25 hover:shadow-[#02E0FB]/50 hover:-translate-y-0.5 transition-all duration-300">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                        Demo'ya Gir
                    </a>
                    <a href="#iletisim"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-600 bg-slate-800/60 px-7 py-3.5 text-sm font-semibold text-slate-200 hover:border-slate-400 hover:text-white hover:-translate-y-0.5 transition-all duration-300">
                        İletişime Geç
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     E. İSTATİSTİKLER
═══════════════════════════════════════════════════════════════ --}}
<section id="istatistikler" class="relative bg-slate-900 py-20 overflow-hidden">
    {{-- Background decorative --}}
    <div class="absolute inset-0 hero-grid opacity-30"></div>
    <div class="orb orb-cyan w-[600px] h-[600px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-20"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/10 px-4 py-1.5 text-xs font-semibold text-[#02E0FB] mb-4 uppercase tracking-widest">Rakamlar</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-4">Güven Rakamlarla Ölçülür</h2>
            <p class="text-slate-400 max-w-xl mx-auto">Türkiye'nin en kapsamlı altyapı kazı yönetim platformunda neler döndüğünü görün.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @php
                $stats = [
                    ['value' => '100+', 'label' => 'Aktif Belediye', 'sub' => 've kurum ortağı', 'color' => '#02E0FB', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>'],
                    ['value' => '50.000+', 'label' => 'Kazı İzni', 'sub' => 'başarıyla onaylandı', 'color' => '#FA6001', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/></svg>'],
                    ['value' => '<1sn', 'label' => 'Bildirim Hızı', 'sub' => 'WebSocket ile anlık', 'color' => '#02E0FB', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>'],
                    ['value' => '%99.9', 'label' => 'Uptime SLA', 'sub' => 'kurumsal güvence', 'color' => '#FA6001', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>'],
                ];
            @endphp

            @foreach($stats as $stat)
            <div class="stat-card group rounded-2xl border border-slate-700/60 bg-slate-900/80 p-6 text-center backdrop-blur-sm glow-cyan cursor-default" style="border-color: {{ $stat['color'] }}20">
                <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl" style="background: {{ $stat['color'] }}15; color: {{ $stat['color'] }}">
                    {!! $stat['icon'] !!}
                </div>
                <div class="text-3xl sm:text-4xl font-black mb-1" style="color: {{ $stat['color'] }}">{{ $stat['value'] }}</div>
                <div class="text-sm font-semibold text-white mb-1">{{ $stat['label'] }}</div>
                <div class="text-xs text-slate-500">{{ $stat['sub'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Testimonial strip --}}
        <div class="mt-12 rounded-2xl border border-slate-700/60 bg-slate-800/40 p-6 text-center backdrop-blur-sm">
            <div class="flex items-center justify-center gap-1 mb-3">
                @for($i = 0; $i < 5; $i++)
                <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @endfor
            </div>
            <blockquote class="text-slate-300 text-sm max-w-2xl mx-auto leading-relaxed">
                "AYKOME ile kazı izin süreçlerimizi %70 kısalttık. Saha ekiplerimiz artık kağıt taşımıyor, haritadan anlık takip yapabiliyoruz."
            </blockquote>
            <p class="mt-3 text-xs text-slate-500 font-medium">— Teknik Müdür, Büyükşehir Belediyesi</p>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     PAZARLAMA CTA BÖLÜMÜ
═══════════════════════════════════════════════════════════════ --}}
<section class="relative bg-slate-950 py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-[#02E0FB]/10 via-transparent to-[#FA6001]/10"></div>
    <div class="orb orb-cyan w-[400px] h-[400px] top-0 left-1/4 opacity-25"></div>
    <div class="orb orb-orange w-[400px] h-[400px] bottom-0 right-1/4 opacity-20"></div>

    <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
        <div class="rounded-3xl border border-slate-700/60 bg-slate-900/80 p-10 sm:p-14 backdrop-blur-sm shadow-2xl">
            <span class="inline-block rounded-full border border-[#02E0FB]/30 bg-[#02E0FB]/10 px-4 py-1.5 text-xs font-semibold text-[#02E0FB] mb-6 uppercase tracking-widest">Hemen Başlayın</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-5 leading-tight">
                Kazı Yönetiminizi<br>
                <span class="gradient-text">Dijitalleştirmeye</span><br>
                Bugün Başlayın
            </h2>
            <p class="text-slate-400 text-lg mb-8 max-w-xl mx-auto">
                30 dakikada kurulum. Teknik ekip desteği. Türkiye geneli referans belediyeler.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#02E0FB] to-[#02AFC6] px-8 py-4 text-base font-bold text-slate-900 shadow-2xl hover:shadow-[0_0_40px_rgba(2,224,251,0.5)] transition-all duration-300 hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Sisteme Giriş Yap
                </a>
                <a href="mailto:{{ config('company.email') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#FA6001]/40 bg-[#FA6001]/10 px-8 py-4 text-base font-semibold text-[#FA6001] hover:bg-[#FA6001]/20 transition-all duration-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    Demo Talep Et
                </a>
            </div>

            {{-- Mini features --}}
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>Kredi kartı gerekmez</span>
                <span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>30 dk kurulum</span>
                <span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>Teknik destek dahil</span>
                <span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>KVKK uyumlu</span>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     F. FOOTER & İLETİŞİM
═══════════════════════════════════════════════════════════════ --}}
<footer id="iletisim" class="bg-slate-950 border-t border-slate-800/60">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
            {{-- Brand --}}
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#02E0FB] to-[#FA6001] text-sm font-black text-white shadow-lg">M</span>
                    <div>
                        <span class="font-black text-white text-lg">AYKOME</span>
                        <span class="text-slate-500 text-xs ml-2">by HGB Bilişim</span>
                    </div>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed max-w-xs mb-5">
                    Türkiye'nin belediyelerine ve altyapı kurumlarına özel akıllı kazı izin ve yönetim platformu.
                </p>
                <span class="inline-block rounded-lg bg-emerald-500/15 border border-emerald-500/30 px-3 py-1 text-xs font-semibold text-emerald-400">Ultra SaaS v3 — Canlı</span>
            </div>

            {{-- Platform --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Platform</h4>
                <ul class="space-y-2.5">
                    @foreach(['Harita İzleme', 'Başvuru Yönetimi', 'E-Ruhsat', 'Saha Uygulaması', 'Raporlama', 'Bildirimler'] as $link)
                    <li><a href="#ozellikler" class="text-sm text-slate-500 hover:text-[#02E0FB] transition">{{ $link }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- İletişim --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">İletişim</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-[#02E0FB] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <a href="mailto:{{ config('company.email') }}" class="text-sm text-slate-400 hover:text-white transition">{{ config('company.email') }}</a>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-[#FA6001] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                        <a href="{{ config('company.website') }}" target="_blank" rel="noopener" class="text-sm text-slate-400 hover:text-white transition">hgbilisim.com</a>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-[#02E0FB] mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span class="text-sm text-slate-500">Türkiye Geneli SaaS</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-slate-800/50 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-600">
                © {{ date('Y') }} {{ config('company.name') }}. Tüm hakları saklıdır. AYKOME — Altyapı Kazı Yönetim Sistemi
            </p>
            <div class="flex items-center gap-4 text-xs text-slate-600">
                <span>KVKK Uyumlu</span>
                <span class="h-3 w-px bg-slate-700"></span>
                <span>v3 Ultra SaaS</span>
                <span class="h-3 w-px bg-slate-700"></span>
                <a href="{{ route('login') }}" class="text-[#02E0FB] hover:text-white transition">Yönetim Paneli →</a>
            </div>
        </div>
    </div>
</footer>

{{-- Scroll-reveal JS --}}
<script>
    // AOS-lite: intersection observer ile fade-up
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('aos-animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-aos]').forEach(el => observer.observe(el));

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
</script>
</body>
</html>
