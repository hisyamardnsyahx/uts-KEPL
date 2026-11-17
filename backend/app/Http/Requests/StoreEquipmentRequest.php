<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Alur 2 dari 3 yang diberi log terstruktur (Tugas 6): validasi
    // gagal. Alasan memilihnya: ini satu-satunya penolakan yang
    // datang dari pengguna sendiri, bukan dari layanan — membedakan
    // "pengguna salah isi" dari "basis data mati" di log menghemat
    // waktu menelusuri. Yang dicatat hanya NAMA kolom yang gagal,
    // bukan nilai yang dikirim (nilai bisa berupa data pribadi), dan
    // tingkatnya warning: bukan galat sistem, tapi patut dilihat
    // kalau polanya berulang pada satu kolom yang sama.
    protected function failedValidation(Validator $validator): void
    {
        Log::warning('equipment.validation_failed', [
            'user_id' => $this->user()?->id,
            'action' => 'equipment.store',
            'fields' => array_keys($validator->errors()->toArray()),
            'reason' => 'validation_failed',
        ]);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:equipment'],
            'category' => ['required', 'string', Rule::in(['Komputer', 'Jaringan', 'Audio Visual', 'Perkakas', 'Lainnya'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'condition' => ['required', 'string', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'perawatan'])],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
