<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $companyIds = Company::pluck('id')->toArray();

        // if (empty($companyIds)) {
        //     $this->command->warn('⚠️ No companies found. Please seed the companies table first.');

        //     return;
        // }

        $departments = [];
        $departmentNames = [
            'Human Resources', 'Information Technology', 'Finance', 'Marketing', 'Sales',
            'Operations', 'Customer Service', 'Research & Development', 'Quality Assurance',
            'Legal', 'Administration', 'Procurement', 'Logistics', 'Engineering',
            'Product Development', 'Business Development', 'Training', 'Compliance',
            'Risk Management', 'Internal Audit', 'Public Relations', 'Facilities',
            'Security', 'Health & Safety', 'Environmental', 'Data Analytics',
            'Digital Transformation', 'Innovation', 'Strategic Planning', 'Project Management'
        ];

        for ($i = 0; $i < 200; $i++) {
            $departmentName = $departmentNames[$i % count($departmentNames)];
            
            if ($i >= count($departmentNames)) {
                $departmentName .= ' ' . ($i - count($departmentNames) + 1);
            }

            $departments[] = [
                'department' => $departmentName,
                'company_id' => 79,
                'head' => 'Department Head ' . ($i + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        \DB::table('departments')->insert($departments);

        $this->command->info('🚀 200 departments seeded successfully!');
    }
}
