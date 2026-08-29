<?php

namespace App\Imports;

use App\Models\Barcode;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\WarehouseLocation;
use App\Models\WorkflowStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductBulkUpdateImport implements WithMultipleSheets
{
    private ProductBulkUpdateSheetImport $productsSheet;

    public function __construct(int $companyId, int $userId)
    {
        $this->productsSheet = new ProductBulkUpdateSheetImport($companyId, $userId);
    }

    public function sheets(): array
    {
        return [0 => $this->productsSheet];
    }

    public function errors(): array
    {
        return $this->productsSheet->errors();
    }

    public function updatedCount(): int
    {
        return $this->productsSheet->updatedCount();
    }

    public function unchangedCount(): int
    {
        return $this->productsSheet->unchangedCount();
    }
}

class ProductBulkUpdateSheetImport implements ToCollection, WithHeadingRow
{
    private const CLEAR_VALUE = '__CLEAR__';

    private array $errors = [];

    private int $updatedCount = 0;

    private int $unchangedCount = 0;

    public function __construct(private readonly int $companyId, private readonly int $userId) {}

    public function collection(Collection $rows): void
    {
        $prepared = [];
        $seenUniqueIds = [];
        $lookups = $this->lookups();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $row->toArray();

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $uniqueId = trim((string) ($data['unique_id'] ?? ''));
            if ($uniqueId === '') {
                $this->errors[] = "Row {$rowNumber}: Unique ID is required.";

                continue;
            }

            $uniqueKey = mb_strtolower($uniqueId);
            if (isset($seenUniqueIds[$uniqueKey])) {
                $this->errors[] = "Row {$rowNumber}: Unique ID '{$uniqueId}' is duplicated in the file.";

                continue;
            }
            $seenUniqueIds[$uniqueKey] = true;

            $product = Product::withoutGlobalScopes()
                ->where('company_id', $this->companyId)
                ->where('unique_id', $uniqueId)
                ->first();

            if (! $product) {
                $this->errors[] = "Row {$rowNumber}: Product '{$uniqueId}' was not found for your company.";

                continue;
            }

            $updates = $this->updatesForRow($data, $rowNumber, $lookups);
            if ($updates !== null) {
                $prepared[] = compact('product', 'updates', 'rowNumber');
            }
        }

        if ($this->errors !== []) {
            return;
        }

