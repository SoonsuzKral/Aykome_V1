/**
 * AYKOME — maps-address.js (LOCAL SHP ÖNCELİK)
 * ════════════════════════════════════════════════════════════════
 * PRIMARY  : storage/shp/15_alti.js (EybAlti) + 15_ustu.js (EybUstu)
 * SECONDARY: WFS (Laravel proxy — yedek)
 *
 * 15m SEMANTİĞİ:
 *   - 15_alti.js = 15m ALTINDAKİ yollar (genişlik < 15m)
 *   - 15_ustu.js = 15m ÜSTÜNDEKİ yollar (genişlik >= 15m)
 *   Aynı veri hem cadde listesi hem 15m kontrolü için kullanılır.
 *
 * KULLANIM (blade'de, harita JS'inden ÖNCE):
 *   <script src="/storage/shp/15_alti.js"></script>
 *   <script src="/storage/shp/15_ustu.js"></script>
 *   <script src="/js/maps-address.js"></script>
 */

'use strict';

/* ─── TR BÜYÜK HARF — İ/I krizi yok ─────────────────────────── */
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

/* ═══ GEOMETRİ YARDIMCILARI ═══
 * GeoJSON: coordinates = [longitude, latitude] (index 0=lng, 1=lat)
 */
function flatCoords(geometry) {
    const out = [];
    function walk(arr) {
        if (!Array.isArray(arr)) return;
        if (typeof arr[0] === 'number') { out.push(arr); return; }
        arr.forEach(walk);
    }
    walk(geometry && geometry.coordinates || []);
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

/* ═══ LOCAL SHP VERİSİ — 15_alti.js + 15_ustu.js ═══ */
let _tumCaddeler = null;
let _tumCaddelerMeta = null; // [ { feature, source, adiToplam, indexAlt } ]

/**
 * Cadde adını 15_alti/15_ustu properties'inden üretir.
 * DOĞRU ALAN: CADDE_SO_1 + CADDE_SO_2 (CADDE_SOKAK_ADI YOK!)
 */
function caddeAdi(props) {
    if (!props) return '';
    const p1 = String(props.CADDE_SO_1 || props.CADDE_SO_1 || '').trim();
    const p2 = String(props.CADDE_SO_2 || props.CAD_SOKAK_ADI || 'SOKAK').trim();
    const ad = (p1 + ' ' + p2).trim();
    return ad || String(props.CADDE_SOKA || '').trim();
}

/**
 * 15_alti + 15_ustu feature'larını birleştirip benzersiz cadde listesi kurar.
 * @returns {Array<{ad:string, indexAlt:number, indexUst:number, firstIndex:number}>}
 */
function buildTumCaddeler() {
    if (_tumCaddeler) return _tumCaddeler;

    const alti = (typeof EybAlti !== 'undefined' && EybAlti && EybAlti.features) ? EybAlti.features : [];
    const ustu = (typeof EybUstu !== 'undefined' && EybUstu && EybUstu.features) ? EybUstu.features : [];

    const all = [];
    alti.forEach(f => all.push({ feature: f, source: 'alti' }));
    ustu.forEach(f => all.push({ feature: f, source: 'ustu' }));

    if (!all.length) {
        console.warn('[AYKOME] 15_alti.js / 15_ustu.js yüklenmedi!');
        return [];
    }

    // İlgi alan adlarını doğrula
    const sample = all[0].feature.properties || {};
    console.log('[AYKOME] SHP property keys:', Object.keys(sample).slice(0, 12));
    console.log('[AYKOME] Örnek cadde adı:', caddeAdi(sample));

    // Unique cadde adları (aynı sokak birden fazla segment olabilir)
    const map = new Map(); // key=trUp(ad) → {ad, source, firstIndex}
    all.forEach(function (entry) {
        const ad = caddeAdi(entry.feature.properties);
        if (!ad) return;
        const key = trUp(ad);
        if (!map.has(key)) {
            map.set(key, {
                ad: ad,
                source: entry.source,
                firstIndex: map.size,
            });
        }
    });

    _tumCaddeler = [...map.values()].sort((a, b) => a.ad.localeCompare(b.ad, 'tr'));
    console.log('[AYKOME] Toplam benzersiz cadde/sokak:', _tumCaddeler.length);
    return _tumCaddeler;
}

/**
 * Nokta en yakın yolu bulur — 15m ALT mı/ÜST mü kararını üretir.
 * @param {number} lat
 * @param {number} lng
 * @param {number} maxDistKm (varsayılan 0.05 = 50m)
 * @returns {null|{source:string, caddeAdi:string, genislik:string, distKm:number}}
 */
function nearestRoadAnd15(lat, lng, maxDistKm = 0.05) {
    let best = null;
    let bestDist = maxDistKm;

    const files = [
        { name: 'alti', data: (typeof EybAlti !== 'undefined') ? EybAlti : null },
        { name: 'ustu', data: (typeof EybUstu !== 'undefined') ? EybUstu : null },
    ];

    files.forEach(function (fl) {
        if (!fl.data || !fl.data.features) return;
        fl.data.features.forEach(function (f) {
            const geom = f.geometry;
            if (!geom) return;
            const pts = flatCoords(geom);
            pts.forEach(function (p) {
                const d = haversineKm(lat, lng, p[1], p[0]);
                if (d < bestDist) {
                    bestDist = d;
                    best = {
                        source: fl.name,
                        cadde: caddeAdi(f.properties || {}),
                        mahalle: (f.properties && f.properties.MAHALLE_AD) || '',
                        genislik: (f.properties && f.properties.GENISLIGI) || '',
                        uzunluk: (f.properties && f.properties.UZUNLUGU) || '',
                    };
                }
            });
        });
    });

    return best;
}

function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return 2 * R * Math.asin(Math.sqrt(a));
}

