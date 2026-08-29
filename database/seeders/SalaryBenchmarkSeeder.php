<?php

namespace Database\Seeders;

use App\Models\SalaryBenchmark;
use App\Models\Company;
use Illuminate\Database\Seeder;

class SalaryBenchmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // You can specify a company ID or seed for all companies
        $companyId = 46; // Setting default to your active company ID

        $benchmarks = [
            [
                'designation' => 'Motor Vehicle Operator',
                'min_salary' => 45000,
                'median_salary' => 55000,
                'max_salary' => 70000
            ],
            [
                'designation' => 'Choreographer',
                'min_salary' => 60000,
                'median_salary' => 80000,
                'max_salary' => 120000
            ],
            [
                'designation' => 'Zoologists OR Wildlife Biologist',
                'min_salary' => 120000,
                'median_salary' => 150000,
                'max_salary' => 200000
            ],
            [
                'designation' => 'Aircraft Rigging Assembler',
                'min_salary' => 80000,
                'median_salary' => 100000,
                'max_salary' => 140000
            ],
            [
                'designation' => 'Umpire and Referee',
                'min_salary' => 40000,
                'median_salary' => 50000,
                'max_salary' => 65000
            ],
            [
                'designation' => 'Mail Machine Operator',
                'min_salary' => 35000,
                'median_salary' => 42000,
                'max_salary' => 50000
            ],
        ];

        foreach ($benchmarks as $benchmark) {
            SalaryBenchmark::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'designation' => $benchmark['designation']
                ],
                $benchmark
            );
        }

        $this->command->info('Salary Benchmarks seeded successfully for Company ID: ' . $companyId);
    }
}
