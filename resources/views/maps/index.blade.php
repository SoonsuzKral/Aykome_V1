@extends('layouts.admin')

@section('title', 'Aykome Maps — CBS Entegrasyon')

@prepend('meta')
<meta name="turbo-visit-control" content="no-cache">
@endprepend

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<style>
.maps-page #admin-sidebar { z-index: 1002; }
.maps-page #app-content,
.maps-page main,
.maps-page .content-wrapper {
    padding: 0 !important;
    margin: 0 !important;
    height: 100% !important;
}

#maps-wrapper {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    display: flex;
    flex-direction: row;
    z-index: 100;
}

#maps-left-panel {
    width: 280px;
    min-width: 280px;
    height: 100%;
    overflow-y: auto;
    background: #1e293b;
    color: #e2e8f0;
    z-index: 1001;
    flex-shrink: 0;
}

#map-canvas {
    flex: 1;
    height: 100%;
    min-height: 0;
    position: relative;
    background: #f1f5f9;
}

#maps-left-panel::-webkit-scrollbar { width: 4px; }
#maps-left-panel::-webkit-scrollbar-track { background: #1e293b; }
#maps-left-panel::-webkit-scrollbar-thumb { background: #475569; border-radius: 2px; }

.panel-header {
    padding: 14px 16px;
    border-bottom: 1px solid #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-header h2 {
    font-size: 14px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0;
}

.accordion-header {
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    border-bottom: 1px solid #334155;
    user-select: none;
    transition: background 0.2s;
    background: #1e293b;
}

.accordion-header:hover { background: #334155; }
.accordion-header .arrow { font-size: 10px; transition: transform 0.2s; }
.accordion-header.collapsed .arrow { transform: rotate(-90deg); }

.accordion-body { padding: 8px 16px 12px; border-bottom: 1px solid #334155; }
.accordion-body.hidden { display: none; }

.layer-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 12px;
    color: #cbd5e1;
}

.layer-row label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    flex: 1;
    min-width: 0;
}

.layer-row input[type="checkbox"] {
    accent-color: #E87722;
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

.layer-opacity {
    width: 60px;
    height: 3px;
    accent-color: #E87722;
    flex-shrink: 0;
    cursor: pointer;
}

.color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.2);
}

.basemap-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 12px;
    color: #cbd5e1;
    cursor: pointer;
}

.basemap-row input[type="radio"] {
    accent-color: #E87722;
    width: 14px;
    height: 14px;
}

