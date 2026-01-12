<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requester_nik' => ['required', 'string'],
            'plant_id'      => ['required', 'integer'], // Sesuaikan tipe data
            'department'    => ['required', 'string'],
            'description'   => ['required', 'string'],
            'category'      => ['required', 'string'],
            'photo'         => ['nullable', 'image', 'max:5120'],
            'target_completion_date' => ['nullable', 'date'],
            'parameter_permintaan' => ['required', 'string'],
        ];
    }
}
