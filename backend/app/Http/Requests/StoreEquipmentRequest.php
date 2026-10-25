<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
