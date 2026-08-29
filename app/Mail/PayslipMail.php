<?php

namespace App\Mail;

use App\Models\LeaveBalance;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payslip $payslip) {}

    public function build()
    {
        $this->payslip->loadMissing(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun']);

        $leaveBalances = LeaveBalance::where('employee_id', $this->payslip->employee_id)
            ->where('year', $this->payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        $pdfContent = Pdf::loadView('pdf.payslip', [
            'payslip'       => $this->payslip,
            'leaveBalances' => $leaveBalances,
        ])->setPaper('a4', 'portrait')->output();

        $fileName = 'Payslip_'
            . str_replace(' ', '_', $this->payslip->employee->full_name)
            . '_' . $this->payslip->payRun->period_end->format('M_Y')
            . '.pdf';

        $period = $this->payslip->payRun->period_end->format('M Y');

        return $this->subject("Your Payslip – {$period}")
            ->view('emails.payslip')
            ->attachData($pdfContent, $fileName, ['mime' => 'application/pdf']);
    }
}
