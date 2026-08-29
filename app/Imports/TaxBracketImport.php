<?php

namespace App\Imports;

use App\Models\TaxBracket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TaxBracketImport implements ToCollection, WithHeadingRow
{
    protected array $errors = [];
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $replacedFiscalYears = [];

    public function __construct(
        protected int $companyId,
        protected string $defaultFiscalYear,
        protected bool $replaceExisting = false,
    ) {
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            $normalizedRows = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $row = $row->toArray();

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $data = $this->normalizeRow($row, $rowNumber);

                if ($data === null) {
                    $this->skippedCount++;
                    continue;
                }

                $normalizedRows[] = $data;
            }

            foreach ($normalizedRows as $data) {
                $rowNumber = $data['_row_number'];
                unset($data['_row_number']);

                if ($data['rate'] === null || $data['rate'] < 0 || $data['rate'] > 100) {
                    $this->errors[] = "Row {$rowNumber}: Rate must be between 0 and 100.";
                    $this->skippedCount++;
                    continue;
                }

                if ($this->replaceExisting && ! in_array($data['fiscal_year'], $this->replacedFiscalYears, true)) {
                    TaxBracket::withoutGlobalScopes()
                        ->where('company_id', $this->companyId)
                        ->where('fiscal_year', $data['fiscal_year'])
                        ->forceDelete();

                    $this->replacedFiscalYears[] = $data['fiscal_year'];
                }

                $this->saveBracket($data);
                $this->importedCount++;
            }
        });
    }

    protected function normalizeRow(array $row, int $rowNumber): ?array
    {
        $fiscalYear = $this->defaultFiscalYear;
        $minIncome = $this->number($this->value($row, [
            'min_income',
            'min_income_pkr',
            'minimum_income',
            'minimum_income_pkr',
            'income_from',
            'from_income',
            'income_min',
            'from',
            'min',
        ]));
        $maxIncome = $this->nullableNumber($this->value($row, [
            'max_income',
            'max_income_pkr',
            'maximum_income',
            'maximum_income_pkr',
            'income_to',
            'to_income',
            'income_max',
            'to',
            'max',
        ]));
        $rate = $this->number($this->value($row, [
            'rate',
            'rate_percent',
            'tax_rate',
            'tax_rate_percent',
            'tax_percentage',
            'percentage',
        ]));
        $sortOrder = $rowNumber - 1;

        $messages = [];

        if ($minIncome === null && $sortOrder === 1) {
            $minIncome = 0;
        }

        if ($rate === null && $sortOrder === 1) {
            $rate = 0;
        }

        if ($fiscalYear === '' || strlen($fiscalYear) > 50) {
            $messages[] = 'Fiscal year is required and must be 50 characters or less.';
        }

        if ($minIncome === null || $minIncome < 0) {
            $messages[] = 'Min income must be zero or greater. Accepted headings include min_income, Min Income (PKR), Income From, or From.';
        }

        if ($maxIncome !== null && $minIncome !== null && $maxIncome <= $minIncome) {
            $messages[] = 'Max income must be greater than min income, or blank for no limit.';
        }

        if ($sortOrder < 0) {
            $messages[] = 'Sort order must be zero or greater.';
        }

        if (! empty($messages)) {
            $headings = implode(', ', array_keys($row));
            $this->errors[] = 'Row ' . $rowNumber . ': ' . implode(' ', $messages) . " Headings found: {$headings}.";
            return null;
        }

        return [
            'company_id' => $this->companyId,
            'fiscal_year' => $fiscalYear,
            'min_income' => $minIncome,
            'max_income' => $maxIncome,
            'base_tax' => 0,
            'rate' => $rate,
            'sort_order' => $sortOrder,
            '_row_number' => $rowNumber,
        ];
    }

    protected function saveBracket(array $data): void
    {
        if ($this->replaceExisting) {
            TaxBracket::withoutGlobalScopes()->create($data);
            return;
        }

        $bracket = TaxBracket::withoutGlobalScopes()
            ->withTrashed()
            ->where('company_id', $this->companyId)
            ->where('fiscal_year', $data['fiscal_year'])
            ->where('sort_order', $data['sort_order'])
            ->first();

        if (! $bracket) {
            $bracket = new TaxBracket();
        } elseif ($bracket->trashed()) {
            $bracket->restore();
        }

        $bracket->fill($data);
        $bracket->save();
    }

    protected function value(array $row, array $keys)
    {
        $normalizedRow = [];

        foreach ($row as $key => $value) {
            $normalizedRow[$this->normalizeKey((string) $key)] = $value;
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);

            if (array_key_exists($normalizedKey, $normalizedRow) && $normalizedRow[$normalizedKey] !== null && trim((string) $normalizedRow[$normalizedKey]) !== '') {
                return $normalizedRow[$normalizedKey];
            }
        }

        return null;
    }

    protected function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim($key, '_');
    }

    protected function number($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $clean = preg_replace('/[^\d.\-]/', '', trim((string) $value));

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function nullableNumber($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = strtolower(trim((string) $value));

        if ($text === '' || in_array($text, ['null', 'n/a', 'na', '-', 'unlimited', 'no limit'], true)) {
            return null;
        }

        return $this->number($value);
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}
