<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TourismRequest extends FormRequest
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
			'project_id' => 'required|string',
			'project_type_id' => 'required|string',
			'nib' => 'required|string',
			'business_name' => 'required|string',
			'oss_publication_date' => 'required',
			'investment_status_description' => 'required|string',
			'business_form' => 'required|string',
			'project_risk' => 'required|string',
			'project_name' => 'required|string',
			'business_scale' => 'required|string',
			'business_address' => 'required|string',
			'district_name' => 'required',
			'village_name' => 'required',
			'longitude' => 'required|string',
			'latitude' => 'required|string',
			'project_submission_date' => 'required',
			'kbli' => 'required|string',
			'kbli_title' => 'required|string',
			'supervisory_sector' => 'required|string',
			'user_name' => 'required|string',
			'email' => 'required|string',
			'contact' => 'required|string',
			'land_area_in_m2' => 'required|string',
			'investment_amount' => 'required|string',
			'tki' => 'required|string',
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
			'project_id.required' => 'ID proyek harus diisi.',
			'project_id.string' => 'ID proyek harus berupa teks.',

			'project_type_id.required' => 'ID tipe proyek harus diisi.',
			'project_type_id.string' => 'ID tipe proyek harus berupa teks.',

			'nib.required' => 'NIB harus diisi.',
			'nib.string' => 'NIB harus berupa teks.',

			'business_name.required' => 'Nama usaha harus diisi.',
			'business_name.string' => 'Nama usaha harus berupa teks.',

			'oss_publication_date.required' => 'Tanggal publikasi OSS harus diisi.',

			'investment_status_description.required' => 'Deskripsi status investasi harus diisi.',
			'investment_status_description.string' => 'Deskripsi status investasi harus berupa teks.',

			'business_form.required' => 'Bentuk usaha harus diisi.',
			'business_form.string' => 'Bentuk usaha harus berupa teks.',

			'project_risk.required' => 'Risiko proyek harus diisi.',
			'project_risk.string' => 'Risiko proyek harus berupa teks.',

			'project_name.required' => 'Nama proyek harus diisi.',
			'project_name.string' => 'Nama proyek harus berupa teks.',

			'business_scale.required' => 'Skala usaha harus diisi.',
			'business_scale.string' => 'Skala usaha harus berupa teks.',

			'business_address.required' => 'Alamat usaha harus diisi.',
			'business_address.string' => 'Alamat usaha harus berupa teks.',

			'district_name.required' => 'Nama kecamatan harus diisi.',

			'village_name.required' => 'Nama desa harus diisi.',

			'longitude.required' => 'Garis bujur harus diisi.',
			'longitude.string' => 'Garis bujur harus berupa teks.',

			'latitude.required' => 'Garis lintang harus diisi.',
			'latitude.string' => 'Garis lintang harus berupa teks.',

			'project_submission_date.required' => 'Tanggal pengajuan proyek harus diisi.',

			'kbli.required' => 'Kode KBLI harus diisi.',
			'kbli.string' => 'Kode KBLI harus berupa teks.',

			'kbli_title.required' => 'Judul KBLI harus diisi.',
			'kbli_title.string' => 'Judul KBLI harus berupa teks.',

			'supervisory_sector.required' => 'Sektor pengawasan harus diisi.',
			'supervisory_sector.string' => 'Sektor pengawasan harus berupa teks.',

			'user_name.required' => 'Nama pengguna harus diisi.',
			'user_name.string' => 'Nama pengguna harus berupa teks.',

			'email.required' => 'Email harus diisi.',
			'email.string' => 'Email harus berupa teks.',

			'contact.required' => 'Kontak harus diisi.',
			'contact.string' => 'Kontak harus berupa teks.',

			'land_area_in_m2.required' => 'Luas lahan dalam m² harus diisi.',
			'land_area_in_m2.string' => 'Luas lahan dalam m² harus berupa teks.',

			'investment_amount.required' => 'Jumlah investasi harus diisi.',
			'investment_amount.string' => 'Jumlah investasi harus berupa teks.',

			'tki.required' => 'Jumlah tenaga kerja Indonesia harus diisi.',
			'tki.string' => 'Jumlah tenaga kerja Indonesia harus berupa teks.',
		];
	}
}
