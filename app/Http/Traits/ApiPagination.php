<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApiPagination
{
    /**
     * Get the number of items per page from request
     *
     * @param int $default
     * @param int $max
     * @return int
     */
    protected function getPerPage(int $default = 15, int $max = 100): int
    {
        $perPage = request()->input('per_page', $default);
        
        // Ensure per_page is numeric and within bounds
        if (!is_numeric($perPage)) {
            return $default;
        }
        
        $perPage = (int) $perPage;
        
        if ($perPage < 1) {
            return $default;
        }
        
        if ($perPage > $max) {
            return $max;
        }
        
        return $perPage;
    }

    /**
     * Apply pagination to a query
     *
     * @param Builder $query
     * @param int|null $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function applyPagination(Builder $query, ?int $perPage = null)
    {
        $perPage = $perPage ?? $this->getPerPage();
        
        return $query->paginate($perPage);
    }
}
