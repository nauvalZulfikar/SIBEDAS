<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
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
			'no' => 'required',
			'business_name' => 'required|string',
			'npwpd' => 'required|string',
			'advertisement_type' => 'required|string',
			'advertisement_content' => 'required|string',
			'business_address' => 'required|string',
			'advertisement_location' => 'required|string',
			'village_name' => 'required',
			'district_name' => 'required',
			'length' => 'required',
			'width' => 'required',
			'viewing_angle' => 'required|string',
			'face' => 'required|string',
			'area' => 'required|string',
			'angle' => 'required|string',
			'contact' => 'required|string',
        ];
    }

    /**
     * pesan error validasi
     */
    public function messages(): array
    {
        return [
            'no.required' => 'Nomor harus diisi.',
            'business_name.required' => 'Nama usaha harus diisi.',
            'business_name.string' => 'Nama usaha harus berupa teks.',
            'npwpd.required' => 'NPWPD harus diisi.',
            'npwpd.string' => 'NPWPD harus berupa teks.',
            'advertisement_type.required' => 'Jenis reklame harus diisi.',
            'advertisement_type.string' => 'Jenis reklame harus berupa teks.',
            'advertisement_content.required' => 'Isi reklame harus diisi.',
            'advertisement_content.string' => 'Isi reklame harus berupa teks.',
            'business_address.required' => 'Alamat usaha harus diisi.',
            'business_address.string' => 'Alamat usaha harus berupa teks.',
            'advertisement_location.required' => 'Lokasi reklame harus diisi.',
            'advertisement_location.string' => 'Lokasi reklame harus berupa teks.',
            'village_name.required' => 'Nama desa harus diisi.',
            'district_name.required' => 'Nama kecamatan harus diisi.',
            'length.required' => 'Panjang harus diisi.',
            'width.required' => 'Lebar harus diisi.',
            'viewing_angle.required' => 'Sudut pandang harus diisi.',
            'viewing_angle.string' => 'Sudut pandang harus berupa teks.',
            'face.required' => 'Jumlah sisi harus diisi.',
            'face.string' => 'Jumlah sisi harus berupa teks.',
            'area.required' => 'Luas harus diisi.',
            'area.string' => 'Luas harus berupa teks.',
            'angle.required' => 'Sudut harus diisi.',
            'angle.string' => 'Sudut harus berupa teks.',
            'contact.required' => 'Kontak harus diisi.',
            'contact.string' => 'Kontak harus berupa teks.',
        ];
    }
}
