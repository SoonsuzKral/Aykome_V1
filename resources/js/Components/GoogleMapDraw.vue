<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    apiKey: { type: String, default: '' },
    strokeColor: { type: String, default: '#DC2626' },
    geojson: { type: String, default: '' },
    areaM2: { type: [Number, String], default: 0 },
});

const emit = defineEmits(['update:geojson', 'update:areaM2', 'update:center']);

const mapRef = ref(null);
const geoLocal = ref(props.geojson || '');
const areaLocal = ref(props.areaM2 || 0);
const lat = ref('');
const lng = ref('');

watch(geoLocal, (v) => emit('update:geojson', v));
watch(areaLocal, (v) => emit('update:areaM2', parseFloat(v) || 0));

watch(
    () => props.geojson,
    (v) => {
        if (v !== geoLocal.value) {
            geoLocal.value = v || '';
        }
    },
);

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

onMounted(async () => {
    if (!props.apiKey || !mapRef.value) {
        return;
    }
    await loadScript(
        `https://maps.googleapis.com/maps/api/js?key=${props.apiKey}&libraries=drawing,geometry`,
    );

    const center = { lat: 39.92, lng: 32.85 };
    const map = new google.maps.Map(mapRef.value, {
        center,
        zoom: 14,
    });

    const drawing = new google.maps.drawing.DrawingManager({
        drawingMode: null,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: [
                google.maps.drawing.OverlayType.POLYGON,
                google.maps.drawing.OverlayType.RECTANGLE,
            ],
        },
        polygonOptions: {
            fillColor: props.strokeColor,
            strokeColor: props.strokeColor,
            editable: true,
        },
        rectangleOptions: {
            fillColor: props.strokeColor,
            strokeColor: props.strokeColor,
            editable: true,
        },
    });
    drawing.setMap(map);

    let activeOverlay = null;

    google.maps.event.addListener(drawing, 'overlaycomplete', (e) => {
        if (activeOverlay) {
            activeOverlay.setMap(null);
        }
        activeOverlay = e.overlay;
        drawing.setDrawingMode(null);
        syncFromOverlay(e.overlay, map);
    });

    function syncFromOverlay(overlay, mapInstance) {
        if (overlay.getPath) {
            const path = overlay.getPath();
            const coords = [];
            path.forEach((latLng) =>
                coords.push([latLng.lng(), latLng.lat()]),
            );
            const closed = [...coords, coords[0]];
            const feature = {
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [closed],
                },
            };
            geoLocal.value = JSON.stringify(feature);
            const area = google.maps.geometry.spherical.computeArea(path);
            areaLocal.value = Math.round(area * 100) / 100;
            emit('update:areaM2', areaLocal.value);
        } else if (overlay.getBounds) {
            const b = overlay.getBounds();
            const ne = b.getNorthEast();
            const sw = b.getSouthWest();
            const feature = {
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [
                        [
                            [sw.lng(), sw.lat()],
                            [ne.lng(), sw.lat()],
                            [ne.lng(), ne.lat()],
                            [sw.lng(), ne.lat()],
                            [sw.lng(), sw.lat()],
                        ],
                    ],
                },
            };
            geoLocal.value = JSON.stringify(feature);
            const path = [
                { lat: sw.lat(), lng: sw.lng() },
                { lat: sw.lat(), lng: ne.lng() },
                { lat: ne.lat(), lng: ne.lng() },
                { lat: ne.lat(), lng: sw.lng() },
            ];
            const area = google.maps.geometry.spherical.computeArea(path);
            areaLocal.value = Math.round(area * 100) / 100;
            emit('update:areaM2', areaLocal.value);
        }
        const c = mapInstance.getCenter();
        lat.value = c.lat().toFixed(6);
        lng.value = c.lng().toFixed(6);
        emit('update:center', { lat: parseFloat(lat.value), lng: parseFloat(lng.value) });
    }
});
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="apiKey"
            ref="mapRef"
            class="h-80 w-full rounded-md border border-gray-200 shadow-sm"
        />
        <p
            v-else
            class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
        >
            Google Maps API anahtarı `.env` içinde tanımlı değil. Alan bilgisini manuel girebilirsiniz.
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <InputLabel value="GeoJSON (çizim veya yapıştırma)" />
                <textarea
                    v-model="geoLocal"
                    rows="5"
                    class="mt-1 w-full rounded-md border-gray-300 font-mono text-xs shadow-sm"
                />
            </div>
            <div>
                <InputLabel value="Alan (m²)" />
                <input
                    v-model="areaLocal"
                    type="number"
                    step="any"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
        </div>
    </div>
</template>
