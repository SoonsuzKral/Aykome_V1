@extends('layouts.admin')

@section('page-heading', 'Canlı Saha İzleme PRO')

@push('styles')
<style>
/* ── Harita sayfası layout ──
   SADECE content column (div.flex-col) kısıtlanıyor.
   Sidebar onun kardeşi, etkilenmiyor → sidebar bağımsız scroll yapabilir.
── */
body:has(#live-map-wrap) > div.flex > div.flex-col {
    height:100vh!important;
    overflow:hidden!important;
    display:flex!important;
    flex-direction:column!important;
}
body:has(#live-map-wrap) main {
    padding:0!important;
    overflow:hidden!important;
    flex:1 1 0%!important;
    min-height:0!important;
}
body:has(#live-map-wrap) footer { flex-shrink:0!important; }
#live-map-wrap { height:100%!important; overflow:hidden!important; }

/* ── Personel kartı ── */
.personnel-card { cursor:pointer; transition:background .15s; border-left:3px solid transparent; }
.personnel-card:hover  { background:#f0fdfe; }
.personnel-card.active { background:#e0fafa!important; border-left-color:#02E0FB!important; }

/* ── Pill sekme ── */
.map-tab {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 13px; border-radius:9999px;
    font-size:11px; font-weight:700; cursor:pointer;
    transition:background .15s,color .15s,box-shadow .15s;
    border:none; background:transparent; color:#6b7280; white-space:nowrap;
}
.map-tab.active             { background:#02E0FB; color:#fff; box-shadow:0 2px 8px rgba(2,224,251,.35); }
.map-tab:not(.active):hover { background:#f0fdfe; color:#02AFC6; }
.map-tab .tab-badge {
    border-radius:9999px; padding:0 5px; font-size:10px; font-weight:700;
    background:rgba(255,255,255,.3); min-width:16px; text-align:center;
}
.map-tab:not(.active) .tab-badge { background:#e5e7eb; color:#6b7280; }

/* ── Google Maps InfoWindow ── */
.gm-style .gm-style-iw-c  { border-radius:14px!important; padding:0!important; box-shadow:0 8px 32px rgba(0,0,0,.14)!important; min-width:270px!important; }
.gm-style .gm-style-iw-d  { overflow:hidden!important; }
.gm-style .gm-style-iw-tc::after { background:#fff!important; }
.gm-style-iw-ch { display:none!important; }

/* ── Marker ping ring ── */
@keyframes ping-ring {
    0%   { transform:scale(1);   opacity:.8; }
    100% { transform:scale(2.4); opacity:0;  }
}
@keyframes dot-pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.2); }
}

/* ── Lightbox ── */
#lbx-overlay {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.85); align-items:center; justify-content:center;
}
#lbx-overlay.open { display:flex; }
#lbx-img { max-width:90vw; max-height:88vh; border-radius:12px; object-fit:contain; }

/* ── GLightbox thumbnail hover ── */
.glightbox-iw img { transition:transform .15s,box-shadow .15s; }
.glightbox-iw:hover img { transform:scale(1.06); box-shadow:0 4px 12px rgba(0,0,0,.22)!important; }

/* ── Harita canvas — her zaman görünür ve tam yükseklik ── */
#live-map-canvas {
    height: calc(100vh - 100px) !important;
    min-height: 600px;
    width: 100%;
    display: block !important;
}

/* ── Scrollbar tamamen gizle (tüm tarayıcılar) ── */
#live-panel *::-webkit-scrollbar,
#panel-live::-webkit-scrollbar,
#panel-recent::-webkit-scrollbar,
#list-live::-webkit-scrollbar,
#list-recent::-webkit-scrollbar  { display:none!important; width:0!important; }
#panel-live, #panel-recent       { scrollbar-width:none!important; -ms-overflow-style:none!important; }
.scrollbar-hide                  { scrollbar-width:none!important; -ms-overflow-style:none!important; }
.scrollbar-hide::-webkit-scrollbar { display:none!important; width:0!important; }

/* ── Premium kart hover ── */
.personnel-card:hover { background:#f8faff!important; }
.personnel-card.active { background:#e8f8ff!important; border-left-color:#02E0FB!important; }

/* ── MOBİL: sol panel alta, harita üste ── */
@media (max-width: 1023px) {
    #live-map-wrap  { flex-direction:column!important; }
    #live-panel     { width:100%!important; min-width:unset!important; max-width:unset!important;
                      height:38vh!important; border-right:none!important;
                      border-top:1px solid #e5e7eb!important; order:2; }
    #live-map-side  { height:62vh!important; order:1; }
}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════
     CANLI SAHA İZLEME PRO — Sol 350px liste | Sağ harita
════════════════════════════════════════════════════════ --}}
<div id="live-map-wrap"
     style="display:flex;flex-direction:row;width:100%;height:100%;overflow:hidden;background:#f8fafc;position:relative;">

    {{-- ══ SOL PANEL ══ --}}
    <div id="live-panel" style="width:350px;min-width:350px;max-width:350px;height:100%;display:flex;flex-direction:column;background:#fff;border-right:1px solid #e5e7eb;box-shadow:2px 0 10px rgba(0,0,0,.06);z-index:10;overflow:hidden;">

        {{-- Başlık --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #f3f4f6;padding:12px 16px;flex-shrink:0;">
            <div style="min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="position:relative;display:inline-flex;width:10px;height:10px;flex-shrink:0;">
                        <span style="position:absolute;inset:0;border-radius:50%;background:#02E0FB;opacity:.6;animation:ping 1.5s ease-out infinite;"></span>
                        <span style="position:relative;display:inline-flex;width:10px;height:10px;border-radius:50%;background:#02E0FB;"></span>
                    </span>
                    <p style="font-size:12px;font-weight:800;color:#1f2937;margin:0;white-space:nowrap;">Canlı Saha İzleme</p>
                    <span style="border-radius:9999px;background:linear-gradient(to right,rgba(250,96,1,.2),rgba(2,224,251,.15));padding:2px 7px;font-size:9px;font-weight:700;text-transform:uppercase;color:#f97316;">PRO</span>
                </div>
                <p style="margin:3px 0 0;font-size:10px;color:#9ca3af;">
                    <span id="live-count" style="font-weight:700;color:#02AFC6;">0</span> aktif ·
                    Son: <span id="last-update">—</span>
                </p>
            </div>
            <button id="refresh-btn"
                    style="flex-shrink:0;border-radius:8px;border:1px solid #e5e7eb;background:#fff;padding:6px 10px;font-size:10px;font-weight:700;color:#6b7280;cursor:pointer;white-space:nowrap;">
                <svg xmlns="http://www.w3.org/2000/svg" style="display:inline;height:11px;width:11px;vertical-align:-1px;margin-right:2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Yenile
            </button>
        </div>

        {{-- Sekmeler --}}
        <div style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-bottom:1px solid #f3f4f6;background:#f9fafb;flex-shrink:0;">
            <button id="tab-live"   class="map-tab active" onclick="switchTab('live')">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#34d399;animation:dot-pulse 2s ease-in-out infinite;"></span>
                Canlı Aktifler
                <span id="cnt-live" class="tab-badge">0</span>
            </button>
            <button id="tab-recent" class="map-tab" onclick="switchTab('recent')">
                🕒 Son Görülenler
                <span id="cnt-recent" class="tab-badge">0</span>
            </button>
        </div>

        {{-- Panel: Canlı --}}
        <div id="panel-live" class="scrollbar-hide" style="flex:1;overflow-y:auto;overflow-x:hidden;">
            <div id="no-live" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:40px 16px;text-align:center;">
                <div style="margin-bottom:12px;display:inline-flex;height:48px;width:48px;align-items:center;justify-content:center;border-radius:16px;background:#f3f4f6;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="height:24px;width:24px;color:#d1d5db;" fill="none" viewBox="0 0 24 24" stroke="#d1d5db">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p style="font-size:14px;font-weight:700;color:#6b7280;margin:0;">Sahada kimse yok</p>
                <p style="margin:4px 0 0;font-size:12px;color:#9ca3af;">Check-in yapıldığında görünür.</p>
            </div>
            <div id="list-live" style="display:none;"></div>
        </div>

        {{-- Panel: Son Görülenler --}}
        <div id="panel-recent" class="scrollbar-hide" style="flex:1;overflow-y:auto;overflow-x:hidden;display:none;">
            <div id="no-recent" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:40px 16px;text-align:center;">
                <div style="margin-bottom:12px;display:inline-flex;height:48px;width:48px;align-items:center;justify-content:center;border-radius:16px;background:#f3f4f6;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="height:24px;width:24px;" fill="none" viewBox="0 0 24 24" stroke="#d1d5db">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p style="font-size:14px;font-weight:700;color:#6b7280;margin:0;">Bugün çıkış yapan yok</p>
                <p style="margin:4px 0 0;font-size:12px;color:#9ca3af;">Check-out yapanlar burada görünür.</p>
            </div>
            <div id="list-recent" style="display:none;"></div>
        </div>

    </div>

    {{-- ══ SAĞ HARİTA ══ --}}
    <div id="live-map-side" style="flex:1;position:relative;height:100%;overflow:hidden;min-width:0;">

        {{-- Floating badge --}}
        <div style="pointer-events:none;position:absolute;left:12px;top:12px;z-index:20;display:flex;align-items:center;">
            <div style="pointer-events:auto;display:flex;align-items:center;gap:8px;border-radius:16px;border:1px solid rgba(2,224,251,.3);background:rgba(255,255,255,.95);padding:8px 14px;box-shadow:0 4px 16px rgba(0,0,0,.1);">
                <span style="position:relative;display:inline-flex;width:10px;height:10px;flex-shrink:0;">
                    <span style="position:absolute;inset:0;border-radius:50%;background:#02E0FB;opacity:.6;animation:ping 1.5s ease-out infinite;"></span>
                    <span style="position:relative;display:inline-flex;width:10px;height:10px;border-radius:50%;background:#02E0FB;"></span>
                </span>
                <span style="font-size:12px;font-weight:700;color:#111827;">Canlı Takip</span>
                <span style="font-size:12px;color:#9ca3af;">·</span>
                <span style="font-size:12px;font-weight:700;color:#02AFC6;"><span id="live-count-map">0</span> sahada</span>
            </div>
        </div>

        {{-- Adres arama + Görünüm filtreleri (sağ üst) --}}
    <input id="live-map-search" type="text"
           style="position:absolute;right:48px;top:12px;z-index:20;width:260px;border-radius:10px;border:1px solid #e5e7eb;background:rgba(255,255,255,.97);padding:10px 14px;font-size:13px;color:#111827;box-shadow:0 4px 20px rgba(250,96,1,.22);outline:none;transition:border-color .15s,box-shadow .15s;"
           placeholder="Sokak, bina veya bölge ara..."
           onfocus="this.style.borderColor='#02E0FB';this.style.boxShadow='0 0 0 3px rgba(2,224,251,.2),0 4px 16px rgba(0,0,0,.12)'"
           onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='0 4px 20px rgba(250,96,1,.22)'">
    <div id="live-style-panel" style="position:absolute;right:48px;top:56px;z-index:20;display:flex;flex-direction:column;gap:4px;width:160px;border-radius:12px;border:1px solid #e5e7eb;background:rgba(255,255,255,.97);padding:8px;box-shadow:0 4px 20px rgba(250,96,1,.15);">
        <p style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;padding:0 4px 4px;margin:0;">Görünüm</p>
        <button id="live-style-standard" style="width:100%;border-radius:8px;border:1px solid rgba(250,96,1,.3);background:rgba(250,96,1,.1);padding:6px 10px;font-size:11px;font-weight:700;color:#FA6001;text-align:left;cursor:pointer;transition:all .15s;">⊙ Standart</button>
        <button id="live-style-roads"    style="width:100%;border-radius:8px;border:1px solid #e5e7eb;background:#fff;padding:6px 10px;font-size:11px;font-weight:600;color:#374151;text-align:left;cursor:pointer;transition:all .15s;">🛣 Sadece Yollar</button>
        <button id="live-style-green"    style="width:100%;border-radius:8px;border:1px solid #e5e7eb;background:#fff;padding:6px 10px;font-size:11px;font-weight:600;color:#374151;text-align:left;cursor:pointer;transition:all .15s;">🌿 Yeşil Alan</button>
    </div>

    {{-- Harita canvas --}}
        <div id="live-map-canvas" style="position:absolute;inset:0;width:100%;height:100%;"></div>

    </div>

</div>

{{-- Lightbox --}}
<div id="lbx-overlay" onclick="closeLightbox()">
    <button onclick="event.stopPropagation();closeLightbox()"
            style="position:absolute;top:1rem;right:1.25rem;color:#fff;font-size:1.5rem;font-weight:700;opacity:.6;border:none;background:transparent;cursor:pointer;">✕</button>
    <img id="lbx-img" src="" alt="" onclick="event.stopPropagation()">
</div>

@endsection

@push('scripts')
<script>
/* ═══════════════════════════════════════════════════════════
   HGB Bilişim  AYKOME — Canlı Saha İzleme PRO v4
   Google Maps AdvancedMarkerElement + İki Sekmeli Panel
═══════════════════════════════════════════════════════════ */

const LIVE_DATA_URL    = '{{ route('admin.live-map-pro.data') }}';
const MAPS_API_KEY     = '{{ $googleMapsApiKey ?? '' }}';
const REFRESH_INTERVAL = 30000;

let map           = null;
let liveMarkers   = {};   /* userId → { marker, infoWindow, lat, lng } */
let recentMarkers = {};
let activeTab     = 'live';

/* Renk paleti (8 renk) */
const AV_COLORS = [
    { bg:'#dbeafe', border:'#3b82f6', text:'#1d4ed8' },
    { bg:'#fef3c7', border:'#f59e0b', text:'#b45309' },
    { bg:'#dcfce7', border:'#22c55e', text:'#15803d' },
    { bg:'#fce7f3', border:'#ec4899', text:'#be185d' },
    { bg:'#ede9fe', border:'#8b5cf6', text:'#6d28d9' },
    { bg:'#fee2e2', border:'#ef4444', text:'#b91c1c' },
    { bg:'#e0f2fe', border:'#0284c7', text:'#0369a1' },
    { bg:'#f0fdf4', border:'#16a34a', text:'#166534' },
];

/* ─── SEKME GEÇİŞİ ─── */
function switchTab(tab) {
    activeTab = tab;

    document.getElementById('tab-live').classList.toggle('active',    tab === 'live');
    document.getElementById('tab-recent').classList.toggle('active',  tab === 'recent');

    const panelLive   = document.getElementById('panel-live');
    const panelRecent = document.getElementById('panel-recent');
    if (panelLive)   panelLive.style.display   = tab === 'live'   ? 'flex' : 'none';
    if (panelRecent) panelRecent.style.display  = tab === 'recent' ? 'flex' : 'none';
    if (panelLive)   panelLive.style.flexDirection   = 'column';
    if (panelRecent) panelRecent.style.flexDirection  = 'column';

    Object.values(liveMarkers).forEach(m => {
        try { if(tab==='live') m.marker.addTo(map); else map.removeLayer(m.marker); } catch(_) {}
    });
    Object.values(recentMarkers).forEach(m => {
        try { if(tab==='recent') m.marker.addTo(map); else map.removeLayer(m.marker); } catch(_) {}
    });
}

/* ─── MAP INIT (Leaflet — harita izleme ile aynı katman) ─── */
function initLiveLeafletMap() {
    var el = document.getElementById('live-map-canvas');
    el.innerHTML = '<div id="leaflet-live" style="width:100%;height:100%;"></div>';

    map = L.map('leaflet-live', {center:[37.924,40.219], zoom:13, zoomControl:true, attributionControl:false});

    var osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19,attribution:'© OpenStreetMap'});
    var uyduLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:21,attribution:'© Esri'});
    var streetLayer = L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', {maxZoom:19,attribution:'© OSM DE'});
    osmLayer.addTo(map);
    L.control.layers({'OpenStreetMap':osmLayer,'Uydu (Esri)':uyduLayer,'Street (OSM DE)':streetLayer}, null, {position:'bottomleft'}).addTo(map);

    loadLiveData();
    setInterval(loadLiveData, REFRESH_INTERVAL);

    var liveStyleBtns = {'live-style-standard':'roadmap','live-style-roads':'roads_only','live-style-green':'green_focus'};
    Object.entries(liveStyleBtns).forEach(function(e){ var btn=document.getElementById(e[0]); if(btn) btn.onclick=function(){ Object.keys(liveStyleBtns).forEach(function(bid){ var b=document.getElementById(bid); if(!b)return; var a=bid===e[0]; b.style.background=a?'rgba(250,96,1,.1)':'#fff'; b.style.borderColor=a?'rgba(250,96,1,.3)':'#e5e7eb'; b.style.color=a?'#FA6001':'#374151'; b.style.fontWeight=a?'700':'600'; }); }; });
}

