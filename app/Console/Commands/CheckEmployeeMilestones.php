<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckEmployeeMilestones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-employee-milestones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check employees for tenure milestones (3 months, 6 months, 1 year) and create notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employees = \App\Models\Employee::where('status', 'active')
            ->whereNotNull('date_of_joining')
            ->get();

        foreach ($employees as $employee) {
            $joiningDate = \Carbon\Carbon::parse($employee->date_of_joining);
            $months = $joiningDate->diffInMonths(now());

            $milestoneType = null;
            if ($months >= 12) {
                $milestoneType = '1_year';
            } elseif ($months >= 6) {
                $milestoneType = '6_months';
            } elseif ($months >= 3) {
                $milestoneType = '3_months';
            }

            if ($milestoneType) {
                $exists = \App\Models\EmployeeTenureMilestone::where('employee_id', $employee->id)
                    ->where('milestone', $milestoneType)
                    ->exists();

                if (!$exists) {
                    \App\Models\EmployeeTenureMilestone::create([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'milestone' => $milestoneType,
                        'milestone_date' => now(),
                        'is_read' => false,
                    ]);

                    $this->info("Created {$milestoneType} milestone for employee: {$employee->full_name}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
