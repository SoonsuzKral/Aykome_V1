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

body.maps-fullscreen #maps-wrapper { left: 0 !important; top: 0 !important; }
body.maps-fullscreen #btn-fullscreen { background: #ef4444; color: white; }

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
            <div class="layer-row"><label><span class="color-dot" style="background:#f97316;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_MAHALLE_KOYLER" checked><span>Mahalle Sınırları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#a855f7;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_KADASTRO_ADA"><span>Adalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>📐 Kadastro & Parseller</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_KADASTRO_PARSEL" checked><span>Parseller (Genel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#22c55e;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:TKGM_PARSEL"><span>Parseller (TKGM Güncel)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🏗️ Yapı & Adres</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#94a3b8;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:MISMAP_NUM_BINA" checked><span>Binalar</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#f59e0b;"></span><input type="checkbox" class="katman-checkbox" data-layer="smpns:m_Numarataj"><span>Kapı Numaraları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#64748b;"></span><input type="checkbox" class="katman-checkbox" data-layer="cbs:MISMAP_CADDE_SOKAK"><span>Cadde/Sokak Hatları</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
        </div>

        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>🔌 Altyapı Şebekeleri</span>
            <span class="arrow">▼</span>
        </div>
        <div class="accordion-body">
            <div class="layer-row"><label><span class="color-dot" style="background:#3b82f6;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_SU_ICMESUYU_LINKS" checked><span>Aykome İçmesuyu</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#92400e;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_SU_KANALIZASYON_LINKS"><span>Aykome Kanalizasyon</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#67e8f9;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_SU_YAGMURSU_LINKS"><span>Aykome Yağmursuyu</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#eab308;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_ELEKTRIK_LINKS"><span>Aykome Elektrik</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
            <div class="layer-row"><label><span class="color-dot" style="background:#ef4444;"></span><input type="checkbox" class="katman-checkbox" data-layer="aykome:AYK_DOGALGAZ_LINKS" checked><span>Doğalgaz (Hatlar)</span></label><input type="range" class="layer-opacity" min="0" max="1" step="0.1" value="0.7"></div>
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
    <div style="background:linear-gradient(135deg,#1e293b,#334155);color:white;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
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
        format:'image/png',transparent:!0,version:'1.3.0',maxZoom:24,
        errorTileUrl:'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
    },opts));
    layer.on('tileerror',function(e){});
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

    var geo3Layers={
        // İdari Sınırlar
        'cbs:MISMAP_MAHALLE_KOYLER':       {on:!0, group:'admin'},
        'cbs:MISMAP_KADASTRO_ADA':         {on:!1, group:'admin'},
        // Kadastro & Parseller
        'smpns:MISMAP_NUM_KADASTRO_PARSEL':{on:!0, group:'cadastre'},
        'smpns:TKGM_PARSEL':               {on:!1, group:'cadastre'},
        // Yapı & Adres
        'smpns:MISMAP_NUM_BINA':           {on:!0, group:'building'},
        'smpns:m_Numarataj':               {on:!1, group:'building'},
        'cbs:MISMAP_CADDE_SOKAK':          {on:!1, group:'building'},
        // Altyapı Şebekeleri
        'aykome:AYK_SU_ICMESUYU_LINKS':    {on:!0, group:'utility'},
        'aykome:AYK_SU_KANALIZASYON_LINKS':{on:!1, group:'utility'},
        'aykome:AYK_SU_YAGMURSU_LINKS':    {on:!1, group:'utility'},
        'aykome:AYK_ELEKTRIK_LINKS':       {on:!1, group:'utility'},
        'aykome:AYK_DOGALGAZ_LINKS':       {on:!0, group:'utility'},
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
    mapsMap.on('layeradd layerremove',updateActiveLayerCount);

    loadBasvuruMarkers();
    setupEventListeners();
    updateActiveLayerCount();
    initSearchControl();

    setTimeout(function(){mapsMap.invalidateSize()},400);
}

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

    if(_nominatimController) _nominatimController.abort();
    _nominatimController=new AbortController();

    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&addressdetails=1&accept-language=tr',{signal:_nominatimController.signal})
    .then(function(r){return r.json()})
    .then(function(data){
        var addr=data.address||{};
        var ilce=addr.county||addr.town||addr.city_district||'';
        var mahalle=addr.suburb||addr.neighbourhood||addr.quarter||addr.village||'';
        var cadde=addr.road||'';
        var displayName=data.display_name||lat.toFixed(6)+', '+lng.toFixed(6);

        window._sonTiklama={lat:lat,lng:lng,ilce:ilce,mahalle:mahalle,cadde:cadde,displayName:displayName};

        var streetViewLink='<a href="https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='+lat+','+lng+'" target="_blank" style="display:inline-block;background:#4285f4;color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:10px;margin-top:4px;">\uD83D\uDEB6 Street View</a>';

        var popup='<div style="min-width:180px;">'+
            '<div style="font-weight:600;margin-bottom:4px;font-size:12px;">\uD83D\uDCCD '+lat.toFixed(6)+', '+lng.toFixed(6)+'</div>'+
            '<div style="font-size:11px;color:#475569;margin-bottom:6px;">'+(ilce ? ilce+' / ' : '')+(mahalle ? mahalle+' / ' : '')+(cadde ? cadde : '')+'</div>'+
            streetViewLink+
            '<div style="margin-top:6px;display:flex;gap:4px;">'+
            '<button onclick="openBasvuruFromClick(\'kazi_ruhsat\')" style="flex:1;background:#E87722;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83D\uDCCB Kaz\u0131 Ruhsat\u0131</button>'+
            '<button onclick="openBasvuruFromClick(\'ortak_kazi\')" style="flex:1;background:#059669;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83E\uDD1D Ortak Kaz\u0131</button>'+
            '</div>'+
        '</div>';
        L.popup().setLatLng(e.latlng).setContent(popup).openOn(mapsMap);
    })
    .catch(function(err){
        if(err&&err.name==='AbortError')return;
        window._sonTiklama={lat:lat,lng:lng,ilce:'',mahalle:'',cadde:'',displayName:lat.toFixed(6)+', '+lng.toFixed(6)};

        var streetViewLink='<a href="https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='+lat+','+lng+'" target="_blank" style="display:inline-block;background:#4285f4;color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:10px;margin-top:4px;">\uD83D\uDEB6 Street View</a>';

        var popup='<div style="min-width:180px;">'+
            '<div style="font-size:11px;color:#64748b;margin-bottom:4px;">'+lat.toFixed(6)+', '+lng.toFixed(6)+'</div>'+
            streetViewLink+
            '<div style="margin-top:6px;display:flex;gap:4px;">'+
            '<button onclick="openBasvuruFromClick(\'kazi_ruhsat\')" style="flex:1;background:#E87722;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83D\uDCCB Kaz\u0131 Ruhsat\u0131</button>'+
            '<button onclick="openBasvuruFromClick(\'ortak_kazi\')" style="flex:1;background:#059669;color:#fff;border:none;padding:6px 8px;border-radius:5px;font-size:11px;cursor:pointer;">\uD83E\uDD1D Ortak Kaz\u0131</button>'+
            '</div>'+
        '</div>';
        L.popup().setLatLng(e.latlng).setContent(popup).openOn(mapsMap);
    });
}

