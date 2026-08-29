<?php

namespace App\Http\Controllers\HRM;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeType;
use App\Services\HRM\HrmAvailableReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HrmReportController extends Controller
{
    public function index(Request $request, HrmAvailableReportService $reports)
    {
        $filters = $this->filters($request);
        $companyId = (int) auth('web')->user()->company_id;
        $data = $reports->build($filters, $companyId);
        $perPage = (int) ($filters['per_page'] ?? 20);

        return view('pages.hrm.reports.index', array_merge($data, [
            'employeeRows' => $this->paginate($data['employees'], $perPage, 'employees_page'),
            'attendanceRows' => $this->paginate($data['attendance'], $perPage, 'attendance_page'),
            'leaveBalanceRows' => $this->paginate($data['leaveBalances'], $perPage, 'leave_page'),
            'jobRows' => $this->paginate($data['jobs'], $perPage, 'jobs_page'),
            'surveyRows' => $this->paginate($data['surveys'], $perPage, 'surveys_page'),
            'exceptionRows' => $this->paginate($data['exceptions'], $perPage, 'exceptions_page'),
            'departments' => Department::select('id', 'department')->orderBy('department')->get(),
            'employeeTypes' => EmployeeType::select('id', 'employee_type')->orderBy('employee_type')->get(),
            'workLocations' => $data['employees']->pluck('work_location')->filter()->unique()->sort()->values(),
            'filters' => $filters,
        ]));
    }

    public function exportPdf(Request $request, HrmAvailableReportService $reports)
    {
        $filters = $this->filters($request);
        $companyId = (int) auth('web')->user()->company_id;
        $data = $reports->build($filters, $companyId);

        return Pdf::loadView('pdf.hrm-available-reports', array_merge($data, compact('filters')))
            ->setPaper('A4', 'landscape')
            ->download('hrm-available-reports-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request, HrmAvailableReportService $reports)
    {
        $filters = $this->filters($request);
        $companyId = (int) auth('web')->user()->company_id;
        $data = $reports->build($filters, $companyId);
        $report = $request->input('export_report', 'employees');
        [$headings, $rows] = $this->csvData($report, $data);

        return response()->streamDownload(function () use ($headings, $rows) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headings);
            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }
            fclose($stream);
        }, 'hrm-'.$report.'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'department_id' => ['nullable', 'integer'],
            'employment_type_id' => ['nullable', 'integer'],
            'employee_status' => ['nullable', 'in:active,inactive,resigned,terminated'],
            'work_location' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'attendance_status' => ['nullable', 'in:present,absent,late,remote,exception'],
            'job_status' => ['nullable', 'in:draft,open,on_hold,closed,cancelled'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
            'export_report' => ['nullable', 'in:employees,attendance,leave,jobs,surveys,exceptions'],
        ]);
    }

    private function paginate(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->except($pageName),
            ]
        );
    }

    private function csvData(string $report, array $data): array
    {
        return match ($report) {
            'attendance' => [
                ['Employee ID', 'Employee', 'Date', 'Check In', 'Check Out', 'Status', 'Work Hours', 'Working Type', 'Exceptions'],
                $data['attendance']->map(fn ($row) => [
                    $row->employee?->employee_id,
                    $row->employee?->full_name,
                    $row->attendance_date?->format('Y-m-d'),
                    $row->formatted_check_in,
                    $row->formatted_check_out,
                    $row->report_status,
                    $row->total_work_hours,
                    $row->working_type,
                    implode(' | ', $row->exception_labels),
                ]),
            ],
            'leave' => [
                ['Employee ID', 'Employee', 'Department', 'Leave Type', 'Year', 'Entitlement', 'Used', 'Remaining', 'Utilization %'],
                $data['leaveBalances']->map(fn ($row) => [
                    $row->employee?->employee_id,
                    $row->employee?->full_name,
                    $row->employee?->department?->department,
                    $row->leaveType?->name,
                    $row->year,
                    $row->total_leaves,
                    $row->used_leaves,
                    $row->remaining_leaves,
                    $row->report_utilization,
                ]),
            ],
            'jobs' => [
                ['Job ID', 'Job Title', 'Department', 'Status', 'Posted Date', 'Closing Date', 'Age Days', 'Candidates', 'Overdue'],
                $data['jobs']->map(fn ($row) => [
                    $row->job_id,
                    $row->job_title,
                    $row->department?->department,
                    $row->status,
                    $row->posted_date?->format('Y-m-d'),
                    $row->closing_date?->format('Y-m-d'),
                    $row->age_days,
                    $row->report_candidates_count,
                    $row->is_overdue ? 'Yes' : 'No',
                ]),
            ],
            'surveys' => [
                ['Survey', 'Status', 'Start Date', 'End Date', 'Anonymous', 'Audience', 'Responses', 'Response Rate %'],
                $data['surveys']->map(fn ($row) => [
                    $row->title,
                    $row->status,
                    $row->start_date?->format('Y-m-d'),
                    $row->end_date?->format('Y-m-d'),
                    $row->is_anonymous ? 'Yes' : 'No',
                    $row->audience_count,
                    $row->response_count,
                    $row->response_rate,
                ]),
            ],
            'exceptions' => [
                ['Area', 'Reference', 'Name', 'Exception'],
                $data['exceptions']->map(fn ($row) => [$row['area'], $row['reference'], $row['name'], $row['message']]),
            ],
            default => [
                ['Employee ID', 'Name', 'Department', 'Employee Type', 'Designation', 'Work Location', 'Status', 'Joining Date', 'Confirmation Date', 'Resignation Date', 'Tenure Years'],
                $data['employees']->map(fn ($row) => [
                    $row->employee_id,
                    $row->full_name,
                    $row->department?->department,
                    $row->employmentType?->employee_type,
                    $row->designation,
                    $row->work_location,
                    $row->status,
                    $row->date_of_joining,
                    $row->date_of_confirmation,
                    $row->resign_date,
                    $row->tenure_years,
                ]),
            ],
        };
    }
}
