<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik'             => preg_replace('/[^0-9]/', '', $this->nik ?? ''),
            'whatsapp_number' => preg_replace('/[^0-9]/', '', $this->whatsapp_number ?? ''),
            'otp'             => preg_replace('/[^0-9]/', '', $this->otp ?? ''),
            'new_pin'         => preg_replace('/[^0-9]/', '', $this->new_pin ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik'             => 'required|string|size:16',
            'whatsapp_number' => 'required|string|min:10|max:15|regex:/^[0-9]+$/',
            'otp'             => 'required|string|size:6|regex:/^[0-9]+$/',
            'new_pin'         => 'required|string|size:6|regex:/^[0-9]+$/|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'             => 'NIK wajib diisi.',
            'nik.size'                 => 'Format NIK harus tepat 16 digit.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.min'      => 'Nomor WhatsApp minimal 10 digit.',
            'whatsapp_number.max'      => 'Nomor WhatsApp maksimal 15 digit.',
            'whatsapp_number.regex'    => 'Nomor WhatsApp hanya boleh berisi angka.',
            'otp.required'             => 'Kode OTP wajib diisi.',
            'otp.size'                 => 'Kode OTP harus tepat 6 digit.',
            'otp.regex'                => 'Kode OTP hanya boleh berisi angka.',
            'new_pin.required'         => 'PIN baru wajib diisi.',
            'new_pin.size'             => 'PIN baru harus tepat 6 digit.',
            'new_pin.regex'            => 'PIN baru hanya boleh berisi angka.',
            'new_pin.confirmed'        => 'Konfirmasi PIN baru tidak cocok.',
        ];
    }
}