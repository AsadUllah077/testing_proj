<?php

namespace App\Services;

use App\Models\StatutoryBody;
use App\Models\ComplianceEvent;
use App\Models\ComplianceAlert;
use App\Models\PayItem;
use App\Models\PayslipItem;
use App\Models\TaxBracket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StatutoryComplianceService
{
    /**
     * Generate compliance events for payroll liabilities.
     */
    public function generatePayrollComplianceEvents($companyId)
    {
        $events = [];

        // Get unpaid statutory deductions from finalized pay runs
        // We include 'draft' for better visibility, but focus on 'paid' and 'posted'
        $unpaidItems = PayslipItem::with(['payItem.statutoryBody', 'payslip.payRun'])
            ->whereHas('payslip', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('payItem', fn($q) => $q->where('type', 'deduction'))
            ->whereHas('payItem.statutoryBody')
            ->whereNull('paid_at')
            ->whereHas('payslip.payRun', fn($q) => $q->whereIn('status', ['posted', 'paid']))
            ->get();

        // Group by Statutory Body and Period End to create distinct events for each filing period
        foreach ($unpaidItems->groupBy(fn($item) => $item->payItem->statutory_body_id . '_' . $item->payslip->payRun->period_end->format('Y-m')) as $groupKey => $items) {
            $payItem = $items->first()->payItem;
            $statutoryBody = $payItem->statutoryBody;
            $periodEnd = $items->first()->payslip->payRun->period_end;

            if (!$statutoryBody) continue;

            $totalAmount = $items->sum(fn ($item) => $item->outstanding_amount);
            $dueDate = $this->calculateStatutoryDueDate($items->first());

            // Check if event already exists for this body and period
            $existingEvent = ComplianceEvent::where('company_id', $companyId)
                ->where('statutory_body_id', $statutoryBody->id)
                ->where('event_type', 'payment')
                ->where('due_date', $dueDate->toDateString())
                ->first();

            if (!$existingEvent) {
                $events[] = ComplianceEvent::create([
                    'company_id' => $companyId,
                    'statutory_body_id' => $statutoryBody->id,
                    'event_type' => 'payment',
                    'title' => "Pay {$statutoryBody->name}",
                    'description' => "Statutory remittance for period ending {$periodEnd->format('M Y')}",
                    'due_date' => $dueDate,
                    'amount_due' => $totalAmount,
                    'metadata' => [
                        'pay_item_id' => $payItem->id,
                        'pay_item_ids' => $items->pluck('pay_item_id')->unique()->values()->all(),
                        'item_count' => $items->count(),
                        'period_end' => $periodEnd->format('Y-m-d'),
                    ],
                ]);
            } else {
                // Update existing event amount if more items were added to the same period
                $existingEvent->update(['amount_due' => $totalAmount]);
            }
        }

        return $events;
    }

    /**
     * Generate compliance events for tax filings.
     */
    public function generateTaxComplianceEvents($companyId)
    {
        $events = [];

        // Fetch active statutory bodies for this company
        $bodies = StatutoryBody::where('company_id', $companyId)->active()->get();

        foreach ($bodies as $body) {
            // For each body, check if there are recurring filing obligations
            // This is a simplified model - in a real app, this would be based on body-specific rules
            $currentYear = now()->year;

            // Quarterly Filing Example (e.g., Income Tax Returns or GST/VAT)
            $quarters = [
                ['start' => "{$currentYear}-01-01", 'end' => "{$currentYear}-03-31", 'due' => "{$currentYear}-04-15"],
                ['start' => "{$currentYear}-04-01", 'end' => "{$currentYear}-06-30", 'due' => "{$currentYear}-07-15"],
                ['start' => "{$currentYear}-07-01", 'end' => "{$currentYear}-09-30", 'due' => "{$currentYear}-10-15"],
                ['start' => "{$currentYear}-10-01", 'end' => "{$currentYear}-12-31", 'due' => ($currentYear + 1) . "-01-15"],
            ];

            foreach ($quarters as $quarter) {
                $existingEvent = ComplianceEvent::where('company_id', $companyId)
                    ->where('statutory_body_id', $body->id)
                    ->where('event_type', 'filing')
                    ->where('period_start', $quarter['start'])
                    ->first();

                // Only create if due date is coming up or past (within reasonable limits)
                if (!$existingEvent && Carbon::parse($quarter['due'])->isBefore(now()->addMonths(3))) {
                    $events[] = ComplianceEvent::create([
                        'company_id' => $companyId,
                        'statutory_body_id' => $body->id,
                        'event_type' => 'filing',
                        'title' => "Quarterly Filing - {$body->code}",
                        'description' => "Periodic regulatory filing for {$body->name}",
                        'due_date' => $quarter['due'],
                        'period_start' => $quarter['start'],
                        'period_end' => $quarter['end'],
                        'status' => Carbon::parse($quarter['due'])->isPast() ? 'overdue' : 'pending',
                        'amount_due' => 0, // Filings might not have fixed amounts
                    ]);
                }
            }
        }

        return $events;
    }

    /**
     * Check for regulatory changes and create alerts.
     */
    public function checkRegulatoryChanges($companyId)
    {
        $alerts = [];

        // Check for tax bracket updates
        $latestBrackets = $this->fetchLatestTaxBrackets();
        if ($latestBrackets) {
            $currentFiscalYear = $this->getCurrentFiscalYear();
            $existingBrackets = TaxBracket::forYear($currentFiscalYear)->count();

            if ($existingBrackets == 0) {
                $alerts[] = ComplianceAlert::create([
                    'company_id' => $companyId,
                    'alert_type' => 'regulatory_change',
                    'title' => 'Tax Brackets Available for Update',
                    'message' => "New tax brackets are available for fiscal year {$currentFiscalYear}. Please review and update your tax calculations.",
                    'severity' => 'warning',
                    'metadata' => [
                        'fiscal_year' => $currentFiscalYear,
                        'bracket_count' => count($latestBrackets),
                    ],
                ]);
            }
        }

        // Check for upcoming deadlines
        $upcomingEvents = ComplianceEvent::where('company_id', $companyId)
            ->pending()
            ->dueSoon(7)
            ->get();

        foreach ($upcomingEvents as $event) {
            $existingAlert = ComplianceAlert::where('company_id', $companyId)
                ->where('compliance_event_id', $event->id)
                ->where('alert_type', 'deadline_reminder')
                ->active()
                ->first();

            if (!$existingAlert) {
                $daysUntilDue = now()->diffInDays($event->due_date);
                $alerts[] = ComplianceAlert::create([
                    'company_id' => $companyId,
                    'compliance_event_id' => $event->id,
                    'alert_type' => 'deadline_reminder',
                    'title' => "Compliance Deadline Approaching",
                    'message' => "{$event->title} is due in {$daysUntilDue} days ({$event->due_date->format('M j, Y')})",
                    'severity' => $daysUntilDue <= 3 ? 'error' : 'warning',
                    'expires_at' => $event->due_date,
                ]);
            }
        }

        // Check for overdue items
        $overdueEvents = ComplianceEvent::where('company_id', $companyId)
            ->overdue()
            ->get();

        foreach ($overdueEvents as $event) {
            $penalty = $event->calculatePenalty();

            $existingAlert = ComplianceAlert::where('company_id', $companyId)
                ->where('compliance_event_id', $event->id)
                ->where('alert_type', 'penalty_warning')
                ->active()
                ->first();

            if (!$existingAlert) {
                $alerts[] = ComplianceAlert::create([
                    'company_id' => $companyId,
                    'compliance_event_id' => $event->id,
                    'alert_type' => 'penalty_warning',
                    'title' => "Overdue Compliance - Penalty Risk",
                    'message' => "{$event->title} is overdue. Potential penalty: " . number_format($penalty, 2),
                    'severity' => 'critical',
                    'metadata' => [
                        'days_overdue' => now()->diffInDays($event->due_date),
                        'penalty_amount' => $penalty,
                    ],
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Update tax brackets from external source.
     */
    public function updateTaxBrackets($fiscalYear, $brackets)
    {
        foreach ($brackets as $bracket) {
            TaxBracket::updateOrCreate(
                [
                    'fiscal_year' => $fiscalYear,
                    'min_income' => $bracket['min_income'],
                ],
                [
                    'max_income' => $bracket['max_income'] ?? null,
                    'base_tax' => $bracket['base_tax'],
                    'rate' => $bracket['rate'],
                    'sort_order' => $bracket['sort_order'] ?? 0,
                ]
            );
        }

        Log::info("Tax brackets updated for fiscal year {$fiscalYear}", ['count' => count($brackets)]);
    }

    /**
     * Get compliance dashboard data.
     */
    public function getComplianceDashboard($companyId)
    {
        $stats = [
            'total_events' => ComplianceEvent::where('company_id', $companyId)->count(),
            'pending_events' => ComplianceEvent::where('company_id', $companyId)->pending()->count(),
            'overdue_events' => ComplianceEvent::where('company_id', $companyId)->overdue()->count(),
            'due_soon_events' => ComplianceEvent::where('company_id', $companyId)->pending()
                ->where('due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
            'active_alerts' => ComplianceAlert::where('company_id', $companyId)->active()->count(),
            'critical_alerts' => ComplianceAlert::where('company_id', $companyId)->active()->bySeverity('critical')->count(),
            'total_amount_due' => (float) ComplianceEvent::where('company_id', $companyId)->pending()->sum('amount_due'),
        ];

        // Generate critical alerts for overdue items if not already alerted
        $this->generateOverdueAlerts($companyId);

        $upcomingDeadlines = ComplianceEvent::with('statutoryBody')
            ->where('company_id', $companyId)
            ->pending()
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        $recentAlerts = ComplianceAlert::with(['complianceEvent.statutoryBody', 'acknowledgedBy'])
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $penaltyExposure = (float) ComplianceEvent::where('company_id', $companyId)
            ->overdue()
            ->get()
            ->sum(function ($event) {
                return $event->calculatePenalty();
            });

        $liabilityBreakdown = ComplianceEvent::with('statutoryBody')
            ->where('company_id', $companyId)
            ->pending()
            ->get()
            ->groupBy('statutory_body_id')
            ->map(function ($events) {
                $body = $events->first()->statutoryBody;
                return [
                    'id' => $body->id ?? 0,
                    'name' => $body->name ?? 'Unknown',
                    'code' => $body->code ?? '??',
                    'amount' => (float) $events->sum('amount_due'),
                    'overdue' => (float) $events->filter->isOverdue()->sum('amount_due'),
                ];
            })->values();

        return [
            'stats' => $stats,
            'upcoming_deadlines' => $upcomingDeadlines,
            'recent_alerts' => $recentAlerts,
            'penalty_exposure' => $penaltyExposure,
            'liability_breakdown' => $liabilityBreakdown,
            'health_score' => $this->calculateComplianceHealthScore($companyId),
        ];
    }

    /**
     * Calculate a compliance health score (0-100).
     */
    private function calculateComplianceHealthScore($companyId)
    {
        $total = ComplianceEvent::where('company_id', $companyId)->count();
        if ($total === 0) return 100;

        $overdue = ComplianceEvent::where('company_id', $companyId)->overdue()->count();

        // Weight: Overdue is heavy penalty
        $score = 100 - (($overdue / $total) * 100);

        return max(0, min(100, round($score)));
    }

    /**
     * Generate alerts for overdue compliance events.
     */
    private function generateOverdueAlerts($companyId)
    {
        $overdueEvents = ComplianceEvent::where('company_id', $companyId)->overdue()->get();

        foreach ($overdueEvents as $event) {
            $existingAlert = ComplianceAlert::where('compliance_event_id', $event->id)
                ->where('alert_type', 'overdue')
                ->first();

            if (!$existingAlert) {
                ComplianceAlert::create([
                    'company_id' => $companyId,
                    'compliance_event_id' => $event->id,
                    'alert_type' => 'overdue',
                    'title' => "Critical Overdue: {$event->title}",
                    'message' => "Payment for {$event->statutoryBody->name} was due on " . $event->due_date->format('d M Y') . ". Potential penalty exposure active.",
                    'severity' => 'critical',
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * Calculate statutory due date for a payslip item.
     */
    private function calculateStatutoryDueDate($payslipItem)
    {
        $periodEnd = $payslipItem->payslip->payRun->period_end;
        $statutoryBody = $payslipItem->payItem->statutoryBody;

        $graceDays = $statutoryBody ? $statutoryBody->default_grace_days : config('accounting.payroll_statutory_grace_days', 15);

        return $periodEnd->copy()->addDays($graceDays);
    }

    /**
     * Fetch latest tax brackets from external source.
     * This is a placeholder - would integrate with actual regulatory APIs.
     */
    private function fetchLatestTaxBrackets()
    {
        // Placeholder implementation
        // In real implementation, this would call external APIs or web scraping
        return null;
    }

    /**
     * Get current fiscal year.
     */
    private function getCurrentFiscalYear()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Assuming fiscal year starts in January
        return $currentMonth >= 1 ? "{$currentYear}-" . ($currentYear + 1) : ($currentYear - 1) . "-{$currentYear}";
    }
}
