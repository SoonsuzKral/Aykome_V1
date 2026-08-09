/**
 * AYKOME — maps-address.js v2 (JSON TEK KAYNAK + GEOMETRİ KÖPRÜSÜ)
 * ═══════════════════════════════════════════════════════════
 * PRİMARY   : storage/shp/caddeler_ve_sokaklar.json (4196 kayıt) → /maps/cadde-veri
 *              İçerir: mahalle + cadde adı + SORUMLULUK (15m) + CADDE_SOKA köprüsü
 * GEOMETRİ  : /maps/15m/alti + /maps/15m/ustu (Laravel route → GeoJSON)
 *              → CADDE_SOKA köprüsüyle koordinat & nokta-atışı
 * SECONDARY : WFS (Laravel proxy /maps/adres-ara — yedek)
 *
 * 15m SEMANTİĞİ:
 *   - 15_alti dosyası = SORUMLULUK "15 METRE ALTI" yollar
 *   - 15_ustu dosyası = SORUMLULUK "15 METRE ÜSTÜ" yollar
 *   Nokta için en yakın yola bakılır; JSON'daki SORUMLULUK asıl kaynak.
 *
 * KULLANIM (blade'de, harita JS'inden ÖNCE):
 *   <script src="/js/maps-address.js"></script>
 *   ve kullanmadan önce:  await window.aykomeVeriHazir();
 */

'use strict';
window.EybAlti = window.EybAlti || null;
window.EybUsty = window.EybUsty || null;

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
        lat: pts.reduce((s, p) => s + p[1], 0) / pts.length,
        lng: pts.reduce((s, p) => s + p[0], 0) / pts.length
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

function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return 2 * R * Math.asin(Math.sqrt(a));
}

/* ═══ CADDE VERİ — /maps/cadde-veri (TEK KAYNAK) ═══ */
let _caddeVeri = null;
let _caddeVeriLoaded = false;

async function buildCaddeVeri(url = '/maps/cadde-veri') {
    if (_caddeVeriLoaded) return _caddeVeri;
    try {
        const resp = await fetch(url);
        const data = await resp.json();
        if (data && data.success && Array.isArray(data.data)) {
            _caddeVeri = data.data;
            _caddeVeriLoaded = true;
            console.log('[AYKOME] Cadde veri yüklendi:', _caddeVeri.length);
            return _caddeVeri;
        }
        console.warn('[AYKOME] cadde-veri yanıtı bozuk', data);
    } catch (e) {
        console.warn('[AYKOME] cadde-veri fetch hatası:', e.message);
    }
    return [];
}

/* ─── Mahalle bazlı cadde listesi (JSON) ──────────────────── */
function mahalleCaddeleri(mahalle) {
    const mh = trUp(mahalle || '').trim();
    if (!mh) return [];
    const kayitlar = _caddeVeri || [];
    return kayitlar.filter(r => trUp(r.mahalle || '') === mh);
}

/* ─── CADDE_SOKA → koordinat (geometri köprüsü) ───────────── */
function _geomIndex() {
    if (window._geomByIdx) return window._geomByIdx;
    const idx = {};
    const files = [
        { src: (typeof window.EybAlti !== 'undefined' && window.EybAlti) ? window.EybAlti : null, srcname: 'alti' },
        { src: (typeof window.EybUsty !== 'undefined' && window.EybUsty) ? window.EybUsty : null, srcname: 'ustu' },
    ];
    files.forEach(function (fl) {
        if (!fl.src || !fl.src.features) return;
        fl.src.features.forEach(function (f) {
            const cid = f.properties && f.properties.CADDE_SOKA;
            if (cid === undefined || cid === null) return;
            const key = String(cid).trim();
            if (!idx[key]) {
                idx[key] = {
                    source: fl.srcname,
                    geometry: f.geometry,
                    ad: (((f.properties && f.properties.CADDE_SO_1) || '') + ' ' + ((f.properties && f.properties.CADDE_SO_2) || '')).trim(),
                };
            }
        });
    });
    window._geomByIdx = idx;
    console.log('[AYKOM] Geometri index:', Object.keys(idx).length);
    return idx;
}

function caddeKoordinat(caddeSoka) {
    if (caddeSoka === null || caddeSoka === undefined || caddeSoka === '') return null;
    const idx = _geomIndex();
    const key = String(caddeSoka).trim();
    const hit = idx[key];
    if (!hit || !hit.geometry) return null;
    const c = centroidOf(hit.geometry);
    return c ? { lat: c.lat, lng: c.lng, source: hit.source, adi: hit.ad } : null;
}

function caddeAdi(props) {
    if (!props) return '';
    const p1 = String(props.CADDE_SO_1 || '').trim();
    const p2 = String(props.CADDE_SO_2 || '').trim();
    const ad = (p1 + ' ' + p2).trim();
    return ad || String(props.CADDE_SOKA || '').trim();
}

