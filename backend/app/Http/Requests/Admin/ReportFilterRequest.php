<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_mulai'  => ['nullable', 'date', 'date_format:Y-m-d'],
            'tgl_akhir'  => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tgl_mulai'],
            'program_id' => ['nullable', 'uuid', 'exists:assistance_programs,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_mulai.date'           => 'Tanggal mulai tidak valid.',
            'tgl_akhir.date'           => 'Tanggal akhir tidak valid.',
            'tgl_akhir.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'program_id.uuid'          => 'Program tidak valid.',
            'program_id.exists'        => 'Program tidak ditemukan.',
        ];
    }
}