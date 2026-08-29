<?php

namespace App\Jobs;

use App\Mail\PayslipMail;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPayslipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $payslipId) {}

    public function handle(): void
    {
        $payslip = Payslip::with(['employee.company.currency', 'employee.department', 'payRun', 'items.payItem'])->find($this->payslipId);

        if (!$payslip || !$payslip->employee?->email) {
            Log::warning("SendPayslipEmailJob: skipping payslip #{$this->payslipId} — no employee or email.");
            return;
        }

        Mail::to($payslip->employee->email)->send(new PayslipMail($payslip));

        Log::info("Payslip email sent to {$payslip->employee->email} for payslip #{$this->payslipId}");
    }
}
