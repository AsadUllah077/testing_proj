<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayRun;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Retrieves and formats employee-owned payslip data for the AI payslip assistant.
 */
class PayslipChatService
{
    protected Employee $employee;
    protected Collection $payslips;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
        $this->payslips = collect();
    }

    /**
     * Load the employee's visible payslips for the current and recent years.
     */
    public function loadPayslipContext(int $yearsBack = 1): Collection
    {
        $startDate = now()->subYears($yearsBack)->startOfYear();

        $this->payslips = $this->visiblePayslipsQuery()
            ->whereHas('payRun', fn($q) => $q->where('period_end', '>=', $startDate))
            ->orderByDesc(
                PayRun::query()
                    ->select('period_end')
                    ->whereColumn('pay_runs.id', 'payslips.pay_run_id')
                    ->limit(1)
            )
            ->get();

        return $this->payslips;
    }

    /**
     * Build comprehensive context for the AI RAG system.
     */
    public function buildContext(): string
    {
        if ($this->payslips->isEmpty()) {
            return "No payslip data available for {$this->employee->full_name}.";
        }

        $latestPayslip = $this->payslips->first();
        $context = [];
        $context[] = "=== EMPLOYEE PAYSLIP CONTEXT ===";
        $context[] = "Employee: {$this->employee->full_name}";
        $context[] = "Employee ID: " . ($this->employee->employee_id ?? $this->employee->id);
        $context[] = "Department: " . ($this->employee->department?->department ?? 'N/A');
        $context[] = "Current Date: " . now()->format('M d, Y');
        $context[] = "";

        $context[] = "=== CURRENT YEAR-TO-DATE (YTD) SUMMARY ===";
        $context[] = "YTD Gross Pay: " . number_format($latestPayslip->ytd_gross ?? 0, 2);
        $context[] = "YTD Deductions: " . number_format($latestPayslip->ytd_deductions ?? 0, 2);
        $context[] = "YTD Net Pay: " . number_format($latestPayslip->ytd_net ?? 0, 2);
        $context[] = "";

        $context[] = "=== RECENT PAYSLIPS (Last 6 visible payroll periods) ===";
        foreach ($this->payslips->take(6) as $payslip) {
            $period = $payslip->payRun->period_end->format('M Y');
            $context[] = "- {$period}: Gross: " . number_format($payslip->gross_earnings ?? 0, 2)
                . " | Deductions: " . number_format($payslip->total_deductions ?? 0, 2)
                . " | Net: " . number_format($payslip->net_pay ?? 0, 2);
        }
        $context[] = "";

        $context[] = "=== LATEST PAYSLIP BREAKDOWN ({$latestPayslip->payRun->period_end->format('M d, Y')}) ===";
        $context[] = "Pay Period: " . $latestPayslip->payRun->period_start->format('M d')
            . " - " . $latestPayslip->payRun->period_end->format('M d, Y');
        $context[] = "Payment Date: " . (optional($latestPayslip->payRun->payment_date)->format('M d, Y') ?? 'N/A');
        $context[] = "Pay Frequency: " . ucfirst($latestPayslip->payRun->frequency ?? 'not specified');
        $context[] = "";

        $this->appendItemBreakdown(
            $context,
            '--- EARNINGS ---',
            $latestPayslip->items
                ->filter(fn($i) => $i->payItem && in_array($i->payItem->type, ['earning', 'reimbursement'], true))
                ->sortByDesc('amount'),
            'Total Earnings'
        );

        $this->appendItemBreakdown(
            $context,
            '--- DEDUCTIONS ---',
            $latestPayslip->items
                ->filter(fn($i) => $i->payItem && $i->payItem->type === 'deduction')
                ->sortByDesc('amount'),
            'Total Deductions'
        );

        $taxDeductions = $latestPayslip->items
            ->filter(fn($item) => $this->isTaxItem($item))
            ->sum('amount');

        if ($taxDeductions > 0) {
            $context[] = "--- TAX DETAILS ---";
            $context[] = "  - Total Tax This Period: " . number_format($taxDeductions, 2);
            $context[] = "  - YTD Tax: " . number_format($this->calculateYTDTax($latestPayslip), 2);
            $context[] = "";
        }

        $context[] = "--- PERIOD SUMMARY ---";
        $context[] = "  - Gross Pay: " . number_format($latestPayslip->gross_earnings ?? 0, 2);
        $context[] = "  - Total Deductions: " . number_format($latestPayslip->total_deductions ?? 0, 2);
        $context[] = "  - Net Pay (Take Home): " . number_format($latestPayslip->net_pay ?? 0, 2);
        $context[] = "";

        $context[] = "=== ACCESS RULES ===";
        $context[] = "Only answer from the employee-owned payslip data above.";
        $context[] = "Do not answer questions about other employees, company payroll totals, or accounting data.";
        $context[] = "If a requested amount is not present, say it is not available in the payslip context.";

        return implode("\n", $context);
    }

    /**
     * Calculate YTD tax by summing tax deductions across visible payslips.
     */
    protected function calculateYTDTax(Payslip $currentPayslip): float
    {
        $currentYear = $currentPayslip->payRun->period_end->year;

        return $this->visiblePayslipsQuery()
            ->whereHas('payRun', fn($q) => $q->whereYear('period_end', $currentYear))
            ->get()
            ->flatMap(fn($p) => $p->items)
            ->filter(fn($item) => $this->isTaxItem($item))
            ->sum('amount');
    }

    /**
     * Get payslip by ID with an employee and company ownership check.
     */
    public function getPayslip(int $payslipId): ?Payslip
    {
        return $this->visiblePayslipsQuery()
            ->where('id', $payslipId)
            ->first();
    }

    /**
     * Answer specific payslip questions with scoped payslip context.
     */
    public function buildQuestionContext(int $payslipId, string $question): string
    {
        $payslip = $this->getPayslip($payslipId);

        if (!$payslip) {
            return "Payslip not found or access denied.";
        }

        $context = [];
        $context[] = "=== PAYSLIP CONTEXT FOR QUESTION ===";
        $context[] = "Question: " . $question;
        $context[] = "";
        $context[] = "Employee: {$this->employee->full_name}";
        $context[] = "Payslip Period: " . $payslip->payRun->period_start->format('M d, Y')
            . " - " . $payslip->payRun->period_end->format('M d, Y');
        $context[] = "";
        $context[] = "Payslip Summary:";
        $context[] = "  - Gross Pay: " . number_format($payslip->gross_earnings ?? 0, 2);
        $context[] = "  - Total Deductions: " . number_format($payslip->total_deductions ?? 0, 2);
        $context[] = "  - Net Pay: " . number_format($payslip->net_pay ?? 0, 2);
        $context[] = "  - YTD Gross: " . number_format($payslip->ytd_gross ?? 0, 2);
        $context[] = "  - YTD Deductions: " . number_format($payslip->ytd_deductions ?? 0, 2);
        $context[] = "  - YTD Net: " . number_format($payslip->ytd_net ?? 0, 2);
        $context[] = "";
        $context[] = "Detailed Items:";

        foreach ($payslip->items->sortByDesc('amount') as $item) {
            $type = $item->payItem->type ?? 'other';
            $context[] = "  - {$this->itemLabel($item)} ({$type}): " . number_format($item->amount, 2);
        }

        $context[] = "";
        $context[] = "ACCESS RULE: Answer only from this employee-owned payslip context.";

        return implode("\n", $context);
    }

    /**
     * Get common Q&A templates for knowledge base.
     */
    public static function getCommonQuestionsTemplates(): array
    {
        return [
            [
                'question' => 'Why is my tax higher this month?',
                'context' => 'Tax deductions depend on earnings level, allowances, and jurisdiction rules.',
                'keywords' => ['tax', 'higher', 'deduction', 'paye', 'income tax'],
            ],
            [
                'question' => "What's my YTD gross?",
                'context' => 'YTD (Year-To-Date) shows cumulative earnings from January to current period.',
                'keywords' => ['ytd', 'gross', 'cumulative', 'year-to-date'],
            ],
            [
                'question' => 'How is my provident fund calculated?',
                'context' => 'Provident fund is typically a percentage of gross salary, deducted monthly.',
                'keywords' => ['provident', 'fund', 'pf', 'pension', 'calculation'],
            ],
            [
                'question' => 'What are my deductions?',
                'context' => 'Deductions include tax, provident fund, insurance, and other withholdings.',
                'keywords' => ['deduction', 'deductions', 'withholding'],
            ],
            [
                'question' => 'When is my salary paid?',
                'context' => 'Payment dates are scheduled monthly, bi-weekly, or weekly depending on your contract.',
                'keywords' => ['payment', 'salary', 'paid', 'date', 'schedule'],
            ],
        ];
    }

    protected function visiblePayslipsQuery(): Builder
    {
        return Payslip::withoutGlobalScopes()
            ->with(['payRun', 'items.payItem', 'employee.department'])
            ->where('employee_id', $this->employee->id)
            ->where('company_id', $this->employee->company_id)
            ->whereIn('status', ['processed', 'paid'])
            ->whereHas('payRun', fn($q) => $q->whereIn('status', ['posted', 'paid']));
    }

    protected function appendItemBreakdown(array &$context, string $heading, Collection $items, string $totalLabel): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $context[] = $heading;
        $total = 0;
        foreach ($items as $item) {
            $context[] = "  - {$this->itemLabel($item)}: " . number_format($item->amount, 2);
            $total += $item->amount;
        }
        $context[] = "  {$totalLabel}: " . number_format($total, 2);
        $context[] = "";
    }

    protected function itemLabel($item): string
    {
        return $item->description ?: ($item->payItem->name ?? 'Payslip item');
    }

    protected function isTaxItem($item): bool
    {
        if (!$item->payItem) {
            return false;
        }

        $label = strtolower(trim($this->itemLabel($item)));

        return str_contains($label, 'tax')
            || str_contains($label, 'paye')
            || in_array($label, ['it', 'income tax'], true);
    }
}