/**
 * Mahalle BBOX içindeki caddeleri döner (LOCAL SHP — WFS yok).
 * @param {{minLng:number,minLat:number,maxLng:number,maxLat:number}} bbox
 * @param {number} tol derece toleransı (varsayılan 0.002 ≈ 200m)
 */
function caddelerInBbox(mahalleBbox, tol = 0.002) {
    const bb = mahalleBbox || {};
    const minLng = parseFloat(bb.minLng);
    const minLat = parseFloat(bb.minLat);
    const maxLng = parseFloat(bb.maxLng);
    const maxLat = parseFloat(bb.maxLat);
    if ([minLng, minLat, maxLng, maxLat].some(isNaN)) return [];

    const found = new Map(); // key=trUp(ad) → list item

    function scan(data, source) {
        if (!data || !data.features) return;
        data.features.forEach(function (f) {
            const geom = f.geometry;
            if (!geom) return;
            const ad = caddeAdi(f.properties || {});
            if (!ad) return;
            const pts = flatCoords(geom);
            const hit = pts.some(function (p) {
                return p[0] >= minLng - tol && p[0] <= maxLng + tol &&
                       p[1] >= minLat - tol && p[1] <= maxLat + tol;
            });
            if (hit) {
                const key = trUp(ad);
                if (!found.has(key)) {
                    found.set(key, {
                        ad: ad,
                        center: centroidOf(geom),
                        bbox: bboxOf(geom),
                        source: source,
                    });
                }
            }
        });
    }

    if (typeof EybAlti !== 'undefined') scan(EybAlti, 'alti');
    if (typeof EybUstu !== 'undefined') scan(EybUstu, 'ustu');

    const list = [...found.values()].sort((a, b) => a.ad.localeCompare(b.ad, 'tr'));
    console.log('[AYKOME] Mahalle bbox içinde', list.length, 'cadde/sokak');
    return list;
}

/**
 * Sokak numarası/adı ile cadde ara (local SHP).
 * @param {string} query örn: "8125" veya "EVREN 72"
 */
function sokakAra(query) {
    const all = buildTumCaddeler();
    const q = trUp(String(query || '').trim());
    if (!q) return null;

    const hits = all.filter(c => trUp(c.ad).includes(q));
    if (!hits.length) return null;

    // En tam eşleşme önce
    hits.sort((a, b) => {
        const aEx = trUp(a.ad) === q ? 0 : 1;
        const bEx = trUp(b.ad) === q ? 0 : 1;
        return aEx - bEx;
    });

    const best = hits[0];
    const geom = firstGeometryFor(best.ad);
    return {
        ad: best.ad,
        center: geom ? centroidOf(geom) : null,
        bbox: geom ? bboxOf(geom) : null,
        source: best.source,
    };
}