.draw-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.draw-btn {
    padding: 6px 10px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 6px;
    color: #cbd5e1;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.draw-btn:hover { background: #475569; color: #f1f5f9; border-color: #E87722; }
.draw-btn.active { background: #E87722; color: #fff; border-color: #E87722; }
.draw-btn-danger { border-color: #ef4444; color: #fca5a5; }
.draw-btn-danger:hover { background: #ef4444; color: #fff; }

.filter-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    font-size: 12px;
    color: #cbd5e1;
    cursor: pointer;
}

.filter-row input[type="checkbox"] { width: 14px; height: 14px; }
.filter-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}

#maps-quick-actions {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 500;
    display: flex;
    gap: 6px;
}

.qa-btn {
    background: white;
    border: none;
    border-radius: 6px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.qa-btn:hover { background: #E87722; color: white; box-shadow: 0 2px 12px rgba(232,119,34,0.3); }

.qa-btn-primary {
    background: #E87722;
    color: white;
}

.qa-btn-primary:hover { background: #d06914; }

#maps-statusbar {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(15,23,42,0.9);
    color: #94a3b8;
    padding: 5px 12px;
    font-size: 11px;
    font-family: monospace;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 500;
    border-top: 1px solid #334155;
}

#maps-coords { color: #e2e8f0; }
#maps-active-layers { color: #E87722; }
.maps-input-sm {
    background: #334155; border: 1px solid #475569; border-radius: 4px; padding: 5px 8px;
    color: #e2e8f0; font-size: 11px; outline: none; box-sizing: border-box;
}
.maps-input-sm:focus { border-color: #E87722; box-shadow: 0 0 0 2px rgba(232,119,34,0.2); }
.maps-input-sm::placeholder { color: #64748b; }

#maps-loader {
    position: fixed; inset: 0; z-index: 99999;
    background: #0f172a;
    display: flex; align-items: center; justify-content: center;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}
#maps-loader.hidden { opacity: 0; visibility: hidden; }
.loader-container { text-align: center; }
.loader-ring {
    width: 80px; height: 80px; margin: 0 auto 20px;
    border: 4px solid #1e293b;
    border-top: 4px solid #E87722;
    border-right: 4px solid #f97316;
    border-radius: 50%;
    animation: loaderSpin 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
    box-shadow: 0 0 30px rgba(232,119,34,0.15);
}
@keyframes loaderSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.loader-text {
    font-size: 28px; font-weight: 800;
    background: linear-gradient(135deg, #E87722, #f97316, #fb923c);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    letter-spacing: 4px; margin-bottom: 8px;
}
.loader-subtext {
    font-size: 13px; color: #64748b;
    animation: loaderPulse 1.5s ease-in-out infinite;
}
@keyframes loaderPulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }

.maps-loader-mini {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; color: #E87722;
}
.maps-loader-mini::before {
    content: ''; width: 12px; height: 12px;
    border: 2px solid #334155; border-top: 2px solid #E87722;
    border-radius: 50%; animation: loaderSpin 0.8s linear infinite;
}

.maps-pin {
    width: 32px; height: 32px; border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg); display: flex; align-items: center;
    justify-content: center; font-size: 14px; color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 2px solid rgba(255,255,255,0.5);
}
.maps-pin > * { transform: rotate(45deg); }

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(249,115,22,0.7); }
    50% { box-shadow: 0 0 0 8px rgba(249,115,22,0); }
}
.maps-pin-pulse { animation: pulse 2s infinite; }

body.maps-fullscreen #btn-fullscreen { background: #ef4444; color: white; }
#maps-left-panel .accordion-body { max-height: 200px; overflow-y: auto; }
.dr-parsel-kart {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    margin-bottom: 6px; overflow: hidden; transition: all 0.15s;
}
.dr-parsel-kart:hover { border-color: #E87722; box-shadow: 0 1px 4px rgba(232,119,34,0.1); }
.dr-parsel-header {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; cursor: pointer; font-size: 12px;
}
.dr-parsel-header input[type="checkbox"] { accent-color: #E87722; width: 16px; height: 16px; }
.dr-parsel-details {
    padding: 0 10px 8px; display: flex; flex-direction: column; gap: 2px;
}
.dr-detail { font-size: 11px; color: #475569; line-height: 1.5; }
.dr-cadde-section { border-top: 1px solid #e2e8f0; background: #f1f5f9; }
.dr-cadde-item { border-bottom: 1px solid #e2e8f0; }
.dr-cadde-item:last-child { border-bottom: none; }
.dr-kapi-section { background: #f8fafc; border-top: 1px solid #e2e8f0; }


/* Draggable panel styles */
.drag-panel { position: fixed; cursor: default; user-select: none; }
.drag-header { cursor: grab; }
.drag-header:active { cursor: grabbing; }

/* Loading overlay */
#maps-loading-overlay {
    position: fixed; inset: 0; z-index: 100000;
    background: rgba(15,23,42,0.7); backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center;
    cursor: wait;
}
#maps-loading-overlay.active { display: flex; }
.maps-loading-box {
    background: #1e293b; border-radius: 16px; padding: 40px 48px;
    text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    border: 1px solid #334155;
}
.maps-loading-ring {
    width: 56px; height: 56px; margin: 0 auto 20px;
    border: 4px solid #334155; border-top: 4px solid #E87722;
    border-radius: 50%; animation: loaderSpin 0.8s cubic-bezier(0.68,-0.55,0.27,1.55) infinite;
}
.maps-loading-text { font-size: 16px; font-weight: 600; color: #e2e8f0; margin-bottom: 4px; }
.maps-loading-sub { font-size: 12px; color: #64748b; }

#maps-toast {
    position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(100px);
    background: #059669; color: white; padding: 12px 24px; border-radius: 8px;
    font-size: 14px; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000; opacity: 0; transition: all 0.3s ease;
}
#maps-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }

#maps-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 1999; display: none;
}
#maps-overlay.active { display: block; }

#maps-basvuru-panel {
    position: fixed; width: 520px; max-width: 98vw; max-height: 92vh; background: white;
    border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    z-index: 2000; display: none; overflow: hidden; flex-direction: column; user-select: none;
}
#maps-basvuru-panel.open { display: flex; }

.basvuru-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white; padding: 16px 20px; display: flex;
    align-items: center; justify-content: space-between;
    cursor: grab;
}
.basvuru-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
.basvuru-close {
    background: none; border: none; color: white; font-size: 24px;
    cursor: pointer; opacity: 0.8; line-height: 1;
}
.basvuru-close:hover { opacity: 1; }

.basvuru-body { padding: 20px; overflow-y: auto; flex: 1; }
.basvuru-footer {
    padding: 14px 20px; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; gap: 10px;
}

.btn-b {
    padding: 8px 16px; border-radius: 8px; font-weight: 500;
    cursor: pointer; border: none; transition: all 0.2s; font-size: 13px;
}
.btn-b-prev { background: #f1f5f9; color: #475569; }
.btn-b-prev:hover { background: #e2e8f0; }
.btn-b-next { background: #E87722; color: white; }
.btn-b-next:hover { background: #d06914; }
.btn-b-submit { background: #059669; color: white; }
.btn-b-submit:hover { background: #047857; }

.f-group { margin-bottom: 14px; }
.f-label { display: block; font-size: 11px; font-weight: 500; color: #64748b; margin-bottom: 4px; }
.f-input, .f-select {
    width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0;
    border-radius: 8px; font-size: 13px; background: white;
}
.f-input:focus, .f-select:focus { outline: none; border-color: #E87722; }
.f-input:disabled, .f-input[readonly] { background: #f8fafc; color: #64748b; }
.f-textarea {
    width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0;
    border-radius: 8px; font-size: 13px; resize: vertical; min-height: 70px;
}
.f-textarea:focus { outline: none; border-color: #E87722; }
.f-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.f-section {
    background: #f8fafc; border-radius: 10px; padding: 14px; margin-bottom: 14px;
}
.f-section-title {
    font-size: 10px; font-weight: 600; color: #64748b;
    text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;
}

.tip-selector { display: flex; gap: 10px; margin-bottom: 14px; }
.tip-option {
    flex: 1; padding: 12px 8px; border: 2px solid #e2e8f0;
    border-radius: 10px; cursor: pointer; text-align: center; transition: all 0.2s;
}
.tip-option:hover { border-color: #E87722; }
.tip-option.selected { border-color: #E87722; background: rgba(232,119,34,0.05); }
.tip-option input { display: none; }
.tip-icon { font-size: 20px; margin-bottom: 4px; }
.tip-label { font-size: 11px; font-weight: 500; color: #475569; }

.ortak-kurum-section { margin-top: 14px; display: none; }
.ortak-kurum-section.show { display: block; }
.ortak-kurum-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px;
}
.ortak-kurum-item {
    padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px;
    text-align: center; cursor: pointer; transition: all 0.2s; font-size: 11px;
}
.ortak-kurum-item:hover { border-color: #E87722; }
.ortak-kurum-item.selected { border-color: #E87722; background: rgba(232,119,34,0.1); }

#maps-search-control {
    position: absolute;
    top: 10px; left: 50px;
    z-index: 1000;
    width: 320px;
}
@media (max-width: 768px) {
    #maps-search-control { width: calc(100vw - 120px); left: 10px; }
}
.search-box input {
    width: 100%; padding: 8px 14px;
    border: none; border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
    font-size: 14px; outline: none;
    background: white;
}
.search-dropdown {
    background: white; border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    margin-top: 4px; max-height: 280px;
    overflow-y: auto;
}
.search-result-item {
    padding: 10px 14px; cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px; line-height: 1.4;
    display: flex; align-items: flex-start; gap: 8px;
}
.search-result-item:hover { background: #f8fafc; }
.search-result-item .result-icon { color: #94a3b8; margin-top: 2px; }
.search-result-item .result-main { font-weight: 500; color: #1e293b; }
.search-result-item .result-sub { color: #64748b; font-size: 12px; }

#maps-toggle-mobile { display: none; }

@media (max-width: 768px) {
    #maps-left-panel {
        position: fixed; left: -280px; top: 0; height: 100%;
        transition: left 0.3s; z-index: 2000;
    }
    #maps-left-panel.mobile-open { left: 0; }
    #map-canvas { left: 0 !important; }
    #maps-toggle-mobile {
        display: flex; position: fixed; bottom: 20px; left: 20px;
        z-index: 2001; background: #E87722;
        color: white; border: none; border-radius: 50%;
        width: 48px; height: 48px; font-size: 20px;
        align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    #maps-quick-actions { top: 8px; right: 8px; gap: 4px; }
    .qa-btn { padding: 5px 8px; font-size: 11px; }
    #maps-statusbar { font-size: 10px; flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
<div id="maps-wrapper">
    <div id="maps-left-panel">
        <div class="panel-header">
            <h2>🗺️ Katmanlar</h2>
            <span style="font-size:10px;color:#64748b;">Şanlıurfa CBS</span>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🛰️ Altlık Harita</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <label class="basemap-row"><input type="radio" name="basemap" value="google" checked> 🛰️ Google Uydu</label>
            <label class="basemap-row"><input type="radio" name="basemap" value="osm"> 🗺️ OpenStreetMap</label>
            <label class="basemap-row"><input type="radio" name="basemap" value="topo"> ⛰️ Topoğrafya</label>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🏛️ İdari Sınırlar</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#f97316;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_MAHALLE_KOYLER"><span>Mahalle Sınırları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#a855f7;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_KADASTRO_ADA"><span>Adalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📐 Kadastro & Parseller</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_KADASTRO_PARSEL"><span>Parseller (Genel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#22c55e;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:TKGM_PARSEL"><span>Parseller (TKGM Güncel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🏗️ Yapı & Adres</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#94a3b8;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_BINA"><span>Binalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#f59e0b;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:m_Numarataj"><span>Kapı Numaraları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#64748b;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_CADDE_SOKAK"><span>Cadde/Sokak Hatları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🔌 Altyapı Şebekeleri</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#eab308;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_ELEKTRIK_LINKS"><span>Aykome Elektrik</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_DOGALGAZ_LINKS"><span>Doğalgaz (Hatlar)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#3b82f6;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_DOGALGAZ_NODES"><span>Doğalgaz (Noktalar)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🛣️ Yol Analizi (15m) <span class="badge" style="background:#22c55e;font-size:9px;padding:1px 5px;border-radius:3px;margin-left:4px;">YENİ</span></span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body" id="road-analysis-panel">
            <div class="layer-row"><label><span class="color-dot" style="background:#22c55e;"></span><input type="checkbox" id="road-15-alti"><span>15 metre ALTINDAKİ yollar</span></label><span style="font-size:9px;color:#64748b;">İlçe Belediyesi</span></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" id="road-15-ustu"><span>15 metre ÜSTÜNDEKİ yollar</span></label><span style="font-size:9px;color:#64748b;">Büyükşehir</span></div>
            <div style="margin-top:8px;border-top:1px solid #334155;padding-top:8px;">
                <button id="btn-hat-kimligi" class="draw-btn" style="width:100%;justify-content:center;" onclick="toggleHatKimligi()">
                    🔍 Hat Kimliği Sorgula
                </button>
                <div style="font-size:10px;color:#64748b;margin-top:4px;text-align:center;">Aktifken yola tıkla, detayları gör</div>
            </div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>✏️ Çizim Araçları</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="draw-tools">
                <button class="draw-btn" onclick="startDrawMarker()">📍 Nokta</button>
                <button class="draw-btn" onclick="startDrawLine()">📏 Çizgi</button>
                <button class="draw-btn" onclick="startDrawPolygon()">⬡ Alan</button>
                <button class="draw-btn" onclick="startDrawRectangle()">▭ Dikdörtgen</button>
                <button class="draw-btn" onclick="startDrawCircle()">⭕ Daire</button>
                <button class="draw-btn" onclick="startDrawCircleMarker()">🔘 İşaret</button>
                <button class="draw-btn draw-btn-danger" onclick="clearDrawing()">🗑️ Temizle</button>
            </div>
            <div id="draw-info" style="margin-top:6px;font-size:11px;color:#64748b;display:none;">Çizim aktif. ESC ile çık.</div>
            <div id="draw-measurement" style="margin-top:6px;font-size:11px;color:#22c55e;display:none;"></div>
            <div id="draw-utility-warning" style="margin-top:6px;font-size:12px;color:#ef4444;display:none;"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🔍 Ada/Parsel/Pafta Sorgula</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div style="display:flex;gap:4px;margin-bottom:6px;flex-wrap:wrap;">
                <input type="text" id="parsel-search-ada" placeholder="Ada No" class="maps-input-sm" style="flex:1;min-width:60px;">
                <input type="text" id="parsel-search-parsel" placeholder="Parsel No" class="maps-input-sm" style="flex:1;min-width:60px;">
            </div>
            <input type="text" id="parsel-search-pafta" placeholder="Pafta (opsiyonel)" class="maps-input-sm" style="width:100%;margin-bottom:6px;box-sizing:border-box;">
            <button class="draw-btn" style="width:100%;justify-content:center;" onclick="parselAra()">🔍 Ada/Parsel Sorgula</button>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📋 Parsel Listesi</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body" id="parsel-listesi-panel">
            <div style="font-size:11px;color:#64748b;margin-bottom:6px;">Çizim yapınca parseller burada listelenecek.</div>
            <div id="parsel-listesi-icerik"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📌 Başvuru Filtresi</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <label class="filter-row"><input type="checkbox" class="filter-status" value="submitted" checked><span class="filter-dot" style="background:#facc15;"></span> Beklemede</label>
            <label class="filter-row"><input type="checkbox" class="filter-status" value="licensed" checked><span class="filter-dot" style="background:#22c55e;"></span> Onaylandı</label>
            <label class="filter-row"><input type="checkbox" class="filter-status" value="field_work" checked><span class="filter-dot" style="background:#f97316;"></span> Saha Çalışması</label>
            <label class="filter-row"><input type="checkbox" class="filter-status" value="awaiting_payment" checked><span class="filter-dot" style="background:#3b82f6;"></span> Ödeme Bekliyor</label>
            <label class="filter-row"><input type="checkbox" class="filter-status" value="completed" checked><span class="filter-dot" style="background:#475569;"></span> Tamamlandı</label>
            <label class="filter-row"><input type="checkbox" class="filter-status" value="rejected" checked><span class="filter-dot" style="background:#ef4444;"></span> Reddedildi</label>
        </div>
    </div>

    <div id="map-canvas">
        <div id="maps-quick-actions">
            <button class="qa-btn qa-btn-primary" id="btn-yeni-basvuru">+ Başvuru</button>
            <button class="qa-btn" id="btn-sorgula">🔍 Sorgula</button>
            <button class="qa-btn" id="btn-konum">📍 Konumum</button>
            <button class="qa-btn" id="btn-fullscreen">⛶ Tam Ekran</button>
        </div>

        <div id="maps-search-control">
            <div class="search-box">
                <input type="text" id="maps-search-input" placeholder="🔍 Adres veya yer ara..." autocomplete="off">
                <div id="maps-search-results" class="search-dropdown" style="display:none"></div>
            </div>
        </div>

        <div id="maps-map-canvas" style="width:100%;height:100%;"></div>

        <div id="maps-statusbar">
            <span id="maps-coords">📍 37.1598° K | 38.7969° D</span>
            <span id="maps-active-layers">Aktif: 0 katman</span>
            <span>© 2026 AYKOME — HGB Bilişim  | Şanlıurfa CBS</span>
        </div>
    </div>
</div>

<button id="maps-toggle-mobile">☰</button>

<div id="maps-overlay"></div>
<div id="maps-loading-overlay">
    <div class="maps-loading-box">
        <div class="maps-loading-ring"></div>
        <div class="maps-loading-text">Parseller sorgulanıyor...</div>
        <div class="maps-loading-sub">Binalar, kapı numaraları ve cadde/sokak bilgileri taranıyor</div>
    </div>
</div>
<div id="maps-loader">
    <div class="loader-container">
        <div class="loader-ring"></div>
        <div class="loader-text">AYKOME CBS</div>
        <div class="loader-subtext">Harita yükleniyor...</div>
    </div>
</div>

<div id="maps-basvuru-panel">
    <div class="basvuru-header">
        <h3 id="basvuru-title">Yeni Başvuru</h3>
        <button onclick="closeBasvuruPanel()" class="basvuru-close">×</button>
    </div>

    <div class="basvuru-body">
        <div class="f-section">
            <div class="f-section-title">Başvuru Tipi</div>
            <div class="tip-selector">
                <label class="tip-option selected" onclick="selectTip('kazi_ruhsat', this)">
                    <input type="radio" name="tip" value="kazi_ruhsat" checked>
                    <div class="tip-icon">📋</div>
                    <div class="tip-label">Kazı Ruhsatı</div>
                </label>
                <label class="tip-option" onclick="selectTip('ortak_kazi', this)">
                    <input type="radio" name="tip" value="ortak_kazi">
                    <div class="tip-icon">🤝</div>
                    <div class="tip-label">Ortak Kazı</div>
                </label>
            </div>
        </div>

        <div class="f-section">
            <div class="f-section-title">Konum</div>
            <div id="basvuru-adres-ozet" class="adres-ozet" style="background:#f8fafc;border-radius:8px;padding:10px;margin-bottom:10px;font-size:13px;line-height:1.6;color:#1e293b;">
                <span style="color:#94a3b8;">Adres bilgisi alınıyor...</span>
            </div>
            <div style="font-size:11px;color:#64748b;margin-bottom:4px;">
                <span id="basvuru-coord-display"></span>
            </div>
            <div id="basvuru-area-display" style="font-size:11px;color:#E87722;font-weight:600;margin-bottom:8px;display:none;"></div>
            <input type="hidden" id="bs-lat">
            <input type="hidden" id="bs-lng">
            <input type="hidden" id="bs-ilce">
            <input type="hidden" id="bs-mahalle">
            <input type="hidden" id="bs-cadde">
        </div>

        <div id="parsel-tablosu-section" class="f-section" style="display:none;">
            <div class="f-section-title">Seçili Alandaki Parseller</div>
            <div id="parsel-tablosu-container" style="max-height:160px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;"></div>
        </div>

        <div class="f-section">
            <div class="f-section-title">Kazı Detayları</div>
            <div class="f-group">
                <label class="f-label">Kazı Açıklaması *</label>
                <textarea id="bs-aciklama" class="f-textarea" placeholder="Kazının amacını ve detaylarını açıklayın..."></textarea>
            </div>
            <div class="f-grid">
                <div class="f-group">
                    <label class="f-label">Kazı Derinliği (metre)</label>
                    <input type="number" id="bs-derinlik" class="f-input" min="0" step="0.5" placeholder="0.0">
                </div>
                <div class="f-group">
                    <label class="f-label">Tahmini Süre (gün)</label>
                    <input type="number" id="bs-sure" class="f-input" min="1" step="1" placeholder="1">
                </div>
            </div>
        </div>

        <div id="ortak-kurum-section" class="ortak-kurum-section" style="display:none;">
            <div class="f-section-title">Ortak Çalışılacak Kurumlar</div>
            <div class="ortak-kurum-grid">
                <div class="ortak-kurum-item" onclick="toggleOrtakKurum(this,'AKSA')">AKSA</div>
                <div class="ortak-kurum-item" onclick="toggleOrtakKurum(this,'TEDAŞ')">TEDAŞ</div>
                <div class="ortak-kurum-item" onclick="toggleOrtakKurum(this,'ŞUSKİ')">ŞUSKİ</div>
                <div class="ortak-kurum-item" onclick="toggleOrtakKurum(this,'Türk Telekom')">Türk Telekom</div>
            </div>
            <input type="hidden" id="bs-ortak-kurumlar" value="">
        </div>
    </div>

    <div class="basvuru-footer">
        <button onclick="closeBasvuruPanel()" class="btn-b btn-b-prev">İptal</button>
        <button onclick="basvuruSubmit()" class="btn-b btn-b-submit">Kaydet ve Başvuruya Git</button>
    </div>
</div>

<div id="maps-toast"></div>

<!-- Hat Kimliği Detay Paneli -->
<div id="hat-kimligi-panel" style="display:none;position:fixed;top:60px;right:20px;width:360px;max-width:90vw;max-height:80vh;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.2);z-index:2000;overflow:hidden;font-size:13px;">
    <div class="drag-header" style="background:linear-gradient(135deg,#1e293b,#334155);color:white;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;cursor:grab;">
        <span style="font-weight:600;">📋 HAT KİMLİĞİ DETAY RAPORU</span>
        <button onclick="kapatHatKimligiPanel()" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;line-height:1;">×</button>
    </div>
    <div id="hat-kimligi-panel-body" style="padding:12px 16px;overflow-y:auto;max-height:calc(80vh - 100px);">
        <div style="text-align:center;color:#94a3b8;padding:20px;">Yükleniyor...</div>
    </div>
    <div style="padding:10px 16px;border-top:1px solid #e2e8f0;display:flex;gap:8px;">
        <button onclick="hatKimligiBasvuruAc()" class="btn-b btn-b-submit" style="flex:1;">📋 Başvuru Yap</button>
        <button onclick="kapatHatKimligiPanel()" class="btn-b btn-b-prev">Kapat</button>
    </div>
</div>

<!-- Hat Kimliği için hidden state -->
<input type="hidden" id="hk-last-props" value="">
<input type="hidden" id="hk-last-lat" value="">
<input type="hidden" id="hk-last-lng" value="">

<!-- Çizim Detay Raporu Paneli -->
<div id="draw-report-panel" style="display:none;position:fixed;top:60px;right:20px;width:400px;max-width:90vw;max-height:85vh;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.2);z-index:2000;overflow:hidden;font-size:13px;">
    <div class="drag-header" style="background:linear-gradient(135deg,#1e293b,#334155);color:white;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;cursor:grab;">
        <span style="font-weight:600;">📋 ÇİZİM RAPORU</span>
        <button onclick="kapatDrawReport()" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;line-height:1;">×</button>
    </div>
    <div id="draw-report-body" style="padding:12px 16px;overflow-y:auto;max-height:calc(85vh - 130px);">
        <div style="text-align:center;color:#94a3b8;padding:30px;">🔍 Parseller sorgulanıyor...</div>
    </div>
    <div id="draw-report-footer" style="padding:10px 16px;border-top:1px solid #e2e8f0;display:none;gap:8px;align-items:center;flex-wrap:wrap;">
        <div style="flex:1;font-size:11px;color:#64748b;" id="draw-report-count">0 parsel seçildi</div>
        <button onclick="kapatDrawReport()" class="btn-b btn-b-prev" style="font-size:12px;padding:6px 14px;">İptal</button>
        <button id="dr-yolhat-btn" onclick="drawReportYolHatSorgula()" class="btn-b" style="font-size:12px;padding:6px 14px;display:none;" disabled>🔍 Yol Hat Sorgula</button>
        <button id="dr-ileri-btn" onclick="drawReportBasvuruyaGit()" class="btn-b btn-b-submit" style="font-size:12px;padding:6px 14px;" disabled>İleri →</button>
    </div>
</div>
<input type="hidden" id="dr-selected" value="">

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
(function() {
    try{
        if(window.Pusher){
            window.Pusher.logToConsole=!1;
            window.Pusher.Runtime.createXHR=function(){return new XMLHttpRequest};
            if(window.Pusher.instances)window.Pusher.instances.forEach(function(i){try{i.disconnect()}catch(e){}});
            window.Pusher.prototype.connect=function(){return this};
            window.Pusher.prototype.send=function(){};
        }
        if(window.Echo){
            try{window.Echo.disconnect&&window.Echo.disconnect()}catch(e){}
            try{window.Echo.leaveChannel&&window.Echo.leaveChannel()}catch(e){}
            window.Echo={listen:function(){return this},channel:function(){return this},private:function(){return this},join:function(){return this},leave:function(){},leaveChannel:function(){},connector:null,disconnect:function(){}};
        }
        var _OWS=window.WebSocket;
        window.WebSocket=function(u,p){
            if(!u||u.toString().match(/pusher|8080|soketi|laravel-echo|reverb/i)){
                var f=new EventTarget();
                f.readyState=3;f.close=f.send=function(){};f.addEventListener=function(){};
                return f;
            }
            return p?new _OWS(u,p):new _OWS(u);
        };
        window.WebSocket.prototype=_OWS.prototype;
        window.WebSocket.CONNECTING=0;window.WebSocket.OPEN=1;window.WebSocket.CLOSING=2;window.WebSocket.CLOSED=3;
    }catch(e){}
})();
</script>
<script>
(function(w){
'use strict';

var GEO3_WMS='https://geo3.sanliurfa.bel.tr:8091/geoserver/wms';
var PROXY_URL='/maps/proxy?url=';
var URFA_CENTER=[37.1598,38.7969];
var URFA_BOUNDS=[[37.0,38.6],[37.4,39.0]];

var mapsMap=null, basemapLayers={}, wmsLayers={};
var roadLayerAlti=null, roadLayerUstu=null;
var basvuruLayer=null, drawnItems=null, currentDrawLayer=null, _isDrawing=!1, _drawJustFinished=!1;
var geo3Layers={};
var statusIcons={
    field_work:       {bg:'#f97316',icon:'⛏',label:'Saha Çalışması',pulse:!0},
    licensed:         {bg:'#22c55e',icon:'✓',label:'Onaylandı',pulse:!1},
    awaiting_payment: {bg:'#3b82f6',icon:'₺',label:'Ödeme Bekliyor',pulse:!1},
    receipt_pending:  {bg:'#8b5cf6',icon:'📄',label:'Makbuz Bekliyor',pulse:!1},
    submitted:        {bg:'#94a3b8',icon:'🕐',label:'Beklemede',pulse:!1},
    rejected:         {bg:'#ef4444',icon:'✕',label:'Reddedildi',pulse:!1},
    completed:        {bg:'#475569',icon:'✓✓',label:'Tamamlandı',pulse:!1},
    approved:         {bg:'#22c55e',icon:'✓',label:'Onaylandı',pulse:!1},
};

function createWmsLayer(url, layerName, opts){
    var layer=L.tileLayer.wms(url,Object.assign({
        format:'image/png',transparent:!0,version:'1.3.0',maxZoom:24
    },opts));
    layer.on('tileerror',function(e){
        if(e.tile._retryCount>2)return;
        e.tile._retryCount=(e.tile._retryCount||0)+1;
        var src=e.tile.src.replace(/&retry=\d+/,'');
        setTimeout(function(){e.tile.src=src+'&retry='+Date.now()},800);
    });
    return layer;
}

function createStatusIcon(status){
    var s=statusIcons[status]||statusIcons.submitted;
    var pc=s.pulse?' maps-pin-pulse':'';
    return L.divIcon({
        className:'',
        html:'<div class="maps-pin'+pc+'" style="background:'+s.bg+'"><span>'+s.icon+'</span></div>',
        iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[0,-32]
    });
}

function updateActiveLayerCount() {
    var count = 0;
    document.querySelectorAll('.katman-checkbox:checked').forEach(function() { count++; });
    var el = document.getElementById('maps-active-layers');
    if (el) el.textContent = 'Aktif: ' + count + ' katman';
}

function initMaps(){
    var canvas=document.getElementById('maps-map-canvas');
    if(!canvas)return;

    var sidebar=document.getElementById('admin-sidebar');
    if(sidebar){
        document.getElementById('maps-wrapper').style.left=sidebar.offsetWidth+'px';
    }

    mapsMap=L.map('maps-map-canvas',{
        center:URFA_CENTER,zoom:15,minZoom:12,maxZoom:20,
        maxBounds:URFA_BOUNDS,maxBoundsViscosity:0.8,preferCanvas:!0
    });

    basemapLayers.google=L.tileLayer('http://mt0.google.com/vt/lyrs=s&hl=tr&x={x}&y={y}&z={z}',{attribution:'© Google',maxZoom:21}).addTo(mapsMap);
    basemapLayers.osm=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19});
    basemapLayers.topos=L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',{attribution:'© OpenTopoMap',maxZoom:17});

    geo3Layers={
        // İdari Sınırlar
        'cbs:MISMAP_MAHALLE_KOYLER':       {on:!1, group:'admin'},
        'cbs:MISMAP_KADASTRO_ADA':         {on:!1, group:'admin'},
        // Kadastro & Parseller
        'smpns:MISMAP_NUM_KADASTRO_PARSEL':{on:!1, group:'cadastre'},
        'smpns:TKGM_PARSEL':               {on:!1, group:'cadastre'},
        // Yapı & Adres
        'smpns:MISMAP_NUM_BINA':           {on:!1, group:'building'},
        'smpns:m_Numarataj':               {on:!1, group:'building'},
        'cbs:MISMAP_CADDE_SOKAK':          {on:!1, group:'building'},
        // Altyapı Şebekeleri
        'aykome:AYK_ELEKTRIK_LINKS':       {on:!1, group:'utility'},
        'aykome:AYK_DOGALGAZ_LINKS':       {on:!1, group:'utility'},
        'aykome:AYK_DOGALGAZ_NODES':       {on:!1, group:'utility'}
    };

    Object.keys(geo3Layers).forEach(function(l){
        wmsLayers[l]=createWmsLayer(GEO3_WMS,l,{layers:l,opacity:0.7,zIndex:100});
        if(geo3Layers[l].on) wmsLayers[l].addTo(mapsMap);
    });

    drawnItems=new L.FeatureGroup();
    mapsMap.addLayer(drawnItems);

    mapsMap.on('click',handleMapsClick);
    mapsMap.on('mousemove',updateCoords);
    mapsMap.on('zoomend',updateCoords);
    mapsMap.on('contextmenu',function(e){
        var lat=e.latlng.lat,lng=e.latlng.lng;
        L.popup().setLatLng(e.latlng).setContent(
            '<div style="text-align:center;padding:4px">'+
            '<b>\uD83D\uDCCD '+lat.toFixed(6)+', '+lng.toFixed(6)+'</b><br><br>'+
            '<a href="https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='+lat+','+lng+'" target="_blank" '+
            'style="background:#4285f4;color:white;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:13px">'+
            '\uD83D\uDEB6 Street View\'da A\u00e7</a>'+
            '</div>'
        ).openOn(mapsMap);
    });
    mapsMap.on('draw:created',handleDrawCreated);
    mapsMap.on('draw:drawstart',onDrawStart);
    mapsMap.on('layeradd layerremove',updateActiveLayerCount);

    loadBasvuruMarkers();
    setupEventListeners();
    updateActiveLayerCount();
    initSearchControl();

    setTimeout(function(){
        mapsMap.invalidateSize();
        var loader=document.getElementById('maps-loader');
        if(loader) loader.classList.add('hidden');
    },800);
}
w.geo3Layers=geo3Layers;

function updateCoords(e){
    if (!e || !e.latlng) { if (mapsMap) updateCoords({latlng:mapsMap.getCenter()}); return; }
    var c = e.latlng;
    var el = document.getElementById('maps-coords');
    if (el) el.textContent = '\uD83D\uDCCD ' + c.lat.toFixed(6) + '\u00B0 K | ' + c.lng.toFixed(6) + '\u00B0 D';
}

function handleMapsClick(e){
    if(_isDrawing || _drawJustFinished) return;
    updateCoords(e);
    var lat=e.latlng.lat, lng=e.latlng.lng;
    var wmsLyr='smpns:MISMAP_NUM_KADASTRO_PARSEL,smpns:MISMAP_NUM_BINA,cbs:MISMAP_CADDE_SOKAK,smpns:m_Numarataj,cbs:MISMAP_MAHALLE_KOYLER,cbs:MISMAP_KADASTRO_ADA';
    var size=mapsMap.getSize();
    var point=mapsMap.latLngToContainerPoint(e.latlng);
    var gfiUrl='/maps/proxy?url='+encodeURIComponent(
        'https://geo3.sanliurfa.bel.tr:8091/geoserver/wms?SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo'+
        '&LAYERS='+wmsLyr+'&QUERY_LAYERS='+wmsLyr+
        '&BBOX='+mapsMap.getBounds().toBBoxString()+'&WIDTH='+Math.round(size.x)+'&HEIGHT='+Math.round(size.y)+
        '&X='+Math.round(point.x)+'&Y='+Math.round(point.y)+
        '&INFO_FORMAT=application/json&SRS=EPSG:4326&FEATURE_COUNT=5&BUFFER=15'
    );

    fetch(gfiUrl)
    .then(function(r){return r.json()})
    .then(function(gfiData){
        // WMS'den parsel/bina bilgisi gelirse kullan, yoksa Nominatim fallback
        if(gfiData&&gfiData.features&&gfiData.features.length){
            var gfiProps=gfiData.features[0].properties||{};
            var ilce=gfiProps.ILCE||gfiProps.ilce||'';
            var mahalle=gfiProps.MAHALLE_AD||gfiProps.MAHALLE||gfiProps.mahalle||'';
            var cadde=((gfiProps.CADDE_SO_1||'')+' '+(gfiProps.CADDE_SO_2||'')).trim()||gfiProps.CADDE||gfiProps.cadde||'';
            var ada=gfiProps.ADA||'';
            var parsel=gfiProps.PARSEL||'';
            window._sonTiklama={lat:lat,lng:lng,ilce:ilce,mahalle:mahalle,cadde:cadde,ada:ada,parsel:parsel,displayName:(ilce?ilce+' / ':'')+(mahalle?mahalle:'')};
            gosterPopup(lat,lng,ilce,mahalle,cadde,ada,parsel);
            return;
        }
        throw new Error('WMS bos');
    })
    .catch(function(){
        // Nominatim fallback
        if(_nominatimController) _nominatimController.abort();
        _nominatimController=new AbortController();
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&addressdetails=1&accept-language=tr',{signal:_nominatimController.signal})
        .then(function(r){return r.json()})
        .then(function(data){
            var addr=data.address||{};
            var ilce=addr.county||addr.town||addr.city_district||'';
            var mahalle=addr.suburb||addr.neighbourhood||addr.quarter||addr.village||'';
            var cadde=addr.road||'';
            window._sonTiklama={lat:lat,lng:lng,ilce:ilce,mahalle:mahalle,cadde:cadde,ada:'',parsel:'',displayName:data.display_name||lat.toFixed(6)+', '+lng.toFixed(6)};
            gosterPopup(lat,lng,ilce,mahalle,cadde,'','');
        })
        .catch(function(err){
            if(err&&err.name==='AbortError')return;
            window._sonTiklama={lat:lat,lng:lng,ilce:'',mahalle:'',cadde:'',ada:'',parsel:'',displayName:lat.toFixed(6)+', '+lng.toFixed(6)};
            gosterPopup(lat,lng,'','','','','');
        });
    });
}

function gosterPopup(lat,lng,ilce,mahalle,cadde,ada,parsel){
    var locStr=(ilce?ilce+' / ':'')+(mahalle?mahalle:'');
    var adaParselStr=(ada||parsel)?'<div style="font-size:11px;color:#1e293b;margin-bottom:4px;">📌 Ada '+(ada||'—')+' / Parsel '+(parsel||'—')+'</div>':'';
    var streetViewLink='<a href="https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='+lat+','+lng+'" target="_blank" style="display:inline-block;background:#4285f4;color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:10px;margin-top:4px;">\uD83D\uDEB6 Street View</a>';
    var popup='<div style="min-width:180px;">'+
        '<div style="font-weight:600;margin-bottom:4px;font-size:12px;">\uD83D\uDCCD '+lat.toFixed(6)+', '+lng.toFixed(6)+'</div>'+
        adaParselStr+
        (locStr?'<div style="font-size:11px;color:#475569;margin-bottom:6px;">'+locStr+(cadde?' / '+cadde:'')+'</div>':'')+
        streetViewLink+
        '<div style="margin-top:6px;display:flex;gap:4px;">'+
        '<button onclick="openBasvuruFromClick(\'kazi_ruhsat\')" style="flex:1;background:#E87722;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83D\uDCCB Kaz\u0131 Ruhsat\u0131</button>'+
        '<button onclick="openBasvuruFromClick(\'ortak_kazi\')" style="flex:1;background:#059669;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83E\uDD1D Ortak Kaz\u0131</button>'+
        '</div>'+
    '</div>';
    L.popup().setLatLng([lat,lng]).setContent(popup).openOn(mapsMap);
}

w.openBasvuruFromClick=function(tip){
    mapsMap.closePopup();
    var t=window._sonTiklama||{lat:0,lng:0,ilce:'',mahalle:'',cadde:'',ada:'',parsel:'',displayName:''};

    document.getElementById('bs-lat').value=t.lat;
    document.getElementById('bs-lng').value=t.lng;
    document.getElementById('bs-ilce').value=t.ilce;
    document.getElementById('bs-mahalle').value=t.mahalle;
    document.getElementById('bs-cadde').value=t.cadde;

    var tipEls=document.querySelectorAll('.tip-option');
    tipEls.forEach(function(el){el.classList.remove('selected')});
    tipEls.forEach(function(el){
        var inp=el.querySelector('input');
        if(inp&&inp.value===tip){el.classList.add('selected');inp.checked=!0}
    });
    document.getElementById('ortak-kurum-section').style.display=(tip==='ortak_kazi'?'block':'none');

    var adresParcalari=[];
    if(t.ilce) adresParcalari.push(t.ilce);
    if(t.mahalle) adresParcalari.push(t.mahalle+' Mahallesi');
    if(t.cadde) adresParcalari.push(t.cadde);
    document.getElementById('basvuru-adres-ozet').innerHTML='\uD83D\uDCCD '+(adresParcalari.length?adresParcalari.join(', '):'Adres bilgisi al\u0131namad\u0131');
    document.getElementById('basvuru-coord-display').textContent=t.lat.toFixed(6)+'\u00B0 K, '+t.lng.toFixed(6)+'\u00B0 D';

    openPanel();
};

function openPanel(){
    var panel=document.getElementById('maps-basvuru-panel');
    var mapCanvas=document.getElementById('map-canvas');
    if(mapCanvas){
        var rect=mapCanvas.getBoundingClientRect();
        panel.style.left=rect.left+(rect.width/2-260)+'px';
        panel.style.top=rect.top+40+'px';
    }
    panel.classList.add('open');
    document.getElementById('maps-overlay').classList.add('active');
}

function closeBasvuruPanel(){
    document.getElementById('maps-overlay').classList.remove('active');
    document.getElementById('maps-basvuru-panel').classList.remove('open');
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}currentDrawLayer=null}
    document.getElementById('draw-info').style.display='none';
}
w.closeBasvuruPanel=closeBasvuruPanel;

w.selectTip=function(tip,el){
    document.querySelectorAll('.tip-option').forEach(function(o){o.classList.remove('selected')});
    el.classList.add('selected');
    document.getElementById('ortak-kurum-section').style.display=(tip==='ortak_kazi'?'block':'none');
};

w.toggleOrtakKurum=function(el,name){
    el.classList.toggle('selected');
    var ortakInput=document.getElementById('bs-ortak-kurumlar');
    var vals=ortakInput.value?ortakInput.value.split(','):[];
    if(el.classList.contains('selected')){if(!vals.includes(name))vals.push(name)}
    else vals=vals.filter(function(k){return k!==name});
    ortakInput.value=vals.join(',');
};

var _nominatimController = null;

w.basvuruSubmit=function(){
    var tipEl=document.querySelector('.tip-option.selected input');
    var tip=tipEl?tipEl.value:'kazi_ruhsat';
    var derinlik=document.getElementById('bs-derinlik').value;
    var sure=document.getElementById('bs-sure').value;

    var descParts=[];
    if(derinlik) descParts.push('Derinlik: '+derinlik+'m');
    if(sure) descParts.push('S\u00fcre: '+sure+' g\u00fcn');

    // Seçili cadde/kapı bilgisini dr-selected'dan al
    var drSecili=document.getElementById('dr-selected').value;
    var secilenCaddeler=[], secilenKapilar=[];
    try{var drData=JSON.parse(drSecili);if(drData.caddeler) secilenCaddeler=Object.keys(drData.caddeler);}catch(e){}

    var data={
        basvuru_tipi:tip,
        ortak_kurumlar:document.getElementById('bs-ortak-kurumlar').value,
        lat:parseFloat(document.getElementById('bs-lat').value),
        lng:parseFloat(document.getElementById('bs-lng').value),
        ilce:document.getElementById('bs-ilce').value,
        mahalle:document.getElementById('bs-mahalle').value,
        excavation_reason:document.getElementById('bs-aciklama').value,
        address_text:document.getElementById('basvuru-adres-ozet').textContent.replace('\uD83D\uDCCD ',''),
        description:descParts.join(', '),
        kazi_derinligi:derinlik||null,
        tahmini_sure:sure||null,
        secili_caddeler:secilenCaddeler,
        secili_kapilar:secilenKapilar
    };

    if(!data.lat||!data.lng){showToast('Konum bilgisi eksik');return}
    if(!data.excavation_reason){showToast('Kaz\u0131 a\u00e7\u0131klamas\u0131 gerekli');return}

    fetch('/maps/nokta-kaydet',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
        body:JSON.stringify(data)
    })
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success){
            closeBasvuruPanel();showToast('Ba\u015fvuru kaydedildi!');
            loadBasvuruMarkers();
        }else showToast('Hata: '+(d.message||'Kay\u0131t ba\u015far\u0131s\u0131z'));
    })
    .catch(function(){showToast('Hata olu\u015ftu')});
};

function handleDrawCreated(e){
    _isDrawing=!1;
    _drawJustFinished=!0;
    setTimeout(function(){_drawJustFinished=!1},600);
    currentDrawLayer=null;
    document.getElementById('draw-info').style.display='none';
    document.getElementById('parsel-tablosu-section').style.display='none';
    document.getElementById('basvuru-area-display').style.display='none';

    var layer=e.layer,type=e.layerType,lat,lng,area=null;

    if(type==='marker'){
        var ll=layer.getLatLng();lat=ll.lat;lng=ll.lng
    } else if(type==='polygon'||type==='rectangle'||type==='circle'){
        lat=lng=null;
    } else {
        var fc=layer.getCenter?layer.getCenter():layer.getLatLngs()[0];lat=fc.lat;lng=fc.lng
    }

    if(drawnItems)drawnItems.addLayer(layer);

    var bounds;
    if(type==='polygon'||type==='rectangle'){
        bounds=layer.getBounds();
        lat=bounds.getCenter().lat;lng=bounds.getCenter().lng;
        try{area=L.GeometryUtil.geodesicArea(layer.getLatLngs()[0])}catch(ae){area=null}
    } else if(type==='circle'){
        var cl=layer.getLatLng();lat=cl.lat;lng=cl.lng;
        bounds=layer.getBounds();
        area=Math.PI*layer.getRadius()*layer.getRadius();
    }

    if(area&&area>0){
        var areaText=area>=1e4?(area/1e4).toFixed(2)+' da':area.toFixed(1)+' m²';
        document.getElementById('basvuru-area-display').textContent='📐 Alan: '+areaText;
        document.getElementById('basvuru-area-display').style.display='block';
    }

    document.getElementById('bs-lat').value=lat;
    document.getElementById('bs-lng').value=lng;

    // sadece koordinatları kaydet, draw report açar
    if(bounds){
        var latlngs=layer.getLatLngs();
        if(type==='polygon'||type==='rectangle') latlngs=layer.getLatLngs()[0];
        else if(type==='circle') latlngs=[layer.getLatLng()];
        else if(type==='polyline'||type==='line') latlngs=layer.getLatLngs();
        if(latlngs&&latlngs.length) afterDrawCheck(type,latlngs);
    }
}

function getBbox(lng,lat,m){
    var pt=L.CRS.EPSG3857.project(L.latLng(lat,lng));
    return (pt.x-m)+','+(pt.y-m)+','+(pt.x+m)+','+(pt.y+m);
}

function loadBasvuruMarkers(){
    if(!mapsMap)return;
    if(basvuruLayer){mapsMap.removeLayer(basvuruLayer);basvuruLayer=null}
    fetch('/maps/basvurular/geojson')
    .then(function(r){return r.json()})
    .then(function(data){
        if(!data.features||!data.features.length)return;
        var activeFilters=[];
        document.querySelectorAll('.filter-status:checked').forEach(function(cb){activeFilters.push(cb.value)});

        basvuruLayer=L.geoJSON(data,{
            pointToLayer:function(feature,latlng){
                var status=feature.properties.durum||'submitted';
                return L.marker(latlng,{icon:createStatusIcon(status)});
            },
            filter:function(feature){
                return activeFilters.includes(feature.properties.durum||'submitted');
            },
            onEachFeature:function(feature,layer){
                var p=feature.properties,st=p.durum||'submitted';
                var si=statusIcons[st]||statusIcons.submitted;
                var content='<div style="min-width:160px;font-size:12px;">'+
                    '<div style="font-weight:600;margin-bottom:4px;">'+(p.application_no||'AYK Nokta #'+p.id)+'</div>'+
                    (p.kurum_adi?'<div style="color:#64748b;font-size:11px;">'+p.kurum_adi+'</div>':'')+
                    '<span style="display:inline-block;background:'+si.bg+';color:white;padding:1px 6px;border-radius:3px;font-size:10px;margin:4px 0;">'+si.label+'</span>'+
                    (p.tarih?'<div style="color:#94a3b8;font-size:10px;">'+p.tarih+'</div>':'')+
                    (p.id?'<div style="margin-top:4px;"><a href="/admin/applications/'+p.id+'" target="_blank" style="color:#E87722;font-size:11px;">Detay \u2192</a></div>':'')+
                '</div>';
                layer.bindPopup(content);
            }
        }).addTo(mapsMap);
    })
    .catch(function(){});
}

function showToast(msg, duration){
    var t=document.getElementById('maps-toast');
    t.textContent=msg;t.classList.add('show');
    clearTimeout(t._hideTimer);
    t._hideTimer=setTimeout(function(){t.classList.remove('show')},duration||4000);
}

w.startDrawMarker=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.Marker(mapsMap,{icon:L.divIcon({className:'',html:'<div style="width:24px;height:24px;background:#E87722;border:3px solid #fff;border-radius:50%;"></div>',iconSize:[24,24],iconAnchor:[12,12]})});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='Tıkla ve işaretle.';
    document.getElementById('draw-info').style.display='block';
};

w.startDrawLine=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.Polygon(mapsMap,{allowIntersection:!0,shapeOptions:{color:'#E87722',weight:3}});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='İlk noktaya tıklayarak bitir.';
    document.getElementById('draw-info').style.display='block';
};

w.startDrawPolygon=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.Polygon(mapsMap,{allowIntersection:!0,shapeOptions:{color:'#E87722',weight:2,fillOpacity:0.15}});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='İlk noktaya tıklayarak bitir.';
    document.getElementById('draw-info').style.display='block';
};

w.startDrawRectangle=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.Rectangle(mapsMap,{shapeOptions:{color:'#E87722',weight:2,fillOpacity:0.15}});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='Sürükleyerek çiz, bırakarak bitir.';
    document.getElementById('draw-info').style.display='block';
};

w.startDrawCircle=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.Circle(mapsMap,{shapeOptions:{color:'#E87722',weight:2,fillOpacity:0.15}});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='Sürükleyerek çiz, bırakarak bitir.';
    document.getElementById('draw-info').style.display='block';
};

w.startDrawCircleMarker=function(){
    if(!mapsMap)return;
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}}
    currentDrawLayer=new L.Draw.CircleMarker(mapsMap,{radius:8,color:'#E87722',fillColor:'#E87722',fillOpacity:0.4});
    currentDrawLayer.enable();_isDrawing=!0;
    document.getElementById('draw-info').textContent='Tıkla ve işaretle.';
    document.getElementById('draw-info').style.display='block';
};

w.clearDrawing=function(){
    if(drawnItems)drawnItems.clearLayers();
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}currentDrawLayer=null}
    _isDrawing=!1;_drawJustFinished=!1;
    document.getElementById('draw-info').style.display='none';
};

function toggleAccordion(el){
    var body=el.nextElementSibling;
    el.classList.toggle('collapsed');
    body.classList.toggle('hidden');
}

w.toggleAccordion=toggleAccordion;

function setupEventListeners(){
    document.querySelectorAll('.katman-checkbox').forEach(function(cb){
        cb.addEventListener('change',function(){
            var layer=wmsLayers[this.dataset.layer];
            if(!layer)return;
            if(this.checked)layer.addTo(mapsMap);
            else mapsMap.removeLayer(layer);
            updateActiveLayerCount();
        });
    });

    document.querySelectorAll('.layer-opacity').forEach(function(sl){
        sl.addEventListener('input',function(){
            var row=this.closest('.layer-row');
            var cb=row.querySelector('input[type=checkbox]');
            var layer=wmsLayers[cb.dataset.layer];
            if(layer)layer.setOpacity(parseFloat(this.value));
        });
    });

    document.querySelectorAll('input[name=basemap]').forEach(function(rb){
        rb.addEventListener('change',function(){
            ['google','osm','topos'].forEach(function(k){
                if(mapsMap.hasLayer(basemapLayers[k]))mapsMap.removeLayer(basemapLayers[k]);
            });
            if(basemapLayers[this.value])basemapLayers[this.value].addTo(mapsMap);
        });
    });

    document.querySelectorAll('.filter-status').forEach(function(cb){
        cb.addEventListener('change',loadBasvuruMarkers);
    });

    document.getElementById('btn-yeni-basvuru').addEventListener('click',function(){
        document.getElementById('bs-lat').value='';
        document.getElementById('bs-lng').value='';
        document.getElementById('bs-ilce').value='';
        document.getElementById('bs-mahalle').value='';
        document.getElementById('bs-cadde').value='';
        document.getElementById('bs-aciklama').value='';
        document.getElementById('bs-derinlik').value='';
        document.getElementById('bs-sure').value='';
        document.getElementById('ortak-kurum-section').style.display='none';
        document.getElementById('basvuru-adres-ozet').innerHTML='<span style="color:#94a3b8;">Haritadan bir konum se\u00e7in</span>';
        document.getElementById('basvuru-coord-display').textContent='';
        document.querySelectorAll('.ortak-kurum-item').forEach(function(el){el.classList.remove('selected')});
        document.getElementById('bs-ortak-kurumlar').value='';
        document.querySelectorAll('.tip-option').forEach(function(el){el.classList.remove('selected')});
        document.querySelector('.tip-option').classList.add('selected');
        document.querySelector('.tip-option input').checked=true;
        openPanel();
    });

    document.getElementById('btn-konum').addEventListener('click',function(){
        if(!navigator.geolocation){showToast('Konum alınamıyor');return}
        navigator.geolocation.getCurrentPosition(function(p){
            var lat=p.coords.latitude,lng=p.coords.longitude;
            mapsMap.setView([lat,lng],17);
            if(window._konumMarker)mapsMap.removeLayer(window._konumMarker);
            window._konumMarker=L.marker([lat,lng],{
                icon:L.divIcon({
                    className:'',
                    html:'<div style="background:#3b82f6;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 0 4px rgba(59,130,246,0.4);animation:pulse 1.5s infinite"></div>',
                    iconSize:[16,16],iconAnchor:[8,8]
                })
            }).addTo(mapsMap).bindPopup('<b>📍 Konumum</b><br>'+lat.toFixed(6)+', '+lng.toFixed(6));
        },function(){showToast('Konum alınamadı')});
    });

    document.getElementById('btn-fullscreen').addEventListener('click',function(){
        if(!document.fullscreenElement){
            document.documentElement.requestFullscreen().then(function(){
                document.body.classList.add('maps-fullscreen');
                setTimeout(function(){mapsMap.invalidateSize()},300);
            }).catch(function(){});
        } else {
            document.exitFullscreen().then(function(){
                document.body.classList.remove('maps-fullscreen');
                setTimeout(function(){mapsMap.invalidateSize()},300);
            }).catch(function(){});
        }
    });
    document.addEventListener('fullscreenchange',function(){
        if(!document.fullscreenElement){
            document.body.classList.remove('maps-fullscreen');
        } else {
            document.body.classList.add('maps-fullscreen');
        }
        setTimeout(function(){mapsMap.invalidateSize()},400);
    });

    document.getElementById('btn-sorgula').addEventListener('click',function(){
        var no=prompt('Başvuru No girin (örn: AYK-2026-)');
        if(!no)return;
        fetch('/maps/basvuru-sorgula?q='+encodeURIComponent(no))
        .then(function(r){return r.json()})
        .then(function(d){
            if(d.data&&d.data.length){
                var b=d.data[0];
                if(b.lat&&b.lng)mapsMap.setView([b.lat,b.lng],17);
                showToast('Bulunan: '+b.application_no);
            }else showToast('Sonuç bulunamadı');
        })
        .catch(function(){showToast('Sorgu başarısız')});
    });

    document.addEventListener('keydown',function(e){
        if(e.key==='Escape'){
            if(document.getElementById('maps-basvuru-panel').classList.contains('open')){closeBasvuruPanel();return}
            if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}currentDrawLayer=null;_isDrawing=!1;document.getElementById('draw-info').style.display='none'}
        }
        if(e.key==='F11'){
            e.preventDefault();
            document.getElementById('btn-fullscreen').click();
        }
    });

    document.getElementById('maps-overlay').addEventListener('click',closeBasvuruPanel);

    document.getElementById('maps-toggle-mobile').addEventListener('click',function(){
        document.getElementById('maps-left-panel').classList.toggle('mobile-open');
    });
}

function initSearchControl(){
    var searchTimeout;
    document.getElementById('maps-search-input').addEventListener('input',function(){
        clearTimeout(searchTimeout);
        var q=this.value.trim();
        if(q.length<3){
            document.getElementById('maps-search-results').style.display='none';
            return;
        }
        searchTimeout=setTimeout(function(){
            var url='https://nominatim.openstreetmap.org/search'
                +'?format=json&q='+encodeURIComponent(q+' \u015Eanl\u0131urfa')
                +'&limit=6&addressdetails=1&accept-language=tr'
                +'&viewbox=37.5,37.8,39.5,36.5&bounded=1';
            fetch(url).then(function(r){return r.json()}).then(function(results){
                var el=document.getElementById('maps-search-results');
                if(!results.length){el.style.display='none';return}
                el.innerHTML=results.map(function(r){
                    var parts=r.display_name.split(', ');
                    var main=parts[0];
                    var sub=parts.slice(1,4).join(', ');
                    return '<div class="search-result-item" data-lat="'+r.lat+'" data-lon="'+r.lon+'">'
                        +'<span class="result-icon">\uD83D\uDCCD</span>'
                        +'<div><div class="result-main">'+main+'</div>'
                        +'<div class="result-sub">'+sub+'</div></div>'
                        +'</div>';
                }).join('');
                el.style.display='block';
                el.querySelectorAll('.search-result-item').forEach(function(item){
                    item.addEventListener('click',function(){
                        var lat=parseFloat(this.dataset.lat);
                        var lon=parseFloat(this.dataset.lon);
                        mapsMap.flyTo([lat,lon],17,{animate:true,duration:1.5,easeLinearity:0.25});
                        if(window._searchMarker)mapsMap.removeLayer(window._searchMarker);
                        window._searchMarker=L.marker([lat,lon],{
                            icon:L.divIcon({
                                className:'',
                                html:'<div style="background:#E87722;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 0 8px rgba(232,119,34,0.8);animation:pulse 1.5s infinite"></div>',
                                iconSize:[14,14],iconAnchor:[7,7]
                            })
                        }).addTo(mapsMap);
                        el.style.display='none';
                        document.getElementById('maps-search-input').value=this.querySelector('.result-main').textContent;
                    });
                });
            }).catch(function(){
                document.getElementById('maps-search-results').style.display='none';
            });
        },400);
    });
    document.addEventListener('click',function(e){
        if(!e.target.closest('#maps-search-control')){
            document.getElementById('maps-search-results').style.display='none';
        }
    });
}

document.addEventListener('DOMContentLoaded',initMaps);
w.addEventListener('resize',function(){
    var sidebar=document.getElementById('admin-sidebar');
    if(sidebar&&!document.body.classList.contains('maps-fullscreen')){
        document.getElementById('maps-wrapper').style.left=sidebar.offsetWidth+'px';
    }
});

var _hatKimligiActive=!1;

w.toggleHatKimligi=function(){
    _hatKimligiActive=!_hatKimligiActive;
    var btn=document.getElementById('btn-hat-kimligi');
    if(_hatKimligiActive){
        if(mapsMap) mapsMap.getContainer().style.cursor='crosshair';
        btn.style.background='#E87722';
        btn.style.color='#fff';
        btn.style.borderColor='#E87722';
        showToast('🔍 Hat Kimliği aktif. Bir yola tıklayın.');
    } else {
        if(mapsMap) mapsMap.getContainer().style.cursor='';
        btn.style.background='#334155';
        btn.style.color='#cbd5e1';
        btn.style.borderColor='#475569';
        kapatHatKimligiPanel();
    }
};

w.showHatKimligiPopup=function(latlng, props){
    var hatNo=props.CADDE_SOKA||'—';
    var adi=(props.CADDE_SO_1||'')+' '+(props.CADDE_SO_2||'');
    var mahalle=props.MAHALLE_AD||'';
    var ilce=props.ILÇE||'';
    var genislik=props.GENISLIGI||'';
    var uzunluk=props.UZUNLUGU||'';
    var sorumluluk=props.SORUMLULUK||'';

    // Save for later use
    document.getElementById('hk-last-props').value=JSON.stringify(props);
    document.getElementById('hk-last-lat').value=latlng.lat;
    document.getElementById('hk-last-lng').value=latlng.lng;

    var content=
        '<div style="min-width:250px;font-size:12px;">'+
        '<div style="font-weight:700;font-size:14px;margin-bottom:6px;color:#1e293b;">🛣️ HAT KİMLİĞİ: #'+hatNo+'</div>'+
        '<hr style="margin:6px 0;border-color:#e2e8f0;">'+
        '<table style="width:100%;font-size:12px;border-collapse:collapse;">'+
        '<tr><td style="color:#64748b;padding:2px 4px;">Cadde/Sokak:</td><td style="padding:2px 4px;font-weight:500;">'+adi+'</td></tr>'+
        '<tr><td style="color:#64748b;padding:2px 4px;">Mahalle:</td><td style="padding:2px 4px;font-weight:500;">'+mahalle+'</td></tr>'+
        '<tr><td style="color:#64748b;padding:2px 4px;">İlçe:</td><td style="padding:2px 4px;font-weight:500;">'+ilce+'</td></tr>'+
        '<tr><td style="color:#64748b;padding:2px 4px;">Genişlik:</td><td style="padding:2px 4px;font-weight:500;">'+genislik+' m</td></tr>'+
        '<tr><td style="color:#64748b;padding:2px 4px;">Uzunluk:</td><td style="padding:2px 4px;font-weight:500;">'+uzunluk+' m</td></tr>'+
        '<tr><td style="color:#64748b;padding:2px 4px;">Yetki:</td><td style="padding:2px 4px;font-weight:500;">'+sorumluluk+'</td></tr>'+
        '</table>'+
        '<hr style="margin:6px 0;border-color:#e2e8f0;">'+
        '<div style="display:flex;gap:6px;">'+
        '<button onclick="showHatKimligiDetail()" style="flex:1;background:#E87722;color:white;border:none;padding:6px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-weight:500;">🔍 Tümünü Göster</button>'+
        '<button onclick="hatKimligiBasvuruAc()" style="flex:1;background:#059669;color:white;border:none;padding:6px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-weight:500;">📋 Başvuru Yap</button>'+
        '</div>'+
        '</div>';
    L.popup({maxWidth:320,className:'hatkimligi-popup'}).setLatLng(latlng).setContent(content).openOn(mapsMap);
};

w.showHatKimligiDetail=function(){
    var props=document.getElementById('hk-last-props').value;
    if(!props) return showToast('⚠️ Önce bir yol seçin');
    try{props=JSON.parse(props)}catch(e){return}
    if(mapsMap) mapsMap.closePopup();

    var hatNo=props.CADDE_SOKA||'—';
    var rows=[
        ['Hat Kimliği', '#'+hatNo],
        ['Cadde/Sokak Adı', props.CADDE_SO_1||'—'],
        ['Tür', props.CADDE_SO_2||'—'],
        ['İlçe', props.ILÇE||'—'],
        ['Mahalle', props.MAHALLE_AD||'—'],
        ['Yetki (Sorumluluk)', props.SORUMLULUK||'—'],
        ['Ana Arter', props.ANA__ARTER||'—'],
        ['Kaplama Türü', props.KAPLAMA_CI||'—'],
        ['Eski Cadde Adı', props.ESKI_CADDE||'—'],
        ['Şerit Sayısı', props.SERIT_SAYI||'0'],
        ['Yaya Geçidi', props.YAYA_GEÇI||'0'],
        ['Genişlik', (props.GENISLIGI||'0')+' m'],
        ['Uzunluk', (props.UZUNLUGU||'0')+' m'],
        ['Eğim', props.EGIMI||'0'],
        ['Hız Limiti', (props.HIZ_LIMITI||'0')+' km/s'],
        ['Kaldırım Türü', props.KALDIRIM_T||'—'],
        ['Trafik Yönü', props.TRAFIK_YÖ||'—'],
        ['UAVT Yol Türü', props.UAVT_YOL_T||'—'],
        ['Kayıt Tarihi', props.KAYIT_TARI||'—'],
    ];

    var html='<table class="table table-condensed" style="width:100%;font-size:12px;border-collapse:collapse;">';
    rows.forEach(function(r,i){
        var bg=i%2===0?'#f8fafc':'#fff';
        html+='<tr style="background:'+bg+';border-bottom:1px solid #f1f5f9;">'+
            '<td style="padding:5px 8px;color:#64748b;white-space:nowrap;">'+r[0]+'</td>'+
            '<td style="padding:5px 8px;font-weight:500;color:#1e293b;">'+r[1]+'</td></tr>';
    });
    html+='</table>';

    document.getElementById('hat-kimligi-panel-body').innerHTML=html;
    document.getElementById('hat-kimligi-panel').style.display='block';
};

w.kapatHatKimligiPanel=function(){
    document.getElementById('hat-kimligi-panel').style.display='none';
};

w.hatKimligiBasvuruAc=function(){
    if(mapsMap) mapsMap.closePopup();
    var props=document.getElementById('hk-last-props').value;
    if(props) try{props=JSON.parse(props)}catch(e){}
    var lat=document.getElementById('hk-last-lat').value;
    var lng=document.getElementById('hk-last-lng').value;

    document.getElementById('bs-lat').value=lat;
    document.getElementById('bs-lng').value=lng;
    document.getElementById('bs-ilce').value=props?.ILÇE||'';
    document.getElementById('bs-mahalle').value=props?.MAHALLE_AD||'';
    document.getElementById('bs-cadde').value=props?.CADDE_SO_1||'';

    document.querySelectorAll('.tip-option').forEach(function(el){el.classList.remove('selected')});
    document.querySelector('.tip-option').classList.add('selected');
    document.querySelector('.tip-option input').checked=true;
    document.getElementById('ortak-kurum-section').style.display='none';

    var adres=[props?.ILÇE, props?.MAHALLE_AD+' Mah.', props?.CADDE_SO_1+' '+props?.CADDE_SO_2].filter(Boolean).join(', ');
    document.getElementById('basvuru-adres-ozet').innerHTML='🛣️ Hat #'+(props?.CADDE_SOKA||'')+' | '+(adres||'Adres bilgisi alınamadı');
    document.getElementById('basvuru-coord-display').textContent=lat+', '+lng;

    openPanel();
};

// 15m yol analizi toggle
document.addEventListener('DOMContentLoaded',function(){
    var elAlti=document.getElementById('road-15-alti');
    var elUstu=document.getElementById('road-15-ustu');
    if(elAlti) elAlti.addEventListener('change',function(){
        if(this.checked) loadRoadLayer('alti');
        else removeRoadLayer('alti');
    });
    if(elUstu) elUstu.addEventListener('change',function(){
        if(this.checked) loadRoadLayer('ustu');
        else removeRoadLayer('ustu');
    });
});

function loadRoadLayer(tip){
    if(!mapsMap) return;
    var url=tip==='alti'?'/maps/15m/alti':'/maps/15m/ustu';
    var color=tip==='alti'?'#22c55e':'#ef4444';
    var label=tip==='alti'?'15m Altı':'15m Üstü';
    var existing=tip==='alti'?roadLayerAlti:roadLayerUstu;
    if(existing) { mapsMap.addLayer(existing); return; }
    showToast('🔄 '+label+' yollar yükleniyor...');
    fetch(url).then(function(r){return r.json()}).then(function(data){
        if(!data.features||!data.features.length){showToast('⚠️ Yol verisi bulunamadı');return;}
        var layer=L.geoJSON(data,{
            style:{color:color,weight:4,opacity:0.6},
            onEachFeature:function(feature,layer){
                var p=feature.properties||{};
                layer.on('click',function(e){
                    if(_hatKimligiActive){
                        showHatKimligiPopup(e.latlng,p);
                        L.DomEvent.stopPropagation(e);
                    } else {
                        layer.bindPopup(
                            '<div style="min-width:180px;font-size:12px;">'+
                            '<div style="font-weight:600;margin-bottom:4px;">🛣️ '+(p.CADDE_SO_1||'')+' '+(p.CADDE_SO_2||'')+'</div>'+
                            '<table style="font-size:11px;width:100%;border-collapse:collapse;">'+
                            '<tr><td style="color:#64748b;padding:1px 4px;">Hat Kimliği:</td><td style="padding:1px 4px;"><strong>#'+(p.CADDE_SOKA||'')+'</strong></td></tr>'+
                            '<tr><td style="color:#64748b;padding:1px 4px;">Mahalle:</td><td style="padding:1px 4px;">'+(p.MAHALLE_AD||'')+'</td></tr>'+
                            '<tr><td style="color:#64748b;padding:1px 4px;">Genişlik:</td><td style="padding:1px 4px;">'+(p.GENISLIGI||'')+' m</td></tr>'+
                            '<tr><td style="color:#64748b;padding:1px 4px;">Uzunluk:</td><td style="padding:1px 4px;">'+(p.UZUNLUGU||'')+' m</td></tr>'+
                            '<tr><td style="color:#64748b;padding:1px 4px;">Yetki:</td><td style="padding:1px 4px;">'+(p.SORUMLULUK||'')+'</td></tr>'+
                            '</table>'+
                            '</div>'
                        ).openPopup(e.latlng);
                    }
                });
            }
        });
        if(tip==='alti') roadLayerAlti=layer; else roadLayerUstu=layer;
        layer.addTo(mapsMap);
        showToast('✅ '+label+' yollar yüklendi ('+data.features.length+' adet)');
    }).catch(function(){showToast('⚠️ Yol verisi yüklenemedi');});
}

function removeRoadLayer(tip){
    if(!mapsMap) return;
    var layer=tip==='alti'?roadLayerAlti:roadLayerUstu;
    if(layer&&mapsMap.hasLayer(layer)) mapsMap.removeLayer(layer);
}

window._parselList=[];

function parselAra(){
    var ada=document.getElementById('parsel-search-ada').value.trim();
    var parsel=document.getElementById('parsel-search-parsel').value.trim();
    var pafta=document.getElementById('parsel-search-pafta').value.trim();
    if(!ada&&!parsel){showToast('⚠️ En az Ada veya Parsel no girin');return}
    showToast('🔍 Parsel sorgulanıyor...');
    var cqlParts=[];
    if(ada) cqlParts.push('ADA='+ada);
    if(parsel) cqlParts.push('PARSEL='+parsel);
    if(pafta) cqlParts.push('PAFTA='+pafta);
    var params='&typeNames=smpns:MISMAP_NUM_KADASTRO_PARSEL';
    if(cqlParts.length) params+='&cql_filter='+encodeURIComponent(cqlParts.join(' AND '));
    params+='&outputFormat=application/json&srsName=EPSG:4326&count=50';
    fetch('/maps/proxy?url='+encodeURIComponent('https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature'+params))
    .then(function(r){if(!r.ok){hideLoadingOverlay();showToast('Parsel servisi yanıt vermiyor');return null}return r.json()})
    .then(function(data){
        if(!data||!data.features||!data.features.length){hideLoadingOverlay();showToast('Parsel bulunamadı');return}
        gosterParselListesi(data.features);
        if(data.features.length===1) haritadaParselGoster(data.features[0]);
        else haritadaParselListesiGoster(data.features);
        showToast('✅ '+data.features.length+' parsel bulundu');
    })
    .catch(function(){showToast('⚠️ Parsel sorgusu başarısız')});
}

function gosterParselListesi(features){
    var container=document.getElementById('parsel-listesi-icerik');
    if(!container) return;
    window._parselList=features;
    var html='<div style="font-size:11px;color:#94a3b8;margin-bottom:4px;">'+features.length+' parsel bulundu. Seçmek için tıklayın:</div>';
    html+='<table style="width:100%;font-size:11px;border-collapse:collapse;">';
    html+='<tr style="color:#64748b;border-bottom:1px solid #475569;"><th style="padding:3px 4px;text-align:left;">Parsel</th><th style="padding:3px 4px;text-align:left;">Ada</th><th style="padding:3px 4px;text-align:left;">Mahalle</th><th style="padding:3px 4px;text-align:left;">İlçe</th></tr>';
    features.forEach(function(f,i){
        var p=f.properties||{};
        html+='<tr style="border-bottom:1px solid #334155;cursor:pointer;" onclick="haritadaParselGoster(window._parselList['+i+'])" onmouseover="this.style.background=\'#334155\'" onmouseout="this.style.background=\'\'">'+
            '<td style="padding:3px 4px;">'+(p.PARSEL||'')+'</td>'+
            '<td style="padding:3px 4px;">'+(p.ADA||'')+'</td>'+
            '<td style="padding:3px 4px;">'+(p.MAHALLE_AD||p.MAHALLE||'')+'</td>'+
            '<td style="padding:3px 4px;">'+(p.ILCE||p.ILÇE||'')+'</td></tr>';
    });
    html+='</table>';
    html+='<div style="margin-top:6px;font-size:10px;color:#64748b;">';
    if(window._parselList.length===1) html+='<button class="draw-btn" style="width:100%;justify-content:center;padding:4px 8px;font-size:11px;" onclick="seciliParselleBasvuruYap(0)">📋 Bu Parselle Başvuru Yap</button>';
    else html+='<button class="draw-btn" style="width:100%;justify-content:center;padding:4px 8px;font-size:11px;" onclick="birdenFazlaParsecSec()">📋 Parsel Seçerek Başvuru Yap</button>';
    html+='</div>';
    container.innerHTML=html;
}

function birdenFazlaParsecSec(){
    var container=document.getElementById('parsel-listesi-icerik');
    var html='<div style="font-size:11px;color:#94a3b8;margin-bottom:4px;">Seçmek istediğiniz parseli tıklayın:</div>';
    html+='<div style="max-height:200px;overflow-y:auto;">';
    window._parselList.forEach(function(f,i){
        var p=f.properties||{};
        var adi=(p.PARSEL||'')+'/'+(p.ADA||'')+' - '+(p.MAHALLE_AD||p.MAHALLE||'');
        html+='<label style="display:flex;align-items:center;gap:6px;padding:4px 6px;cursor:pointer;border-bottom:1px solid #334155;font-size:11px;">'+
            '<input type="radio" name="parcel_sec" value="'+i+'" onchange="haritadaParselGoster(window._parselList['+i+'])">'+
            '<span>'+adi+'</span></label>';
    });
    html+='</div>';
    html+='<button class="draw-btn" style="width:100%;justify-content:center;padding:4px 8px;font-size:11px;margin-top:6px;" onclick="seciliParselleBasvuruYapSec()">📋 Başvuru Yap</button>';
    container.innerHTML=html;
}

function seciliParselleBasvuruYap(index){
    var f=window._parselList[index];
    if(!f) return;
    var p=f.properties||{};
    var lat=f.geometry&&f.geometry.coordinates?f.geometry.coordinates[1]:null;
    var lng=f.geometry&&f.geometry.coordinates?f.geometry.coordinates[0]:null;
    if(lat&&lng) mapsMap.flyTo([lat,lng],18,{animate:true,duration:1});
    setTimeout(function(){openBasvuruFromParsel(p,lat,lng)},600);
}

function seciliParselleBasvuruYapSec(){
    var sec=document.querySelector('input[name="parcel_sec"]:checked');
    if(!sec){showToast('⚠️ Lütfen bir parsel seçin');return}
    seciliParselleBasvuruYap(parseInt(sec.value));
}

function openBasvuruFromParsel(p,lat,lng){
    document.getElementById('bs-lat').value=lat;
    document.getElementById('bs-lng').value=lng;
    var adres='<div style="font-size:12px;line-height:1.7;">';
    adres+='<b>📍 Koordinat:</b> '+lat.toFixed(6)+', '+lng.toFixed(6)+'<br>';
    adres+='<b>🏛️ Ada/Parsel:</b> '+(p.ADA||'')+'/'+(p.PARSEL||'')+'<br>';
    if(p.PAFTA) adres+='<b>📋 Pafta:</b> '+p.PAFTA+'<br>';
    adres+='<b>🏘️ Mahalle:</b> '+(p.MAHALLE_AD||p.MAHALLE||'')+'<br>';
    adres+='<b>🏛️ İlçe:</b> '+(p.ILCE||p.ILÇE||'')+'<br>';
    if(p.CADDE_SOKAK||p.CADDE) adres+='<b>🛣️ Cadde/Sokak:</b> '+(p.CADDE_SOKAK||p.CADDE||'')+'<br>';
    adres+='</div>';
    document.getElementById('basvuru-adres-ozet').innerHTML=adres;
    document.getElementById('basvuru-coord-display').textContent=lat.toFixed(6)+', '+lng.toFixed(6);
    document.getElementById('maps-basvuru-panel').classList.add('open');
    document.getElementById('maps-overlay').style.display='block';
}

function haritadaParselGoster(feature){
    var layer=L.geoJSON(feature,{
        style:{color:'#E87722',weight:3,fillColor:'rgba(232,119,34,0.15)',fillOpacity:0.5},
        onEachFeature:function(f,l){
            mapsMap.fitBounds(l.getBounds(),{padding:[50,50],animate:true,duration:0.8});
            var p=f.properties||{};
            var html='<div style="font-size:12px;min-width:220px;">';
            html+='<b>📍 Ada/Parsel:</b> '+(p.ADA||'')+'/'+(p.PARSEL||'')+'<br>';
            html+='<b>🏘️ Mahalle:</b> '+(p.MAHALLE_AD||p.MAHALLE||'')+'<br>';
            html+='<b>🏛️ İlçe:</b> '+(p.ILCE||p.ILÇE||'')+'<br>';
            if(p.NİTELİK) html+='<b>📋 Nitelik:</b> '+p.NİTELİK+'<br>';
            if(p.YÜZÖLÇÜM) html+='<b>📐 Yüzölçüm:</b> '+p.YÜZÖLÇÜM+' m²<br>';
            if(p.M2) html+='<b>📐 Alan:</b> '+p.M2+' m²';
            html+='</div>';
            l.bindPopup(html).openPopup();
        }
    });
    if(window._parcelHighlight) mapsMap.removeLayer(window._parcelHighlight);
    window._parcelHighlight=layer;
    layer.addTo(mapsMap);
}

function haritadaParselListesiGoster(features){
    var layer=L.geoJSON(features,{
        style:{color:'#22c55e',weight:2,fillColor:'rgba(34,197,94,0.1)',fillOpacity:0.3}
    });
    if(window._parcelHighlight) mapsMap.removeLayer(window._parcelHighlight);
    window._parcelHighlight=layer;
    layer.addTo(mapsMap);
}

// Çizim mesafe hesaplama & parsel sorgulama
var _drawMeasurements={};

function showDrawMeasurement(type,latlngs){
    var el=document.getElementById('draw-measurement');
    if(!el) return;
    if(type==='marker'||type==='circlemarker'){el.style.display='none';return}
    var text='';
    if(type==='polyline'){
        if(!latlngs||latlngs.length<2){el.style.display='none';return}
        var total=0;
        for(var i=1;i<latlngs.length;i++) total+=latlngs[i-1].distanceTo(latlngs[i]);
        text='📏 Mesafe: '+total.toFixed(1)+' m';
        if(total>1000) text+=' ('+(total/1000).toFixed(2)+' km)';
    } else {
        if(!latlngs||latlngs.length<3){el.style.display='none';return}
        var perimeter=0;
        for(var i=1;i<latlngs.length;i++) perimeter+=latlngs[i-1].distanceTo(latlngs[i]);
        var centroid=latlngs.reduce(function(a,b){return {lat:a.lat+b.lat/3,lng:a.lng+b.lng/3}},{lat:0,lng:0});
        var area=0;
        for(var i=0;i<latlngs.length;i++){
            var j=(i+1)%latlngs.length;
            area+=latlngs[i].lat*latlngs[j].lng-latlngs[j].lat*latlngs[i].lng;
        }
        area=Math.abs(area)/2*111319*111319*Math.cos(centroid.lat*Math.PI/180);
        text='📐 Çevre: '+perimeter.toFixed(1)+' m | Alan: '+area.toFixed(1)+' m²';
        if(area>10000) text+=' ('+(area/10000).toFixed(2)+' ha)';
        if(area>1000000) text+=' ('+(area/1000000).toFixed(4)+' km²)';
    }
    el.innerHTML=text;
    el.style.display='block';
}

// Çizim bittiğinde parsel + alt yapı sorgula
function afterDrawCheck(drawType,latlngs){
    if(!latlngs||latlngs.length<2) return;
    showDrawMeasurement(drawType,latlngs);
    showLoadingOverlay('Çizim alanı taranıyor...');
    setTimeout(function(){
        sorguCizimDetayRaporu(latlngs);
        sorguCizimAltyapiKesisimi(latlngs);
    },100);
}

function showLoadingOverlay(msg){
    var el=document.getElementById('maps-loading-overlay');
    if(!el) return;
    el.classList.add('active');
    if(msg){
        el.querySelector('.maps-loading-text').textContent=msg;
        el.querySelector('.maps-loading-sub').textContent='Binalar, kapı numaraları ve cadde/sokak bilgileri taranıyor';
    }
}
function hideLoadingOverlay(){
    var el=document.getElementById('maps-loading-overlay');
    if(el) el.classList.remove('active');
}

var _drawReportParsels=[];

function sorguCizimDetayRaporu(latlngs){
    if(!latlngs||latlngs.length<2) return;
    var bounds=L.latLngBounds(latlngs);
    var sw=L.CRS.EPSG3857.project(bounds.getSouthWest());
    var ne=L.CRS.EPSG3857.project(bounds.getNorthEast());
    var bbox=sw.x+','+sw.y+','+ne.x+','+ne.y;
    var body=document.getElementById('draw-report-body');
    var panel=document.getElementById('draw-report-panel');
    body.innerHTML='<div style="text-align:center;color:#94a3b8;padding:30px;">🔍 Çizim alanı taranıyor...<br><span style="font-size:11px;">Parseller, binalar, kapı numaraları sorgulanıyor</span></div>';
    panel.style.display='block';
    document.getElementById('draw-report-footer').style.display='none';

    // AŞAMA 1: WFS ile parsel + bina sorgula (geo4)
    var wfsBase='https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs';
    var typeNames=['smpns:MISMAP_NUM_KADASTRO_PARSEL','smpns:MISMAP_NUM_BINA'];
    var results={};
    var wfsDone=0;
    typeNames.forEach(function(tn){
        var url='/maps/proxy?url='+encodeURIComponent(
            wfsBase+'?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames='+tn+'&bbox='+bbox+',EPSG:3857'+
            '&outputFormat=application/json&srsName=EPSG:4326&count=1000'
        );
        fetch(url).then(function(r){
            if(!r.ok) throw new Error('HTTP '+r.status);
            return r.json();
        }).then(function(data){
            results[tn]=(data&&data.features)||[];
        }).catch(function(){
            results[tn]=[];
        }).then(function(){
            wfsDone++;
            if(wfsDone===typeNames.length) drawReportAsama2(results,bounds);
        });
    });
}

function drawReportAsama2(results,bounds){
    var parsels=results['smpns:MISMAP_NUM_KADASTRO_PARSEL']||[];
    var binas=results['smpns:MISMAP_NUM_BINA']||[];

    // Alan filtrele
    var filteredParsels=parsels.filter(function(f){
        try{var poly=L.geoJSON(f);return bounds.intersects(poly.getBounds())}
        catch(e){return true}
    });
    if(!filteredParsels.length) filteredParsels=parsels;

    _drawReportParsels=filteredParsels;

    if(!filteredParsels.length){
        document.getElementById('draw-report-body').innerHTML='<div style="text-align:center;color:#64748b;padding:30px;">📭 Bu alanda parsel bulunamadı.</div>';
        hideLoadingOverlay();
        return;
    }

    // AŞAMA 2: WMS GetFeatureInfo ile numarataj + cadde + bina bilgisi çek
    var wmsBase='https://geo3.sanliurfa.bel.tr:8091/geoserver/wms';
    var wmsLayers='smpns:m_Numarataj,cbs:MISMAP_CADDE_SOKAK,smpns:MISMAP_NUM_BINA';
    var parselCenters=[];
    filteredParsels.forEach(function(p,idx){
        try{
            var center=L.geoJSON(p).getBounds().getCenter();
            parselCenters.push({lat:center.lat,lng:center.lng,parsel:p,idx:idx});
        }catch(e){}
    });
    var sample=parselCenters.slice(0,50); // 50 parsel sorgula

    if(!sample.length){
        drawReportOlustur(filteredParsels,binas,[]);
        return;
    }

    var wmsSonuclari=[];
    var wmsDone=0;
    var size=mapsMap?mapsMap.getSize():L.point(2000,1000);
    var gfiBounds=mapsMap?mapsMap.getBounds():L.latLngBounds(L.latLng(37.0,38.6),L.latLng(37.4,39.0));

    sample.forEach(function(s){
        var pt=mapsMap?mapsMap.latLngToContainerPoint(L.latLng(s.lat,s.lng)):L.point(1000,500);
        var gfiUrl='/maps/proxy?url='+encodeURIComponent(
            wmsBase+'?SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo'+
            '&LAYERS='+wmsLayers+'&QUERY_LAYERS='+wmsLayers+
            '&BBOX='+gfiBounds.toBBoxString()+'&WIDTH='+Math.round(size.x)+'&HEIGHT='+Math.round(size.y)+
            '&X='+Math.round(pt.x)+'&Y='+Math.round(pt.y)+
            '&INFO_FORMAT=application/json&SRS=EPSG:4326&FEATURE_COUNT=10&BUFFER=20'
        );
        fetch(gfiUrl).then(function(r){
            if(!r.ok) return null;
            return r.json();
        }).then(function(data){
            if(data&&data.features){
                data.features.forEach(function(f){
                    wmsSonuclari.push({
                        parselIdx:s.idx, // hangi parsele ait oldugu
                        layer:f.id?f.id.split('.')[0]:'',
                        props:f.properties||{}
                    });
                });
            }
        }).catch(function(){}).then(function(){
            wmsDone++;
            if(wmsDone===sample.length) drawReportOlustur(filteredParsels,binas,wmsSonuclari);
        });
    });
}

function drawReportOlustur(parsels,binas,wmsSonuclari){
    // WMS sonuçlarını parsel index'ine göre grupla
    var numData={}; // key = ada|parsel
    parsels.forEach(function(p){
        var pr=p.properties||{};
        var key=(pr.ADA||'')+'|'+(pr.PARSEL||'');
        numData[key]={kapilarObj:{}, caddeler:{}, binaAdlari:[]};
    });

    (wmsSonuclari||[]).forEach(function(wr){
        var p=parsels[wr.parselIdx];
        if(!p) return;
        var pr=p.properties||{};
        var key=(pr.ADA||'')+'|'+(pr.PARSEL||'');
        if(!numData[key]) return;
        var fp=wr.props;
        var layer=wr.layer;

        if(layer==='smpns:m_Numarataj'){
            var kapiNo=fp.KAPI_NO||fp.kapi_no||'';
            var binaAdi=fp.BINA_ADI||fp.bina_adi||'';
            var cadde=((fp.CADDE_SO_1||'')+' '+(fp.CADDE_SO_2||'')).trim()||fp.CADDE_ADI||fp.CADDE||'';
            if(!cadde) cadde='_NOCADDE_';
            if(kapiNo){
                if(!numData[key].kapilarObj[cadde]) numData[key].kapilarObj[cadde]=[];
                // Aynı kapı no'yu tekrar ekleme
                var exists=numData[key].kapilarObj[cadde].some(function(k){return k.kapiNo===kapiNo});
                if(!exists) numData[key].kapilarObj[cadde].push({kapiNo:kapiNo,binaAdi:binaAdi||''});
            }
            if(cadde&&cadde!=='_NOCADDE_') numData[key].caddeler[cadde]=cadde;
        }

        if(layer==='cbs:MISMAP_CADDE_SOKAK'){
            var caddeAdi=fp.CADDE_ADI||fp.ADI||fp.AD||((fp.CADDE_SO_1||'')+' '+(fp.CADDE_SO_2||'')).trim()||'';
            if(caddeAdi){
                // Cadde adını formatla
                if(!fp.CADDE_SO_1&&!fp.CADDE_SO_2&&caddeAdi.indexOf(' ')===-1){
                    // Muhtemelen sadece isim, düz ekle
                }
                numData[key].caddeler[caddeAdi]=caddeAdi;
            }
        }

        if(layer==='smpns:MISMAP_NUM_BINA'){
            var bAdi=fp.BINA_ADI||fp.bina_adi||fp.NAME||'';
            if(bAdi&&numData[key].binaAdlari.indexOf(bAdi)===-1){
                numData[key].binaAdlari.push(bAdi);
            }
        }
    });

    // Parselleri grupla + cadde bilgilerini birleştir
    var parselMap={};
    var allCaddeler={};
    parsels.forEach(function(p){
        var pr=p.properties||{};
        var key=(pr.ADA||'')+'|'+(pr.PARSEL||'');
        var cadde=((pr.CADDE_SO_1||'')+' '+(pr.CADDE_SO_2||'')).trim()||pr.CADDE||pr.CADDE_SOKAK||'';
        var mahalle=pr.MAHALLE_AD||pr.MAHALLE||'';
        if(!parselMap[key]) parselMap[key]={parsel:p, binas:0, caddeler:{}, binaAdlari:[]};
        if(cadde){
            var cKey=mahalle+'|'+cadde;
            parselMap[key].caddeler[cKey]=cadde;
            allCaddeler[cKey]=cadde;
        }
        // WMS caddelerini merge et
        if(numData[key]){
            Object.keys(numData[key].caddeler).forEach(function(ck){
                parselMap[key].caddeler[ck]=numData[key].caddeler[ck];
                allCaddeler[ck]=numData[key].caddeler[ck];
            });
            // Bina adlarını merge et
            parselMap[key].binaAdlari=numData[key].binaAdlari;
        }
    });

    // Bina sayısını ata
    binas.forEach(function(b){
        var bp=b.properties||{};
        var key=(bp.ADA||'')+'|'+(bp.PARSEL||'');
        if(parselMap[key]) parselMap[key].binas++;
    });

    // Global'e ata
    window._drawReportParselMap=parselMap;
    window._drawReportNumData=numData;

    // Panel HTML
    var caddeKeys=Object.keys(allCaddeler);
    var html='<div style="margin-bottom:8px;font-size:11px;color:#64748b;">';
    html+=parsels.length+' parsel, '+binas.length+' bina'+(caddeKeys.length?', '+caddeKeys.length+' cadde/sokak':'')+' bulundu.';
    html+='<hr style="border-color:#e2e8f0;margin:6px 0;"></div>';

    var keys=Object.keys(parselMap);
    html+='<div id="dr-parsel-list">';
    keys.forEach(function(key){
        var item=parselMap[key];
        var p=item.parsel.properties||{};
        var ada=p.ADA||'—', parsel=p.PARSEL||'—';
        var mahalle=p.MAHALLE_AD||p.MAHALLE||'—', ilce=p.ILCE||p.ILÇE||'—';
        var caddeKeys=Object.keys(item.caddeler);

        var secili=window._drSecili&&window._drSecili[key]?'checked':'';
        var showCadde=secili?'style="display:block"':'style="display:none"';

        html+='<div class="dr-parsel-kart" data-key="'+key+'">'+
            '<label class="dr-parsel-header" onclick="toggleDrParsel(\''+key+'\')">'+
                '<input type="checkbox" '+secili+' class="dr-parsel-cb" data-key="'+key+'" onchange="toggleDrParsel(\''+key+'\')">'+
                '<span style="font-weight:600;font-size:13px;">Ada '+ada+' / Parsel '+parsel+'</span>'+
            '</label>'+
            '<div class="dr-parsel-details" style="padding-left:28px;">'+
                '<span class="dr-detail">🏛️ '+ilce+' | 🏘️ '+mahalle+'</span>'+
                (item.binas?'<span class="dr-detail">🏠 '+item.binas+' bina</span>':'')+
                (item.binaAdlari.length?'<span class="dr-detail">🏷️ '+item.binaAdlari.join(', ')+'</span>':'')+
                '<span class="dr-detail" style="font-size:10px;color:#94a3b8;">📍 '+(p.NİTELİK||'')+(p.YÜZÖLÇÜM?' | '+p.YÜZÖLÇÜM+' m²':'')+'</span>'+
            '</div>'+

            '<div class="dr-cadde-section" data-parsel="'+key+'" '+showCadde+'>'+
                '<div style="font-size:10px;color:#64748b;padding:2px 10px 4px 28px;font-weight:600;">Cadde / Sokak:</div>';

        caddeKeys.forEach(function(ck,ci){
            var caddeAdi=item.caddeler[ck];
            var cKey=key+'|cadde|'+ci;
            var caddeSecili=window._drCaddeSecili&&window._drCaddeSecili[cKey]?'checked':'';
            var showKapi=caddeSecili?'style="display:block"':'style="display:none"';
            var kapiArr=numData[key]?numData[key].kapilarObj[caddeAdi]||[]:[];
            var kapiCount=kapiArr.length;

            html+='<div class="dr-cadde-item">'+
                '<label style="display:flex;align-items:center;gap:6px;padding:3px 10px 3px 28px;cursor:pointer;font-size:12px;" onclick="toggleDrCadde(\''+key+'\','+ci+')">'+
                    '<input type="checkbox" '+caddeSecili+' onchange="toggleDrCadde(\''+key+'\','+ci+')">'+
                    '<span>🛣️ '+caddeAdi+'</span>'+
                    (kapiCount?'<span style="font-size:10px;color:#94a3b8;">('+kapiCount+' kapı)</span>':'')+
                '</label>'+

                '<div class="dr-kapi-section" data-parsel="'+key+'" data-cadde="'+ci+'" '+showKapi+'>'+
                    (kapiCount?'<div style="font-size:10px;color:#64748b;padding:2px 10px 2px 28px;font-weight:500;">Kapı Numaraları:</div>':'')+
                    (kapiCount?'<label style="display:flex;align-items:center;gap:6px;padding:2px 10px 2px 28px;cursor:pointer;font-size:11px;color:#64748b;" onclick="toggleDrKapiAll(\''+key+'\','+ci+')">'+
                        '<span style="font-size:10px;">☐ Tümünü Seç</span>'+
                    '</label>':'')+
                    kapiArr.map(function(kap,ki){
                        var kKey=key+'|kapi|'+ci+'|'+ki;
                        var kapiSecili=window._drKapiSecili&&window._drKapiSecili[kKey]?'checked':'';
                        return '<label style="display:flex;align-items:center;gap:6px;padding:2px 10px 2px 28px;cursor:pointer;font-size:12px;" onclick="toggleDrKapi(\''+key+'\','+ci+','+ki+')">'+
                            '<input type="checkbox" '+kapiSecili+' onchange="toggleDrKapi(\''+key+'\','+ci+','+ki+')">'+
                            '<span>🚪 '+kap.kapiNo+'</span>'+
                            (kap.binaAdi?'<span style="font-size:10px;color:#94a3b8;">— '+kap.binaAdi+'</span>':'')+
                        '</label>';
                    }).join('')+
                    (!kapiCount?'<span style="font-size:10px;color:#94a3b8;padding:2px 10px 2px 28px;">Kapı bilgisi bulunamadı</span>':'')+
                '</div>'+
            '</div>';
        });

        html+='</div></div>';
    });
    html+='</div>';

    document.getElementById('draw-report-body').innerHTML=html;
    document.getElementById('draw-report-footer').style.display='flex';
    guncelleDrSayac();
    haritadaParselListesiGoster(parsels);
    document.getElementById('parsel-listesi-icerik').innerHTML=
        '<div style="font-size:11px;color:#94a3b8;">✅ '+parsels.length+' parsel bulundu. Detaylar için sağ paneldeki 📋 Çizim Raporu\'nu kullanın.</div>';
    hideLoadingOverlay();
    showToast('✅ '+parsels.length+' parsel bulundu');
}

w.toggleDrParsel=function(key){
    if(!window._drSecili) window._drSecili={};
    if(window._drSecili[key]) delete window._drSecili[key];
    else window._drSecili[key]=true;
    var cb=document.querySelector('.dr-parsel-cb[data-key="'+key+'"]');
    if(cb) cb.checked=!!window._drSecili[key];
    var caddeSec=document.querySelector('.dr-cadde-section[data-parsel="'+key+'"]');
    if(caddeSec) caddeSec.style.display=window._drSecili[key]?'block':'none';
    // Seçim kalkınca alt caddeleri + kapıları temizle
    if(!window._drSecili[key]){
        if(window._drCaddeSecili){
            Object.keys(window._drCaddeSecili).forEach(function(k){
                if(k.startsWith(key+'|')) delete window._drCaddeSecili[k];
            });
        }
        if(window._drKapiSecili){
            Object.keys(window._drKapiSecili).forEach(function(k){
                if(k.startsWith(key+'|')) delete window._drKapiSecili[k];
            });
        }
        // Kapı section'larını gizle
        document.querySelectorAll('.dr-kapi-section[data-parsel="'+key+'"]').forEach(function(el){
            el.style.display='none';
        });
    }
    guncelleDrSayac();
};

w.drawReportYolHatSorgula=function(){
    if(!window._drSecili||!Object.keys(window._drSecili).length){
        showToast('⚠️ En az bir parsel seçin');
        return;
    }
    if(!window._drKapiSecili||!Object.keys(window._drKapiSecili).length){
        showToast('⚠️ En az bir kapı numarası seçin');
        return;
    }

    showLoadingOverlay('Yol hat bilgileri sorgulanıyor...');

    var seciliKeys=Object.keys(window._drSecili);
    var seciliParsels=seciliKeys.map(function(k){
        return _drawReportParsels.find(function(p){
            var pr=p.properties||{};
            return (pr.ADA||'')+'|'+(pr.PARSEL||'')===k;
        });
    }).filter(function(p){return p});

    if(!seciliParsels.length){hideLoadingOverlay();showToast('⚠️ Seçilen parseller bulunamadı');return}

    // Seçili parsellerin bounds'ını hesapla
    var allLatLngs=[];
    seciliParsels.forEach(function(sp){
        try{
            var layer=L.geoJSON(sp);
            allLatLngs.push(layer.getBounds().getSouthWest());
            allLatLngs.push(layer.getBounds().getNorthEast());
        }catch(e){}
    });
    if(allLatLngs.length<2){hideLoadingOverlay();showToast('⚠️ Parsel koordinatları alınamadı');return}

    var bounds=L.latLngBounds(allLatLngs);
    var sw=L.CRS.EPSG3857.project(bounds.getSouthWest());
    var ne=L.CRS.EPSG3857.project(bounds.getNorthEast());
    var bbox=sw.x+','+sw.y+','+ne.x+','+ne.y;

    var layers=['aykome:AYK_DOGALGAZ_LINKS','aykome:AYK_ELEKTRIK_LINKS'];
    var names=['Doğalgaz Hattı','Elektrik Hattı'];
    var found=[];
    var done=0;
    layers.forEach(function(l,i){
        var url='/maps/proxy?url='+encodeURIComponent(
            'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames='+l+'&bbox='+bbox+',EPSG:3857'+
            '&outputFormat=application/json&srsName=EPSG:4326&count=50'
        );
        fetch(url).then(function(r){return r.json()}).then(function(data){
            if(data.features&&data.features.length){
                found.push(names[i]+' ('+data.features.length+' adet)');
                var hl=L.geoJSON(data,{
                    style:{color:'#ef4444',weight:6,opacity:0.8,dashArray:'10,10',fillOpacity:0}
                });
                if(window._utilityHighlight) mapsMap.removeLayer(window._utilityHighlight);
                window._utilityHighlight=hl;
                hl.addTo(mapsMap);
                // İlgili katmanları aktif et
                var cb=document.querySelector('.katman-checkbox[data-layer="'+l+'"]');
                if(cb&&!cb.checked) cb.checked=true;
            }
            done++;
            if(done===layers.length) yolHatSonucGoster(found);
        }).catch(function(){done++;if(done===layers.length) yolHatSonucGoster(found);});
    });
};

function yolHatSonucGoster(found){
    hideLoadingOverlay();
    var html='<div class="dr-yolhat-sonuc" style="margin-top:8px;padding:10px 16px;border-top:1px solid #e2e8f0;">';
    if(found.length){
        html+='<div style="color:#dc2626;font-size:13px;font-weight:600;margin-bottom:6px;">⚠️ Yol Hat Bilgisi</div>';
        found.forEach(function(r){
            html+='<div style="font-size:12px;padding:3px 0;">• '+r+'</div>';
        });
        // Uyarıyı sidebar'a da ekle
        var warnEl=document.getElementById('draw-utility-warning');
        if(warnEl){
            warnEl.innerHTML='⚠️ <b>Uyarı:</b> Seçili alanda ' + found.join(', ')+' bulundu!';
            warnEl.style.display='block';
        }
    } else {
        html+='<div style="color:#16a34a;font-size:13px;font-weight:600;margin-bottom:6px;">✅ Altyapı hattı bulunamadı</div>';
    }
    html+='<hr style="border-color:#e2e8f0;margin:8px 0;">';
    html+='<button onclick="drawReportBasvuruyaGit()" class="btn-b btn-b-submit" style="width:100%;font-size:13px;padding:8px;">📝 Başvuruya İlerle</button>';
    html+='</div>';
    document.getElementById('draw-report-body').insertAdjacentHTML('beforeend',html);
    document.getElementById('dr-yolhat-btn').style.display='none';
    window._yolHatSorgulandi=true;
    guncelleDrSayac();
    showToast(found.length?'⚠️ Yol hat bulundu':'✅ Altyapı temiz');
}

w.drawReportBasvuruyaGit=function(){
    if(!window._drSecili||!Object.keys(window._drSecili).length){
        showToast('⚠️ En az bir parsel seçin');
        return;
    }

    var seciliKeys=Object.keys(window._drSecili);
    var seciliParsels=seciliKeys.map(function(k){
        return _drawReportParsels.find(function(p){
            var pr=p.properties||{};
            return (pr.ADA||'')+'|'+(pr.PARSEL||'')===k;
        });
    }).filter(function(p){return p});

    if(!seciliParsels.length){showToast('⚠️ Seçilen parseller bulunamadı');return}

    var ilk=seciliParsels[0].properties||{};
    var lat=seciliParsels[0].geometry&&seciliParsels[0].geometry.coordinates?seciliParsels[0].geometry.coordinates[1]:null;
    var lng=seciliParsels[0].geometry&&seciliParsels[0].geometry.coordinates?seciliParsels[0].geometry.coordinates[0]:null;

    // Seçili cadde/sokak bilgilerini topla
    var caddeList=[];
    if(window._drCaddeSecili){
        Object.keys(window._drCaddeSecili).forEach(function(ck){
            var parts=ck.split('|');
            if(parts.length>=4){
                var pIdx=parts[0]+'|'+parts[1];
                var pObj=seciliParsels.find(function(s){var sp=s.properties||{};return (sp.ADA||'')+'|'+(sp.PARSEL||'')===pIdx});
                var caddeName=pObj?((pObj.properties||{}).CADDE_SO_1||'')+' '+(pObj.properties||{}).CADDE_SO_2||'Cadde #'+parts[3]:'Cadde #'+parts[3];
                caddeList.push(caddeName.trim());
            }
        });
    }

    // Seçili kapı numaralarını topla
    var kapiList=[];
    if(window._drKapiSecili&&window._drawReportNumData){
        Object.keys(window._drKapiSecili).forEach(function(kk){
            var parts=kk.split('|');
            if(parts.length>=5){
                var pKey=parts[0]+'|'+parts[1];
                var cIdx=parseInt(parts[3]);
                var kIdx=parseInt(parts[4]);
                var numData=window._drawReportNumData[pKey];
                if(numData){
                    var caddeKeys=Object.keys(numData.kapilarObj);
                    var caddeNames=caddeKeys;
                    var caddeName=caddeNames[cIdx]||'';
                    var kapiArr=numData.kapilarObj[caddeName]||[];
                    var kapi=kapiArr[kIdx];
                    if(kapi) kapiList.push(kapi.kapiNo+(kapi.binaAdi?' ('+kapi.binaAdi+')':''));
                }
            }
        });
    }

    kapatDrawReport();

    if(lat&&lng) mapsMap.flyTo([lat,lng],18,{animate:true,duration:0.8});
    setTimeout(function(){
        var adres='<div style="font-size:12px;line-height:1.7;">';
        adres+='<b>📍 Seçili Parseller:</b><br>';
        seciliParsels.forEach(function(sp,i){
            var pr=sp.properties||{};
            adres+='<span style="font-size:11px;">'+(i+1)+'. Ada '+(pr.ADA||'')+' / Parsel '+(pr.PARSEL||'')+' — '+(pr.MAHALLE_AD||pr.MAHALLE||'')+'</span><br>';
        });
        adres+='<b>🏛️ İlçe:</b> '+(ilk.ILCE||ilk.ILÇE||'')+'<br>';
        if(caddeList.length) adres+='<b>🛣️ Cadde/Sokak:</b> '+caddeList.join(', ')+'<br>';
        if(kapiList.length) adres+='<b>🚪 Kapı No:</b> '+kapiList.join(', ')+'<br>';
        adres+='</div>';
        document.getElementById('basvuru-adres-ozet').innerHTML=adres;
        if(ilk.ILCE) document.getElementById('bs-ilce').value=ilk.ILCE;
        if(ilk.ILÇE) document.getElementById('bs-ilce').value=ilk.ILÇE;
        if(ilk.MAHALLE_AD) document.getElementById('bs-mahalle').value=ilk.MAHALLE_AD;
        else if(ilk.MAHALLE) document.getElementById('bs-mahalle').value=ilk.MAHALLE;
        if(lat) document.getElementById('bs-lat').value=lat;
        if(lng) document.getElementById('bs-lng').value=lng;
        var aciklamaEl=document.getElementById('bs-aciklama');
        var detayParts=[];
        seciliParsels.forEach(function(sp,i){
            var pr=sp.properties||{};
            detayParts.push('Ada '+pr.ADA+' / Parsel '+pr.PARSEL);
        });
        if(caddeList.length) detayParts.push('Cadde/Sokak: '+caddeList.join(', '));
        if(kapiList.length) detayParts.push('Kapı No: '+kapiList.join(', '));
        if(!aciklamaEl.value) aciklamaEl.value=detayParts.join(' | ');

        document.getElementById('maps-basvuru-panel').classList.add('open');
        document.getElementById('maps-overlay').style.display='block';
    },500);
};

w.toggleDrKapiAll=function(parselKey,caddeIdx){
    var numData=window._drawReportNumData;
    if(!numData||!numData[parselKey]) return;
    var caddeKeys=Object.keys(numData[parselKey].kapilarObj);
    var caddeName=caddeKeys[caddeIdx];
    if(!caddeName) return;
    var kapiArr=numData[parselKey].kapilarObj[caddeName]||[];
    if(!kapiArr.length) return;

    if(!window._drKapiSecili) window._drKapiSecili={};
    // Tümünün seçili olup olmadığını kontrol et
    var allChecked=true;
    kapiArr.forEach(function(kap,ki){
        var kKey=parselKey+'|kapi|'+caddeIdx+'|'+ki;
        if(!window._drKapiSecili[kKey]) allChecked=false;
    });
    // Toggle: hepsini seç veya hepsini kaldır
    kapiArr.forEach(function(kap,ki){
        var kKey=parselKey+'|kapi|'+caddeIdx+'|'+ki;
        if(allChecked) delete window._drKapiSecili[kKey];
        else window._drKapiSecili[kKey]=true;
    });
    // UI checkbox'ları güncelle
    var boxes=document.querySelectorAll('.dr-kapi-section[data-parsel="'+parselKey+'"][data-cadde="'+caddeIdx+'"] input[type="checkbox"]');
    kapiArr.forEach(function(kap,ki){
        var kKey=parselKey+'|kapi|'+caddeIdx+'|'+ki;
        if(boxes[ki]) boxes[ki].checked=!!window._drKapiSecili[kKey];
    });
    guncelleDrSayac();
};

// 📌 Modal Drag — tüm panelleri sürüklenebilir yap
var _dragState=null;
function makeDraggable(panelSelector,headerSelector){
    var panel=document.querySelector(panelSelector);
    var header=document.querySelector(headerSelector);
    if(!panel||!header) return;
    panel.classList.add('drag-panel');
    header.classList.add('drag-header');
    header.addEventListener('mousedown',function(e){
        if(e.target.closest('button,input,select,textarea,.btn-b,.draw-btn')) return;
        _dragState={panel:panel,startX:e.clientX,startY:e.clientY,
            origLeft:parseInt(panel.style.left)||(window.innerWidth-parseInt(panel.style.width||'400'))/2,
            origTop:parseInt(panel.style.top)||60};
        panel.style.left=_dragState.origLeft+'px';
        panel.style.top=_dragState.origTop+'px';
        panel.style.right='auto';
        document.addEventListener('mousemove',onDrag);
        document.addEventListener('mouseup',stopDrag);
        e.preventDefault();
    });
}
function onDrag(e){
    if(!_dragState) return;
    _dragState.panel.style.left=(_dragState.origLeft+e.clientX-_dragState.startX)+'px';
    _dragState.panel.style.top=(_dragState.origTop+e.clientY-_dragState.startY)+'px';
}
function stopDrag(){_dragState=null;document.removeEventListener('mousemove',onDrag);document.removeEventListener('mouseup',stopDrag);}

// Init drag on DOM ready
document.addEventListener('DOMContentLoaded',function(){
    makeDraggable('#draw-report-panel','#draw-report-panel .drag-header');
    makeDraggable('#hat-kimligi-panel','#hat-kimligi-panel .drag-header');
    makeDraggable('#maps-basvuru-panel','#maps-basvuru-panel .basvuru-header');
});

w.kapatDrawReport=function(){
    document.getElementById('draw-report-panel').style.display='none';
};

w.toggleDrCadde=function(parselKey,caddeIdx){
    var key=parselKey+'|cadde|'+caddeIdx;
    if(!window._drCaddeSecili) window._drCaddeSecili={};
    if(window._drCaddeSecili[key]) delete window._drCaddeSecili[key];
    else window._drCaddeSecili[key]=true;
    // Kapı section göster/gizle
    var kapiSec=document.querySelector('.dr-kapi-section[data-parsel="'+parselKey+'"][data-cadde="'+caddeIdx+'"]');
    if(kapiSec) kapiSec.style.display=window._drCaddeSecili[key]?'block':'none';
    // Cadde seçimi kalkınca kapı seçimlerini de temizle
    if(!window._drCaddeSecili[key]&&window._drKapiSecili){
        Object.keys(window._drKapiSecili).forEach(function(k){
            if(k.startsWith(parselKey+'|kapi|'+caddeIdx+'|')) delete window._drKapiSecili[k];
        });
    }
    guncelleDrSayac();
};

w.toggleDrKapi=function(parselKey,caddeIdx,kapiIdx){
    var key=parselKey+'|kapi|'+caddeIdx+'|'+kapiIdx;
    if(!window._drKapiSecili) window._drKapiSecili={};
    if(window._drKapiSecili[key]) delete window._drKapiSecili[key];
    else window._drKapiSecili[key]=true;
    guncelleDrSayac();
};

function guncelleDrSayac(){
    if(!window._drSecili) window._drSecili={};
    if(!window._drCaddeSecili) window._drCaddeSecili={};
    if(!window._drKapiSecili) window._drKapiSecili={};
    var pCount=Object.keys(window._drSecili).length;
    var cCount=Object.keys(window._drCaddeSecili).length;
    var kCount=Object.keys(window._drKapiSecili).length;
    var sayac=pCount+' parsel, '+cCount+' cadde';
    if(kCount) sayac+=', '+kCount+' kapı';
    sayac+=' seçildi';
    document.getElementById('draw-report-count').textContent=sayac;
    document.getElementById('dr-selected').value=JSON.stringify({
        parseller:window._drSecili,
        caddeler:window._drCaddeSecili,
        kapilar:window._drKapiSecili
    });
    // İleri butonu (en az 1 parsel seçiliyse aktif)
    var ileriBtn=document.getElementById('dr-ileri-btn');
    if(ileriBtn) ileriBtn.disabled=pCount===0;
    // Yol hat butonu (en az 1 kapı seçiliyse göster)
    var yolBtn=document.getElementById('dr-yolhat-btn');
    if(yolBtn){
        if(kCount>0&&!window._yolHatSorgulandi){
            yolBtn.style.display='inline-block';
            yolBtn.disabled=false;
        } else {
            yolBtn.style.display='none';
            yolBtn.disabled=true;
        }
    }
}

function sorguCizimAltyapiKesisimi(latlngs){
    var warnEl=document.getElementById('draw-utility-warning');
    if(!warnEl||!latlngs||latlngs.length<2){if(warnEl)warnEl.style.display='none';return}
    var bounds=L.latLngBounds(latlngs);
    var sw=L.CRS.EPSG3857.project(bounds.getSouthWest());
    var ne=L.CRS.EPSG3857.project(bounds.getNorthEast());
    var bbox=sw.x+','+sw.y+','+ne.x+','+ne.y;
    // Hatlar + noktalar sorgula
    var layers=['aykome:AYK_DOGALGAZ_LINKS','aykome:AYK_ELEKTRIK_LINKS','aykome:AYK_DOGALGAZ_NODES','aykome:AYK_ELEKTRIK_NODES'];
    var names=['Doğalgaz Hattı','Elektrik Hattı','Doğalgaz Noktası','Elektrik Noktası'];
    var found=[];
    var done=0;
    var allFeatures={lines:[],nodes:[]};
    layers.forEach(function(l,i){
        var url='/maps/proxy?url='+encodeURIComponent(
            'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames='+l+'&bbox='+bbox+',EPSG:3857'+
            '&outputFormat=application/json&srsName=EPSG:4326&count=50'
        );
        fetch(url).then(function(r){if(!r.ok)throw new Error();return r.json()}).then(function(data){
            if(data.features&&data.features.length){
                found.push(names[i]+' ('+data.features.length+' adet)');
                if(i<2) allFeatures.lines=allFeatures.lines.concat(data.features);
                else allFeatures.nodes=allFeatures.nodes.concat(data.features);
            }
            done++;
            if(done===layers.length) altyapiSonucGoster(found,allFeatures);
        }).catch(function(){done++;if(done===layers.length) altyapiSonucGoster(found,allFeatures);});
    });
}

function altyapiSonucGoster(found,allFeatures){
    var warnEl=document.getElementById('draw-utility-warning');
    if(!warnEl) return;
    // Haritada highlight
    if(window._utilityHighlight) mapsMap.removeLayer(window._utilityHighlight);
    window._utilityHighlight=null;
    if(found.length){
        // Hatları kırmızı kesik çizgi olarak göster
        if(allFeatures.lines.length){
            var lineLayer=L.geoJSON(allFeatures.lines,{
                style:{color:'#ef4444',weight:6,opacity:0.8,dashArray:'10,10',fillOpacity:0}
            });
            lineLayer.addTo(mapsMap);
            window._utilityHighlight=lineLayer;
        }
        // Noktaları daire marker olarak göster (checkbox kapalı olsa bile)
        if(allFeatures.nodes.length){
            var nodeLayer=L.geoJSON(allFeatures.nodes,{
                pointToLayer:function(feat,ll){
                    return L.circleMarker(ll,{
                        radius:7,color:'#dc2626',fillColor:'#fca5a5',
                        fillOpacity:0.9,weight:2,opacity:1
                    });
                }
            });
            nodeLayer.addTo(mapsMap);
            // Hem hatları hem noktaları tek bir grup olarak yönet
            if(window._utilityHighlight){
                var group=L.layerGroup([window._utilityHighlight,nodeLayer]);
                mapsMap.removeLayer(window._utilityHighlight);
                window._utilityHighlight=group;
                group.addTo(mapsMap);
            } else {
                window._utilityHighlight=nodeLayer;
            }
        }
        warnEl.innerHTML='⚠️ <b>Uyarı:</b> Çizim alanında ' + found.join(', ')+' bulundu! Kazı öncesi kontrol edin.';
        warnEl.style.display='block';
        showToast('⚠️ Uyarı: Çizim alanında ' + found.join(', ')+' tespit edildi!');
        // Katman checkbox'larını aç (LINKS için)
        ['aykome:AYK_DOGALGAZ_LINKS','aykome:AYK_ELEKTRIK_LINKS','aykome:AYK_DOGALGAZ_NODES','aykome:AYK_ELEKTRIK_NODES'].forEach(function(l){
            var cb=document.querySelector('.katman-checkbox[data-layer="'+l+'"]');
            if(cb&&!cb.checked) cb.checked=true;
        });
    } else {
        warnEl.style.display='none';
    }
}

function onDrawStart(){
    document.getElementById('draw-measurement').style.display='none';
    document.getElementById('draw-utility-warning').style.display='none';
    if(window._utilityHighlight) mapsMap.removeLayer(window._utilityHighlight);
}

var _origClearDrawing=clearDrawing;
clearDrawing=function(){
    if(_origClearDrawing) _origClearDrawing();
    document.getElementById('draw-measurement').style.display='none';
    document.getElementById('draw-utility-warning').style.display='none';
    document.getElementById('parsel-listesi-icerik').innerHTML='<div style="font-size:11px;color:#64748b;">Çizim yapınca parseller burada listelenecek.</div>';
    if(window._parcelHighlight) mapsMap.removeLayer(window._parcelHighlight);
    if(window._utilityHighlight) mapsMap.removeLayer(window._utilityHighlight);
    window._drawReportParselMap=null;
    window._drawReportNumData=null;
    window._drSecili={};
    window._drCaddeSecili={};
    window._drKapiSecili={};
    window._yolHatSorgulandi=false;
    window._parselList=[];
};

// Expose functions for HTML onclick handlers
w.parselAra=parselAra;
w.haritadaParselGoster=haritadaParselGoster;
w.seciliParselleBasvuruYap=seciliParselleBasvuruYap;
w.birdenFazlaParsecSec=birdenFazlaParsecSec;
w.seciliParselleBasvuruYapSec=seciliParselleBasvuruYapSec;
w.showDrawMeasurement=showDrawMeasurement;
w.hideLoadingOverlay=hideLoadingOverlay;
w.showLoadingOverlay=showLoadingOverlay;


})(window);
</script>
@endpush