/* ─── POLLİNG ─── */
async function loadLiveData() {
    try {
        const res = await fetch(LIVE_DATA_URL, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();

        /* Sayaçlar */
        ['live-count', 'live-count-map'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = data.total ?? (data.users?.length ?? 0);
        });
        const lu = document.getElementById('last-update');
        if (lu) lu.textContent = data.updated_at ?? '—';

        const users       = data.users        || [];
        const recentUsers = data.recent_users || [];

        const cntLive   = document.getElementById('cnt-live');
        const cntRecent = document.getElementById('cnt-recent');
        if (cntLive)   cntLive.textContent   = users.length;
        if (cntRecent) cntRecent.textContent  = recentUsers.length;

        renderLivePanel(users);
        renderRecentPanel(recentUsers);
    } catch (err) {
        console.error('[LiveMap] Polling hatası:', err);
    }
}

/* ─── CANLI PANEL ─── */
function renderLivePanel(users) {
    const listEl  = document.getElementById('list-live');
    const emptyEl = document.getElementById('no-live');
    if (!listEl || !emptyEl) return;

    if (!users.length) {
        emptyEl.style.display = 'flex';
        listEl.style.display  = 'none';
        listEl.innerHTML      = '';
    } else {
        emptyEl.style.display = 'none';
        listEl.style.display  = 'block';
    }

    const liveSet = new Set(users.map(u => u.id));
    Object.keys(liveMarkers).map(Number).forEach(uid => {
        if (!liveSet.has(uid)) {
            try { map.removeLayer(liveMarkers[uid].marker); } catch(_) {}
            delete liveMarkers[uid];
        }
    });

    listEl.innerHTML = '';

    users.forEach(user => {
        const color = AV_COLORS[user.color_index] || AV_COLORS[0];
        const lat = parseFloat(user.lat), lng = parseFloat(user.lng);

        if (!liveMarkers[user.id]) {
            try {
                var marker = L.marker([lat,lng], {
                    icon: L.divIcon({
                        className:'',
                        html: buildMarkerDiv(user.initials, color, true),
                        iconSize:[42,42], iconAnchor:[21,21]
                    }),
                    zIndexOffset:100
                });
                if(activeTab==='live') marker.addTo(map);
                var popupContent = buildMarkerIWContent(user, color, true);
                var popup = L.popup({className:'live-popup',closeButton:true}).setContent(popupContent);
                marker.bindPopup(popup);

                marker.on('click', function(){
                    closeAllIW();
                    map.panTo([lat,lng]);
                    marker.openPopup();
                    highlightCard('pcard-'+user.id);
                    setTimeout(reloadGallery, 150);
                });

                liveMarkers[user.id] = { marker:marker, infoWindow:popup, lat:lat, lng:lng, _popupContent:popupContent, _user:user, _color:color };
            } catch(e) { console.warn('[LiveMap] marker:', e); }
        } else {
            var m = liveMarkers[user.id];
            if(activeTab==='live'){ try{ m.marker.addTo(map); }catch(e){} } else { try{ map.removeLayer(m.marker); }catch(e){} }
            m.marker.setLatLng([lat,lng]);
            m.lat=lat; m.lng=lng;
            m._user=user; m._color=color;
            var newContent = buildMarkerIWContent(user, color);
            m.infoWindow.setContent(newContent);
            m._popupContent = newContent;
        }

        const mins = user.minutes_on_field;
        const dur  = mins == null ? '—' : (mins < 60 ? mins + 'dk' : Math.floor(mins / 60) + 'sa ' + (mins % 60) + 'dk');

        listEl.insertAdjacentHTML('beforeend', `
        <div id="pcard-${user.id}" class="personnel-card"
             style="padding:10px 14px;border-bottom:1px solid #f3f4f6;border-radius:0;"
             onclick="focusOnUser(${user.id}, ${lat}, ${lng}, 'live')">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="display:inline-flex;height:42px;width:42px;flex-shrink:0;align-items:center;justify-content:center;border-radius:12px;font-size:13px;font-weight:900;background:${color.bg};color:${color.text};border:2px solid ${color.border};box-shadow:0 2px 8px ${color.border}33;">
                    ${escHtml(user.initials)}
                </span>
                <div style="min-width:0;flex:1;">
                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(user.name)}</p>
                    <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                        <span style="font-size:10px;color:#6b7280;">Giriş: ${user.field_started_at ?? '—'}</span>
                        <span style="width:2px;height:2px;border-radius:50%;background:#d1d5db;display:inline-block;"></span>
                        <span style="font-size:10px;font-weight:600;color:#059669;">${dur}</span>
                    </div>
                </div>
                <span style="flex-shrink:0;width:9px;height:9px;border-radius:50%;background:#34d399;box-shadow:0 0 0 3px #d1fae5;animation:dot-pulse 2s ease-in-out infinite;"></span>
            </div>
            ${user.active_app_no ? `
            <div style="margin-top:7px;margin-left:52px;padding:5px 9px;background:#f0fdfe;border:1px solid #a5f3fc;border-radius:8px;display:flex;align-items:center;gap:5px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="height:11px;width:11px;flex-shrink:0;" viewBox="0 0 20 20" fill="#0891b2">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
                <span style="font-size:11px;font-weight:700;color:#0e7490;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(user.active_app_no)}</span>
            </div>` : ''}
        </div>`);
    });
}

