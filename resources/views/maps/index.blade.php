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
    position: fixed; width: 720px; max-width: 98vw; max-height: 92vh; background: white;
    border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    z-index: 2000; display: none; overflow: hidden; flex-direction: column; user-select: none;
}
#maps-basvuru-panel.open { display: flex; }

/* Wizard Stepper */
.wizard-steps { display:flex; align-items:center; justify-content:center; padding:14px 16px 10px; border-bottom:1px solid #e2e8f0; flex-shrink:0; } .w-step { display:flex; flex-direction:column; align-items:center; gap:4px; cursor:default; } .w-step-circle { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; border:2px solid #cbd5e1; color:#94a3b8; background:#fff; transition:all 0.3s; } .w-step.active .w-step-circle { border-color:#E87722; background:#E87722; color:#fff; box-shadow:0 2px 8px rgba(232,119,34,0.3); } .w-step.done .w-step-circle { border-color:#22c55e; background:#22c55e; color:#fff; } .w-step-label { font-size:9px; color:#94a3b8; white-space:nowrap; font-weight:500; line-height:1.2; text-align:center; } .w-step.active .w-step-label { color:#1e293b; font-weight:700; } .w-step.done .w-step-label { color:#16a34a; } .w-step-line { width:36px; height:2px; background:#e2e8f0; margin:0 4px; margin-bottom:20px; } .w-step.done + .w-step-line { background:#22c55e; }

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
@media print {
    body * { visibility: hidden; }
    #maps-basvuru-panel, #maps-basvuru-panel * { visibility: visible; }
    #maps-basvuru-panel { position: absolute; left: 0; top: 0; width: 100%; height: auto; box-shadow: none; border: none; }
    #maps-overlay, #maps-wrapper, .btn, .wizard-steps, .basvuru-header, .basvuru-footer { display: none !important; }
    #step-4 { display: block !important; }
    #basvuru-ozet-icerik { max-height: none !important; overflow: visible !important; }
    .wizard-step { display: none !important; }
    #step-4.wizard-step { display: block !important; }
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
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
            <div class="layer-row"><label><span class="color-dot" style="background:#f97316;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_MAHALLE_KOYLER"><span>Mahalle Sınırları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#a855f7;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_KADASTRO_ADA"><span>Adalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📐 Kadastro & Parseller</span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_KADASTRO_PARSEL"><span>Parseller (Genel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#22c55e;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:TKGM_PARSEL"><span>Parseller (TKGM Güncel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🏗️ Yapı & Adres</span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
            <div class="layer-row"><label><span class="color-dot" style="background:#94a3b8;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_BINA"><span>Binalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#f59e0b;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:m_Numarataj"><span>Kapı Numaraları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#64748b;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_CADDE_SOKAK"><span>Cadde/Sokak Hatları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🔌 Altyapı Şebekeleri</span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
            <div class="layer-row"><label><span class="color-dot" style="background:#eab308;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_ELEKTRIK_LINKS"><span>Aykome Elektrik</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_DOGALGAZ_LINKS"><span>Doğalgaz (Hatlar)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#3b82f6;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_DOGALGAZ_NODES"><span>Doğalgaz (Noktalar)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🛣️ Yol Analizi (15m) <span class="badge" style="background:#22c55e;font-size:9px;padding:1px 5px;border-radius:3px;margin-left:4px;">YENİ</span></span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden" id="road-analysis-panel">
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
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
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
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
            <div style="display:flex;gap:4px;margin-bottom:6px;flex-wrap:wrap;">
                <input type="text" id="parsel-search-ada" placeholder="Ada No" class="maps-input-sm" style="flex:1;min-width:60px;">
                <input type="text" id="parsel-search-parsel" placeholder="Parsel No" class="maps-input-sm" style="flex:1;min-width:60px;">
            </div>
            <input type="text" id="parsel-search-pafta" placeholder="Pafta (opsiyonel)" class="maps-input-sm" style="width:100%;margin-bottom:6px;box-sizing:border-box;">
            <button class="draw-btn" style="width:100%;justify-content:center;" onclick="parselAra()">🔍 Ada/Parsel Sorgula</button>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📋 Parsel Listesi</span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden" id="parsel-listesi-panel">
            <div style="font-size:11px;color:#64748b;margin-bottom:6px;">Çizim yapınca parseller burada listelenecek.</div>
            <div id="parsel-listesi-icerik"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📌 Başvuru Filtresi</span>
            <span class="arrow">▶</span>
        </div>
        <div class="accordion-body hidden">
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

    <div class="wizard-steps">
        <div class="w-step active" data-step="1"><div class="w-step-circle">1</div><div class="w-step-label">Seçim &amp;<br>Konum</div></div>
        <div class="w-step-line"></div>
        <div class="w-step" data-step="2"><div class="w-step-circle">2</div><div class="w-step-label">Başvuru<br>Bilgileri</div></div>
        <div class="w-step-line"></div>
        <div class="w-step" data-step="3"><div class="w-step-circle">3</div><div class="w-step-label">Evraklar</div></div>
        <div class="w-step-line"></div>
        <div class="w-step" data-step="4"><div class="w-step-circle">4</div><div class="w-step-label">Özet &amp;<br>Onay</div></div>
    </div>

    <div class="basvuru-body">
        <!-- Adım 1: Seçim & Konum -->
        <div id="step-1" class="wizard-step">
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

        <!-- Adım 2: Başvuru Bilgileri -->
        <div id="step-2" class="wizard-step" style="display:none;">
            <div class="f-section" style="font-size:12px;">
                <div class="f-section-title" style="margin-bottom:10px;">👤 Başvuru Sahibi & İletişim</div>

                <!-- Satır 1: Kurum -->
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Kurum</label>
                        <select id="bs-institution" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#fff;">
                            <option value="">— Seçiniz —</option>
                            <option value="1">AKSA</option>
                            <option value="2">TEDAŞ</option>
                            <option value="3">ŞUSKİ</option>
                            <option value="4">Türk Telekom</option>
                            <option value="5">HGB Bilişim Demo</option>
                        </select>
                    </div>
                </div>

                <!-- Satır 2: Ad + Soyad -->
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Ad</label>
                        <input type="text" id="bs-first-name" placeholder="Ad" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Soyad</label>
                        <input type="text" id="bs-last-name" placeholder="Soyad" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Satır 3: TCKN + Telefon -->
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">TC Kimlik No</label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" id="bs-tckn" placeholder="TC Kimlik No" maxlength="11" style="flex:1;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
                            <button type="button" id="btn-tckn-sorgula" style="padding:7px 12px;border:1px solid #E87722;background:#E87722;color:#fff;border-radius:6px;font-size:11px;cursor:pointer;white-space:nowrap;">TCKN Sorgula</button>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Telefon Numarası</label>
                        <input type="text" id="bs-phone" placeholder="05XX XXX XX XX" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Satır 4: İşin Adı -->
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">İşin Adı</label>
                    <input type="text" id="bs-excavation-reason" placeholder="Kazı işinin adı" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                </div>

                <!-- Satır 5: Çalışma Türü + Adres -->
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Çalışma Türü</label>
                        <input type="text" id="bs-work-type" placeholder="Kazı / Altyapı / Onarım" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Adres</label>
                        <input type="text" id="bs-address" placeholder="Haritadan otomatik doldurulur" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;background:#f8fafc;">
                    </div>
                </div>

                <!-- Satır 6: Başlangıç + Bitiş Tarihi -->
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Başlangıç Tarihi</label>
                        <input type="date" id="bs-start-date" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Bitiş Tarihi</label>
                        <input type="date" id="bs-end-date" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Satır 7: Açıklama -->
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Açıklama</label>
                    <textarea id="bs-description" rows="3" placeholder="Kazı ile ilgili açıklamalar..." style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;resize:vertical;box-sizing:border-box;"></textarea>
                </div>

                <div class="f-section-title" style="margin-top:14px;margin-bottom:10px;">⚙️ Yüzey ve Keşif</div>

                <!-- Satır 8: Yüzey Tipi + Genişlik + Uzunluk -->
                <div style="display:flex;gap:10px;">
                    <div style="flex:2;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Yüzey Tipi</label>
                        <select id="bs-surface-type" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#fff;">
                            <option value="">— Seçiniz —</option>
                            @foreach($surfaceTypes as $s)
                            <option value="{{ $s->id }}" data-price="{{ $s->price_per_m2 }}">{{ $s->name }} — {{ number_format($s->price_per_m2, 2) }} TL/m²</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Genişlik (m)</label>
                        <input type="number" id="bs-width" placeholder="0.00" step="0.01" min="0" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Uzunluk (m)</label>
                        <input type="number" id="bs-length" placeholder="0.00" step="0.01" min="0" style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                    </div>
                </div>

                <input type="hidden" id="bs-polygon-geojson">
                <input type="hidden" id="bs-total-area">
                <input type="hidden" id="bs-drawing-type" value="">
                <input type="hidden" id="bs-drawing-length" value="">
                <input type="hidden" id="bs-center-lat">
                <input type="hidden" id="bs-center-lng">
            </div>
        </div>

        <!-- Adım 3: Evraklar ve Teminat -->
        <div id="step-3" class="wizard-step" style="display:none;">
            <div class="f-section" style="font-size:12px;">
                <div class="f-section-title" style="margin-bottom:10px;">💰 Teminat & Kazı Bedeli</div>
                <div style="display:flex;gap:10px;margin-bottom:10px;">
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Teminat Bedeli (₺)</label>
                        <div style="position:relative;">
                            <input type="text" id="bs-deposit-amount" inputmode="decimal" placeholder="0,00" style="width:100%;padding:7px 28px 7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:12px;color:#94a3b8;pointer-events:none;">₺</span>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:11px;color:#475569;font-weight:600;display:block;margin-bottom:3px;">Kazı Bedeli (₺)</label>
                        <div style="position:relative;">
                            <input type="text" id="bs-excavation-amount" inputmode="decimal" placeholder="0,00" style="width:100%;padding:7px 28px 7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;box-sizing:border-box;">
                            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:12px;color:#94a3b8;pointer-events:none;">₺</span>
                        </div>
                    </div>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:8px 12px;margin-bottom:14px;font-size:12px;color:#166534;display:flex;justify-content:space-between;align-items:center;">
                    <span>💰 <b>Hesaplanan Tutar:</b></span>
                    <span id="bs-hesaplanan-tutar" style="font-weight:700;font-size:14px;color:#059669;">0.00 TL</span>
                </div>

                <div class="f-section-title" style="margin-bottom:10px;">📎 Yüklenecek Evraklar</div>
                <div id="bs-file-upload-area" style="border:2px dashed #cbd5e1;border-radius:10px;padding:36px 20px;text-align:center;cursor:pointer;transition:all 0.2s;background:#fafbfc;" onclick="document.getElementById('bs-file-input').click()" ondragover="this.style.borderColor='#E87722';this.style.background='#fff7ed'" ondragleave="this.style.borderColor='#cbd5e1';this.style.background='#fafbfc'" ondrop="handleFileDrop(event)">
                    <div style="font-size:36px;color:#3b82f6;margin-bottom:8px;">📤</div>
                    <div style="color:#475569;font-weight:600;font-size:14px;margin-bottom:4px;">Tıklayarak veya sürükleyerek belge yükleyin</div>
                    <div style="color:#94a3b8;font-size:11px;">PDF, JPG, PNG, DOC (max 20MB)</div>
                    <input type="file" id="bs-file-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;" onchange="handleFileSelect(event)">
                    <div id="bs-file-list" style="margin-top:12px;text-align:left;font-size:12px;color:#475569;"></div>
                </div>
            </div>
        </div>

        <!-- Adım 4: Özet & Onay -->
        <div id="step-4" class="wizard-step" style="display:none;">
            <div class="f-section">
                <div class="f-section-title">📋 Başvuru Özeti</div>
                <div id="basvuru-ozet-icerik" style="background:#f8fafc;border-radius:8px;padding:14px;font-size:12px;line-height:1.8;color:#334155;max-height:300px;overflow-y:auto;">
                    Lütfen tüm bilgileri gözden geçirin ve başvuruyu tamamlayın.
                </div>
            </div>
        </div>
    </div>

    <div class="basvuru-footer" id="wizard-footer">
        <button type="button" class="btn btn-outline-secondary" id="btn-geri" onclick="wizardGeri()" style="display:none;padding:7px 14px;font-size:12px;border-radius:6px;">← Önceki</button>
        <div style="flex:1;"></div>
        <button type="button" class="btn btn-success" id="btn-ileri" onclick="wizardIleri()" style="padding:7px 20px;font-size:12px;border-radius:6px;background:#059669;border-color:#059669;color:#fff;">Sonraki Adım →</button>
        <button type="button" class="btn btn-primary" id="btn-kaydet" onclick="basvuruSubmit()" style="display:none;padding:7px 20px;font-size:12px;border-radius:6px;background:#E87722;border-color:#E87722;color:#fff;">Başvuruyu Tamamla ✓</button>
        <button type="button" class="btn btn-outline-secondary" id="btn-yazdir" onclick="window.print()" style="display:none;padding:7px 14px;font-size:12px;border-radius:6px;margin-left:6px;"><i class="fa fa-print"></i> Yazdır</button>
        <button type="button" class="btn btn-outline-danger" onclick="closeBasvuruPanel()" style="margin-left:6px;padding:7px 14px;font-size:12px;border-radius:6px;">İptal</button>
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
        panel.style.left=rect.left+(rect.width/2-360)+'px';
        panel.style.top=rect.top+40+'px';
    }
    wizardReset();
    panel.classList.add('open');
    document.getElementById('maps-overlay').classList.add('active');
}

