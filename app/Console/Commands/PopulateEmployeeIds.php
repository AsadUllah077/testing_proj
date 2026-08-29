<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PopulateEmployeeIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:populate-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate employee_id for existing employees based on tenure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting employee ID data migration...');

        DB::transaction(function () {
            // Group employees by company to maintain separate sequences per tenant
            $companyIds = Employee::select('company_id')->distinct()->pluck('company_id');

            foreach ($companyIds as $companyId) {
                $this->info("Processing company ID: " . ($companyId ?? 'NULL'));

                // Order by tenure (oldest joining date first) and then by name globally
                $employees = Employee::where('company_id', $companyId)
                    ->orderByRaw('COALESCE(date_of_joining, created_at) ASC')
                    ->orderBy('full_name', 'asc')
                    ->get();

                $sequence = 1;

                foreach ($employees as $employee) {
                    // Use joining date or fallback to created_at
                    if ($employee->date_of_joining) {
                        $date = Carbon::parse($employee->date_of_joining);
                    } else {
                        $date = $employee->created_at ?? Carbon::now();
                    }

                    $month = $date->format('m');
                    $year = $date->format('Y');

                    $employeeId = sprintf('EMP-%s-%s-%03d', $month, $year, $sequence);
                    $employee->employee_id = $employeeId;
                    $employee->save();

                    $this->line("Assigned {$employeeId} to {$employee->full_name}");
                    $sequence++;
                }
            }
        });

        $this->info('✅ Successfully populated all employee IDs!');
    }
}
