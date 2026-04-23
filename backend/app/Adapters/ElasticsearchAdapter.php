<?php

namespace App\Adapters;

use App\Contracts\SearchEngineInterface;
use Elastic\Elasticsearch\Client;

class ElasticsearchAdapter implements SearchEngineInterface
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function index(array $params)
    {
        // Memanggil fungsi asli dari library final Elasticsearch
        return $this->client->index($params);
    }
}