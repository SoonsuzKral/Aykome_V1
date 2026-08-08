# AYKOME ADRES SİSTEMİ — TAM HATA ANALİZİ VE ÇÖZÜM PLANI
# Claude Code'a Gönderilecek Prompt

---

## MEVCUT DURUM ANALİZİ (Ekran görüntülerinden tespit)

### SORUN 1: Başvuruda Mahalle Seçilince Cadde/Sokak Çıkmıyor
- WMS harita katmanında 8125 SOKAK, 8123 SOKAK vb. görünüyor
- Ama aynı mahalle seçildiğinde başvuru formunda "Cadde bulunamadı" yazıyor
- KUZEY SEBEBI: WFS BBOX sorgusunda mahalle polygon koordinatları yanlış hesaplanıyor
  veya CQL_FILTER'da mahalle adı büyük/küçük harf eşleşmiyor

### SORUN 2: 15_alti.js ve 15_ustu.js Kullanılmıyor
- storage/shp/15_alti.js → Eyyübiye 15 metre ALTI cadde/sokak geometrileri (LineString)
- storage/shp/15_ustu.js → Eyyübiye 15 metre ÜSTÜ cadde/sokak geometrileri (LineString)
- Bu dosyalar WFS'e gerek kalmadan LOCAL olarak cadde listesi verebilir!
- Şu an bu dosyalar tamamen görmezden geliniyor

### SORUN 3: Tek Adres Input Yanlış Konuma Gidiyor
- "8125. Sk. 122 Kadıkendi" girilince başka yere gidiyor
- Parse algoritması sokak numarasını yanlış çıkarıyor
- CORS proxy (allorigins) bazen timeout yapıyor, yanlış sonuç dönüyor

---

## ÇÖZÜM MİMARİSİ

### Öncelik Sırası:
1. 15_alti.js / 15_ustu.js'i PRIMARY cadde kaynağı olarak kullan
2. WFS'i SECONDARY (yedek) kaynak olarak bırak
3. Adres parse algoritmasını güçlendir

---

## CLAUDE CODE PROMPT (Bunu Claude Code'a ver)

```
Sen AYKOME ERP projesinin baş mimarısın. Şu an 3 kritik sorunu çözmemiz gerek:

## PROJE YAPISI
- storage/shp/15_alti.js → var EybAlti = {"type":"FeatureCollection","features":[...]} 
  Her feature: LineString geometry + properties (CADDE_SOKAK_ADI veya benzeri alan)
- storage/shp/15_ustu.js → var EybUstu = {"type":"FeatureCollection","features":[...]}
  Aynı yapı, 15m üstü sokaklar
- Bu iki dosya Eyyübiye'nin TÜM cadde/sokak geometrilerini içeriyor
- WMS endpoint: https://geo3.sanliurfa.bel.tr:8091/geoserver/wms (harita görseli için)
- WFS endpoint: https://geo3.sanliurfa.bel.tr:8091/geoserver/wfs (CORS sorunlu, proxy gerekiyor)

## SORUN 1: Cadde/Sokak Listesi Boş Geliyor

Şu anki yanlış yaklaşım:
- WFS'e BBOX ile MISMAP_CADDE_SOKAK sorgusu atılıyor
- CORS hatası veya boş sonuç dönüyor

DOĞRU YAKLAŞIM:
1. 15_alti.js ve 15_ustu.js dosyalarını JavaScript'e import et (script tag ile)
2. Bu local GeoJSON verilerini kullanarak cadde listesi oluştur
3. Mahalle seçildiğinde → mahalle polygon'unun BBOX'ı içindeki LineString'leri filtrele
4. Bu yaklaşımda CORS yok, internet yok, tamamen local çalışır

Mahalle BBOX içinde cadde filtresi algoritması:
```javascript
function getCaddelerInMahalle(mahalleBbox, allCaddeler) {
    // mahalleBbox = [minLng, minLat, maxLng, maxLat]
    return allCaddeler.filter(cadde => {
        if (!cadde.geometry || !cadde.geometry.coordinates) return false;
        // LineString'in herhangi bir noktası mahalle bbox içinde mi?
        const coords = cadde.geometry.type === 'LineString' 
            ? cadde.geometry.coordinates 
            : cadde.geometry.coordinates.flat();
        
        return coords.some(([lng, lat]) => 
            lng >= mahalleBbox[0] && lng <= mahalleBbox[2] &&
            lat >= mahalleBbox[1] && lat <= mahalleBbox[3]
        );
    });
}
```

## SORUN 2: Mahalle BBOX Hesaplama Yanlış

Şu anki sorun: WFS'ten gelen mahalle polygon'u için bbox hesaplanırken 
koordinat sırası karışıyor (lng/lat vs lat/lng).

GeoJSON standardı: coordinates = [longitude, latitude] sırası
Ama Leaflet/WMS bazen lat/lng bekliyor.

DOĞRU centroid ve bbox fonksiyonu:
```javascript
// GeoJSON koordinatlar [lng, lat] formatında gelir
function getBboxFromGeojson(geometry) {
    const coords = [];
    function extract(c) {
        if (typeof c[0] === 'number') { coords.push(c); return; }
        c.forEach(extract);
    }
    extract(geometry.coordinates);
    
    const lngs = coords.map(c => c[0]); // index 0 = longitude
    const lats  = coords.map(c => c[1]); // index 1 = latitude
    
    return {
        minLng: Math.min(...lngs),
        minLat: Math.min(...lats),
        maxLng: Math.max(...lngs),
        maxLat: Math.max(...lats),
        // WFS BBOX string: minX,minY,maxX,maxY (X=lng, Y=lat)
        wfsBbox: `${Math.min(...lngs)},${Math.min(...lats)},${Math.max(...lngs)},${Math.max(...lats)}`,
        // Leaflet bounds: [[minLat,minLng],[maxLat,maxLng]]
        leafletBounds: [[Math.min(...lats), Math.min(...lngs)], [Math.max(...lats), Math.max(...lngs)]]
    };
}

