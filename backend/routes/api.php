<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipmentController;
use Illuminate\Support\Facades\Route;

// Satu-satunya endpoint publik: login. Semua endpoint lain di bawah ini
// memakai middleware auth:sanctum, jadi tanpa token balasannya 401 JSON.
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::apiResource('equipments', EquipmentController::class);
});
