<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApiSorting
{
    /**
     * Apply sorting to a query based on request parameters
     *
     * @param Builder $query
     * @param array $allowedSortFields
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return Builder
     */
    protected function applySorting(
        Builder $query, 
        array $allowedSortFields, 
        string $defaultSort = 'id', 
        string $defaultDirection = 'desc'
    ): Builder {
        $sortField = request()->input('sort', $defaultSort);
        $sortDirection = request()->input('direction', $defaultDirection);
        
        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) 
            ? strtolower($sortDirection) 
            : $defaultDirection;
        
        // Check if sort field is allowed
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = $defaultSort;
        }
        
        return $query->orderBy($sortField, $sortDirection);
    }

    /**
     * Apply multiple sorts to a query
     *
     * @param Builder $query
     * @param array $allowedSortFields
     * @return Builder
     */
    protected function applyMultipleSorts(Builder $query, array $allowedSortFields): Builder
    {
        $sorts = request()->input('sorts', []);
        
        if (!is_array($sorts) || empty($sorts)) {
            return $query;
        }
        
        foreach ($sorts as $sort) {
            if (!is_array($sort) || !isset($sort['field'])) {
                continue;
            }
            
            $field = $sort['field'];
            $direction = $sort['direction'] ?? 'asc';
            
            // Validate
            if (!in_array($field, $allowedSortFields)) {
                continue;
            }
            
            $direction = in_array(strtolower($direction), ['asc', 'desc']) 
                ? strtolower($direction) 
                : 'asc';
            
            $query->orderBy($field, $direction);
        }
        
        return $query;
    }
}
