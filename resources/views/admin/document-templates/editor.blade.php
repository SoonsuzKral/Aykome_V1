<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #d7dce3; }

        /* ── Üst şerit (Microsoft Word / Excel benzeri) ── */
        .ribbon { position: fixed; top: 0; left: 0; right: 0; height: 58px; z-index: 1000; display: flex; align-items: center; gap: 10px; padding: 0 12px; background: linear-gradient(180deg, #2d3a4f, #243140); border-bottom: 1px solid #1b2534; color: #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
        .ribbon-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .back-btn { width: 38px; height: 38px; flex: none; border: none; border-radius: 8px; background: #334155; color: #cbd5e1; font-size: 18px; cursor: pointer; }
        .back-btn:hover { background: #475569; color: #fff; }
        .ribbon-title { min-width: 0; }
        .ribbon-name { font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ribbon-sub { font-size: 10px; color: #94a3b8; white-space: nowrap; }
        .ribbon-center { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .ribbon-center .chip { font-size: 11px; padding: 5px 10px; border-radius: 999px; background: #334155; color: #a5b4fc; font-weight: 600; }
        .ribbon-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .tool-btn { background: #334155; color: #e2e8f0; border: none; padding: 7px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .tool-btn:hover { background: #475569; color: #fff; }
        .reset-btn { background: transparent; color: #f59e0b; border: 1px solid #b45309; padding: 7px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .reset-btn:hover { background: #7c2d12; color: #fcd34d; }
        .cancel-btn { background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .cancel-btn:hover { background: #334155; color: #fff; }
        .save-btn { background: #2563eb; color: #fff; border: none; padding: 8px 18px; border-radius: 7px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .save-btn:hover { background: #1d4ed8; }
        .save-btn:disabled { opacity: .6; cursor: wait; }

        /* ── Çalışma alanı ── */
        .editor-wrap { position: fixed; top: 58px; left: 0; right: 0; bottom: 0; overflow: auto; padding: 24px 0 60px; background: #eef1f5; }

        /* ── ContentEditable A4 kağıt ── */
        #doc-editor {
            max-width: 220mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 6px 20px rgba(0,0,0,.35);
            min-height: 297mm;
            padding: 18mm 20mm;
            border: 1px solid #cbd5e1;
            outline: none;
            font-family: 'Times New Roman', Times, serif;
        }
        /* Düzenlenebilir hücre/metin odak stili */
        #doc-editor [contenteditable="true"] { border-radius: 2px; transition: box-shadow .15s, background .15s; }
        #doc-editor [contenteditable="true"]:hover { box-shadow: inset 0 0 0 1px rgba(37,99,235,.35); }
        #doc-editor [contenteditable="true"]:focus { box-shadow: inset 0 0 0 2px rgba(37,99,235,.6); background: rgba(37,99,235,.04); outline: none; }
        #doc-editor img { max-height: 100px; max-width: auto; object-fit: contain; }
        #doc-editor table { width: 100% !important; border-collapse: collapse !important; }
        #doc-editor td, #doc-editor th { vertical-align: top !important; padding: 3px !important; }

        /* ── Toast ── */
        #toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 2000; background: #0f172a; color: #fff; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; box-shadow: 0 6px 18px rgba(0,0,0,.35); opacity: 0; pointer-events: none; transition: opacity .25s; }
        #toast.show { opacity: 1; }
        #toast.ok { background: #059669; }
        #toast.err { background: #dc2626; }

        .hidden { display: none !important; }

        /* ── GÖREV 2 Salt-Okunur (readonly) modu ── */
        .ro-banner { display: none; align-items: center; gap: 8px; background: #18212f; color: #fbbf24; border-bottom: 1px solid #b45309; padding: 7px 16px; font-size: 12px; font-weight: 600; margin-top: 58px; }
        .ro-banner svg { flex: 0 0 auto; }
        body.ro-readonly .save-btn, body.ro-readonly .reset-btn { display: none !important; }
        body.ro-readonly #doc-editor [contenteditable] { cursor: default !important; user-select: none !important; }
        body.ro-readonly #doc-editor td { cursor: default !important; }
    </style>

    {{-- Belgenin kendi CSS'i (A4 görünümünü korur) --}}
    <style>{!! $docCss !!}</style>

    @php
        /*
         * DIRECTİF 3 — CSS'LERİ INLINE ET:
         * Blade'den gelen fragment yalnızca class'lı ham HTML içerir (style bloğu ayrıştırılmıştır).
         * $docCss kurallarını elementlere inline style olarak uygulayıp editöre self-contained
         * (kendine yeten) tablolu/div'li HTML hidrate ediyoruz; böylece layout editörde de,
         * kaydedilen içerik sonradan PDF/e-imza'da renderlanırken de ezilmez.
         */
        $hydratedContent = $initialContent;
        if ($initialContent !== '' && $docCss !== '') {
            try {
                $rules = [];
                preg_match_all('/([^{}]+)\{([^}]+)\}/', $docCss, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $selector = trim($m[1]);
                    if ($selector === '' || str_starts_with($selector, '@')) continue;
                    $decls = [];
                    foreach (explode(';', $m[2]) as $d) {
                        $pp = explode(':', $d, 2);
                        if (count($pp) !== 2) continue;
                        $prop = strtolower(trim($pp[0]));
                        $val = trim($pp[1]);
                        if ($prop !== '' && $val !== '' && ! str_contains($val, '{') && ! str_contains($val, '}')) {
                            $decls[$prop] = $val;
                        }
                    }
                    if (! $decls) continue;
                    foreach (explode(',', $selector) as $single) {
                        $single = trim($single);
                        if ($single === '' || str_starts_with($single, '@')
                            || str_contains($single, ':') || str_contains($single, '>')
                            || str_contains($single, '+') || str_contains($single, '~')) continue;
                        $rules[] = ['selector' => $single, 'decls' => $decls];
                    }
                }
                $cssToXPath = function (string $selector): ?string {
                    $parts = preg_split('/\s+/', trim($selector));
                    $xpath = '//';
                    foreach ($parts as $i => $part) {
                        if ($i > 0) $xpath .= '//';
                        if (preg_match('/^([a-zA-Z][\w-]*)?((?:\.[\w-]+)*)$/', $part, $m)) {
                            $xpath .= ($m[1] !== '' ? $m[1] : '*');
                            if (isset($m[2]) && $m[2] !== '') {
                                preg_match_all('/\.([\w-]+)/', $m[2], $cm);
                                $conds = [];
                                foreach ($cm[1] as $cls) {
                                    $conds[] = 'contains(concat(" ",normalize-space(@class)," ")," ' . $cls . ' ")';
                                }
                                $xpath .= '[' . implode(' and ', $conds) . ']';
                            }
                        } else {
                            return null;
                        }
                    }
                    return $xpath;
                };
                $doc = new \DOMDocument();
                libxml_use_internal_errors(true);
                $doc->loadHTML('<?xml encoding="utf-8" ?>' . $initialContent);
                libxml_clear_errors();
                $xp = new \DOMXPath($doc);
                foreach ($rules as $rule) {
                    $xpath = $cssToXPath($rule['selector']);
                    if ($xpath === null) continue;
                    $nodes = @$xp->query($xpath);
                    if ($nodes === false) continue;
                    foreach ($nodes as $node) {
                        if (! ($node instanceof \DOMElement)) continue;
                        $styleMap = [];
                        foreach (explode(';', $node->getAttribute('style')) as $d) {
                            $pp = explode(':', $d, 2);
                            if (count($pp) === 2 && trim($pp[0]) !== '') {
                                $styleMap[strtolower(trim($pp[0]))] = trim($pp[1]);
                            }
                        }
                        $styleMap = array_merge($rule['decls'], $styleMap);
                        $inline = '';
                        foreach ($styleMap as $prop => $val) {
                            $inline .= $prop . ': ' . $val . '; ';
                        }
                        $node->setAttribute('style', trim($inline));
                    }
                }
                $body = $doc->getElementsByTagName('body')->item(0);
                if ($body) {
                    $out = '';
                    foreach ($body->childNodes as $child) {
                        $out .= $doc->saveHTML($child);
                    }
                    if (trim($out) !== '') {
                        $hydratedContent = $out;
                    }
                }
            } catch (\Throwable $e) {
                $hydratedContent = $initialContent;
            }
        }
    @endphp
</head>
<body>

    <div class="ribbon">
        <div class="ribbon-left">
            <button type="button" class="back-btn" onclick="goBack()" title="Geri">←</button>
            <div class="ribbon-title">
                <div class="ribbon-name">{{ $title }}</div>
                <div class="ribbon-sub">
                    @if($scope === 'application')
                        🔒 Bu başvuruya özel taslak — yalnızca bu başvurunun PDF'inde kullanılır
                    @elseif($scope === 'institution')
                        🏢 Kurum şablonu — yalnızca bu kurumun başvurularında kullanılır
                    @else
                        🌐 Global şablon — başvuruya özel taslağı olmayan tüm PDF'lerde kullanılır
                    @endif
                </div>
            </div>
        </div>

        <div class="ribbon-center">
            <span class="chip">📄 A4 Belge Düzenle</span>
        </div>

        <div class="ribbon-right">
            @if(!empty($editorGridType))
                <button type="button" class="tool-btn" onclick="excelAction('insertRow')">＋ Satır Ekle</button>
                <button type="button" class="tool-btn" onclick="excelAction('deleteRow')">－ Satır Sil</button>
            @endif
            @if($resetUrl)
                <button type="button" class="reset-btn" onclick="resetOverride()">↺ Varsayılana Dön</button>
            @endif
            <button type="button" class="cancel-btn" onclick="goBack()">İptal</button>
            <button type="button" class="save-btn" id="btn-save" onclick="saveDoc()">💾 Kaydet</button>
        </div>
    </div>

    <div class="ro-banner" id="ro-banner">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        🔒 Bu belge kuruma gönderilmiş olup salt-okunur durumdadır — düzenlenemez.
    </div>

    <div class="editor-wrap">
        <div id="doc-editor"></div>
    </div>

    <div id="toast"></div>

    <form id="reset-form" method="POST" action="{{ $resetUrl ?? '#' }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        var CSRF_TOKEN = @json(csrf_token());
        var SAVE_URL = @json($saveUrl);
        var BACK_URL = @json($backUrl);
        // GÖREV 2 (CELL-BASED AUTH): Alt kurum oturumunda (IS_MUNI=false) belediye
        // makam hücreleri KESİN kilitlenir; hiçbir JS kod yolu bunları "true" yapamaz.
        var IS_MUNI = @json($isMuni ?? true);
        // GÖREV 2 (ÜST YAZI TESLİMİYET DONDURMASI): Alt kurum, belge submit edildikten sonra
        // (status != draft) editörü SALT-OKUNUR görür — tüm contenteditable devre dışıdır.
        var READ_ONLY = @json($readOnly ?? false);
        var INITIAL_CONTENT = {!! json_encode($hydratedContent) !!};

        // ── Editör başlatma: orijinal A4 HTML'i bas + contenteditable uygula ──
        // CELL-BASED AUTH (Güvenlik Duvarı):
        //  - contenteditable="true"  → serbest düzenleme (altkuruma açık hücreler)
        //  - contenteditable="false" → KESİN KİLİT: hiçbir JS burayı "true" yapamaz,
        //    tıklama/yazma preventDefault ile engellenir. (Belediye makam hücreleri)
        var EDITABLE_SELECTOR = 'td, th, p, h1, h2, h3, h4, li, .imza .ad, .imza .unvan';

        function initEditor() {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            el.innerHTML = INITIAL_CONTENT || '';

            var editable = el.querySelectorAll(EDITABLE_SELECTOR);
            for (var i = 0; i < editable.length; i++) {
                var cur = editable[i].getAttribute('contenteditable');
                if (cur === 'true') {
                    editable[i].setAttribute('contenteditable', 'true');
                } else {
                    // "false" veya belirtilmemiş → kilitli kalır; DOM normalize sonrası
                    // öznitelik düşmüşse yeniden "false" olarak zorla yazılır.
                    editable[i].setAttribute('contenteditable', 'false');
                    editable[i].setAttribute('data-locked-cell', '1');
                    editable[i].style.userSelect = 'none';
                }
            }
            lockProtectedCells(el);
            if (IS_MUNI === false) {
                // GÖREV 2: Alt kurum oturumunda belediye makam bölgeleri (data-muni="1"
                // işaretli hücreler) contenteditable="true" bile gelse mutlak kilitlenir.
                forceLockMuniOwned(el);
            }
            if (READ_ONLY === true) {
                lockAllCells(el);
            }
        }

        // GÖREV 2 (TESLİMİYET DONDURMASI): Yayınlanmış (submit edilmiş) üst yazıda alt kurum
        // için editör tamamen salt-okunur "Pdf kağıdı" görünümüne döner. Tüm contenteditable
        // iptal edilir, işlem düğmeleri gizlenir, kullanıcıya bilgi bandı gösterilir.
        function lockAllCells(root) {
            var cells = root.querySelectorAll(EDITABLE_SELECTOR + ', [contenteditable="true"]');
            if (!cells.length) cells = root.querySelectorAll('*');
            for (var i = 0; i < cells.length; i++) {
                cells[i].setAttribute('contenteditable', 'false');
                cells[i].setAttribute('data-locked-cell', '1');
                cells[i].style.userSelect = 'none';
            }
            document.body.classList.add('ro-readonly');
            var banner = document.getElementById('ro-banner');
            if (banner) banner.style.display = 'flex';
        }

        // Belediye makam hücreleri alt kurum için mutlak kilitli kalır.
        function forceLockMuniOwned(root) {
            var m = root.querySelectorAll('[data-muni="1"], [data-muni="true"]');
            for (var i = 0; i < m.length; i++) {
                m[i].setAttribute('contenteditable', 'false');
                m[i].setAttribute('data-locked-cell', '1');
                m[i].style.userSelect = 'none';
            }
        }

        // Kilitli hücrelerde tıklama/metin girişi/hamle iptal edilir.
        function lockProtectedCells(root) {
            root.addEventListener('mousedown', onProtectedDown, true);
            root.addEventListener('mouseup', onProtectedDown, true);
            root.addEventListener('keydown', onProtectedKey, true);
            root.addEventListener('keypress', onProtectedKey, true);
            root.addEventListener('beforeinput', onProtectedKey, true);
            root.addEventListener('dblclick', onProtectedDown, true);
        }

        function isLockedCell(node) {
            if (!node) return false;
            // Metin düğümleriyse parent elementine bak, aksi takdirde kilit aşılabilir (zafiyet onarımı)
            var p = node.nodeType === 3 ? node.parentNode : node;

            while (p && p !== document.getElementById('doc-editor')) {
                if (p.nodeType === 1 && p.getAttribute) {
                    var ce = p.getAttribute('contenteditable');
                    // Düzenlenebilir bir bölgeye ulaşıldıysa: içindeki düzenlemeye açık kabul et.
                    // Örn: taahhütname imza kutuları (div.bilgi contenteditable="true") düzenlenebilir;
                    // onları saran td (contenteditable="false") imza kutusunu KİLİTLEMEMELİ.
                    if (ce === 'true') return false;
                    if (ce === 'false' || p.getAttribute('data-locked-cell') === '1') return true;
                }
                p = p.parentNode;
            }
            return false;
        }

        function onProtectedDown(e) {
            if (isLockedCell(e.target)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }

        function onProtectedKey(e) {
            var t = e.target;
            if (isLockedCell(t)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            // Seçim kilitli bir hücreye kayıyorsa da engelle
            var sel = window.getSelection && window.getSelection();
            var anchor = sel && sel.anchorNode;
            var focus = sel && sel.focusNode;
            if ((anchor && isLockedCell(anchor.nodeType === 3 ? anchor.parentNode : anchor)) ||
                (focus && isLockedCell(focus.nodeType === 3 ? focus.parentNode : focus))) {
                e.preventDefault();
                e.stopPropagation();
            }
        }

        function collectContent() {
            var el = document.getElementById('doc-editor');
            if (!el) throw new Error('Editör hazır değil');

            // GÜVENLİK: Kaydetmeden önce kilitli hücrelerin contenteditable="false" olduğunu
            // garanti et — tarayıcı/DOM bir şekilde "true" yapmışsa bile geri kilitlenir.
            var locked = el.querySelectorAll('[data-locked-cell]');
            for (var i = 0; i < locked.length; i++) {
                locked[i].setAttribute('contenteditable', 'false');
            }
            return el.innerHTML;
        }

        function toast(msg, type) {
            var el = document.getElementById('toast');
            el.textContent = msg;
            el.className = 'show ' + (type || '');
            clearTimeout(el._t);
            el._t = setTimeout(function () { el.className = ''; }, 2400);
        }

        function goBack() {
            window.location.href = BACK_URL;
        }

        function saveDoc() {
            if (READ_ONLY === true) {
                toast('Bu belge kuruma gönderildiği için salt-okunurdur.', 'err');
                return;
            }
            var btn = document.getElementById('btn-save');
            btn.disabled = true;
            btn.textContent = '⏳ Kaydediliyor...';
            var content;
            try {
                content = collectContent();
            } catch (e) {
                toast(e.message, 'err');
                btn.disabled = false;
                btn.textContent = '💾 Kaydet';
                return;
            }

            // ── FRONTEND → DB KÖPRÜSÜ (live_sync_lines) ──
            // Editördeki tüm zemin miktar hücreleri (.sync-dom-value) toplanır ve satır
            // kimliği (data-id) ile server'a POST edilir. Backend buSayıları
            // application_surface_areas'ta günceller, Eyyübiye matematiğiyle toplamları
            // BAŞTAN kurar ve diğer evrakların (Tahakkuk/Ruhsat) eski override'larını
            // SİLİP onların DB'den taze (fresh) render üretmesini sağlar.
            let domUpdates = [];
            document.querySelectorAll('#doc-editor .sync-dom-value').forEach(td => {
                let raw = (td.innerText || '').trim();
                let parsedVal = parseFloat(raw.replace(/\./g, '').replace(',', '.'));
                if (!isNaN(parsedVal) && td.getAttribute('data-id')) {
                    domUpdates.push({ id: td.getAttribute('data-id'), val: parsedVal });
                }
            });
            var payload = { content_data: content, live_sync_lines: JSON.stringify(domUpdates) };

            fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify(payload)
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Sunucu hatası: ' + r.status);
                return r.json();
            })
            .then(function () {
                toast('✓ Şablon kaydedildi', 'ok');
                setTimeout(function () { window.location.href = BACK_URL; }, 700);
            })
            .catch(function (err) {
                console.error(err);
                toast('Kaydetme başarısız: ' + err.message, 'err');
                btn.disabled = false;
                btn.textContent = '💾 Kaydet';
            });
        }

        function resetOverride() {
            if (!confirm('Bu başvuruya özel taslak silinsin mi? Varsayılan (global/blade) şablona dönülür.')) return;
            document.getElementById('reset-form').submit();
        }

        // ── Excel satır ekle/sil (contenteditable tablo üzerinde) ──
        function excelAction(method) {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            // Seçili hücreyi bul (seçim varsa)
            var sel = window.getSelection && window.getSelection();
            var anchor = sel && sel.anchorNode;
            var cell = anchor && anchor.nodeType === 3 ? anchor.parentElement : anchor;
            while (cell && cell.tagName && cell.tagName.toLowerCase() !== 'td' && cell.tagName.toLowerCase() !== 'th') {
                cell = cell.parentElement;
            }
            if (!cell) { toast('Önce bir tablo hücresine tıklayın', 'err'); return; }
            // GÜVENLİK: Kilitli (belediye makam) hücre/ satırda satır ekleme-silme yasak.
            if (isLockedCell(cell)) {
                toast('Bu alan kilitli — belediye yetkisindedir.', 'err');
                return;
            }

            var row = cell.parentElement; // tr
            var tbody = row.parentElement; // tbody veya table

            if (method === 'insertRow') {
                var clone = row.cloneNode(true);
                var cells = clone.querySelectorAll('td, th');
                for (var i = 0; i < cells.length; i++) {
                    cells[i].textContent = '';
                }
                tbody.insertBefore(clone, row.nextSibling);
                toast('Satır eklendi', 'ok');
            } else if (method === 'deleteRow') {
                if (tbody.querySelectorAll('tr').length <= 1) { toast('En az bir satır kalmalı', 'err'); return; }
                row.remove();
                toast('Satır silindi', 'ok');
            }
            // CANLI MATEMATİK: satır ekle/sil sonrası birim fiyat sabitle + toplamları tazele
            if (typeof mathIndexRows === 'function' && MATH) {
                mathIndexRows();
                mathComputeFees();
            }
        }

        // ── CANLI DOM MATEMATİĞİ (GÖREV 2 / AykomeMath aynası) ────────────
        // Memur bir Excel belgesinin (ruhsat/tahakkuk/metraj) sayısal hücresini
        // düzenlediğinde satır tutarı + ücret hücreleri (KDV/harç/keşif/teminat/
        // genel toplam) MS Excel gibi ANINDA yeniden hesaplanır. Kırmızı Çizgi
        // kuralları PHP AykomeMath ile birebir aynıdır (server tarafında da aynı
        // değer DB'ye geri beslenir — GÖREV 3).
        var MATH = @json($math ?? null);
        var MATH_KDV = 0.20, MATH_HARC_PER_M2 = 9, MATH_KESIF_BASE = 361, MATH_KESIF_RATE = 0.01, MATH_TEMINAT_RATE = 0.50;

        // FRONTEND → DB KÖPRÜSÜ (satır kimliği enjeksiyonu):
        // Sunucunun gönderdiği zemin-adı → surface_line id haritasıyla, editördeki
        // ESKİ override'larda bile data-id yüklenirken takılır. Böylece M² hücresi
        // düzenlenip kaydedildiğinde live_sync_lines her zaman dolu gelir ve backend
        // (DocumentTemplateController) DB'yi güncelleyip Tahakkuk/Ruhsat'ı temizler.
        function annotateLineIds() {
            var el = document.getElementById('doc-editor');
            var map = (MATH && MATH.surfaceLineIds) ? MATH.surfaceLineIds : null;
            if (!el || !map) return;
            var rows = el.querySelectorAll('[data-aykome-surface]');
            for (var i = 0; i < rows.length; i++) {
                var name = rows[i].getAttribute('data-aykome-surface');
                if (!name) continue;
                var id = map[String(name).toLowerCase()];
                if (!id) continue;
                var qtyCells = rows[i].querySelectorAll('.sync-dom-value, .sync-miktar-td, [data-aykome-col="miktar"], [data-aykome-col="m2"]');
                for (var j = 0; j < qtyCells.length; j++) {
                    qtyCells[j].setAttribute('data-id', id);
                    qtyCells[j].setAttribute('data-type', 'miktar');
                    qtyCells[j].classList.add('sync-dom-value');
                }
            }
        }

        function mathParse(v) {
            if (v === null || v === undefined) return NaN;
            var s = String(v).replace(/[^\d.,\-]/g, '');
            if (s === '' || s === '-') return NaN;
            if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) {
                s = s.split('.').join('').replace(',', '.');
            } else if (s.indexOf(',') !== -1) {
                s = s.replace(',', '.');
            }
            var n = parseFloat(s);
            return isNaN(n) ? NaN : n;
        }

        function mathFmt(n, dec) {
            dec = (dec === undefined) ? 2 : dec;
            if (isNaN(n)) return '';
            var neg = n < 0;
            n = Math.abs(n);
            var fixed = n.toFixed(dec);
            var parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var out = parts.join(',');
            return (neg ? '-' : '') + out;
        }

        function mathLookupPrice(name) {
            if (!MATH || !MATH.surfacePrices || !name) return NaN;
            var lk = String(name).toLowerCase();
            var keys = Object.keys(MATH.surfacePrices);
            for (var i = 0; i < keys.length; i++) {
                if (String(keys[i]).toLowerCase() === lk) {
                    var p = parseFloat(MATH.surfacePrices[keys[i]]);
                    return isNaN(p) ? NaN : p;
                }
            }
            return NaN;
        }

        function mathCells(row) {
            var out = {};
            var cells = row.querySelectorAll('[data-aykome-col]');
            for (var i = 0; i < cells.length; i++) {
                out[cells[i].getAttribute('data-aykome-col')] = cells[i];
            }
            return out;
        }

        // Başlangıçtaki satır birim fiyatlarını sabitler (tutar/miktar oranı →
        // birim_fiyat hücresi → SURFACE_PRICES haritası).
        function mathIndexRows() {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            var rows = el.querySelectorAll('[data-aykome-surface]');
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var cols = mathCells(row);
                var qty = cols['miktar'] ? mathParse(cols['miktar'].textContent) : (cols['m2'] ? mathParse(cols['m2'].textContent) : NaN);
                var price = cols['birim_fiyat'] ? mathParse(cols['birim_fiyat'].textContent) : NaN;
                var tutar = cols['tutar'] ? mathParse(cols['tutar'].textContent) : NaN;
                var derived = NaN;
                if (!isNaN(price)) derived = price;
                else if (!isNaN(qty) && !isNaN(tutar) && qty !== 0) derived = tutar / qty;
                else derived = mathLookupPrice(row.getAttribute('data-aykome-surface'));
                row.__aykomePrice = derived;
            }
        }

        // Bir satırın tutarını yeniden hesaplar (metrajda M² = Uzunluk × Genişlik).
        function mathRecomputeRow(row) {
            var cols = mathCells(row);
            var qty = cols['miktar'] ? mathParse(cols['miktar'].textContent) : (cols['m2'] ? mathParse(cols['m2'].textContent) : NaN);

            if (cols['genislik'] && cols['uzunluk'] && cols['m2']) {
                var w = mathParse(cols['genislik'].textContent);
                var l = mathParse(cols['uzunluk'].textContent);
                if (!isNaN(w) && !isNaN(l)) {
                    qty = w * l;
                    cols['m2'].textContent = mathFmt(qty, 2);
                }
            }

            if (cols['tutar']) {
                var price = cols['birim_fiyat'] ? mathParse(cols['birim_fiyat'].textContent) : NaN;
                if (isNaN(price) && !isNaN(row.__aykomePrice)) price = row.__aykomePrice;
                if (!isNaN(qty) && !isNaN(price)) {
                    cols['tutar'].textContent = mathFmt(qty * price, 2);
                }
            }
        }

        function mathSetFee(key, val) {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            var nodes = el.querySelectorAll('[data-aykome-fee="' + key + '"]');
            for (var i = 0; i < nodes.length; i++) {
                var td = nodes[i];
                var suffix = td.textContent.indexOf(' TL') !== -1 ? ' TL' : '';
                td.textContent = val + suffix;
            }
        }

        // Tüm ücret hücrelerini Kırmızı Çizgi kurallarıyla canlı yeniden basar.
        function mathComputeFees() {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            var ztb = 0, toplamMiktar = 0;
            var rows = el.querySelectorAll('[data-aykome-surface]');
            for (var i = 0; i < rows.length; i++) {
                var cols = mathCells(rows[i]);
                var qty = cols['miktar'] ? mathParse(cols['miktar'].textContent) : (cols['m2'] ? mathParse(cols['m2'].textContent) : NaN);
                var tutar = cols['tutar'] ? mathParse(cols['tutar'].textContent) : NaN;
                if (!isNaN(qty)) toplamMiktar += qty;
                if (!isNaN(tutar)) ztb += tutar;
            }
            var isDicle = !!(MATH && MATH.isDicle);
            var instApp = !!(MATH && MATH.isInstitutionApp);
            var addPerm = !!(MATH && MATH.isAdditionalPermit);
            var kdv = ztb * MATH_KDV;
            var harci = isDicle ? 0 : toplamMiktar * MATH_HARC_PER_M2;
            var kesif = MATH_KESIF_BASE + ztb * MATH_KESIF_RATE;
            var ztbToplam = ztb + kdv + harci + kesif;
            var teminat = (instApp || addPerm) ? 0 : ztb * MATH_TEMINAT_RATE;
            var genel = ztbToplam + teminat;

            mathSetFee('toplam_miktar', mathFmt(toplamMiktar));
            mathSetFee('toplam_m2', mathFmt(toplamMiktar));
            mathSetFee('ztb_amount', mathFmt(ztb));
            mathSetFee('kdv_amount', mathFmt(kdv));
            mathSetFee('license_fee', mathFmt(harci));
            mathSetFee('discovery_fee', mathFmt(kesif));
            mathSetFee('ztb_total', mathFmt(ztbToplam));
            mathSetFee('teminat', mathFmt(teminat));
            mathSetFee('general_total', mathFmt(genel));
        }

        function initReactiveMath() {
            if (!MATH) return;
            var el = document.getElementById('doc-editor');
            if (!el) return;
            mathIndexRows();
            var handler = function (e) {
                var t = e.target;
                if (!t || !t.getAttribute || !t.getAttribute('data-aykome-col')) return;
                var row = t.closest ? t.closest('[data-aykome-surface]') : null;
                if (!row) return;
                mathRecomputeRow(row);
                mathComputeFees();
            };
            el.addEventListener('input', handler);
            el.addEventListener('focusout', handler);
        }

        // Başlat
        initEditor();
        annotateLineIds();
        initReactiveMath();

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (READ_ONLY === true) return;
                saveDoc();
            }
        });
    </script>
</body>
</html>
