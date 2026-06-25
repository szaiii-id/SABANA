<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nip'               => preg_replace('/[^0-9]/', '', $this->nip ?? ''),
            'name'              => strip_tags(trim($this->name ?? '')),
            'regency_id'        => $this->regency_id ? preg_replace('/[^0-9]/', '', $this->regency_id) : null,
            'district_id'       => $this->district_id ? preg_replace('/[^0-9]/', '', $this->district_id) : null,
            'village_id'        => $this->village_id ? preg_replace('/[^0-9]/', '', $this->village_id) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nip'         => 'required|string|size:18|unique:admins,nip',
            'name'        => 'required|string|max:255',
            'password'    => 'required|string|min:8',
            'role'        => 'required|in:regency_admin,district_admin,village_officer',
            'regency_id'  => 'required_if:role,regency_admin|nullable|exists:regencies,id|size:4',
            'district_id' => 'required_if:role,district_admin|nullable|exists:districts,id|size:7',
            'village_id'  => 'required_if:role,village_officer|nullable|exists:villages,id|size:10',
        ];
    }

    public function messages(): array
    {
        return [
            'nip.required'              => 'NIP wajib diisi.',
            'nip.size'                  => 'NIP harus tepat 18 digit.',
            'nip.unique'                => 'NIP ini sudah digunakan.',
            'name.required'             => 'Nama lengkap wajib diisi.',
            'name.max'                  => 'Nama maksimal 255 karakter.',
            'password.required'         => 'Kata sandi wajib diisi.',
            'password.min'              => 'Kata sandi minimal 8 karakter.',
            'role.required'             => 'Level akses wajib dipilih.',
            'role.in'                   => 'Level akses tidak valid.',
            'regency_id.required_if'    => 'Kabupaten/Kota wajib dipilih untuk Admin Kabupaten.',
            'regency_id.size'           => 'Format kabupaten tidak valid.',
            'district_id.required_if'   => 'Kecamatan wajib dipilih untuk Admin Kecamatan.',
            'district_id.size'          => 'Format kecamatan tidak valid.',
            'village_id.required_if'    => 'Desa/Kelurahan wajib dipilih untuk Petugas Desa.',
            'village_id.size'           => 'Format desa tidak valid.',
        ];
    }
}