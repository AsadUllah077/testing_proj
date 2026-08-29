<?php

namespace App\Services\Account;

use App\Models\AccountingPeriod;
use App\Models\BankReconciliationEvent;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankStatementImportService
{
    public function importChunk(int $companyId, int $bankAccountId, array $rows, array $metadata): array
    {
        return DB::transaction(function () use ($companyId, $bankAccountId, $rows, $metadata) {
            $statement = $this->statement($companyId, $bankAccountId, $metadata);
            if ($statement->status === 'finalized') {
                throw ValidationException::withMessages(['statement' => 'A finalized statement cannot receive more rows.']);
            }

            $controls = app(BankReconciliationControlService::class);
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $parsed = $this->parseRow($row);
                    if ($parsed === null) { $skipped++; continue; }
                    if ($parsed['date'] < $statement->start_date->format('Y-m-d') || $parsed['date'] > $statement->end_date->format('Y-m-d')) {
                        throw new \InvalidArgumentException('Date is outside the declared statement period.');
                    }

                    $period = AccountingPeriod::findPeriodForDate($parsed['date'], $companyId);
                    if (! $period || $period->is_locked) throw new \InvalidArgumentException('Date is outside an unlocked accounting period.');
                    $rowHash = $controls->rowHash($bankAccountId, $parsed);
                    if (BankStatementLine::withoutGlobalScopes()->where('bank_statement_import_id', $statement->id)->where('row_hash', $rowHash)->exists()) {
                        $skipped++;
                        continue;
                    }

                    BankStatementLine::create([
                        'company_id' => $companyId,
                        'accounting_period_id' => $period->id,
                        'bank_account_id' => $bankAccountId,
                        'bank_statement_import_id' => $statement->id,
                        'date' => $parsed['date'],
                        'amount' => $parsed['amount'],
                        'remaining_amount' => $parsed['amount'],
                        'payee' => $parsed['payee'],
                        'description' => $parsed['description'],
                        'reference' => $parsed['reference'],
                        'status' => 'Unreconciled',
                        'unique_no' => $controls->uniqueNumber($companyId),
                        'row_hash' => $rowHash,
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $index + 1, 'message' => $e->getMessage(), 'values' => $row];
                }
            }

            $statement->update([
                'line_count' => $statement->lines()->count(),
                'status' => 'in_progress',
            ]);
            BankReconciliationEvent::create([
                'company_id' => $companyId,
                'bank_statement_import_id' => $statement->id,
                'event' => 'chunk_imported',
                'metadata' => ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors],
                'user_id' => auth('web')->id(),
            ]);

            return ['statement_import_id' => $statement->id, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
        }, 3);
    }

    private function statement(int $companyId, int $bankAccountId, array $metadata): BankStatementImport
    {
        if (! empty($metadata['statement_import_id'])) {
            return BankStatementImport::where('company_id', $companyId)
                ->where('bank_account_id', $bankAccountId)->lockForUpdate()->findOrFail($metadata['statement_import_id']);
        }

        try {
            return BankStatementImport::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'statement_number' => $metadata['statement_number'],
                'start_date' => $metadata['start_date'],
                'end_date' => $metadata['end_date'],
                'opening_balance' => $metadata['opening_balance'],
                'closing_balance' => $metadata['closing_balance'],
                'currency' => strtoupper($metadata['currency'] ?? 'USD'),
                'source_filename' => $metadata['source_filename'] ?? null,
                'file_hash' => $metadata['file_hash'] ?? null,
                'status' => 'draft',
                'imported_by' => auth('web')->id(),
                'imported_at' => now(),
            ]);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'file' => 'This statement number or exact file has already been imported for the selected bank account.',
            ]);
        }
    }

    private function parseRow(array $row): ?array
    {
        $dateValue = trim((string) ($row[0] ?? ''));
        if ($dateValue === '') return null;
        $date = $this->parseDate($dateValue);

        // Supports Amount or separate Debit/Credit columns.
        if (array_key_exists(2, $row) && is_numeric($this->number($row[1])) && is_numeric($this->number($row[2])) && count($row) >= 6) {
            $debit = abs($this->number($row[1]));
            $credit = abs($this->number($row[2]));
            if ($debit > 0 && $credit > 0) throw new \InvalidArgumentException('A row cannot contain both debit and credit amounts.');
            $amount = $credit > 0 ? -$credit : $debit;
            $offset = 1;
        } else {
            $amount = $this->number($row[1] ?? 0);
            $offset = 0;
        }
        if (abs($amount) < 0.00005) throw new \InvalidArgumentException('Amount must not be zero.');

        return [
            'date' => $date,
            'amount' => $amount,
            'payee' => $row[2 + $offset] ?? null,
            'description' => $row[3 + $offset] ?? null,
            'reference' => $row[4 + $offset] ?? null,
        ];
    }

    private function parseDate(string $value): string
    {
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
            } catch (\Throwable) {}
        }
        try { return Carbon::parse($value)->format('Y-m-d'); }
        catch (\Throwable) { throw new \InvalidArgumentException("Invalid date: {$value}"); }
    }

    private function number($value): float
    {
        $value = trim((string) $value);
        $negative = str_starts_with($value, '(') || str_ends_with($value, '-');
        $clean = preg_replace('/[^0-9.\-]/', '', $value);
        if ($clean === '' || ! is_numeric(rtrim($clean, '-'))) return 0.0;
        $number = (float) rtrim($clean, '-');
        return $negative ? -abs($number) : $number;
    }
}
