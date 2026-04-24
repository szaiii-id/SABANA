<?php 

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6|different:current_pin|confirmed'
        ];
    }
}