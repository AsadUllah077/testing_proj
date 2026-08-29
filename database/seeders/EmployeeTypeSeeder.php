<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $batchSize = 5; // Handle 5,000 records at a time
        $totalRecords = 10;
        $now = now()->toDateTimeString(); // Create the timestamp string once

        for ($i = 0; $i < $totalRecords; $i += $batchSize) {
            $data = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $currentId = $i + $j;
                if ($currentId >= $totalRecords) {
                    break;
                }

                $data[] = [
                    'employee_type' => 'Employee Type '.$currentId,
                    'company_id' => 79,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert this batch and clear the array from memory
            DB::table('employee_types')->insert($data);

            // Optional: Output progress to the console
            $this->command->info('Inserted '.($i + $batchSize).' records...');
        }
    }
}
