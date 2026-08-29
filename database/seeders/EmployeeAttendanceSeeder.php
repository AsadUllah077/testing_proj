<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Clear existing data to avoid the Unique Constraint error
        DB::table('employee_attendances')->truncate();

        $totalGoal = 100;
        $batchSize = 50;
        $employeeIds = DB::table('employees')->pluck('id')->toArray();

        if (empty($employeeIds)) {
            $this->command->error('No employees found!');

            return;
        }

        $now = now()->toDateTimeString();
        $insertedCount = 0;
        $daysBack = 0;

        while ($insertedCount < $totalGoal) {
            $date = Carbon::now()->subDays($daysBack)->toDateString();
            $batch = [];

            foreach ($employeeIds as $empId) {
                $batch[] = [
                    'employee_id' => $empId,
                    'attendance_date' => $date,
                    'check_in' => '09:00:00',
                    'check_out' => '17:00:00',
                    'total_work_hours' => 8.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('employee_attendances')->insert($batch);
                    $insertedCount += count($batch);
                    $batch = [];
                    if ($insertedCount >= $totalGoal) {
                        break 2;
                    }
                }
            }

            if (! empty($batch)) {
                DB::table('employee_attendances')->insert($batch);
                $insertedCount += count($batch);
            }

            $daysBack++; // Move to the previous day for the next loop
            $this->command->info("Inserted $insertedCount records...");
        }
    }
}
