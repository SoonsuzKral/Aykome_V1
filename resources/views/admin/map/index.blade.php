@extends('layouts.admin')

@section('page-heading', 'Harita İzleme')

@section('content')
@php
$applications = collect($mapApplications ?? [])->values();
$filters = $filters ?? ['status' => '', 'institution_id' => '', 'drawing' => 'all'];
$withDrawingCount = $applications->filter(fn($r) => !empty($r['drawing']['polygon_geojson'] ?? null))->count();
$withCenterCount = $applications->filter(fn($r) => ($r['drawing']['center_lat'] ?? null) !== null && ($r['drawing']['center_lng'] ?? null) !== null)->count();
$legendItems = $applications->map(fn($r) => ['name' => $r['institution']['name'] ?? 'Tanımsız', 'color' => $r['institution']['draw_color'] ?? '#6B7280'])->unique(fn($i) => $i['name'].'-'.$i['color'])->values();
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Harita İzleme</h1>
        <p class="text-sm text-slate-500">Başvuru çizimlerini kurum rengi, durum ve çizim tipine göre izleyin.</p>
    </div>
    <a href="{{ route('admin.applications.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">→ Başvuru Listesi</a>
</div>

<div class="flex flex-col lg:flex-row gap-6 mb-6">

    {{-- SOL PANEL --}}
    <div class="w-full lg:w-[280px] shrink-0 space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Özet</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Toplam</dt><dd class="font-semibold">{{ $applications->count() }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Polygon</dt><dd class="font-semibold">{{ $withDrawingCount }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Marker</dt><dd class="font-semibold">{{ $withCenterCount }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Filtreler</h2>
            <form method="GET" action="{{ route('admin.map.index') }}" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Arama</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Başvuru no / adres" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Durum</label>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Tümü</option>
                        @foreach($statuses as $st)
                        <option value="{{ $st->value }}" @selected(($filters['status']??'')===$st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Kurum</label>
                    <select name="institution_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Tümü</option>
                        @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}" @selected((string)($filters['institution_id']??'')===(string)$inst->id)>{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Çizim</label>
                    <select name="drawing" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="all" @selected(($filters['drawing']??'all')==='all')>Tümü</option>
                        <option value="polygon" @selected(($filters['drawing']??'all')==='polygon')>Polygon</option>
                        <option value="marker" @selected(($filters['drawing']??'all')==='marker')>Sadece marker</option>
                        <option value="none" @selected(($filters['drawing']??'all')==='none')>Çizim yok</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 rounded-lg bg-emerald-700 px-3 py-2 text-sm text-white hover:bg-emerald-800">Uygula</button>
                    <a href="{{ route('admin.map.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Temizle</a>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Kurum Renkleri</h2>
            <ul class="space-y-2 text-sm">
                @forelse($legendItems as $li)
                <li class="flex items-center gap-2 text-slate-700"><span class="inline-block h-3 w-3 rounded-full" style="background:{{ $li['color'] }}"></span>{{ $li['name'] }}</li>
                @empty
                <li class="text-slate-400">Veri yok</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- SAĞ PANEL --}}
    <div class="min-w-0 flex-1 space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="relative">
                <div id="map-controls" class="absolute top-3 right-3 z-[1000] flex gap-2">
                    <button id="btn-konumum" class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 shadow-md border border-slate-200 hover:bg-slate-50">📍 Konumum</button>
                    <button id="btn-sifirla" class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 shadow-md border border-slate-200 hover:bg-slate-50">⟲ Sıfırla</button>
                    <button id="btn-uydu" class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 shadow-md border border-slate-200 hover:bg-slate-50">🛰 Uydu</button>
                    <button id="btn-street" class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 shadow-md border border-slate-200 hover:bg-slate-50">🗺 Street</button>
                </div>
                <div id="map-canvas" style="height:520px;width:100%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;">
                    Harita yükleniyor...
                </div>
                <div id="map-statusbar" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-1.5 text-xs text-slate-500 font-mono">
                    <span id="map-coords">📍 Bekleniyor...</span>
                    <span id="map-zoom">Zoom: -</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Başvurular</h2>
                <div class="flex items-center gap-2">
                    <input type="text" id="table-search" placeholder="Tabloda ara..." class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                    <select id="table-status-filter" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                        <option value="">Tüm Durumlar</option>
                        @foreach($statuses as $st)
                        <option value="{{ $st->label() }}">{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="max-height:380px;overflow-y:auto;">
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 cursor-pointer hover:text-slate-900" data-sort="no">No ↕</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 cursor-pointer hover:text-slate-900" data-sort="kurum">Kurum ↕</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 cursor-pointer hover:text-slate-900" data-sort="durum">Durum ↕</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">Çizim</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">Adres</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="basvuru-tbody">
                        @forelse($applications as $appRow)
                        <tr class="hover:bg-slate-50 basvuru-row" data-no="{{ $appRow['application_no'] }}" data-kurum="{{ $appRow['institution']['name'] ?? '—' }}" data-durum="{{ $appRow['status_label'] ?? \App\Enums\ApplicationStatus::tryFrom($appRow['status'] ?? '')?->label() ?? $appRow['status'] ?? '' }}">
                            <td class="px-4 py-3 font-medium whitespace-nowrap">{{ $appRow['application_no'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $appRow['institution']['name'] ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $appRow['status_badge_class'] ?? 'bg-slate-100 text-slate-700' }}">{{ $appRow['status_label'] ?? \App\Enums\ApplicationStatus::tryFrom($appRow['status'] ?? '')?->label() ?? $appRow['status'] ?? '' }}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if(!empty($appRow['drawing']['polygon_geojson']))<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Polygon</span>
                                @elseif(($appRow['drawing']['center_lat']??null)!==null && ($appRow['drawing']['center_lng']??null)!==null)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Marker</span>
                                @else<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Yok</span>@endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 max-w-[200px] truncate">{{ Str::limit($appRow['address_text'] ?? '—', 48) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ $appRow['detail_url'] }}" class="text-emerald-700 hover:underline text-xs">Detay</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Kayıt yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-2 text-xs text-slate-500 flex items-center justify-between">
                <span id="table-count">{{ $applications->count() }} kayıt</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#map-canvas { position:relative;z-index:1; }
#map-canvas .leaflet-container { border-radius:0; }
.aykome-marker { width:44px;height:44px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);border:2px solid rgba(255,255,255,0.5); }
.aykome-marker > * { transform:rotate(45deg);font-size:18px; }
.aykome-marker-pulse { animation:pulse 2s infinite; }
@keyframes pulse { 0%,100% { box-shadow:0 0 0 0 rgba(249,115,22,0.7); } 50% { box-shadow:0 0 0 8px rgba(249,115,22,0); } }
.streetview-popup .leaflet-popup-content-wrapper { border-radius:12px;overflow:hidden; }
.streetview-popup .leaflet-popup-content { margin:0; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function initMaps(){
    var el = document.getElementById('map-canvas');
    if(!el) return;
    var apps = @json($applications);
    var dc = @json($defaultCenter);
    var center = [Number(dc?.lat??39.93), Number(dc?.lng??32.85)];

    el.innerHTML = '<div id="leaflet-map" style="width:100%;height:520px;"></div>';

    var map = L.map('leaflet-map', {center:center, zoom:12, zoomControl:true, attributionControl:false});

    var osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19,attribution:'© OpenStreetMap'});
    var uyduLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:21,attribution:'© Esri'});
    var streetLayer = L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {maxZoom:19,attribution:'© OSM DE'});

    osmLayer.addTo(map);
    var baseLayers = {'OpenStreetMap': osmLayer, 'Uydu (Esri)': uyduLayer, 'Street (OSM DE)': streetLayer};
    L.control.layers(baseLayers, null, {position:'bottomleft'}).addTo(map);

    map.on('mousemove', function(e){
        document.getElementById('map-coords').textContent = '\uD83D\uDCCD '+e.latlng.lat.toFixed(6)+'\u00B0 K | '+e.latlng.lng.toFixed(6)+'\u00B0 D';
        document.getElementById('map-zoom').textContent = 'Zoom: '+map.getZoom();
    });
    map.on('zoomend', function(){
        document.getElementById('map-zoom').textContent = 'Zoom: '+map.getZoom();
    });
    map.on('moveend', function(){
        var c = map.getCenter();
        document.getElementById('map-coords').textContent = '\uD83D\uDCCD '+c.lat.toFixed(6)+'\u00B0 K | '+c.lng.toFixed(6)+'\u00B0 D';
    });

    map.doubleClickZoom.disable();
    map.on('dblclick', function(e){
            var lat=e.latlng.lat, lng=e.latlng.lng;
            var iframe='<div style="width:550px;height:400px;"><iframe src="https://maps.google.com/maps?q='+lat+','+lng+'&z=18&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen></iframe><div style="text-align:center;margin-top:6px;"><a href="https://www.google.com/maps?q='+lat+','+lng+'" target="_blank" style="color:#2563eb;font-size:13px;text-decoration:underline;">Google Haritalar\'da aç →</a></div></div>';
            L.popup({className:'streetview-popup', maxWidth:600, minWidth:500, closeButton:true})
                .setLatLng(e.latlng)
                .setContent(iframe)
                .openOn(map);
    });

    map.on('zoomend', function(){
        var c=map.getCenter();
        document.getElementById('map-coords').textContent='\uD83D\uDCCD '+c.lat.toFixed(6)+'\u00B0 K | '+c.lng.toFixed(6)+'\u00B0 D';
    });

    document.getElementById('btn-konumum').onclick = function(){
        if(!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(function(pos){
            var lat=pos.coords.latitude, lng=pos.coords.longitude;
            map.flyTo([lat,lng],17);
            L.marker([lat,lng], {icon:L.divIcon({className:'',html:'<div style="background:#3b82f6;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 0 0 3px rgba(59,130,246,0.4);"></div>',iconSize:[14,14],iconAnchor:[7,7]})}).addTo(map).bindPopup('<b>Konumum</b><br>'+lat.toFixed(6)+', '+lng.toFixed(6)).openPopup();
        });
    };
    document.getElementById('btn-sifirla').onclick = function(){ map.setView([39.93,32.85],6); };
    document.getElementById('btn-uydu').onclick = function(){ uyduLayer.addTo(map); osmLayer.remove(); streetLayer.remove(); };
    document.getElementById('btn-street').onclick = function(){ streetLayer.addTo(map); osmLayer.remove(); uyduLayer.remove(); };
    document.getElementById('btn-sifirla').after(Object.assign(document.createElement('button'),{id:'btn-osm',className:'rounded-lg bg-white px-3 py-2 text-sm text-slate-700 shadow-md border border-slate-200 hover:bg-slate-50',textContent:'🗺 OSM',onclick:function(){ osmLayer.addTo(map); uyduLayer.remove(); streetLayer.remove(); }}));

    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(pos){
            var lat=pos.coords.latitude, lng=pos.coords.longitude;
            map.setView([lat,lng],14);
            L.marker([lat,lng], {icon:L.divIcon({className:'',html:'<div style="background:#3b82f6;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 0 0 3px rgba(59,130,246,0.4);"></div>',iconSize:[14,14],iconAnchor:[7,7]})}).addTo(map).bindPopup('<b>Konumum</b><br>'+lat.toFixed(6)+', '+lng.toFixed(6));
        });
    }

    var bounds = L.latLngBounds();
    var icons = {field_work:{bg:'#f97316',ico:'🚧'},licensed:{bg:'#22c55e',ico:'✅'},awaiting_payment:{bg:'#3b82f6',ico:'💰'},receipt_pending:{bg:'#8b5cf6',ico:'🧾'},submitted:{bg:'#94a3b8',ico:'📋'},rejected:{bg:'#ef4444',ico:'❌'},completed:{bg:'#475569',ico:'🏁'},approved:{bg:'#22c55e',ico:'✅'}};

    apps.forEach(function(row){
        if(!row) return;
        var color=row.institution?.draw_color||'#6B7280';
        var raw=row.drawing?.polygon_geojson;
        var st=row.status||'submitted';
        var si=icons[st]||icons.submitted;
        var popup='<div style="min-width:180px;font-size:12px;line-height:1.5;"><div style="font-weight:600;">'+(row.application_no||'')+'</div><div style="color:#475569;">'+(row.institution?.name||'—')+'</div><div style="margin-top:4px;"><span style="display:inline-block;margin-right:4px;">'+si.ico+'</span> '+row.status_label+'</div><div style="color:#334155;font-size:11px;">Alan: '+Number(row.total_area_m2||row.drawing?.total_area_m2||0).toFixed(2)+' m²</div><a href="'+row.detail_url+'" style="display:inline-block;margin-top:6px;color:#E87722;font-weight:500;font-size:11px;">Detay →</a></div>';

        if(raw){
            try{
                var data = typeof raw==='string' ? JSON.parse(raw) : raw;
                var feats = data.type==='FeatureCollection' ? data.features : [data];
                feats.forEach(function(f){
                    if(f?.geometry?.type==='Polygon'){
                        var coords = f.geometry.coordinates[0].map(function(c){ return [c[1],c[0]]; });
                        var poly = L.polygon(coords,{color:color,weight:2,opacity:0.9,fillColor:color,fillOpacity:0.2}).addTo(map);
                        poly.bindPopup(popup);
                        poly.on('click', function(){ map.fitBounds(poly.getBounds(), {padding:[40,40]}); poly.openPopup(); });
                        coords.forEach(function(p){ bounds.extend(p); });
                    }
                });
            }catch(e){}
        }

        var lat=Number(row.drawing?.center_lat), lng=Number(row.drawing?.center_lng);
        if(Number.isFinite(lat) && Number.isFinite(lng)){
            var marker = L.marker([lat,lng], {icon:L.divIcon({className:'',html:'<div class="aykome-marker'+(si.ico==='🚧'?' aykome-marker-pulse':'')+'" style="background:'+si.bg+'"><span>'+si.ico+'</span></div>',iconSize:[44,44],iconAnchor:[22,22],popupAnchor:[0,-26]})}).addTo(map);
            marker.bindPopup(popup);
            marker.on('click', function(){
                map.setView([lat,lng], map.getZoom()<16 ? 18 : map.getZoom());
                marker.openPopup();
            });
            bounds.extend([lat,lng]);
        }
    });

    if(bounds.isValid()){ map.fitBounds(bounds, {padding:[40,40]}); setTimeout(function(){ if(map.getZoom()>16) map.setZoom(16); },200); }

    document.getElementById('map-drawing-status') && (document.getElementById('map-drawing-status').textContent = '✅ ' + apps.length + ' başvuru');

    var tb = document.getElementById('basvuru-tbody');
    var ts = document.getElementById('table-search');
    var tf = document.getElementById('table-status-filter');

    function filterTable(){
        var q = (ts?.value||'').toLowerCase();
        var st = tf?.value||'';
        var rows = tb?.querySelectorAll('.basvuru-row')||[];
        var cnt=0;
        rows.forEach(function(r){
            var match = (!q || r.dataset.no.toLowerCase().includes(q) || r.dataset.kurum.toLowerCase().includes(q) || (r.cells[4]?.textContent||'').toLowerCase().includes(q)) && (!st || r.dataset.durum.toLowerCase().includes(st.toLowerCase()));
            r.style.display = match ? '' : 'none';
            if(match) cnt++;
        });
        document.getElementById('table-count').textContent = cnt+' kayıt';
    }
    ts?.addEventListener('input', filterTable);
    tf?.addEventListener('change', filterTable);

    var sortState = {col:null,dir:'asc'};
    document.querySelectorAll('[data-sort]').forEach(function(th){
        th.onclick = function(){
            var col = th.dataset.sort;
            var dir = (sortState.col===col && sortState.dir==='asc') ? 'desc' : 'asc';
            sortState = {col,dir};
            document.querySelectorAll('[data-sort]').forEach(function(h){ h.textContent = h.textContent.replace(/ [↑↓]/g,''); });
            th.textContent += dir==='asc' ? ' ↑' : ' ↓';
            var rows = Array.from(tb?.querySelectorAll('.basvuru-row')||[]);
            rows.sort(function(a,b){
                var va=col==='no'?a.dataset.no:col==='kurum'?a.dataset.kurum:a.dataset.durum;
                var vb=col==='no'?b.dataset.no:col==='kurum'?b.dataset.kurum:b.dataset.durum;
                return dir==='asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            });
            rows.forEach(function(r){ tb.appendChild(r); });
            filterTable();
        };
    });
    filterTable();
}
document.addEventListener('DOMContentLoaded', initMaps);
</script>
@endpush