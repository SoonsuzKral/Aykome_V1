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
        .editor-wrap { position: fixed; top: 58px; left: 0; right: 0; bottom: 0; overflow: auto; padding: 24px 0 60px; background: #eef1f5; transition: right .2s ease; }
        body.panel-open .editor-wrap { right: 300px; }

        /* ── BİLGİ KATMANI: sağ dinamik alan seçici paneli ── */
        /* 16.08 6. tur FIX: #fmt-bar (top:58px, z-index:990) eklenince bu ikon
           (eskiden top:66px, z-index:960) çubuğun ARKASINDA kalıp görünmez oldu —
           fmt-bar'ın ALTINA (top:106px) indirildi + z-index yükseltildi. */
        .fp-toggle { position: fixed; top: 106px; right: 12px; z-index: 991; width: 38px; height: 38px; border-radius: 9px; border: 1px solid #334155; background: #243141; color: #e2e8f0; font-size: 16px; cursor: pointer; box-shadow: 0 3px 10px rgba(0,0,0,.25); }
        .fp-toggle:hover { background: #334155; color: #fff; }
        #field-panel { position: fixed; top: 58px; right: 0; bottom: 0; width: 300px; z-index: 950; background: #fff; border-left: 1px solid #cbd5e1; box-shadow: -2px 0 10px rgba(0,0,0,.08); transform: translateX(100%); transition: transform .2s ease; display: flex; flex-direction: column; }
        #field-panel.panel-open { transform: translateX(0); }
        .fp-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .fp-title { font-weight: 700; font-size: 13px; color: #0f172a; }
        .fp-close { border: none; background: #e2e8f0; color: #475569; width: 26px; height: 26px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .fp-close:hover { background: #cbd5e1; }
        .fp-search { margin: 10px 12px 0; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 7px; font-size: 12px; }
        .fp-groups { flex: 1; overflow-y: auto; padding: 6px 12px 20px; }
        .fp-group-title { font-weight: 700; font-size: 11px; color: #334155; margin: 12px 0 4px; text-transform: uppercase; letter-spacing: .4px; }
        .fp-item { display: flex; align-items: baseline; gap: 6px; width: 100%; text-align: left; margin: 2px 0; padding: 6px 8px; border: none; border-radius: 6px; background: #f1f5f9; color: #0f172a; font-family: Consolas, Menlo, monospace; font-size: 12px; cursor: pointer; }
        .fp-item:hover { background: #dbeafe; }
        .fp-item .fp-lbl { font-family: inherit; color: #64748b; font-size: 11px; }

        /* ── ContentEditable A4 kağıt ── */
        /* 16.08 6. tur FIX: "max-width" tek başına yetersiz kaldı (kullanıcı raporu:
           sayfa hala çok geniş) — gerçek PDF çıktısındaki .a4-container ile BİREBİR
           aynı SABiT genişlik (210mm) + olası içerik taşmasını kesin engelleyen
           overflow-x:hidden eklendi. */
        #doc-editor {
            width: 210mm;
            max-width: 210mm;
            overflow-x: hidden;
            margin: 0 auto;
            background: #fff;
            /* 16.08 14. tur FIX: 1px'lik gerçek `border` KALDIRILDI — kutu modelini
               (padding box orijinini) gerçek .a4-container ile BİREBİR aynı yapmak
               için (o, border kullanmıyor, sadece box-shadow). Serbest konumlandırılan
               (position:absolute) blokların px koordinatı CSS'e göre konteynerin
               PADDING KUTUSUNA (border'ın hemen içi) göre hesaplanır — 1px'lik border
               farklılığı bile başvuru modülündeki gerçek çıktıyla ufak bir kaymaya
               sebep olabiliyordu; görsel sınır çizgisi tamamen box-shadow'a devredildi. */
            box-shadow: 0 0 0 1px #cbd5e1, 0 6px 20px rgba(0,0,0,.35);
            min-height: 297mm;
            /* 16.08 14. tur FIX: sabit "18mm 20mm" yerine PDF/e-imza çıktısıyla AYNI
               TEK kaynaktan gelen değer (bkz. DocumentTemplateService::
               A4_CONTAINER_PADDING + DocumentTemplateController::editorView()) —
               kullanıcı raporu: taslakta taşınan bir blok başvuru modülünde aynı
               mesafede çıkmıyordu, kök neden bu iki değerin BAĞIMSIZ olmasıydı. */
            padding: {{ $containerPadding ?? '18mm 20mm' }};
            outline: none;
            font-family: 'Times New Roman', Times, serif;
            /* 16.08 8. tur FIX: gercek PDF'teki .a4-container'da position:relative VAR
               (bkz. cover_letter.blade.php) - icerideki .a4-footer gibi position:absolute
               bloklar (Tesis Kontrol/Yetkilisi, dogrulama kodu) bu ANKORA gore konumlanir.
               #doc-editor'da bu eksikti -> mutlak bloklar SAYFAYA degil, en yakin
               konumlanmis ataya (.editor-wrap, tam ekran genislikte) gore hizalaniyordu;
               sayfa dar+ortali hale gelince bu kayma GORUNUR oldu (sayfanin SOLUNA tasti). */
            position: relative;
        }
        /* 16.08 9. tur FIX: cover_letter.blade.php'nin .a4-footer'i (Tesis Kontrol +
           dogrulama kodu) PDF'te BiLiNCLi olarak position:absolute;bottom:10mm
           kullanir (imza/dogrulama her zaman sayfanin dibine sabitlenir - gerçek
           PDF'te DOGRU). Ama editorde icerik KISA oldugunda #doc-editor'in
           min-height:297mm'lik TAM sayfa yuksekliginin dibine yapisip devasa
           bosluk yaratiyordu (kullanici ekran goruntusu). AYNI FELSEFE zaten
           pre_permit.blade.php'deki .sayfa-alti-wrapper'da var (PDF'te absolute,
           web onizlemede normal akis) - burada da ayni desen uygulandi. Gercek
           cover_letter.blade.php DOKUNULMADI (PDF ciktisi ETKiLENMEZ), sadece
           EDiTOR gorunumu override edildi. */
        #doc-editor .a4-footer {
            position: static !important;
            left: auto !important;
            right: auto !important;
            bottom: auto !important;
            margin-top: 24px !important;
        }
        /* 16.08 10. tur FIX: kullanıcı netleştirdi — "Tesis Kontrol/Yetkilisi" (sig-table)
           içeriğin HEMEN ALTINDA aksın (yukarıdaki düzeltme), AMA "BELGE DOĞRULAMA
           KODU" (.footer-line) — ve e-imza sonrası üstüne gelecek 5070 metni —
           GERÇEK bir sayfa altbilgisi gibi hep SAYFA DİBİNE yapışık kalmalı.
           .a4-footer static olunca .footer-line'ın mutlak konumu artık bir üst
           konumlanmış atası olan #doc-editor'e (position:relative) göre çalışır. */
        #doc-editor .a4-footer .footer-line {
            position: absolute !important;
            bottom: 10mm !important;
            left: 20mm !important;
            right: 20mm !important;
            margin-top: 0 !important;
        }
        /* Düzenlenebilir hücre/metin odak stili */
        #doc-editor [contenteditable="true"] { border-radius: 2px; transition: box-shadow .15s, background .15s; }
        #doc-editor [contenteditable="true"]:hover { box-shadow: inset 0 0 0 1px rgba(37,99,235,.35); }
        #doc-editor [contenteditable="true"]:focus { box-shadow: inset 0 0 0 2px rgba(37,99,235,.6); background: rgba(37,99,235,.04); outline: none; }
        #doc-editor img { max-height: 100px; max-width: 100% !important; object-fit: contain; }
        #doc-editor table { max-width: 100% !important; border-collapse: collapse !important; }
        #doc-editor td, #doc-editor th { vertical-align: top !important; padding: 3px !important; max-width: 210mm; }
        /* 16.08 6. tur — Kazı Metraj gibi yatay (landscape) belgeler portrait 210mm'e
           SıKIŞTIRILMAZ — tam yatay A4 genişliği kullanır. */
        #doc-editor.landscape-doc { width: 297mm; max-width: 297mm; }
        #doc-editor.landscape-doc td, #doc-editor.landscape-doc th { max-width: 297mm; }

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
        body.ro-readonly #fmt-bar { display: none !important; }

        /* ── 16.08 5. tur — BİÇİMLENDİRME ARAÇ ÇUBUĞU (Windows Word/Dabnet benzeri) ── */
        #fmt-bar { position: fixed; top: 58px; left: 0; right: 0; z-index: 990; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; padding: 6px 12px; background: #eef2f7; border-bottom: 1px solid #cbd5e1; transition: right .2s ease; }
        body.panel-open #fmt-bar { right: 300px; }
        body.ro-readonly .editor-wrap { top: 58px !important; }
        .fmt-group { display: flex; align-items: center; gap: 2px; padding: 0 6px; border-right: 1px solid #cbd5e1; }
        .fmt-group:last-child { border-right: none; }
        .fmt-btn { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid transparent; border-radius: 5px; background: transparent; color: #1e293b; font-size: 13px; font-weight: 700; cursor: pointer; }
        .fmt-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .fmt-btn.active { background: #bfdbfe; border-color: #60a5fa; }
        .fmt-select { height: 28px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 12px; background: #fff; color: #1e293b; padding: 0 4px; }
        #fmt-font-name { width: 130px; }
        #fmt-font-size { width: 62px; }
        .fmt-color-btn { position: relative; }
        .fmt-color-btn input[type=color] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

        /* Araç çubuğu varken çalışma alanını aşağı it */
        .editor-wrap { top: 96px !important; }

        /* ── Resim sürükle-boyutlandır (TAM_WORLD_YAPISI.md Aşama 4) ── */
        #doc-editor img { cursor: pointer; }
        #doc-editor img.aykome-img-selected { outline: 2px solid #2563eb; }
        #img-resize-overlay { position: fixed; pointer-events: none; z-index: 1500; outline: 2px dashed #2563eb; }
        .img-resize-handle { position: absolute; width: 11px; height: 11px; background: #2563eb; border: 2px solid #fff; border-radius: 3px; pointer-events: all; box-shadow: 0 1px 3px rgba(0,0,0,.4); }
        .img-resize-handle.handle-nw { top: -6px; left: -6px; cursor: nwse-resize; }
        .img-resize-handle.handle-ne { top: -6px; right: -6px; cursor: nesw-resize; }
        .img-resize-handle.handle-sw { bottom: -6px; left: -6px; cursor: nesw-resize; }
        .img-resize-handle.handle-se { bottom: -6px; right: -6px; cursor: nwse-resize; }

        /* ── 16.08 11. tur — SERBEST BLOK TAŞIMA ("tutup istediğim yere sürükleme") ── */
        #doc-editor.move-mode-active [contenteditable="true"]:hover,
        #doc-editor.move-mode-active td:hover,
        #doc-editor.move-mode-active div:hover,
        #doc-editor.move-mode-active p:hover { outline: 1.5px dashed #7c3aed !important; cursor: default; }
        [data-aykome-free-position="1"] { outline: 1px dashed #a78bfa; }
        #block-move-handle { position: fixed; width: 24px; height: 24px; background: #7c3aed; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px; cursor: move; z-index: 1600; box-shadow: 0 2px 8px rgba(0,0,0,.4); user-select: none; }
        #block-move-handle:active { background: #6d28d9; }
        /* 16.08 13. tur: reset ARTıK amber (daha az yıkıcı "geri al" eylemi) —
           gerçek KıRMIZI renk sadece silme (delete) için ayırıldı, karışıklık önlenir. */
        #block-reset-handle { position: fixed; width: 20px; height: 20px; background: #d97706; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; cursor: pointer; z-index: 1600; box-shadow: 0 2px 8px rgba(0,0,0,.4); user-select: none; }
        #move-readout { position: fixed; background: #0f172a; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; z-index: 1700; pointer-events: none; white-space: nowrap; }
        #move-mode-btn.active { background: #7c3aed !important; color: #fff !important; }
        /* 16.08 12. tur — blok DARALTMA/GENİŞLETME tutamacı (sağ-alt köşe) */
        #block-resize-handle { position: fixed; width: 14px; height: 14px; background: #2563eb; border: 2px solid #fff; border-radius: 3px; cursor: nwse-resize; z-index: 1600; box-shadow: 0 2px 6px rgba(0,0,0,.4); }
        /* 16.08 13. tur — blok SİLME tutamacı (sağ-üst köşe) */
        #block-delete-handle { position: fixed; width: 20px; height: 20px; background: #dc2626; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; cursor: pointer; z-index: 1600; box-shadow: 0 2px 8px rgba(0,0,0,.4); user-select: none; }
        #block-delete-handle:hover { background: #b91c1c; }

        /* ── Taslak Kütüphanesi paneli (Bilgi Alanları paneliyle aynı desen) ── */
        #draft-panel { position: fixed; top: 58px; right: 0; bottom: 0; width: 300px; z-index: 950; background: #fff; border-left: 1px solid #cbd5e1; box-shadow: -2px 0 10px rgba(0,0,0,.08); transform: translateX(100%); transition: transform .2s ease; display: flex; flex-direction: column; }
        #draft-panel.panel-open { transform: translateX(0); }
        .draft-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 10px; margin-bottom: 8px; }
        .draft-card-name { font-weight: 700; font-size: 12px; color: #0f172a; }
        .draft-card-meta { font-size: 10px; color: #94a3b8; margin: 2px 0 8px; }
        .draft-card-actions { display: flex; gap: 6px; }
        .draft-btn-load { background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 4px 9px; font-size: 11px; font-weight: 600; cursor: pointer; }
        .draft-btn-load:hover { background: #1d4ed8; }
        .draft-btn-del { background: transparent; color: #dc2626; border: 1px solid #fca5a5; border-radius: 6px; padding: 4px 9px; font-size: 11px; font-weight: 600; cursor: pointer; }
        .draft-btn-del:hover { background: #fee2e2; }
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
                    @elseif($scope === 'institution_cover')
                        🏢 {{ $institution?->name ?? 'Kurum' }} — Üst Yazı Şablonu (yalnızca bu kurumun başvurularında kullanılır)
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
            @if(!empty($importWordUrl) && !($readOnly ?? false))
                <input type="file" id="word-import-input" accept=".docx" class="hidden" onchange="importWordFile(this)">
                <button type="button" class="tool-btn" onclick="document.getElementById('word-import-input').click()" title="Kendi bilgisayarınızdaki .docx dosyasını yükleyip belgenin yerine koyar">📄 Word'den İçe Aktar</button>
            @endif
            @if($resetUrl)
                <button type="button" class="reset-btn" onclick="resetOverride()">↺ Varsayılana Dön</button>
            @endif
            @if(!empty($draftsUrl) && !($readOnly ?? false))
                <button type="button" class="tool-btn" onclick="toggleDraftPanel()" title="Kaydedilmiş taslak sürümleri (Word'den yüklenen veya elle yazılan)">📂 Taslak Kütüphanesi</button>
            @endif
            <button type="button" class="cancel-btn" onclick="goBack()">İptal</button>
            <button type="button" class="save-btn" id="btn-save" onclick="saveDoc()">💾 Kaydet</button>
        </div>
    </div>

    {{-- 16.08 5. tur — BİÇİMLENDİRME ARAÇ ÇUBUĞU: Windows Word/Dabnet benzeri ribbon.
         Mevcut contenteditable motoruna DOKUNMADAN (hücre kilitleri + canlı matematik
         korunur) document.execCommand ile çalışır — TipTap gibi bir motor değişikliği
         RiSKi YOK. --}}
    @if(!($readOnly ?? false))
    <div id="fmt-bar">
        <div class="fmt-group">
            <select id="fmt-font-name" class="fmt-select" onchange="setFontName(this.value)" title="Yazı Tipi">
                <option value="">Yazı Tipi</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Arial">Arial</option>
                <option value="Calibri">Calibri</option>
                <option value="Verdana">Verdana</option>
                <option value="Georgia">Georgia</option>
                <option value="Courier New">Courier New</option>
            </select>
            <select id="fmt-font-size" class="fmt-select" onchange="setFontSize(this.value)" title="Yazı Boyutu">
                <option value="">Boyut</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
                <option value="14">14</option>
                <option value="16">16</option>
                <option value="18">18</option>
                <option value="20">20</option>
                <option value="24">24</option>
                <option value="28">28</option>
                <option value="32">32</option>
                <option value="36">36</option>
            </select>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" style="font-style:normal;" onclick="fmt('bold')" title="Kalın (Ctrl+B)"><b>K</b></button>
            <button type="button" class="fmt-btn" onclick="fmt('italic')" title="Eğik (Ctrl+I)"><i>Y</i></button>
            <button type="button" class="fmt-btn" style="text-decoration:underline;" onclick="fmt('underline')" title="Altı Çizili (Ctrl+U)">A</button>
            <button type="button" class="fmt-btn" style="text-decoration:line-through;" onclick="fmt('strikeThrough')" title="Üstü Çizili">A</button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" onclick="fmt('justifyLeft')" title="Sola Yasla">≡</button>
            <button type="button" class="fmt-btn" onclick="fmt('justifyCenter')" title="Ortala">☰</button>
            <button type="button" class="fmt-btn" onclick="fmt('justifyRight')" title="Sağa Yasla">≡</button>
            <button type="button" class="fmt-btn" onclick="fmt('justifyFull')" title="iki Yana Yasla">☱</button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn fmt-color-btn" title="Yazı Rengi">
                A
                <input type="color" onchange="fmt('foreColor', this.value)" value="#000000">
            </button>
            <button type="button" class="fmt-btn fmt-color-btn" title="Vurgu (Fon) Rengi">
                🖊
                <input type="color" onchange="fmt('hiliteColor', this.value)" value="#fff59d">
            </button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" onclick="fmt('insertUnorderedList')" title="Madde İşareti">•≡</button>
            <button type="button" class="fmt-btn" onclick="fmt('insertOrderedList')" title="Numaralı Liste">1≡</button>
            <button type="button" class="fmt-btn" onclick="fmt('outdent')" title="Girintiyi Azalt">⇤</button>
            <button type="button" class="fmt-btn" onclick="fmt('indent')" title="Girinti Ekle">⇥</button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" style="width:auto;padding:0 8px;" onclick="insertTableAtCursor()" title="Tablo Ekle">⊞ Tablo</button>
            <input type="file" id="img-insert-input" accept="image/*" class="hidden" onchange="insertImageAtCursor(this)">
            <button type="button" class="fmt-btn" style="width:auto;padding:0 8px;" onclick="document.getElementById('img-insert-input').click()" title="Resim Ekle (sonrasında köşesinden sürükleyerek boyutlandırın)">🖼 Resim</button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" onclick="fmt('undo')" title="Geri Al (Ctrl+Z)">↶</button>
            <button type="button" class="fmt-btn" onclick="fmt('redo')" title="Yinele (Ctrl+Y)">↷</button>
            <button type="button" class="fmt-btn" onclick="fmt('removeFormat')" title="Biçimlendirmeyi Temizle">⌫A</button>
        </div>
        <div class="fmt-group">
            <button type="button" class="fmt-btn" id="move-mode-btn" style="width:auto;padding:0 10px;" onclick="toggleMoveMode()" title="AçıncA: herhangi bir bloğun (hücre/paragraf) üzerine gelip ✥ simgesinden tutup istediğiniz yere sürükleyin (cm hassasiyetinde serbest konumlandırma)">✥ Taşı Modu</button>
            <button type="button" class="fmt-btn" style="width:auto;padding:0 10px;" onclick="splitSelectionIntoBlock()" title="Önce ayırmak istediğiniz cümleyi/metni seçin, sonra bu butona basın — seçili kısım AYRI, bağımsız taşınabilir bir hücre haline gelir">✂ Seçimi Ayır</button>
        </div>
    </div>
    @endif

    <div class="ro-banner" id="ro-banner">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        🔒 Bu belge kuruma gönderilmiş olup salt-okunur durumdadır — düzenlenemez.
    </div>

    <div class="editor-wrap">
        <div id="doc-editor" class="{{ !empty($isLandscape) ? 'landscape-doc' : '' }}"></div>
    </div>

    {{-- BİLGİ KATMANI: sağ dinamik alan seçici — başvuru verisinin {token} ile nereye
         geleceğine kullanıcı karar verir. Kaydedilen şablonda token kalır, PDF'te
         başvurunun kendi verisiyle değiştirilir (tüm belge tipleri). --}}
    <button type="button" id="fp-toggle" class="fp-toggle" onclick="toggleFieldPanel()" title="Bilgi Alanları (başvuru verisi ekle)">⚙</button>
    <div id="field-panel">
        <div class="fp-head">
            <span class="fp-title">📊 Bilgi Alanları</span>
            <button type="button" class="fp-close" onclick="toggleFieldPanel()" title="Kapat">✕</button>
        </div>
        <input type="text" id="fp-search" class="fp-search" placeholder="Alan ara (kurum, tarih, müdür...)" oninput="renderCatalog()">
        <div id="fp-groups" class="fp-groups"></div>
    </div>

    {{-- 16.08 5. tur — TASLAK KÜTÜPHANESİ: birden fazla adlandırılmış şablon sürümü
         (elle yazılan / Word'den aktarılan) sakla + editöre yükle. AKTİF şablonu
         DEĞİŞTİRMEZ — kullanıcı hala 💾 Kaydet'e basmalı. --}}
    @if(!empty($draftsUrl) && !($readOnly ?? false))
    <div id="draft-panel">
        <div class="fp-head">
            <span class="fp-title">📂 Taslak Kütüphanesi</span>
            <button type="button" class="fp-close" onclick="toggleDraftPanel()" title="Kapat">✕</button>
        </div>
        <div style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">
            <button type="button" class="tool-btn" style="width:100%;" onclick="saveAsNewDraft()">💾 Farklı Kaydet (Yeni Taslak)</button>
            <p style="margin:8px 0 0;font-size:10.5px;color:#94a3b8;line-height:1.4;">Editördeki mevcut içeriği bir isimle (ör. "WORLD_PC") sakla. Aktif şablonu değiştirmez.</p>
        </div>
        <div id="draft-list" style="flex:1;overflow-y:auto;padding:10px 12px;"></div>
    </div>
    @endif

    <div id="toast"></div>

    <form id="reset-form" method="POST" action="{{ $resetUrl ?? '#' }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        var CSRF_TOKEN = @json(csrf_token());
        var SAVE_URL = @json($saveUrl);
        var BACK_URL = @json($backUrl);
        var IMPORT_WORD_URL = @json($importWordUrl ?? null);
        var DRAFTS_URL = @json($draftsUrl ?? null);
        // GÖREV 2 (CELL-BASED AUTH): Alt kurum oturumunda (IS_MUNI=false) belediye
        // makam hücreleri KESİN kilitlenir; hiçbir JS kod yolu bunları "true" yapamaz.
        var IS_MUNI = @json($isMuni ?? true);
        // GÖREV 2 (ÜST YAZI TESLİMİYET DONDURMASI): Alt kurum, belge submit edildikten sonra
        // (status != draft) editörü SALT-OKUNUR görür — tüm contenteditable devre dışıdır.
        var READ_ONLY = @json($readOnly ?? false);
        var INITIAL_CONTENT = {!! json_encode($hydratedContent) !!};
        // BİLGİ KATMANI: sunucudan gelen alan kataloğu ({token} → başvuru verisi).
        var FIELD_CATALOG = @json($fieldCatalog ?? []);

        // ── Editör başlatma: orijinal A4 HTML'i bas + contenteditable uygula ──
        // CELL-BASED AUTH (Güvenlik Duvarı):
        //  - contenteditable="true"  → serbest düzenleme (altkuruma açık hücreler)
        //  - contenteditable="false" → KESİN KİLİT: hiçbir JS burayı "true" yapamaz,
        //    tıklama/yazma preventDefault ile engellenir. (Belediye makam hücreleri)
        var EDITABLE_SELECTOR = 'td, th, p, h1, h2, h3, h4, li, .imza .ad, .imza .unvan';

        // Cell-based auth kilit mantığı — hem sayfa açılışında (initEditor) hem
        // Word içe aktarma sonrası (importWordFile) AYNI şekilde uygulanır.
        function applyCellLocks(el) {
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

        function initEditor() {
            var el = document.getElementById('doc-editor');
            if (!el) return;
            el.innerHTML = INITIAL_CONTENT || '';
            applyCellLocks(el);
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

        /* ── BİLGİ KATMANI: sağ panel + token ekleme ── */
        var PANEL_OPEN = false;

        function toggleFieldPanel() {
            PANEL_OPEN = !PANEL_OPEN;
            var p = document.getElementById('field-panel');
            var t = document.getElementById('fp-toggle');
            if (p) p.classList.toggle('panel-open', PANEL_OPEN);
            if (t) t.style.display = PANEL_OPEN ? 'none' : '';
            document.body.classList.toggle('panel-open', PANEL_OPEN);
            if (PANEL_OPEN) renderCatalog();
        }

        function renderCatalog() {
            var wrap = document.getElementById('fp-groups');
            if (!wrap) return;
            wrap.innerHTML = '';
            var q = ((document.getElementById('fp-search') || {}).value || '').toLowerCase().trim();
            var cat = FIELD_CATALOG || {};
            Object.keys(cat).forEach(function (g) {
                var items = (cat[g] || []).filter(function (f) {
                    return !q || (f.label || '').toLowerCase().indexOf(q) !== -1 || (f.key || '').indexOf(q) !== -1;
                });
                if (!items.length) return;
                var title = document.createElement('div');
                title.className = 'fp-group-title';
                title.textContent = g;
                wrap.appendChild(title);
                items.forEach(function (f) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'fp-item';
                    b.innerHTML = '{' + f.key + '} <span class="fp-lbl">' + f.label + '</span>';
                    b.onclick = function () { insertToken('{' + f.key + '}'); };
                    wrap.appendChild(b);
                });
            });
        }

        function insertToken(token) {
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            var el = document.getElementById('doc-editor');
            if (!el) return;
            el.focus();
            var sel = window.getSelection && window.getSelection();
            var anchor = sel && sel.anchorNode;
            var anchorEl = anchor ? (anchor.nodeType === 3 ? anchor.parentElement : anchor) : null;

            // Kilitli (belediye makam / salt-okunur) hücrede token eklemeyi engelle
            if (anchorEl && isLockedCell(anchorEl)) {
                toast('Bu alan kilitli olduğu için buraya alan eklenemez.', 'err');
                return;
            }

            if (anchorEl && el.contains(anchorEl)) {
                // İmleç contenteditable içinde → imleç konumu korunur, düz metin eklenir.
                document.execCommand('insertText', false, token);
            } else {
                // İmleç belge içinde değil → son düzenlenebilir hücrenin sonuna ekle.
                var ok = el.querySelectorAll(EDITABLE_SELECTOR);
                var target = null;
                for (var i = ok.length - 1; i >= 0; i--) {
                    if (ok[i].getAttribute('contenteditable') === 'true') { target = ok[i]; break; }
                }
                if (!target) { toast('Önce imleci belge içine tıklayın.', 'err'); return; }
                target.focus();
                var range = document.createRange();
                range.selectNodeContents(target);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
                document.execCommand('insertText', false, token);
            }
            toast('Eklendi: ' + token, 'ok');
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
            // kimliği (data-id) ile server'a POST edilir. Backend bu sayıları
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

        // TAM_WORLD_YAPISI.md Aşama 1 — Word (.docx) içe aktarma. Seçilen dosya sunucuya
        // gönderilir (PhpWord ile HTML'e çevrilir), dönen HTML editorün içeriğinin
        // YERİNE konur (mevcut içerik kaybolur — kullanıcı önce onaylar). Sonuç henüz
        // KAYDEDilMEZ — kullanıcı gözden geçirip 💾 Kaydet'e basmalı.
        function importWordFile(input) {
            if (!input.files || !input.files[0]) return;
            if (!IMPORT_WORD_URL) { toast('İçe aktarma adresi bulunamadı.', 'err'); input.value = ''; return; }
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); input.value = ''; return; }
            if (!confirm('Word dosyasını içe aktarmak MEVCUT belge içeriğinin üzerine yazacak (henüz kaydedilmez, gözden geçirebilirsiniz; ayrıca taslak kütüphanesine de kaydedilecek). Devam edilsin mi?')) {
                input.value = '';
                return;
            }
            // 16.08 5. tur - kullanici istegi: eski taslak SILINMESIN, Word yuklenince
            // ONAY sonrasi hem editore yuklenir HEM DE (isim verilirse) Taslak Kutuphanesi'ne
            // AYRI bir surum olarak kaydedilir - aktif sablon SADECE Kaydet'e basilirsa
            // degisir, eski surumler kutuphanede kalir.
            var suggested = input.files[0].name.replace(/\.docx$/i, '');
            var draftName = DRAFTS_URL ? prompt('Bu Word belgesine taslak kutuphanesinde bir isim verin (ornek: WORLD_PC). Bos birakirsaniz kutuphaneye kaydedilmez, sadece editore yuklenir:', suggested) : null;

            var fd = new FormData();
            fd.append('file', input.files[0]);
            toast('Word dosyası içe aktarılıyor...', 'ok');

            fetch(IMPORT_WORD_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: fd
            })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.ok) {
                    toast(res.data.message || 'İçe aktarma başarısız.', 'err');
                    return;
                }
                var el = document.getElementById('doc-editor');
                el.innerHTML = res.data.html;
                applyCellLocks(el);
                if (draftName && DRAFTS_URL) {
                    fetch(DRAFTS_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                        body: JSON.stringify({ name: draftName, content_data: res.data.html, source: 'word_import' })
                    }).catch(function () {});
                }
                toast(draftName ? ("Word icerigi aktarildi ve '" + draftName + "' adiyla kutuphaneye kaydedildi. Kontrol edip Kaydet'e basin.") : "Word icerigi aktarildi, kontrol edip Kaydet'e basin.", 'ok');
            })
            .catch(function (err) {
                toast('Ice aktarma hatasi: ' + err.message, 'err');
            })
            .finally(function () {
                input.value = '';
            });
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

        // ── 16.08 5. tur — BİÇİMLENDİRME ARAÇ ÇUBUĞU (Windows Word/Dabnet benzeri) ──
        // Mevcut contenteditable motoruna dokunmadan document.execCommand kullanır —
        // hücre kilitleri + canlı matematik motoru ETKİLENMEZ (TipTap geçiş riski YOK).
        function fmt(cmd, value) {
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            var editor = document.getElementById('doc-editor');
            editor.focus();
            var sel = window.getSelection && window.getSelection();
            var anchor = sel && sel.anchorNode;
            if (anchor && isLockedCell(anchor.nodeType === 3 ? anchor.parentNode : anchor)) {
                toast('Bu alan kilitli olduğu için biçimlendirilemez.', 'err');
                return;
            }
            try {
                document.execCommand(cmd, false, value || null);
            } catch (e) {
                toast('Biçimlendirme uygulanamadı: ' + e.message, 'err');
            }
        }

        function setFontName(name) {
            if (!name) return;
            fmt('fontName', name);
        }

        // execCommand('fontSize') yalnızca 1-7 eski ölçek destekler; gerçek px
        // boyutu için execCommand ile geçici <font size=7> üretip px'e çeviriyoruz
        // (Word/Dabnet'teki gibi serbest punto seçimi).
        function setFontSize(px) {
            if (!px) return;
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            var editor = document.getElementById('doc-editor');
            editor.focus();
            var sel = window.getSelection && window.getSelection();
            if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
                toast('Önce boyutunu değiştirmek istediğiniz metni seçin.', 'err');
                return;
            }
            var anchor = sel.anchorNode;
            if (anchor && isLockedCell(anchor.nodeType === 3 ? anchor.parentNode : anchor)) {
                toast('Bu alan kilitli olduğu için biçimlendirilemez.', 'err');
                return;
            }
            document.execCommand('fontSize', false, '7');
            var marks = editor.querySelectorAll('font[size="7"]');
            for (var i = 0; i < marks.length; i++) {
                marks[i].removeAttribute('size');
                marks[i].style.fontSize = px + 'px';
            }
        }

        function insertTableAtCursor() {
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            var rows = parseInt(prompt('Satır sayısı:', '3'), 10);
            var cols = parseInt(prompt('Sütun sayısı:', '3'), 10);
            if (!rows || !cols || rows < 1 || cols < 1 || rows > 30 || cols > 15) { toast('Geçersiz satır/sütun sayısı.', 'err'); return; }
            var html = '<table style="width:100%;border-collapse:collapse;" border="1"><tbody>';
            for (var r = 0; r < rows; r++) {
                html += '<tr>';
                for (var c = 0; c < cols; c++) {
                    html += '<td contenteditable="true" style="padding:4px;border:1px solid #94a3b8;">&nbsp;</td>';
                }
                html += '</tr>';
            }
            html += '</tbody></table><p contenteditable="true">&nbsp;</p>';
            document.getElementById('doc-editor').focus();
            document.execCommand('insertHTML', false, html);
            toast('Tablo eklendi', 'ok');
        }

        function insertImageAtCursor(input) {
            if (!input.files || !input.files[0]) return;
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); input.value = ''; return; }
            var file = input.files[0];
            var reader = new FileReader();
            reader.onload = function (e) {
                var html = '<img src="' + e.target.result + '" style="max-width:300px;height:auto;">';
                document.getElementById('doc-editor').focus();
                document.execCommand('insertHTML', false, html);
                toast('Resim eklendi — üzerine tıklayıp köşesinden sürükleyerek boyutlandırabilirsiniz.', 'ok');
            };
            reader.onerror = function () { toast('Resim okunamadı.', 'err'); };
            reader.readAsDataURL(file);
            input.value = '';
        }

        // ── 16.08 5. tur — RESİM SÜRÜKLE-BOYUTLANDIR (TAM_WORLD_YAPISI.md Aşama 4) ──
        // Tutamalar #doc-editor'ün DIŞINDA (document.body'e eklenir) — kaydedilen
        // içeriğe (innerHTML) ASLA karışmaz, saveDoc() etkilenmez.
        var IMG_RESIZE = null;

        function initImageResize() {
            var editor = document.getElementById('doc-editor');
            if (!editor) return;
            editor.addEventListener('click', function (e) {
                // 16.08 16. tur — Taşı Modu AÇIKKEN resimler artık findMovableBlock()
                // üzerinden GENEL taşı/boyutlandır/sil tutamaçlarını kullanıyor (serbest
                // konum). Bu ESKİ (sadece oranlı boyutlandırma) sistem, iki ayrı tutamaç
                // takımının üst üste binmemesi için SADECE Taşı Modu KAPALIYKEN çalışır.
                if (MOVE_MODE) { hideResizeHandles(); return; }
                if (e.target && e.target.tagName === 'IMG' && !isLockedCell(e.target) && READ_ONLY !== true) {
                    showResizeHandles(e.target);
                } else {
                    hideResizeHandles();
                }
            });
            document.getElementById('doc-editor').closest('.editor-wrap').addEventListener('scroll', function () {
                if (IMG_RESIZE) positionOverlay(IMG_RESIZE.img);
            });
        }

        function showResizeHandles(img) {
            hideResizeHandles();
            var overlay = document.createElement('div');
            overlay.id = 'img-resize-overlay';
            ['nw', 'ne', 'sw', 'se'].forEach(function (pos) {
                var h = document.createElement('div');
                h.className = 'img-resize-handle handle-' + pos;
                h.addEventListener('mousedown', function (e) { startResize(e, img, pos); });
                overlay.appendChild(h);
            });
            document.body.appendChild(overlay);
            img.classList.add('aykome-img-selected');
            IMG_RESIZE = { img: img, overlay: overlay };
            positionOverlay(img);
        }

        function positionOverlay(img) {
            if (!IMG_RESIZE) return;
            var r = img.getBoundingClientRect();
            var o = IMG_RESIZE.overlay;
            o.style.left = r.left + 'px';
            o.style.top = r.top + 'px';
            o.style.width = r.width + 'px';
            o.style.height = r.height + 'px';
        }

        function hideResizeHandles() {
            if (IMG_RESIZE) {
                IMG_RESIZE.img.classList.remove('aykome-img-selected');
                IMG_RESIZE.overlay.remove();
            }
            IMG_RESIZE = null;
        }

        function startResize(e, img, corner) {
            e.preventDefault();
            e.stopPropagation();
            var startX = e.clientX;
            var startW = img.getBoundingClientRect().width;
            var startH = img.getBoundingClientRect().height;
            var ratio = startW / (startH || 1);
            function onMove(ev) {
                var dx = ev.clientX - startX;
                var dir = (corner === 'ne' || corner === 'se') ? 1 : -1;
                var newW = Math.max(30, startW + dx * dir);
                var newH = newW / ratio;
                img.style.width = newW + 'px';
                img.style.height = newH + 'px';
                positionOverlay(img);
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }

        // ── 16.08 11. tur — SERBEST BLOK TAŞIMA ("tutup istediğim yere sürükleme") ──
        // Kullanıcı isteği: geniş hücre/paragraf bloklarını (ör. "Tesis Kontrol/
        // Yetkilisi") fareyle tutup cm hassasiyetinde istediği yere taşıyabilsin.
        // GÜVENLİK: kilitli hücreler (isLockedCell) VE canlı matematik izleyen
        // hücreler (data-aykome-surface/col, .sync-dom-value) TAŞINAMAZ — fiyat
        // hesaplaması / belediye yetki kilitleri bozulmasın diye.
        var MOVE_MODE = false;
        var MOVE_HOVER = null;
        var MOVE_DRAG = null;

        function toggleMoveMode() {
            MOVE_MODE = !MOVE_MODE;
            document.getElementById('doc-editor').classList.toggle('move-mode-active', MOVE_MODE);
            var btn = document.getElementById('move-mode-btn');
            if (btn) btn.classList.toggle('active', MOVE_MODE);
            if (!MOVE_MODE) removeMoveHandles();
            toast(MOVE_MODE
                ? 'Taşıma modu AÇIK — bir bloğun üzerine gelip ✥ simgesinden tutup sürükleyin'
                : 'Taşıma modu kapatıldı', 'ok');
        }

        // Üç ÖZEL: dogrudan tiklanan en KUCUK anlamli blogu bulur (td > p > div ...).
        // 16.08 16. tur — kullanıcı raporu: bir hücre içindeki logoyu (img) sadece
        // çevresindeki hücreyle AYNI hizada taşıyabiliyordu — çünkü "img" bu listede
        // yoktu, tıklama her zaman EN YAKIN td/p/div'e çıkıyordu. Artık resmin
        // üzerine gelindiğinde/tıklandığında RESMİN KENDiSi seçiliyor — hücreden
        // BAĞIMSIZ, istediği her yere sürüklenebilir.
        function findMovableBlock(el) {
            var selector = 'td, th, p, div, li, h1, h2, h3, h4, img';
            var editor = document.getElementById('doc-editor');
            while (el && el !== editor && el.nodeType) {
                if (el.nodeType === 1 && el.matches && el.matches(selector)) {
                    if (isLockedCell(el)) return null;
                    if (el.hasAttribute('data-aykome-surface') || el.hasAttribute('data-aykome-fee')
                        || el.querySelector('[data-aykome-col], .sync-dom-value')) return null;
                    if (el.hasAttribute('data-aykome-col') || el.classList.contains('sync-dom-value')) return null;
                    // 16.08 14. tur — kullanıcı raporu: ".a4-footer" içindeki bir hücreyi
                    // (ör. "Tesis Kontrol/Yetkilisi") serbest taşıyla konumlandırınca,
                    // başvuru modulünde AYNI MESAFEDE çıkmıyordu. KÖK NEDEN: ".a4-footer"
                    // editörde BiLiNÇLi olarak position:static (9. tur — içerik kısaysa dev
                    // boşluk oluşmasın diye), AMA gerçek çıktıda (PDF/önizleme) HER ZAMAN
                    // position:absolute;bottom:10mm kalıyor (imza/doğrulama sayfa dibine
                    // sabit). Yani bu alanın konum REFERANS NOKTASI editörde ile gerçek
                    // çıktıda FARKLI — serbest sürükleme ile eklenen px offset iki yerde
                    // asla aynı görsel mesafeye denk gelmez. Güvenli çözüm: bu alanı
                    // serbest taşımadan MUAF tut (kilitli hücre muamelesiyle aynı);
                    // kullanıcı hala normal metin düzenleyebilir, sadece ✥ ile
                    // sürükleyemez.
                    if (el.closest('.a4-footer')) return null;
                    return el;
                }
                el = el.parentElement;
            }
            return null;
        }

        function initBlockMove() {
            var editor = document.getElementById('doc-editor');
            if (!editor) return;

            // 16.08 16. tur — kullanıcı raporu: "hangi hücreye tıklasam [tutamaçlar]
            // gelsin, yoksa yakalayamıyorum" + "silme X'e basamıyorum, Delete'te
            // çalışmıyor". KÖK NEDEN: tutamaçlar sadece fare TAM üzerindeyken
            // (hover) görünüyordu, fare hafif kaydığında ~220ms sonra kayboluyordu
            // — küçük bir butona (özellikle ✕) yetişmek zordu. Çözüm: TİKLAMA artık
            // KALıCI ("kilitli") bir seçim yapıyor — tutamaçlar fare uzaklaşsa bile
            // başka bir yere tıklanıncaya kadar EKRANDA KALIR. `mousedown`
            // (capture fazında, contenteditable'ın kendi imleç yerleştirmesinden
            // ÖNCE) ile metin imleci bilerek YERLEŞTİRİLMİYOR — Taşı Modu açıkken
            // bir bloğa tıklamak HER ZAMAN "bu bloğu seç" demektir, metin düzenleme
            // ile karışmaz (metin düzenlemek için kullanıcı önce Taşı Modu'nu kapatır).
            // Bu aynı zamanda Delete tuşunu da GÜVENİLİR hale getirir: seçili blok
            // artık metin imleci ALMADIĞI için "kullanıcı yazı mı yazıyor" belirsizliği
            // ortadan kalkar.
            editor.addEventListener('mousedown', function (e) {
                if (!MOVE_MODE) return;
                var block = findMovableBlock(e.target);
                if (!block) return;
                e.preventDefault();
                showMoveHandles(block, true);
            }, true);

            // Hover: sadece KİLİTLİ (tıklanmış) bir seçim YOKKEN önizleme amaçlı
            // gösterir — kilitli seçimi fare geçişiyle değiştirmez.
            editor.addEventListener('mouseover', function (e) {
                if (!MOVE_MODE || (MOVE_DRAG && MOVE_DRAG.active)) return;
                if (MOVE_HOVER && MOVE_HOVER.locked) return;
                var block = findMovableBlock(e.target);
                if (block) showMoveHandles(block, false);
            });

            // Editör içinde boş bir alana veya editör dışına tıklanınca kilitli
            // seçim kaldırılır (yeni bir bloğa tıklamak zaten kendi mousedown'ında
            // seçimi değiştirir).
            document.addEventListener('mousedown', function (e) {
                if (!MOVE_MODE || !MOVE_HOVER || !MOVE_HOVER.locked) return;
                if (editor.contains(e.target) && findMovableBlock(e.target)) return;
                removeMoveHandles();
            });

            // Escape ile kilitli seçimi kaldır (klasik, beklenen davranış).
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && MOVE_HOVER && MOVE_HOVER.locked) {
                    removeMoveHandles();
                }
            });

            document.getElementById('doc-editor').closest('.editor-wrap').addEventListener('scroll', function () {
                if (MOVE_HOVER) positionMoveHandles(MOVE_HOVER.block);
            });
        }

        function showMoveHandles(block, locked) {
            if (MOVE_HOVER && MOVE_HOVER.block === block) {
                if (locked) MOVE_HOVER.locked = true;
                positionMoveHandles(block);
                return;
            }
            removeMoveHandles();
            var moveBtn = document.createElement('div');
            moveBtn.id = 'block-move-handle';
            moveBtn.textContent = '✥';
            moveBtn.title = 'Tutup sürükleyerek taşıyın';
            moveBtn.addEventListener('mousedown', function (e) { startBlockDrag(e, block); });

            var resetBtn = document.createElement('div');
            resetBtn.id = 'block-reset-handle';
            resetBtn.textContent = '⇺';
            resetBtn.title = 'Normal konuma geri döndür (boyut da sıfırlanır)';
            resetBtn.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); });
            resetBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); resetBlockPosition(block); });

            // 16.08 12. tur — DARALTMA/GENİŞLETME tutamacı (sağ-alt köşe).
            var resizeBtn = document.createElement('div');
            resizeBtn.id = 'block-resize-handle';
            resizeBtn.title = 'Köşeden tutup sürükleyerek hücreyi daraltın/genişletin';
            resizeBtn.addEventListener('mousedown', function (e) { startBlockResize(e, block); });
            resizeBtn.addEventListener('mouseenter', cancelHideMoveHandles);
            resizeBtn.addEventListener('mouseleave', scheduleHideMoveHandles);

            // 16.08 13. tur — SİLME tutamacı (sağ-üst köşe).
            var deleteBtn = document.createElement('div');
            deleteBtn.id = 'block-delete-handle';
            deleteBtn.textContent = '✕';
            deleteBtn.title = 'Bu hücreyi/bloğu kalıcı olarak sil';
            deleteBtn.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); });
            deleteBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); deleteBlock(block); });
            deleteBtn.addEventListener('mouseenter', cancelHideMoveHandles);
            deleteBtn.addEventListener('mouseleave', scheduleHideMoveHandles);

            document.body.appendChild(moveBtn);
            document.body.appendChild(resetBtn);
            document.body.appendChild(resizeBtn);
            document.body.appendChild(deleteBtn);
            MOVE_HOVER = { block: block, moveBtn: moveBtn, resetBtn: resetBtn, resizeBtn: resizeBtn, deleteBtn: deleteBtn, locked: !!locked };
            positionMoveHandles(block);

            block.addEventListener('mouseleave', scheduleHideMoveHandles);
            moveBtn.addEventListener('mouseenter', cancelHideMoveHandles);
            moveBtn.addEventListener('mouseleave', scheduleHideMoveHandles);
            resetBtn.addEventListener('mouseenter', cancelHideMoveHandles);
            resetBtn.addEventListener('mouseleave', scheduleHideMoveHandles);
        }

        function positionMoveHandles(block) {
            if (!MOVE_HOVER) return;
            var r = block.getBoundingClientRect();
            MOVE_HOVER.moveBtn.style.left = r.left + 'px';
            MOVE_HOVER.moveBtn.style.top = Math.max(0, r.top - 26) + 'px';
            MOVE_HOVER.resetBtn.style.left = (r.left + 28) + 'px';
            MOVE_HOVER.resetBtn.style.top = Math.max(0, r.top - 25) + 'px';
            MOVE_HOVER.resizeBtn.style.left = (r.right - 7) + 'px';
            MOVE_HOVER.resizeBtn.style.top = (r.bottom - 7) + 'px';
            MOVE_HOVER.deleteBtn.style.left = (r.right - 22) + 'px';
            MOVE_HOVER.deleteBtn.style.top = Math.max(0, r.top - 25) + 'px';
        }

        var MOVE_HIDE_TIMER = null;
        function scheduleHideMoveHandles() {
            // 16.08 16. tur — kilitli (tıklanmış) bir seçim fare uzaklaştı diye
            // KAYBOLMAZ; sadece başka bir yere tıklayınca veya Escape ile kapanır.
            if (MOVE_HOVER && MOVE_HOVER.locked) return;
            clearTimeout(MOVE_HIDE_TIMER);
            MOVE_HIDE_TIMER = setTimeout(function () {
                if (!MOVE_DRAG || !MOVE_DRAG.active) removeMoveHandles();
            }, 220);
        }
        function cancelHideMoveHandles() { clearTimeout(MOVE_HIDE_TIMER); }

        function removeMoveHandles() {
            if (MOVE_HOVER) {
                MOVE_HOVER.moveBtn.remove();
                MOVE_HOVER.resetBtn.remove();
                MOVE_HOVER.resizeBtn.remove();
                MOVE_HOVER.deleteBtn.remove();
            }
            MOVE_HOVER = null;
        }

        // Serbest konuma geçirir (ilk sürüklemede GORSEL OLARAK YER DEGISTIRMEZ —
        // mevcut render konumu px'e sabitlenip oradan devam edilir) ve fareyi takip eder.
        function startBlockDrag(e, block) {
            e.preventDefault();
            e.stopPropagation();
            var editor = document.getElementById('doc-editor');
            var editorRect = editor.getBoundingClientRect();
            var blockRect = block.getBoundingClientRect();

            if (block.getAttribute('data-aykome-free-position') !== '1') {
                var startLeftPx = blockRect.left - editorRect.left + editor.scrollLeft;
                var startTopPx = blockRect.top - editorRect.top + editor.scrollTop;
                block.style.position = 'absolute';
                block.style.left = startLeftPx + 'px';
                block.style.top = startTopPx + 'px';
                block.style.margin = '0';
                block.style.zIndex = '400';
                block.setAttribute('data-aykome-free-position', '1');
            }

            var startMouseX = e.clientX;
            var startMouseY = e.clientY;
            var origLeft = parseFloat(block.style.left) || 0;
            var origTop = parseFloat(block.style.top) || 0;
            MOVE_DRAG = { active: true };

            function onMove(ev) {
                var dx = ev.clientX - startMouseX;
                var dy = ev.clientY - startMouseY;
                var newLeft = origLeft + dx;
                var newTop = origTop + dy;
                block.style.left = newLeft + 'px';
                block.style.top = newTop + 'px';
                positionMoveHandles(block);
                showPositionReadout(ev.clientX, ev.clientY, newLeft, newTop);
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                MOVE_DRAG.active = false;
                hidePositionReadout();
                toast('Konum güncellendi — kalıcı olması için 💾 Kaydet\'e basın.', 'ok');
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }

        // Bloğu tekrar normal (akış içi) konuma döndürür — serbest konum VE özel
        // boyut (16.08 12. tur daraltma/genişletme) tamamen kaldırılır.
        function resetBlockPosition(block) {
            block.style.position = '';
            block.style.left = '';
            block.style.top = '';
            block.style.margin = '';
            block.style.zIndex = '';
            block.style.width = '';
            block.style.height = '';
            block.removeAttribute('data-aykome-free-position');
            removeMoveHandles();
            toast('Blok normal konumuna/boyutuna döndürüldü.', 'ok');
        }

        // 16.08 13. tur — HÜCRE/BLOK SİLME. Kilit/matematik koruması zaten
        // findMovableBlock() üzerinden miras alınır (bu tutamaçlar o korumalı
        // hücrelerde hiç gösterilmez). Geri alınamaz olduğu için onay istenir —
        // Kaydet'e basılmadan sayfa yenilenirse silme de geri alınmış olur.
        function deleteBlock(block) {
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            if (!confirm('Bu hücre/blok KALICI olarak silinsin mi? (Sayfayı Kaydet\'e basmadan yenilerseniz silme işlemi geri alınır.)')) {
                return;
            }
            removeMoveHandles();
            block.remove();
            toast('Blok silindi — kalıcı olması için 💾 Kaydet\'e basın.', 'ok');
        }

        // 16.08 12. tur — HÜCRE DARALTMA/GENİŞLETME: sağ-alt köşeden tutup
        // sürükleyerek bloğun genişlik/yükseklik piksel değerini değiştirir
        // (resim resize'dan farkı: en-boy oranı SABiT TUTULMAZ — metin blokları
        // bağımsız genişlik/yükseklik ister).
        function startBlockResize(e, block) {
            e.preventDefault();
            e.stopPropagation();
            var rect = block.getBoundingClientRect();
            var startW = rect.width;
            var startH = rect.height;
            var startX = e.clientX;
            var startY = e.clientY;

            // Ilk boyutlandirmada mevcut px genisligi/yuksekligi sabitle (gorsel sicrama olmaz).
            block.style.width = startW + 'px';
            block.style.boxSizing = 'border-box';

            function onMove(ev) {
                var dx = ev.clientX - startX;
                var dy = ev.clientY - startY;
                var newW = Math.max(40, startW + dx);
                var newH = Math.max(20, startH + dy);
                block.style.width = newW + 'px';
                block.style.height = newH + 'px';
                positionMoveHandles(block);
                showPositionReadout(ev.clientX, ev.clientY, newW, newH, true);
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                hidePositionReadout();
                toast('Boyut güncellendi — kalıcı olması için 💾 Kaydet\'e basın.', 'ok');
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }

        function showPositionReadout(mouseX, mouseY, leftPx, topPx, isSize) {
            var el = document.getElementById('move-readout');
            if (!el) {
                el = document.createElement('div');
                el.id = 'move-readout';
                document.body.appendChild(el);
            }
            var cmA = (leftPx / 96 * 2.54).toFixed(1);
            var cmB = (topPx / 96 * 2.54).toFixed(1);
            el.textContent = isSize
                ? ('Genişlik: ' + cmA + ' cm  •  Yükseklik: ' + cmB + ' cm')
                : ('X: ' + cmA + ' cm  •  Y: ' + cmB + ' cm');
            el.style.left = (mouseX + 16) + 'px';
            el.style.top = (mouseY + 16) + 'px';
        }

        function hidePositionReadout() {
            var el = document.getElementById('move-readout');
            if (el) el.remove();
        }

        // 16.08 12. tur — SEÇİMİ AYIR: bir hücre/paragraf içindeki BELİRLİ bir cümleyi/
        // metni seçip AYRI, bağımsız taşınabilir bir hücre haline getirir. Kullanıcı
        // örneği: "Tesis Kontrol / Yetkilisi" gibi geniş bir hücredeki tek bir cümleyi
        // ayırıp başka bir yere taşımak.
        function splitSelectionIntoBlock() {
            if (READ_ONLY === true) { toast('Bu belge salt-okunurdur.', 'err'); return; }
            var sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
                toast('Önce ayırmak istediğiniz metni (bir cümle vb.) seçin.', 'err');
                return;
            }
            var range = sel.getRangeAt(0);
            var editor = document.getElementById('doc-editor');
            if (!editor.contains(range.commonAncestorContainer)) {
                toast('Seçim belge içinde olmalı.', 'err');
                return;
            }
            var anchorEl = range.commonAncestorContainer.nodeType === 3
                ? range.commonAncestorContainer.parentElement
                : range.commonAncestorContainer;
            if (isLockedCell(anchorEl)) {
                toast('Bu alan kilitli olduğu için ayrılamaz.', 'err');
                return;
            }
            if (anchorEl.closest && anchorEl.closest('[data-aykome-surface], [data-aykome-fee], [data-aykome-col], .sync-dom-value')) {
                toast('Bu hücre canlı fiyat hesaplamasına bağlı olduğu için ayrılamaz.', 'err');
                return;
            }

            var sourceBlock = findMovableBlock(anchorEl) || anchorEl;
            var extracted;
            try {
                extracted = range.extractContents();
            } catch (e) {
                toast('Seçim ayrılamadı: ' + e.message, 'err');
                return;
            }
            if (!extracted || !extracted.textContent || !extracted.textContent.trim()) {
                toast('Seçili alan boş görünüyor.', 'err');
                return;
            }

            var newBlock = document.createElement('div');
            newBlock.setAttribute('contenteditable', 'true');
            newBlock.style.cssText = 'display:inline-block; padding:2px 4px;';
            newBlock.appendChild(extracted);

            if (sourceBlock && sourceBlock.parentNode) {
                sourceBlock.parentNode.insertBefore(newBlock, sourceBlock.nextSibling);
            } else {
                range.insertNode(newBlock);
            }

            sel.removeAllRanges();
            toast('Yeni bağımsız hücre oluşturuldu — Taşı Modu ile istediğiniz yere sürükleyin.', 'ok');
            if (!MOVE_MODE) toggleMoveMode();
            showMoveHandles(newBlock);
        }

        // ── 16.08 5. tur — TASLAK KÜTÜPHANESİ (elle veya Word'den kaydedilen çoklu sürüm) ──
        var DRAFT_PANEL_OPEN = false;

        function toggleDraftPanel() {
            DRAFT_PANEL_OPEN = !DRAFT_PANEL_OPEN;
            var p = document.getElementById('draft-panel');
            if (p) p.classList.toggle('panel-open', DRAFT_PANEL_OPEN);
            document.body.classList.toggle('panel-open', DRAFT_PANEL_OPEN);
            if (DRAFT_PANEL_OPEN) loadDraftsList();
        }

        function escapeHtmlText(s) {
            var d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        function loadDraftsList() {
            if (!DRAFTS_URL) return;
            var wrap = document.getElementById('draft-list');
            if (!wrap) return;
            wrap.innerHTML = '<div style="color:#94a3b8;font-size:12px;">Yükleniyor...</div>';
            fetch(DRAFTS_URL, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                wrap.innerHTML = '';
                if (!data.drafts || !data.drafts.length) {
                    wrap.innerHTML = '<div style="color:#94a3b8;font-size:12px;">Henüz kayıtlı taslak yok. Word içe aktarırken isim verin ya da "Farklı Kaydet" ile ekleyin.</div>';
                    return;
                }
                data.drafts.forEach(function (d) {
                    var icon = d.source === 'word_import' ? '📄' : '✍️';
                    var card = document.createElement('div');
                    card.className = 'draft-card';
                    card.innerHTML = '<div class="draft-card-name">' + icon + ' ' + escapeHtmlText(d.name) + '</div>'
                        + '<div class="draft-card-meta">' + escapeHtmlText(d.created_at || '') + '</div>'
                        + '<div class="draft-card-actions"><button type="button" class="draft-btn-load">📥 Yükle</button> <button type="button" class="draft-btn-del">🗑 Sil</button></div>';
                    card.querySelector('.draft-btn-load').addEventListener('click', function () { loadDraftIntoEditor(d.id, d.name); });
                    card.querySelector('.draft-btn-del').addEventListener('click', function () { deleteDraft(d.id); });
                    wrap.appendChild(card);
                });
            })
            .catch(function () { wrap.innerHTML = '<div style="color:#dc2626;font-size:12px;">Taslaklar yüklenemedi.</div>'; });
        }

        function saveAsNewDraft() {
            if (!DRAFTS_URL) return;
            var name = prompt('Bu taslağa bir isim verin (örn: WORLD_PC, Kendi Yazdığım Taslak):', '');
            if (!name) return;
            var content;
            try { content = collectContent(); } catch (e) { toast(e.message, 'err'); return; }
            fetch(DRAFTS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ name: name, content_data: content, source: 'manual' })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) { toast('"' + name + '" taslak olarak kaydedildi.', 'ok'); loadDraftsList(); }
                else { toast(data.message || 'Kaydedilemedi.', 'err'); }
            })
            .catch(function (err) { toast('Hata: ' + err.message, 'err'); });
        }

        function loadDraftIntoEditor(id, name) {
            if (!confirm('"' + name + '" taslağı editöre yüklensin mi? Editördeki kaydedilmemiş değişiklikleriniz kaybolur. Aktif şablon SADECE Kaydet basilinca degisir.')) return;
            fetch(DRAFTS_URL + '/' + id, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { toast('Taslak yüklenemedi.', 'err'); return; }
                var el = document.getElementById('doc-editor');
                el.innerHTML = data.html;
                applyCellLocks(el);
                toggleDraftPanel();
                toast('"' + name + '" editöre yüklendi, kontrol edip Kaydet e basin.', 'ok');
            })
            .catch(function (err) { toast('Hata: ' + err.message, 'err'); });
        }

        function deleteDraft(id) {
            if (!confirm('Bu taslak kalıcı olarak silinsin mi?')) return;
            fetch(DRAFTS_URL + '/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } })
            .then(function (r) { return r.json(); })
            .then(function () { toast('Taslak silindi.', 'ok'); loadDraftsList(); })
            .catch(function (err) { toast('Hata: ' + err.message, 'err'); });
        }

        // ── CANLI DOM MATEMATiĞi (GÖREV 2 / AykomeMath aynası) ────
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
        initImageResize();
        initBlockMove();

        // BİLGİ KATMANI: salt-okunur durumda panel gizlenir; değilse katalog çizilir.
        if (READ_ONLY === true) {
            var fpT = document.getElementById('fp-toggle');
            var fpP = document.getElementById('field-panel');
            if (fpT) fpT.style.display = 'none';
            if (fpP) fpP.style.display = 'none';
        } else {
            renderCatalog();
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (READ_ONLY === true) return;
                saveDoc();
                return;
            }
            // 16.08 14. tur — kullanıcı raporu: "silme butonu bazen ayarlamıyor,
            // koordinatların dolayı silme tuşu gelmiyor" — blok uzak bir konuma
            // taşınınca küçük ✕ tutamacı ekran dışına/yanlış yere denk gelebiliyordu.
            // Klavyeden Delete/Backspace artık AYNI silme işlemini tetikler — ama
            // SADECE Taşı Modu AÇIKKEN, üzerine gelinmiş (MOVE_HOVER) bir blok
            // varken VE kullanıcı o an bir metin alanında YAZI YAZMIYORKEN (aksi
            // halde normal metin düzenlemede Backspace/Delete ile karakter silmeyi
            // KIRARDI).
            if ((e.key === 'Delete' || e.key === 'Backspace') && MOVE_MODE && MOVE_HOVER && MOVE_HOVER.block) {
                if (READ_ONLY === true) return;
                if (isCaretInsideEditableText()) return;
                if (!document.body.contains(MOVE_HOVER.block)) return;
                e.preventDefault();
                deleteBlock(MOVE_HOVER.block);
            }
        });

        // Seçim imleci (caret) şu an düzenlenebilir bir metin alanının İÇİNDE mi?
        // Delete/Backspace'in blok silmeyi mi yoksa normal metin düzenlemeyi mi
        // hedeflemesi gerektiğini ayırt etmek için kullanılır.
        function isCaretInsideEditableText() {
            var active = document.activeElement;
            if (active && active.isContentEditable) return true;
            var sel = window.getSelection ? window.getSelection() : null;
            if (!sel || sel.rangeCount === 0 || !sel.anchorNode) return false;
            var node = sel.anchorNode;
            var el = node.nodeType === 1 ? node : node.parentElement;
            return !!(el && el.closest && el.closest('[contenteditable="true"]'));
        }
    </script>
</body>
</html>
