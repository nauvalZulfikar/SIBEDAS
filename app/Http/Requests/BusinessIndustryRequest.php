<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessIndustryRequest extends FormRequest
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
            'nama_kecamatan' => 'required|string|max:255',
            'nama_kelurahan' => 'required|string|max:255',
            'nop' => 'required|string|max:255|unique:business_or_industries,nop,' . $this->route('api_business_industry'),
            'nama_wajib_pajak' => 'required|string|max:255',
            'alamat_wajib_pajak' => 'nullable|string|max:255',
            'alamat_objek_pajak' => 'required|string|max:255',
            'luas_bumi' => 'required|numeric',
            'luas_bangunan' => 'required|numeric',
            'njop_bumi' => 'required|numeric',
            'njop_bangunan' => 'required|numeric',
            'ketetapan' => 'required|string|max:255',
            'tahun_pajak' => 'required|integer|min:1900|max:' . date('Y'),
        ];
    }
}
