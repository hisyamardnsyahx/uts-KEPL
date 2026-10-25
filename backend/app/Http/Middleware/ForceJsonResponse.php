<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya untuk rute api/*. Middleware ini didaftarkan sebagai
        // middleware GLOBAL (bukan grup api) karena Laravel mengurutkan
        // middleware auth:sanctum lebih dulu dari middleware grup api,
        // sehingga versi grup datang terlambat untuk unauthenticated.
        // Di sini Accept + X-Requested-With dipaksa sebelum auth berjalan:
        // validasi gagal -> 422 JSON (bukan redirect), tanpa token -> 401 JSON.
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $next($request);
    }
}
