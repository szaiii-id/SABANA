<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }


    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                'exists:assistance_programs,id',
                Rule::unique('assistance_submissions')
                    ->where('citizen_id', $this->user()->id)
                    ->where('status', 'pending')
            ],
            'regency_id' => 'required|string|size:4',
            'district_id' => 'required|string|size:7',
            'village_id' => 'required|string|size:10',
            'disbursement_method' => 'required|in:bpd_transfer,village_cash',
            'bank_account_number' => 'required_if:disbursement_method,bpd_transfer',
            '*' => 'sometimes', 
        ];
    }

    public function messages(): array
    {
        return [
            'program_id.unique' => 'Anda sudah memiliki pengajuan yang sedang diproses untuk program ini.',
        ];
    }
}