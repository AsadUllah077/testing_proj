<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Warehouse\ProductReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductReportServiceTest extends TestCase
{
    private ProductReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-27 12:00:00');
        $this->reports = new ProductReportService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_barcode_product_is_one_ready_unit(): void
    {
        $product = $this->product([
            'manual_status' => 0,
            'status' => 'Active',
        ]);

        $metrics = $this->reports->metricsFor($product);

        $this->assertSame(1, $metrics['total_units']);
        $this->assertSame(1, $metrics['remaining_units']);
        $this->assertSame('ready', $metrics['reporting_status']);
    }

    public function test_partially_sold_manual_product_splits_sold_and_remaining_units(): void
    {
        $product = $this->product([
            'manual_status' => 1,
            'count' => 10,
            'status' => 'Hold',
        ]);
        $product->setAttribute('sold_quantity', 3);

        $metrics = $this->reports->metricsFor($product);

        $this->assertSame(10, $metrics['total_units']);
        $this->assertSame(3, $metrics['sold_units']);
        $this->assertSame(7, $metrics['remaining_units']);
        $this->assertSame('hold', $metrics['reporting_status']);
        $this->assertTrue($metrics['is_partially_sold']);
    }

    public function test_status_precedence_applies_scrap_before_operational_statuses(): void
    {
        $product = $this->product([
            'manual_status' => 1,
            'count' => 4,
            'status' => 'Active',
            'scrap_status' => 1,
            'assigned_to' => 99,
        ]);

        $metrics = $this->reports->metricsFor($product);

        $this->assertSame('scrap', $metrics['reporting_status']);
        $this->assertSame(4, $metrics['remaining_units']);
    }

    public function test_summary_status_units_reconcile_to_total_inventory_units(): void
    {
        $ready = $this->product(['status' => 'Active']);
        $manual = $this->product(['manual_status' => 1, 'count' => 10, 'status' => 'Hold']);
        $manual->setAttribute('sold_quantity', 3);
        $scrap = $this->product(['status' => 'Inactive', 'scrap_status' => 1]);

        $products = $this->reports->decorate(new Collection([$ready, $manual, $scrap]));
        $summary = $this->reports->summary($products);

        $this->assertSame(12, $summary['total_units']);
        $this->assertSame(12, array_sum($summary['status_units']));
        $this->assertSame(3, $summary['sold_units']);
        $this->assertSame(7, $summary['status_units']['hold']);
        $this->assertSame(1, $summary['status_units']['ready']);
        $this->assertSame(1, $summary['status_units']['scrap']);
    }

    private function product(array $attributes = []): Product
    {
        return new Product(array_merge([
            'unique_id' => fake()->unique()->bothify('TEST-####'),
            'title' => 'Test Product',
            'manual_status' => 0,
            'count' => 0,
            'status' => 'Inactive',
            'sold_status' => 0,
            'scrap_status' => 0,
            'created_at' => Carbon::parse('2026-07-01'),
            'updated_at' => Carbon::parse('2026-07-20'),
        ], $attributes));
    }
}
