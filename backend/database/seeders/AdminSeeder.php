<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $nipSuperAdmin = '198501012010011001';

        $admin = Admin::updateOrCreate(
            ['nip' => $nipSuperAdmin], 
            [
                'name'      => 'Super Admin SABANA Center',
                'password'  => 'SabanaKalsel63!',
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info("Akun Super Admin berhasil disiapkan!");
        $this->command->info("NIP: {$admin->nip}");
        $this->command->info("Password: SabanaKalsel63!");
    }
}