function trims(s) { return String(s || '').trim(); }

function firstFeatureFor(caddeAd) {
    const ad = caddeAd.trim();
    const srcs = [
        { name: 'alti', data: (typeof EybAlti !== 'undefined') ? EybAlti : null },
        { name: 'ustu', data: (typeof EybUstu !== 'undefined') ? EybUstu : null },
    ];
    for (let i = 0; i < srcs.length; i++) {
        const src = srcs[i];
        if (!src.data || !src.data.features) continue;
        const found = src.data.features.find(f => caddeAdi(f.properties) === ad);
        if (found) return { feature: found, source: src.name };
    }
    return null;
}

function firstGeometryFor(caddeAd) {
    const hit = firstFeatureFor(caddeAd);
    return hit ? hit.feature.geometry : null;
}

/* ═══ ADRES PARSE ═══ */
function parseAdres(raw) {
    raw = trims(raw);
    if (!raw) return { sokakNo: null, kapiNo: null, mahalleAdi: null, raw: '' };

    // Sokak numarası: "8125. Sk." / "8125 SOKAK" / "8125 SK" / "8125.SK"
    const sokakM =
        raw.match(/(\d{3,5})\s*\.?\s*(?:sk|sokak|cad|cadde)\.?/i) ||
        raw.match(/(\d{3,5})\s+(?:nolu\s+)?(?:sk|sokak|cad|cadde)/i);
    const sokakNo = sokakM ? sokakM[1] : null;

    // Kapı no: sokaktan sonraki ilk sayı VEYA "No:122"
    let kapiNo = null;
    if (sokakM) {
        const after = raw.slice(sokakM.index + sokakM[0].length);
        const kM = after.match(/^\s*\.?\s*(\d{1,4})\b/) ||
                   raw.match(/\bno\.?\s*:?\s*(\d{1,4})\b/i);
        kapiNo = kM ? kM[1] : null;
    }

    // Mahalle: "Kadıkendi" / "15 TEMMUZ" / "BATIKENT MAH"
    const mahM =
        raw.match(/([\wçğışöüÇĞİŞÖÜ]+(?:\s+[\wçğışöüÇĞİŞÖÜ]+)?)\s+(?:mah|mahalle|mahallesi)\b/i) ||
        raw.match(/\b([\wçğışöüÇĞİŞÖÜ]+)\s+mahallesinde\b/i);
    const mahalleAdi = mahM ? mahM[1].trim() : null;

    return { sokakNo, kapiNo, mahalleAdi, raw };
}

/* ═══ ANA ADRES ARAMA (çok katmanlı strateji) ═══ */
async function adresKonumBul(raw, wfsProxy = '/maps/adres-ara', csrfToken = '') {
    const parsed = parseAdres(raw);

    // STRATEJİ 1: Local SHP'de sokak no / ad ile ara
    if (parsed.sokakNo) {
        const hit = sokakAra(parsed.sokakNo);
        if (hit && hit.center) {
            return {
                lat: hit.center.lat,
                lng: hit.center.lng,
                label: hit.ad,
                method: '✅ Local SHP',
                confidence: 'high',
            };
        }
    }

    // STRATEJİ 2: WFS Laravel proxy (yedek)
    try {
        const res = await fetch(wfsProxy, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ adres: raw }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data && data.success) {
                return {
                    lat: data.lat,
                    lng: data.lon,
                    label: data.detail || data.cadde || raw,
                    method: '⚡ WFS Proxy',
                    confidence: data.confidence || 'medium',
                };
            }
        }
    } catch (e) {
        console.warn('[AYKOME] WFS proxy hatası:', e.message);
    }

    return null;
}

/* ═══ GLOBAL ERİŞİM ═══ */
window.AykomeMapsBuild = buildTumCaddeler;
window.aykomeSokakAra = sokakAra;
window.aykomeParseAdres = parseAdres;
window.aykomeCaddelerInBbox = caddelerInBbox;
window.aykome15mKontrol = nearestRoadAnd15;
window.aykomeCaddeAdi = caddeAdi;
window.aykomeAdresKonumBul = adresKonumBul;