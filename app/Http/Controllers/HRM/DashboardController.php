<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeType;
use App\Models\HrmCandidate;
use App\Models\HrmJob;
use App\Models\LeaveRecord;
use App\Models\Resignation;
use App\Models\Task;
use App\Models\EmployeeTenureMilestone;
use App\Models\Warning;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display HRM Dashboard
     */
    public function index(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|required_with:to_date|date_format:Y-m-d',
            'to_date' => 'nullable|required_with:from_date|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        $companyId = Auth::user()->company_id;

        // Empty dates mean all-time; otherwise the range filters every widget.
        $hasDateFilter = $request->filled('from_date') && $request->filled('to_date');
        if ($hasDateFilter) {
            $filterStartDate = Carbon::createFromFormat('Y-m-d', (string) $request->query('from_date'))->startOfDay();
            $filterEndDate = Carbon::createFromFormat('Y-m-d', (string) $request->query('to_date'))->endOfDay();
            $startDate = $filterStartDate;
            $endDate = $filterEndDate;
        } else {
            $filterStartDate = null;
            $filterEndDate = null;
            $earliestDate = collect([
                Employee::where('company_id', $companyId)->min('date_of_joining'),
                EmployeeAttendance::where('company_id', $companyId)->min('attendance_date'),
                Warning::where('company_id', $companyId)->min('warning_date'),
                Complaint::where('company_id', $companyId)->min('date'),
                Resignation::where('company_id', $companyId)->min('resign_date'),
            ])->filter()
                ->map(fn ($date) => Carbon::parse($date)->startOfDay())
                ->sortBy(fn (Carbon $date) => $date->timestamp)
                ->first();
            $startDate = $earliestDate ?? now()->startOfMonth();
            $endDate = now()->endOfDay();
        }

        $rangeDays = $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1;
        $period = match (true) {
            $rangeDays === 1 => 'daily',
            $rangeDays <= 14 => 'weekly',
            $rangeDays <= 62 => 'monthly',
            $rangeDays <= 183 => 'quarterly',
            default => 'yearly',
        };

        // Employees whose joining/record date falls inside the selected range.
        $employeePeriod = $this->employeePeriodQuery($companyId, $filterStartDate, $filterEndDate);
        $activeEmployees = (clone $employeePeriod)->where('status', 'active')->count();
        $inactiveEmployees = (clone $employeePeriod)->where('status', 'inactive')->count();
        $resignedEmployees = (clone $employeePeriod)->where('status', 'resigned')->count();
        $terminatedEmployees = (clone $employeePeriod)->where('status', 'terminated')->count();

        $totalEmployees = $activeEmployees + $inactiveEmployees + $resignedEmployees + $terminatedEmployees;

        // Current period attendance
        $todayCount = EmployeeAttendance::where('company_id', $companyId)
            ->when($hasDateFilter, fn ($query) => $query->whereBetween('attendance_date', [$filterStartDate, $filterEndDate]))
            ->count();

        // Previous period count for comparison
        $prevCount = 0;
        if ($hasDateFilter) {
            $prevDateRange = $this->getPreviousDateRange($filterStartDate, $filterEndDate);
            $prevCount = EmployeeAttendance::where('company_id', $companyId)
                ->whereBetween('attendance_date', [$prevDateRange['start'], $prevDateRange['end']])
                ->count();
        }

        // Rates
        $todayRate = ($totalEmployees > 0) ? ($todayCount / $totalEmployees) * 100 : 0;
        $prevRate = ($totalEmployees > 0) ? ($prevCount / $totalEmployees) * 100 : 0;

        // Change percentage
        if (! $hasDateFilter) {
            $changeToday = 0;
        } elseif ($prevCount == 0) {
            $changeToday = $todayCount > 0 ? 100 : 0;
        } else {
            $changeToday = (($todayCount - $prevCount) / $prevCount) * 100;
        }

        $todayAttendances = EmployeeAttendance::with('employee.department')
            ->where('company_id', $companyId)
            ->when($hasDateFilter, fn ($query) => $query->whereBetween('attendance_date', [$filterStartDate, $filterEndDate]))
            ->orderBy('attendance_date', 'desc')
            ->take(5)
            ->get();

        $activeEmployeePeriod = fn ($query) => $this->applyEmployeePeriod($query, $filterStartDate, $filterEndDate)
            ->where('status', 'active');

        $departments = Department::where('company_id', $companyId)
            ->whereHas('employees', $activeEmployeePeriod)
            ->withCount(['employees' => $activeEmployeePeriod])
            ->get();

        // Define a set of colors to use for the chart and legend
        $colors = ['#6366f1', '#60a5fa', '#fbbf24', '#2dd4bf', '#f87171', '#a78bfa'];

        $onLeaveCount = LeaveRecord::where('company_id', $companyId)
            ->when($hasDateFilter, function ($query) use ($filterStartDate, $filterEndDate) {
                $query->where(function ($q) use ($filterStartDate, $filterEndDate) {
                    $q->whereBetween('start_date', [$filterStartDate, $filterEndDate])
                        ->orWhereBetween('end_date', [$filterStartDate, $filterEndDate])
                        ->orWhere(function ($q2) use ($filterStartDate, $filterEndDate) {
                            $q2->where('start_date', '<=', $filterStartDate)
                                ->where('end_date', '>=', $filterEndDate);
                        });
                    });
            })
            ->count();

        $openPositionsCount = HrmJob::where('company_id', $companyId)
            ->where('status', 'open')
            ->when($hasDateFilter, fn ($query) => $query->whereBetween('created_at', [$filterStartDate, $filterEndDate]))
            ->count();

        // Attendance trend for the selected dashboard period.
        $groupTrendByMonth = in_array($period, ['quarterly', 'yearly'], true)
            || $startDate->diffInDays($endDate) > 62;
        $trendBuckets = $this->getTrendBuckets($period, $startDate, $endDate, $groupTrendByMonth);
        $trendKeyFormat = $groupTrendByMonth ? 'Y-m' : 'Y-m-d';
        $attendanceTrendRecords = EmployeeAttendance::where('company_id', $companyId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();
        $attendanceRecordsByBucket = $attendanceTrendRecords
            ->groupBy(fn ($attendance) => $attendance->attendance_date->format($trendKeyFormat));

        $attendanceTrend = [
            'labels' => $trendBuckets->pluck('label')->values(),
            'checkIns' => $trendBuckets->map(function ($bucket) use ($attendanceRecordsByBucket) {
                return $attendanceRecordsByBucket->get($bucket['key'], collect())
                    ->whereNotNull('check_in')
                    ->count();
            })->values(),
            'lateArrivals' => $trendBuckets->map(function ($bucket) use ($attendanceRecordsByBucket) {
                return $attendanceRecordsByBucket->get($bucket['key'], collect())
                    ->filter(fn ($attendance) => $attendance->status === 'late')
                    ->count();
            })->values(),
        ];

        // Active workforce composition by configured employment type.
        $employmentTypeNames = EmployeeType::where('company_id', $companyId)
            ->pluck('employee_type', 'id');
        $employmentTypeCounts = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->tap(fn ($query) => $this->applyEmployeePeriod($query, $filterStartDate, $filterEndDate))
            ->get(['employment_type_id'])
            ->countBy(fn ($employee) => $employee->employment_type_id ?: 'unassigned')
            ->sortDesc();
        $employmentTypePalette = ['#2563eb', '#14b8a6', '#8b5cf6', '#f59e0b', '#ef4444', '#64748b'];

        $employmentTypeChart = [
            'labels' => $employmentTypeCounts->keys()
                ->map(fn ($typeId) => $typeId === 'unassigned'
                    ? 'Unassigned'
                    : ($employmentTypeNames[$typeId] ?? 'Unknown'))
                ->values(),
            'counts' => $employmentTypeCounts->values(),
            'colors' => $employmentTypeCounts->values()
                ->map(fn ($count, $index) => $employmentTypePalette[$index % count($employmentTypePalette)]),
        ];

        $warningsByMonth = Warning::where('company_id', $companyId)
            ->whereBetween('warning_date', [$startDate, $endDate])
            ->get(['warning_date'])
            ->countBy(fn ($warning) => Carbon::parse($warning->warning_date)->format($trendKeyFormat));
        $complaintsByMonth = Complaint::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get(['date'])
            ->countBy(fn ($complaint) => Carbon::parse($complaint->date)->format($trendKeyFormat));
        $resignationsByMonth = Resignation::where('company_id', $companyId)
            ->whereBetween('resign_date', [$startDate, $endDate])
            ->get(['resign_date'])
            ->countBy(fn ($resignation) => Carbon::parse($resignation->resign_date)->format($trendKeyFormat));

        $hrCasesChart = [
            'labels' => $trendBuckets->pluck('label')->values(),
            'warnings' => $trendBuckets->map(fn ($bucket) => $warningsByMonth->get($bucket['key'], 0))->values(),
            'complaints' => $trendBuckets->map(fn ($bucket) => $complaintsByMonth->get($bucket['key'], 0))->values(),
            'resignations' => $trendBuckets->map(fn ($bucket) => $resignationsByMonth->get($bucket['key'], 0))->values(),
        ];

        $dashboardData = [
            'departmentIds' => $departments->pluck('id'),
            'labels' => $departments->pluck('department'),
            'counts' => $departments->pluck('employees_count'),
            'total' => $departments->sum('employees_count'),
            'colors' => $colors,
            'attendance_rate_today' => $todayRate,
            'todayCount' => $todayCount,
            'attendance_change_percentage' => $changeToday,
            'onLeaveCount' => $onLeaveCount,
            'openPositionsCount' => $openPositionsCount,
            'period' => $period,
            'hasDateFilter' => $hasDateFilter,
            'periodLabel' => $hasDateFilter
                ? ($filterStartDate->isSameDay($filterEndDate)
                    ? $filterStartDate->format('d M Y')
                    : $filterStartDate->format('d M Y').' - '.$filterEndDate->format('d M Y'))
                : 'All time',
            'startDate' => $filterStartDate?->format('Y-m-d') ?? '',
            'endDate' => $filterEndDate?->format('Y-m-d') ?? '',
        ];

        // 11. Tenure Milestones
        $tenureMilestonesDailyCount = EmployeeTenureMilestone::where('company_id', $companyId)
            ->where('is_read', false)
            ->when($hasDateFilter, fn ($query) => $query->whereBetween('milestone_date', [$filterStartDate, $filterEndDate]))
            ->count();

        return view('pages.hrm.dashboard', compact(
            'dashboardData', 'todayAttendances', 'period', 'attendanceTrend', 'employmentTypeChart', 'hrCasesChart',
            'totalEmployees', 'activeEmployees', 'inactiveEmployees', 'resignedEmployees', 'terminatedEmployees',
            'tenureMilestonesDailyCount'
        ));
    }

    private function getPreviousDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $periodDays = $startDate->copy()->startOfDay()
            ->diffInDays($endDate->copy()->startOfDay()) + 1;
        $previousEnd = $startDate->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        return ['start' => $previousStart, 'end' => $previousEnd];
    }

    private function employeePeriodQuery(int $companyId, ?Carbon $startDate, ?Carbon $endDate)
    {
        return $this->applyEmployeePeriod(
            Employee::where('company_id', $companyId),
            $startDate,
            $endDate
        );
    }

    private function applyEmployeePeriod($query, ?Carbon $startDate, ?Carbon $endDate)
    {
        return $query->where('full_name', '!=', 'Safe Solutions')
            ->when($startDate && $endDate, function ($employeeQuery) use ($startDate, $endDate) {
                $employeeQuery->where(function ($periodQuery) use ($startDate, $endDate) {
                $periodQuery->whereBetween('date_of_joining', [$startDate, $endDate])
                    ->orWhere(function ($fallbackQuery) use ($startDate, $endDate) {
                        $fallbackQuery->whereNull('date_of_joining')
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
                });
            });
    }

    /**
     * Build chart buckets that exactly match the selected dashboard period.
     */
    private function getTrendBuckets(
        string $period,
        Carbon $startDate,
        Carbon $endDate,
        bool $groupByMonth = false
    )
    {
        $buckets = collect();

        if ($groupByMonth) {
            $cursor = $startDate->copy()->startOfMonth();

            while ($cursor->lte($endDate)) {
                $buckets->push([
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->format('M Y'),
                ]);
                $cursor->addMonthNoOverflow();
            }

            return $buckets;
        }

        $cursor = $startDate->copy()->startOfDay();

        while ($cursor->lte($endDate)) {
            $buckets->push([
                'key' => $cursor->format('Y-m-d'),
                'label' => $period === 'daily' ? $cursor->format('d M') : $cursor->format('D d'),
            ]);
            $cursor->addDay();
        }

        return $buckets;
    }

    /**
     * Calculates working days between two dates based on your allowed $days string.
     */
    private function getWorkingDaysCount($from, $to)
    {
        // Your defined validation rule string
        $allowedDays = explode(',', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday');

        $workingDays = 0;
        $period = new \DatePeriod($from, new \DateInterval('P1D'), $to->copy()->addDay());

        foreach ($period as $date) {
            if (in_array($date->format('l'), $allowedDays)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Logic to determine the +/- percentage change for the current day.
     */
    private function calculateDailyChange()
    {
        $isPresentToday = EmployeeAttendance::whereDate('attendance_date', now())
            // ->where('status', 'present')
            ->exists();

        // Logic: If present, +0.5% boost to trend; if absent, -0.2% drop.
        return $isPresentToday ? 0.5 : -0.2;
    }

    /**
     * Get Dashboard Data
     */
    private function getDashboardData()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Total Employees
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();

        // Today's Attendance
        $presentToday = EmployeeAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->count();

        $lateToday = EmployeeAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->whereTime('check_in', '>', '09:00:00')
            ->count();

        // Get absent count by checking which active employees don't have attendance today
        $employeesWithAttendanceToday = EmployeeAttendance::whereDate('attendance_date', $today)
            ->whereNotNull('check_in')
            ->pluck('employee_id')
            ->toArray();

        $absentToday = Employee::where('status', 'active')
            ->whereNotIn('id', $employeesWithAttendanceToday)
            ->count();

        $attendanceRate = $activeEmployees > 0 ? round(($presentToday / $activeEmployees) * 100, 1) : 0;

        // On Leave Today
        $onLeaveToday = LeaveRecord::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        // Open Positions
        $openPositions = HrmJob::where('status', 'open')
            ->count();

        // Department Distribution
        $departments = Department::withCount('employees')->get()
            ->map(function ($department) use ($totalEmployees) {
                return [
                    'name' => $department->department,
                    'count' => $department->employees_count,
                    'percentage' => $totalEmployees > 0 ? round(($department->employees_count / $totalEmployees) * 100, 1) : 0,
                    'color' => $this->getDepartmentColor($department->department),
                ];
            });

        // Weekly Attendance Trend
        $weeklyAttendance = $this->getWeeklyAttendanceTrend($startOfWeek, $endOfWeek);

        // Recent Activities (Last 5 activities from various sources)
        $recentActivities = $this->getRecentActivities();

        // Pending Requests
        $pendingLeaveRequests = LeaveRecord::count();
        $pendingTasks = Task::where('status', 'pending')->count();

        // Pending Onboarding - assuming you have an onboarding status field
        $pendingOnboarding = Employee::where(function ($query) {
            $query->where('status', 'in_progress')
                ->orWhereNull('status'); // If no onboarding status field
        })->count();

        // Quick Stats
        // $promotionsThisMonth = Employee::where('last_promotion_date', '>=', Carbon::now()->startOfMonth())->count();
        $promotionsThisMonth = 5;

        // Calculate retention rate
        $totalActive = Employee::where('status', 'active')->count();
        $totalInactive = Employee::where('status', 'inactive')->count();
        $retentionRate = ($totalActive + $totalInactive) > 0
            ? round(($totalActive / ($totalActive + $totalInactive)) * 100, 1)
            : 100;

        return [
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'presentCount' => $presentToday,
            'lateCount' => $lateToday,
            'absentCount' => $absentToday,
            'attendanceRate' => $attendanceRate,
            'onLeaveToday' => $onLeaveToday,
            'openPositions' => $openPositions,
            'departments' => $departments,
            'weeklyAttendance' => $weeklyAttendance,
            'recentActivities' => $recentActivities,
            'pendingLeaveRequests' => $pendingLeaveRequests,
            'pendingTasks' => $pendingTasks,
            'pendingOnboarding' => $pendingOnboarding,
            'promotionsThisMonth' => $promotionsThisMonth,
            'retentionRate' => $retentionRate,
        ];
    }

    /**
     * Get Weekly Attendance Trend
     */
    private function getWeeklyAttendanceTrend($startOfWeek, $endOfWeek)
    {
        $attendanceData = [];
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

        // Get active employee count
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        // Get attendance for each day of the week (Monday to Friday)
        for ($i = 0; $i < 5; $i++) {
            $date = $startOfWeek->copy()->addDays($i);

            // Get present count for this day
            $presentCount = EmployeeAttendance::whereDate('attendance_date', $date)
                ->whereNotNull('check_in')
                ->count();

            // Calculate attendance rate
            $rate = $totalActiveEmployees > 0 ? round(($presentCount / $totalActiveEmployees) * 100, 0) : 0;

            $attendanceData[] = [
                'day' => $days[$i],
                'date' => $date->format('Y-m-d'),
                'rate' => $rate,
                'present' => $presentCount,
                'height' => $rate, // For chart visualization
            ];
        }

        return $attendanceData;
    }

    /**
     * Get Recent Activities
     */
    private function getRecentActivities()
    {
        $activities = collect();

        // Recent hires (last 5 days) - assuming 'join_date' or 'hire_date' field exists
        $recentHires = Employee::where(function ($query) {
            // Check for different possible date fields
            $query->where('date_of_joining', '>=', Carbon::now()->subDays(5))
                ->orWhere('date_of_joining', '>=', Carbon::now()->subDays(5))
                ->orWhere('created_at', '>=', Carbon::now()->subDays(5));
        })
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($employee) {
                $dateField = $employee->hire_date ?? $employee->join_date ?? $employee->created_at;

                return [
                    'action' => 'New employee onboarded',
                    'person' => $employee->full_name ?? $employee->name ?? 'Employee',
                    'time' => Carbon::parse($dateField)->diffForHumans(),
                    'type' => 'onboarding',
                    'icon' => 'user-add',
                ];
            });

        // Recent leave approvals (last 3 days)
        $recentLeaves = LeaveRecord::where('updated_at', '>=', Carbon::now()->subDays(3))
            ->orderBy('updated_at', 'desc')
            ->take(2)
            ->get()
            ->map(function ($leave) {
                return [
                    'action' => 'Leave request approved',
                    'person' => $leave->employee->full_name ?? 'Employee',
                    'time' => $leave->updated_at->diffForHumans(),
                    'type' => 'leave',
                    'icon' => 'calendar',
                ];
            });

        // Recent attendance updates
        $recentAttendance = EmployeeAttendance::with('employee')
            ->where('updated_at', '>=', Carbon::now()->subDays(2))
            ->orderBy('updated_at', 'desc')
            ->take(2)
            ->get()
            ->map(function ($attendance) {
                return [
                    'action' => 'Attendance updated',
                    'person' => $attendance->employee->full_name ?? 'Employee',
                    'time' => $attendance->updated_at->diffForHumans(),
                    'type' => 'attendance',
                    'icon' => 'clock',
                ];
            });

        // Merge all activities
        // $activities = $recentHires->merge($recentLeaves)->merge($recentAttendance);
        $activities = collect($recentHires)
            ->merge(collect($recentLeaves))
            ->merge(collect($recentAttendance));

        // Sort by time (most recent first)
        return $activities->sortByDesc(function ($activity) {
            // Convert relative time to timestamp for sorting
            return strtotime($activity['time']);
        })->values()->take(5);
    }

    /**
     * Get Department Color
     */
    private function getDepartmentColor($departmentName)
    {
        $colors = [
            'Engineering' => 'bg-[#0078C8]',
            'Sales' => 'bg-blue-400',
            'Marketing' => 'bg-blue-400',
            'Operations' => 'bg-purple-500',
            'Human Resources' => 'bg-pink-500',
            'HR' => 'bg-pink-500',
            'Finance' => 'bg-amber-500',
            'Customer Support' => 'bg-green-500',
            'Support' => 'bg-green-500',
            'IT' => 'bg-indigo-500',
            'Technology' => 'bg-indigo-500',
            'Admin' => 'bg-gray-500',
            'Administration' => 'bg-gray-500',
        ];

        // Check for exact matches first
        if (isset($colors[$departmentName])) {
            return $colors[$departmentName];
        }

        // Check for partial matches
        foreach ($colors as $key => $color) {
            if (stripos($departmentName, $key) !== false) {
                return $color;
            }
        }

        // Default color
        $defaultColors = [
            'bg-[#0078C8]',
            'bg-blue-400',
            'bg-purple-500',
            'bg-pink-500',
            'bg-amber-500',
            'bg-green-500',
            'bg-indigo-500',
            'bg-gray-500',
        ];

        return $defaultColors[array_rand($defaultColors)];
    }

    /**
     * AJAX endpoint for dashboard metrics
     */
    public function getDashboardMetrics1(Request $request)
    {
        try {
            $dashboardData = $this->getDashboardData();

            return response()->json([
                'success' => true,
                'data' => [
                    'totalEmployees' => $dashboardData['totalEmployees'],
                    'activeEmployees' => $dashboardData['activeEmployees'],
                    'presentCount' => $dashboardData['presentCount'],
                    'lateCount' => $dashboardData['lateCount'],
                    'absentCount' => $dashboardData['absentCount'],
                    'attendanceRate' => $dashboardData['attendanceRate'],
                    'onLeaveToday' => $dashboardData['onLeaveToday'],
                    'openPositions' => $dashboardData['openPositions'],
                    'pendingLeaveRequests' => $dashboardData['pendingLeaveRequests'],
                    'pendingOnboarding' => $dashboardData['pendingOnboarding'],
                    'pendingTasks' => $dashboardData['pendingTasks'],
                    'promotionsThisMonth' => $dashboardData['promotionsThisMonth'],
                    'retentionRate' => $dashboardData['retentionRate'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Dashboard Chart Data
     */
    public function getDashboardChartData(Request $request)
    {
        try {
            $period = $request->get('period', 'week'); // week, month, year

            if ($period === 'week') {
                $data = $this->getWeeklyAttendanceTrend(
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                );
            } elseif ($period === 'month') {
                $data = $this->getMonthlyAttendanceData();
            } else {
                $data = $this->getYearlyAttendanceData();
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chart data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Monthly Attendance Data
     */
    private function getMonthlyAttendanceData()
    {
        $data = [];
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $weeks = ceil($startOfMonth->diffInDays($endOfMonth) / 7);
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        for ($week = 1; $week <= min($weeks, 4); $week++) {
            $weekStart = $startOfMonth->copy()->addWeeks($week - 1);
            $weekEnd = $weekStart->copy()->addDays(6);

            // Calculate average attendance for the week
            $presentCount = EmployeeAttendance::whereBetween('attendance_date', [$weekStart, $weekEnd])
                ->whereNotNull('check_in')
                ->count();

            // Get unique employee count per week
            $uniqueEmployees = EmployeeAttendance::whereBetween('attendance_date', [$weekStart, $weekEnd])
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count('employee_id');

            $averageAttendance = $totalActiveEmployees > 0
                ? round(($uniqueEmployees / $totalActiveEmployees) * 100, 0)
                : 0;

            $data[] = [
                'label' => "Week $week",
                'rate' => $averageAttendance,
                'height' => $averageAttendance,
            ];
        }

        return $data;
    }

    /**
     * Get Yearly Attendance Data
     */
    private function getYearlyAttendanceData()
    {
        $data = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $currentYear = Carbon::now()->year;
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        for ($month = 0; $month < 12; $month++) {
            $monthNumber = $month + 1;
            $startOfMonth = Carbon::create($currentYear, $monthNumber, 1);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // Calculate monthly attendance
            $presentCount = EmployeeAttendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('check_in')
                ->count();

            $uniqueEmployees = EmployeeAttendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('check_in')
                ->distinct('employee_id')
                ->count('employee_id');

            $monthlyRate = $totalActiveEmployees > 0
                ? round(($uniqueEmployees / $totalActiveEmployees) * 100, 0)
                : 0;

            $data[] = [
                'label' => $months[$month],
                'rate' => $monthlyRate,
                'height' => $monthlyRate,
            ];
        }

        return $data;
    }

    /**
     * Get real-time attendance statistics
     */
    public function getRealTimeStats(Request $request)
    {
        try {
            $today = Carbon::today();

            // Get current attendance stats
            $totalActive = Employee::where('status', 'active')->count();
            $presentCount = EmployeeAttendance::whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->count();

            $lateCount = EmployeeAttendance::whereDate('attendance_date', $today)
                ->whereNotNull('check_in')
                ->whereTime('check_in', '>', '09:00:00')
                ->count();

            $attendanceRate = $totalActive > 0 ? round(($presentCount / $totalActive) * 100, 1) : 0;

            // Get pending leave requests
            $pendingLeaves = LeaveRecord::where('status', 'pending')->count();

            // Get recent activity count
            $recentActivities = EmployeeAttendance::where('created_at', '>=', Carbon::now()->subHours(1))->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'presentCount' => $presentCount,
                    'lateCount' => $lateCount,
                    'attendanceRate' => $attendanceRate,
                    'pendingLeaves' => $pendingLeaves,
                    'recentActivities' => $recentActivities,
                    'lastUpdated' => Carbon::now()->format('h:i A'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching real-time stats',
            ], 500);
        }
    }

    /**
     * Display the recruitment dashboard.
     */
    public function recruitmentDashboard(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            // 1. Get Top Statistics
            $activeJobs = HrmJob::where('company_id', $companyId)
                ->where('status', 'open')
                ->count();

            $newApplications = HrmCandidate::where('company_id', $companyId)
                ->whereDate('applied_date', Carbon::today())
                ->count();

            // Get interviews scheduled for today
            $interviewsToday = HrmCandidate::where('company_id', $companyId)
                ->where('stage', 'interviewing')
                ->whereDate('interview_date', Carbon::today())
                ->count();

            // Calculate average time-to-hire in days
            $avgTimeToHire = $this->calculateAvgTimeToHire($companyId);

            // 2. Get Candidate Pipeline Statistics
            $pipelineStats = $this->getCandidatePipelineStats($companyId);

            // 3. Get Upcoming Interviews (next 3 days)
            $upcomingInterviews = HrmCandidate::with(['job'])
                ->where('company_id', $companyId)
                ->where('stage', 'interview')
                ->whereNotNull('interview_date')
                ->whereBetween('interview_date', [
                    Carbon::today(),
                    Carbon::today()->addDays(3),
                ])
                ->orderBy('interview_date')
                ->orderBy('interview_time')
                ->limit(5)
                ->get()
                ->map(function ($candidate) {
                    $interviewDate = Carbon::parse($candidate->interview_date);
                    $isToday = $interviewDate->isToday();
                    $isTomorrow = $interviewDate->isTomorrow();

                    return [
                        'id' => $candidate->id,
                        'full_name' => $candidate->full_name,
                        'initials' => strtoupper(substr($candidate->first_name, 0, 1).substr($candidate->last_name, 0, 1)),
                        'current_role' => $candidate->current_role,
                        'job_title' => $candidate->job->job_title ?? 'Not Assigned',
                        'interview_time' => $candidate->interview_time ? date('h:i A', strtotime($candidate->interview_time)) : 'N/A',
                        'interview_date_formatted' => $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $interviewDate->format('M d')),
                        'interview_date' => $interviewDate,
                        'is_today' => $isToday,
                        'color_class' => $isToday ? 'blue' : ($isTomorrow ? 'green' : 'purple'),
                    ];
                });

            // 4. Get Recent Applications
            $recentApplications = HrmCandidate::with(['job'])
                ->where('company_id', $companyId)
                ->latest('applied_date')
                ->limit(5)
                ->get()
                ->map(function ($candidate) {
                    return [
                        'id' => $candidate->id,
                        'full_name' => $candidate->full_name,
                        'initials' => strtoupper(substr($candidate->first_name, 0, 1).substr($candidate->last_name, 0, 1)),
                        'role_applied' => $candidate->job->job_title ?? 'Not Specified',
                        'applied_date' => $candidate->applied_date ? $candidate->applied_date->format('M d, Y') : 'N/A',
                        'stage' => ucfirst($candidate->stage),
                        'stage_color' => $this->getStageColor($candidate->stage),
                    ];
                });

            // 5. Get Quick Stats
            $quickStats = $this->getQuickStats($companyId);

            // 6. Calculate growth percentages
            $jobsChange = $this->calculateJobGrowth($companyId);
            $appChange = $this->calculateApplicationGrowth($companyId);

            return view('pages.hrm.recruitment', compact(
                'activeJobs',
                'newApplications',
                'interviewsToday',
                'avgTimeToHire',
                'pipelineStats',
                'upcomingInterviews',
                'recentApplications',
                'quickStats',
                'jobsChange',
                'appChange',
                'companyId'
            ));

        } catch (\Exception $e) {
            \Log::error('Error loading recruitment dashboard: '.$e->getMessage());
            \Log::error('Trace: '.$e->getTraceAsString());

            return redirect()->back()->with('error', 'Error loading dashboard: '.$e->getMessage());
        }
    }

    /**
     * Calculate average time-to-hire in days.
     */
    private function calculateAvgTimeToHire($companyId)
    {
        // Get hired candidates with interview date and applied date
        $hiredCandidates = HrmCandidate::where('company_id', $companyId)
            ->where('stage', 'hired')
            ->whereNotNull('applied_date')
            ->whereNotNull('interview_date')
            ->get();

        if ($hiredCandidates->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($hiredCandidates as $candidate) {
            $appliedDate = Carbon::parse($candidate->applied_date);
            $interviewDate = Carbon::parse($candidate->interview_date);

            // Calculate days between application and interview
            $days = $appliedDate->diffInDays($interviewDate);

            // Only count if interview happened after application
            if ($days >= 0) {
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count) : 0;
    }

    /**
     * Get candidate pipeline statistics.
     */
    private function getCandidatePipelineStats($companyId)
    {
        $stages = ['applied', 'screening', 'interview', 'offer', 'hired'];

        $stats = [];
        foreach ($stages as $stage) {
            $count = HrmCandidate::where('company_id', $companyId)
                ->where('stage', $stage)
                ->count();

            $stats[$stage] = [
                'count' => $count,
                'percentage' => $this->calculatePipelinePercentage($stage, $companyId),
            ];
        }

        return $stats;
    }

    /**
     * Calculate percentage for pipeline visualization.
     */
    private function calculatePipelinePercentage($stage, $companyId)
    {
        // Calculate percentage based on actual data
        $totalCandidates = HrmCandidate::where('company_id', $companyId)->count();

        if ($totalCandidates == 0) {
            $stageOrder = ['applied' => 100, 'screening' => 58, 'interview' => 28, 'offer' => 10, 'hired' => 5];

            return $stageOrder[$stage] ?? 0;
        }

        $stageCount = HrmCandidate::where('company_id', $companyId)
            ->where('stage', $stage)
            ->count();

        // For demo purposes, use fixed percentages if we don't have enough data
        if ($stageCount == 0) {
            $stageOrder = ['applied' => 100, 'screening' => 58, 'interview' => 28, 'offer' => 10, 'hired' => 5];

            return $stageOrder[$stage] ?? 0;
        }

        // Calculate actual percentage
        return round(($stageCount / $totalCandidates) * 100);
    }

    /**
     * Get color class for stage badge.
     */
    private function getStageColor($stage)
    {
        $colors = [
            'new' => 'blue',
            'applied' => 'blue',
            'screening' => 'purple',
            'interview' => 'green',
            'offer' => 'amber',
            'hired' => 'green',
            'rejected' => 'red',
        ];

        return $colors[$stage] ?? 'gray';
    }

    /**
     * Get quick statistics for dashboard.
     */
    private function getQuickStats($companyId)
    {
        // Offers accepted percentage
        $offersMade = HrmCandidate::where('company_id', $companyId)
            ->where('stage', 'offer')
            ->count();

        $offersAccepted = HrmCandidate::where('company_id', $companyId)
            ->where('stage', 'hired')
            ->whereNotNull('interview_date')
            ->count();

        $offerAcceptanceRate = $offersMade > 0
            ? round(($offersAccepted / $offersMade) * 100)
            : 0;

        // Average candidate rating (quality score)
        $avgRating = HrmCandidate::where('company_id', $companyId)
            ->whereNotNull('rating')
            ->avg('rating');

        // Hiring satisfaction - using fixed value as per design
        $hiringSatisfaction = 92;

        return [
            'offer_acceptance_rate' => $offerAcceptanceRate,
            'quality_score' => $avgRating ? round($avgRating, 1) : 4.8,
            'hiring_satisfaction' => $hiringSatisfaction,
        ];
    }

    /**
     * Get dashboard metrics for AJAX requests.
     */
    public function getDashboardMetrics(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;

            $metrics = [
                'active_jobs' => HrmJob::where('company_id', $companyId)
                    ->where('status', 'open')
                    ->count(),

                'new_applications' => HrmCandidate::where('company_id', $companyId)
                    ->whereDate('applied_date', Carbon::today())
                    ->count(),

                'interviews_today' => HrmCandidate::where('company_id', $companyId)
                    ->where('stage', 'interviewing')
                    ->whereDate('interview_date', Carbon::today())
                    ->count(),

                'avg_time_to_hire' => $this->calculateAvgTimeToHire($companyId),
            ];

            // Get pipeline stats for visualization
            $pipelineStats = $this->getCandidatePipelineStats($companyId);

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'pipeline' => $pipelineStats,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching dashboard metrics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching metrics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate job growth percentage.
     */
    private function calculateJobGrowth($companyId)
    {
        // Get active jobs this week
        $currentWeekJobs = HrmJob::where('company_id', $companyId)
            ->where('status', 'open')
            ->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])
            ->count();

        // Get active jobs last week
        $lastWeekJobs = HrmJob::where('company_id', $companyId)
            ->where('status', 'open')
            ->whereBetween('created_at', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
            ])
            ->count();

        if ($lastWeekJobs == 0) {
            return $currentWeekJobs > 0 ? 21 : 0; // Default 21% as per design
        }

        $growth = (($currentWeekJobs - $lastWeekJobs) / $lastWeekJobs) * 100;

        return round($growth, 1);
    }

    /**
     * Calculate application growth percentage.
     */
    private function calculateApplicationGrowth($companyId)
    {
        // Get applications this month
        $currentMonthApps = HrmCandidate::where('company_id', $companyId)
            ->whereBetween('applied_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();

        // Get applications last month
        $lastMonthApps = HrmCandidate::where('company_id', $companyId)
            ->whereBetween('applied_date', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->count();

        if ($lastMonthApps == 0) {
            return $currentMonthApps > 0 ? 12 : 0; // Default 12% as per design
        }

        $growth = (($currentMonthApps - $lastMonthApps) / $lastMonthApps) * 100;

        return round($growth, 1);
    }

    /**
     * Get the recruitment dashboard view.
     */
    public function recruitment()
    {
        return $this->recruitmentDashboard(request());
    }

    /**
     * Display tenure milestones list
     */
    public function tenureMilestones(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = EmployeeTenureMilestone::with('employee.department')
            ->where('company_id', $companyId);

        // Filter: from_date / to_date (on milestone_date)
        if ($request->filled('from_date')) {
            $query->whereDate('milestone_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('milestone_date', '<=', $request->to_date);
        }

        // Filter: department
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter: milestone type (e.g. 6_months, 1_year, 2_years)
        if ($request->filled('milestone')) {
            $query->where('milestone', $request->milestone);
        }

        // Filter: acknowledged status
        if ($request->filled('status')) {
            $query->where('is_read', $request->status === 'acknowledged' ? 1 : 0);
        }

        // Filter: search by employee name
        if ($request->filled('q')) {
            $search = $request->q;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('is_read', 'asc')->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 20);
        $milestones = $query->paginate($perPage)->withQueryString();

        $departments = Department::where('company_id', $companyId)->orderBy('department')->get();

        return view('pages.hrm.tenure_milestones.index', compact('milestones', 'departments'));
    }

    /**
     * Mark milestone as read
     */
    public function markMilestoneAsRead($id)
    {
        try {
            $milestone = EmployeeTenureMilestone::findOrFail($id);
            $milestone->update(['is_read' => true]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
