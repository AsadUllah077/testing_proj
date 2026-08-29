<?php

namespace Tests\Unit;

use App\Support\AccountingDate;
use App\Support\DecimalAmount;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountingDateTest extends TestCase
{
    public static function accountingDates(): array
    {
        return [
            ['2026-06-30', 'UTC'],
            ['2026-07-01', 'Asia/Karachi'],
            ['2026-07-01', 'America/Los_Angeles'],
            ['2026-07-01', 'Pacific/Auckland'],
        ];
    }

    #[DataProvider('accountingDates')]
    public function test_date_only_values_never_shift_with_timezone(string $input, string $timezone): void
    {
        config(['app.timezone' => $timezone]);

        $this->assertSame($input, AccountingDate::fromIso($input));
    }

    public function test_accounting_decimal_comparison_does_not_use_binary_float_equality(): void
    {
        $this->assertTrue(DecimalAmount::equals('140000.0000', '140000'));
        $this->assertFalse(DecimalAmount::equals('15970794.0001', '15970794.0000'));
        $this->assertTrue(DecimalAmount::isZero('0.0000'));
        $this->assertSame('15970794.1000', DecimalAmount::sum(['140000.0000', '15830794.1000']));
    }
}
