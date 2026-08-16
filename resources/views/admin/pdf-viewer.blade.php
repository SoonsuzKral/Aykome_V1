<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belge Görüntüle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; background: #525659; }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 44px;
            padding: 0 12px;
            background: #1e293b;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
        }
        .toolbar .sag { display: flex; align-items: center; gap: 14px; }
        .toolbar .durum { color: #94a3b8; font-size: 11.5px; }
        .toolbar a {
            color: #93c5fd;
            text-decoration: none;
            font-weight: 600;
        }
        .toolbar a:hover { text-decoration: underline; }
        iframe {
            width: 100%;
            height: calc(100% - 44px);
            border: none;
            display: block;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>📄 AYKOME — Belge Görüntüleyici</span>
        <div class="sag">
            <span class="durum" id="viewer-durum">Belge yükleniyor...</span>
            <a href="{{ $url }}" target="_blank" rel="noopener">Yeni sekmede aç ⤢</a>
        </div>
    </div>
    <iframe id="viewer-frame" title="Belge Önizleme"></iframe>

    <script>
        (function () {
            // İndirme yöneticisi eklentileri (IDM vb.) tarayıcının PDF navigasyonunu
            // (iframe src = doğrudan PDF URL'si) yakalayıp "indir" penceresi açabiliyor
            // — Content-Disposition:inline gönderilse bile bunu görmezden gelebiliyorlar.
            // Çözüm: PDF'i arka planda fetch() ile (XHR/fetch trafiği, indirme
            // yöneticilerinin "sayfa navigasyonu" filtresine takılmaz) Blob olarak
            // çekip, iframe'e blob: URL veriyoruz — hiçbir zaman doğrudan bir HTTP
            // navigasyonu/indirme tetiklenmiyor.
            var pdfUrl = @json($url);
            var frame = document.getElementById('viewer-frame');
            var durum = document.getElementById('viewer-durum');

            fetch(pdfUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('Belge alınamadı (HTTP ' + res.status + ')');
                    return res.blob();
                })
                .then(function (blob) {
                    var pdfBlob = (blob.type && blob.type.indexOf('pdf') !== -1) ? blob : new Blob([blob], { type: 'application/pdf' });
                    var blobUrl = URL.createObjectURL(pdfBlob);
                    frame.src = blobUrl;
                    durum.textContent = '';
                })
                .catch(function (err) {
                    durum.textContent = 'Önizleme başarısız, doğrudan yükleniyor...';
                    // Son çare: fetch başarısız olduysa (ör. eski tarayıcı) direkt iframe'e yükle.
                    frame.src = pdfUrl;
                });
        })();
    </script>
</body>
</html>
