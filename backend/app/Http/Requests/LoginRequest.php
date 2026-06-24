<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => preg_replace('/[^0-9]/', '', $this->nik ?? ''),
            'pin' => preg_replace('/[^0-9]/', '', $this->pin ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => 'required|string|size:16',
            'pin' => 'required|string|size:6|regex:/^[0-9]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size'     => 'Format NIK harus tepat 16 digit.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.size'     => 'PIN harus tepat 6 digit.',
            'pin.regex' => 'PIN hanya boleh berisi angka.',
        ];
    }
}