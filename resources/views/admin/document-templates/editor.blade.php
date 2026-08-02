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
        .editor-wrap { position: fixed; top: 58px; left: 0; right: 0; bottom: 0; overflow: auto; padding: 0 0 40px; }

        /* ── Word: CKEditor A4 kağıt ── */
        .ck.ck-editor { border: none !important; max-width: 220mm; margin: 24px auto 40px; }
        .ck.ck-editor__main { background: transparent; }
        .ck.ck-editor__main > .ck-editor__editable {
            min-height: 295mm; background: #fff; box-shadow: 0 6px 20px rgba(0,0,0,.45);
            padding: 18mm 20mm; font-family: 'Times New Roman', Times, serif; border: 1px solid #cbd5e1;
        }
        .ck.ck-toolbar { background: #1e293b !important; border: none !important; border-radius: 8px 8px 0 0 !important; padding: 6px 8px; }
        .ck.ck-toolbar .ck-button { color: #e2e8f0; }
        .ck.ck-toolbar .ck-button:hover, .ck.ck-toolbar .ck-button.ck-on { color: #fff; background: #334155; }
        .ck.ck-toolbar .ck-toolbar__separator { background: #475569; }

        /* ── Excel: Jexcel ── */
        .excel-stage { background: #eef1f5; min-height: 100%; padding: 24px 16px 60px; }
        #excel-spreadsheet { max-width: 1200px; margin: 0 auto; background: #fff; box-shadow: 0 6px 20px rgba(0,0,0,.25); }

        /* ── Toast ── */
        #toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 2000; background: #0f172a; color: #fff; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; box-shadow: 0 6px 18px rgba(0,0,0,.35); opacity: 0; pointer-events: none; transition: opacity .25s; }
        #toast.show { opacity: 1; }
        #toast.ok { background: #059669; }
        #toast.err { background: #dc2626; }

        .hidden { display: none !important; }

        /* ── Word: TinyMCE A4 kağıt ── */
        #doc-editor { visibility: hidden; }
        .tox-tinymce { border-radius: 10px !important; border: none !important; box-shadow: 0 6px 20px rgba(0,0,0,.35); max-width: 220mm; margin: 24px auto; }
        .tox .tox-toolbar, .tox .tox-toolbar__primary, .tox .tox-menubar { background: #f8fafc !important; }
        .tox .tox-statusbar { border-top: none !important; }
    </style>

    @if($editorType === 'word')
        <style>{!! $docCss !!}</style>
    @endif

    @php
        /*
         * DIRECTİF 3 — CSS'LERİ INLINE ET:
         * Blade'den gelen fragment yalnızca class'lı ham HTML içerir (style bloğu ayrıştırılmıştır).
         * $docCss kurallarını elementlere inline style olarak uygulayıp editöre self-contained
         * (kendine yeten) tablolu/div'li HTML hidrate ediyoruz; böylece layout editörde de,
         * kaydedilen içerik sonradan PDF/e-imza'da renderlanırken de ezilmez.
         */
        $hydratedContent = $initialContent;
        if ($editorType === 'word' && $initialContent !== '' && $docCss !== '') {
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
            <span class="chip">{{ $editorType === 'word' ? '📄 Word — A4 Düzen' : '📊 Excel — Hücre Düzen' }}</span>
        </div>

        <div class="ribbon-right">
            @if($editorType === 'excel')
                <button type="button" class="tool-btn" onclick="excelAction('insertRow')">＋ Satır</button>
                <button type="button" class="tool-btn" onclick="excelAction('insertColumn')">＋ Sütun</button>
                <button type="button" class="tool-btn" onclick="excelAction('deleteRow')">－ Satır</button>
                <button type="button" class="tool-btn" onclick="excelAction('deleteColumn')">－ Sütun</button>
            @endif
            @if($scope === 'application' && $resetUrl)
                <button type="button" class="reset-btn" onclick="resetOverride()">↺ Varsayılana Dön</button>
            @endif
            <button type="button" class="cancel-btn" onclick="goBack()">İptal</button>
            <button type="button" class="save-btn" id="btn-save" onclick="saveDoc()">💾 Kaydet</button>
        </div>
    </div>

    @if($editorType === 'word')
        <div class="editor-wrap">
            <div id="doc-editor"></div>
        </div>
    @else
        <div class="editor-wrap">
            <div class="excel-stage">
                <div id="excel-spreadsheet"></div>
            </div>
        </div>
    @endif

    <div id="toast"></div>

    <form id="reset-form" method="POST" action="{{ $resetUrl ?? '#' }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    @if($editorType === 'word')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js"></script>
        <script>
            var editor = null;
            var INITIAL_CONTENT = {!! json_encode($hydratedContent) !!};

            tinymce.init({
                selector: '#doc-editor',
                language: 'tr',
                height: 950,
                menubar: false,
                branding: false,
                promotion: false,

                // ═══ DIRECTİF 1 — HTML KORUMA (hiçbir tag/attribute ezilmesin) ═══
                verify_html: false,                     // temizlik yapma (legacy, zararsız)
                valid_elements: '*[*]',                 // NE VAR NE YOK HTML'e izin ver (style dahil)
                extended_valid_elements: '*[*]',
                valid_children: '+body[style],+div[div],+div[table],+div[p]',
                valid_styles: { '*': '*' },             // tüm CSS özellikleri kalsın
                keep_styles: true,
                paste_auto_cleanup_on_paste: false,     // yapıştırırken silme
                paste_remove_styles: false,
                paste_remove_styles_if_webkit: false,
                paste_strip_class_attributes: 'none',

                // ═══ PLUGIN + TOOLBAR (Tablo plugini mutlaka AÇIK) ═══
                plugins: 'table image link media paste lists',
                toolbar: 'undo redo | formatselect fontselect fontsizeselect | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | table | link image removeformat',
                table_default_attributes: { border: '1' },

                // ═══ DIRECTİF 2 — İFRAME = GÖRSEL A4 KAĞIDI ═══
                content_style: [
                    "body { font-family: 'Times New Roman', Times, serif, 'DejaVu Sans', Arial, sans-serif !important; font-size: 13px !important; margin: 40px !important; background-color: #ffffff; color: #000000; min-height: 1000px; box-shadow: 0 6px 20px rgba(0,0,0,0.25); }",
                    "table { border-collapse: collapse; width: 100%; }",
                    "td { vertical-align: top; padding: 4px; }"
                ].join('\n'),

                setup: function (ed) {
                    editor = ed;
                },
                init_instance_callback: function (ed) {
                    var el = document.getElementById('doc-editor');
                    if (el) el.style.visibility = 'visible';
                }
            });
        </script>
    @else
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsuites@4.9.11/dist/jsuites.min.css">
        <script src="https://cdn.jsdelivr.net/npm/jsuites@4.9.11/dist/jsuites.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jspreadsheet@4.7.1/dist/jspreadsheet.min.css">
        <script src="https://cdn.jsdelivr.net/npm/jspreadsheet@4.7.1/dist/jspreadsheet.min.js"></script>
        <script>
            var GRID_DATA = {!! json_encode(json_decode($initialContent, true) ?: []) !!};
            $(function () {
                $('#excel-spreadsheet').jexcel({
                    data: GRID_DATA,
                    minDimensions: [5, 10],
                    tableOverflow: true,
                    columnSorting: false,
                    wordWrap: true,
                    defaultColWidth: 150,
                    rowResize: true,
                    allowInsertRow: true,
                    allowInsertColumn: true,
                    allowDeleteRow: true,
                    allowDeleteColumn: true
                });
            });
        </script>
    @endif

    <script>
        var CSRF_TOKEN = @json(csrf_token());
        var SAVE_URL = @json($saveUrl);
        var BACK_URL = @json($backUrl);
        var EDITOR_TYPE = @json($editorType);

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

        function collectContent() {
            if (EDITOR_TYPE === 'word') {
                if (!editor) throw new Error('Editör hazır değil');
                return editor.getContent();
            }
            var data = $('#excel-spreadsheet').jexcel('getData');
            return JSON.stringify(data);
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

        function excelAction(method) {
            try {
                $('#excel-spreadsheet').jexcel(method, 0);
            } catch (e) {
                console.warn(e);
            }
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveDoc();
            }
        });
    </script>
</body>
</html>
