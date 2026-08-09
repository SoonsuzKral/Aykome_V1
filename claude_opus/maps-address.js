/**
 * AYKOME — maps-address.js
 * ════════════════════════════════════════════════════════════════
 * PRIMARY kaynak : storage/shp/15_alti.js  (EybAlti global var)
 *                  storage/shp/15_ustu.js  (EybUstu global var)
 * SECONDARY kaynak: WFS (Laravel proxy üzerinden, yedek)
 * WMS : Sadece görsel katman (tile), CORS yok
 * ════════════════════════════════════════════════════════════════
 *
 * KULLANIM (blade'de):
 *   <script src="/storage/shp/15_alti.js"></script>
 *   <script src="/storage/shp/15_ustu.js"></script>
 *   <script src="/js/maps-address.js"></script>
 *
 *   const addr = new AykomeMaps({ csrfToken: '...', mapId: 'harita' });
 *   addr.init();
 */

'use strict';

// ═══════════════════════════════════════════════════════════
// TR BÜYÜK HARF — İ/I krizi yok
// ═══════════════════════════════════════════════════════════
function trUp(s) {
    if (!s) return '';
    return String(s)
        .replace(/i/g, 'İ')
        .replace(/ı/g, 'I')
        .replace(/ğ/g, 'Ğ')
        .replace(/ü/g, 'Ü')
        .replace(/ş/g, 'Ş')
        .replace(/ö/g, 'Ö')
        .replace(/ç/g, 'Ç')
        .toUpperCase();
}

// ═══════════════════════════════════════════════════════════
// GEOMETRİ YARDIMCILARI
// GeoJSON koordinatlar: [longitude, latitude] — index 0=lng, 1=lat
// ═══════════════════════════════════════════════════════════
function flatCoords(geometry) {
    /** Tüm [lng,lat] çiftlerini düz diziye çevirir */
    const out = [];
    function walk(arr) {
        if (!Array.isArray(arr)) return;
        if (typeof arr[0] === 'number') { out.push(arr); return; }
        arr.forEach(walk);
    }
    walk(geometry.coordinates || []);
    return out;
}

function centroidOf(geometry) {
    const pts = flatCoords(geometry);
    if (!pts.length) return null;
    return {
        lat: pts.reduce((s, p) => s + p[1], 0) / pts.length,  // p[1] = lat
        lng: pts.reduce((s, p) => s + p[0], 0) / pts.length   // p[0] = lng
    };
}

function bboxOf(geometry) {
    const pts = flatCoords(geometry);
    if (!pts.length) return null;
    return {
        minLng: Math.min(...pts.map(p => p[0])),
        minLat: Math.min(...pts.map(p => p[1])),
        maxLng: Math.max(...pts.map(p => p[0])),
        maxLat: Math.max(...pts.map(p => p[1])),
    };
}

function bboxStr(bb, pad = 0) {
    /** WFS/BBOX string: minLng,minLat,maxLng,maxLat */
    return `${bb.minLng - pad},${bb.minLat - pad},${bb.maxLng + pad},${bb.maxLat + pad}`;
}

// ═══════════════════════════════════════════════════════════
// LOCAL SHP VERİSİ — 15_alti.js + 15_ustu.js
// ═══════════════════════════════════════════════════════════
let _tumCaddeler = null; // cache

function buildTumCaddeler() {
    if (_tumCaddeler) return _tumCaddeler;

    const alti = (typeof EybAlti !== 'undefined') ? EybAlti.features : [];
    const ustu = (typeof EybUstu !== 'undefined') ? EybUstu.features : [];
    const all  = [...alti, ...ustu];

    if (!all.length) {
        console.warn('[AYKOME] 15_alti.js ve 15_ustu.js yüklenemedi!');
        return [];
    }

    // İlk feature'dan alan adını otomatik tespit et
    const sample = all[0]?.properties || {};
    console.log('[AYKOME] SHP sample properties:', sample);

    // Olası alan adları — hangisi varsa kullan
    const NAME_FIELDS = [
        'CADDE_SOKAK_ADI', 'ADI', 'NAME', 'ad', 'name',
        'SOKAK_ADI', 'CAD_SOK_ADI', 'LABEL', 'label'
    ];

    _CAD_FIELD = NAME_FIELDS.find(f => sample.hasOwnProperty(f)) || Object.keys(sample)[0];
    console.log('[AYKOME] Cadde alan adı:', _CAD_FIELD);

    _tumCaddeler = all.filter(f => f.geometry && f.properties?.[_CAD_FIELD]);
    console.log('[AYKOME] Toplam cadde/sokak:', _tumCaddeler.length);

    return _tumCaddeler;
}

