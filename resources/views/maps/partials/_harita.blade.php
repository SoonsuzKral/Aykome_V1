@props([
    'mode' => 'embedded',
    'canvasId' => 'maps-map-canvas-' . uniqid(),
    'drawingEnabled' => false,
    'hatKimligiEnabled' => false,
    'show15mRoads' => false,
    'height' => '400px',
    'readOnly' => false,
    'application' => null,
    'areas' => [],
])

{{-- Parametreleri PHP'den JS'ye aktarmak için --}}
@php
    $initData = [
        'mode' => $mode,
        'canvasId' => $canvasId,
        'drawingEnabled' => $drawingEnabled && !$readOnly,
        'hatKimligiEnabled' => $hatKimligiEnabled,
        'show15mRoads' => $show15mRoads,
        'readOnly' => $readOnly,
        'center' => $application ? [
            'lat' => (float)($application->center_lat ?? 37.1598),
            'lng' => (float)($application->center_lng ?? 38.7969),
        ] : null,
        'applicationId' => $application?->id,
        'areas' => collect($areas)
            ->filter()
            ->map(function ($raw) {
                return is_string($raw) ? json_decode($raw, true) : $raw;
            })
            ->filter()
            ->values()
            ->toArray(),
    ];
@endphp

<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.css') }}" />
<style>
    .cbs-search-spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(46,109,164,.3);border-top-color:#2e6da4;border-radius:50%;animation:cbsSpin .7s linear infinite;vertical-align:middle;margin-right:6px}
    @keyframes cbsSpin{to{transform:rotate(360deg)}}
    .cbs-search-results button:hover{background:#e8f0fe}
</style>
@if($drawingEnabled && !$readOnly)
<link rel="stylesheet" href="{{ asset('assets/vendor/leaflet/leaflet.draw.css') }}" />
@endif

<div id="{{ $canvasId }}-wrapper" style="position:relative;width:100%;height:{{ $height }};border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;">
    <div id="{{ $canvasId }}" style="width:100%;height:100%;"></div>

    @if(!$readOnly)
    <div style="position:absolute;top:8px;right:8px;z-index:1000;display:flex;gap:4px;">
        @if($drawingEnabled)
        <button data-draw="{{ $canvasId }}" data-tool="marker" class="cbs-draw-btn" style="background:white;border:none;border-radius:6px;padding:5px 8px;font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">📍</button>
        <button data-draw="{{ $canvasId }}" data-tool="polygon" class="cbs-draw-btn" style="background:white;border:none;border-radius:6px;padding:5px 8px;font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">⬡</button>
        <button data-draw="{{ $canvasId }}" data-tool="line" class="cbs-draw-btn" style="background:white;border:none;border-radius:6px;padding:5px 8px;font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">📏</button>
        <button data-draw="{{ $canvasId }}" data-tool="clear" class="cbs-draw-btn" style="background:white;border:none;border-radius:6px;padding:5px 8px;font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">🗑️</button>
        @endif
    </div>
    @endif

    {{-- Harita içi arama + koordinat → Leaflet L.Control ile JS'te oluşturuluyor (blade'de input YOK) --}}

    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(15,23,42,0.85);color:#94a3b8;padding:3px 10px;font-size:10px;font-family:monospace;z-index:500;display:flex;justify-content:space-between;">
        <span class="cbs-coords-{{ $canvasId }}">📍 —</span>
        <span>Aykome CBS</span>
    </div>
</div>

<script src="{{ asset('assets/vendor/leaflet/leaflet.js') }}"></script>
@if($drawingEnabled && !$readOnly)
<script src="{{ asset('assets/vendor/leaflet/leaflet.draw.js') }}"></script>
@endif

<script>
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.imagePath = '{{ asset('assets/vendor/leaflet/images') }}';
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon-2x.png') }}',
        iconUrl: '{{ asset('assets/vendor/leaflet/images/marker-icon.png') }}',
        shadowUrl: '{{ asset('assets/vendor/leaflet/images/marker-shadow.png') }}'
    });
</script>

