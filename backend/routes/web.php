<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Tugas 6: /health publik, tanpa auth. Healthcheck Docker (curl -f)
// tidak membawa token, dan yang diperiksa di sini hanya kesehatan
// layanan (basis data + cache), bukan data pengguna.
Route::get('/health', HealthController::class);

// Tugas 7: /metrics publik, tanpa auth. Prometheus membaca endpoint
// ini lewat jaringan Compose (backend:80); port-nya tidak dibuka
// ke host, dan isinya hanya hitungan permintaan, bukan data pengguna.
Route::get('/metrics', MetricsController::class);
