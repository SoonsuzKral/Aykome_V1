<?php

use App\Http\Controllers\MapsController;
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
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('verification.notice');
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

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('maps')->name('maps.')->group(function () {
    Route::get('/',                   [MapsController::class, 'index'])->name('index');
    Route::get('/proxy',              [MapsController::class, 'proxy'])->name('proxy');
    Route::post('/nokta-kaydet',      [MapsController::class, 'noktaKaydet'])->name('noktaKaydet');
    Route::get('/basvurular-geojson', [MapsController::class, 'basvurularGeoJson'])->name('basvurularGeoJson');
    Route::get('/basvuru-sorgula', [MapsController::class, 'basvuruSorgula'])->name('basvuruSorgula');
});

Route::prefix('db-switch')->name('db-switch.')->controller(\App\Http\Controllers\DatabaseSwitcherController::class)->group(function () {
    Route::match(['get', 'post'], '/login', 'login')->name('login');
    Route::get('/', 'index')->name('index');
    Route::post('/switch', 'switch')->name('switch');
    Route::get('/status', 'status')->name('status');
    Route::post('/migrate', 'migrate')->name('migrate');
    Route::post('/confirm-migrate', 'confirmMigrate')->name('confirm-migrate');
    Route::get('/logout', 'logout')->name('logout');
});

require __DIR__.'/admin.php';

Route::middleware('auth')->group(function () {
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/admin/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');
});

require __DIR__.'/auth.php';
