<?php

declare(strict_types=1);

namespace App\Http\Requests\Citizen;

use App\Models\AssistanceSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'regency_id'  => preg_replace('/[^0-9]/', '', (string) ($this->regency_id ?? '')),
            'district_id' => preg_replace('/[^0-9]/', '', (string) ($this->district_id ?? '')),
            'village_id'  => preg_replace('/[^0-9]/', '', (string) ($this->village_id ?? '')),
        ];

        if ($this->has('bank_account_number') && $this->bank_account_number !== null && $this->bank_account_number !== '') {
            $data['bank_account_number'] = preg_replace('/[^0-9]/', '', (string) $this->bank_account_number);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $citizenId = $this->user()?->id;

        $rules = [
            'program_id' => [
                'required',
                'exists:assistance_programs,id',
            ],
            'regency_id'          => 'required|string|size:4',
            'district_id'         => 'required|string|size:7',
            'village_id'          => 'required|string|size:10',
            'disbursement_method' => 'required|in:bpd_transfer,village_cash',
            'bank_account_number' => 'required_if:disbursement_method,bpd_transfer|string|min:5|max:30',
        ];

        if ($citizenId) {
            $rules['program_id'][] = Rule::unique('assistance_submissions')
                ->where('citizen_id', $citizenId)
                ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                ->whereNull('deleted_at');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'program_id.required'          => 'Program bantuan wajib dipilih.',
            'program_id.exists'            => 'Program bantuan tidak valid.',
            'program_id.unique'            => 'Anda sudah memiliki pengajuan aktif untuk program ini.',
            'regency_id.required'          => 'Kabupaten/Kota wajib dipilih.',
            'regency_id.size'              => 'Format kabupaten/kota tidak valid.',
            'district_id.required'         => 'Kecamatan wajib dipilih.',
            'district_id.size'             => 'Format kecamatan tidak valid.',
            'village_id.required'          => 'Desa/Kelurahan wajib dipilih.',
            'village_id.size'              => 'Format desa/kelurahan tidak valid.',
            'disbursement_method.required' => 'Metode penyaluran wajib dipilih.',
            'disbursement_method.in'       => 'Metode penyaluran tidak valid.',
            'bank_account_number.required_if' => 'Nomor rekening wajib diisi untuk transfer BPD.',
            'bank_account_number.min'      => 'Nomor rekening minimal 5 digit.',
            'bank_account_number.max'      => 'Nomor rekening maksimal 30 digit.',
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->allFiles() as $key => $file) {
                if ($file->getSize() > 2048 * 1024) {
                    $validator->errors()->add($key, 'Ukuran file ' . $key . ' maksimal 2MB.');
                }
            }
        });
    }
}