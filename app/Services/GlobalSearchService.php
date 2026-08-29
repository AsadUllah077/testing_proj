<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Map of models registered for global search.
     * In a larger app, this could be loaded from a configuration file.
     */
    protected array $models = [
        User::class,
        Customer::class,
        Vendor::class,
        Bill::class,
        Sale::class,
        Product::class,
        Employee::class,
    ];

    /**
     * Perform the global search across all registered models.
     */
    public function search(string $term, int $limitPerModel = 5, int $totalLimit = 20): Collection
    {
        $results = collect();

        if (strlen($term) < 2) {
            return $results;
        }

        foreach ($this->models as $modelClass) {
            // Check if model uses GlobalSearchTrait or has scopeGlobalSearch
            $modelResults = $modelClass::globalSearch($term)
                ->limit($limitPerModel)
                ->get()
                ->map(fn($model) => $model->toGlobalSearchResult());

            $results = $results->merge($modelResults);
        }

        return $results->take($totalLimit);
    }

    /**
     * Register a new model for global search dynamically.
     */
    public function registerModel(string $modelClass): void
    {
        if (!in_array($modelClass, $this->models)) {
            $this->models[] = $modelClass;
        }
    }
}
