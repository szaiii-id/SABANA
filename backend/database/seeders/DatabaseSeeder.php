<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\ProgramSearchService;
use App\Services\Admin\SubmissionSearchService;
use Elastic\Elasticsearch\Client;
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
        if (DB::table('provinces')->exists()) {
            $this->command->info('Data wilayah sudah ada. Proses seeding dilewati untuk mencegah duplikasi.');
            $this->resyncElasticsearch();
            return;
        }

        $this->call([
            IndoRegionSeeder::class,
            AdminSeeder::class,
        ]);
        
        $this->command->info('Proses seeding data wilayah berhasil.');
        $this->resyncElasticsearch();
    }

    /**
     * Reset & reindex Elasticsearch agar sinkron dengan DB.
     */
    private function resyncElasticsearch(): void
    {
        try {
            $es = app(Client::class);

            $indices = ['sabana_submissions', 'sabana_programs', 'citizens'];
            foreach ($indices as $index) {
                try {
                    $es->indices()->delete(['index' => $index]);
                } catch (\Exception) {
                    // Index belum ada
                }
            }

            app(SubmissionSearchService::class)->ensureIndexExists();
            app(ProgramSearchService::class)->createIndex();
            app(SubmissionSearchService::class)->reindexAll();

            $this->command->info('Elasticsearch resynced.');
        } catch (\Exception $e) {
            $this->command->warn('Elasticsearch sync failed: ' . $e->getMessage());
        }
    }
}