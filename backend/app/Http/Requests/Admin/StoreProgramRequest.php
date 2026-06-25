<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProgramRequest extends FormRequest
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

        if ($this->has('name')) {
            $data['name'] = strip_tags(trim((string) $this->name));
        }
        if ($this->has('description')) {
            $data['description'] = strip_tags(trim((string) $this->description));
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|min:3|max:255',
            'description'    => 'required|string|min:10',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'quota_total'    => 'required|integer|min:1',
            'benefit_amount' => 'required|numeric|min:0',
            'status'         => 'required|in:draft,active',
            'banner'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'criteria'       => 'nullable|array',
            'ai_config'      => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'Nama program wajib diisi.',
            'name.min'                 => 'Nama program minimal 3 karakter.',
            'name.max'                 => 'Nama program maksimal 255 karakter.',
            'description.required'     => 'Deskripsi program wajib diisi.',
            'description.min'          => 'Deskripsi program minimal 10 karakter.',
            'start_date.required'      => 'Tanggal mulai wajib diisi.',
            'start_date.date'          => 'Format tanggal mulai tidak valid.',
            'end_date.required'        => 'Tanggal selesai wajib diisi.',
            'end_date.date'            => 'Format tanggal selesai tidak valid.',
            'end_date.after'           => 'Tanggal selesai harus setelah tanggal mulai.',
            'status.required'          => 'Status program wajib dipilih.',
            'status.in'                => 'Status hanya boleh draft atau active.',
            'quota_total.required'     => 'Kuota total wajib diisi.',
            'quota_total.integer'      => 'Kuota total harus berupa angka.',
            'quota_total.min'          => 'Kuota total minimal 1.',
            'benefit_amount.required'  => 'Nilai bantuan wajib diisi.',
            'benefit_amount.numeric'   => 'Nilai bantuan harus berupa angka.',
            'benefit_amount.min'       => 'Nilai bantuan minimal 0.',
            'banner.image'             => 'File harus berupa gambar.',
            'banner.max'               => 'Ukuran banner maksimal 2MB.',
        ];
    }
}