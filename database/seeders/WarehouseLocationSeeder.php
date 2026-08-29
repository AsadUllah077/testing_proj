<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;

class WarehouseLocationSeeder extends Seeder
{
    /**
     * Seed two fixed scan locations into every existing company:
     *   - Photography  → used at Scan Station A (Yellow)
     *   - Warehouse#2  → used at Scan Station B (Green)
     */
    public function run(): void
    {
        $locations = [
            'Photography',
            'Warehouse#2',
        ];

        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found. Please seed companies first.');
            return;
        }

        $inserted = 0;
        $skipped  = 0;

        foreach ($companies as $company) {
            foreach ($locations as $name) {
                // Use updateOrCreate to avoid duplicate-key errors on re-runs
                $result = WarehouseLocation::withTrashed()
                    ->where('name', $name)
                    ->where('company_id', $company->id)
                    ->first();

                if ($result) {
                    // Restore if soft-deleted
                    if ($result->trashed()) {
                        $result->restore();
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                } else {
                    WarehouseLocation::create([
                        'name'       => $name,
                        'company_id' => $company->id,
                    ]);
                    $inserted++;
                }
            }
        }

        $this->command->info("✅ Warehouse locations seeded — {$inserted} inserted/restored, {$skipped} already existed.");
        $this->command->line('   📷  Photography  → Scan Station A (Yellow)');
        $this->command->line('   🏭  Warehouse#2  → Scan Station B (Green)');
    }
}
