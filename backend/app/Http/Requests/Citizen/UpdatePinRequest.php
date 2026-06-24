<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'current_pin'          => preg_replace('/[^0-9]/', '', $this->current_pin ?? ''),
            'new_pin'              => preg_replace('/[^0-9]/', '', $this->new_pin ?? ''),
            'new_pin_confirmation' => preg_replace('/[^0-9]/', '', $this->new_pin_confirmation ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'current_pin' => 'required|size:6|regex:/^[0-9]+$/',
            'new_pin'     => 'required|size:6|regex:/^[0-9]+$/|different:current_pin|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'current_pin.required'  => 'PIN saat ini wajib diisi.',
            'current_pin.size'      => 'PIN saat ini harus tepat 6 digit.',
            'current_pin.regex'     => 'PIN saat ini hanya boleh berisi angka.',
            'new_pin.required'      => 'PIN baru wajib diisi.',
            'new_pin.size'          => 'PIN baru harus tepat 6 digit.',
            'new_pin.regex'         => 'PIN baru hanya boleh berisi angka.',
            'new_pin.different'     => 'PIN baru tidak boleh sama dengan PIN saat ini.',
            'new_pin.confirmed'     => 'Konfirmasi PIN baru tidak cocok.',
        ];
    }
}