<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\District;
use App\Models\Village;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminManagementService
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository
    ) {}

    public function getList(Admin $actor, array $filters)
    {
        return $this->adminRepository->getHierarchicalAdmins($actor, $filters);
    }

    public function createAdmin(Admin $actor, array $data): Admin
    {
        $this->ensureCanManageRole($actor, $data['role']);
        $this->ensureRegionConsistency($actor, $data);

        $data['password'] = Hash::make($data['password']);

        return $this->adminRepository->create($data);
    }

    public function updateAdmin(Admin $actor, string $id, array $data): Admin
    {
        $targetAdmin = $this->adminRepository->findById($id);

        if (!$targetAdmin) {
            throw new NotFoundHttpException('Admin tidak ditemukan.');
        }

        $this->ensureCanManageRole($actor, $targetAdmin->role);

        if (isset($data['role']) && $data['role'] !== $targetAdmin->role) {
            $this->ensureCanManageRole($actor, $data['role']);
        }

        $this->ensureRegionConsistency($actor, $data);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $this->adminRepository->update($targetAdmin, $data);

        return $targetAdmin;
    }

    public function deleteAdmin(Admin $actor, string $id): void
    {
        $targetAdmin = $this->adminRepository->findById($id);

        if (!$targetAdmin) {
            throw new NotFoundHttpException('Admin tidak ditemukan.');
        }

        if ($targetAdmin->id === $actor->id) {
            throw new BadRequestHttpException('Anda tidak bisa menghapus akun sendiri.');
        }

        $this->ensureCanManageRole($actor, $targetAdmin->role);
        
        $this->adminRepository->update($targetAdmin, ['is_active' => false]);
        
        $this->adminRepository->delete($targetAdmin);
    }

    public function activateAdmin(Admin $actor, string $id): Admin
    {
        $targetAdmin = $this->adminRepository->findById($id);

        if (!$targetAdmin) {
            throw new NotFoundHttpException('Admin tidak ditemukan.');
        }

        $this->ensureCanManageRole($actor, $targetAdmin->role);
        
        $this->adminRepository->update($targetAdmin, ['is_active' => true]);
        
        if ($targetAdmin->trashed()) {
            $this->adminRepository->restore($targetAdmin);
        }

        return $targetAdmin;
    }

    public function resetPassword(Admin $actor, string $id, string $newPassword): void
    {
        $targetAdmin = $this->adminRepository->findById($id);

        if (!$targetAdmin) {
            throw new NotFoundHttpException('Admin tidak ditemukan.');
        }

        $this->ensureCanManageRole($actor, $targetAdmin->role);

        $this->adminRepository->update($targetAdmin, [
            'password' => Hash::make($newPassword),
        ]);
    }

    private function ensureCanManageRole(Admin $actor, string $targetRole): void
    {
        $permissions = [
            'super_admin'    => ['regency_admin', 'district_admin', 'village_officer'],
            'regency_admin'  => ['district_admin', 'village_officer'],
            'district_admin' => ['village_officer'],
            'village_officer' => [],
        ];

        $allowedRoles = $permissions[$actor->role] ?? [];

        if (!in_array($targetRole, $allowedRoles)) {
            throw new AccessDeniedHttpException(
                "Anda tidak memiliki wewenang untuk mengelola akun dengan role: {$targetRole}."
            );
        }
    }

    private function ensureRegionConsistency(Admin $actor, array &$data): void
    {
        if ($actor->isRegencyAdmin()) {
            $data['regency_id'] = $actor->regency_id;
        } elseif ($actor->isDistrictAdmin()) {
            $data['regency_id'] = $actor->regency_id;
            $data['district_id'] = $actor->district_id;
        }

        if (!empty($data['village_id'])) {
            $village = Village::find($data['village_id']);

            if (!$village) {
                throw new BadRequestHttpException('Desa/Kelurahan tidak ditemukan.');
            }

            if (!empty($data['district_id']) && $village->district_id !== $data['district_id']) {
                throw new BadRequestHttpException('Desa/Kelurahan tidak berada di kecamatan yang dipilih.');
            }
        }

        if (!empty($data['district_id'])) {
            $district = District::find($data['district_id']);

            if (!$district) {
                throw new BadRequestHttpException('Kecamatan tidak ditemukan.');
            }

            if (!empty($data['regency_id']) && $district->regency_id !== $data['regency_id']) {
                throw new BadRequestHttpException('Kecamatan tidak berada di kabupaten yang dipilih.');
            }
        }
    }
}