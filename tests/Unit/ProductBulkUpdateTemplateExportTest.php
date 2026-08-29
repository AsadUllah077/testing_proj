<?php

namespace Tests\Unit;

use App\Exports\ProductBulkUpdateTemplateExport;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductBulkUpdateTemplateExportTest extends TestCase
{
    #[Test]
    public function it_creates_partial_update_rows_with_current_specifications_as_json(): void
    {
        $products = new Collection([
            new Product([
                'unique_id' => 'PROD-001',
                'specifications' => [
                    ['key' => 'RAM', 'value' => '16 GB'],
                    ['key' => 'Model', 'value' => 'EliteBook رینج'],
                ],
            ]),
            new Product(['unique_id' => '00001234']),
        ]);

        $sheets = (new ProductBulkUpdateTemplateExport($products))->sheets();
        $productSheet = $sheets['Products'];
        $rows = $productSheet->array();

        $this->assertSame('Unique ID', $productSheet->headings()[0]);
        $this->assertContains('Specifications JSON', $productSheet->headings());
        $this->assertSame('PROD-001', $rows[0][0]);
        $this->assertSame('00001234', $rows[1][0]);
        $specificationsIndex = array_search('Specifications JSON', $productSheet->headings(), true);
        $this->assertSame(
            '[{"key":"RAM","value":"16 GB"},{"key":"Model","value":"EliteBook رینج"},{"key":"SSD","value":""}]',
            $rows[0][$specificationsIndex]
        );
        $this->assertSame(
            '[{"key":"RAM","value":""},{"key":"SSD","value":""}]',
            $rows[1][$specificationsIndex]
        );
        $this->assertSame(
            array_fill(0, $specificationsIndex - 1, null),
            array_slice($rows[0], 1, $specificationsIndex - 1)
        );
    }

    #[Test]
    public function its_headings_normalize_to_the_imported_field_names(): void
    {
        $sheet = (new ProductBulkUpdateTemplateExport(new Collection))->sheets()['Products'];

        $this->assertSame([
            'unique_id',
            'title',
            'dpn_sn_number',
            'product_details',
            'category',
            'warehouse_location',
            'disposition',
            'workflow_stage',
            'status',
            'specifications_json',
        ], HeadingRowFormatter::format($sheet->headings()));
    }
}
