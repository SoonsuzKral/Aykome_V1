<?php

use App\Http\Controllers\MapsController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Giriş (/)
|--------------------------------------------------------------------------
| Uygulama: Laravel taban URL (örn. http://127.0.0.1:8000). Yönetim paneli
| Blade: /admin/... — bkz. docs/roadmap.md
*/

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('admin.dashboard');
    }

    return view('frontend.aykome_landing');
})->name('home');

Route::get('/tanitim', function () {
    return view('frontend.aykome_landing');
})->name('landing');

Route::get('/license-blocked', function () {
    return response()->view('errors.license-blocked', [], 402);
})->name('license.blocked');

Route::get('/docs', function () {
    return view('docs.index');
})->name('docs.index');

/*
|--------------------------------------------------------------------------
| EBYS Kamu Belge Doğrulama Portalı (/dogrulama)
|--------------------------------------------------------------------------
| KORUMASIZ (public) — giriş yapan/yapmayan herkes görür. Yalnızca doğrulama,
| dosya indirme / belge içeriği asla gösterilmez (KVKK).
*/
Route::get('/dogrulama', [DocumentVerificationController::class, 'index'])->name('verify.index');
Route::post('/dogrulama', [DocumentVerificationController::class, 'verifyDocument'])->name('verify.check');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'permission:maps.view'])->prefix('maps')->name('maps.')->group(function () {
    Route::get('/',                          [MapsController::class, 'index'])->name('index');
    Route::get('/proxy',                     [MapsController::class, 'proxy'])->name('proxy');
    Route::post('/nokta-kaydet',             [MapsController::class, 'noktaKaydet'])->name('noktaKaydet');
    Route::post('/basvuru-olustur',          [MapsController::class, 'basvuruOlustur'])->name('basvuruOlustur');
    Route::get('/basvurular/geojson',        [MapsController::class, 'basvurularGeoJson'])->name('basvurularGeoJson');
    Route::get('/basvuru-sorgula',           [MapsController::class, 'basvuruSorgula'])->name('basvuruSorgula');
    Route::get('/tckn-sorgula/{tckn}',       [MapsController::class, 'tcknSorgula'])->name('tcknSorgula');

    // CBS v7 — 15m Yol + Hat Kimliği
    Route::get('/15m/alti',                  [MapsController::class, 'geoJson15Alti'])->name('15m.alti');
    Route::get('/15m/ustu',                  [MapsController::class, 'geoJson15Ustu'])->name('15m.ustu');
    Route::get('/15m/sorgula',               [MapsController::class, 'roadQuery'])->name('15m.roadQuery');

    // CBS v7 — Çizim Yönetimi
    Route::post('/drawing/save',             [MapsController::class, 'drawingSave'])->name('drawing.save');
    Route::match(['put', 'patch'],'/drawing/{drawing}', [MapsController::class, 'drawingUpdate'])->name('drawing.update');
    Route::delete('/drawing/{drawing}',      [MapsController::class, 'drawingDelete'])->name('drawing.delete');
    Route::get('/drawing/app/{app}',         [MapsController::class, 'drawingGetByApp'])->name('drawing.byApp');
    Route::get('/drawing/user',              [MapsController::class, 'drawingGetByUser'])->name('drawing.byUser');

    // CBS v7 — Katman Tercihleri
    Route::post('/katman/kaydet',            [MapsController::class, 'katmanKaydet'])->name('katman.kaydet');
    Route::get('/katman/yukle',              [MapsController::class, 'katmanYukle'])->name('katman.yukle');
    Route::post('/renk-kaydet',              [MapsController::class, 'renkKaydet'])->name('renk.kaydet');

    // CBS v7 — Adres Arama (Nominatim proxy)
    Route::get('/ara',                       [MapsController::class, 'search'])->name('ara');

    // WMS NOKTA ATIŞI ADRES BULMA — mahalle/cadde/kapı (S2S GeoServer)
    Route::match(['get', 'post'], '/adres-ara',   [MapsController::class, 'adresAra'])->name('adres-ara');
    Route::get('/mahalle-caddeler',             [MapsController::class, 'mahalleCaddeler'])->name('mahalle-caddeler');

    // WMS CASCADING — Opus mantığı (WFS 1.1.0 + BBOX + öncache)
    Route::get('/mahalleler',                [MapsController::class, 'mahalleler'])->name('mahalleler');
    Route::get('/kapi-no',                   [MapsController::class, 'kapiNoAra'])->name('kapi-no');
    Route::get('/cadde-veri',                [MapsController::class, 'caddeVeri'])->name('cadde-veri');
});

Route::middleware(['auth', 'role:super-admin'])->prefix('db-switch')->name('db-switch.')->controller(\App\Http\Controllers\DatabaseSwitcherController::class)->group(function () {
    Route::match(['get', 'post'], '/login', 'login')->name('login');
    Route::get('/', 'index')->name('index');
    Route::post('/switch', 'switch')->name('switch');
    Route::get('/status', 'status')->name('status');
    Route::post('/migrate', 'migrate')->name('migrate');
    Route::post('/confirm-migrate', 'confirmMigrate')->name('confirm-migrate');
    Route::get('/logout', 'logout')->name('logout');
});

require __DIR__.'/admin.php';

// ─── E-İmza (Web: auth ile) ──────────────────────────────────────
Route::middleware(['auth'])->prefix('api/e-imza')->name('api.e-imza.')->group(function () {
    Route::post('/baslat',       [\App\Http\Controllers\Api\EImzaController::class, 'baslat'])->name('baslat');
    Route::get('/durum/{transactionId}', [\App\Http\Controllers\Api\EImzaController::class, 'durum'])->name('durum');
});

// ─── E-İmza (Machine-to-machine: API Key ile) ────────────────────
Route::prefix('api/e-imza')->group(function () {
    Route::get('/pdf/{transactionId}',       [\App\Http\Controllers\Api\EImzaController::class, 'pdf'])->name('api.e-imza.pdf');
    Route::post('/tamamla',                  [\App\Http\Controllers\Api\EImzaController::class, 'tamamla'])->middleware('e-imza.api-key')->name('api.e-imza.tamamla');
});

// ─── E-İmza (Auth ile dosya indirme) ─────────────────────────────
Route::middleware('auth')->prefix('e-imza')->name('e-imza.')->group(function () {
    Route::get('/indir/{transactionId}',     [\App\Http\Controllers\Api\EImzaController::class, 'indir'])->name('indir');
});

Route::middleware('auth')->group(function () {
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/admin/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');
});

require __DIR__.'/auth.php';
