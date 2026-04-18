<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AspirasiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subjek' => 'required|string',
            'pesan' => 'required|string|min:10',
        ];
    }
}