/* ─── SON GÖRÜLENLER PANELİ ─── */
function renderRecentPanel(users) {
    const listEl  = document.getElementById('list-recent');
    const emptyEl = document.getElementById('no-recent');
    if (!listEl || !emptyEl) return;

    if (!users.length) {
        emptyEl.style.display = 'flex';
        listEl.style.display  = 'none';
        listEl.innerHTML      = '';
        Object.values(recentMarkers).forEach(m => {
            try { map.removeLayer(m.marker); } catch(_) {}
        });
        recentMarkers = {};
        return;
    }

    emptyEl.style.display = 'none';
    listEl.style.display  = 'block';

    const recentSet = new Set(users.map(u => u.id));
    Object.keys(recentMarkers).map(Number).forEach(uid => {
        if (!recentSet.has(uid)) {
            try { map.removeLayer(recentMarkers[uid].marker); } catch(_) {}
            delete recentMarkers[uid];
        }
    });

    listEl.innerHTML = '';

    users.forEach(user => {
        const color = AV_COLORS[user.color_index] || AV_COLORS[0];
        const lat = parseFloat(user.lat), lng = parseFloat(user.lng);

        if (!recentMarkers[user.id]) {
            try {
                var marker = L.marker([lat,lng], {
                    icon: L.divIcon({
                        className:'',
                        html: buildMarkerDiv(user.initials, color, false),
                        iconSize:[42,42], iconAnchor:[21,21]
                    }),
                    zIndexOffset:50
                });
                if(activeTab==='recent') marker.addTo(map);
                var popupContent = buildMarkerIWContent(user, color, false);
                var popup = L.popup({className:'live-popup',closeButton:true}).setContent(popupContent);
                marker.bindPopup(popup);
                marker.on('click', function(){
                    closeAllIW();
                    map.panTo([lat,lng]);
                    marker.openPopup();
                    highlightCard('rcard-'+user.id);
                });
                recentMarkers[user.id] = { marker:marker, infoWindow:popup, lat:lat, lng:lng, _popupContent:popupContent };
            } catch(e) { console.warn('[LiveMap] recent marker:', e); }
        } else {
            var m = recentMarkers[user.id];
            if(activeTab==='recent'){ try{ m.marker.addTo(map); }catch(e){} } else { try{ map.removeLayer(m.marker); }catch(e){} }
            m.marker.setLatLng([lat,lng]);
            m.lat=lat; m.lng=lng;
        }

        listEl.insertAdjacentHTML('beforeend', `
        <div id="rcard-${user.id}" class="personnel-card"
             style="padding:10px 14px;border-bottom:1px solid #f3f4f6;border-radius:0;"
             onclick="focusOnUser(${user.id}, ${lat}, ${lng}, 'recent')">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="display:inline-flex;height:42px;width:42px;flex-shrink:0;align-items:center;justify-content:center;border-radius:12px;font-size:13px;font-weight:900;background:#f9fafb;color:#9ca3af;border:2px solid #e5e7eb;">
                    ${escHtml(user.initials)}
                </span>
                <div style="min-width:0;flex:1;">
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(user.name)}</p>
                    <div style="display:flex;align-items:center;gap:5px;margin-top:2px;">
                        <span style="font-size:10px;color:#9ca3af;">${user.last_seen_at ?? '—'}</span>
                        <span style="width:2px;height:2px;border-radius:50%;background:#d1d5db;display:inline-block;"></span>
                        <span style="font-size:10px;color:#b45309;">${user.last_seen_diff ?? ''}</span>
                    </div>
                </div>
                <span style="flex-shrink:0;width:9px;height:9px;border-radius:50%;background:#d1d5db;box-shadow:0 0 0 3px #f3f4f6;"></span>
            </div>
            ${user.last_activity ? `
            <div style="margin-top:6px;margin-left:52px;display:flex;align-items:center;gap:4px;">
                <span style="font-size:10px;color:#d97706;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">⏱ ${escHtml(user.last_activity)}</span>
            </div>` : ''}
            ${user.last_app_no ? `
            <div style="margin-top:4px;margin-left:52px;padding:4px 9px;background:#f0fdfe;border:1px solid #a5f3fc;border-radius:7px;display:flex;align-items:center;gap:5px;"
                 onclick="event.stopPropagation();${user.last_app_id ? `window.location.href='/admin/applications/${user.last_app_id}'` : ''}">
                <svg xmlns="http://www.w3.org/2000/svg" style="height:11px;width:11px;flex-shrink:0;" viewBox="0 0 20 20" fill="#0891b2">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
                <span style="font-size:11px;font-weight:700;color:#0e7490;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(user.last_app_no)}</span>
            </div>` : ''}
        </div>`);
    });
}

