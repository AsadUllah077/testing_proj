<?php

namespace Tests\Unit;

use App\Exports\WarehouseProductExport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WarehouseProductExportTest extends TestCase
{
    #[Test]
    public function it_includes_the_ram_and_ssd_specification_heading(): void
    {
        $headings = (new WarehouseProductExport)->headings();

        $this->assertContains('Specifications (RAM/SSD)', $headings);
        $this->assertSame('Specifications (RAM/SSD)', $headings[array_search('Created At', $headings, true) - 1]);
    }

    #[Test]
    public function it_formats_product_specifications_for_exports(): void
    {
        $specifications = [
            ['key' => 'RAM', 'value' => '8 GB'],
            ['key' => 'SSD', 'value' => '512 GB'],
            ['key' => 'Touchscreen', 'value' => true],
        ];

        $this->assertSame(
            'RAM: 8 GB | SSD: 512 GB',
            WarehouseProductExport::formatSpecifications($specifications)
        );
    }

    #[Test]
    public function it_formats_associative_and_empty_specifications_safely(): void
    {
        $this->assertSame(
            'RAM: 16 GB',
            WarehouseProductExport::formatSpecifications([
                'RAM' => '16 GB',
                'Ports' => ['usb' => 2],
            ])
        );
        $this->assertSame('', WarehouseProductExport::formatSpecifications(null));
    }

    #[Test]
    public function it_filters_export_columns_through_the_allowlist(): void
    {
        $export = new WarehouseProductExport([
            'export_columns' => 'unique_id,specifications,not_a_real_column',
        ]);

        $this->assertSame(['unique_id', 'specifications'], $export->columns());
        $this->assertSame(['Unique ID', 'Specifications (RAM/SSD)'], $export->headings());
    }

    #[Test]
    public function it_recognizes_ram_and_ssd_in_legacy_names_and_values(): void
    {
        $this->assertSame(
            'Memory: 32 GB RAM | Storage: 1 TB SSD',
            WarehouseProductExport::formatSpecifications([
                ['name' => 'Memory', 'value' => '32 GB RAM'],
                ['key' => 'Storage', 'value' => '1 TB SSD'],
                ['key' => 'Processor', 'value' => 'Core i7'],
            ])
        );
    }
}
