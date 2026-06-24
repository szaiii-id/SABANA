<?php

declare(strict_types=1);

namespace App\Contracts;

interface SearchEngineInterface
{
    /**
     * Index a document into the search engine.
     *
     * @param array{
     *     index: string,
     *     id: string,
     *     body: array<string, mixed>,
     * } $params
     * @return void
     */
    public function index(array $params): void;

    /**
     * Get the indices management client.
     *
     * @return object
     */
    public function indices(): object;
}