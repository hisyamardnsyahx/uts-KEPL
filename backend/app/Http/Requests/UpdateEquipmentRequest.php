<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Sama seperti StoreEquipmentRequest (Tugas 6, alur validasi
    // gagal), hanya aksinya yang berbeda: equipment.update.
    protected function failedValidation(Validator $validator): void
    {
        Log::warning('equipment.validation_failed', [
            'user_id' => $this->user()?->id,
            'action' => 'equipment.update',
            'fields' => array_keys($validator->errors()->toArray()),
            'reason' => 'validation_failed',
        ]);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('equipment')->ignore($this->route('equipment'))],
            'category' => ['required', 'string', Rule::in(['Komputer', 'Jaringan', 'Audio Visual', 'Perkakas', 'Lainnya'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'condition' => ['required', 'string', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'perawatan'])],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
