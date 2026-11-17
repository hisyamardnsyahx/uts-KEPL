<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

// Tugas 7: mencatat tiap permintaan HTTP sebagai counter Prometheus.
// Saya sengaja tidak menambah dependensi eksternal (misalnya
// promphp/prometheus_client_php): format eksposisi Prometheus untuk
// satu counter cukup sederhana untuk ditulis sendiri, dan dengan
// begitu tidak ada `composer install` tambahan yang harus berhasil
// di laptop saat docker build. Yang dicatat hanya tiga label yang
// jumlahnya terbatas: method, status, dan pola route (misalnya
// api/equipments/{equipment}), bukan URL lengkap, alamat IP, atau
// isi permintaan. Label yang nilainya tidak terbatas - id, email,
// URL lengkap - akan membuat jumlah deret waktu di Prometheus
// meledak, seperti yang dijelaskan pada modul Pertemuan 11.
// Permintaan ke /metrics dan /health tidak dicatat: keduanya
// dipanggil berulang oleh Prometheus dan healthcheck, jadi kalau
// ikut dihitung, panel "permintaan per detik" akan mengukur lalu
// lintas pemantau, bukan lalu lintas pengguna aplikasi.
class CatatMetrik
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $path = trim($request->path(), '/');
        if ($path === 'metrics' || $path === 'health' || $path === 'up') {
            return $response;
        }

        try {
            $method = $request->method();
            $status = (string) $response->getStatusCode();
            $route = $request->route()?->uri() ?? $path;
            $key = "metrics:http_requests_total:{$method}:{$status}:{$route}";

            Cache::increment($key);

            $index = Cache::get('metrics:http_requests_total:index', []);
            if (! in_array($key, $index, true)) {
                $index[] = $key;
                Cache::forever('metrics:http_requests_total:index', $index);
            }
        } catch (Throwable) {
            // Metrik tidak boleh mematahkan balasan: kalau cache
            // gagal, permintaan tetap dibalas seperti biasa, hanya
            // tidak tercatat pada counter ini.
        }

        return $response;
    }
}
