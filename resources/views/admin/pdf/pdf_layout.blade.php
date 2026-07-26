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
  position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
  background: #1e293b; color: #fff; height: 48px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.no-print-bar .title { font-size: 14px; font-weight: 600; }
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

@media print {
  body { background: white; padding: 0; display: block; }
  .a4-container {
    width: 100%; box-shadow: none; padding: 0; margin: 0; min-height: auto;
  }
  .no-print-bar { display: none !important; }
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
  </div>
</div>
<div class="a4-container">
@yield('content')
</div>
</body>
</html>
