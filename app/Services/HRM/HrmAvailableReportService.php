<?php

namespace App\Services\HRM;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeTenureMilestone;
use App\Models\HrmJob;
use App\Models\LeaveBalance;
use App\Models\LeaveRecord;
use App\Models\Survey;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HrmAvailableReportService
{
    public function build(array $filters, int $companyId): array
    {
        $employees = $this->employees($filters, $companyId);
        $attendance = $this->attendance($filters, $companyId);
        $leaveRecords = $this->leaveRecords($filters, $companyId);
        $leaveBalances = $this->leaveBalances($filters, $companyId);
        $jobs = $this->jobs($filters, $companyId);
        $surveys = $this->surveys($filters, $companyId);
        $exceptions = $this->exceptions($employees, $attendance, $leaveBalances, $jobs, $surveys);

        return [
            'employees' => $employees,
            'attendance' => $attendance,
            'leaveRecords' => $leaveRecords,
            'leaveBalances' => $leaveBalances,
            'jobs' => $jobs,
            'surveys' => $surveys,
            'exceptions' => $exceptions,
            'summary' => $this->summary(
                $employees,
                $attendance,
                $leaveRecords,
                $leaveBalances,
                $jobs,
                $surveys,
                $exceptions
            ),
            'workforceMovement' => $this->workforceMovement($employees),
            'demographics' => $this->demographics($employees),
            'tenure' => $this->tenure($employees),
            'tenureMilestones' => EmployeeTenureMilestone::with('employee:id,full_name,department_id')
                ->where('company_id', $companyId)
                ->whereDate('milestone_date', '>=', now()->startOfDay())
                ->orderBy('milestone_date')
                ->limit(20)
                ->get(),
        ];
    }

    public function employees(array $filters, int $companyId): Collection
    {
        $query = Employee::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->with(['department:id,department', 'employmentType:id,employee_type'])
            ->select([
                'id', 'employee_id', 'employee_card_id', 'full_name', 'email', 'gender',
                'date_of_birth', 'employment_type_id', 'reporting_to', 'department_id',
                'designation', 'date_of_joining', 'date_of_confirmation', 'work_location',
                'working_day_from', 'working_day_to', 'start_time', 'end_time', 'status',
                'resign_date', 'company_id', 'created_at',
            ]);

        $this->applyEmployeeFilters($query, $filters);

        return $query->orderBy('full_name')->get()->map(function (Employee $employee) {
            $joiningDate = $this->date($employee->date_of_joining);
            $birthDate = $this->date($employee->date_of_birth);

            $employee->setAttribute('tenure_months', $joiningDate ? $joiningDate->diffInMonths(now()) : null);
            $employee->setAttribute('tenure_years', $joiningDate ? round($joiningDate->diffInMonths(now()) / 12, 1) : null);
            $employee->setAttribute('age_years', $birthDate ? $birthDate->age : null);

            return $employee;
        });
    }

    public function attendance(array $filters, int $companyId): Collection
    {
        $query = EmployeeAttendance::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->with(['employee:id,employee_id,full_name,department_id,start_time,end_time,date_of_joining,resign_date'])
            ->whereHas('employee', fn ($employee) => $this->applyEmployeeFilters($employee, $filters))
            ->orderByDesc('attendance_date');

        $from = $filters['date_from'] ?? now()->subDays(29)->toDateString();
        $to = $filters['date_to'] ?? now()->toDateString();
        $query->whereBetween('attendance_date', [$from, $to]);

        $records = $query->get()->map(function (EmployeeAttendance $attendance) {
            $attendance->setAttribute('report_status', $attendance->status);
            $attendance->setAttribute('exception_labels', $this->attendanceExceptionLabels($attendance));

            return $attendance;
        });

        if (! empty($filters['attendance_status'])) {
            $status = $filters['attendance_status'];
            $records = $status === 'exception'
                ? $records->filter(fn ($record) => count($record->exception_labels) > 0)
                : $records->where('report_status', $status);
        }

        return $records->values();
    }

    public function leaveRecords(array $filters, int $companyId): Collection
    {
        $query = LeaveRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->with([
                'employee:id,employee_id,full_name,department_id,working_day_from,working_day_to',
                'employee.department:id,department',
                'leaveType:id,name',
            ])
            ->whereHas('employee', fn ($employee) => $this->applyEmployeeFilters($employee, $filters));

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '1900-01-01';
            $to = $filters['date_to'] ?? '2999-12-31';
            $query->inDateRange($from, $to);
        } else {
            $query->whereYear('start_date', (int) ($filters['year'] ?? now()->year));
        }

        return $query->orderByDesc('start_date')->get()->map(function (LeaveRecord $record) {
            $record->setAttribute('report_days', (float) $record->calculateTotalDays());

            return $record;
        });
    }

    public function leaveBalances(array $filters, int $companyId): Collection
    {
        $query = LeaveBalance::query()
            ->where('company_id', $companyId)
            ->where('year', (int) ($filters['year'] ?? now()->year))
            ->with([
                'employee:id,employee_id,full_name,department_id,status',
                'employee.department:id,department',
                'leaveType:id,name',
            ])
            ->whereHas('employee', fn ($employee) => $this->applyEmployeeFilters($employee, $filters));

        return $query->orderBy('employee_id')->get()->map(function (LeaveBalance $balance) {
            $balance->setAttribute(
                'report_utilization',
                $balance->total_leaves > 0 ? round(((float) $balance->used_leaves / $balance->total_leaves) * 100, 1) : 0
            );

            return $balance;
        });
    }

    public function jobs(array $filters, int $companyId): Collection
    {
        $query = HrmJob::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->with(['department:id,department', 'hiringManager:id,username'])
            ->withCount('candidates');

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['job_status'])) {
            $query->where('status', $filters['job_status']);
        }
        if (! empty($filters['q'])) {
            $search = strtolower(trim($filters['q']));
            $query->where(function ($nested) use ($search) {
                $nested->whereRaw('LOWER(job_title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(job_id) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->orderByDesc('created_at')->get()->map(function (HrmJob $job) {
            $start = $job->posted_date ?: $job->created_at;
            $job->setAttribute('report_candidates_count', (int) ($job->getAttributes()['candidates_count'] ?? 0));
            $job->setAttribute('age_days', $start ? Carbon::parse($start)->startOfDay()->diffInDays(now()->startOfDay()) : null);
            $job->setAttribute('is_overdue', $job->closing_date && $job->closing_date->isPast() && $job->status === 'open');

            return $job;
        });
    }

    public function surveys(array $filters, int $companyId): Collection
    {
        return Survey::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->with(['assignments', 'responses:id,survey_id,employee_id,submitted_at'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Survey $survey) use ($companyId) {
                $eligibleIds = $this->surveyEligibleEmployeeIds($survey, $companyId);
                $responses = $survey->responses->count();
                $audience = $eligibleIds->count();

                $survey->setAttribute('audience_count', $audience);
                $survey->setAttribute('response_count', $responses);
                $survey->setAttribute('response_rate', $audience > 0 ? round(($responses / $audience) * 100, 1) : 0);

                return $survey;
            });
    }

    public function exceptions(
        Collection $employees,
        Collection $attendance,
        Collection $leaveBalances,
        Collection $jobs,
        Collection $surveys
    ): Collection {
        $exceptions = collect();

        foreach ($employees as $employee) {
            $checks = [
                'Missing employee ID' => ! $employee->employee_id,
                'Missing email' => ! $employee->email,
                'Missing department' => ! $employee->department_id,
                'Missing employee type' => ! $employee->employment_type_id,
                'Missing joining date' => ! $employee->date_of_joining,
                'Missing work schedule' => ! $employee->start_time || ! $employee->end_time,
                'Active employee has resignation date' => strtolower((string) $employee->status) === 'active' && $employee->resign_date,
                'Confirmation date is before joining date' => $employee->date_of_confirmation
                    && $employee->date_of_joining
                    && $this->date($employee->date_of_confirmation)?->lt($this->date($employee->date_of_joining)),
            ];

            foreach ($checks as $message => $failed) {
                if ($failed) {
                    $exceptions->push($this->exception('Employee', $employee->id, $employee->employee_id, $employee->full_name, $message));
                }
            }
        }

        foreach ($attendance as $record) {
            foreach ($record->exception_labels as $message) {
                $exceptions->push($this->exception(
                    'Attendance',
                    $record->id,
                    $record->employee?->employee_id,
                    $record->employee?->full_name,
                    $message.' on '.$record->attendance_date?->format('d M Y')
                ));
            }
        }

        foreach ($leaveBalances as $balance) {
            if ((float) $balance->remaining_leaves < 0) {
                $exceptions->push($this->exception('Leave', $balance->id, $balance->employee?->employee_id, $balance->employee?->full_name, 'Negative leave balance'));
            }
            if (abs(((float) $balance->total_leaves - (float) $balance->used_leaves) - (float) $balance->remaining_leaves) > 0.01) {
                $exceptions->push($this->exception('Leave', $balance->id, $balance->employee?->employee_id, $balance->employee?->full_name, 'Leave balance does not reconcile'));
            }
        }

        foreach ($jobs as $job) {
            if (! $job->posted_date && $job->status === 'open') {
                $exceptions->push($this->exception('Recruitment', $job->id, $job->job_id, $job->job_title, 'Open job has no posted date'));
            }
            if ($job->is_overdue) {
                $exceptions->push($this->exception('Recruitment', $job->id, $job->job_id, $job->job_title, 'Open job is past its closing date'));
            }
            if ((int) $job->applicants_count !== (int) $job->report_candidates_count) {
                $exceptions->push($this->exception('Recruitment', $job->id, $job->job_id, $job->job_title, 'Stored applicant count does not match candidates'));
            }
        }

        foreach ($surveys as $survey) {
            if ($survey->response_count > $survey->audience_count && $survey->audience_count > 0) {
                $exceptions->push($this->exception('Survey', $survey->id, 'SURVEY-'.$survey->id, $survey->title, 'Responses exceed resolved audience'));
            }
        }

        return $exceptions->values();
    }

    private function summary(
        Collection $employees,
        Collection $attendance,
        Collection $leaveRecords,
        Collection $leaveBalances,
        Collection $jobs,
        Collection $surveys,
        Collection $exceptions
    ): array {
        $active = $employees->filter(fn ($employee) => strtolower((string) $employee->status) === 'active')->count();
        $approvedLeaveDays = $leaveRecords->where('status', 'approved')->sum('report_days');
        $audience = $surveys->sum('audience_count');
        $responses = $surveys->sum('response_count');

        return [
            'employees' => $employees->count(),
            'active_employees' => $active,
            'attendance_records' => $attendance->count(),
            'late_records' => $attendance->where('report_status', 'late')->count(),
            'attendance_exceptions' => $attendance->filter(fn ($record) => count($record->exception_labels) > 0)->count(),
            'approved_leave_days' => round((float) $approvedLeaveDays, 1),
            'leave_balance_remaining' => round((float) $leaveBalances->sum('remaining_leaves'), 1),
            'open_jobs' => $jobs->where('status', 'open')->count(),
            'survey_response_rate' => $audience > 0 ? round(($responses / $audience) * 100, 1) : 0,
            'exceptions' => $exceptions->count(),
        ];
    }

    private function workforceMovement(Collection $employees): array
    {
        $months = collect(range(11, 0))->map(fn ($ago) => now()->subMonthsNoOverflow($ago)->startOfMonth());

        return [
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->values(),
            'joiners' => $months->map(fn ($month) => $employees->filter(function ($employee) use ($month) {
                $date = $this->date($employee->date_of_joining);

                return $date?->betweenIncluded($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
            })->count())->values(),
            'leavers' => $months->map(fn ($month) => $employees->filter(function ($employee) use ($month) {
                $date = $this->date($employee->resign_date);

                return $date?->betweenIncluded($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
            })->count())->values(),
        ];
    }

    private function demographics(Collection $employees): array
    {
        $gender = $employees->countBy(fn ($employee) => $employee->gender ?: 'Unspecified')->sortDesc();
        $types = $employees->countBy(fn ($employee) => $employee->employmentType?->employee_type ?: 'Unassigned')->sortDesc();
        $departments = $employees->countBy(fn ($employee) => $employee->department?->department ?: 'Unassigned')->sortDesc();

        return compact('gender', 'types', 'departments');
    }

    private function tenure(Collection $employees): array
    {
        $bands = ['Under 6 months' => 0, '6–12 months' => 0, '1–3 years' => 0, '3–5 years' => 0, '5+ years' => 0, 'Unknown' => 0];

        foreach ($employees as $employee) {
            $months = $employee->tenure_months;
            $band = match (true) {
                $months === null => 'Unknown',
                $months < 6 => 'Under 6 months',
                $months < 12 => '6–12 months',
                $months < 36 => '1–3 years',
                $months < 60 => '3–5 years',
                default => '5+ years',
            };
            $bands[$band]++;
        }

        return $bands;
    }

    private function applyEmployeeFilters($query, array $filters)
    {
        if (! empty($filters['q'])) {
            $search = strtolower(trim($filters['q']));
            $query->where(function ($nested) use ($search) {
                $nested->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(employee_id) LIKE ?', ["%{$search}%"]);
            });
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['employment_type_id'])) {
            $query->where('employment_type_id', $filters['employment_type_id']);
        }
        if (! empty($filters['employee_status'])) {
            $query->where('status', $filters['employee_status']);
        }
        if (! empty($filters['work_location'])) {
            $query->where('work_location', $filters['work_location']);
        }

        return $query;
    }

    private function attendanceExceptionLabels(EmployeeAttendance $attendance): array
    {
        $labels = [];
        if (! $attendance->check_in) {
            $labels[] = 'Missing check-in';
        }
        if ($attendance->check_in && ! $attendance->check_out) {
            $labels[] = 'Missing check-out';
        }
        if ($attendance->status === 'late') {
            $labels[] = 'Late arrival';
        }
        if ($attendance->overtime_minutes !== null) {
            $labels[] = 'Overtime over one hour';
        }

        $joining = $this->date($attendance->employee?->date_of_joining);
        $resigned = $this->date($attendance->employee?->resign_date);
        $date = $attendance->attendance_date;
        if ($joining && $date && $date->lt($joining)) {
            $labels[] = 'Attendance before joining date';
        }
        if ($resigned && $date && $date->gt($resigned)) {
            $labels[] = 'Attendance after resignation date';
        }

        return $labels;
    }

    private function surveyEligibleEmployeeIds(Survey $survey, int $companyId): Collection
    {
        $ids = collect();

        foreach ($survey->assignments as $assignment) {
            if ($assignment->assignable_type === 'All') {
                return Employee::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where('status', 'active')
                    ->pluck('id')
                    ->unique()
                    ->values();
            }
            if ($assignment->assignable_type === 'Employee' && $assignment->assignable_id) {
                $ids->push((int) $assignment->assignable_id);
            }
            if ($assignment->assignable_type === 'Department' && $assignment->assignable_id) {
                $ids = $ids->merge(Employee::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where('department_id', $assignment->assignable_id)
                    ->where('status', 'active')
                    ->pluck('id'));
            }
            if ($assignment->assignable_type === 'Team' && $assignment->assignable_id) {
                $ids = $ids->merge(DB::table('team_members')
                    ->join('employees', 'employees.id', '=', 'team_members.employee_id')
                    ->where('team_members.team_id', $assignment->assignable_id)
                    ->where('employees.company_id', $companyId)
                    ->where('employees.status', 'active')
                    ->pluck('employees.id'));
            }
        }

        return $ids->unique()->values();
    }

    private function exception(string $area, int $id, ?string $reference, ?string $name, string $message): array
    {
        return compact('area', 'id', 'reference', 'name', 'message');
    }

    private function date($value): ?Carbon
    {
        return $value ? Carbon::parse($value)->startOfDay() : null;
    }
}
