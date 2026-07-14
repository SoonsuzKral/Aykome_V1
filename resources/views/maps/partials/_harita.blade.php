@props([
    'mode' => 'embedded',
    'canvasId' => 'maps-map-canvas-' . uniqid(),
    'drawingEnabled' => false,
    'hatKimligiEnabled' => false,
    'show15mRoads' => false,
    'height' => '400px',
    'readOnly' => false,
    'application' => null,
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
    ];
@endphp

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@if($drawingEnabled && !$readOnly)
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
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
        @if($hatKimligiEnabled)
        <button data-hk="{{ $canvasId }}" class="cbs-hk-btn" style="background:white;border:none;border-radius:6px;padding:5px 8px;font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.15);">🔍 HK</button>
        @endif
    </div>
    @endif

    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(15,23,42,0.85);color:#94a3b8;padding:3px 10px;font-size:10px;font-family:monospace;z-index:500;display:flex;justify-content:space-between;">
        <span class="cbs-coords-{{ $canvasId }}">📍 —</span>
        <span>Aykome CBS</span>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@if($drawingEnabled && !$readOnly)
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
@endif

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
        maxZoom: 20,
        zoomControl: !opts.readOnly,
        attributionControl: false,
    });

    // Basemap
    L.tileLayer('http://mt0.google.com/vt/lyrs=s&hl=tr&x={x}&y={y}&z={z}', {
        maxZoom: 21, attribution: '© Google'
    }).addTo(map);

    // WMS katmanları — varsayılan açık olanlar
    var defaultLayers = [
        'cbs:MISMAP_MAHALLE_KOYLER',
        'smpns:MISMAP_NUM_KADASTRO_PARSEL',
        'smpns:MISMAP_NUM_BINA',
        'aykome:AYK_SU_ICMESUYU_LINKS',
        'aykome:AYK_DOGALGAZ_LINKS',
    ];
    defaultLayers.forEach(function(l){
        L.tileLayer.wms(GEO3_WMS, {
            layers: l, format:'image/png', transparent:!0,
            version:'1.3.0', maxZoom:24, opacity: 0.7
        }).addTo(map);
    });

    // Çizim katmanı
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

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

    // Mevcut çizim varsa yükle
    if(opts.applicationId){
        fetch('/maps/drawing/app/' + opts.applicationId)
            .then(function(r){ return r.json(); })
            .then(function(data){
                if(data.features){
                    L.geoJSON(data, {
                        pointToLayer: function(f,ll){ return L.marker(ll); },
                        style: { color:'#E87722', weight:2, fillOpacity:0.1 }
                    }).addTo(drawnItems);
                }
            }).catch(function(){});
    }

    // Harita tıklama — GetFeatureInfo (parsel sorgusu) veya Hat Kimliği
    var hkBtn = document.querySelector('[data-hk="'+opts.canvasId+'"]');
    var hkActive = false;
    if(hkBtn){
        hkBtn.addEventListener('click', function(){
            hkActive = !hkActive;
            map.getContainer().style.cursor = hkActive ? 'crosshair' : '';
            hkBtn.style.background = hkActive ? '#E87722' : 'white';
            hkBtn.style.color = hkActive ? 'white' : '';
        });
    }
    map.on('click', function(e){
        var lat = e.latlng.lat, lng = e.latlng.lng;

        // Hat Kimliği aktifse önce yol sorgula
        if(hkActive && opts.hatKimligiEnabled){
            fetch('/maps/15m/sorgula?lat='+lat+'&lng='+lng)
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(!data.found){ showCbsToast('Bu noktada yol bulunamadı'); return; }
                    var p = data.properties;
                    var html = '<div style="min-width:220px;font-size:12px;">'+
                        '<div style="font-weight:700;font-size:14px;margin-bottom:4px;color:#1e293b;">🛣️ HAT KİMLİĞİ: #'+(p.CADDE_SOKA||'')+'</div>'+
                        '<hr style="margin:4px 0;border-color:#e2e8f0;">'+
                        '<table style="width:100%;font-size:12px;">'+
                        '<tr><td style="color:#64748b;padding:2px 4px;">Yol:</td><td style="padding:2px 4px;font-weight:500;">'+(p.CADDE_SO_1||'')+' '+(p.CADDE_SO_2||'')+'</td></tr>'+
                        '<tr><td style="color:#64748b;padding:2px 4px;">Mahalle:</td><td style="padding:2px 4px;">'+(p.MAHALLE_AD||'')+'</td></tr>'+
                        '<tr><td style="color:#64748b;padding:2px 4px;">Genişlik:</td><td style="padding:2px 4px;">'+(p.GENISLIGI||'')+' m</td></tr>'+
                        '<tr><td style="color:#64748b;padding:2px 4px;">Yetki:</td><td style="padding:2px 4px;">'+(p.SORUMLULUK||'')+'</td></tr>'+
                        '</table></div>';
                    L.popup({maxWidth:300}).setLatLng(e.latlng).setContent(html).openOn(map);
                }).catch(function(){});
            return;
        }

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

    setTimeout(function(){ map.invalidateSize(); }, 300);

    window['cbsMap_' + opts.canvasId] = map;
    window['cbsDrawnItems_' + opts.canvasId] = drawnItems;
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
