<?php

use App\Http\Controllers\Api\AgentCoordinationController;
use Illuminate\Support\Facades\Route;

/*
| API Routes
| ------------------------------------------------------------------
|
| Claude ↔ Minimax arası koordinasyon kanalı.
|
|   GET    /api/coordination?since=<id>  — mesaj listesi (artımlı)
|   POST   /api/coordination             — mesaj gönder
|   DELETE /api/coordination             — temizle
|
| Auth: X-Coordination-Key header (AgentCoordinationController içinde kontrol edilir)
*/

Route::get('coordination',  [AgentCoordinationController::class, 'index']);
Route::post('coordination', [AgentCoordinationController::class, 'store']);
Route::delete('coordination', [AgentCoordinationController::class, 'destroy']);