/* ─── Sokak detay: mahalle + cadde adı ile TAM JSON satırı + koordinat ── */
function normalizeCadde(s) {
    // "8013. SK", "8013.SOKAK", "16 ANADOLU CADDE" → normalize: nokta/sonekleri sil
    let v = trUp(String(s || '').trim())
        .replace(/\./g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    v = v.replace(/\b(SK|SOKAK|CADDE|CD|MAH|MAHALLE|YOL|BULVAR|MEYDAN)\b/gi, '').replace(/\s+/g, ' ').trim();
    v = v.replace(/(\d+)\s*(\d+)/g, '$1$2');
    return v;
}

function extractDigits(s) {
    const m = String(s || '').match(/\d+/g);
    return m ? m.join('') : '';
}

function sokakDetay(mahalle, caddeStr) {
    if (!caddeStr) return null;
    const rows = _caddeVeri || [];
    if (!rows.length) return null;

    const mU = trUp(mahalle || '').trim();
    const target = normalizeCadde(caddeStr);
    if (!target) return null;

    // Skor: 0 = mahalle+ad tam, 1 = mahalle+parça/sayı, 2 = yalnız cadde
    let best = null;
    let bestScore = null;
    for (let i = 0; i < rows.length; i++) {
        const r = rows[i];
        const adi = trUp(r.cadde_adi || '').trim();
        if (!adi) continue;
        const adNorm = normalizeCadde(adi);
        const mahHit = (mU === '' || trUp(r.mahalle || '').trim() === mU);
        const adHit = (adNorm === target);
        const containsHit = (adNorm.indexOf(target) !== -1 || target.indexOf(adNorm) !== -1);
        const numHit = (extractDigits(adNorm) !== '' && extractDigits(adNorm) === extractDigits(target));

        let score = null;
        if (mahHit && adHit) score = 0;
        else if (mahHit && (containsHit || numHit)) score = 1;
        else if (adHit) score = 2;
        else if (numHit) score = 2;

        if (score !== null && (bestScore === null || score < bestScore)) {
            bestScore = score;
            best = r;
            if (bestScore === 0) break;
        }
    }
    if (!best) return null;

    const coord = caddeKoordinat(best.cadde_soka);
    return {
        cadde_soka: best.cadde_soka,
        mahalle: best.mahalle,
        cadde_adi: best.cadde_adi,
        turu: best.turu,
        sorumluluk: best.sorumluluk,
        genislik: best.genislik,
        uzunluk: best.uzunluk,
        kaplama: best.kaplama,
        arter: best.arter,
        trafik_yolu: best.trafik_yolu,
        serit: best.serit,
        uavt_turu: best.uavt_turu,
        center: coord ? { lat: coord.lat, lng: coord.lng } : null,
        source: (best.sorumluluk || '').indexOf('ÜSTÜ') !== -1 ? 'ustu' : 'alti',
        bulunamadi: false,
    };
}

/* ─── Sokak ara: cadde_adi veya cadde_soka ile ────────────── */
function sokakAra(query) {
    const q = trUp(String(query || '').trim());
    if (!q) return null;
    const rows = _caddeVeri || [];
    const hits = rows.filter(r => {
        const adi = trUp(String(r.cadde_adi || ''));
        return adi.indexOf(q) !== -1;
    });
    if (!hits.length) return null;
    const best = hits[0];
    const coord = caddeKoordinat(best.cadde_soka);
    return {
        ad: best.cadde_adi,
        mahalle: best.mahalle,
        sorumluluk: best.sorumluluk,
        genislik: best.genislik,
        center: coord ? { lat: coord.lat, lng: coord.lng } : null,
    };
}

/* ─── Adres parse: "Kadıkendi, 4203. Sk." ─────────────────── */
function parseAdres(raw) {
    raw = String(raw || '').trim();
    if (!raw) return { mahalle: null, cadde: null, kapiNo: null, raw: '' };

    let mahalle = null;
    const mahM = raw.match(/([\wçğışöüÇĞİŞÖÜ]+(?:\s+[\wçğışöüÇĞİŞÖÜ]+)?)\s+(?:mah|mahalle|mahallesi)\b/i);
    if (mahM) {
        mahalle = mahM[1].trim().toUpperCase();
    } else if (raw.indexOf(',') > -1) {
        const ilk = raw.split(',')[0].trim();
        if (ilk && !/\d/.test(ilk)) mahalle = ilk.toUpperCase();
    }

    let cadde = null;
    const caddeM = raw.match(/(\d{2,4})\s*\.?\s*(?:sk|sokak|cad|cadde)\.?/i);
    if (caddeM) cadde = caddeM[1];

    let kapiNo = null;
    if (caddeM) {
        const after = raw.slice(caddeM.index + caddeM[0].length);
        const kM = after.match(/^\s*\.?\s*(\d{1,4})\b/) || raw.match(/\bno\.?\s*:?\s*(\d{1,4})\b/i);
        kapiNo = kM ? kM[1] : null;
    }

    return { mahalle, cadde, kapiNo, raw };
}

/* ─── En yakın yol + 15m kararı (geometri + JSON SORUMLULUK) ── */
function nearestRoadAnd15(lat, lng, maxDistKm = 0.06) {
    let best = null;
    let bestDist = maxDistKm;
    const files = [
        { data: (window.EybAlti && window.EybAlti.features) ? window.EybAlti : null, src: 'alti' },
        { data: (window.EybUsty && window.EybUsty.features) ? window.EybUsty : null, src: 'ustu' },
    ];
    files.forEach(function (fl) {
        if (!fl.data) return;
        fl.data.features.forEach(function (f) {
            const pts = flatCoords(f.geometry);
            pts.forEach(function (p) {
                const d = haversineKm(lat, lng, p[1], p[0]);
                if (d < bestDist) {
                    bestDist = d;
                    best = {
                        source: fl.src,
                        caddeSoka: f.properties ? f.properties.CADDE_SOKA : null,
                        mahalle: f.properties ? (f.properties.MAHALLE_AD || '') : '',
                        cadde: ((f.properties && f.properties.CADDE_SO_1) || '') + ' ' + ((f.properties && f.properties.CADDE_SO_2) || ''),
                        genislik: f.properties ? (f.properties.GENISLIGI || '') : '',
                    };
                }
            });
        });
    });
    if (!best) return null;

    // JSON'dan SORUMLULUK (15m) asıl kaynak — geometri sadece yedek
    let sorumluluk = null;
    if (best.caddeSoka !== null && best.caddeSoka !== undefined && (_caddeVeri || []).length) {
        const row = _caddeVeri.find(r => String(r.cadde_soka) === String(best.caddeSoka));
        if (row) {
            sorumluluk = row.sorumluluk;
            best.genislik = row.genislik || best.genislik;
            best.mahalle = row.mahalle || best.mahalle;
            best.cadde = row.cadde_adi || best.cadde;
        }
    }
    if (sorumluluk) {
        best.source = sorumluluk.indexOf('ÜSTÜ') !== -1 ? 'ustu' : 'alti';
    }

    return {
        source: best.source,          // 'alti' | 'ustu'
        caddeSoka: best.caddeSoka,
        mahalle: best.mahalle,
        cadde: best.cadde.trim(),
        genislik: best.genislik,
        sorumluluk: sorumluluk || (best.source === 'alti' ? '15 METRE ALTI' : '15 METRE ÜSTÜ'),
        distKm: bestDist,
    };
}

/* ─── Mahalle BBOX → cadde/sokak listesi (JSON + geometri) ── */
function caddelerInBbox(mahalleObj, tol = 0.004) {
    // mahalleObj: { ad, bbox:{minLng..} } ya da doğrudan bbox objesi
    const bb = (mahalleObj && mahalleObj.bbox) ? mahalleObj.bbox : mahalleObj;
    const minLng = parseFloat(bb && bb.minLng);
    const minLat = parseFloat(bb && bb.minLat);
    const maxLng = parseFloat(bb && bb.maxLng);
    const maxLat = parseFloat(bb && bb.maxLat);
    if ([minLng, minLat, maxLng, maxLat].some(isNaN)) return [];

    const adMahalle = (mahalleObj && mahalleObj.ad) ? trUp(mahalleObj.ad).trim() : '';
    const pad = 0.003; // ~300m bbox genişletme

    function inBox(lng, lat) {
        return lng >= minLng - pad && lng <= maxLng + pad &&
               lat >= minLat - pad && lat <= maxLat + pad;
    }

    const mapped = new Map();
    function putAd(name, lat, lon, extra) {
        if (!name) return;
        const k = trUp(name);
        if (!mapped.has(k)) {
            mapped.set(k, { name: name, lat: (lat === null || isNaN(lat)) ? null : lat, lon: (lon === null || isNaN(lon)) ? null : lon, ...(extra || {}) });
        }
    }

    // 1) JSON — mahalle ismiyle TAM liste (BatiKENT 8013 dahil)
    const kayitlar = _caddeVeri || [];
    if (adMahalle) {
        kayitlar.forEach(function (r) {
            if (trUp(r.mahalle || '').trim() !== adMahalle) return;
            const coord = caddeKoordinat(r.cadde_soka);
            putAd(r.cadde_adi, coord ? coord.lat : null, coord ? coord.lng : null, {
                mahalle: r.mahalle, sorumluluk: r.sorumluluk, genislik: r.genislik,
            });
        });
    }

    // 2) Geometri — BBOX içi yollar (JSON'da yoksa ad + koordinat)
    const files = [
        { src: window.EybAlti, source: 'alti' },
        { src: window.EybUsty, source: 'ustu' },
    ];
    files.forEach(function (fl) {
        if (!fl.src || !fl.src.features) return;
        fl.src.features.forEach(function (f) {
            const cid = f.properties && f.properties.CADDE_SOKA;
            const adi = caddeAdi(f.properties || {});
            if (!adi) return;
            const pts = flatCoords(f.geometry);
            const hit = pts.some(function (p) { return inBox(p[0], p[1]); });
            if (!hit) return;
            if (cid !== null && cid !== undefined && (kayitlar || []).length) {
                const row = kayitlar.find(function (r) { return String(r.cadde_soka) === String(cid); });
                if (row) { putAd(row.cadde_adi || adi, null, null, { mahalle: row.mahalle, sorumluluk: row.sorumluluk }); return; }
            }
            const c = centroidOf(f.geometry);
            putAd(adi, c ? c.lat : null, c ? c.lng : null);
        });
    });

    const list = [...mapped.values()].filter(function (x) { return x.name; })
        .sort(function (a, b) { return (a.name || '').localeCompare(b.name || '', 'tr'); });
    return list;
}

/* ─── Ana adres arama (yerel → WFS) ─── */
async function adresKonumBul(raw, wfsProxy = '/maps/adres-ara', csrfToken = '') {
    await aykomeVeriHazir();

    const parsed = parseAdres(raw);

    if (parsed.cadde) {
        const hit = sokakAra(parsed.cadde);
        if (hit && hit.center) {
            return {
                lat: hit.center.lat, lng: hit.center.lng,
                label: hit.ad + (hit.mahalle ? ' — ' + hit.mahalle : ''),
                method: 'Local JSON', confidence: 'high',
            };
        }
    }

    try {
        const res = await fetch(wfsProxy, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ adres: raw }),
        });
        if (res.ok) {
            const data = await res.json();
            if (data && data.success) {
                return { lat: data.lat, lng: data.lon, label: data.detail || data.cadde || raw, method: 'WFS', confidence: data.confidence || 'medium' };
            }
        }
    } catch (e) { console.warn('[AYKOM] WFS hatası:', e.message); }

    return null;
}