function getCentroid(geometry) {
    const coords = [];
    function extract(c) {
        if (typeof c[0] === 'number') { coords.push(c); return; }
        c.forEach(extract);
    }
    extract(geometry.coordinates);
    return {
        lat: coords.reduce((s, c) => s + c[1], 0) / coords.length, // c[1] = lat
        lng: coords.reduce((s, c) => s + c[0], 0) / coords.length  // c[0] = lng
    };
}
```

## SORUN 3: Tek Adres Inputu Parse Algoritması Zayıf

Şu anki sorun: "8125. Sk. 122" girilince sokak numarasını doğru çıkarıyor
ama 15_alti.js veya 15_ustu.js'te CADDE_SOKAK_ADI alanı ne isimde?

YAPILACAK:
1. Önce 15_alti.js içindeki bir feature'ın properties'ini console.log ile yazdır
2. Alan adını öğren (CADDE_SOKAK_ADI mı? ADI mı? NAME mı?)
3. Ona göre arama yap

Güçlendirilmiş parse algoritması:
```javascript
function parseAdres(raw) {
    raw = raw.trim();
    
    // Pattern 1: "8125. Sk. 122" veya "8125.Sk.122" veya "8125 SK 122"
    const p1 = raw.match(/(\d{3,5})\s*\.?\s*(?:sk|sokak|cad|cadde)\.?\s*(?:no\.?\s*)?(\d+)?/i);
    
    // Pattern 2: "8125 SOKAK NO:122"
    const p2 = raw.match(/(\d{3,5})\s+(?:sokak|cadde|sk|cad)\s+(?:no:?\s*)?(\d+)/i);
    
    // Pattern 3: Sadece sokak adı "8125 SOKAK"
    const p3 = raw.match(/(\d{3,5})\s*\.?\s*(?:sk|sokak|cad|cadde)/i);

    // Mahalle: "Kadıkendi" veya "15 TEMMUZ" veya "BATIKENT"
    const mahM = raw.match(/([\wçğışöüÇĞİŞÖÜ]+(?:\s+[\wçğışöüÇĞİŞÖÜ]+)?)\s+(?:mah|mahalle|mahallesi)/i);
    
    return {
        sokakNo: (p1 || p2 || p3)?.[1] || null,
        kapiNo:  (p1 || p2)?.[2] || null,
        mahalleAdi: mahM?.[1]?.trim() || null,
        // Tüm metni de sakla (fallback için)
        raw
    };
}
```

## YAPILACAKLAR LİSTESİ (Öncelik sırasıyla)

### ADIM 1: 15_alti.js ve 15_ustu.js'i incele
```javascript
// Hangi dosyada ne var öğren:
console.log('15_alti feature count:', EybAlti.features.length);
console.log('Sample properties:', EybAlti.features[0].properties);
console.log('Sample geometry type:', EybAlti.features[0].geometry.type);
```

### ADIM 2: MapsController.php'ye local SHP desteği ekle
Veya frontend'de direkt kullan:

```javascript
// public/js/maps-local.js olarak kaydet
// 15_alti.js ve 15_ustu.js'i birleştir
const TUM_CADDELER = [
    ...(typeof EybAlti !== 'undefined' ? EybAlti.features : []),
    ...(typeof EybUstu !== 'undefined' ? EybUstu.features : [])
];

