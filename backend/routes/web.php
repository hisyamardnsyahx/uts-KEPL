<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Tugas 6: /health publik, tanpa auth. Healthcheck Docker (curl -f)
// tidak membawa token, dan yang diperiksa di sini hanya kesehatan
// layanan (basis data + cache), bukan data pengguna.
Route::get('/health', HealthController::class);
