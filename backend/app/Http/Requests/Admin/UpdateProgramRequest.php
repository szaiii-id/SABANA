<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AssistanceProgram;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('criteria')) {
            $criteria = is_string($this->criteria)
                ? json_decode($this->criteria, true)
                : $this->criteria;
            $data['criteria'] = is_array($criteria) ? $criteria : [];
        }

        if ($this->has('ai_config')) {
            $aiConfig = is_string($this->ai_config)
                ? json_decode($this->ai_config, true)
                : $this->ai_config;
            $data['ai_config'] = is_array($aiConfig) ? $aiConfig : [];
        }

        if ($this->has('name') && $this->name) {
            $data['name'] = strip_tags(trim((string) $this->name));
        }
        if ($this->has('description') && $this->description) {
            $data['description'] = strip_tags(trim((string) $this->description));
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $programId = $this->route('id');

        return [
            'name'           => 'sometimes|string|min:3|max:255',
            'description'    => 'sometimes|string|min:10',
            'start_date'     => 'sometimes|date',
            'end_date' => [
                'sometimes',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($programId) {
                    $startDate = $this->input('start_date');

                    if (!$startDate && $programId) {
                        $program = AssistanceProgram::find($programId);
                        $startDate = $program?->start_date?->format('Y-m-d');
                    }

                    if (!$startDate) {
                        return;
                    }

                    if ($value <= $startDate) {
                        $fail('Tanggal selesai harus setelah tanggal mulai.');
                    }
                },
            ],
            'quota_total'    => 'sometimes|integer|min:1',
            'benefit_amount' => 'sometimes|numeric|min:0',
            'status'         => 'sometimes|in:draft,active,closed,completed',
            'banner'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'criteria'       => 'sometimes|array',
            'ai_config'      => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.min'                => 'Nama program minimal 3 karakter.',
            'name.max'                => 'Nama program maksimal 255 karakter.',
            'description.min'         => 'Deskripsi program minimal 10 karakter.',
            'start_date.date'         => 'Format tanggal mulai tidak valid.',
            'end_date.date'           => 'Format tanggal selesai tidak valid.',
            'end_date.after'          => 'Tanggal selesai harus setelah tanggal mulai.',
            'quota_total.integer'     => 'Kuota total harus berupa angka.',
            'quota_total.min'         => 'Kuota total minimal 1.',
            'quota_total.required'    => 'Kuota total wajib diisi.',
            'benefit_amount.numeric'  => 'Nilai bantuan harus berupa angka.',
            'benefit_amount.min'      => 'Nilai bantuan minimal 0.',
            'banner.image'            => 'File harus berupa gambar.',
            'banner.max'              => 'Ukuran banner maksimal 2MB.',

        ];
    }
}