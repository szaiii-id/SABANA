<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Admin;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AdminAuthService
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository
    ) {}

    public function login(array $credentials): array
    {
        $admin = $this->adminRepository->findByNip($credentials['nip']);

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages(['nip' => ['Kredensial tidak cocok.']]);
        }

        if ($admin->trashed()) {
            throw ValidationException::withMessages(['nip' => ['Akun Anda telah dinonaktifkan.']]);
        }

        if (!$admin->is_active) {
            throw ValidationException::withMessages(['nip' => ['Akun Anda dinonaktifkan.']]);
        }

        $this->adminRepository->updateLastLogin($admin->id);

        $token = $admin->createToken('AdminToken', ['admin'], now()->addHours(12))->plainTextToken;

        return ['admin' => $admin, 'token' => $token];
    }

    public function logout(Admin $admin): void
    {
        $token = $admin->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
            return;
        }

        $admin->tokens()->delete();
    }
}