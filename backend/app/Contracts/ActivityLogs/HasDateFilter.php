<?php


namespace App\Contracts\ActivityLogs;

trait HasDateFilter
{
    public function scopeDateFilter($query, array $filters)
    {
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        return $query;
    }
}