<?php

// ============================================================
// routes/web.php veya routes/api.php içine ekle
// ============================================================

use App\Http\Controllers\MapsController;
use Illuminate\Support\Facades\Route;

// WFS Proxy Endpointleri (CSRF korumalı, auth middleware eklenebilir)
Route::prefix('maps')->name('maps.')->group(function () {

    // Mahalle listesi (GET — cache'li, hızlı)
    Route::get('/mahalleler', [MapsController::class, 'mahalleler'])
        ->name('mahalleler');

    // Tek mahalle arama
    Route::get('/mahalle-bul', [MapsController::class, 'mahalleBul'])
        ->name('mahalle-bul');

    // Cadde/Sokak listesi (POST — bbox gerekli)
    Route::post('/sokak-caddeler', [MapsController::class, 'sokakCaddeler'])
        ->name('sokak-caddeler');

    // Kapı numaraları (POST — bbox gerekli)
    Route::post('/kapi-numaralari', [MapsController::class, 'kapiNumaralari'])
        ->name('kapi-numaralari');

    // Serbest adres arama (POST)
    Route::post('/adres-ara', [MapsController::class, 'adresAra'])
        ->name('adres-ara');
});

// ============================================================
// ÖRNEK KULLANIM (test için tarayıcıdan):
// GET  /maps/mahalleler
// GET  /maps/mahalleler?q=kadıkendi
// GET  /maps/mahalle-bul?mahalle=15 Temmuz
// POST /maps/sokak-caddeler   body: { bbox: "38.75,37.13,38.80,37.16" }
// POST /maps/kapi-numaralari  body: { bbox: "38.74,37.14,38.76,37.15" }
// POST /maps/adres-ara        body: { adres: "8125. Sk. 122 Kadıkendi Eyyübiye" }
// ============================================================
