<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>@yield('title', 'Belge')</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  background-color: #f3f4f6;
  margin: 0;
  padding-top: 50px;
  display: flex;
  justify-content: center;
  font-family: 'Times New Roman', Times, serif;
}

.no-print-bar {
  position: fixed; top: 1rem; left: 1rem; right: auto; z-index: 99999;
  background: #1e293b; color: #fff;
  display: flex; flex-direction: row; align-items: center; gap: 10px;
  padding: 8px 14px; border-radius: 10px; box-shadow: 0 5px 14px rgba(0,0,0,.35);
}
.no-print-bar .title { font-size: 14px; font-weight: 600; white-space: nowrap; }
.no-print-bar .actions { display: flex; gap: 8px; align-items: center; }
.no-print-bar .btn-close {
  background: transparent; color: #94a3b8; border: 1px solid #475569;
  padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 13px;
}
.no-print-bar .btn-close:hover { background: #334155; color: #fff; }
.no-print-bar .btn-print {
  background: #2563eb; color: #fff; border: none;
  padding: 6px 20px; border-radius: 4px; cursor: pointer;
  font-size: 13px; font-weight: 700;
}
.no-print-bar .btn-print:hover { background: #1d4ed8; }

.a4-container {
  background: #fff;
  width: @yield('page_width', '210mm');
  min-height: @yield('page_height', '297mm');
  padding: @yield('page_padding', '15mm');
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  margin: 0 auto;
  box-sizing: border-box;
}

/* Ortak kurum logosu boyutlandırma */
.print-logo {
  max-width: 140px !important;
  width: auto;
  height: auto;
  object-fit: contain;
}

/* Vanilla JS Mini Format Toolbar (yalnızca ekranda, print'te gizli) */
.toolbar {
  position: fixed; bottom: 14px; right: 14px; z-index: 99999;
  display: flex; gap: 5px; align-items: center;
  background: #1e3a8a; padding: 7px 9px; border-radius: 9px;
  box-shadow: 0 5px 14px rgba(0,0,0,.35);
}
.toolbar button {
  background: #2563eb; color: #fff; border: none;
  min-width: 32px; height: 32px; border-radius: 6px;
  font-weight: 700; cursor: pointer; font-size: 13px; line-height: 1;
}
.toolbar button:hover { background: #1d4ed8; }
.toolbar .sep { width: 1px; height: 20px; background: rgba(255,255,255,.25); margin: 0 2px; }
@media print { .toolbar { display: none !important; } }

@media print {
  body { background: white; padding: 0; display: block; }
  .a4-container {
    width: 100%; box-shadow: none; padding: 0; margin: 0; min-height: auto;
  }
  .no-print-bar { display: none !important; }
  /* KATI A4 SINIRI: içerik sayfa yüksekliğini aşarsa taşan kısım görünmez — 2. boş sayfa engellenir */
  .a4-container, .a4-container * { page-break-inside: avoid; }
  @page { size: @yield('page_size', 'A4'); margin: @yield('page_margin', '15mm'); }
}
@yield('extra_style')
</style>
@yield('head')
</head>
<body>
<div class="no-print-bar">
  <span class="title">@yield('title', 'Belge Önizleme')</span>
  <div class="actions">
    <button class="btn-close" onclick="window.close()">✕ Kapat</button>
    <button class="btn-print" onclick="window.print()">🖨️ Yazdır / PDF Kaydet</button>
    <button class="btn-print" onclick="window.print()">💾 Şablonu Düzenle (Kaydet)</button>
  </div>
</div>
<div class="a4-container">
@yield('content')
</div>

<!-- Vanilla JS Mini Format Toolbar -->
<div class="toolbar no-print">
    <button onclick="fmtCmd('bold')" title="Kalın (Ctrl+B)"><b>B</b></button>
    <button onclick="fmtCmd('italic')" title="İtalik (Ctrl+I)"><i>I</i></button>
    <button onclick="fmtCmd('underline')" title="Altı Çizili (Ctrl+U)"><u>U</u></button>
    <span class="sep"></span>
    <button onclick="fmtSize(1)" title="Yazıyı Büyüt">A+</button>
    <button onclick="fmtSize(-1)" title="Yazıyı Küçült">A−</button>
</div>

<script>
function fmtCmd(cmd) {
    document.execCommand(cmd, false, null);
}
function fmtSize(delta) {
    var sel = document.getSelection();
    var el = sel && sel.anchorNode
        ? (sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode : sel.anchorNode)
        : null;
    var size = 3; // varsayılan HTML font-size 1..7
    if (el && el.style && el.style.fontSize) {
        var px = parseFloat(el.style.fontSize);
        if (!isNaN(px)) size = Math.min(7, Math.max(1, Math.round(px / 2)));
    }
    size = Math.min(7, Math.max(1, size + delta));
    document.execCommand('fontSize', false, String(size));
    // Focus'u koru
    if (el && el.focus) el.focus();
}
</script>
</body>
</html>
