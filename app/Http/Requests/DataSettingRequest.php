<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataSettingRequest extends FormRequest
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
        $id = $this->route('data_setting_id');
        return [
            "key" => "required|unique:data_settings,key," . $id,
            "value" => "required",
            "type" => "nullable",
        ];
    }
}
