<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('full_name') && $this->full_name) {
            $data['full_name'] = strip_tags(trim($this->full_name));
        }

        if ($this->has('whatsapp_number') && $this->whatsapp_number) {
            $data['whatsapp_number'] = preg_replace('/[^0-9]/', '', $this->whatsapp_number);
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $citizenId = $this->user() ? $this->user()->id : null;

        $rules = [
            'full_name' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\'.,\-]+$/',
            ],
            'whatsapp_number' => [
                'sometimes',
                'string',
                'min:10',
                'max:15',
                'regex:/^[0-9]+$/',
            ],
        ];

        if ($citizenId) {
            $rules['whatsapp_number'][] = Rule::unique('citizens', 'whatsapp_number')->ignore($citizenId);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'full_name.regex'        => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, dan strip.',
            'whatsapp_number.unique' => 'Nomor WhatsApp ini sudah digunakan oleh akun lain.',
            'whatsapp_number.regex'  => 'Nomor WhatsApp hanya boleh berisi angka.',
            'whatsapp_number.min'    => 'Nomor WhatsApp minimal 10 digit.',
            'whatsapp_number.max'    => 'Nomor WhatsApp maksimal 15 digit.',
        ];
    }
}