<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpatialPlanningRequest extends FormRequest
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
			'name' => 'nullable|string',
			'kbli' => 'nullable|string',
			'activities' => 'nullable|string',
			'area' => 'nullable|string',
			'location' => 'nullable|string',
			'number' => 'nullable|string',
            'date' => 'nullable|date_format:Y-m-d',
            'is_terbit' => 'nullable|boolean',
        ];
    }

    /**
     * Get the validation messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Kolom Nama harus berupa teks.',
            'kbli.string' => 'Kolom KBLI harus berupa teks.',
            'activities.string' => 'Kolom Kegiatan harus berupa teks.',
            'area.string' => 'Kolom Area harus berupa teks.',
            'location.string' => 'Kolom Lokasi harus berupa teks.',
            'number.string' => 'Kolom Nomor harus berupa teks.',
            'date.date_format' => 'Format tanggal tidak valid, gunakan format Y-m-d H:i:s.',
        ];
    }
}
