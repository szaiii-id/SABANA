<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class RevokeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'required|string|min:10|max:500|regex:/\S/',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Alasan penolakan evaluasi wajib diisi.',
            'notes.min'      => 'Alasan minimal 10 karakter.',
            'notes.max'      => 'Alasan maksimal 500 karakter.',
            'notes.regex'    => 'Alasan tidak boleh hanya spasi.',
        ];
    }
}