function closeBasvuruPanel(){
    var overlay=document.getElementById('maps-overlay');
    overlay.classList.remove('active');
    overlay.style.display='none';
    document.getElementById('maps-basvuru-panel').classList.remove('open');
    hideLoadingOverlay();
    if(currentDrawLayer){try{currentDrawLayer.disable()}catch(e){}currentDrawLayer=null}
    document.getElementById('draw-info').style.display='none';
    wizardReset();
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

/* Wizard Adım Yönetimi */
var _currentStep=1;
var _totalSteps=4;
function showWizardStep(step){
    document.querySelectorAll('.wizard-step').forEach(function(s){s.style.display='none';});
    document.querySelectorAll('.w-step').forEach(function(s){s.classList.remove('active','done');});
    var es=document.getElementById('step-'+step);
    if(es)es.style.display='block';
    for(var i=1;i<=_totalSteps;i++){
        var ws=document.querySelector('.w-step[data-step="'+i+'"]');
        if(!ws)continue;
        if(i<step)ws.classList.add('done');
        else if(i===step)ws.classList.add('active');
    }
    document.getElementById('btn-geri').style.display=(step>1?'inline-block':'none');
    document.getElementById('btn-ileri').style.display=(step<_totalSteps?'inline-block':'none');
    document.getElementById('btn-kaydet').style.display=(step===_totalSteps?'inline-block':'none');
    document.getElementById('btn-yazdir').style.display=(step===_totalSteps?'inline-block':'none');
}
function wizardGeri(){
    if(_currentStep>1){_currentStep--;showWizardStep(_currentStep)}
}
/* Wizard'a gidişte özeti doldur — github arayüzü için w. ile de export et */
function wizardIleri(){
    if(_currentStep===1)_copyStep1ToStep2();
    if(_currentStep<_totalSteps){
        _currentStep++;
        if(_currentStep===_totalSteps)_buildOzet();
        showWizardStep(_currentStep);
    }
}
w.wizardIleri=wizardIleri;
w.wizardGeri=wizardGeri;
function wizardOzetGuncelle(){if(_currentStep===_totalSteps)_buildOzet()}
/* Wizard'ı resetle */
function wizardReset(){_currentStep=1;showWizardStep(1)}
/* closeBasvuruPanel zaten aşağıda tanımlı */

var _nominatimController = null;
var _selectedFiles=[];

/* Dosya yükleme işleyicileri */
function handleFileSelect(e){
    var files=e.target.files;
    if(files&&files.length)_selectedFiles=_selectedFiles.concat(Array.from(files));
    _renderFileList();
}
function handleFileDrop(e){
    e.preventDefault();
    var area=document.getElementById('bs-file-upload-area');
    area.style.borderColor='#cbd5e1';area.style.background='#fafbfc';
    var files=e.dataTransfer.files;
    if(files&&files.length)_selectedFiles=_selectedFiles.concat(Array.from(files));
    _renderFileList();
}
function _renderFileList(){
    var list=document.getElementById('bs-file-list');
    if(!_selectedFiles.length){list.innerHTML='';return}
    list.innerHTML=_selectedFiles.map(function(f,i){
        var sizeKB=Math.round(f.size/1024);
        return '<div style="display:flex;align-items:center;gap:6px;padding:4px 0;border-bottom:1px solid #f1f5f9;">'+
            '<span style="color:#3b82f6;font-size:14px;">📄</span>'+
            '<span style="flex:1;">'+f.name+'</span>'+
            '<span style="color:#94a3b8;font-size:10px;">'+sizeKB+' KB</span>'+
            '<span onclick="_removeFile('+i+')" style="cursor:pointer;color:#ef4444;font-size:14px;">×</span></div>';
    }).join('');
}
function _removeFile(idx){_selectedFiles.splice(idx,1);_renderFileList()}

/* Adres kopyalama: Step-1 → Step-2 */
function _copyStep1ToStep2(){
    var t=window._sonTiklama||{};
    var adresParcalari=[];
    if(t.ilce) adresParcalari.push(t.ilce);
    if(t.mahalle) adresParcalari.push(t.mahalle+' Mahallesi');
    if(t.cadde) adresParcalari.push(t.cadde);
    document.getElementById('bs-address').value=adresParcalari.join(', ');
    if(t.lat)document.getElementById('bs-center-lat').value=t.lat;
    if(t.lng)document.getElementById('bs-center-lng').value=t.lng;
}

/* Özet oluştur */
function _buildOzet(){
    function v(id){return document.getElementById(id)?.value||'-'}
    var html='<table style="width:100%;border-collapse:collapse;">';
    var kurumEl=document.getElementById('bs-institution');
    var kurumText=kurumEl?kurumEl.options[kurumEl.selectedIndex]?.text||'-':'-';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Kurum</td><td style="padding:3px 6px;font-weight:600;">'+kurumText+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Ad Soyad</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-first-name')+' '+v('bs-last-name')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">TCKN</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-tckn')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Telefon</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-phone')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">İşin Adı</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-excavation-reason')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Çalışma Türü</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-work-type')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Adres</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-address')+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Tarih</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-start-date')+' → '+v('bs-end-date')+'</td></tr>';
    var drawType=document.getElementById('bs-drawing-type')?.value||'';
    var kapsamText='';
    if(drawType==='polygon'){
        var alan=v('bs-total-area');
        kapsamText=alan!=='—'?alan+' m² (Poligon Kesisi)':'—';
    }else if(drawType==='polyline'){
        var hatUzunluk=v('bs-drawing-length');
        var genislik=v('bs-width');
        kapsamText=(hatUzunluk!=='—'?hatUzunluk+' m Hat':'—')+(genislik!=='—'&&genislik?' × '+genislik+'m Genişlik':'');
    }else{
        kapsamText=v('bs-total-area');
        if(kapsamText==='—')kapsamText=v('bs-drawing-length');
    }
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Kapsam</td><td style="padding:3px 6px;font-weight:600;">'+kapsamText+'</td></tr>';
    var yuzey=document.getElementById('bs-surface-type');
    var yuzeyText=yuzey?yuzey.options[yuzey.selectedIndex]?.text||'-':'-';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Yüzey / Keşif</td><td style="padding:3px 6px;font-weight:600;">'+yuzeyText+'</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Teminat</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-deposit-amount')+' ₺</td></tr>';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Kazı Bedeli</td><td style="padding:3px 6px;font-weight:600;">'+v('bs-excavation-amount')+' ₺</td></tr>';
    var dosyaSayisi=_selectedFiles.length;
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Evraklar</td><td style="padding:3px 6px;font-weight:600;">'+dosyaSayisi+' dosya</td></tr>';
    var hesaplanan=document.getElementById('bs-hesaplanan-tutar')?.textContent||'0.00 TL';
    html+='<tr><td style="padding:3px 6px;color:#64748b;">Hesaplanan Tutar</td><td style="padding:3px 6px;font-weight:600;">'+hesaplanan+'</td></tr>';
    html+='</table>';
    document.getElementById('basvuru-ozet-icerik').innerHTML=html;
}
w._buildOzet=_buildOzet;
w._copyStep1ToStep2=_copyStep1ToStep2;
w.handleFileSelect=handleFileSelect;
w.handleFileDrop=handleFileDrop;
w._removeFile=_removeFile;

