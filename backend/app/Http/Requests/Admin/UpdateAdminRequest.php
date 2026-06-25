<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = strip_tags(trim($this->name));
        }
        if ($this->has('regency_id') && $this->regency_id) {
            $data['regency_id'] = preg_replace('/[^0-9]/', '', $this->regency_id);
        }
        if ($this->has('district_id') && $this->district_id) {
            $data['district_id'] = preg_replace('/[^0-9]/', '', $this->district_id);
        }
        if ($this->has('village_id') && $this->village_id) {
            $data['village_id'] = preg_replace('/[^0-9]/', '', $this->village_id);
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'password'    => 'sometimes|string|min:8',
            'is_active'   => 'sometimes|boolean',
            'regency_id'  => 'sometimes|exists:regencies,id|size:4',
            'district_id' => 'sometimes|exists:districts,id|size:7',
            'village_id'  => 'sometimes|exists:villages,id|size:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'              => 'Nama maksimal 255 karakter.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'regency_id.size'       => 'Format kabupaten tidak valid.',
            'district_id.size'      => 'Format kecamatan tidak valid.',
            'village_id.size'       => 'Format desa tidak valid.',
        ];
    }
}