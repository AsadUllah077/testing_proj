<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CurrencySeeder::class,
            // // CountrySeeder::class,
            // StateSeeder::class,
            // CompanySeeder::class,
            // UserSeeder::class,
            // ChartOfAccountSeeder::class,
            // // InvoiceTemplateSeeder::class,
            // RoleSeeder::class,
            // // PermissionSeeder::class,
            // ProductPermissionSeeder::class,
            // PayrollTestDataSeeder::class,
            // // DepartmentSeeder::class,
            // EmployeeTypeSeeder::class,
        ]);
    }
}