/* Yüzey tipleri (DB'den) */
var surfaceTypes = @json($surfaceTypes);
function getSelectedSurfacePrice(){
    var id = Number(document.getElementById('bs-surface-type')?.value || 0);
    var s = surfaceTypes.find(function(i){return Number(i.id)===id});
    return s ? Number(s.price_per_m2) : 0;
}
function turkishNumber(v){
    if(!v||v==='')return 0;
    var s=String(v).trim();
    if(s.indexOf(',')>-1){
        s=s.replace(/\./g,'').replace(',','.');
    }
    return parseFloat(s)||0;
}
function digitsOnly(v){
    return String(v).replace(/[^0-9]/g,'');
}
function formatInput(el){
    var liraPart=el.value.replace(/,00$/,'');
    var d=digitsOnly(liraPart);
    if(!d){el.value='';return;}
    d=String(parseInt(d,10)||0);
    var formatted=d.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    el.value=formatted+',00';
    var pos=formatted.length;
    el.setSelectionRange(pos,pos);
}
function updateSurfaceSummary(){
    var price=getSelectedSurfacePrice();
    var total=0;
    var drawType=document.getElementById('bs-drawing-type')?.value||'';
    if(drawType==='polygon'){
        var area=turkishNumber(document.getElementById('bs-total-area')?.value);
        total=area*price;
    }else{
        var w=turkishNumber(document.getElementById('bs-width')?.value);
        var l=turkishNumber(document.getElementById('bs-length')?.value);
        total=(w>0&&l>0)?w*l*price:0;
    }
    var hesaplanan=document.getElementById('bs-hesaplanan-tutar');
    if(hesaplanan) hesaplanan.textContent=total.toFixed(2)+' TL';
}
function autoFillDimensions(){
    if(!drawnItems||drawnItems.getLayers().length===0)return;
    var drawType=document.getElementById('bs-drawing-type')?.value||'';
    if(drawType==='polygon'){
        document.getElementById('bs-width').value='';
        document.getElementById('bs-length').value='';
        document.getElementById('bs-width').disabled=!0;
        document.getElementById('bs-length').disabled=!0;
        updateSurfaceSummary();
        return;
    }
    var len=parseFloat(document.getElementById('bs-drawing-length')?.value);
    if(len>0){
        document.getElementById('bs-length').value=len.toFixed(2);
        document.getElementById('bs-length').disabled=!0;
        document.getElementById('bs-width').disabled=!1;
    }
    updateSurfaceSummary();
}