/* ─── KARTA TIKLAYINCA HARİTAYA PAN + ZOOM ─── */
function focusOnUser(userId, lat, lng, type) {
    if (!map) return;
    try {
        const bucket = type === 'recent' ? recentMarkers : liveMarkers;
        const cardId = (type === 'recent' ? 'rcard-' : 'pcard-') + userId;

        if (activeTab !== type) switchTab(type);

        map.flyTo([lat,lng], 16);
        closeAllIW();

        const entry = bucket[userId];
        if (entry && entry.infoWindow) {
            entry.marker.openPopup();
        }
        highlightCard(cardId);
    } catch (e) {
        console.warn('[LiveMap] focusOnUser:', e);
    }
}

/* ─── YARDIMCILAR ─── */
function escHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function buildMarkerDiv(initials, color, isActive) {
    var ring = isActive ? '<span style="position:absolute;inset:-6px;border-radius:50%;border:2px solid ' + color.border + ';animation:ping-ring 2s ease-out infinite;pointer-events:none;"></span>' : '';
    return '<div style="width:42px;height:42px;border-radius:50%;background:' + (isActive ? color.bg : '#f3f4f6') + ';border:3px solid ' + (isActive ? color.border : '#d1d5db') + ';display:flex;align-items:center;justify-content:center;font-weight:900;font-size:13px;color:' + (isActive ? color.text : '#9ca3af') + ';box-shadow:0 0 0 6px ' + (isActive ? color.border + '33' : 'transparent') + ',0 4px 12px rgba(0,0,0,.18);cursor:pointer;position:relative;user-select:none;opacity:' + (isActive ? '1' : '0.65') + ';">' + escHtml(initials) + ring + '</div>';
}

