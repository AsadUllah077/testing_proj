<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\HrmJob;
use App\Models\LeaveBalance;
use App\Services\HRM\HrmAvailableReportService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HrmAvailableReportServiceTest extends TestCase
{
    public function test_employee_data_quality_exceptions_are_reported(): void
    {
        $employee = new Employee([
            'id' => 10,
            'full_name' => 'Incomplete Employee',
            'status' => 'active',
            'resign_date' => '2026-07-01',
        ]);

        $exceptions = (new HrmAvailableReportService)->exceptions(
            new Collection([$employee]),
            collect(),
            collect(),
            collect(),
            collect()
        );

        $messages = $exceptions->pluck('message');

        $this->assertContains('Missing employee ID', $messages);
        $this->assertContains('Missing department', $messages);
        $this->assertContains('Missing joining date', $messages);
        $this->assertContains('Active employee has resignation date', $messages);
    }

    public function test_leave_balance_reconciliation_exception_is_reported(): void
    {
        $balance = new LeaveBalance([
            'id' => 5,
            'total_leaves' => 20,
            'used_leaves' => 5,
            'remaining_leaves' => 20,
        ]);

        $exceptions = (new HrmAvailableReportService)->exceptions(
            collect(),
            collect(),
            new Collection([$balance]),
            collect(),
            collect()
        );

        $this->assertTrue(
            $exceptions->contains(fn ($exception) => $exception['message'] === 'Leave balance does not reconcile')
        );
    }

    public function test_recruitment_count_and_overdue_exceptions_are_reported(): void
    {
        $job = new HrmJob([
            'id' => 7,
            'job_id' => 'JOB-007',
            'job_title' => 'Analyst',
            'status' => 'open',
            'applicants_count' => 4,
        ]);
        $job->setAttribute('report_candidates_count', 2);
        $job->setAttribute('is_overdue', true);

        $exceptions = (new HrmAvailableReportService)->exceptions(
            collect(),
            collect(),
            collect(),
            new Collection([$job]),
            collect()
        );

        $messages = $exceptions->pluck('message');

        $this->assertContains('Open job has no posted date', $messages);
        $this->assertContains('Open job is past its closing date', $messages);
        $this->assertContains('Stored applicant count does not match candidates', $messages);
    }
}