let _CAD_FIELD = 'CADDE_SOKAK_ADI'; // default, buildTumCaddeler'da override edilir

// ─── Mahalle BBOX'ı içindeki caddeleri filtrele ───────────
function caddelerInBbox(mahalleBbox, tolerans = 0.001) {
    const all = buildTumCaddeler();
    const { minLng, minLat, maxLng, maxLat } = mahalleBbox;

    const filtered = all.filter(f => {
        const pts = flatCoords(f.geometry);
        // En az 1 koordinat bbox içinde olsun (toleranslı)
        return pts.some(([lng, lat]) =>
            lng >= minLng - tolerans && lng <= maxLng + tolerans &&
            lat >= minLat - tolerans && lat <= maxLat + tolerans
        );
    });

    // Unique cadde adları (aynı sokak birden fazla segment olabilir)
    const unique = new Map();
    filtered.forEach(f => {
        const ad = f.properties[_CAD_FIELD];
        if (!ad) return;
        const key = trUp(ad);
        if (!unique.has(key)) {
            unique.set(key, {
                ad,
                center: centroidOf(f.geometry),
                bbox:   bboxOf(f.geometry),
            });
        }
    });

    const list = [...unique.values()].sort((a, b) => a.ad.localeCompare(b.ad, 'tr'));
    console.log(`[AYKOME] Mahalle bbox içinde ${list.length} cadde/sokak bulundu`);
    return list;
}

// ─── Sokak adına göre ara ─────────────────────────────────
function sokakAra(query) {
    const all = buildTumCaddeler();
    const q   = trUp(query.trim());

    const hits = all.filter(f => {
        const ad = trUp(f.properties[_CAD_FIELD] || '');
        return ad.includes(q);
    });

    // En iyi eşleşme (tam eşleşme önce)
    hits.sort((a, b) => {
        const aEx = trUp(a.properties[_CAD_FIELD]) === q ? 0 : 1;
        const bEx = trUp(b.properties[_CAD_FIELD]) === q ? 0 : 1;
        return aEx - bEx;
    });

    if (!hits.length) return null;
    return {
        ad:     hits[0].properties[_CAD_FIELD],
        center: centroidOf(hits[0].geometry),
        bbox:   bboxOf(hits[0].geometry),
    };
}

// ═══════════════════════════════════════════════════════════
// ADRES PARSE
// ═══════════════════════════════════════════════════════════
function parseAdres(raw) {
    raw = raw.trim();

    // Sokak numarası: "8125. Sk." / "8125 SOKAK" / "8125 SK" / "8125.SK"
    const sokakM =
        raw.match(/(\d{3,5})\s*\.?\s*(?:sk|sokak|cad|cadde)\.?/i) ||
        raw.match(/(\d{3,5})\s+(?:nolu\s+)?(?:sk|sokak|cad|cadde)/i);

    const sokakNo = sokakM?.[1] || null;

    // Kapı no: "122" hemen sokaktan sonra VEYA "No:122" şeklinde
    let kapiNo = null;
    if (sokakM) {
        const after = raw.slice(sokakM.index + sokakM[0].length);
        const kM = after.match(/^\s*\.?\s*(\d{1,4})\b/) ||
                   raw.match(/\bno\.?\s*:?\s*(\d{1,4})\b/i);
        kapiNo = kM?.[1] || null;
    }

    // Mahalle: "Kadıkendi" / "15 TEMMUZ" / "BATIKENT MAH"
    const mahM = raw.match(/([\wçğışöüÇĞİŞÖÜ]+(?:\s+[\wçğışöüÇĞİŞÖÜ]+)?)\s+(?:mah|mahalle|mahallesi)\b/i) ||
                 raw.match(/\b([\wçğışöüÇĞİŞÖÜ]+)\s+mahallesinde\b/i);
    const mahalleAdi = mahM?.[1]?.trim() || null;

    console.log('[parseAdres]', { raw, sokakNo, kapiNo, mahalleAdi });
    return { sokakNo, kapiNo, mahalleAdi, raw };
}

