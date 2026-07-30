<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kullanım Kılavuzu') — HGB Bilişim AYKOME</title>
    <meta name="description" content="HGB Bilişim AYKOME Altyapı İzin Yönetim Sistemi resmi kullanım kılavuzu ve teknik dokümantasyon.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { cyan: '#02E0FB', orange: '#FA6001' },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
                    },
                    typography: {
                        DEFAULT: { css: { color: '#374151', lineHeight: '1.8' } }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Scroll offset for sticky navbar ── */
        html { scroll-padding-top: 4.5rem; }

        /* ── Sidebar active state ── */
        .doc-nav-link.active {
            background: linear-gradient(90deg, rgba(2,224,251,.12) 0%, transparent 100%);
            color: #02E0FB;
            border-left-color: #02E0FB;
        }

        /* ── Code block ── */
        .doc-code {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: .5rem;
            padding: 1rem 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: .8125rem;
            line-height: 1.7;
            color: #e2e8f0;
            overflow-x: auto;
        }
        .doc-code .kw  { color: #7dd3fc; }
        .doc-code .str { color: #86efac; }
        .doc-code .cm  { color: #64748b; font-style: italic; }
        .doc-code .hl  { background: rgba(250,96,1,.15); border-left: 2px solid #FA6001; margin: 0 -1.25rem; padding: 0 1.25rem; display: block; }

        /* ── Callout boxes ── */
        .callout-info    { background:#eff6ff; border-left:4px solid #3b82f6; }
        .callout-warn    { background:#fffbeb; border-left:4px solid #f59e0b; }
        .callout-danger  { background:#fff1f2; border-left:4px solid #f43f5e; }
        .callout-success { background:#f0fdf4; border-left:4px solid #22c55e; }
        .callout-tip     { background:#f0fdfa; border-left:4px solid #02E0FB; }

        /* ── Step badges ── */
        .step-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width:2rem; height:2rem; border-radius:50%;
            background: linear-gradient(135deg,#02E0FB,#FA6001);
            color:#fff; font-weight:700; font-size:.8rem; flex-shrink:0;
        }

        /* ── Role pills ── */
        .role-pill { display:inline-flex; align-items:center; gap:.3rem;
            padding:.2rem .7rem; border-radius:9999px; font-size:.75rem; font-weight:600; }

        /* ── Table of contents item ── */
        .toc-item {
            display: block; padding: .35rem .75rem .35rem 1rem;
            border-left: 2px solid transparent;
            color: #6b7280; font-size: .8125rem; line-height: 1.5;
            transition: color .15s, border-color .15s, background .15s;
            border-radius: 0 .375rem .375rem 0;
            text-decoration: none;
        }
        .toc-item:hover { color: #111827; background: #f9fafb; border-left-color: #d1d5db; }
        .toc-item.toc-h2 { font-weight: 600; color: #374151; margin-top:.5rem; }
        .toc-item.toc-h3 { padding-left: 1.75rem; }

        /* ── Search highlight ── */
        .doc-section { transition: opacity .2s; }
        .doc-section.hidden-by-search { display: none; }

        /* ── Navbar backdrop ── */
        #docs-navbar { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

        /* ── Mobile sidebar toggle ── */
        #docs-sidebar { transition: transform .2s ease; }
        @media(max-width:1023px) {
            #docs-sidebar { position:fixed; inset-y:0; left:0; z-index:40;
                transform: translateX(-100%); width:18rem; }
            #docs-sidebar.open { transform: translateX(0); }
        }

        /* ── Scrollbar styling ── */
        #docs-sidebar::-webkit-scrollbar { width:4px; }
        #docs-sidebar::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:4px; }

        /* ── Section title underline ── */
        .section-title-bar {
            height:3px; width:3rem; border-radius:9999px;
            background: linear-gradient(90deg,#02E0FB,#FA6001);
            margin-top:.4rem; margin-bottom:1.5rem;
        }

        /* ── Tag badge ── */
        .tag-badge { display:inline-block; padding:.1rem .55rem; border-radius:.25rem;
            font-size:.7rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }

        /* Print */
        @media print {
            #docs-navbar, #docs-sidebar { display:none; }
            main { margin:0; padding:0; max-width:100%; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased">

{{-- ═══════════════════════════════════════════════════════
     TOP NAVBAR
═══════════════════════════════════════════════════════ --}}
<header id="docs-navbar" class="fixed inset-x-0 top-0 z-50 border-b border-gray-200/80 bg-white/90">
    <div class="mx-auto flex h-14 max-w-screen-2xl items-center gap-4 px-4 sm:px-6">

        {{-- Logo + Title --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            <button id="sidebar-toggle" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Menü">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-[#02E0FB] to-[#FA6001] text-xs font-black text-white shadow-sm">M</span>
                <span class="hidden font-bold text-gray-900 tracking-tight sm:block">AYKOME</span>
            </a>
            <span class="hidden items-center gap-1.5 text-sm text-gray-400 sm:flex">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                Kullanım Kılavuzu
            </span>
        </div>

        {{-- Search Bar --}}
        <div class="flex-1 max-w-xl mx-auto">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/>
                </svg>
                <input
                    id="docs-search"
                    type="search"
                    placeholder="Kılavuzda arayın... (örn: makbuz, saha, ruhsat)"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:border-[#02E0FB] focus:bg-white focus:ring-2 focus:ring-[#02E0FB]/20"
                    autocomplete="off"
                    spellcheck="false"
                >
                <kbd class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 hidden rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-gray-400 sm:block">⌘K</kbd>
            </div>
            <div id="search-no-result" class="hidden mt-1 text-xs text-red-500 pl-1">Arama sonucu bulunamadı.</div>
        </div>

        {{-- Right actions --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('home') }}" class="hidden items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition sm:flex">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Ana Sayfa
            </a>
            @auth
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#0ab8d0] px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:opacity-90 transition">
                Panele Dön
            </a>
            @endauth
        </div>
    </div>
</header>

{{-- Mobile overlay --}}
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-black/40 hidden lg:hidden" aria-hidden="true"></div>

{{-- ═══════════════════════════════════════════════════════
     LAYOUT SHELL (sidebar + content)
═══════════════════════════════════════════════════════ --}}
<div class="mx-auto flex max-w-screen-2xl pt-14">

    {{-- ──────────────── LEFT SIDEBAR ──────────────── --}}
    <aside id="docs-sidebar" class="w-72 flex-shrink-0 overflow-y-auto border-r border-gray-200 bg-white lg:sticky lg:top-14 lg:h-[calc(100vh-3.5rem)]">
        <div class="px-4 py-5">

            {{-- Version badge --}}
            <div class="mb-4 flex items-center gap-2">
                <span class="tag-badge bg-[#02E0FB]/10 text-[#02E0FB]">v3 Ultra</span>
                <span class="tag-badge bg-emerald-50 text-emerald-600">Güncel</span>
            </div>

            {{-- Nav sections --}}
            <nav id="doc-toc" aria-label="İçindekiler">

                {{-- Başlangıç --}}
                <p class="mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Başlangıç</p>
                <a href="#giris" class="toc-item toc-h2" data-section="giris">Sisteme Giriş ve İzolasyon</a>
                <a href="#giris-rol-tablosu" class="toc-item toc-h3" data-section="giris">Rol Tablosu</a>
                <a href="#giris-kvkk" class="toc-item toc-h3" data-section="giris">KVKK ve Veri İzolasyonu</a>

                {{-- Başvuru --}}
                <p class="mt-4 mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Başvuru & Harita</p>
                <a href="#basvuru" class="toc-item toc-h2" data-section="basvuru">Başvuru ve Harita (GeoJSON)</a>
                <a href="#basvuru-olusturma" class="toc-item toc-h3" data-section="basvuru">Yeni Başvuru Oluşturma</a>
                <a href="#basvuru-harita" class="toc-item toc-h3" data-section="basvuru">Polygon Çizimi</a>
                <a href="#basvuru-kaydet" class="toc-item toc-h3" data-section="basvuru">Kaydet ve İlerle</a>

                {{-- Ödeme --}}
                <p class="mt-4 mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Ödeme & Onay</p>
                <a href="#odeme" class="toc-item toc-h2" data-section="odeme">Fiyat Onayı ve Vezne/Makbuz</a>
                <a href="#odeme-hesaplama" class="toc-item toc-h3" data-section="odeme">Ücret Hesaplama</a>
                <a href="#odeme-vezne" class="toc-item toc-h3" data-section="odeme">Vezne Ödemesi</a>
                <a href="#odeme-makbuz" class="toc-item toc-h3" data-section="odeme">Makbuz Yükleme</a>

                {{-- Saha --}}
                <p class="mt-4 mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Saha Operasyonları</p>
                <a href="#saha" class="toc-item toc-h2" data-section="saha">Saha Operasyonları</a>
                <a href="#saha-devir" class="toc-item toc-h3" data-section="saha">Görev Devri</a>
                <a href="#saha-asamalar" class="toc-item toc-h3" data-section="saha">3 Zorunlu Aşama</a>
                <a href="#saha-bildirim" class="toc-item toc-h3" data-section="saha">Bildirim ve Sesli Uyarı</a>

                {{-- Ruhsat --}}
                <p class="mt-4 mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">E-Belge</p>
                <a href="#ruhsat" class="toc-item toc-h2" data-section="ruhsat">Dijital Ruhsat E-Belge</a>
                <a href="#ruhsat-sablonu" class="toc-item toc-h3" data-section="ruhsat">PDF Şablonu</a>
                <a href="#ruhsat-indir" class="toc-item toc-h3" data-section="ruhsat">İndirme ve Arşivleme</a>

            </nav>

            {{-- Help footer --}}
            <div class="mt-6 rounded-xl border border-[#02E0FB]/20 bg-[#02E0FB]/5 p-3">
                <p class="text-xs font-semibold text-[#02E0FB]">Destek & İletişim</p>
                <p class="mt-1 text-xs text-gray-500 leading-relaxed">Kılavuzda bulamadığınız sorularınız için:</p>
                <a href="mailto:destek@hgbilisim.com" class="mt-1.5 block text-xs font-medium text-[#FA6001] hover:underline">destek@hgbilisim.com</a>
            </div>
        </div>
    </aside>

    {{-- ──────────────── MAIN CONTENT ──────────────── --}}
    <main class="min-w-0 flex-1 px-4 pb-24 sm:px-8 lg:px-12 xl:px-16">
        @yield('content')
    </main>

    {{-- ──────────────── RIGHT: ON-THIS-PAGE (lg+) ──────────────── --}}
    <div class="hidden w-56 flex-shrink-0 xl:block">
        <div class="sticky top-20 px-4 py-5">
            <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Bu Sayfada</p>
            <nav id="on-page-nav" class="space-y-1 text-xs">
                <a href="#giris"    class="block text-gray-500 hover:text-gray-900 transition py-0.5">Sisteme Giriş</a>
                <a href="#basvuru"  class="block text-gray-500 hover:text-gray-900 transition py-0.5">Başvuru & Harita</a>
                <a href="#odeme"    class="block text-gray-500 hover:text-gray-900 transition py-0.5">Ödeme & Makbuz</a>
                <a href="#saha"     class="block text-gray-500 hover:text-gray-900 transition py-0.5">Saha Operasyonları</a>
                <a href="#ruhsat"   class="block text-gray-500 hover:text-gray-900 transition py-0.5">Dijital Ruhsat</a>
            </nav>
            <div class="mt-6 border-t border-gray-100 pt-4">
                <a href="#" onclick="window.print();return false;" class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-600 transition">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Yazdır / PDF
                </a>
            </div>
        </div>
    </div>

</div>{{-- end flex shell --}}

{{-- ═══════════════════════════════════════════════════════
     VANILLA JS — SEARCH + SIDEBAR + KEYBOARD
═══════════════════════════════════════════════════════ --}}
<script>
(function() {
    /* ── Mobile sidebar toggle ── */
    const toggle   = document.getElementById('sidebar-toggle');
    const sidebar  = document.getElementById('docs-sidebar');
    const overlay  = document.getElementById('sidebar-overlay');

    function openSidebar()  { sidebar.classList.add('open');    overlay.classList.remove('hidden'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.add('hidden'); }

    toggle  && toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay && overlay.addEventListener('click', closeSidebar);

    /* ── Close sidebar on anchor click (mobile) ── */
    sidebar && sidebar.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', () => { if (window.innerWidth < 1024) closeSidebar(); });
    });

    /* ── Keyboard shortcut: ⌘K / Ctrl+K ── */
    document.addEventListener('keydown', e => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const inp = document.getElementById('docs-search');
            inp && inp.focus();
        }
    });

    /* ──────────────────────────────────────────
       SEARCH ENGINE
       Strategy:
       1. Split query into terms.
       2. For each .doc-section, check if ALL terms appear in its text content.
       3. Show only matching sections (plus the hero header).
       4. Within visible sections, use <mark> highlight.
       5. If no match → show "no result" notice.
    ────────────────────────────────────────── */
    const searchInput  = document.getElementById('docs-search');
    const noResult     = document.getElementById('search-no-result');
    const allSections  = document.querySelectorAll('.doc-section');

    /* Restore original inner text (before marks) for each section */
    const originalHTML = new Map();
    allSections.forEach(sec => originalHTML.set(sec, sec.innerHTML));

    function escapeReg(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    function highlightText(node, terms) {
        if (node.nodeType === Node.TEXT_NODE) {
            let html = node.textContent;
            terms.forEach(t => {
                html = html.replace(new RegExp(`(${escapeReg(t)})`, 'gi'),
                    '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>');
            });
            const span = document.createElement('span');
            span.innerHTML = html;
            node.replaceWith(span);
        } else if (node.nodeType === Node.ELEMENT_NODE &&
                   !['SCRIPT','STYLE','CODE','PRE'].includes(node.tagName)) {
            Array.from(node.childNodes).forEach(child => highlightText(child, terms));
        }
    }

    function doSearch(raw) {
        const q     = raw.trim().toLowerCase();
        const terms = q ? q.split(/\s+/).filter(Boolean) : [];

        /* Reset originals */
        allSections.forEach(sec => {
            sec.innerHTML = originalHTML.get(sec);
            sec.classList.remove('hidden-by-search');
        });
        noResult && noResult.classList.add('hidden');

        if (!terms.length) return; /* empty query → show all */

        let visibleCount = 0;
        allSections.forEach(sec => {
            const text  = sec.textContent.toLowerCase();
            const match = terms.every(t => text.includes(t));
            if (match) {
                visibleCount++;
                highlightText(sec, terms);
                sec.classList.remove('hidden-by-search');
                /* Scroll first match into view */
                if (visibleCount === 1) {
                    setTimeout(() => sec.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
                }
            } else {
                sec.classList.add('hidden-by-search');
            }
        });

        if (visibleCount === 0 && noResult) noResult.classList.remove('hidden');
    }

    if (searchInput) {
        searchInput.addEventListener('input', e => doSearch(e.target.value));
        /* Clear on ESC */
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Escape') { e.target.value = ''; doSearch(''); e.target.blur(); }
        });
    }

    /* ── Active TOC link on scroll (IntersectionObserver) ── */
    const tocLinks = document.querySelectorAll('.toc-item[href^="#"]');
    if ('IntersectionObserver' in window && tocLinks.length) {
        const headings = document.querySelectorAll('h2[id], h3[id]');
        const obs = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    tocLinks.forEach(l => l.classList.remove('active'));
                    const match = document.querySelector(`.toc-item[href="#${entry.target.id}"]`);
                    match && match.classList.add('active');
                }
            });
        }, { rootMargin: '-60px 0px -70% 0px', threshold: 0 });
        headings.forEach(h => obs.observe(h));
    }

})();
</script>

@stack('scripts')
</body>
</html>
