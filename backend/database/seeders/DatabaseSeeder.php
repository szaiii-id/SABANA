<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\IndoRegionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ganti 'provinces' dengan nama tabel aktual yang di-generate oleh IndoRegionSeeder
        if (DB::table('provinces')->exists()) {
            $this->command->info('Data wilayah sudah ada. Proses seeding dilewati untuk mencegah duplikasi.');
            return;
        }

        $this->call([
            IndoRegionSeeder::class
        ]);
        
        $this->command->info('Proses seeding data wilayah berhasil.');
    }
}