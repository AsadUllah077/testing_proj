<?php

namespace App\Exports;

use App\Models\Disposition;
use App\Models\ProductCategory;
use App\Models\WarehouseLocation;
use App\Models\WorkflowStage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductBulkUpdateTemplateExport implements WithMultipleSheets
{
    public function __construct(private readonly Collection $products) {}

    public function sheets(): array
    {
        return [
            'Products' => new ProductBulkUpdateProductsSheet($this->products),
            'Instructions & References' => new ProductBulkUpdateReferencesSheet,
        ];
    }
}

class ProductBulkUpdateProductsSheet extends StringValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles
{
    public const HEADINGS = [
        'Unique ID',
        'Title',
        'DPN SN Number',
        'Product Details',
        'Category',
        'Warehouse Location',
        'Disposition',
        'Workflow Stage',
        'Status',
        'Specifications JSON',
    ];

    public function __construct(private readonly Collection $products) {}

    public function array(): array
    {
        return $this->products
            ->map(function ($product): array {
                $row = array_merge([$product->unique_id], array_fill(0, count(self::HEADINGS) - 1, null));
                $row[array_search('Specifications JSON', self::HEADINGS, true)] = $this->specificationsJson($product->specifications);

                return $row;
            })
            ->values()
            ->all();
    }

    private function specificationsJson(mixed $specifications): ?string
    {
        $specifications = is_array($specifications) ? array_values($specifications) : [];
        $existingKeys = collect($specifications)
            ->pluck('key')
            ->filter(fn ($key) => is_string($key))
            ->map(fn (string $key) => mb_strtolower(trim($key)))
            ->all();

        foreach (['RAM', 'SSD'] as $key) {
            if (! in_array(mb_strtolower($key), $existingKeys, true)) {
                $specifications[] = ['key' => $key, 'value' => ''];
            }
        }

        return json_encode(
            $specifications,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0078C8']],
            ],
            'A' => [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $this->products->count() + 1);
                $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADINGS));
                $statusColumn = Coordinate::stringFromColumnIndex(
                    array_search('Status', self::HEADINGS, true) + 1
                );

                $sheet->freezePane('B2');
                $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
                $sheet->getStyle('A2:A'.$lastRow)->getNumberFormat()->setFormatCode('@');
                $sheet->getComment('A1')->getText()->createTextRun('Required identifier. Do not change this column.');

                for ($row = 2; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("{$statusColumn}{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Active,Inactive,Hold"');
                }
            },
        ];
    }
}

class ProductBulkUpdateReferencesSheet implements FromArray, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        $rows = [
            ['BULK PRODUCT UPDATE INSTRUCTIONS'],
            ['1. Edit only the Products sheet. Do not change Unique ID values or column headings.'],
            ['2. Blank cells keep the product\'s current value. Only populated cells are updated.'],
            ['3. Enter __CLEAR__ to clear an optional text or relationship field.'],
            ['4. Category, location, disposition, and workflow accept an ID or an exact full name listed below. Name matching ignores uppercase/lowercase and surrounding spaces.'],
            ['5. Specifications JSON must be valid JSON. RAM and SSD entries are included in every exported row; enter their values before importing.'],
            [],
            ['REFERENCE TYPE', 'ID', 'NAME / CODE'],
        ];

        $groups = [
            'Category' => ProductCategory::query()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => [$item->id, $item->name]),
            'Warehouse Location' => WarehouseLocation::query()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => [$item->id, $item->name]),
            'Disposition' => Disposition::query()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => [$item->id, $item->name]),
            'Workflow Stage' => WorkflowStage::query()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => [$item->id, $item->name]),
        ];

        foreach ($groups as $type => $items) {
            foreach ($items as $item) {
                $rows[] = [$type, $item[0], $item[1]];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0078C8']]],
            8 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0078C8']],
            ],
        ];
    }
}
