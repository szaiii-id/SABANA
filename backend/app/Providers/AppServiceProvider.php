<?php

namespace App\Providers;

use App\Adapters\ElasticsearchAdapter;
use App\Contracts\SearchEngineInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SearchEngineInterface::class, ElasticsearchAdapter::class);
        $this->app->singleton(Client::class, function ($app) {
            // Ambil data dari config (yang datanya ditarik dari .env)
            $host = config('services.elasticsearch.host', 'http://sabana_search:9200');
            $user = config('services.elasticsearch.username', 'elastic');
            $pass = config('services.elasticsearch.password');

            $builder = ClientBuilder::create()
                ->setHosts([$host]);

            // Hanya tambahkan auth jika password ada di config
            if ($pass) {
                $builder->setBasicAuthentication($user, $pass);
            }

            return $builder->build();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
