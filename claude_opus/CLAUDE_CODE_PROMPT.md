Sen AYKOME ERP projesinin baş mimarısın. Şu an 3 kritik sorunu çözeceksin.

## MEVCUT SORUNLAR

1. Başvuruda mahalle seçilince cadde/sokak listesi boş geliyor ("Cadde bulunamadı")
   - WMS haritasında 8125 SOKAK görünüyor ama başvuruda aynı mahalle için çıkmıyor
   
2. Adres inputuna "8125. Sk. 122 Kadıkendi" yazılınca yanlış koordinata gidiyor

3. storage/shp/15_alti.js ve storage/shp/15_ustu.js dosyaları var ama kullanılmıyor
   - Bu dosyalar Eyyübiye'nin TÜM cadde/sokak geometrilerini içeriyor
   - WFS'e gerek kalmadan local olarak çalışabilir

## ADIM 1: SHP DOSYALARINI TANI

Önce bu dosyaları oku:
```
cat storage/shp/15_alti.js | head -3
```

`EybAlti.features[0].properties` içindeki alanları gör. Cadde adı hangi alanda?
(CADDE_SOKAK_ADI mı? ADI mı? NAME mı?)

Aynısını 15_ustu.js için yap:
```
cat storage/shp/15_ustu.js | head -3  
```

## ADIM 2: maps-address.js DOSYASINI OLUŞTUR