        DB::transaction(function () use ($prepared): void {
            foreach ($prepared as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $updates = $item['updates'];

                if ($updates === []) {
                    $this->unchangedCount++;

                    continue;
                }

                $oldValues = $product->getOriginal();
                $product->fill($updates);

                if (! $product->isDirty()) {
                    $this->unchangedCount++;

                    continue;
                }

                $product->save();
                $changes = $product->getChanges();
                unset($changes['updated_at']);

                foreach ($changes as $field => $newValue) {
                    $oldValue = $oldValues[$field] ?? null;
                    $product->histories()->create([
                        'action' => 'bulk_excel_update',
                        'field' => $field,
                        'old_value' => $this->historyValue($oldValue),
                        'new_value' => $this->historyValue($newValue),
                        'notes' => 'Updated via bulk product Excel import',
                        'changed_by' => $this->userId,
                        'company_id' => $this->companyId,
                    ]);
                }

                if (array_intersect(array_keys($changes), ['status', 'assigned_to', 'disposition_id']) !== []) {
                    Barcode::withoutGlobalScopes()
                        ->where('company_id', $this->companyId)
                        ->where('unique_id', $product->unique_id)
                        ->where('status', '!=', 'used')
                        ->update(['status' => 'used']);
                }

                $this->updatedCount++;
            }
        });
    }

    private function updatesForRow(array $row, int $rowNumber, array $lookups): ?array
    {
        $updates = [];

        $textFields = [
            'title' => ['title', 255, false],
            'dpn_sn_number' => ['dpn_sn_number', 255, true],
            'product_details' => ['product_details', null, true],
            'remarks' => ['remarks', null, true],
            'problem' => ['problem', 255, true],
        ];

        foreach ($textFields as $heading => [$field, $max, $clearable]) {
            if (! $this->hasValue($row[$heading] ?? null)) {
                continue;
            }

            $value = trim((string) $row[$heading]);
            if ($value === self::CLEAR_VALUE) {
                if (! $clearable) {
                    $this->errors[] = "Row {$rowNumber}: Title cannot be cleared.";

                    continue;
                }
                $updates[$field] = null;

                continue;
            }

            if ($max !== null && mb_strlen($value) > $max) {
                $this->errors[] = "Row {$rowNumber}: {$heading} may not exceed {$max} characters.";

                continue;
            }
            $updates[$field] = $value;
        }

        if ($this->hasValue($row['count'] ?? null)) {
            $count = filter_var($row['count'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($count === false) {
                $this->errors[] = "Row {$rowNumber}: Count must be a whole number of zero or more.";
            } else {
                $updates['count'] = $count;
            }
        }

        $relations = [
            'category' => ['category_id', $lookups['categories']],
            'warehouse_location' => ['warehouse_location_id', $lookups['locations']],
            'assigned_employee' => ['assigned_to', $lookups['employees']],
            'disposition' => ['disposition_id', $lookups['dispositions']],
            'workflow_stage' => ['work_flow_stage_id', $lookups['workflowStages']],
        ];

        foreach ($relations as $heading => [$field, $lookup]) {
            if (! $this->hasValue($row[$heading] ?? null)) {
                continue;
            }

            $value = trim((string) $row[$heading]);
            if ($value === self::CLEAR_VALUE) {
                $updates[$field] = null;

                continue;
            }

            $resolved = $this->resolveLookup($value, $lookup);
            if ($resolved === null) {
                $label = str_replace('_', ' ', $heading);
                $this->errors[] = "Row {$rowNumber}: {$label} '{$value}' was not found in the reference data.";
            } else {
                $updates[$field] = $resolved;
            }
        }

        if ($this->hasValue($row['status'] ?? null)) {
            $status = ucfirst(strtolower(trim((string) $row['status'])));
            if (! in_array($status, ['Active', 'Inactive', 'Hold'], true)) {
                $this->errors[] = "Row {$rowNumber}: Status must be Active, Inactive, or Hold.";
            } else {
                $updates['status'] = $status;
            }
        }

        if ($this->hasValue($row['specifications_json'] ?? null)) {
            $value = trim((string) $row['specifications_json']);
            if ($value === self::CLEAR_VALUE) {
                $updates['specifications'] = null;
            } else {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    $this->errors[] = "Row {$rowNumber}: Specifications JSON must be a valid JSON object or array.";
                } else {
                    $updates['specifications'] = $decoded;
                }
            }
        }

        return $updates;
    }

    private function lookups(): array
    {
        return [
            'categories' => $this->modelLookup(ProductCategory::withoutGlobalScopes()->where('company_id', $this->companyId)->get(['id', 'name'])),
            'locations' => $this->modelLookup(WarehouseLocation::withoutGlobalScopes()->where('company_id', $this->companyId)->get(['id', 'name'])),
            'dispositions' => $this->modelLookup(Disposition::withoutGlobalScopes()->where('company_id', $this->companyId)->get(['id', 'name'])),
            'workflowStages' => $this->modelLookup(WorkflowStage::withoutGlobalScopes()->where('company_id', $this->companyId)->get(['id', 'name'])),
            'employees' => $this->employeeLookup(),
        ];
    }

    private function modelLookup(Collection $models): array
    {
        $lookup = [];
        foreach ($models as $model) {
            $lookup[(string) $model->id] = (int) $model->id;
            $lookup[mb_strtolower(trim((string) $model->name))] = (int) $model->id;
        }

        return $lookup;
    }

    private function employeeLookup(): array
    {
        $lookup = [];
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $this->companyId)
            ->get(['id', 'employee_id', 'full_name']);

        foreach ($employees as $employee) {
            $lookup[(string) $employee->id] = (int) $employee->id;
            $lookup[mb_strtolower(trim((string) $employee->full_name))] = (int) $employee->id;
            if ($employee->employee_id) {
                $lookup[mb_strtolower(trim((string) $employee->employee_id))] = (int) $employee->id;
                $lookup[mb_strtolower(trim($employee->employee_id.' - '.$employee->full_name))] = (int) $employee->id;
            }
        }

        return $lookup;
    }

    private function resolveLookup(string $value, array $lookup): ?int
    {
        return $lookup[$value] ?? $lookup[mb_strtolower($value)] ?? null;
    }

    private function hasValue(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->hasValue($value)) {
                return false;
            }
        }

        return true;
    }

    private function historyValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) ? json_encode($value) : (string) $value;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function updatedCount(): int
    {
        return $this->updatedCount;
    }

    public function unchangedCount(): int
    {
        return $this->unchangedCount;
    }
}
