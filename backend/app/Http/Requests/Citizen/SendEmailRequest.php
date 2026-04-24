<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'nama'   => 'required|string',
            'email'  => 'required|email',
            'subjek' => 'required|string',
            'pesan'  => 'required|string|min:10',
        ];
    }
}