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
            @if($scope === 'application' && $resetUrl)
                <button type="button" class="reset-btn" onclick="resetOverride()">↺ Varsayılana Dön</button>
            @endif
            <button type="button" class="cancel-btn" onclick="goBack()">İptal</button>
            <button type="button" class="save-btn" id="btn-save" onclick="saveDoc()">💾 Kaydet</button>
        </div>
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
        var INITIAL_CONTENT = {!! json_encode($hydratedContent) !!};

        // ── Editör başlatma: orijinal A4 HTML'i bas + contenteditable uygula ──
        function initEditor() {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            el.innerHTML = INITIAL_CONTENT || '';

            // Metin/hücre elemanlarını contenteditable yap (A4 yapısı asla bozulmaz)
            var editable = el.querySelectorAll('td, th, p, h1, h2, h3, h4, li, .imza .ad, .imza .unvan');
            for (var i = 0; i < editable.length; i++) {
                editable[i].setAttribute('contenteditable', 'true');
            }
        }

        function collectContent() {
            var el = document.getElementById('doc-editor');
            if (!el) throw new Error('Editör hazır değil');
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
            fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ content_data: content })
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
        }

        // Başlat
        initEditor();

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveDoc();
            }
        });
    </script>
</body>
</html>
