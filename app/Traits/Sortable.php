<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    public function scopeSortable(Builder $query)
    {
        $sortBy = request('sort_by');
        $sortDir = request('sort_dir', 'desc');

        // If model defines allowed sortable columns
        $allowed = property_exists($this, 'sortable')
            ? $this->sortable
            : [];

        // Validate
        if (!$sortBy || !in_array($sortBy, $allowed)) {
            // default sorting defined in model OR fallback to id
            $sortBy = property_exists($this, 'defaultSort')
                ? $this->defaultSort
                : 'id';
        }

        $sortDir = ($sortDir === 'desc') ? 'desc' : 'asc';

        return $query->orderBy($sortBy, $sortDir);
    }
}
