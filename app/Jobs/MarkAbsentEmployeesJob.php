<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRecord;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkAbsentEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    protected string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date ?? Carbon::today()->toDateString();
    }

    public function handle(): void
    {
        $date = Carbon::parse($this->date);

        Log::info("MarkAbsentEmployeesJob: processing absences for {$date->toDateString()}");

        $totalAbsent = 0;

        foreach (Company::all() as $company) {
            $annualLeaveType = LeaveType::where('company_id', $company->id)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%annual%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%vacation%']);
                })
                ->first();

            if (! $annualLeaveType) {
                Log::warning("MarkAbsentEmployeesJob: No annual leave type for company {$company->id} ({$company->name})");
            }

            $employees = Employee::where('company_id', $company->id)->get();

            foreach ($employees as $employee) {
                if (! $this->isWorkingDay($date, $employee)) {
                    continue;
                }

                // Skip if an attendance record already exists for this date
                $alreadyMarked = EmployeeAttendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date->toDateString())
                    ->exists();

                if ($alreadyMarked) {
                    continue;
                }

                DB::beginTransaction();
                try {
                    // Insert absent attendance record (no check-in / check-out)
                    EmployeeAttendance::create([
                        'employee_id'      => $employee->id,
                        'company_id'       => $company->id,
                        'attendance_date'  => $date->toDateString(),
                        'check_in'         => null,
                        'check_out'        => null,
                        'total_work_hours' => null,
                    ]);

                    if ($annualLeaveType) {
                        // Skip if a leave record already covers this date
                        $leaveAlreadyExists = LeaveRecord::where('employee_id', $employee->id)
                            ->where('start_date', '<=', $date->toDateString())
                            ->where('end_date', '>=', $date->toDateString())
                            ->exists();

                        if (! $leaveAlreadyExists) {
                            $year = $date->year;

                            $balance = LeaveBalance::where('employee_id', $employee->id)
                                ->where('leave_type_id', $annualLeaveType->id)
                                ->where('year', $year)
                                ->first();

                            if (! $balance) {
                                $balance = LeaveBalance::create([
                                    'employee_id'      => $employee->id,
                                    'company_id'       => $company->id,
                                    'leave_type_id'    => $annualLeaveType->id,
                                    'total_leaves'     => $annualLeaveType->count ?? 20,
                                    'used_leaves'      => 0,
                                    'remaining_leaves' => $annualLeaveType->count ?? 20,
                                    'year'             => $year,
                                ]);
                            }

                            LeaveRecord::create([
                                'employee_id'       => $employee->id,
                                'company_id'        => $company->id,
                                'leave_type_id'     => $annualLeaveType->id,
                                'start_date'        => $date->toDateString(),
                                'end_date'          => $date->toDateString(),
                                'status'            => 'pending',
                                'adjustment_reason' => 'Auto-generated: employee did not check in.',
                                'created_by'        => null,
                            ]);

                            $balance->incrementUsedLeaves(1);
                        }
                    }

                    DB::commit();
                    $totalAbsent++;
                    Log::info("MarkAbsentEmployeesJob: absent — employee {$employee->id} ({$employee->full_name})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("MarkAbsentEmployeesJob: failed for employee {$employee->id}: {$e->getMessage()}");
                }
            }
        }

        Log::info("MarkAbsentEmployeesJob: done. Absent records created: {$totalAbsent}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("MarkAbsentEmployeesJob failed: {$exception->getMessage()}");
    }

    /**
     * Determine whether the given date is a scheduled working day for the employee.
     */
    private function isWorkingDay(Carbon $date, Employee $employee): bool
    {
        if (! $employee->working_day_from || ! $employee->working_day_to) {
            return ! $date->isWeekend();
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $fromIndex = array_search($employee->working_day_from, $days);
        $toIndex   = array_search($employee->working_day_to, $days);

        if ($fromIndex === false || $toIndex === false) {
            return ! $date->isWeekend();
        }

        // Carbon format('N') returns 1 (Mon) – 7 (Sun), convert to 0-based index
        $current = (int) $date->format('N') - 1;

        if ($fromIndex <= $toIndex) {
            return $current >= $fromIndex && $current <= $toIndex;
        }

        // Handles wrap-around work weeks (e.g. Saturday → Tuesday)
        return $current >= $fromIndex || $current <= $toIndex;
    }
}
