<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductReportService
{
    private ExceptionService $exceptions;

    public function __construct(?ExceptionService $exceptions = null)
    {
        $this->exceptions = $exceptions ?? new ExceptionService;
    }

    public const STATUS_LABELS = [
        'ready' => 'Ready for Sale',
        'not_ready' => 'Not Ready for Sale',
        'hold' => 'Hold',
        'issued' => 'Issued',
        'sold' => 'Sold',
        'scrap' => 'Scrap',
    ];

    /**
     * Build the single product query used by the screen and both exports.
     */
    public function query(array $filters = []): Builder
    {
        $query = Product::query()
            ->select([
                'id',
                'unique_id',
                'title',
                'product_details',
                'category_id',
                'warehouse_location_id',
                'assigned_to',
                'disposition_id',
                'work_flow_stage_id',
                'status',
                'sold_status',
                'scrap_status',
                'manual_status',
                'count',
                'problem',
                'remarks',
                'last_action',
                'created_at',
                'updated_at',
            ])
            ->with([
                'category:id,name',
                'warehouseLocation:id,name',
                'assignedTo:id,full_name',
                'disposition:id,name',
                'workflowStage:id,name',
            ])
            ->withSum([
                'gatePassItems as sold_quantity' => fn ($item) => $item
                    ->whereNull('returned_at')
                    ->whereHas('gatePass', fn ($gatePass) => $gatePass->where('status', '!=', 'REJECTED')),
            ], 'quantity');

        if (! empty($filters['q'])) {
            $search = trim($filters['q']);
            $query->where(function ($nested) use ($search) {
                $nested->where('unique_id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('product_details', 'like', "%{$search}%");
            });
        }

        $columnFilters = [
            'category' => 'category_id',
            'location' => 'warehouse_location_id',
            'technician' => 'assigned_to',
            'work_flow_stage' => 'work_flow_stage_id',
            'disposition' => 'disposition_id',
            'manual_status' => 'manual_status',
        ];

        foreach ($columnFilters as $filter => $column) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($column, $filters[$filter]);
            }
        }

        if (($filters['assigned'] ?? '') === 'assigned') {
            $query->whereNotNull('assigned_to');
        } elseif (($filters['assigned'] ?? '') === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['age_bucket'])) {
            $this->applyAgeBucket($query, $filters['age_bucket']);
        }

        if (! empty($filters['status'])) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        return $query;
    }

    public function collection(array $filters = []): Collection
    {
        return $this->decorate(
            $this->query($filters)->orderByDesc('created_at')->get()
        );
    }

    public function decorate(Collection $products): Collection
    {
        return $products->map(function (Product $product) {
            $metrics = $this->metricsFor($product);

            foreach ($metrics as $key => $value) {
                $product->setAttribute($key, $value);
            }

            return $product;
        });
    }

    /**
     * Canonical unit and status calculation.
     *
     * Sold units are separated from the remaining units for partially sold
     * manual products, so the status chart always reconciles to total units.
     */
    public function metricsFor(Product $product): array
    {
        $isManual = (int) $product->manual_status === 1;
        $rawCount = (int) ($product->count ?? 0);
        $totalUnits = $isManual ? max(1, $rawCount) : 1;
        $rawSoldUnits = $isManual ? max(0, (int) ($product->sold_quantity ?? 0)) : 0;
        $soldUnits = (int) $product->sold_status === 1
            ? $totalUnits
            : min($rawSoldUnits, $totalUnits);
        $remainingUnits = max(0, $totalUnits - $soldUnits);
        $status = strtolower(trim((string) $product->status));

        if ($remainingUnits === 0) {
            $reportingStatus = 'sold';
        } elseif ((int) $product->scrap_status === 1) {
            $reportingStatus = 'scrap';
        } elseif ($status === 'issued' || ($isManual && $product->assigned_to && $status !== 'hold')) {
            $reportingStatus = 'issued';
        } elseif ($status === 'hold') {
            $reportingStatus = 'hold';
        } elseif (in_array($status, ['active', 'ready for sale'], true)) {
            $reportingStatus = 'ready';
        } else {
            $reportingStatus = 'not_ready';
        }

        return [
            'record_count' => 1,
            'total_units' => $totalUnits,
            'sold_units' => $soldUnits,
            'remaining_units' => $remainingUnits,
            'raw_sold_units' => $rawSoldUnits,
            'reporting_status' => $reportingStatus,
            'reporting_status_label' => self::STATUS_LABELS[$reportingStatus],
            'is_partially_sold' => $soldUnits > 0 && $remainingUnits > 0,
            'age_days' => $product->created_at instanceof CarbonInterface
                ? $product->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                : null,
            'days_since_update' => $product->updated_at instanceof CarbonInterface
                ? $product->updated_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                : null,
        ];
    }

    public function summary(Collection $products): array
    {
        $statusUnits = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
        $statusRecords = array_fill_keys(array_keys(self::STATUS_LABELS), 0);
        $aging = ['0_7' => 0, '8_15' => 0, '16_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];

        foreach ($products as $product) {
            if ($product->sold_units > 0) {
                $statusUnits['sold'] += $product->sold_units;
            }

            if ($product->remaining_units > 0) {
                $statusUnits[$product->reporting_status] += $product->remaining_units;
            }

            $statusRecords[$product->reporting_status]++;
            $aging[$this->ageBucket((int) $product->age_days)] += $product->total_units;
        }

        $exceptions = $this->exceptions($products);

        return [
            'record_count' => $products->count(),
            'total_units' => (int) $products->sum('total_units'),
            'available_units' => (int) ($statusUnits['ready'] + $statusUnits['not_ready'] + $statusUnits['hold']),
            'sold_units' => (int) $statusUnits['sold'],
            'issued_units' => (int) $statusUnits['issued'],
            'exception_count' => $exceptions->count(),
            'status_units' => $statusUnits,
            'status_records' => $statusRecords,
            'aging' => $aging,
        ];
    }

    public function exceptions(Collection $products): Collection
    {
        return $this->exceptions->detectForProducts($products);
    }

    public function categoryBreakdown(Collection $products): Collection
    {
        return $products
            ->groupBy(fn (Product $product) => $product->category?->name ?: 'Uncategorized')
            ->map(function (Collection $categoryProducts, string $name) {
                return [
                    'name' => $name,
                    'records' => $categoryProducts->count(),
                    'units' => (int) $categoryProducts->sum('total_units'),
                    'ready_units' => (int) $categoryProducts
                        ->where('reporting_status', 'ready')
                        ->sum('remaining_units'),
                    'exception_count' => $this->exceptions($categoryProducts)->count(),
                ];
            })
            ->sortByDesc('units')
            ->values();
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'sold') {
            $query->where(function ($sold) {
                $sold->where('sold_status', 1)
                    ->orWhere(function ($manual) {
                        $manual->where('manual_status', 1)
                            ->whereHas('gatePassItems', fn ($item) => $item
                                ->whereNull('returned_at')
                                ->whereHas('gatePass', fn ($gatePass) => $gatePass->where('status', '!=', 'REJECTED')));
                    });
            });
        } elseif ($status === 'scrap') {
            $query->where('scrap_status', 1)->where('sold_status', 0);
        } elseif ($status === 'issued') {
            $query->warehouseIssued();
        } elseif ($status === 'hold') {
            $query->whereRaw("LOWER(status) = 'hold'")
                ->where('sold_status', 0)
                ->where('scrap_status', 0);
        } elseif ($status === 'ready') {
            $query->whereIn(\DB::raw('LOWER(status)'), ['active', 'ready for sale'])
                ->warehouseNotIssued()
                ->where('sold_status', 0)
                ->where('scrap_status', 0);
        } elseif ($status === 'not_ready') {
            $query->where(function ($notReady) {
                $notReady->whereNull('status')
                    ->orWhereNotIn(\DB::raw('LOWER(status)'), ['active', 'ready for sale', 'issued', 'hold']);
            })->warehouseNotIssued()
                ->where('sold_status', 0)
                ->where('scrap_status', 0);
        }
    }

    private function applyAgeBucket(Builder $query, string $bucket): void
    {
        $today = now()->startOfDay();

        match ($bucket) {
            '0_7' => $query->whereDate('created_at', '>=', $today->copy()->subDays(7)),
            '8_15' => $query->whereBetween('created_at', [$today->copy()->subDays(15), $today->copy()->subDays(8)->endOfDay()]),
            '16_30' => $query->whereBetween('created_at', [$today->copy()->subDays(30), $today->copy()->subDays(16)->endOfDay()]),
            '31_60' => $query->whereBetween('created_at', [$today->copy()->subDays(60), $today->copy()->subDays(31)->endOfDay()]),
            '61_90' => $query->whereBetween('created_at', [$today->copy()->subDays(90), $today->copy()->subDays(61)->endOfDay()]),
            '90_plus' => $query->whereDate('created_at', '<', $today->copy()->subDays(90)),
            default => null,
        };
    }

    private function ageBucket(int $days): string
    {
        return match (true) {
            $days <= 7 => '0_7',
            $days <= 15 => '8_15',
            $days <= 30 => '16_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => '90_plus',
        };
    }
}