// Unique cadde adlarını çıkar
function getAllCaddeNames() {
    const names = new Map();
    TUM_CADDELER.forEach(f => {
        const props = f.properties;
        // Alan adını bul (hangisi dolu ise)
        const ad = props.CADDE_SOKAK_ADI || props.ADI || props.NAME || props.ad || null;
        if (!ad) return;
        
        const key = ad.toUpperCase().replace(/I/g,'İ'); // TR normalize
        if (!names.has(key)) {
            names.set(key, {
                ad,
                geometry: f.geometry,
                // Centroid hesapla
                center: getCentroid(f.geometry)
            });
        }
    });
    return [...names.values()].sort((a,b) => a.ad.localeCompare(b.ad, 'tr'));
}
```

### ADIM 3: Mahalle seçince cadde filtresi (WFS YERİNE LOCAL)
```javascript
async function caddeleriYukle_LOCAL(mahalleBbox) {
    // mahalleBbox = {minLng, minLat, maxLng, maxLat}
    
    const filtered = TUM_CADDELER.filter(f => {
        if (!f.geometry?.coordinates) return false;
        
        let coords = [];
        if (f.geometry.type === 'LineString') {
            coords = f.geometry.coordinates;
        } else if (f.geometry.type === 'MultiLineString') {
            coords = f.geometry.coordinates.flat();
        }
        
        // En az bir koordinat mahalle bbox içinde olsun
        return coords.some(([lng, lat]) =>
            lng >= mahalleBbox.minLng - 0.001 &&  // küçük tolerans
            lng <= mahalleBbox.maxLng + 0.001 &&
            lat >= mahalleBbox.minLat - 0.001 &&
            lat <= mahalleBbox.maxLat + 0.001
        );
    });

    // Unique isimler
    const unique = new Map();
    filtered.forEach(f => {
        const props = f.properties;
        const ad = props.CADDE_SOKAK_ADI || props.ADI || props.NAME || null;
        if (!ad) return;
        if (!unique.has(ad)) {
            unique.set(ad, { ad, geometry: f.geometry, center: getCentroid(f.geometry) });
        }
    });

    return [...unique.values()].sort((a,b) => a.ad.localeCompare(b.ad, 'tr'));
}
```

### ADIM 4: WFS mahalle sorgusunu düzelt (Mahalle listesi için WFS hâlâ lazım)

WFS çalışmıyorsa alternatif: WMS GetFeatureInfo ile mahalle al.
Haritayı Eyyübiye merkezine zoom'la, tüm mahalle centroidlerini tarayarak 
GetFeatureInfo ile mahalle isimlerini çek:

```javascript
// Eyyübiye mahallelerini WMS GetFeatureInfo ile çekme stratejisi:
// 1. Sabit Eyyübiye mahalle listesini hardcode et (değişmez veri)
// 2. WFS çalışırsa override et
// 3. WFS çalışmazsa hardcode liste kullan

