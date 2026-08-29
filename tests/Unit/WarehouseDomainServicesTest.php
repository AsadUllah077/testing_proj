<?php

namespace Tests\Unit;

use App\Models\GatePass;
use App\Models\Part;
use App\Models\Product;
use App\Models\ProductAssignment;
use App\Services\Warehouse\BarcodeBatchService;
use App\Services\Warehouse\ExceptionService;
use App\Services\Warehouse\GatePassService;
use App\Services\Warehouse\InspectionService;
use App\Services\Warehouse\InventoryMovementService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class WarehouseDomainServicesTest extends TestCase
{
    public function test_barcode_batch_service_preserves_sequence_range_rules(): void
    {
        $service = new BarcodeBatchService;

        $range = $service->validateSequenceRange('A001', 'A003');

        $this->assertSame(['prefix' => 'A', 'number' => 1], $range['start']);
        $this->assertSame(['prefix' => 'A', 'number' => 3], $range['end']);
    }

    public function test_barcode_batch_service_rejects_mismatched_prefixes(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Start and end sequences must have the same prefix');

        (new BarcodeBatchService)->validateSequenceRange('A001', 'B003');
    }

    public function test_exception_service_preserves_existing_product_exception_detection(): void
    {
        $product = new Product([
            'unique_id' => 'TEST-001',
            'title' => 'Test Product',
            'manual_status' => 1,
            'count' => 0,
            'sold_status' => 1,
            'scrap_status' => 1,
        ]);
        $product->setAttribute('raw_sold_units', 2);
        $product->setAttribute('reporting_status', 'not_ready');
        $product->setAttribute('age_days', 10);

        $messages = (new ExceptionService)
            ->detectForProducts(new Collection([$product]))
            ->pluck('message')
            ->all();

        $this->assertContains('Missing category', $messages);
        $this->assertContains('Missing warehouse location', $messages);
        $this->assertContains('Manual product count is zero', $messages);
        $this->assertContains('Product is marked both sold and scrap', $messages);
        $this->assertContains('Sold quantity is greater than product count', $messages);
        $this->assertContains('Operational product has no technician', $messages);
    }

    public function test_exception_lifecycle_methods_keep_the_exception_payload(): void
    {
        $service = new ExceptionService;
        $exception = ['product_id' => 10, 'message' => 'Missing location'];

        $assigned = $service->assign($exception, 20);
        $acknowledged = $service->acknowledge($assigned, 21);
        $escalated = $service->escalate($acknowledged, 22, 'SLA exceeded');
        $resolved = $service->resolve($escalated, 23, 'Location assigned');

        $this->assertSame(10, $resolved['product_id']);
        $this->assertSame(22, $resolved['assignee_id']);
        $this->assertSame('resolved', $resolved['status']);
        $this->assertSame('Location assigned', $resolved['resolution_notes']);
    }

    public function test_gate_pass_service_rejects_invalid_qc_transition_before_writing(): void
    {
        $gatePass = new GatePass(['status' => 'SECURITY_APPROVAL_PENDING']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('QC approval is not available for this gate pass.');

        (new GatePassService)->approveQc($gatePass, null, 1);
    }

    public function test_inspection_service_rejects_starting_completed_task_before_writing(): void
    {
        $assignment = new ProductAssignment(['completed_at' => now()]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot start a completed task');

        (new InspectionService)->start($assignment, 1);
    }

    public function test_inventory_service_rejects_insufficient_part_transfer_before_writing(): void
    {
        $part = new Part(['quantity' => 2, 'location' => 'A']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient stock for transfer');

        (new InventoryMovementService)->transferPart($part, 'B', 3);
    }
}
