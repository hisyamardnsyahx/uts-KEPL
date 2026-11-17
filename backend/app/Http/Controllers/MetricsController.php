<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

// Tugas 7: endpoint /metrics yang dibaca Prometheus. Formatnya
// adalah format eksposisi Prometheus (teks biasa, satu baris per
// sampel), bukan JSON. Counter yang dikeluarkan di sini adalah
// http_requests_total yang dihitung middleware CatatMetrik.
// Endpoint ini publik tanpa auth, seperti /health: Prometheus tidak
// membawa token, dan yang dikeluarkan hanya hitungan permintaan
// per method/status/route - bukan data pengguna. Port /metrics
// tidak dibuka ke host di compose; Prometheus menjangkaunya lewat
// jaringan Compose (backend:80), supaya isinya tidak terlihat
// dari luar laptop.
class MetricsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [];
        $lines[] = '# HELP http_requests_total Jumlah permintaan HTTP yang dilayani aplikasi.';
        $lines[] = '# TYPE http_requests_total counter';

        $index = Cache::get('metrics:http_requests_total:index', []);
        foreach ($index as $key) {
            $rest = substr((string) $key, strlen('metrics:http_requests_total:'));
            $parts = explode(':', $rest, 3);
            if (count($parts) !== 3) {
                continue;
            }
            [$method, $status, $route] = $parts;
            $value = (int) Cache::get($key, 0);
            $route = str_replace(['\\', '"'], '', $route);
            $lines[] = "http_requests_total{method=\"{$method}\",status=\"{$status}\",route=\"{$route}\"} {$value}";
        }

        $lines[] = '# HELP app_up Apakah aplikasi sedang berjalan (1 = ya).';
        $lines[] = '# TYPE app_up gauge';
        $lines[] = 'app_up 1';

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; version=0.0.4',
        ]);
    }
}