function buildMarkerIWContent(user, color, isActive) {
    var n = escHtml(user.name);
    var lat = parseFloat(user.lat).toFixed(5);
    var lng = parseFloat(user.lng).toFixed(5);
    var mins = user.minutes_on_field;
    var dur = mins == null ? '' : (mins < 60 ? mins + ' dk' : Math.floor(mins / 60) + ' sa ' + (mins % 60) + ' dk');
    var d = user.last_seen_diff || '';
    var status = isActive ? 'Sahada aktif ' + dur : 'Cevrimdisi ' + d;
    var appLink = user.active_app_no ? '<a href="/admin/applications/' + (user.active_app_id || '') + '" target="_top" style="font-size:13px;font-weight:800;color:#0e7490;">' + escHtml(user.active_app_no) + '</a>' : 'Aktif gorev yok';
    var thumbHtml = '';
    if (user.recent_media && user.recent_media.length > 0) {
        for (var i = 0; i < user.recent_media.length; i++) {
            var m = user.recent_media[i];
            thumbHtml += '<a href="' + escHtml(m.full) + '" class="glightbox-iw" data-gallery="gal-' + user.id + '" data-type="image" style="display:inline-block;line-height:0;"><img src="' + escHtml(m.thumb) + '" style="width:54px;height:54px;border-radius:9px;object-fit:cover;cursor:pointer;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.12)"></a>';
        }
        thumbHtml = '<div style="display:flex;gap:7px;flex-wrap:wrap">' + thumbHtml + '</div>';
    }
    var bg = isActive ? color.bg : '#f9fafb';
    var bdr = isActive ? color.border : '#e5e7eb';
    var txtC = isActive ? color.text : '#9ca3af';
    return '<div style="width:280px;font-family:Inter,sans-serif;border-radius:14px;overflow:hidden;">'
        + '<div style="background:linear-gradient(135deg,' + bg + ', #fff);border-bottom:1px solid #f3f4f6;padding:12px 16px;display:flex;align-items:center;gap:10px;">'
        + '<span style="display:inline-flex;width:42px;height:42px;border-radius:11px;align-items:center;justify-content:center;font-weight:900;font-size:15px;background:' + bg + ';color:' + txtC + ';border:2px solid ' + bdr + ';flex-shrink:0;">' + escHtml(user.initials) + '</span>'
        + '<div><p style="font-size:13px;font-weight:700;color:#111827;margin:0">' + n + '</p>'
        + '<div style="display:flex;align-items:center;gap:4px;margin-top:2px;">'
        + '<span style="width:6px;height:6px;border-radius:50%;background:' + (isActive ? '#22c55e' : '#9ca3af') + ';display:inline-block"></span>'
        + '<span style="font-size:11px;color:#6b7280;">' + status + '</span></div></div></div>'
        + '<div style="padding:11px 16px;background:#fff;"><div style="background:#f0fdfe;border:1px solid #a5f3fc;border-radius:9px;padding:8px 11px;margin-bottom:8px;">' + appLink + '</div>' + thumbHtml + '</div>'
        + '<div style="border-top:1px solid #f3f4f6;padding:7px 16px;background:#fafafa;"><span style="font-size:10px;color:#9ca3af">' + lat + ', ' + lng + '</span></div></div>';
}

