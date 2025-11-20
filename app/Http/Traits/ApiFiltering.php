<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ApiFiltering
{
    /**
     * Apply filters to a query based on request parameters
     *
     * @param Builder $query
     * @param array $allowedFilters Format: ['field_name' => 'operator'] or ['field_name']
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $allowedFilters): Builder
    {
        $filters = request()->input('filter', []);
        
        if (!is_array($filters)) {
            return $query;
        }
        
        foreach ($filters as $field => $value) {
            // Skip if filter is not allowed
            if (!isset($allowedFilters[$field]) && !in_array($field, $allowedFilters)) {
                continue;
            }
            
            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }
            
            // Get operator (default to '=')
            $operator = is_array($allowedFilters) && isset($allowedFilters[$field]) 
                ? $allowedFilters[$field] 
                : '=';
            
            // Apply filter based on operator
            $this->applyFilter($query, $field, $value, $operator);
        }
        
        return $query;
    }

    /**
     * Apply a single filter to the query
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @return void
     */
    protected function applyFilter(Builder $query, string $field, $value, string $operator): void
    {
        switch ($operator) {
            case 'like':
                $query->where($field, 'like', "%{$value}%");
                break;
                
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($field, $values);
                break;
                
            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereNotIn($field, $values);
                break;
                
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
                
            case 'null':
                if ($value === 'true' || $value === true || $value === 1) {
                    $query->whereNull($field);
                } else {
                    $query->whereNotNull($field);
                }
                break;
                
            case 'date':
                $query->whereDate($field, $value);
                break;
                
            case 'date_from':
                $query->whereDate($field, '>=', $value);
                break;
                
            case 'date_to':
                $query->whereDate($field, '<=', $value);
                break;
                
            default:
                // Standard operators: =, !=, >, <, >=, <=
                $query->where($field, $operator, $value);
                break;
        }
    }

    /**
     * Apply search across multiple fields
     *
     * @param Builder $query
     * @param array $searchableFields
     * @param string|null $searchTerm
     * @return Builder
     */
    protected function applySearch(Builder $query, array $searchableFields, ?string $searchTerm = null): Builder
    {
        $searchTerm = $searchTerm ?? request()->input('search');
        
        if (empty($searchTerm) || empty($searchableFields)) {
            return $query;
        }
        
        $query->where(function ($q) use ($searchableFields, $searchTerm) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$searchTerm}%");
            }
        });
        
        return $query;
    }
}
