<?php

namespace Tests\Unit;

use App\Imports\ProductBulkUpdateImport;
use App\Imports\ProductBulkUpdateSheetImport;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ProductBulkUpdateImportTest extends TestCase
{
    #[Test]
    public function it_resolves_relation_names_by_exact_case_insensitive_match(): void
    {
        class_exists(ProductBulkUpdateImport::class);
        $sheet = new ProductBulkUpdateSheetImport(10, 20);
        $method = new ReflectionMethod($sheet, 'updatesForRow');

        $updates = $method->invoke($sheet, [
            'category' => '  LAPTOPS ',
            'warehouse_location' => 'karachi MAIN warehouse',
            'disposition' => 'READY for SALE',
            'workflow_stage' => 'quality CHECK',
        ], 2, [
            'categories' => ['laptops' => 101],
            'locations' => ['karachi main warehouse' => 202],
            'employees' => [],
            'dispositions' => ['ready for sale' => 303],
            'workflowStages' => ['quality check' => 404],
        ]);

        $this->assertSame([
            'category_id' => 101,
            'warehouse_location_id' => 202,
            'disposition_id' => 303,
            'work_flow_stage_id' => 404,
        ], $updates);
        $this->assertSame([], $sheet->errors());
    }

    #[Test]
    public function it_does_not_accept_partial_relation_names(): void
    {
        class_exists(ProductBulkUpdateImport::class);
        $sheet = new ProductBulkUpdateSheetImport(10, 20);
        $method = new ReflectionMethod($sheet, 'updatesForRow');

        $updates = $method->invoke($sheet, ['category' => 'Laptop'], 2, [
            'categories' => ['laptops and computers' => 101],
            'locations' => [],
            'employees' => [],
            'dispositions' => [],
            'workflowStages' => [],
        ]);

        $this->assertSame([], $updates);
        $this->assertSame(
            ["Row 2: category 'Laptop' was not found in the reference data."],
            $sheet->errors()
        );
    }
}
