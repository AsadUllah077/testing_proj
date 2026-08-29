<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class ProductPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ========== PRODUCT MODULE ==========
            ['name' => 'product.dashboard.view'],

            // ========== WAREHOUSE DASHBOARD ==========
            ['name' => 'warehouse.dashboard.view'],
            ['name' => 'warehouse.dashboard.stats'],
            ['name' => 'warehouse.dashboard.chart-data'],
            ['name' => 'warehouse.activity-logs.view'],

            // ========== BARCODE ==========
            ['name' => 'warehouse.barcode.view'],
            ['name' => 'warehouse.barcode.generate'],
            ['name' => 'warehouse.barcode.delete'],
            ['name' => 'warehouse.barcode.bulk-delete'],
            ['name' => 'warehouse.barcode.print'],
            ['name' => 'warehouse.barcode.print-pdf'],
            ['name' => 'warehouse.barcode.print-view'],
            ['name' => 'warehouse.barcode.scan'],
            ['name' => 'warehouse.barcode.export-pdf'],
            ['name' => 'warehouse.barcode.export-excel'],
            ['name' => 'warehouse.barcode.mark-as-used'],
            ['name' => 'warehouse.barcode.bulk-mark-as-used'],

            // ========== MASTER DATA ==========
            ['name' => 'warehouse.master-data.view'],

            // Category
            ['name' => 'warehouse.master-data.category.view'],
            ['name' => 'warehouse.master-data.category.create'],
            ['name' => 'warehouse.master-data.category.edit'],
            ['name' => 'warehouse.master-data.category.delete'],
            ['name' => 'warehouse.master-data.category.bulk-delete'],

            // Locations
            ['name' => 'warehouse.master-data.locations.view'],
            ['name' => 'warehouse.master-data.locations.create'],
            ['name' => 'warehouse.master-data.locations.edit'],
            ['name' => 'warehouse.master-data.locations.delete'],
            ['name' => 'warehouse.master-data.locations.bulk-delete'],

            // Dispositions
            ['name' => 'warehouse.master-data.dispositions.view'],
            ['name' => 'warehouse.master-data.dispositions.create'],
            ['name' => 'warehouse.master-data.dispositions.edit'],
            ['name' => 'warehouse.master-data.dispositions.delete'],
            ['name' => 'warehouse.master-data.dispositions.bulk-delete'],

            // Workflow Stages
            ['name' => 'warehouse.master-data.workflow-stages.view'],
            ['name' => 'warehouse.master-data.workflow-stages.create'],
            ['name' => 'warehouse.master-data.workflow-stages.edit'],
            ['name' => 'warehouse.master-data.workflow-stages.delete'],
            ['name' => 'warehouse.master-data.workflow-stages.bulk-delete'],

            // Scrap Types
            ['name' => 'warehouse.master-data.scrap-types.view'],
            ['name' => 'warehouse.master-data.scrap-types.create'],
            ['name' => 'warehouse.master-data.scrap-types.edit'],
            ['name' => 'warehouse.master-data.scrap-types.delete'],
            ['name' => 'warehouse.master-data.scrap-types.bulk-delete'],

            // Scrap Categories
            ['name' => 'warehouse.master-data.scrap-category.view'],
            ['name' => 'warehouse.master-data.scrap-category.create'],
            ['name' => 'warehouse.master-data.scrap-category.edit'],
            ['name' => 'warehouse.master-data.scrap-category.delete'],
            ['name' => 'warehouse.master-data.scrap-category.bulk-delete'],

            // ========== PRODUCTS ==========
            ['name' => 'warehouse.products.view'],
            ['name' => 'warehouse.products.create'],
            ['name' => 'warehouse.products.show'],
            ['name' => 'warehouse.products.edit'],
            ['name' => 'warehouse.products.delete'],
            ['name' => 'warehouse.products.history'],
            ['name' => 'warehouse.products.action'],
            ['name' => 'warehouse.products.catalog-search'],
            ['name' => 'warehouse.products.check-unique-id'],
            ['name' => 'warehouse.products.bulk-delete'],
            ['name' => 'warehouse.products.bulk-status-change'],
            ['name' => 'warehouse.products.bulk-add-part'],
            ['name' => 'warehouse.products.bulk-extract-part'],
            ['name' => 'warehouse.products.bulk-move-location'],
            ['name' => 'warehouse.products.bulk-assign-tech'],
            ['name' => 'warehouse.products.extract-part'],
            ['name' => 'warehouse.products.process-extract-part'],
            ['name' => 'warehouse.products.restore'],
            ['name' => 'warehouse.products.qrcode'],
            ['name' => 'warehouse.products.print-qrcode'],
            ['name' => 'warehouse.products.export-pdf'],
            ['name' => 'warehouse.products.export-excel'],

            // ========== PRODUCT ISSUE ==========
            ['name' => 'warehouse.product-issue.view'],
            ['name' => 'warehouse.product-issue.create'],
            ['name' => 'warehouse.product-issue.edit'],
            ['name' => 'warehouse.product-issue.delete'],
            ['name' => 'warehouse.product-issue.return'],
            ['name' => 'warehouse.product-issue.export-pdf'],
            ['name' => 'warehouse.product-issue.export-excel'],

            // ========== PARTS ==========
            ['name' => 'warehouse.parts.view'],
            ['name' => 'warehouse.parts.create'],
            ['name' => 'warehouse.parts.show'],
            ['name' => 'warehouse.parts.edit'],
            ['name' => 'warehouse.parts.delete'],
            ['name' => 'warehouse.parts.add-to-product'],
            ['name' => 'warehouse.parts.transfer'],
            ['name' => 'warehouse.parts.movements'],
            ['name' => 'warehouse.parts.bulk-delete'],
            ['name' => 'warehouse.parts.export-pdf'],
            ['name' => 'warehouse.parts.export-excel'],

            // ========== TECHNICIANS ==========
            ['name' => 'warehouse.technicians.view'],
            ['name' => 'warehouse.technicians.delete'],
            ['name' => 'warehouse.technicians.bulk-assign'],
            ['name' => 'warehouse.technicians.bulk-delete'],
            ['name' => 'warehouse.technicians.bulk-complete'],
            ['name' => 'warehouse.technicians.start-task'],
            ['name' => 'warehouse.technicians.retry-task'],
            ['name' => 'warehouse.technicians.complete-task'],
            ['name' => 'warehouse.technicians.submit-result'],
            ['name' => 'warehouse.technicians.submit-visual'],
            ['name' => 'warehouse.technicians.submit-functional'],
            ['name' => 'warehouse.technicians.submit-general'],
            ['name' => 'warehouse.technicians.view-result'],
            ['name' => 'warehouse.technicians.export-pdf'],
            ['name' => 'warehouse.technicians.export-excel'],

            // ========== GATE PASS ==========
            ['name' => 'warehouse.gate-pass.view'],
            ['name' => 'warehouse.gate-pass.create'],
            ['name' => 'warehouse.gate-pass.show'],
            ['name' => 'warehouse.gate-pass.edit'],
            ['name' => 'warehouse.gate-pass.delete'],
            ['name' => 'warehouse.gate-pass.bulk-delete'],
            ['name' => 'warehouse.gate-pass.search-products'],
            ['name' => 'warehouse.gate-pass.qc-approval'],
            ['name' => 'warehouse.gate-pass.security-approval'],
            ['name' => 'warehouse.gate-pass.export-pdf'],
            ['name' => 'warehouse.gate-pass.export-excel'],

            // ========== SCRAP ==========
            ['name' => 'warehouse.scrap.view'],
            ['name' => 'warehouse.scrap.create'],
            ['name' => 'warehouse.scrap.show'],
            ['name' => 'warehouse.scrap.edit'],
            ['name' => 'warehouse.scrap.delete'],
            ['name' => 'warehouse.scrap.bulk-delete'],
            ['name' => 'warehouse.scrap.export-pdf'],
            ['name' => 'warehouse.scrap.export-excel'],
            ['name' => 'warehouse.scrap.export-csv'],

            // ========== PROGRESS SHEET ==========
            ['name' => 'warehouse.progress-sheet.view'],
            ['name' => 'warehouse.progress-sheet.create'],
            ['name' => 'warehouse.progress-sheet.edit'],
            ['name' => 'warehouse.progress-sheet.delete'],
            ['name' => 'warehouse.progress-sheet.bulk-delete'],
            ['name' => 'warehouse.progress-sheet.export-pdf'],
            ['name' => 'warehouse.progress-sheet.export-csv'],

            // ========== SCAN TRACKING ==========
            ['name' => 'warehouse.scan.view'],
            ['name' => 'warehouse.scan.process'],
            ['name' => 'warehouse.scan.logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
