<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// Tugas 6: endpoint /health yang benar-benar memeriksa, bukan sekadar
// membalas teks "ok". Basis data diperiksa dengan query nyata, cache
// diperiksa dengan tulis-baca kunci uji. Kalau ada satu saja yang gagal,
// balasannya 503 dan bagian yang bermasalah disebut namanya, supaya
// healthcheck Compose dan orang yang membaca log langsung tahu ke mana
// harus melihat. Endpoint ini publik (tanpa auth:sanctum): healthcheck
// Docker tidak membawa token, dan status kesehatan layanan memang
// informasi operasional, bukan data pengguna.
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $failed = [];

        // 1) Basis data: query nyata, bukan sekadar "koneksi dibuat".
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (Throwable $e) {
            $checks['database'] = 'failed';
            $failed[] = 'database';
            // Yang dicatat hanya sebab yang sudah disaring (kode galat),
            // bukan pesan lengkapnya: pesan PDO bisa memuat nama host
            // atau potongan query, dan itu tidak perlu ada di log.
            Log::error('health.database_error', [
                'action' => 'health.check',
                'component' => 'database',
                'reason' => 'database_unreachable',
                'error_code' => $e->getCode(),
            ]);
        }

        // 2) Cache: tulis lalu baca kembali kunci uji. Kalau tulisnya
        // berhasil tapi bacanya tidak sama, cache dianggap gagal.
        try {
            Cache::put('health-check', 'ok', 10);
            if (Cache::get('health-check') === 'ok') {
                $checks['cache'] = 'ok';
            } else {
                throw new \RuntimeException('cache_read_mismatch');
            }
        } catch (Throwable $e) {
            $checks['cache'] = 'failed';
            $failed[] = 'cache';
            Log::error('health.cache_error', [
                'action' => 'health.check',
                'component' => 'cache',
                'reason' => 'cache_unavailable',
                'error_code' => $e->getCode(),
            ]);
        }

        $healthy = $failed === [];

        if (! $healthy) {
            Log::error('health.unhealthy', [
                'action' => 'health.check',
                'failed_components' => $failed,
                'reason' => 'dependency_check_failed',
            ]);
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'failed' => $failed,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
