<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nip' => preg_replace('/[^0-9]/', '', $this->nip ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'nip'      => 'required|string|size:18',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'nip.required'      => 'NIP wajib diisi.',
            'nip.size'          => 'NIP harus tepat 18 digit.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min'      => 'Kata sandi minimal 8 karakter.',
        ];
    }

    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = config('sabana.rate_limit.max_attempts', 3);

        if (!RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw new TooManyRequestsHttpException(
            $seconds,
            'Terlalu banyak percobaan. Coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
        );
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('nip')) . '|' . $this->ip());
    }
}