w.openBasvuruFromClick=function(tip){
    mapsMap.closePopup();
    var t=window._sonTiklama||{lat:0,lng:0,ilce:'',mahalle:'',cadde:'',displayName:''};

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

var _dragData=null;
document.addEventListener('mousedown',function(e){
    if(e.target.closest('.basvuru-close'))return;
    var h=e.target.closest('.basvuru-header');
    if(!h)return;
    var panel=document.getElementById('maps-basvuru-panel');
    if(!panel.classList.contains('open'))return;
    _dragData={ox:e.clientX-panel.offsetLeft,oy:e.clientY-panel.offsetTop};
    h.style.cursor='grabbing';
    e.preventDefault();
});
document.addEventListener('mousemove',function(e){
    if(!_dragData)return;
    var panel=document.getElementById('maps-basvuru-panel');
    panel.style.left=(e.clientX-_dragData.ox)+'px';
    panel.style.top=(e.clientY-_dragData.oy)+'px';
});
document.addEventListener('mouseup',function(){
    if(!_dragData)return;
    _dragData=null;
    var h=document.querySelector('.basvuru-header');
    if(h)h.style.cursor='grab';
});

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
        tahmini_sure:sure||null
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
    var isAreaShape=(type==='polygon'||type==='rectangle'||type==='circle');

    if(type==='marker'){
        var ll=layer.getLatLng();lat=ll.lat;lng=ll.lng
    } else if(isAreaShape){
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

    var bbox;
    if(bounds){
        bbox=bounds.getWest()+','+bounds.getSouth()+','+bounds.getEast()+','+bounds.getNorth();
        var wfsUrl='https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs?service=WFS&version=2.0.0&request=GetFeature&typeNames=smpns:MISMAP_NUM_KADASTRO_PARSEL&outputFormat=application/json&srsName=EPSG:3857&bbox='+bbox;
        fetch(PROXY_URL+encodeURIComponent(wfsUrl)).then(function(r){return r.json()}).then(function(data){
            var features=data.features||[];
            var ilc='',mh='';
            if(features.length>0){
                var pr0=features[0].properties||{};
                ilc=pr0.ILCE||pr0.ilce||'';
                mh=pr0.MAHALLE||pr0.mahalle||'';
                document.getElementById('bs-ilce').value=ilc;
                document.getElementById('bs-mahalle').value=mh;
            }
            var adresParcalari=[];
            if(ilc) adresParcalari.push(ilc);
            if(mh) adresParcalari.push(mh+' Mah.');
            document.getElementById('basvuru-adres-ozet').innerHTML='\uD83D\uDCCD '+(adresParcalari.length?adresParcalari.join(', '):lat.toFixed(6)+', '+lng.toFixed(6));

            if(features.length>0){
                var tbl='<table style="width:100%;border-collapse:collapse;font-size:11px;"><thead><tr style="background:#f1f5f9;"><th style="padding:4px 6px;text-align:left;border-bottom:1px solid #e2e8f0;">#</th><th style="padding:4px 6px;text-align:left;border-bottom:1px solid #e2e8f0;">Ada</th><th style="padding:4px 6px;text-align:left;border-bottom:1px solid #e2e8f0;">Parsel</th><th style="padding:4px 6px;text-align:left;border-bottom:1px solid #e2e8f0;">İlçe</th><th style="padding:4px 6px;text-align:left;border-bottom:1px solid #e2e8f0;">Mahalle</th></tr></thead><tbody>';
                var seen={},cnt=0,limit=100;
                for(var i=0;i<features.length&&cnt<limit;i++){
                    var p=features[i].properties||{};
                    var ai=p.ADA||p.ada||'',pi=p.PARSEL||p.parsel||'',ii=p.ILCE||p.ilce||'',mi=p.MAHALLE||p.mahalle||'';
                    if(!ai&&!pi)continue;
                    var key=ai+'|'+pi;
                    if(seen[key])continue;
                    seen[key]=!0;cnt++;
                    tbl+='<tr><td style="padding:3px 6px;border-bottom:1px solid #f1f5f9;color:#94a3b8;">'+cnt+'</td><td style="padding:3px 6px;border-bottom:1px solid #f1f5f9;font-weight:600;">'+(ai||'-')+'</td><td style="padding:3px 6px;border-bottom:1px solid #f1f5f9;">'+(pi||'-')+'</td><td style="padding:3px 6px;border-bottom:1px solid #f1f5f9;">'+(ii||'-')+'</td><td style="padding:3px 6px;border-bottom:1px solid #f1f5f9;">'+(mi||'-')+'</td></tr>';
                }
                tbl+='</tbody></table>';
                if(features.length>limit)tbl+='<div style="font-size:10px;color:#94a3b8;padding:4px 6px;text-align:center;">+ '+(features.length-limit)+' daha fazla</div>';
                document.getElementById('parsel-tablosu-container').innerHTML=tbl;
                document.getElementById('parsel-tablosu-section').style.display='block';
            }

            document.getElementById('basvuru-coord-display').textContent=lat.toFixed(6)+'\u00B0 K, '+lng.toFixed(6)+'\u00B0 D';
            openPanel();
        }).catch(function(){
            document.getElementById('basvuru-adres-ozet').innerHTML='\uD83D\uDCCD '+(isAreaShape?'\u00c7izim alan\u0131: ':'')+lat.toFixed(6)+', '+lng.toFixed(6);
            document.getElementById('basvuru-coord-display').textContent=lat.toFixed(6)+'\u00B0 K, '+lng.toFixed(6)+'\u00B0 D';
            openPanel();
        });
    } else {
        document.getElementById('basvuru-adres-ozet').innerHTML='\uD83D\uDCCD '+(type==='marker'?'\u0130\u015faret: ':'\u00c7izgi: ')+lat.toFixed(6)+', '+lng.toFixed(6);
        document.getElementById('basvuru-coord-display').textContent=lat.toFixed(6)+'\u00B0 K, '+lng.toFixed(6)+'\u00B0 D';
        openPanel();
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

function showToast(msg){
    var t=document.getElementById('maps-toast');
    t.textContent=msg;t.classList.add('show');
    setTimeout(function(){t.classList.remove('show')},2500);
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
        document.body.classList.toggle('maps-fullscreen');
        setTimeout(function(){mapsMap.invalidateSize()},300);
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

})(window);
</script>
@endpush
