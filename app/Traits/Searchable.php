<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Scope a query to search across the model's searchable fields.
     */
    public function scopeGlobalSearch(Builder $query, string $term): Builder
    {
        $searchableFields = $this->getSearchableFields();

        return $query->where(function ($q) use ($term, $searchableFields) {
            foreach ($searchableFields as $field) {
                if (str_contains($field, '.')) {
                    $this->applyRelationshipSearch($q, $field, $term);
                } else {
                    $q->orWhere($field, 'ILIKE', "%{$term}%");
                }
            }

            $this->applyExtraSearchLogic($q, $term);
        });
    }

    /**
     * Get the fields that should be searchable.
     */
    protected function getSearchableFields(): array

    {
        return $this->searchable ?? [];
    }

    /**
     * Apply search on related models.
     */
    protected function applyRelationshipSearch(Builder $query, string $field, string $term): void
    {
        [$relation, $attribute] = explode('.', $field);

        $query->orWhereHas($relation, function ($q) use ($attribute, $term) {
            $q->where($attribute, 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Hook for adding extra search logic in models.
     */
    protected function applyExtraSearchLogic(Builder $query, string $term): void
    {
        // To be overridden in models if needed.
    }

    /**
     * Get the search result formatting for this model.
     * Overriding this in models is recommended.
     */
    abstract public function toGlobalSearchResult(): array;
}
