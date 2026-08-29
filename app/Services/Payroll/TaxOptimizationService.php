<?php

namespace App\Services\Payroll;

use App\Models\AccountingPeriod;
use App\Models\Employee;
use App\Models\TaxBracket;
use App\Models\PayItem;
use App\Services\TaxCalculationService;
use Illuminate\Support\Collection;

class TaxOptimizationService
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Analyze an employee's pay structure and provide optimization suggestions.
     */
    public function analyze(Employee $employee, Collection $structures, string $fiscalYear = ''): array
    {
        if (!$fiscalYear) {
            $period = AccountingPeriod::findPeriodForDate(now(), $employee->company_id);
            if ($period) {
                $fiscalYear = $period->name;
            } else {
                $year = now()->year;
                $fiscalYear = ($year - 1) . '-' . $year;
            }
        }

        $monthlyTaxable = 0;
        foreach ($structures as $s) {
            $type = strtolower($s->payItem->type ?? '');
            if (($type === 'earning' || $type === 'reimbursement') && ($s->payItem->is_taxable ?? true)) {
                $monthlyTaxable += $s->amount;
            }
        }

        $projection = $this->taxService->getTaxBreakdown($employee->company_id, $monthlyTaxable, $fiscalYear);

        // Find current and next bracket
        $annualGross = $monthlyTaxable * 12;
        $brackets = TaxBracket::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $employee->company_id)
            ->where('fiscal_year', 'like', "%{$fiscalYear}%")
            ->orderBy('sort_order')
            ->get();

        $currentBracket = null;
        $nextBracket = null;
        foreach ($brackets as $b) {
            $max = $b->max_income ?? PHP_INT_MAX;
            if ($annualGross > $b->min_income && $annualGross <= $max) {
                $currentBracket = $b;
            } elseif ($annualGross <= $b->min_income && !$nextBracket) {
                $nextBracket = $b;
            }
        }

        $marginalRate = $currentBracket ? $currentBracket->rate : 0;
        $headroom = $nextBracket ? $nextBracket->min_income - $annualGross : 0;

        $taxItemId = PayItem::where('company_id', $employee->company_id)
            ->where('type', 'deduction')
            ->whereRaw('LOWER(name) LIKE ?', ['%income tax%'])
            ->value('id');

        $overtimeItemId = PayItem::where('company_id', $employee->company_id)
            ->where('type', 'earning')
            ->whereRaw('LOWER(name) LIKE ?', ['%overtime%'])
            ->value('id');

        $insights = [
            'tax_item_id' => $taxItemId,
            'overtime_item_id' => $overtimeItemId,
            'projection' => array_merge($projection, [
                'marginal_rate' => $marginalRate,
                'current_bracket_label' => $currentBracket ? "{$currentBracket->rate}% (Slab #{$currentBracket->sort_order})" : 'Tax Exempt',
                'headroom' => $headroom,
                'next_slab_at' => $nextBracket ? $nextBracket->min_income : null,
            ]),
            'suggestions' => $this->generateSuggestions($employee, $structures, $annualGross, $marginalRate, $headroom)
        ];

        return $insights;
    }

    protected function generateSuggestions(Employee $employee, Collection $structures, float $annualGross, float $marginalRate, float $headroom): array
    {
        $suggestions = [];

        // 1. Marginal Rate Warning
        if ($headroom > 0 && $headroom < ($annualGross * 0.05)) {
            $suggestions[] = [
                'type' => 'marginal_rate',
                'priority' => 'medium',
                'title' => 'Near Next Tax Slab',
                'description' => "This employee is within 5% of the next tax slab. A small increase or bonus of " . number_format($headroom, 2) . " will push them into the {$marginalRate}%+ bracket.",
                'potential_saving' => null
            ];
        }

        // 2. Tax Exempt Benefits (Medical, etc.) - Heuristic based on names
        $hasMedical = $structures->contains(fn($s) => stripos($s->payItem->name, 'medical') !== false);
        if (!$hasMedical && $marginalRate > 0) {
            $suggestions[] = [
                'type' => 'benefit',
                'priority' => 'high',
                'title' => 'Missing Medical Allowance',
                'description' => 'Medical allowance (up to 10% of basic) is often tax-exempt in many jurisdictions (including FBR rules). Consider re-allocating a portion of basic pay to Medical.',
                'potential_saving' => round(($annualGross * 0.05) * ($marginalRate / 100), 2)
            ];
        }

        // 3. Conveyance/Fuel
        $hasFuel = $structures->contains(fn($s) => stripos($s->payItem->name, 'fuel') !== false || stripos($s->payItem->name, 'conveyance') !== false);
        if (!$hasFuel && $marginalRate > 0) {
            $suggestions[] = [
                'type' => 'benefit',
                'priority' => 'medium',
                'title' => 'Conveyance Optimization',
                'description' => 'Re-allocating salary towards Fuel/Conveyance allowances can reduce the taxable base depending on local tax rules.',
                'potential_saving' => null
            ];
        }

        // 4. House Rent Split
        $hasHRA = $structures->contains(fn($s) => stripos($s->payItem->name, 'rent') !== false || stripos($s->payItem->name, 'house') !== false);
        if (!$hasHRA && $marginalRate > 0) {
            $suggestions[] = [
                'type' => 'benefit',
                'priority' => 'medium',
                'title' => 'House Rent Allowance (HRA)',
                'description' => 'Separate HRA from basic pay can sometimes offer better tax treatment depending on the jurisdiction.',
                'potential_saving' => null
            ];
        }

        // 5. Bonus timing suggestion
        if ($headroom > 0 && $headroom < 50000) {
            $suggestions[] = [
                'type' => 'timing',
                'priority' => 'low',
                'title' => 'Bonus Timing Advice',
                'description' => 'If a bonus is planned, consider paying it in the next fiscal year to avoid jumping to a higher tax slab this year.',
                'potential_saving' => null
            ];
        }

        return $suggestions;
    }
}
