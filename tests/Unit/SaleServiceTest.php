<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Services\SaleService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    public function test_it_calculates_quantity_weight_discount_and_final_totals_consistently(): void
    {
        $totals = app(SaleService::class)->calculateTotals([
            [
                'quantity' => 2,
                'kg' => 0,
                'price' => 50,
                'discount_percentage' => 10,
            ],
            [
                'quantity' => 0,
                'kg' => 2.5,
                'price' => 40,
                'discount_percentage' => 5,
            ],
        ]);

        $this->assertSame(200.0, $totals['sub_total']);
        $this->assertSame(15.0, $totals['discount']);
        $this->assertSame(185.0, $totals['total']);
        $this->assertSame(0.0, $totals['tax_total']);
        $this->assertSame(185.0, $totals['total_after_discount']);
    }

    #[DataProvider('paymentStatuses')]
    public function test_payment_status_is_independent_from_approval_status(
        float $paid,
        float $remaining,
        string $expected
    ): void {
        $this->assertSame($expected, app(SaleService::class)->paymentStatus($paid, $remaining));
    }

    public static function paymentStatuses(): array
    {
        return [
            'unpaid' => [0, 100, 'unpaid'],
            'partial' => [25, 75, 'partial'],
            'paid' => [100, 0, 'paid'],
            'overpaid' => [110, -10, 'overpaid'],
        ];
    }

    public function test_only_unpaid_draft_or_rejected_invoice_is_editable(): void
    {
        $draft = new Sale(['approval_status' => 'draft', 'paid_amount' => 0]);
        $rejected = new Sale(['approval_status' => 'rejected', 'paid_amount' => 0]);
        $approved = new Sale(['approval_status' => 'approved', 'paid_amount' => 0]);
        $paidDraft = new Sale(['approval_status' => 'draft', 'paid_amount' => 1]);

        $this->assertTrue($draft->isEditable());
        $this->assertTrue($rejected->isEditable());
        $this->assertFalse($approved->isEditable());
        $this->assertFalse($paidDraft->isEditable());
    }
}
