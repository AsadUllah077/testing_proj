<?php

namespace App\Services\Account;

use App\Imports\OpeningBalanceConversionImport;
use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Support\AccountingConstants;
use App\Support\OpeningBalanceAccountEligibility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use App\Support\DecimalAmount;
use InvalidArgumentException;

class OpeningBalanceImportService
{
    public function __construct(private readonly AccountingPeriodResolver $periodResolver)
    {
    }

    public function importToDraft(UploadedFile $file, array $batchData, int $companyId): OpeningBalanceBatch
    {
        $period = $this->periodResolver->resolve($companyId, $batchData['conversion_date'], false);
        $sourceFileHash = hash_file('sha256', $file->getRealPath());
        if (OpeningBalanceBatch::query()->where('company_id', $companyId)->where('source_file_hash', $sourceFileHash)
            ->whereDate('closing_date', $batchData['closing_date'])->whereNotIn('status', [AccountingConstants::BATCH_STATUS_REVERSED])->exists()) {
            throw ValidationException::withMessages(['import_file' => 'This exact source file has already been imported for the selected balance date. Open the existing draft or revise the posted conversion set.']);
        }
        $import = new OpeningBalanceConversionImport();
        Excel::import($import, $file);

        [$lines, $errors] = $this->buildLines($import->rows(), $companyId);

        if ($errors) {
            throw ValidationException::withMessages(['import' => $errors]);
        }

        return DB::transaction(function () use ($batchData, $file, $sourceFileHash, $lines, $companyId, $period) {
            $batch = OpeningBalanceBatch::create([
                'company_id' => $companyId,
                'reference' => $batchData['reference'],
                'idempotency_key' => hash('sha256', implode('|', [$companyId, $batchData['source_system'], $batchData['reference'], $batchData['conversion_date']])),
                'source_system' => $batchData['source_system'],
                'source_file_reference' => mb_substr($file->getClientOriginalName(), 0, 255),
                'source_file_hash' => $sourceFileHash,
                'batch_kind' => 'conversion',
                'closing_date' => $batchData['closing_date'],
                'conversion_date' => $batchData['conversion_date'],
                'go_live_date' => $batchData['go_live_date'] ?? \Carbon\CarbonImmutable::parse($batchData['closing_date'])->addDay()->toDateString(),
                'fiscal_year' => $period->name,
                'notes' => $batchData['notes'] ?? null,
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'created_by' => auth('web')->id(),
                'updated_by' => auth('web')->id(),
            ]);

            $batch->lines()->createMany($lines);

            $totals = $batch->lines()
                ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
                ->first();

            $batch->update([
                'total_debit' => $totals?->debit_total ?? 0,
                'total_credit' => $totals?->credit_total ?? 0,
            ]);

            return $batch;
        });
    }

    private function buildLines($rows, int $companyId): array
    {
        $errors = [];
        $lines = [];
        $mappedAccountIds = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $erpAccountNumber = trim((string) ($row['erp_account_number'] ?? $row['account_number'] ?? $row['account_code'] ?? ''));
            $erpAccountName = trim((string) ($row['erp_account_name'] ?? $row['account_name'] ?? ''));
            try {
                $debit = $this->toDecimal($row['debit'] ?? 0);
                $credit = $this->toDecimal($row['credit'] ?? 0);
            } catch (InvalidArgumentException $exception) {
                $errors[] = "Row {$rowNumber}: debit and credit must be non-negative numbers with no more than four decimal places.";
                continue;
            }

            if (DecimalAmount::isZero($debit) && DecimalAmount::isZero($credit)) {
                continue;
            }

            if ($erpAccountNumber === '' && $erpAccountName === '') {
                $errors[] = "Row {$rowNumber}: ERP account number or name is required when an amount is entered.";
                continue;
            }

            if (! DecimalAmount::isZero($debit) && ! DecimalAmount::isZero($credit)) {
                $errors[] = "Row {$rowNumber}: enter an amount in either debit or credit, not both.";
                continue;
            }

            $account = $this->findAccount($erpAccountNumber, $erpAccountName, $companyId);

            if (! $account) {
                $errors[] = "Row {$rowNumber}: ERP account '{$erpAccountNumber} {$erpAccountName}' was not found for the current company.";
                continue;
            }

            if ($reason = OpeningBalanceAccountEligibility::reason($account)) {
                $errors[] = "Row {$rowNumber}: {$reason}";
                continue;
            }

            if (isset($mappedAccountIds[$account->id])) {
                $errors[] = "Row {$rowNumber}: duplicate mapping for ERP account '{$account->account_number} - {$account->name}'. First seen on row {$mappedAccountIds[$account->id]}.";
                continue;
            }

            $mappedAccountIds[$account->id] = $rowNumber;

            $lines[] = [
                'company_id' => $companyId,
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $this->nullableText($row['description'] ?? null),
                'source_account_code' => $this->nullableText($row['xero_account_code'] ?? null),
                'source_account_name' => $this->nullableText($row['xero_account_name'] ?? null),
                'source_reference' => $this->nullableText($row['source_reference'] ?? ($row['xero_account_code'] ?? null)),
            ];
        }

        if (empty($lines) && empty($errors)) {
            $errors[] = 'The import file does not contain any valid opening balance rows.';
        }

        return [$lines, $errors];
    }

    private function findAccount(string $accountNumber, string $accountName, int $companyId): ?ChartOfAccount
    {
        $baseQuery = ChartOfAccount::query()
            ->when(Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', $companyId));

        if ($accountNumber !== '') {
            return (clone $baseQuery)->where('account_number', $accountNumber)->first();
        }

        if ($accountName !== '') {
            return (clone $baseQuery)->whereRaw('LOWER(name) = ?', [strtolower($accountName)])->first();
        }

        return null;
    }

    private function toDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        return DecimalAmount::normalize(str_replace([',', ' '], '', (string) $value));
    }

    private function isEmptyRow($row): bool
    {
        return collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isEmpty();
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
