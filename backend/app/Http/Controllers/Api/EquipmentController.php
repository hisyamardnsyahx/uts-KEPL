<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;

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
        $equipment = Equipment::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil ditambahkan.',
            'data' => $equipment,
        ], 201);
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
        $equipment->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil diubah.',
            'data' => $equipment->fresh(),
        ]);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alat berhasil dihapus.',
            'data' => null,
        ]);
    }
}
