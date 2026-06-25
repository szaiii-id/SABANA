<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name'          => trim($this->full_name),
            'nik'                => preg_replace('/[^0-9]/', '', $this->nik),
            'family_card_number' => preg_replace('/[^0-9]/', '', $this->family_card_number),
            'whatsapp_number'    => preg_replace('/[^0-9]/', '', $this->whatsapp_number ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^63[0-9]{14}$/',
            ],
            'family_card_number' => 'required|string|size:16|regex:/^[0-9]+$/',
            'full_name'          => 'required|string|min:3|max:255|regex:/^[a-zA-Z\s\.\,\-\'\/]+$/',
            'whatsapp_number'    => 'required|string|min:10|max:15|regex:/^[0-9]+$/',
            'with_pin'           => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.size'                   => 'NIK harus 16 digit.',
            'nik.regex'                  => 'NIK tidak valid. Gunakan NIK KTP Kalimantan Selatan (diawali 63).',
            'family_card_number.size'    => 'Nomor KK harus 16 digit.',
            'family_card_number.regex'   => 'Nomor KK hanya boleh berisi angka.',
            'full_name.required'         => 'Nama lengkap wajib diisi.',
            'full_name.regex'            => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, strip, dan petik.',
            'whatsapp_number.required'   => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex'      => 'Nomor WhatsApp hanya boleh berisi angka.',
            'with_pin.required'          => 'Pilih metode pendaftaran.',
        ];
    }
}