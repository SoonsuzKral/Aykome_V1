<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Maps Yalıtılmış Test — AYKOME</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #0F172A; color: #E2E8F0; min-height: 100vh; padding: 1.5rem; }
        h1 { font-size: 1.25rem; font-weight: 700; color: #02E0FB; margin-bottom: 0.5rem; }
        p { font-size: 0.8rem; color: #94A3B8; margin-bottom: 1rem; }
        #map { width: 100%; height: 560px; border-radius: 12px; border: 2px solid #1E293B; background: #1E293B; }
        #log { margin-top: 1rem; background: #020617; border-radius: 8px; padding: 1rem; font-family: monospace; font-size: 0.75rem; min-height: 160px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; color: #94A3B8; }
        #geojson-out { margin-top: 1rem; background: #020617; border-radius: 8px; padding: 1rem; font-family: monospace; font-size: 0.75rem; min-height: 80px; color: #4ADE80; white-space: pre-wrap; }
        .status-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #6B7280; flex-shrink: 0; }
        .dot.green { background: #22C55E; box-shadow: 0 0 6px #22C55E; }
        .dot.red { background: #EF4444; box-shadow: 0 0 6px #EF4444; }
        .dot.yellow { background: #EAB308; box-shadow: 0 0 6px #EAB308; }
        label { display: block; font-size: 0.7rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.3rem; margin-top: 1rem; }
        a.back { display: inline-block; margin-bottom: 1rem; font-size: 0.8rem; color: #02E0FB; text-decoration: none; }
        a.back:hover { text-decoration: underline; }
    </style>
</head>
<body>

<a class="back" href="{{ route('admin.map.index') }}">← Harita İzleme</a>
<h1>🗺️ Google Maps Yalıtılmış Test Sayfası</h1>
<p>Bu sayfa sidebar, Tailwind, Vite veya admin layouttan tamamen bağımsızdır. Harita buraya yükleniyorsa sorun Google Maps/API değil, ana ekranda başka bir şeydir.</p>

<div class="status-bar">
    <div id="status-dot" class="dot yellow"></div>
    <span id="status-text" style="font-size:0.8rem">Harita yükleniyor…</span>
</div>

<div id="map">
    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#475569;font-size:0.85rem">
        Google Maps API bekleniyor…
    </div>
</div>

<label>Çizilen Son GeoJSON</label>
<div id="geojson-out">Henüz çizim yok. Sağ panelden polygon/polyline/marker çizin.</div>

<label>Konsol Logu</label>
<div id="log">Başlatılıyor…
</div>

<script>
    const logEl = document.getElementById('log');
    const statusDot = document.getElementById('status-dot');
    const statusText = document.getElementById('status-text');
    const geojsonOut = document.getElementById('geojson-out');

    function log(msg, level) {
        const ts = new Date().toLocaleTimeString('tr-TR', { hour12: false });
        const color = level === 'error' ? '#EF4444' : level === 'warn' ? '#EAB308' : level === 'success' ? '#22C55E' : '#94A3B8';
        logEl.innerHTML += `<span style="color:${color}">[${ts}] ${msg}</span>\n`;
        logEl.scrollTop = logEl.scrollHeight;
    }

    function setStatus(msg, state) {
        statusText.textContent = msg;
        statusDot.className = 'dot ' + (state || '');
    }

    log('Page loaded. Waiting for Google Maps callback...', 'info');
    log('API Key present: ' + ('{{ $googleMapsApiKey }}' !== '' ? 'YES' : 'NO — set GOOGLE_MAPS_API_KEY in .env'), '{{ $googleMapsApiKey }}' ? 'success' : 'error');

    window.__aykomeMapTestInit = function () {
        log('__aykomeMapTestInit() callback fired!', 'success');
        setStatus('Google Maps API yüklendi, harita oluşturuluyor…', 'yellow');

        try {
            log('google.maps version: ' + (google.maps.version || 'unknown'), 'info');
            log('DrawingManager available: ' + (!!google.maps.drawing?.DrawingManager), 'info');

            const mapEl = document.getElementById('map');
            const map = new google.maps.Map(mapEl, {
                center: { lat: 39.93, lng: 32.85 },
                zoom: 12,
                mapTypeId: 'roadmap',
            });

            log('Map object created successfully.', 'success');
            setStatus('Harita oluşturuldu.', 'green');

            if (!google.maps.drawing?.DrawingManager) {
                log('WARNING: drawing library not available — libraries=drawing may be missing in script URL.', 'warn');
                setStatus('DrawingManager yok — libraries=drawing parametresi eksik!', 'red');
                return;
            }

            const dm = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.POLYGON,
                        google.maps.drawing.OverlayType.POLYLINE,
                        google.maps.drawing.OverlayType.MARKER,
                    ],
                },
                polygonOptions: { editable: true, draggable: true, strokeColor: '#02E0FB', fillColor: '#02E0FB', fillOpacity: 0.25 },
            });
            dm.setMap(map);

            dm.addListener('overlaycomplete', (event) => {
                log('Overlay drawn: ' + event.type, 'success');
                let feature = null;

                if (event.type === google.maps.drawing.OverlayType.POLYGON) {
                    const coords = [];
                    const path = event.overlay.getPath();
                    for (let i = 0; i < path.getLength(); i++) {
                        const pt = path.getAt(i);
                        coords.push([pt.lng(), pt.lat()]);
                    }
                    if (coords.length) coords.push(coords[0]);
                    feature = { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: { source: 'drawn' } };
                } else if (event.type === google.maps.drawing.OverlayType.MARKER) {
                    const pos = event.overlay.getPosition();
                    feature = { type: 'Feature', geometry: { type: 'Point', coordinates: [pos.lng(), pos.lat()] }, properties: { source: 'drawn' } };
                }

                if (feature) {
                    geojsonOut.textContent = JSON.stringify(feature, null, 2);
                    log('GeoJSON output updated.', 'success');
                }
            });

            log('DrawingManager attached successfully.', 'success');
            setStatus('Harita ve DrawingManager hazır! Çizim yapabilirsiniz.', 'green');

        } catch (err) {
            log('ERROR: ' + err.message, 'error');
            setStatus('Hata: ' + err.message, 'red');
            console.error('[AykomeMapTest] Error:', err);
        }
    };

    // Fallback timeout
    setTimeout(function () {
        if (typeof google === 'undefined') {
            log('TIMEOUT: google is still undefined after 10s. API script may not have loaded.', 'error');
            log('Possible causes: invalid API key, network error, billing disabled, or browser blocked the script.', 'warn');
            setStatus('Zaman aşımı — Google yüklenemedi!', 'red');
        }
    }, 10000);
</script>

<script
    async
    defer
    src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=drawing,places&callback=__aykomeMapTestInit&v=3.64"
    onerror="log('SCRIPT LOAD ERROR: Google Maps script failed to load. Check network, CORS, API key restrictions.', 'error'); setStatus('Script yükleme hatası!', 'red');"
></script>

</body>
</html>
