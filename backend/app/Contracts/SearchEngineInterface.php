<?php

namespace App\Contracts;

interface SearchEngineInterface
{
    /**
     * Melakukan indexing data ke Search Engine
     */
    public function index(array $params);
}