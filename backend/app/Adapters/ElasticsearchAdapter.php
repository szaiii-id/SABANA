<?php

declare(strict_types=1);

namespace App\Adapters;

use App\Contracts\SearchEngineInterface;
use Elastic\Elasticsearch\Client;

final class ElasticsearchAdapter implements SearchEngineInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function index(array $params): void
    {
        $this->client->index($params);
    }

    public function indices(): object
    {
        return $this->client->indices();
    }
}