const EYYUBIYE_MAHALLE_HARDCODE = [
    "15 TEMMUZ MAHALLESİ",
    "AKBAYIR MAHALLESİ", 
    "ALİ BABA MAHALLESİ",
    "BAHÇELIEVLER MAHALLESİ",
    "BARIŞ MAHALLESİ",
    "BATIKENT MAHALLESİ",
    "CAMIKEBIR MAHALLESİ",
    "CANKAYA MAHALLESİ",
    "DAĞKENT MAHALLESİ",
    "DEMOKRASİ MAHALLESİ",
    "DOĞUKENT MAHALLESİ",
    "ESENLİK MAHALLESİ",
    "ESENTEPE MAHALLESİ",
    "GÜZELYURT MAHALLESİ",
    "HALEPLİBARÇE MAHALLESİ",
    "KADIKENDİ MAHALLESİ",
    "KARAKÖPRÜ MAHALLESİ",
    "KARŞIYAKA MAHALLESİ",
    "NARLIPINAR MAHALLESİ",
    "TURGUT ÖZAL MAHALLESİ",
    "YEŞİLYURT MAHALLESİ",
    // WFS'ten tüm listeyi çek ve buraya ekle
];
```

### ADIM 5: Adres input → koordinat (Geliştirilmiş)
```javascript
async function adresKonumBul(raw) {
    const parsed = parseAdres(raw);
    
    // STRATEJİ 1: Local SHP verisinde sokak ara
    if (parsed.sokakNo) {
        const matches = TUM_CADDELER.filter(f => {
            const props = f.properties;
            const ad = props.CADDE_SOKAK_ADI || props.ADI || props.NAME || '';
            return ad.includes(parsed.sokakNo);
        });
        
        if (matches.length > 0) {
            // Mahalle bilgisi varsa en yakın olanı seç
            const best = matches[0];
            const center = getCentroid(best.geometry);
            return { lat: center.lat, lng: center.lng, label: best.properties.CADDE_SOKAK_ADI || parsed.sokakNo + ' SOKAK', confidence: 'high', method: 'local_shp' };
        }
    }
    
    // STRATEJİ 2: WFS ile ara (CORS proxy üzerinden)
    // ... mevcut WFS kodu
    
    // STRATEJİ 3: WMS GetFeatureInfo (son çare)
    // ... 
}
```

---

## ÖZET: CLAUDE CODE'UN YAPACAKLARI

1. `storage/shp/15_alti.js` ve `storage/shp/15_ustu.js` dosyalarını oku
   - İlk feature'ın properties alanlarını console.log et
   - CADDE_SOKAK_ADI alanının adını kesinleştir

2. `public/js/maps-address.js` (yeni dosya) oluştur:
   - TUM_CADDELER array'ini 15_alti + 15_ustu'dan oluştur
   - getCaddesInBbox(bbox) fonksiyonu yaz
   - parseAdres(raw) fonksiyonu yaz  
   - adresKonumBul(raw) async fonksiyonu yaz

3. `MapsController.php` güncelle:
   - /maps/mahalleler endpoint'i → WFS çalışmazsa hardcode liste dönsün
   - /maps/sokak-caddeler endpoint'i → PHP tarafında 15_alti.js parse et veya WFS kullan
   - bbox hesaplamada lng/lat sırasını doğrula

4. Blade view güncelle:
   - 15_alti.js ve 15_ustu.js'i script tag ile yükle
   - Mahalle seçince WFS YERİNE local getCaddesInBbox() kullan
   - Adres inputu için geliştirilmiş parseAdres() kullan

5. Test senaryosu:
   - "BATIKENT" mahallesi seçildiğinde 8125 SOKAK listede çıkmalı
   - "8125. Sk. 122" inputu → 37.136528, 38.741008 koordinatı bulmalı
   - "15 TEMMUZ MAHALLESİ" seçildiğinde o mahallenin sokakları çıkmalı
```

---

## TEKNİK NOTLAR CLAUDE CODE İÇİN

### Koordinat Sistemi Notu:
- GeoJSON: [longitude, latitude] (X,Y)
- Leaflet: [latitude, longitude] (Y,X)
- WFS BBOX: minX,minY,maxX,maxY = minLng,minLat,maxLng,maxLat
- Karışıklık buradan kaynaklanıyor!

### CORS Sorunu:
- WFS: CORS engelliyor → Laravel proxy şart
- WMS: CORS yok (tile/image) → direkt çalışıyor
- 15_alti.js / 15_ustu.js: Local dosya → CORS yok, en hızlı çözüm

### 15 Metre Mantığı:
- 15_alti.js = İnsan yürüyüşüyle 15 dakikada ulaşılabilir mesafe altı sokaklar (veya 15m genişlik altı)
- 15_ustu.js = Ana arterler/büyük caddeler (15m üstü genişlik)
- Adres aramada her ikisini de kullan ama önce 15_alti'yi dene

### Mahalle ↔ Cadde Eşleşmesi Sorunu:
- WMS'de "BATIKENT" mahallesi görünüyor ama WFS'te "BATIKENT MAHALLESİ" olarak geçiyor
- String normalizasyonu şart: trim(), toUpperCase() ile Türkçe karakter dönüşümü

---

## BAŞARI KRİTERİ

Test adresi: 37.136528, 38.741008 (8125. Sk. 122 Kadıkendi, Eyyübiye/Şanlıurfa)
- [x] Mahalle listesinde "KADIKENDİ" görünmeli
- [x] Kadıkendi seçilince 8125 SOKAK listede çıkmalı  
- [x] "8125. Sk. 122" yazılınca 37.136528, 38.741008'e işaret etmeli
- [x] BATIKENT seçilince 8125 SOKAK ve diğerleri çıkmalı
