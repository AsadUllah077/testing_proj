<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsBookingV2;
use App\Models\LogisticsLcPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LcPaymentsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private ?int $companyId;

    private int $importedCount = 0;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows): void
    {
        $preparedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $row = $row->toArray();

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $bookingNumber = $this->clean($row['booking_number'] ?? null);
            $booking = $bookingNumber
                ? LogisticsBookingV2::where('booking_number', $bookingNumber)->first()
                : null;

            $data = [
                'booking_number' => $bookingNumber,
                'booking_id' => $booking?->id,
                'date' => $this->normalizeDate($row['payment_date_yyyy_mm_dd'] ?? null),
                'receipt_no' => $this->clean($row['receipt_no'] ?? null),
                'payment_type' => $this->normalizePaymentType($row['payment_type'] ?? null),
                'amount' => $this->normalizeAmount($row['amount'] ?? null),
                'currency' => $this->normalizeCurrency($row['currency'] ?? null),
                'currency_value' => $this->normalizeAmount(
                    $row['currency_value_pkr_per_1_usd']
                        ?? $row['currency_value_pkr_per_usd']
                        ?? null
                ),
                'status' => $this->normalizeStatus($row['status'] ?? null),
                'details' => $this->clean($row['details'] ?? null),
                'remarks' => $this->clean($row['remarks'] ?? null),
                'company_id' => $this->companyId,
                'entry_source' => 'import',
            ];

            $validator = Validator::make($data, [
                'booking_number' => ['nullable', 'string'],
                'date' => ['nullable', 'date'],
                'receipt_no' => ['nullable', 'string', 'max:255'],
                'payment_type' => ['required', 'in:freight_payment,product_payment'],
                'amount' => ['nullable', 'numeric', 'min:0'],
                'currency' => ['required', 'in:PKR,USD'],
                'currency_value' => ['required', 'numeric', 'gt:0'],
                'status' => ['nullable', 'in:pending,completed'],
                'details' => ['nullable', 'string'],
                'remarks' => ['nullable', 'string'],
            ]);

            $validator->after(function ($validator) use ($bookingNumber, $booking, $data) {
                if ($bookingNumber && ! $booking) {
                    $validator->errors()->add('booking_number', "Booking number {$bookingNumber} was not found.");
                }

                if (! $this->hasPaymentData($data)) {
                    $validator->errors()->add('row', 'No payment data was found. Please use the LC payment import template.');
                }
            });

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->errors[] = "Row {$rowNumber}: {$message}";
                }

                continue;
            }

            unset($data['booking_number']);
            $preparedRows[] = $data;
        }

        if ($this->errors) {
            return;
        }

        if (count($preparedRows) === 0) {
            $this->errors[] = 'No payment rows were found in the uploaded file.';
            return;
        }

        DB::transaction(function () use ($preparedRows) {
            foreach ($preparedRows as $payment) {
                LogisticsLcPayment::create($payment);
                $this->importedCount++;
            }
        });
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->clean($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function hasPaymentData(array $data): bool
    {
        return $data['booking_id']
            || $data['date']
            || $data['receipt_no']
            || $data['amount'] !== null
            || $data['details']
            || $data['remarks'];
    }

    private function normalizeDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function normalizeAmount($value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        return str_replace([',', ' '], '', $value);
    }

    private function normalizeStatus($value): ?string
    {
        $value = Str::lower((string) $this->clean($value));

        if ($value === '') {
            return 'pending';
        }

        if (in_array($value, ['complete', 'completed'], true)) {
            return 'completed';
        }

        return $value;
    }

    private function normalizePaymentType($value): string
    {
        $value = Str::lower((string) $this->clean($value));
        $value = str_replace([' ', '-'], '_', $value);

        return match ($value) {
            'freight', 'freight_payment' => 'freight_payment',
            '', 'product', 'product_payment' => 'product_payment',
            default => $value,
        };
    }

    private function normalizeCurrency($value): string
    {
        $value = Str::upper((string) $this->clean($value));

        return $value ?: 'PKR';
    }

    private function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
