<?php

namespace Tests\Unit;

use App\Models\LogisticsLcPayment;
use PHPUnit\Framework\TestCase;

class LogisticsLcPaymentConversionTest extends TestCase
{
    public function test_it_converts_usd_amount_to_pkr(): void
    {
        $payment = new LogisticsLcPayment([
            'amount' => 100,
            'currency' => 'USD',
            'currency_value' => 280,
        ]);

        $this->assertSame(100.0, $payment->amountInUsd());
        $this->assertSame(28000.0, $payment->amountInPkr());
    }

    public function test_it_converts_pkr_amount_to_usd(): void
    {
        $payment = new LogisticsLcPayment([
            'amount' => 28000,
            'currency' => 'PKR',
            'currency_value' => 280,
        ]);

        $this->assertSame(28000.0, $payment->amountInPkr());
        $this->assertSame(100.0, $payment->amountInUsd());
    }

    public function test_it_keeps_the_original_currency_when_conversion_value_is_missing(): void
    {
        $usdPayment = new LogisticsLcPayment([
            'amount' => 100,
            'currency' => 'USD',
        ]);
        $pkrPayment = new LogisticsLcPayment([
            'amount' => 28000,
            'currency' => 'PKR',
        ]);

        $this->assertSame(100.0, $usdPayment->amountInUsd());
        $this->assertNull($usdPayment->amountInPkr());
        $this->assertSame(28000.0, $pkrPayment->amountInPkr());
        $this->assertNull($pkrPayment->amountInUsd());
    }

    public function test_legacy_payments_without_a_currency_are_treated_as_pkr(): void
    {
        $payment = new LogisticsLcPayment([
            'amount' => 28000,
            'currency_value' => 280,
        ]);

        $this->assertSame(28000.0, $payment->amountInPkr());
        $this->assertSame(100.0, $payment->amountInUsd());
    }
}
