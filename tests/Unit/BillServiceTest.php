<?php

namespace Tests\Unit;

use App\Models\Bill;
use App\Services\BillService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillServiceTest extends TestCase
{
    public function test_it_calculates_quantity_weight_discount_tax_and_payable_consistently(): void
    {
        $totals = app(BillService::class)->calculateTotals([
            ['quantity' => 2, 'kg' => 0, 'price' => 50, 'discount_percentage' => 10],
            ['quantity' => 0, 'kg' => 2.5, 'price' => 40, 'discount_percentage' => 5],
        ]);

        $this->assertSame(200.0, $totals['sub_total']);
        $this->assertSame(15.0, $totals['discount']);
        $this->assertSame(185.0, $totals['total']);
        $this->assertSame(185.0, $totals['total_after_discount']);
    }

    #[DataProvider('paymentStatuses')]
    public function test_payment_status_is_independent_from_approval(float $paid, float $remaining, string $expected): void
    {
        $this->assertSame($expected, app(BillService::class)->paymentStatus($paid, $remaining));
    }

    public static function paymentStatuses(): array
    {
        return [[0, 100, 'unpaid'], [25, 75, 'partial'], [100, 0, 'paid'], [110, -10, 'overpaid']];
    }

    public function test_only_unpaid_draft_or_rejected_bill_is_editable(): void
    {
        $this->assertTrue((new Bill(['approval_status' => 'draft', 'payment_status' => 'unpaid', 'paid_amount' => 0]))->isEditable());
        $this->assertTrue((new Bill(['approval_status' => 'rejected', 'payment_status' => 'unpaid', 'paid_amount' => 0]))->isEditable());
        $this->assertFalse((new Bill(['approval_status' => 'approved', 'payment_status' => 'unpaid', 'paid_amount' => 0]))->isEditable());
        $this->assertFalse((new Bill(['approval_status' => 'draft', 'payment_status' => 'partial', 'paid_amount' => 1]))->isEditable());
    }
}
