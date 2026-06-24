<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => strip_tags(trim($this->full_name)),
            'nik' => preg_replace('/[^0-9]/', '', $this->nik),
            'family_card_number' => preg_replace('/[^0-9]/', '', $this->family_card_number),
            'whatsapp_number' => preg_replace('/[^0-9]/', '', $this->whatsapp_number),
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
                
                Rule::unique('citizens', 'nik')->where(function ($query) {
                    return $query->where('is_verified', true);
                }),
            ],
            'family_card_number' => 'required|string|size:16|regex:/^[0-9]+$/',
            'full_name' => 'required|string|min:3|max:255|regex:/^[a-zA-Z\s\'\.,\-]+$/',
            'whatsapp_number' => 'required|string|min:10|max:15|regex:/^[0-9]+$/',
            'pin' => 'required|string|size:6|regex:/^[0-9]+$/|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'Format NIK harus tepat 16 digit.',
            'nik.regex' => 'NIK tidak valid. Pendaftaran SABANA khusus untuk KTP Kalimantan Selatan.',
            'nik.unique' => 'NIK ini sudah terdaftar dan terverifikasi. Gunakan fitur Lupa PIN jika Anda pemilik akun.',

            'family_card_number.required' => 'Nomor Kartu Keluarga wajib diisi.',
            'family_card_number.size' => 'Format Nomor KK harus tepat 16 digit.',
            'family_card_number.regex' => 'Nomor KK hanya boleh berisi angka.',

            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.min' => 'Nama lengkap minimal 3 karakter.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'full_name.regex' => 'Nama lengkap hanya boleh berisi huruf.',

            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.min' => 'Nomor WhatsApp minimal 10 digit.',
            'whatsapp_number.max' => 'Nomor WhatsApp maksimal 15 digit.',
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',

            'pin.required' => 'PIN wajib diisi.',
            'pin.size' => 'PIN harus tepat 6 digit.',
            'pin.regex' => 'PIN hanya boleh berisi angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok dengan PIN yang dibuat.',
        ];
    }
}