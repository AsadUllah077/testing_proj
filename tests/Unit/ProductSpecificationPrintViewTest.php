<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductSpecificationPrintViewTest extends TestCase
{
    #[Test]
    public function it_renders_one_print_label_for_each_selected_product(): void
    {
        $products = new Collection([
            new Product([
                'unique_id' => 'PROD-001',
                'title' => 'First Laptop',
                'specifications' => [['key' => 'RAM', 'value' => '16 GB']],
            ]),
            new Product([
                'unique_id' => 'PROD-002',
                'title' => 'Second Laptop',
                'specifications' => [['key' => 'SSD', 'value' => '512 GB']],
            ]),
        ]);

        $html = view('pages.products.warehouse.products.print-specification', compact('products'))->render();

        $this->assertSame(2, substr_count($html, 'aria-label="Product specification label"'));
        $this->assertStringContainsString('PROD-001', $html);
        $this->assertStringContainsString('PROD-002', $html);
        $this->assertStringContainsString('16 GB', $html);
        $this->assertStringContainsString('512 GB', $html);
    }
}