function closeAllIW() {
    Object.values(liveMarkers).forEach(function(m){ try{ m.marker.closePopup(); }catch(e){} });
    Object.values(recentMarkers).forEach(function(m){ try{ m.marker.closePopup(); }catch(e){} });
}

function highlightCard(id) {
    document.querySelectorAll('.personnel-card').forEach(c => c.classList.remove('active'));
    const card = document.getElementById(id);
    if (card) {
        card.classList.add('active');
        card.scrollIntoView({ behavior:'smooth', block:'nearest' });
    }
}

/* ─── LIGHTBOX ─── */
function openLightbox(src) {
    const img = document.getElementById('lbx-img');
    const ov  = document.getElementById('lbx-overlay');
    if (img) img.src = src;
    if (ov)  ov.classList.add('open');
}
function closeLightbox() {
    const img = document.getElementById('lbx-img');
    const ov  = document.getElementById('lbx-overlay');
    if (ov)  ov.classList.remove('open');
    if (img) img.src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

/* ─── YENİLE BUTONU ─── */
document.getElementById('refresh-btn')?.addEventListener('click', function () {
    loadLiveData();
    const orig = this.innerHTML;
    this.textContent = '⟳ Yükleniyor…';
    setTimeout(() => { this.innerHTML = orig; }, 1400);
});

/* ─── GLightbox galeri yardımcısı ─── */
let _glb = null;
function reloadGallery() {
    try {
        if (_glb) { _glb.destroy(); _glb = null; }
        if (typeof GLightbox === 'function') {
            _glb = GLightbox({ selector: '.glightbox-iw', touchNavigation: true, loop: true, zoomable: true });
        }
    } catch (e) { console.warn('[LiveMap] GLightbox reload:', e); }
}

/* ─── LEAFLET LOADER ─── */
(function(){
    var s=document.createElement('script');
    s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    s.onload=function(){
        var css=document.createElement('link');
        css.rel='stylesheet';
        css.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(css);
        initLiveLeafletMap();
    };
    document.head.appendChild(s);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
@endpush