<script>
(function(){
    var opts = @json($initData);
    var canvas = document.getElementById(opts.canvasId);
    if (!canvas) return;

    var GEO3_WMS='https://geo3.sanliurfa.bel.tr:8091/geoserver/wms';

    var map = L.map(canvas, {
        center: opts.center ? [opts.center.lat, opts.center.lng] : [37.1598, 38.7969],
        zoom: opts.center ? 17 : 14,
        minZoom: 12,
        maxZoom: 22,
        maxNativeZoom: 19,
        zoomControl: !opts.readOnly,
        attributionControl: false,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        dragging: true,
    });

    // Basemap — HTTPS Google Uydu (belediye ağında mixed-content riski önlendi)
    L.tileLayer('https://mt0.google.com/vt/lyrs=s&hl=tr&x={x}&y={y}&z={z}', {
        maxZoom: 22, maxNativeZoom: 19, attribution: '© Google'
    }).addTo(map);

    // WMS katmanları — yalnızca doğrulanmış layer isimleri (geo3:8091)
    // Kapı Numaraları (smpns:m_Numarataj) ayrı + en üstte açık gösterilir.
    L.tileLayer.wms(GEO3_WMS, {
        layers: 'cbs:MISMAP_MAHALLE_KOYLER,smpns:MISMAP_NUM_KADASTRO_PARSEL,smpns:MISMAP_NUM_BINA,cbs:MISMAP_CADDE_SOKAK,cbs:MISMAP_KADASTRO_ADA',
        format: 'image/png', transparent: true,
        version: '1.3.0', maxZoom: 22, opacity: 0.6
    }).addTo(map);

    // Kapı Numaraları — ayrı ve her zaman açık
    L.tileLayer.wms(GEO3_WMS, {
        layers: 'smpns:m_Numarataj',
        format: 'image/png', transparent: true,
        version: '1.3.0', maxZoom: 22, opacity: 1.0, zIndex: 50
    }).addTo(map);

    // Çizim katmanı
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // Gösterilecek çizim (başvuru excavationAreas) — normal haritayla eşit veri
    var hasOpData = false;
    if(opts.areas && opts.areas.length){
        opts.areas.forEach(function(poly){
            if(!poly || !poly.features || !poly.features.length) return;
            try{
                L.geoJSON(poly, {
                    style: { color:'#E87722', weight:2.5, fillOpacity:0.15 },
                    pointToLayer: function(f,ll){ return L.marker(ll); }
                }).addTo(drawnItems);
                hasOpData = true;
            }catch(e){}
        });
    }

    // Mevcut çizim varsa yükle
    if(opts.applicationId){
        fetch('/maps/drawing/app/' + opts.applicationId)
            .then(function(r){ return r.json(); })
            .then(function(data){
                if(data.features && data.features.length){
                    L.geoJSON(data, {
                        pointToLayer: function(f,ll){ return L.marker(ll); },
                        style: { color:'#E87722', weight:2, fillOpacity:0.1 }
                    }).addTo(drawnItems);
                }
                if(drawnItems.getLayers().length){
                    setTimeout(function(){ map.fitBounds(drawnItems.getBounds().pad(0.1), { maxZoom: 18 }); }, 250);
                }
            }).catch(function(){});
    }

    // Gösterilecek çizim verisi varsa hemen odağı eşitle (normal haritayla aynı veri)
    if(hasOpData && drawnItems.getLayers().length){
        setTimeout(function(){ map.fitBounds(drawnItems.getBounds().pad(0.12), { maxZoom: 18 }); }, 250);
        setTimeout(function(){ map.invalidateSize(); }, 300);
    }

    if(opts.drawingEnabled && !opts.readOnly){
        map.on('draw:created', function(e){
            drawnItems.addLayer(e.layer);
            var geojson = e.layer.toGeoJSON();
            // Canvas wrapper'ına event gönder — parent form dinler
            var wrapper = document.getElementById(opts.canvasId + '-wrapper');
            var evt = new CustomEvent('cbs-draw-created', {
                detail: { geojson: geojson, type: e.layerType }
            });
            wrapper.dispatchEvent(evt);
        });

        // Toolbar hook
        document.querySelectorAll('[data-draw="'+opts.canvasId+'"]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var tool = this.dataset.tool;
                if(tool === 'clear'){ drawnItems.clearLayers(); return; }
                var drawHandler = null;
                if(tool === 'marker') drawHandler = new L.Draw.Marker(map);
                else if(tool === 'polygon') drawHandler = new L.Draw.Polygon(map, {allowIntersection:!0});
                else if(tool === 'line') drawHandler = new L.Draw.Polyline(map);
                if(drawHandler) drawHandler.enable();
            });
        });
    }

    // Harita tıklama — koordinat + WFS parsel sorgusu
    map.on('click', function(e){
        var lat = e.latlng.lat, lng = e.latlng.lng;

        // Normal tıklama — WFS parsel sorgusu (GetFeatureInfo)
        var bbox = getBboxForPoint(lat, lng, 10);
        var wfsUrl = GEO3_WMS.replace('/wms','/wfs')+'?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames=smpns:MISMAP_NUM_KADASTRO_PARSEL&outputFormat=application/json&srsName=EPSG:4326'+
            '&bbox='+bbox;
        fetch('/maps/proxy?url='+encodeURIComponent(wfsUrl))
            .then(function(r){ return r.json(); })
            .then(function(data){
                var feat = data.features && data.features[0];
                var p = feat ? (feat.properties || {}) : {};
                var ilce = p.ILCE || p.ilce || '';
                var mahalle = p.MAHALLE_AD || p.mahalle || '';
                var ada = p.ADA || p.ada || '';
                var parsel = p.PARSEL || p.parsel || '';
                var html = '<div style="min-width:180px;font-size:12px;">'+
                    '<div style="font-weight:600;margin-bottom:4px;">📌 '+lat.toFixed(6)+', '+lng.toFixed(6)+'</div>'+
                    (ilce||mahalle ? '<div style="color:#475569;margin-bottom:4px;font-size:11px;">'+ilce+(ilce&&mahalle?' / ':'')+mahalle+'</div>' : '')+
                    (ada||parsel ? '<div style="margin-bottom:4px;"><span style="background:#f1f5f9;padding:2px 6px;border-radius:3px;font-size:11px;">Ada: '+(ada||'-')+' | Parsel: '+(parsel||'-')+'</span></div>' : '')+
                    '<div style="margin-top:4px;color:#64748b;font-size:10px;">Parsel sorgusu</div>'+
                    '</div>';
                L.popup({maxWidth:300}).setLatLng(e.latlng).setContent(html).openOn(map);
            })
            .catch(function(){
                // Nominatim fallback
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&accept-language=tr')
                    .then(function(r){return r.json()})
                    .then(function(d){
                        var addr = d.address||{};
                        var ilce = addr.county||'';
                        var mahalle = addr.suburb||addr.neighbourhood||'';
                        var html = '<div style="min-width:180px;font-size:12px;">'+
                            '<div style="font-weight:600;margin-bottom:4px;">📍 '+lat.toFixed(6)+', '+lng.toFixed(6)+'</div>'+
                            (ilce||mahalle ? '<div style="color:#475569;">'+ilce+(ilce&&mahalle?' / ':'')+mahalle+'</div>' : '<div style="color:#94a3b8;">Adres bilgisi alınamadı</div>')+
                            '</div>';
                        L.popup({maxWidth:300}).setLatLng(e.latlng).setContent(html).openOn(map);
                    }).catch(function(){});
            });
    });

    function getBboxForPoint(lat, lng, meters){
        var dLat = meters / 111320;
        var dLng = meters / (111320 * Math.cos(lat * Math.PI / 180));
        return (lng-dLng)+','+(lat-dLat)+','+(lng+dLng)+','+(lat+dLat);
    }

    // 15m yolları
    if(opts.show15mRoads){
        ['alti','ustu'].forEach(function(tip){
            var color = tip==='alti' ? '#22c55e' : '#ef4444';
            fetch('/maps/15m/'+tip)
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if(d.features) L.geoJSON(d, {style:{color:color,weight:4,opacity:0.5}}).addTo(map);
                }).catch(function(){});
        });
    }

    // Koordinat göstergesi
    map.on('mousemove', function(e){
        var el = document.querySelector('.cbs-coords-'+opts.canvasId);
        if(el) el.textContent = '📍 '+e.latlng.lat.toFixed(6)+'° | '+e.latlng.lng.toFixed(6)+'°';
    });

    // Embedded mode'da container resize takibi
    if(opts.mode === 'embedded' && window.ResizeObserver){
        var ro = new ResizeObserver(function(){ map.invalidateSize(); });
        ro.observe(canvas);
    }
    setTimeout(function(){ map.invalidateSize(); }, 300);
    // Sayfa görünür olunca da invalidate (tab switch vs)
    document.addEventListener('visibilitychange', function(){
        if(!document.hidden) setTimeout(function(){ map.invalidateSize(); }, 200);
    });

    window['cbsMap_' + opts.canvasId] = map;
    window.cbsMap = map;
    window.appCbsMap = map;
    window['cbsDrawnItems_' + opts.canvasId] = drawnItems;

    // Koordinat ile bul → CBS haritasında işaretle (istenen canvas için)
    window.aykomeCbsGoster = function (lat, lon, label) {
        var tgt = document.getElementById(opts.canvasId);
        var m = window['cbsMap_' + opts.canvasId] || map;
        var mk = window._cbsGlobalMarker;
        if (mk) m.removeLayer(mk);
        window._cbsGlobalMarker = L.marker([lat, lon], {
            icon: L.divIcon({ className: '', html: '<div style="background:#1e5fa8;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 4px rgba(30,95,168,.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
        }).addTo(m).bindPopup('<b>' + (label || '') + '</b><br>Koordinat ile bulundu').openPopup();
        m.flyTo([lat, lon], 17, { animate: true, duration: 1 });
    };

// ─── HARİTA-İÇİ ARAMA + KOORDİNABAK (Leaflet L.Control — harita yüzeyine gömülü) ────
    if (!opts.readOnly) {
        var MapInsSearchControl = L.Control.extend({
            options: { position: 'topleft' },
            onAdd: function () {
                var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                L.DomEvent.disableClickPropagation(div);
                L.DomEvent.disableScrollPropagation(div);
                div.style.cssText = 'display:flex;flex-direction:column;gap:4px;padding:6px;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.2);';

                div.innerHTML =
                    '<input type="text" class="cbs-native-search" placeholder="Cadde, sokak ara..." autocomplete="off" style="width:220px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;outline:none;">' +
                    '<div class="cbs-native-results" style="display:none;max-height:180px;overflow-y:auto;background:#fff;border:1px solid #2e6da4;border-radius:0 0 6px 6px;"></div>' +
                    '<input type="text" class="cbs-native-coord" placeholder="🧭 veya koordinat: 37.161298, 38.782200" autocomplete="off" style="width:220px;padding:6px 8px;border:1px dashed #94a3b8;border-radius:6px;font-size:11px;background:#f8fafc;color:#334155;outline:none;">';

                var sInput = div.querySelector('.cbs-native-search');
                var sResults = div.querySelector('.cbs-native-results');
                var cInput = div.querySelector('.cbs-native-coord');
                var sMarker = null;

                var goTo = function (lat, lon, lbl) {
                    if (sMarker) map.removeLayer(sMarker);
                    sMarker = L.marker([lat, lon], {
                        icon: L.divIcon({ className: '', html: '<div style="background:#1e5fa8;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 4px rgba(30,95,168,.3);"></div>', iconSize: [16, 16], iconAnchor: [8, 8] })
                    }).addTo(map).bindPopup('<b>' + (lbl || '') + '</b>').openPopup();
                    map.flyTo([lat, lon], 17, { animate: true, duration: 1 });
                    if (sInput) sInput.value = lbl;
                };

                // Adres / cadde arama
                var sTimer = null, sSeq = 0;
                sInput.addEventListener('input', function () {
                    clearTimeout(sTimer);
                    var q = this.value.trim();
                    if (q.length < 3) { sResults.style.display = 'none'; sResults.innerHTML = ''; return; }
                    sTimer = setTimeout(function () {
                        var seq = ++sSeq;
                        sResults.innerHTML = '<div style="padding:8px;color:#94a3b8;text-align:center"><span class="cbs-search-spinner"></span> Aranıyor...</div>';
                        sResults.style.display = 'block';
                        fetch('/maps/adres-ara?q=' + encodeURIComponent(q))
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                if (seq !== sSeq) return;
                                if (!d || !d.success || !d.lat) { sResults.innerHTML = '<div style="padding:8px;color:#e74c3c;text-align:center">Bulunamadı</div>'; return; }
                                var lat = parseFloat(d.lat), lon = parseFloat(d.lon);
                                var lbl = d.detail || d.cadde || q;
                                sResults.innerHTML = '<button type="button" style="width:100%;padding:8px 10px;text-align:left;border:none;background:#fff;cursor:pointer;font-size:12px;border-bottom:1px solid #f0f4f8;">📍 ' + lbl + ' <span style="color:#2e6da4">Konuma Git</span></button>';
                                sResults.querySelector('button').addEventListener('click', function () {
                                    sResults.style.display = 'none';
                                    goTo(lat, lon, lbl);
                                });
                            })
                            .catch(function () { sResults.style.display = 'none'; });
                    }, 500);
                });
                sInput.addEventListener('blur', function () { setTimeout(function () { sResults.style.display = 'none'; }, 250); });

                // Koordinat ile bul
                cInput.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    var m = cInput.value.trim().match(/^([-+]?\d+(?:[.,]\d+)?)\s*[,;\s]\s*([-+]?\d+(?:[.,]\d+)?)$/);
                    if (!m) { alert('⚠ Format hatalı — örn: 37.161298, 38.782200'); return; }
                    var lat = parseFloat(m[1].replace(',', '.'));
                    var lon = parseFloat(m[2].replace(',', '.'));
                    if (isNaN(lat) || isNaN(lon) || lat < 33 || lat > 43 || lon < 26 || lon > 45) {
                        alert('⚠️ Geçersiz koordinat — Şanlıurfa bölgesi için girin (örn: 37.161298, 38.782200)');
                        return;
                    }
                    goTo(lat, lon, lat.toFixed(6) + ', ' + lon.toFixed(6));
                });

                return div;
            }
        });
        map.addControl(new MapInsSearchControl());
    }
})();

function showCbsToast(msg){
    var t = document.getElementById('cbs-toast');
    if(!t){
        t = document.createElement('div');
        t.id = 'cbs-toast';
        t.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#059669;color:white;padding:8px 16px;border-radius:8px;font-size:13px;z-index:10000;opacity:0;transition:all 0.3s;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    setTimeout(function(){ t.style.opacity = '0'; t.style.transform = 'translateX(-50%) translateY(20px)'; }, 2500);
}
</script>
