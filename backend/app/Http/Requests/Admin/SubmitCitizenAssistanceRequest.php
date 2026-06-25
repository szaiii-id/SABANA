<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AssistanceProgram;
use App\Models\AssistanceSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubmitCitizenAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('bank_account_number') && $this->bank_account_number) {
            $this->merge([
                'bank_account_number' => preg_replace('/[^0-9]/', '', (string) $this->bank_account_number),
            ]);
        }

        $programId = $this->input('program_id');
        if ($programId) {
            $program = AssistanceProgram::find($programId);
            if ($program && isset($program->criteria['inputs'])) {
                $sanitized = [];
                foreach ($program->criteria['inputs'] as $input) {
                    $key = $input['key'];
                    if ($this->has($key)) {
                        $value = $this->input($key);
                        if (is_string($value)) {
                            $sanitized[$key] = strip_tags(trim($value));
                        }
                    }
                }
                if (!empty($sanitized)) {
                    $this->merge($sanitized);
                }
            }
        }
    }

    public function rules(): array
    {
        $admin = $this->user();
        $rules = [
            'citizen_id'          => 'required|uuid|exists:citizens,id',
            'is_evaluation'       => 'sometimes|boolean',
            'program_id'          => [
                'required',
                'uuid',
                'exists:assistance_programs,id',
                Rule::when(!$this->is_evaluation,
                    Rule::unique('assistance_submissions')
                        ->where('citizen_id', $this->citizen_id)
                        ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                        ->whereNull('deleted_at')
                ),
                function ($attribute, $value, $fail) {
                    if ($this->is_evaluation) {
                        return;
                    }

                    $program = AssistanceProgram::find($value);
                    if (!$program) return;

                    if ($program->start_date && now()->lessThan($program->start_date)) {
                        $fail('Program belum dibuka untuk pendaftaran.');
                    }

                    if ($program->end_date && now()->greaterThan($program->end_date)) {
                        $fail('Program sudah melewati batas tanggal pendaftaran.');
                    }

                    if ($program->status !== 'active') {
                        $fail('Program tidak aktif. Hanya program aktif yang bisa diajukan.');
                    }

                    if ($program->quota_total) {
                        $submissionCount = AssistanceSubmission::where('program_id', $value)
                            ->whereIn('status', AssistanceSubmission::ACTIVE_STATUSES)
                            ->whereNull('deleted_at')
                            ->count();

                        if ($submissionCount >= $program->quota_total) {
                            $fail('Kuota program sudah penuh. Silakan pilih program lain.');
                        }
                    }
                },
            ],
            'regency_id'          => $this->regionRules('regency_id', $admin),
            'district_id'         => $this->regionRules('district_id', $admin),
            'village_id'          => $this->regionRules('village_id', $admin),
            'disbursement_method' => 'required|in:village_cash,bpd_transfer',
            'bank_account_number' => 'required_if:disbursement_method,bpd_transfer|nullable|string',
        ];

        $programId = $this->input('program_id');
        if ($programId) {
            $program = AssistanceProgram::find($programId);
            if ($program && isset($program->criteria['inputs'])) {
                foreach ($program->criteria['inputs'] as $input) {
                    $key = $input['key'];
                    $rule = ['nullable'];

                    if (in_array($input['type'] ?? '', ['number', 'decimal', 'currency'])) {
                        $rule[] = 'numeric'; 
                    } else {
                        $rule[] = 'max:255'; 
                    }

                    $rules[$key] = $rule;
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'citizen_id.required'             => 'Warga wajib dipilih.',
            'citizen_id.exists'               => 'Warga tidak ditemukan.',
            'program_id.required'             => 'Program wajib dipilih.',
            'program_id.exists'               => 'Program tidak ditemukan.',
            'program_id.unique'               => 'Warga ini sudah memiliki pengajuan aktif di program yang sama.',
            'regency_id.required'             => 'Kabupaten wajib dipilih.',
            'regency_id.in'                   => 'Anda hanya bisa mengajukan di wilayah kabupaten Anda.',
            'district_id.required'            => 'Kecamatan wajib dipilih.',
            'district_id.in'                  => 'Anda hanya bisa mengajukan di wilayah kecamatan Anda.',
            'village_id.required'             => 'Desa wajib dipilih.',
            'village_id.in'                   => 'Anda hanya bisa mengajukan di desa Anda.',
            'disbursement_method.required'    => 'Metode penyaluran wajib dipilih.',
            'disbursement_method.in'          => 'Metode penyaluran tidak valid.',
            'bank_account_number.required_if' => 'Nomor rekening wajib diisi untuk metode transfer.',
        ];
    }

    private function regionRules(string $field, $admin): array
    {
        $rules = ['required', 'string'];

        match ($field) {
            'regency_id'  => $rules[] = 'size:4',
            'district_id' => $rules[] = 'size:7',
            'village_id'  => $rules[] = 'size:10',
            default       => null,
        };

        if ($admin?->role === 'village_officer' && $field === 'village_id') {
            $rules[] = 'in:' . $admin->village_id;
        }

        if ($admin?->role === 'district_admin' && $field === 'district_id') {
            $rules[] = 'in:' . $admin->district_id;
        }

        if ($admin?->role === 'regency_admin' && $field === 'regency_id') {
            $rules[] = 'in:' . $admin->regency_id;
        }

        return $rules;
    }
}