`public/js/maps-address.js` olarak şu dosyayı kopyala:
[maps-address.js içeriği buraya gelecek — Claude Code'a ayrıca ver]

Kritik noktalar:
- `buildTumCaddeler()` fonksiyonu EybAlti + EybUstu'yu birleştirir
- `_CAD_FIELD` otomatik tespit edilir (hangi properties alanı dolu ise)
- `caddelerInBbox(mahalleBbox)` mahalle bbox'ı içindeki caddeleri döner
- `sokakAra(query)` local SHP'de sokak arar

## ADIM 3: MapsController.php'yi GÜNCELLE

`/maps/sokak-caddeler` endpoint'inde bbox hesaplama düzeltmesi:

```php
// YANLIŞ (mevcut):
$bbox = $request->input('bbox'); // "38.75,37.13,38.80,37.16"
// Bu string doğrudan WFS'e gidiyor ama format yanlış olabilir

// DOĞRU:
// bbox GeoJSON formatında: [minLng, minLat, maxLng, maxLat]
// WFS BBOX formatı: "minLng,minLat,maxLng,maxLat,EPSG:4326"
```

`/maps/mahalleler` endpoint'inde bbox döndürme formatını düzelt:

```php
// Her mahalle için bbox dönerken hem object hem string gönder:
$result[] = [
    'ad'     => $ad,
    'center' => $center,  // { lat, lng }
    'bbox'   => [         // Object formatı (JS tarafı kullanır)
        'minLng' => $bboxArr[0],
        'minLat' => $bboxArr[1],
        'maxLng' => $bboxArr[2],
        'maxLat' => $bboxArr[3],
        'wfsBbox' => implode(',', $bboxArr), // "minLng,minLat,maxLng,maxLat"
    ],
];
```

## ADIM 4: BLADE DOSYASINI GÜNCELLE

Blade'e şu script tag'larını ekle (harita JS'inden ÖNCE):
```html
{{-- LOCAL SHP VERİSİ --}}
<script src="{{ asset('storage/shp/15_alti.js') }}"></script>
<script src="{{ asset('storage/shp/15_ustu.js') }}"></script>
{{-- ADRES ARAMA MODÜLÜ --}}
<script src="{{ asset('js/maps-address.js') }}"></script>
```

## ADIM 5: MAHALLEYİ SEÇİNCE CADDE YÜKLEME MANTIĞINI DEĞİŞTİR

Mevcut blade/JS'teki cadde yükleme kodunu bul ve değiştir.

MEVCUT (yanlış):
```javascript
// WFS'e POST atıyor, sonuç boş dönüyor
async function caddeleriGetir(mahalle) {
    const data = await apiPost('/maps/sokak-caddeler', { bbox: mahalle.bbox });
    // ...
}
```

YENİ (doğru):
```javascript
async function caddeleriGetir(mahalle) {
    // ÖNCE LOCAL SHP'de ara
    if (typeof EybAlti !== 'undefined' || typeof EybUstu !== 'undefined') {
        const bb = mahalle.bbox;
        // bbox object veya string olabilir, normalize et
        const bboxObj = (typeof bb === 'string')
            ? (parts => ({ 
                minLng: +parts[0], minLat: +parts[1],
                maxLng: +parts[2], maxLat: +parts[3]
              }))(bb.split(','))
            : bb;

        const caddeler = window.aykomeCaddelerInBbox(bboxObj);
        
        if (caddeler.length > 0) {
            // Select'i doldur
            const sel = document.getElementById('cadde-' + satirId);
            sel.innerHTML = `<option value="">— ${caddeler.length} cadde/sokak —</option>`;
            caddeler.forEach((c, i) => {
                const opt = document.createElement('option');
                opt.value = i;
                opt.dataset.centerLat = c.center?.lat;
                opt.dataset.centerLng = c.center?.lng;
                opt.dataset.bboxMinLng = c.bbox?.minLng;
                opt.dataset.bboxMinLat = c.bbox?.minLat;
                opt.dataset.bboxMaxLng = c.bbox?.maxLng;
                opt.dataset.bboxMaxLat = c.bbox?.maxLat;
                opt.textContent = c.ad;
                sel.appendChild(opt);
            });
            sel._caddeler = caddeler;
            sel.disabled = false;
            return caddeler;
        }
    }

    // YEDEK: WFS proxy
    const data = await apiPost('/maps/sokak-caddeler', { bbox: mahalle.bbox?.wfsBbox || mahalle.bbox });
    // ... mevcut kod
}
```

## ADIM 6: ADRES ARAMA MANTIĞINI GÜNCELLE

Mevcut `konumBul()` veya `adresAra()` fonksiyonunu bul ve güncelle:

```javascript
async function konumBul() {
    const raw = document.getElementById('adres').value.trim();
    if (!raw) return;

    setStatus('Aranıyor...', true);

    // YENİ: window.aykomeParseAdres kullan
    const parsed = window.aykomeParseAdres(raw);
    console.log('[konumBul] Parsed:', parsed);

    // STRATEJİ 1: Local SHP'de sokak numarasıyla ara
    if (parsed.sokakNo) {
        const hit = window.aykomeSokakAra(parsed.sokakNo);
        if (hit?.center) {
            flyToPoint(hit.center.lat, hit.center.lng, hit.ad, 18);
            setStatus('✅ Local SHP: ' + hit.ad);
            return;
        }
    }

    // STRATEJİ 2: WFS proxy
    try {
        const data = await apiPost('/maps/adres-ara', { adres: raw });
        if (data.success) {
            flyToPoint(data.lat, data.lng, data.label, 18);
            setStatus('✅ ' + data.method + ': ' + data.label);
            return;
        }
    } catch(e) {
        console.warn('WFS proxy hatası:', e);
    }

    setStatus('❌ Adres bulunamadı. Mahalle→Cadde seçin.');
}
```

## ADIM 7: TEST ET

Test senaryoları:
1. Console'da: `buildTumCaddeler()` → kaç cadde yükledi?
2. Console'da: `aykomeSokakAra('8125')` → sonuç ne?
3. Console'da: `aykomeCaddelerInBbox({minLng:38.73, minLat:37.12, maxLng:38.76, maxLat:37.16})` → kaç cadde?
4. BATIKENT mahallesi seçilince 8125 SOKAK listede çıkmalı
5. "8125. Sk. 122" girilince 37.136528, 38.741008 yakınına gitmeli

## KRİTİK NOTLAR

### GeoJSON Koordinat Sırası:
- GeoJSON: coordinates = [longitude, latitude] (index 0=lng, index 1=lat)
- Leaflet: [latitude, longitude] (index 0=lat, index 1=lng)
- Bu karışıklık "yanlış yere gidiyor" sorununun ana sebebi

### WFS bbox vs Leaflet bounds:
- WFS BBOX: "minLng,minLat,maxLng,maxLat" (X,Y sırası)
- Leaflet bounds: [[minLat,minLng],[maxLat,maxLng]] (Y,X sırası)
- MapsController.php'de bbox string'ini parse ederken dikkat!

### 15_alti.js Properties:
- Dosyayı oku, properties alanını bul
- maps-address.js otomatik tespit ediyor ama alan adını doğrula
- console.log(EybAlti.features[0].properties) ile kontrol et

### Mahalle ↔ Cadde BBOX Sorunu:
- WFS'ten gelen mahalle bbox'ı küçük olabilir
- caddelerInBbox'ta tolerans = 0.001 derece (~100m) kullanıyoruz
- Gerekirse 0.002'ye çıkar

## BAŞARI KRİTERİ

- BATIKENT mahallesi → 8125 SOKAK ve diğerleri görünmeli
- KADIKENDİ mahallesi → mahalle sokakları görünmeli  
- "8125. Sk. 122" → 37.136528, 38.741008 civarına pin atmalı
- "15 TEMMUZ MAHALLESİ 4162 SOKAK" → doğru yere gitmeli
