<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PbgTaskMultiStepRequest extends FormRequest
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
    public function rules()
    {
        return [
            // rules step 1
            "step1Form.uuid" => "required",
            "step1Form.name" => "nullable|string|max:255",
            "step1Form.owner_name" => "nullable|string|max:255",
            "step1Form.application_type" => "nullable|string|max:255",
            "step1Form.application_type_name" => "nullable|string|max:255",
            "step1Form.condition" => "nullable|string|max:255",
            "step1Form.registration_number" => "nullable|string|max:255",
            "step1Form.document_number" => "nullable|string|max:255",
            "step1Form.address" => "nullable|string|max:255",
            "step1Form.status" => "nullable|integer",
            "step1Form.status_name" => "nullable|string|max:255",
            "step1Form.slf_status" => "nullable|string|max:255",
            "step1Form.slf_status_name" => "nullable|string|max:255",
            "step1Form.function_type" => "nullable|string|max:255",
            "step1Form.consultation_type" => "nullable|string|max:255",
            "step1Form.due_date" => "nullable|date",
            "step1Form.land_certificate_phase" => "nullable|boolean",
            "step1Form.task_created_at" => "nullable|date",
        ];
    }

    public function messages()
    {
        return [
            // message step 1
            "step1Form.uuid.required" => "UUID wajib diisi.",
            "step1Form.uuid.uuid" => "Format UUID tidak valid.",
            "step1Form.name.max" => "Nama tidak boleh lebih dari 255 karakter.",
            "step1Form.owner_name.max" => "Nama pemilik tidak boleh lebih dari 255 karakter.",
            "step1Form.registration_number.max" => "Nomor registrasi tidak boleh lebih dari 255 karakter.",
            "step1Form.document_number.max" => "Nomor dokumen tidak boleh lebih dari 255 karakter.",
            "step1Form.status.integer" => "Status harus berupa angka.",
            "step1Form.due_date.date" => "Tanggal jatuh tempo tidak valid.",
            "step1Form.land_certificate_phase.boolean" => "Fase sertifikat tanah harus berupa true/false.",
        ];
    }
}