/* ═══ BİRLEŞİK VERİ HAZIRLIK (tek async) ═══ */
let _veriYukleme = null;
function aykomeVeriHazir() {
    if (!_veriYukleme) {
        _veriYukleme = (async function () {
            await Promise.all([
                buildCaddeVeri(),
                fetch('/maps/15m/alti').then(function (r) { return r.json(); }).then(function (d) { window.EybAlti = d; return d; }).catch(function () { return null; }),
                fetch('/maps/15m/ustu').then(function (r) { return r.json(); }).then(function (d) { window.EybUsty = d; return d; }).catch(function () { return null; }),
            ]);
            window._geomByIdx = null;
            _geomIndex();
            return true;
        }());
    }
    return _veriYukleme;
}

/* ═══ GLOBAL ERİŞİM (yeni isimler + blade beklediği eski adlar) ═══ */
window.buildCaddeVeri = buildCaddeVeri;
window.mahalleCaddeleri = mahalleCaddeleri;
window.caddeKoordinat = caddeKoordinat;
window.sokakAra = sokakAra;
window.sokakDetay = sokakDetay;
window.parseAdres = parseAdres;
window.adresKonumBul = adresKonumBul;
window.aykomeTrUp = trUp;
window.caddeAdi = caddeAdi;
window.nearestRoadAnd15 = nearestRoadAnd15;
window.caddelerInBbox = caddelerInBbox;

// Blade'lerin çağırdığı isimler (kritik!):
window.aykome15mKontrol = function (lat, lng, maxDistKm) { return nearestRoadAnd15(lat, lng, maxDistKm); };
window.aykomeCaddelerInBbox = function (bboxObj, tol) { return caddelerInBbox(bboxObj, tol); };
window.aykomeSokakAra = sokakAra;
window.aykomeSokakDetay = sokakDetay;
window.aykomeParseAdres = parseAdres;
window.aykomeCaddeAdi = caddeAdi;
window.aykomeAdresKonumBul = adresKonumBul;
window.AykomeMapsBuild = function () { return caddelerInBbox; };

// AykomeManzara hazır promise (blade'de await)
window.aykomeVeriHazir = aykomeVeriHazir;
window.aykomeVeriYukle = aykomeVeriHazir;

// Otomatik başlat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { aykomeVeriHazir(); });
} else {
    aykomeVeriHazir();
}