// ═══════════════════════════════════════════════════════════
// ANA ADRES ARAMA (çok katmanlı strateji)
// ═══════════════════════════════════════════════════════════
async function adresKonumBul(raw, wfsProxy = '/maps/adres-ara', csrfToken = '') {
    const parsed = parseAdres(raw);

    // ── STRATEJİ 1: Local SHP'de sokak no ile ara ──────────
    if (parsed.sokakNo) {
        const hit = sokakAra(parsed.sokakNo);
        if (hit?.center) {
            console.log('[adresKonumBul] LOCAL SHP ile bulundu:', hit);
            return {
                lat:        hit.center.lat,
                lng:        hit.center.lng,
                label:      hit.ad,
                method:     '✅ Local SHP (15_alti/ustu)',
                confidence: 'high',
            };
        }
    }

    // ── STRATEJİ 2: WFS Laravel proxy ──────────────────────
    try {
        const res = await fetch(wfsProxy, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ adres: raw }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data.success) {
                console.log('[adresKonumBul] WFS proxy ile bulundu:', data);
                return {
                    lat:        data.lat,
                    lng:        data.lng,
                    label:      data.label,
                    method:     '⚡ WFS Proxy',
                    confidence: data.confidence || 'medium',
                };
            }
        }
    } catch (e) {
        console.warn('[adresKonumBul] WFS proxy hatası:', e.message);
    }

    // ── STRATEJİ 3: Mahalle centroidi (son çare) ────────────
    if (parsed.mahalleAdi) {
        // WFS mahalle endpoint
        try {
            const res = await fetch(`/maps/mahalle-bul?mahalle=${encodeURIComponent(parsed.mahalleAdi)}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                if (data.success && data.data.length) {
                    const m = data.data[0];
                    return {
                        lat:        m.center.lat,
                        lng:        m.center.lng,
                        label:      m.ad + ' Mahallesi',
                        method:     '📍 Mahalle Centroid',
                        confidence: 'low',
                    };
                }
            }
        } catch (e) {
            console.warn('[adresKonumBul] Mahalle proxy hatası:', e.message);
        }
    }

    return null; // Bulunamadı
}

// ═══════════════════════════════════════════════════════════
// LEAFLET PULSE MARKER
// ═══════════════════════════════════════════════════════════
function makePulseIcon(color = '#1e5fa8') {
    return L.divIcon({
        className: '',
        html: `<div style="position:relative;width:18px;height:18px;">
          <div style="width:18px;height:18px;border-radius:50%;background:${color};position:absolute;
               animation:aykome_core 2s infinite;"></div>
          <div style="width:18px;height:18px;border-radius:50%;border:3px solid ${color}80;position:absolute;
               animation:aykome_ring 2s infinite;"></div>
        </div>
        <style>
          @keyframes aykome_core{0%,100%{box-shadow:0 0 0 0 ${color}B3;}70%{box-shadow:0 0 0 14px ${color}00;}}
          @keyframes aykome_ring{0%{transform:scale(.5);opacity:1;}100%{transform:scale(2.8);opacity:0;}}
        </style>`,
        iconSize:   [18, 18],
        iconAnchor: [9, 9],
    });
}

// ═══════════════════════════════════════════════════════════
// ANA CLASS
// ═══════════════════════════════════════════════════════════
class AykomeMaps {
    constructor(opts = {}) {
        this.opts = {
            mapId:          'aykome-map',
            csrfToken:      document.querySelector('meta[name=csrf-token]')?.content || '',
            mahallelerUrl:  '/maps/mahalleler',
            sokakCaddelerUrl: '/maps/sokak-caddeler',
            kapiUrl:        '/maps/kapi-numaralari',
            adresAraUrl:    '/maps/adres-ara',
            eyyubiyeCenter: [37.1591, 38.7969],
            ...opts,
        };

        this.map       = null;
        this.marker    = null;
        this.mahalleler = [];
        this.secilenMahalle = null;
        this.secilenCadde   = null;
        this.caddeler  = [];
    }

    // ── Haritayı başlat ──────────────────────────────────
    initMap() {
        this.map = L.map(this.opts.mapId).setView(this.opts.eyyubiyeCenter, 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OSM | AYKOME', maxZoom: 22,
        }).addTo(this.map);

        // WMS katmanları
        const wo = { format: 'image/png', transparent: true, version: '1.1.1', maxZoom: 22 };
        const WMS = 'https://geo3.sanliurfa.bel.tr:8091/geoserver/wms';
        const wl = n => L.tileLayer.wms(WMS, { ...wo, layers: n });

        wl('cbs:MISMAP_MAHALLE_KOYLER').addTo(this.map);
        wl('cbs:MISMAP_CADDE_SOKAK').addTo(this.map);
        wl('smpns:MISMAP_NUM_BINA').addTo(this.map);
        wl('smpns:m_Numarataj').addTo(this.map);

        return this;
    }

    // ── Noktaya fly ──────────────────────────────────────
    flyTo(lat, lng, label, zoom = 17) {
        if (this.marker) this.map.removeLayer(this.marker);
        this.marker = L.marker([lat, lng], { icon: makePulseIcon() })
            .addTo(this.map)
            .bindPopup(`<b>${label}</b><br><small>${lat.toFixed(6)}, ${lng.toFixed(6)}</small>`)
            .openPopup();
        this.map.flyTo([lat, lng], zoom, { animate: true, duration: 1.2 });
        return this;
    }

    // ── Mahalle listesini yükle ──────────────────────────
    async loadMahalleler() {
        try {
            const r = await fetch(this.opts.mahallelerUrl,
                { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (d.success) {
                this.mahalleler = d.data;
                console.log(`[AYKOME] ${this.mahalleler.length} mahalle yüklendi`);
                return this.mahalleler;
            }
        } catch (e) {
            console.error('[AYKOME] Mahalle yüklenemedi:', e.message);
        }
        return [];
    }

    // ── Mahalle seçilince cadde yükle (LOCAL ÖNCE) ───────
    async loadCaddeler(mahalle) {
        this.secilenMahalle = mahalle;
        this.caddeler = [];

        const bb = mahalle.bbox; // { minLng, minLat, maxLng, maxLat, wfsBbox }

        // ── ÖNCE LOCAL SHP ──
        if (bb && (typeof EybAlti !== 'undefined' || typeof EybUstu !== 'undefined')) {
            // bbox objesi string gelebilir, düzelt
            const bboxObj = typeof bb === 'string'
                ? (([a,b,c,d]) => ({ minLng:+a, minLat:+b, maxLng:+c, maxLat:+d }))(bb.split(','))
                : bb;

            this.caddeler = caddelerInBbox(bboxObj);
            console.log(`[AYKOME] LOCAL: ${this.caddeler.length} cadde (${mahalle.ad})`);

            if (this.caddeler.length > 0) return this.caddeler;
        }

        // ── YEDEK: WFS proxy ──
        console.warn('[AYKOME] LOCAL veri yok, WFS proxy deneniyor...');
        try {
            const bboxStr2 = typeof bb === 'string' ? bb : bboxStr(bb);
            const r = await fetch(this.opts.sokakCaddelerUrl, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.opts.csrfToken,
                    Accept:         'application/json',
                },
                body: JSON.stringify({ bbox: bboxStr2 }),
            });
            const d = await r.json();
            if (d.success) {
                this.caddeler = d.data;
                console.log(`[AYKOME] WFS: ${this.caddeler.length} cadde`);
            }
        } catch (e) {
            console.error('[AYKOME] WFS cadde hatası:', e.message);
        }

        return this.caddeler;
    }

    // ── Adres ara ──────────────────────────────────────
    async searchAdres(raw) {
        return adresKonumBul(raw, this.opts.adresAraUrl, this.opts.csrfToken);
    }

    // ── Tam init ─────────────────────────────────────────
    async init() {
        // SHP verisini hazırla (async değil ama global var gerekiyor)
        buildTumCaddeler();

        this.initMap();
        await this.loadMahalleler();
        return this;
    }
}

// Global olarak da erişilebilir
window.AykomeMaps       = AykomeMaps;
window.aykomeSokakAra   = sokakAra;
window.aykomeParseAdres = parseAdres;
window.aykomeCentroid   = centroidOf;
window.aykomeBbox       = bboxOf;
window.aykomeCaddelerInBbox = caddelerInBbox;
