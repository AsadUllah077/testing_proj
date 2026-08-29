<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WarehouseProductExport implements FromCollection, WithHeadings, WithMapping
{
    public const AVAILABLE_COLUMNS = [
        'serial_number' => 'S.No',
        'unique_id' => 'Unique ID',
        'dpn_sn_number' => 'DPN/SN Number',
        'title' => 'Title',
        'category' => 'Category',
        'location' => 'Location',
        'assigned_to' => 'Assigned To',
        'disposition' => 'Disposition',
        'workflow_stage' => 'Stage',
        'status' => 'Status',
        'quantity' => 'Quantity',
        'last_action' => 'Last Action',
        'specifications' => 'Specifications (RAM/SSD)',
        'created_at' => 'Created At',
    ];

    protected $filters;
    protected $companyId;
    protected array $columns;
    private int $rowNumber = 0;

    public function __construct($filters = [], $companyId = null)
    {
        $this->filters = $filters;
        $this->companyId = $companyId;
        $this->columns = $this->parseColumns($filters['export_columns'] ?? null);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = $this->companyId
            ? Product::withoutGlobalScope('company')->where('company_id', $this->companyId)
            : Product::query();

        $query->select([
            'id',
            'unique_id',
            'dpn_sn_number',
            'title',
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
            'last_action',
            'specifications',
            'created_at',
        ])->with([
            'warehouseLocation:id,name',
            'category:id,name',
            'assignedTo:id,full_name',
            'disposition:id,name',
            'workflowStage:id,name',
        ]);

        $f = $this->filters;

        // Apply filters
        if ($this->hasFilter('ids')) {
            $ids = $this->parseIds($f['ids']);
            $query->whereIn('id', $ids);
        } else {
            if ($this->hasFilter('q')) {
                $search = $f['q'];
                $query->where(function ($q) use ($search) {
                    $q->where('unique_id', 'LIKE', "%{$search}%")
                        ->orWhere('dpn_sn_number', 'LIKE', "%{$search}%")
                        ->orWhere('title', 'LIKE', "%{$search}%")
                        ->orWhere('product_details', 'LIKE', "%{$search}%");
                });
            }

            if ($this->hasFilter('category')) {
                $query->where('category_id', $f['category']);
            }

            if ($this->hasFilter('location')) {
                $query->where('warehouse_location_id', $f['location']);
            }

            if ($this->hasFilter('assigned_to')) {
                if ($f['assigned_to'] === 'Assigned') {
                    $query->whereNotNull('assigned_to');
                } elseif ($f['assigned_to'] === 'Unassigned') {
                    $query->whereNull('assigned_to');
                }
            }

            if ($this->hasFilter('work_flow_stage')) {
                $query->where('work_flow_stage_id', $f['work_flow_stage']);
            }

            if ($this->hasFilter('disposition')) {
                $query->where('disposition_id', $f['disposition']);
            }

            if ($this->hasFilter('status')) {
                if ($f['status'] == 'sold') {
                    $query->where(function ($q) {
                        $q->where('sold_status', 1)
                            ->orWhere(function ($manualSold) {
                                $manualSold->where('manual_status', 1)
                                    ->whereHas('gatePassItems', function ($item) {
                                        $item->whereNull('returned_at')
                                            ->whereHas('gatePass', fn ($gatePass) => $gatePass->where('status', '!=', 'REJECTED'));
                                    });
                            });
                    });
                } elseif ($f['status'] == 'scrap') {
                    $query->where('scrap_status', 1);
                } elseif ($f['status'] == 'active') {
                    $query->where(function ($q) {
                        $q->whereRaw("LOWER(status) = 'active'")
                          ->orWhereRaw("LOWER(status) = 'ready for sale'");
                    })->warehouseNotIssued();
                } elseif ($f['status'] == 'inactive') {
                    $query->where(function ($q) {
                        $q->whereRaw("LOWER(status) != 'active'")
                          ->whereRaw("LOWER(status) != 'ready for sale'")
                          ->whereRaw("LOWER(status) != 'issued'")
                          ->whereRaw("LOWER(status) != 'hold'");
                    })->warehouseNotIssued()
                      ->where('sold_status', 0)
                      ->where('scrap_status', 0);
                } elseif ($f['status'] == 'hold') {
                    $query->whereRaw("LOWER(status) = 'hold'")
                        ->where('sold_status', 0)
                        ->where('scrap_status', 0);
                } elseif ($f['status'] == 'issued') {
                    $query->warehouseIssued();
                }
            }

            if ($this->hasFilter('manual_status')) {
                $query->where('manual_status', $f['manual_status']);
            }

            if ($this->hasFilter('date_from')) {
                $query->whereDate('created_at', '>=', $f['date_from']);
            }

            if ($this->hasFilter('date_to')) {
                $query->whereDate('created_at', '<=', $f['date_to']);
            }

            if ($this->hasFilter('updated_today') && $f['updated_today'] == '1') {
                $query->whereDate('updated_at', today());
            }

            if ($this->hasFilter('disposition_group')) {
                $groups = [
                    'pass' => ['Pass', 'Approved', 'Completed'],
                    'hold' => ['Hold', 'Pending', 'Waiting'],
                    'fail' => ['Fail', 'Rejected', 'Failed'],
                ];

                if (isset($groups[$f['disposition_group']])) {
                    $query->whereHas('disposition', fn ($q) => $q->whereIn('name', $groups[$f['disposition_group']]));
                }
            }
        }

        $sortCol = ($f['sort'] ?? null) === 'unique_id' ? 'unique_id' : 'created_at';
        $sortDir = ($f['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortCol, $sortDir)->get();
    }

    private function hasFilter(string $key): bool
    {
        return array_key_exists($key, $this->filters)
            && $this->filters[$key] !== null
            && $this->filters[$key] !== '';
    }

    private function parseIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        return array_values(array_filter(array_map('trim', $ids), fn ($id) => $id !== ''));
    }

    public function headings(): array
    {
        return array_map(fn ($column) => self::AVAILABLE_COLUMNS[$column], $this->columns);
    }

    public function columns(): array
    {
        return $this->columns;
    }

    private function parseColumns($columns): array
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        if (! is_array($columns)) {
            return array_keys(self::AVAILABLE_COLUMNS);
        }

        $selected = array_values(array_unique(array_filter(
            array_map(fn ($column) => trim((string) $column), $columns),
            fn ($column) => array_key_exists($column, self::AVAILABLE_COLUMNS)
        )));

        return $selected !== [] ? $selected : array_keys(self::AVAILABLE_COLUMNS);
    }

    public static function formatSpecifications($specifications, string $separator = ' | '): string
    {
        if (! is_array($specifications) || $specifications === []) {
            return '';
        }

        $parts = [];

        foreach ($specifications as $index => $specification) {
            if (is_array($specification)
                && (array_key_exists('key', $specification)
                    || array_key_exists('name', $specification)
                    || array_key_exists('value', $specification))) {
                $key = trim((string) ($specification['key'] ?? $specification['name'] ?? ''));
                $value = self::formatSpecificationValue($specification['value'] ?? '');
            } else {
                $key = is_string($index) ? trim($index) : '';
                $value = self::formatSpecificationValue($specification);
            }

            if (! self::isRamOrSsdSpecification($key, $value)) {
                continue;
            }

            if ($key !== '' && $value !== '') {
                $parts[] = $key.': '.$value;
            } elseif ($key !== '') {
                $parts[] = $key;
            } elseif ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode($separator, $parts);
    }

    private static function isRamOrSsdSpecification(string $key, string $value): bool
    {
        $searchable = trim($key.' '.$value);
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', ' ', $searchable));

        return preg_match('/\b(?:ram|ssd)\b/', $normalized) === 1;
    }

    private static function formatSpecificationValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return trim((string) ($value ?? ''));
    }

    public function map($product): array
    {
        $this->rowNumber++;

        return array_map(
            fn ($column) => $this->valueFor($product, $column, $this->rowNumber),
            $this->columns
        );
    }

    public function valueFor($product, string $column, int $rowNumber)
    {
        return match ($column) {
            'serial_number' => $rowNumber,
            'unique_id' => $product->unique_id,
            'dpn_sn_number' => $product->dpn_sn_number ?? '—',
            'title' => $product->title ?? '—',
            'category' => $product->category->name ?? '—',
            'location' => $product->warehouseLocation->name ?? '—',
            'assigned_to' => $product->assignedTo->full_name ?? '—',
            'disposition' => $product->disposition->name ?? '—',
            'workflow_stage' => $product->workflowStage->name ?? '—',
            'status' => $product->status ?? '—',
            'quantity' => (int) $product->manual_status === 1 ? (int) ($product->count ?? 0) : 1,
            'last_action' => $product->last_action ?? '—',
            'specifications' => self::formatSpecifications($product->specifications) ?: '—',
            'created_at' => $product->created_at?->format('d-m-Y') ?? '—',
            default => '—',
        };
    }
}
