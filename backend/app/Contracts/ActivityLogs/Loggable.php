<?php

namespace App\Contracts\ActivityLogs;

interface Loggable
{
    public function toActivityLog(): array;
}