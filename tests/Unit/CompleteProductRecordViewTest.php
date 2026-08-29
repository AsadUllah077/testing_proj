<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteProductRecordViewTest extends TestCase
{
    #[Test]
    public function it_displays_every_loaded_product_attribute(): void
    {
        $product = new Product([
            'id' => 42,
            'unique_id' => 'PROD-0042',
            'dpn_sn_number' => 'SN-9000',
            'title' => 'Test Product',
            'product_details' => 'Complete details',
            'remarks' => 'Warehouse remarks',
            'problem' => 'Damaged screen',
            'category_id' => 3,
            'warehouse_location_id' => 4,
            'assigned_to' => 5,
            'disposition_id' => 6,
            'work_flow_stage_id' => 7,
            'company_id' => 8,
            'created_by' => 9,
            'manual_status' => 0,
            'sold_status' => 0,
            'scrap_status' => 1,
            'specifications' => [['key' => 'RAM', 'value' => '16 GB']],
            'future_product_field' => 'Future value',
        ]);

        foreach (['category', 'warehouseLocation', 'assignedTo', 'disposition', 'workflowStage', 'company', 'createdBy'] as $relation) {
            $product->setRelation($relation, null);
        }

        $html = view(
            'pages.products.warehouse.products.partials.complete_product_record',
            compact('product')
        )->render();

        $this->assertStringContainsString('Complete Product Record', $html);
        $this->assertStringContainsString('PROD-0042', $html);
        $this->assertStringContainsString('Complete details', $html);
        $this->assertStringContainsString('Warehouse remarks', $html);
        $this->assertStringContainsString('Damaged screen', $html);
        $this->assertStringContainsString('Future Product Field', $html);
        $this->assertStringContainsString('Future value', $html);
    }
}