w.basvuruSubmit=function(){
    var tipEl=document.querySelector('.tip-option.selected input');
    var tip=tipEl?tipEl.value:'kazi_ruhsat';
    var ortakKurumlar=document.getElementById('bs-ortak-kurumlar')?.value||'';

    function gv(id){var el=document.getElementById(id);return el?el.value:''}

    var payload={
        _token:document.querySelector('meta[name=csrf-token]').content,
        basvuru_tipi:tip,
        ortak_kurumlar:ortakKurumlar,
        lat:parseFloat(gv('bs-lat'))||null,
        lng:parseFloat(gv('bs-lng'))||null,
        ilce:gv('bs-ilce'),
        mahalle:gv('bs-mahalle'),
        ada:gv('bs-ada')||'',
        parsel:gv('bs-parsel')||'',
        address_text:gv('bs-address')||document.getElementById('basvuru-adres-ozet')?.textContent?.replace('\uD83D\uDCCD ','')||'',
        // Step-2 form fields
        institution_id:gv('bs-institution')||null,
        applicant_first_name:gv('bs-first-name'),
        applicant_last_name:gv('bs-last-name'),
        applicant_national_id:gv('bs-tckn'),
        tc_no:gv('bs-tckn'),
        identity_no:gv('bs-tckn'),
        applicant_phone:gv('bs-phone'),
        excavation_reason:gv('bs-excavation-reason'),
        work_type:gv('bs-work-type'),
        description:gv('bs-description'),
        start_date:gv('bs-start-date'),
        end_date:gv('bs-end-date'),
        surface_type_id:gv('bs-surface-type')||null,
        width_m:parseFloat(gv('bs-width'))||null,
        length_m:parseFloat(gv('bs-length'))||null,
        polygon_geojson:gv('bs-polygon-geojson')||null,
        total_area_m2:parseFloat(gv('bs-total-area'))||0,
        drawing_type:gv('bs-drawing-type')||'polygon',
        drawing_length_m:parseFloat(gv('bs-drawing-length'))||null,
        center_lat:parseFloat(gv('bs-center-lat'))||null,
        center_lng:parseFloat(gv('bs-center-lng'))||null,
        deposit_amount:turkishNumber(gv('bs-deposit-amount')),
        excavation_amount:turkishNumber(gv('bs-excavation-amount')),
    };

    if(!payload.lat||!payload.lng){showToast('⚠️ Konum bilgisi eksik');return}
    if(!payload.applicant_first_name||!payload.applicant_last_name){showToast('⚠️ Ad ve Soyad gerekli');return}

    showToast('📤 Başvuru gönderiliyor...');

    fetch('/maps/basvuru-olustur',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
        body:JSON.stringify(payload)
    })
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success){
            closeBasvuruPanel();
            if(typeof Swal!=='undefined'){
                Swal.fire({
                    toast:true,
                    position:'top-end',
                    icon:'success',
                    title:'Başvuru Oluşturuldu',
                    html:'<div style="font-size:13px;line-height:1.6;">' +
                        '<b>Başvuru No:</b> '+(d.application_no||'')+'<br>' +
                        '<b>Tarih:</b> '+new Date().toLocaleString('tr-TR')+'<br>' +
                        '<span style="color:#02E0FB;font-size:11px;">Başvurunuz başarıyla kaydedildi.</span>' +
                        '</div>',
                    showConfirmButton:false,
                    timer:5500,
                    timerProgressBar:true,
                    iconColor:'#02E0FB',
                });
            }else{
                showToast('✅ Başvuru oluşturuldu: '+(d.application_no||''));
            }
            loadBasvuruMarkers();
        }else{
            showToast('⚠️ '+(d.message||'Kayıt başarısız'));
        }
    })
    .catch(function(){showToast('❌ Sunucu hatası')});
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
    document.getElementById('bs-drawing-type').value='';
    document.getElementById('bs-drawing-length').value='';
    document.getElementById('bs-width').disabled=!1;
    document.getElementById('bs-length').disabled=!1;

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
    } else if(type==='polyline'||type==='line'){
        try{
            var ll=layer.getLatLngs();
            var totalLen=0;
            for(var i=1;i<ll.length;i++){
                totalLen+=ll[i-1].distanceTo(ll[i]);
            }
            document.getElementById('bs-drawing-length').value=totalLen.toFixed(2);
            document.getElementById('bs-drawing-type').value='polyline';
        }catch(le){}
    }

    if(area&&area>0){
        var areaText=area>=1e4?(area/1e4).toFixed(2)+' da':area.toFixed(1)+' m²';
        document.getElementById('basvuru-area-display').textContent='📐 Alan: '+areaText;
        document.getElementById('basvuru-area-display').style.display='block';
        document.getElementById('bs-total-area').value=area.toFixed(2);
        document.getElementById('bs-drawing-type').value='polygon';
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
    setTimeout(autoFillDimensions,200);
    setTimeout(updateSurfaceSummary,250);
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
    var arrow=el.querySelector('.arrow');
    if(arrow) arrow.textContent=body.classList.contains('hidden')?'▶':'▼';
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

    document.getElementById('btn-tckn-sorgula').addEventListener('click',function(){
        var tckn=document.getElementById('bs-tckn').value.trim();
        if(tckn.length<10){showToast('⚠️ Geçerli bir TCKN girin');return}
        fetch('/maps/tckn-sorgula/'+tckn)
        .then(function(r){return r.json()})
        .then(function(d){
            if(d.found&&d.data){
                if(d.data.first_name) document.getElementById('bs-first-name').value=d.data.first_name;
                if(d.data.last_name) document.getElementById('bs-last-name').value=d.data.last_name;
                if(d.data.phone) document.getElementById('bs-phone').value=d.data.phone;
                if(d.data.address) document.getElementById('bs-address').value=d.data.address;
                showToast('✅ Bilgiler getirildi');
            }else showToast('⚠️ Bu TCKN\'ye ait kayıt bulunamadı');
        })
        .catch(function(){showToast('⚠️ Sorgu başarısız')});
    });

    document.getElementById('maps-toggle-mobile').addEventListener('click',function(){
        document.getElementById('maps-left-panel').classList.toggle('mobile-open');
    });

    /* Yüzey tipi / genişlik / uzunluk değişiminde fiyat güncelle */
    document.getElementById('bs-surface-type').addEventListener('change',updateSurfaceSummary);
    document.getElementById('bs-width').addEventListener('input',updateSurfaceSummary);
    document.getElementById('bs-length').addEventListener('input',updateSurfaceSummary);

    /* Teminat / Kazı bedeli — Türk lirası formatı */
    ['bs-deposit-amount','bs-excavation-amount'].forEach(function(id){
        var el=document.getElementById(id);
        if(!el)return;
        el.addEventListener('input',function(){formatInput(this);});
        el.addEventListener('blur',function(){formatInput(this);});
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
    document.getElementById('maps-overlay').classList.add('active');
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
    var bboxStr=bounds.toBBoxString(); // EPSG:4326 — minLng,minLat,maxLng,maxLat
    var body=document.getElementById('draw-report-body');
    var panel=document.getElementById('draw-report-panel');
    body.innerHTML='<div style="text-align:center;color:#94a3b8;padding:30px;">🔍 Çizim alanı taranıyor...<br><span style="font-size:11px;">Parseller, binalar, kapı numaraları sorgulanıyor</span></div>';
    panel.style.display='block';
    document.getElementById('draw-report-footer').style.display='none';

    // AŞAMA 1: WFS 1.1.0 ile parsel + bina sorgula (geo4) — EPSG:4326
    var wfsBase='https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs';
    var typeNames=['smpns:MISMAP_NUM_KADASTRO_PARSEL','smpns:MISMAP_NUM_BINA'];
    var results={};
    var wfsDone=0;
    typeNames.forEach(function(tn){
        var url='/maps/proxy?url='+encodeURIComponent(
            wfsBase+'?service=WFS&version=1.1.0&request=GetFeature'+
            '&typeNames='+tn+
            '&bbox='+bboxStr+',EPSG:4326'+
            '&outputFormat=application/json'+
            '&srsName=EPSG:4326&count=1000'
        );
        fetch(url).then(function(r){
            if(!r.ok) throw new Error('HTTP '+r.status);
            return r.text();
        }).then(function(text){
            try{var data=JSON.parse(text);results[tn]=(data&&data.features)||[]}
            catch(e){results[tn]=[]}
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

    // Sadece WFS geo4'ten gelen parsel + bina verisi ile draw report oluştur
    drawReportOlustur(filteredParsels,binas);
}

function drawReportOlustur(parsels,binas){
    var parselMap={};
    var bolgeCaddeler={};
    var bolgeBinalar=[];

    parsels.forEach(function(p){
        var pr=p.properties||{};
        var key=(pr.ADA||'')+'|'+(pr.PARSEL||'');
        var cadde=((pr.CADDE_SO_1||'')+' '+(pr.CADDE_SO_2||'')).trim()||pr.CADDE||pr.CADDE_SOKAK||'';
        if(!parselMap[key]) parselMap[key]={parsel:p, binas:0, caddeler:{}, binaAdlari:[]};
        if(cadde){
            parselMap[key].caddeler[cadde]=cadde;
            bolgeCaddeler[cadde]=cadde;
        }
    });

    binas.forEach(function(b){
        var bp=b.properties||{};
        var key=(bp.ADA||'')+'|'+(bp.PARSEL||'');
        if(parselMap[key]){
            parselMap[key].binas++;
            if(bp.BINA_ADI&&parselMap[key].binaAdlari.indexOf(bp.BINA_ADI)===-1){
                parselMap[key].binaAdlari.push(bp.BINA_ADI);
                if(bolgeBinalar.indexOf(bp.BINA_ADI)===-1) bolgeBinalar.push(bp.BINA_ADI);
            }
        }
    });

    window._drawReportParselMap=parselMap;

    var caddeKeys=Object.keys(bolgeCaddeler);
    var binaCount=bolgeBinalar.length;

    var html='<div style="margin-bottom:8px;font-size:11px;color:#64748b;">';
    html+=parsels.length+' parsel, '+binas.length+' bina'+
        (caddeKeys.length?', '+caddeKeys.length+' cadde/sokak':'')+
        (binaCount?', '+binaCount+' bina adı':'')+' bulundu.';
    html+='</div>';

    if(caddeKeys.length||binaCount){
        html+='<div style="background:#f8fafc;border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:11px;line-height:1.6;">';
        html+='<div style="font-weight:600;color:#334155;margin-bottom:4px;">📊 Bölge Özeti</div>';
        if(caddeKeys.length) html+='🛣️ <b>Cadde/Sokak:</b> '+caddeKeys.join(', ')+'<br>';
        if(binaCount) html+='🏷️ <b>Binalar:</b> '+bolgeBinalar.join(', ')+'<br>';
        html+='</div>';
    }

    html+='<hr style="border-color:#e2e8f0;margin:6px 0;"><div style="font-weight:600;color:#334155;font-size:11px;margin-bottom:4px;">📍 Parseller</div>';
    html+='<div id="dr-parsel-list">';
    Object.keys(parselMap).forEach(function(key){
        var item=parselMap[key];
        var p=item.parsel.properties||{};
        var ada=p.ADA||'—', parsel=p.PARSEL||'—';
        var mahalle=p.MAHALLE_AD||p.MAHALLE||'—', ilce=p.ILCE||p.ILÇE||'—';
        var cKeys=Object.keys(item.caddeler);

        var secili=window._drSecili&&window._drSecili[key]?'checked':'';

        html+='<div class="dr-parsel-kart" data-key="'+key+'">'+
            '<label class="dr-parsel-header" onclick="toggleDrParsel(\''+key+'\')">'+
                '<input type="checkbox" '+secili+' class="dr-parsel-cb" data-key="'+key+'" onchange="toggleDrParsel(\''+key+'\')">'+
                '<span style="font-weight:600;font-size:13px;">Ada '+ada+' / Parsel '+parsel+'</span>'+
            '</label>'+
            '<div class="dr-parsel-details" style="padding-left:28px;">'+
                '<span class="dr-detail">🏛️ '+ilce+' | 🏘️ '+mahalle+'</span>'+
                (item.binas?'<span class="dr-detail">🏠 '+item.binas+' bina</span>':'')+
                (item.binaAdlari.length?'<span class="dr-detail">🏷️ '+item.binaAdlari.join(', ')+'</span>':'')+
                (cKeys.length?'<span class="dr-detail">🛣️ '+cKeys.join(', ')+'</span>':'')+
                '<span class="dr-detail" style="font-size:10px;color:#94a3b8;">📍 '+(p.NİTELİK||'')+(p.YÜZÖLÇÜM?' | '+p.YÜZÖLÇÜM+' m²':'')+'</span>'+
            '</div>'+

            '<div class="dr-cadde-section" data-parsel="'+key+'" style="padding:2px 10px 6px 28px;">'+
                '<div style="font-size:10px;color:#64748b;font-weight:600;">Cadde / Sokak:</div>';

        cKeys.forEach(function(ck,ci){
            var cKey=key+'|cadde|'+ci;
            var caddeSecili=window._drCaddeSecili&&window._drCaddeSecili[cKey]?'checked':'';

            html+='<div class="dr-cadde-item">'+
                '<label style="display:flex;align-items:center;gap:6px;padding:3px 0;cursor:pointer;font-size:12px;" onclick="toggleDrCadde(\''+key+'\','+ci+')">'+
                    '<input type="checkbox" '+caddeSecili+' onchange="toggleDrCadde(\''+key+'\','+ci+')">'+
                    '<span>🛣️ '+ck+'</span>'+
                '</label>'+
            '</div>';
        });

        html+='</div></div>';
    });
    html+='</div>';

    document.getElementById('draw-report-body').innerHTML=html;
    document.getElementById('draw-report-footer').style.display='flex';
    window._drParselCount=parsels.length;
    guncelleDrSayac();
    haritadaParselListesiGoster(parsels);
    document.getElementById('parsel-listesi-icerik').innerHTML=
        '<div style="font-size:11px;color:#94a3b8;">✅ '+parsels.length+' parsel'+
        (caddeKeys.length?', '+caddeKeys.length+' cadde/sokak':'')+' bulundu.</div>';
    hideLoadingOverlay();
    showToast('✅ '+parsels.length+' parsel'+(caddeKeys.length?', '+caddeKeys.length+' cadde/sokak':'')+' bulundu');
}

w.toggleDrParsel=function(key){
    if(!window._drSecili) window._drSecili={};
    if(window._drSecili[key]) delete window._drSecili[key];
    else window._drSecili[key]=true;
    var cb=document.querySelector('.dr-parsel-cb[data-key="'+key+'"]');
    if(cb) cb.checked=!!window._drSecili[key];
    if(!window._drSecili[key]&&window._drCaddeSecili){
        Object.keys(window._drCaddeSecili).forEach(function(k){
            if(k.startsWith(key+'|')) delete window._drCaddeSecili[k];
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
    var bboxStr=bounds.toBBoxString(); // EPSG:4326

    var layers=['aykome:AYK_DOGALGAZ_LINKS','aykome:AYK_ELEKTRIK_LINKS'];
    var names=['Doğalgaz Hattı','Elektrik Hattı'];
    var found=[];
    var done=0;
    layers.forEach(function(l,i){
        // WFS 2.0.0 — EPSG:4326 bbox (geo3 utility katmanlari 2.0.0 gerektirir)
        var url='/maps/proxy?url='+encodeURIComponent(
            'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames='+l+
            '&bbox='+bboxStr+',EPSG:4326'+
            '&outputFormat=application/json'+
            '&srsName=EPSG:4326&count=50'
        );
        fetch(url).then(function(r){if(!r.ok)throw new Error(r.status);return r.text()}).then(function(text){
            try{var data=JSON.parse(text)}catch(e){done++;if(done===layers.length)yolHatSonucGoster(found);return}
            if(data.features&&data.features.length){
                found.push(names[i]+' ('+data.features.length+' adet)');
                var hl=L.geoJSON(data,{
                    style:{color:'#ef4444',weight:6,opacity:0.8,dashArray:'10,10',fillOpacity:0}
                });
                if(window._utilityHighlight) mapsMap.removeLayer(window._utilityHighlight);
                window._utilityHighlight=hl;
                hl.addTo(mapsMap);
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

// 🔍 Parsel detay sorgulama — WFS BBOX (geo4) ile bina + numarataj sorgula
// CRS: toBBoxString() = EPSG:4326 (minLng,minLat,maxLng,maxLat) — hiçbir CRS dönüşümü yok
// WFS 1.1.0 — bbox ve srsName aynı CRS'de (EPSG:4326)
// Tüm property'ler dinamik okunur, hardcoded kolon adı yok
// Harita hareketi İÇERMEZ, safe try/catch ile korunur
w.parseleDetayGoster=function(key){
    try {
        var feature=_drawReportParsels.find(function(p){
            var pr=p.properties||{};
            return (pr.ADA||'')+'|'+(pr.PARSEL||'')===key;
        });
        if(!feature){
            showToast('⚠️ Parsel bulunamadı');
            return;
        }

        var pr=feature.properties||{};
        var ada=pr.ADA||'—';
        var parsel=pr.PARSEL||'—';

        // BBOX: toBBoxString() → "minLng,minLat,maxLng,maxLat" (EPSG:4326)
        var bboxStr;
        try {
            var layer=L.geoJSON(feature);
            var bb=layer.getBounds();
            if(!bb.isValid()){
                // Fallback: feature geometrisinden ilk koordinatı oku
                var c=_getFirstCoord(feature);
                if(c){
                    var d=0.001;
                    bboxStr=(c[0]-d)+','+(c[1]-d)+','+(c[0]+d)+','+(c[1]+d);
                } else {
                    throw new Error('Geçersiz geometri');
                }
            } else {
                bboxStr=bb.toBBoxString();
            }
        } catch(e){
            console.error('BBOX hesaplama hatası:', e);
            hideLoadingOverlay();
            var frm=document.querySelector('.detay-sonuc-form[data-key="'+key+'"]');
            if(frm){
                frm.querySelector('.detay-input-adres').value='📍 Ada: '+ada+' | Parsel: '+parsel+' ⚠️ Parsel geometrisi okunamadı.';
                frm.style.display='block';
            }
            showToast('⚠️ Parsel geometri hatası');
            return;
        }

        showLoadingOverlay('Ada '+ada+' / Parsel '+parsel+' sorgulanıyor...');

        // WFS 1.1.0 — BBOX ve srsName aynı CRS'de (EPSG:4326)
        var wfsBase='https://geo4.sanliurfa.bel.tr:7171/geoserver/wfs';
        var typeNames=['smpns:MISMAP_NUM_BINA']; // smpns:m_Numarataj — GeoServer 500 hatası, düzelene kadar pasif
        var results={};
        var done=0;
        typeNames.forEach(function(tn){
            var url='/maps/proxy?url='+encodeURIComponent(
                wfsBase+'?service=WFS&version=1.1.0&request=GetFeature'+
                '&typeNames='+tn+
                '&bbox='+bboxStr+',EPSG:4326'+
                '&outputFormat=application/json'+
                '&srsName=EPSG:4326&count=100'
            );
            console.log('🔍 WFS istek URL:', decodeURIComponent(url));
            console.log('🌐 BBOX (EPSG:4326):', bboxStr);
            fetch(url).then(function(r){
                return r.text();
            }).then(function(text){
                try {
                    var data=JSON.parse(text);
                    if(data&&data.features){
                        console.log('✅ WFS yanıt ['+tn+']:', data);
                        results[tn]=data.features;
                        return;
                    }
                } catch(e){}
                console.warn('⚠️ WFS JSON hatası ['+tn+']:', text.substring(0,200));
                results[tn]=[];
            }).catch(function(err){
                console.error('❌ WFS fetch hatası ['+tn+']:', err);
                results[tn]=[];
            }).then(function(){
                done++;
                if(done===typeNames.length) _detaySonucGoster(key,ada,parsel,results);
            });
        });
    } catch(error){
        console.error('❌ parseleDetayGoster kritik hata:', error);
        hideLoadingOverlay();
        var frm=document.querySelector('.detay-sonuc-form[data-key="'+key+'"]');
        if(frm){
            frm.querySelector('.detay-input-adres').value='Hata: '+error.message;
            frm.style.display='block';
        }
        showToast('⚠️ Sorgu hatası');
    }
};

function _getFirstCoord(feature){
    try {
        var coords=feature&&feature.geometry&&feature.geometry.coordinates;
        if(!coords) return null;
        var type=feature.geometry.type;
        if(type==='Point') return coords;
        if(type==='Polygon') return coords[0]&&coords[0][0];
        if(type==='MultiPolygon') return coords[0]&&coords[0][0]&&coords[0][0][0];
        if(type==='MultiLineString') return coords[0]&&coords[0][0];
        if(type==='LineString') return coords[0];
        return null;
    } catch(e){ return null; }
}

function _detaySonucGoster(key,ada,parsel,results){
    try {
        hideLoadingOverlay();

        // Raw JSON'u global'e kaydet (Tam Detaylı Rapor butonu için)
        if(!window._wfsRawResults) window._wfsRawResults={};
        window._wfsRawResults[key]={ada:ada,parsel:parsel,results:JSON.parse(JSON.stringify(results))};

        var feature=_drawReportParsels.find(function(p){
            var pr=p.properties||{};
            return (pr.ADA||'')+'|'+(pr.PARSEL||'')===key;
        });
        var pr=feature?feature.properties||{}:{};

        // Akıllı adres verisi çıkarımı — dinamik key eşleştirme
        var wfsAddr={mahalle:'',cadde:'',bina:'',kapino:''};
        var ilce=pr.ILCE||pr.ILÇE||'';

        Object.keys(results).forEach(function(tn){
            var feats=results[tn]||[];
            feats.forEach(function(f){
                var fp=f.properties||{};
                Object.keys(fp).forEach(function(pk){
                    var val=fp[pk];
                    if(val===null||val===undefined||val==='') return;
                    var s=String(val);
                    var low=pk.toLowerCase();
                    if((low.includes('mahalle')||low.includes('mahall'))&&!wfsAddr.mahalle) wfsAddr.mahalle=s;
                    else if((low.includes('cadde')||low.includes('sokak')||low.includes('soka')||low.includes('cad')||low.includes('yol')||low.includes('csbm')||low.includes('bulvar'))&&!wfsAddr.cadde) wfsAddr.cadde=s;
                    else if((low.includes('bina')||low.includes('yapi')||low.includes('yapı')||low.includes('site'))&&(low.includes('adi')||low.includes('adı')||low.includes('isim')||low.includes('name'))&&!wfsAddr.bina) wfsAddr.bina=s;
                    else if((low.includes('kapi')||low.includes('kapı')||low.includes('no')||low.includes('numara')||low.includes('numara'))&&!wfsAddr.kapino) wfsAddr.kapino=s;
                });
            });
        });

        // Parsel property'lerinden yedek
        if(!wfsAddr.mahalle) wfsAddr.mahalle=pr.MAHALLE_AD||pr.MAHALLE||'';
        var caddeParsel=((pr.CADDE_SO_1||'')+' '+(pr.CADDE_SO_2||'')).trim();
        if(!wfsAddr.cadde&&caddeParsel) wfsAddr.cadde=caddeParsel;

        // Uzun adres oluştur
        var adresParts=[];
        if(ilce) adresParts.push(ilce);
        if(wfsAddr.mahalle) adresParts.push(wfsAddr.mahalle+(wfsAddr.mahalle.toLowerCase().includes('mah')?'':' Mah.'));
        if(wfsAddr.cadde) adresParts.push(wfsAddr.cadde);
        if(wfsAddr.bina) adresParts.push(wfsAddr.bina);
        if(wfsAddr.kapino) adresParts.push('No: '+wfsAddr.kapino);
        var adresStr=adresParts.length?adresParts.join(', '):'📍 Ada: '+ada+' / Parsel: '+parsel;

        // Form container'ı bul ve doldur
        var formEl=document.querySelector('.detay-sonuc-form[data-key="'+key+'"]');
        if(!formEl){showToast('⚠️ Form alanı bulunamadı');return}

        formEl.querySelector('.detay-input-adres').value=adresStr;
        formEl.querySelector('.detay-input-mahalle').value=wfsAddr.mahalle||(Object.keys(results).some(function(t){return(results[t]||[]).length})?'...':'');
        formEl.querySelector('.detay-input-cadde').value=wfsAddr.cadde||(Object.keys(results).some(function(t){return(results[t]||[]).length})?'...':'');
        formEl.querySelector('.detay-input-bina').value=wfsAddr.bina||(Object.keys(results).some(function(t){return(results[t]||[]).length})?'...':'');
        formEl.querySelector('.detay-input-kapino').value=wfsAddr.kapino||(Object.keys(results).some(function(t){return(results[t]||[]).length})?'...':'');
        formEl.style.display='block';

        var hasData=wfsAddr.mahalle||wfsAddr.cadde||wfsAddr.bina||wfsAddr.kapino;
        showToast(hasData?'✅ Adres bilgileri getirildi':'📭 Bu parsele ait detay bulunamadı');
    } catch(error){
        console.error('❌ _detaySonucGoster hatası:', error);
        hideLoadingOverlay();
    }
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
    var lat=((seciliParsels[0].geometry&&seciliParsels[0].geometry.type==='Point')
        ?seciliParsels[0].geometry.coordinates[1]:null);
    var lng=((seciliParsels[0].geometry&&seciliParsels[0].geometry.type==='Point')
        ?seciliParsels[0].geometry.coordinates[0]:null);

    // Seçili cadde/sokak bilgilerini topla
    var caddeList=[];
    if(window._drCaddeSecili){
        Object.keys(window._drCaddeSecili).forEach(function(ck){
            var parts=ck.split('|');
            var pIdx=parts[0]+'|'+parts[1];
            var pObj=seciliParsels.find(function(s){
                var sp=s.properties||{};
                return (sp.ADA||'')+'|'+(sp.PARSEL||'')===pIdx;
            });
            var caddeName=pObj
                ? ((pObj.properties||{}).CADDE_SO_1||'')+' '+((pObj.properties||{}).CADDE_SO_2||'')
                : '';
            if(caddeName.trim()) caddeList.push(caddeName.trim());
        });
    }

    kapatDrawReport();

    setTimeout(function(){
        // Altyapı uyarısını çizim panelinden al
        var warnEl=document.getElementById('draw-utility-warning');
        var warnText=warnEl&&warnEl.style.display!=='none'?warnEl.innerHTML:'';

        var adresHtml='<div style="font-size:12px;line-height:1.7;">';
        if(warnText){
            adresHtml+='<div class="alert alert-danger mb-3 fw-bolder text-danger" style="font-size:12px;padding:10px 12px;border-radius:6px;background:#fef2f2;border:2px solid #dc2626;box-shadow:0 2px 8px rgba(220,38,38,0.15);"><strong>⚠️ '+warnText+'</strong></div>';
        }
        adresHtml+='<b>📍 Seçili Parseller:</b><br>';
        seciliParsels.forEach(function(sp,i){
            var pr=sp.properties||{};
            var sKey=(pr.ADA||'')+'|'+(pr.PARSEL||'');
            var cadde=((pr.CADDE_SO_1||'')+' '+(pr.CADDE_SO_2||'')).trim()||'—';
            adresHtml+='<div class="detay-parsel-item" style="margin-bottom:6px;">'+
                '<div style="display:flex;align-items:flex-start;gap:6px;padding:3px 0;">'+
                '<span style="font-size:11px;flex:1;">'+(i+1)+'. Ada <b>'+pr.ADA+'</b> / Parsel <b>'+pr.PARSEL+'</b> — '+(pr.MAHALLE_AD||pr.MAHALLE||'')+'<br>'+
                '<span style="color:#64748b;">🛣️ '+cadde+'</span></span>'+
                '<button onclick="parseleDetayGoster(\''+sKey+'\')" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;font-size:11px;cursor:pointer;white-space:nowrap;flex-shrink:0;">🔍 Detayları Göster</button>'+
                '</div>'+
                '<div class="detay-sonuc-form" data-key="'+sKey+'" style="margin-top:4px;display:none;">'+
                    '<input type="text" class="form-control detay-input-adres" placeholder="Uzun Adres" readonly style="width:100%;font-size:11px;padding:5px 8px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;color:#334155;margin-bottom:4px;box-sizing:border-box;">'+
                    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;">'+
                        '<input type="text" class="form-control detay-input-mahalle" placeholder="Mahalle" readonly style="font-size:11px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;color:#334155;box-sizing:border-box;">'+
                        '<input type="text" class="form-control detay-input-cadde" placeholder="Cadde/Sokak" readonly style="font-size:11px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;color:#334155;box-sizing:border-box;">'+
                        '<input type="text" class="form-control detay-input-bina" placeholder="Bina / Site Adı" readonly style="font-size:11px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;color:#334155;box-sizing:border-box;">'+
                        '<input type="text" class="form-control detay-input-kapino" placeholder="Numarataj/Kapı No" readonly style="font-size:11px;padding:4px 6px;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;color:#334155;box-sizing:border-box;">'+
                    '</div>'+
                    '<div style="margin-top:4px;display:flex;gap:4px;">'+
                        '<button type="button" class="btn btn-sm toggle-raw-report" data-key="'+sKey+'" style="font-size:10px;padding:3px 8px;background:transparent;border:1px solid #06b6d4;border-radius:4px;color:#0891b2;cursor:pointer;">🔍 Tam Detaylı Rapor</button>'+
                    '</div>'+
                    '<div class="raw-report-content" data-key="'+sKey+'" style="display:none;margin-top:4px;max-height:200px;overflow-y:auto;font-size:10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:6px;"></div>'+
                '</div>'+
                '</div>';
        });
        adresHtml+='<div style="margin-top:4px;font-size:11px;color:#475569;">🏛️ '+(ilk.ILCE||ilk.ILÇE||'')+(caddeList.length?' | 🛣️ '+caddeList.join(', '):'')+'</div>';
        adresHtml+='</div>';
        document.getElementById('basvuru-adres-ozet').innerHTML=adresHtml;
        if(ilk.ILCE) document.getElementById('bs-ilce').value=ilk.ILCE;
        if(ilk.ILÇE) document.getElementById('bs-ilce').value=ilk.ILÇE;
        if(ilk.MAHALLE_AD) document.getElementById('bs-mahalle').value=ilk.MAHALLE_AD;
        else if(ilk.MAHALLE) document.getElementById('bs-mahalle').value=ilk.MAHALLE;
        if(lat) document.getElementById('bs-lat').value=lat;
        if(lng) document.getElementById('bs-lng').value=lng;
        document.getElementById('maps-basvuru-panel').classList.add('open');
        document.getElementById('maps-overlay').classList.add('active');
    },500);
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
    if(!_dragState||e.buttons===0){stopDrag();return}
    _dragState.panel.style.left=(_dragState.origLeft+e.clientX-_dragState.startX)+'px';
    _dragState.panel.style.top=(_dragState.origTop+e.clientY-_dragState.startY)+'px';
}
function stopDrag(){_dragState=null;document.removeEventListener('mousemove',onDrag);document.removeEventListener('mouseup',stopDrag);}
// Global drag güvenlik ağı — odak kaybı veya sayfa gizlenirse zorla sıfırla
window.addEventListener('blur',function(){if(_dragState)stopDrag()});
document.addEventListener('visibilitychange',function(){if(document.hidden&&_dragState)stopDrag()});

// 🗂️ Tam Detaylı Rapor toggle
document.addEventListener('click',function(e){
    var btn=e.target.closest('.toggle-raw-report');
    if(!btn) return;
    var key=btn.dataset.key;
    var content=document.querySelector('.raw-report-content[data-key="'+key+'"]');
    if(!content) return;
    if(content.style.display!=='none'){content.style.display='none';return}
    var rawData=window._wfsRawResults&&window._wfsRawResults[key];
    if(!rawData){
        content.innerHTML='<div style="color:#94a3b8;padding:8px;text-align:center;">Önce 🔍 Detayları Göster ile sorgulama yapın.</div>';
        content.style.display='block';return
    }
    var html='<table style="width:100%;border-collapse:collapse;font-size:10px;">'+
        '<tr style="background:#e2e8f0;"><th style="padding:4px 6px;border:1px solid #cbd5e1;text-align:left;width:40%;">Özellik</th><th style="padding:4px 6px;border:1px solid #cbd5e1;text-align:left;">Değer</th></tr>';
    var rowCount=0;
    Object.keys(rawData.results).forEach(function(tn){
        var feats=rawData.results[tn]||[];
        feats.forEach(function(f){
            var fp=f.properties||{};
            Object.keys(fp).forEach(function(k){
                if(k==='bbox'||k==='geometry'||k==='the_geom'||k==='geom') return;
                var val=fp[k]!==null&&fp[k]!==undefined?String(fp[k]):'-';
                html+='<tr style="background:'+(rowCount%2===0?'#fff':'#f8fafc')+'">'+
                    '<td style="padding:3px 6px;border:1px solid #e2e8f0;font-weight:600;color:#475569;">'+k+'</td>'+
                    '<td style="padding:3px 6px;border:1px solid #e2e8f0;color:#334155;">'+val+'</td></tr>';
                rowCount++;
            });
        });
    });
    html+='</table>';
    if(!rowCount) html='<div style="color:#94a3b8;padding:8px;text-align:center;">Bu parsele ait ek nitelik bulunamadı.</div>';
    content.innerHTML=html;
    content.style.display='block';
});

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
    guncelleDrSayac();
};



function guncelleDrSayac(){
    if(!window._drSecili) window._drSecili={};
    if(!window._drCaddeSecili) window._drCaddeSecili={};
    var pCount=Object.keys(window._drSecili).length;
    var cCount=Object.keys(window._drCaddeSecili).length;
    document.getElementById('draw-report-count').textContent=pCount+' parsel, '+cCount+' cadde seçildi';
    document.getElementById('dr-selected').value=JSON.stringify({
        parseller:window._drSecili,
        caddeler:window._drCaddeSecili
    });
    var ileriBtn=document.getElementById('dr-ileri-btn');
    if(ileriBtn) ileriBtn.disabled=pCount===0;
    var yolBtn=document.getElementById('dr-yolhat-btn');
    if(yolBtn) yolBtn.style.display='none';
}

function sorguCizimAltyapiKesisimi(latlngs){
    var warnEl=document.getElementById('draw-utility-warning');
    if(!warnEl||!latlngs||latlngs.length<2){if(warnEl)warnEl.style.display='none';return}
    var bounds=L.latLngBounds(latlngs);
    var bboxStr=bounds.toBBoxString(); // EPSG:4326
    var layers=['aykome:AYK_DOGALGAZ_LINKS','aykome:AYK_ELEKTRIK_LINKS','aykome:AYK_DOGALGAZ_NODES','aykome:AYK_ELEKTRIK_NODES'];
    var names=['Doğalgaz Hattı','Elektrik Hattı','Doğalgaz Noktası','Elektrik Noktası'];
    var found=[];
    var done=0;
    var allFeatures={lines:[],nodes:[]};
    layers.forEach(function(l,i){
        // WFS 2.0.0 — EPSG:4326 bbox (geo3 utility katmanlari 2.0.0 gerektirir)
        var url='/maps/proxy?url='+encodeURIComponent(
            'https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature'+
            '&typeNames='+l+
            '&bbox='+bboxStr+',EPSG:4326'+
            '&outputFormat=application/json'+
            '&srsName=EPSG:4326&count=50'
        );
        fetch(url).then(function(r){if(!r.ok)throw new Error();return r.text()}).then(function(text){
            try{var data=JSON.parse(text)}catch(e){done++;if(done===layers.length)altyapiSonucGoster(found,allFeatures);return}
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
        showToast('⚠️ ' + found.join(', '));
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
        showToast('✅ Altyapı sorgusu tamam — çizim alanında doğalgaz/elektrik hattı bulunamadı');
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
    window._drSecili={};
    window._drCaddeSecili={};
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
