<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar alat.',
            'data' => Equipment::orderByDesc('id')->get(),
        ]);
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        // Alur 1 dari 3 yang diberi log terstruktur (Tugas 6): menyimpan
        // data. Alasan memilihnya: ini alur tulis utama aplikasi — kalau
        // penyimpanan gagal diam-diam, pengguna mengira alatnya sudah
        // tercatat padahal belum. Log ini info, karena penyimpanannya
        // berhasil; yang gagal justru dicatat sebagai error di catch.
        try {
            $equipment = Equipment::create($request->validated());

            Log::info('equipment.created', [
                'user_id' => $request->user()?->id,
                'action' => 'equipment.store',
                'equipment_id' => $equipment->id,
                'equipment_code' => $equipment->code,
                'category' => $equipment->category,
                'reason' => 'equipment_created',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alat berhasil ditambahkan.',
                'data' => $equipment,
            ], 201);
        } catch (QueryException $e) {
            // Alur 3 dari 3 (Tugas 6): gagal menghubungi basis data.
            // Yang dicatat hanya kode galatnya, bukan pesan lengkap PDO
            // (bisa memuat potongan query) dan bukan isi formulir.
            // Balasan ke klien 503, bukan 500: dari sisi pengguna ini
            // memang layanan yang sedang tidak sehat, bukan galat
            // program, dan /health akan membalas hal yang sama.
            Log::error('equipment.database_error', [
                'user_id' => $request->user()?->id,
                'action' => 'equipment.store',
                'reason' => 'database_unreachable',
                'error_code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Basis data tidak dapat dihubungi, alat belum tersimpan.',
                'data' => null,
            ], 503);
        }
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail alat.',
            'data' => $equipment,
        ]);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        try {
            $equipment->update($request->validated());

            Log::info('equipment.updated', [
                'user_id' => $request->user()?->id,
                'action' => 'equipment.update',
                'equipment_id' => $equipment->id,
                'equipment_code' => $equipment->code,
                'reason' => 'equipment_updated',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alat berhasil diubah.',
                'data' => $equipment->fresh(),
            ]);
        } catch (QueryException $e) {
            Log::error('equipment.database_error', [
                'user_id' => $request->user()?->id,
                'action' => 'equipment.update',
                'equipment_id' => $equipment->id,
                'reason' => 'database_unreachable',
                'error_code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Basis data tidak dapat dihubungi, perubahan belum tersimpan.',
                'data' => null,
            ], 503);
        }
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        try {
            $equipment->delete();

            Log::info('equipment.deleted', [
                'user_id' => request()->user()?->id,
                'action' => 'equipment.destroy',
                'equipment_id' => $equipment->id,
                'equipment_code' => $equipment->code,
                'reason' => 'equipment_deleted',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alat berhasil dihapus.',
                'data' => null,
            ]);
        } catch (QueryException $e) {
            Log::error('equipment.database_error', [
                'user_id' => request()->user()?->id,
                'action' => 'equipment.destroy',
                'equipment_id' => $equipment->id,
                'reason' => 'database_unreachable',
                'error_code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Basis data tidak dapat dihubungi, alat belum terhapus.',
                'data' => null,
            ], 503);
        }
    }
}
