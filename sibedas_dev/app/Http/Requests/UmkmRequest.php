<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UmkmRequest extends FormRequest
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
			'business_name' => 'required|string',
			'business_address' => 'required|string',
			'business_desc' => 'required|string',
			'business_contact' => 'required|string',
			'business_id_number' => 'string',
			'business_scale_id' => 'required',
			'owner_id' => 'required|string',
			'owner_name' => 'required|string',
			'owner_address' => 'required|string',
			'owner_contact' => 'required|string',
			'business_type' => 'required|string',
			'business_form_id' => 'required|string',
			'revenue' => 'required|numeric|between:0,999999999999999999.99',
			'village_name' => 'required|string',
			'district_name' => 'required',
			'number_of_employee' => 'required',
			'permit_status_id' => 'required',
            'land_area' => 'required|integer|between:0,99999999999',
        ];
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'Nama usaha wajib diisi.',
            'business_name.string' => 'Nama usaha harus berupa teks.',

            'business_address.required' => 'Alamat usaha wajib diisi.',
            'business_address.string' => 'Alamat usaha harus berupa teks.',

            'business_desc.required' => 'Deskripsi usaha wajib diisi.',
            'business_desc.string' => 'Deskripsi usaha harus berupa teks.',

            'business_contact.required' => 'Kontak usaha wajib diisi.',
            'business_contact.string' => 'Kontak usaha harus berupa teks.',

            'business_id_number.string' => 'Nomor ID usaha harus berupa teks.',

            'business_scale_id.required' => 'Skala usaha wajib diisi.',

            'owner_id.required' => 'ID pemilik wajib diisi.',
            'owner_id.string' => 'ID pemilik harus berupa teks.',

            'owner_name.required' => 'Nama pemilik wajib diisi.',
            'owner_name.string' => 'Nama pemilik harus berupa teks.',

            'owner_address.required' => 'Alamat pemilik wajib diisi.',
            'owner_address.string' => 'Alamat pemilik harus berupa teks.',

            'owner_contact.required' => 'Kontak pemilik wajib diisi.',
            'owner_contact.string' => 'Kontak pemilik harus berupa teks.',

            'business_type.required' => 'Jenis usaha wajib diisi.',
            'business_type.string' => 'Jenis usaha harus berupa teks.',

            'business_form.required' => 'Bentuk usaha wajib diisi.',
            'business_form.string' => 'Bentuk usaha harus berupa teks.',

            'revenue.required' => 'Omset wajib diisi.',
            'revenue.numeric' => 'Omset harus berupa angka yang valid.',
            'revenue.between' => 'Omset harus berada di antara 0 dan 9.999.999.999,99.',

            'village_name.required' => 'Nama desa wajib diisi.',
            'village_name.string' => 'Nama desa harus berupa teks.',

            'district_name.required' => 'Nama distrik wajib diisi.',

            'number_of_employee.required' => 'Jumlah karyawan wajib diisi.',

            'permit_status_id.required' => 'Status izin wajib diisi.',

            'land_area.required' => 'Luas lahan wajib diisi.',
            'land_area.integer' => 'Luas lahan harus berupa angka bulat.',
            'land_area.between' => 'Luas lahan harus berada di antara 0 dan 99.999.999.999.',
        ];
    }
}
