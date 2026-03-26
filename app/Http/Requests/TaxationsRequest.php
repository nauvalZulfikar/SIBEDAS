<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxationsRequest extends FormRequest
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
            'tax_no' => ['required', 'string', Rule::unique('taxs')->ignore($this->id)],
            'tax_code' => ['required', 'string'],
            'wp_name' => ['required', 'string'],
            'business_name' => ['required', 'string'],
            'address' => ['required', 'string'],
            'start_validity' => ['required', 'date_format:Y-m-d'],
            'end_validity' => ['required', 'date_format:Y-m-d'],
            'tax_value' => ['required', 'numeric', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'subdistrict' => ['required', 'string'],
            'village' => ['required', 'string'],
        ];
    }
}
