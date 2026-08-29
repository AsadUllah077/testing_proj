<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\TaxBracket;
use Carbon\Carbon;

class TaxCalculationService
{
    protected array $fiscalYearCache = [];
    protected array $bracketsCache = [];

    /**
     * Calculate monthly income tax using FBR progressive slabs.
     *
     * @param  int    $companyId
     * @param  float  $monthlyGross  Monthly gross salary
     * @param  string $fiscalYear    e.g. "2024-2025"
     * @return float  Monthly tax amount
     */
    public function calculateMonthlyTax(int $companyId, float $monthlyGross, string $fiscalYear = ''): float
    {
        $fiscalYear = $this->resolveFiscalYear($companyId, $fiscalYear);

        $annualGross = $monthlyGross * 12;

        $brackets = $this->taxBracketsFor($companyId, $fiscalYear);

        if ($brackets->isEmpty()) {
            return 0.0;
        }

        $annualTax = 0.0;

        foreach ($brackets as $bracket) {
            $max = $bracket->max_income ?? PHP_INT_MAX;

            if ($annualGross > $bracket->min_income) {
                $taxable    = min($annualGross, $max) - $bracket->min_income;
                $annualTax  = $bracket->base_tax + ($taxable * $bracket->rate / 100);
                // Don't break — last matching slab wins (slabs are exclusive ranges)
            }
        }

        return round($annualTax / 12, 2);
    }

    /**
     * Get a breakdown of annual tax by slab.
     */
    public function getTaxBreakdown(int $companyId, float $monthlyGross, string $fiscalYear = ''): array
    {
        $fiscalYear = $this->resolveFiscalYear($companyId, $fiscalYear);

        $annualGross = $monthlyGross * 12;
        $monthlyTax  = $this->calculateMonthlyTax($companyId, $monthlyGross, $fiscalYear);

        return [
            'annual_gross'   => $annualGross,
            'monthly_gross'  => $monthlyGross,
            'annual_tax'     => $monthlyTax * 12,
            'monthly_tax'    => $monthlyTax,
            'effective_rate' => $annualGross > 0 ? round(($monthlyTax * 12 / $annualGross) * 100, 2) : 0,
            'fiscal_year'    => $fiscalYear,
        ];
    }

    public function calculateMonthlyTaxForDate(int $companyId, float $monthlyGross, $payrollDate): float
    {
        return $this->calculateMonthlyTax(
            $companyId,
            $monthlyGross,
            $this->fiscalYearForDate($companyId, $payrollDate)
        );
    }

    public function auditSnapshotForDate(int $companyId, float $monthlyGross, $payrollDate): array
    {
        $fiscalYear = $this->fiscalYearForDate($companyId, $payrollDate);
        $brackets = $this->taxBracketsFor($companyId, $fiscalYear);

        return [
            'fiscal_year' => $fiscalYear,
            'monthly_taxable_earnings' => round($monthlyGross, 2),
            'monthly_income_tax' => $this->calculateMonthlyTax($companyId, $monthlyGross, $fiscalYear),
            'bracket_ids' => $brackets->pluck('id')->values()->all(),
            'bracket_version' => $brackets->max('updated_at')
                ? Carbon::parse($brackets->max('updated_at'))->toIso8601String()
                : null,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    public function fiscalYearForDate(int $companyId, $payrollDate): string
    {
        $date = Carbon::parse($payrollDate);
        $cacheKey = $companyId . ':payroll:' . $date->toDateString();
        if (isset($this->fiscalYearCache[$cacheKey])) {
            return $this->fiscalYearCache[$cacheKey];
        }

        $period = AccountingPeriod::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first(['start_date', 'end_date']);

        if (! $period) {
            throw new \RuntimeException(
                "No accounting period found for company ID {$companyId} and payroll date {$date->toDateString()}."
            );
        }

        $startYear = Carbon::parse($period->start_date)->year;
        $endYear = Carbon::parse($period->end_date)->year;

        return $this->fiscalYearCache[$cacheKey] = $startYear.'-'.$endYear;
    }

    protected function resolveFiscalYear(int $companyId, string $fiscalYear): string
    {
        if ($fiscalYear) {
            return $fiscalYear;
        }

        $cacheKey = $companyId . ':' . now()->toDateString();
        if (isset($this->fiscalYearCache[$cacheKey])) {
            return $this->fiscalYearCache[$cacheKey];
        }

        $period = AccountingPeriod::findPeriodForDate(now()->toDateTimeString(), $companyId);
        if ($period) {
            return $this->fiscalYearCache[$cacheKey] = $period->name;
        }

        $year = now()->year;
        return $this->fiscalYearCache[$cacheKey] = ($year - 1) . '-' . $year;
    }

    protected function taxBracketsFor(int $companyId, string $fiscalYear)
    {
        $cacheKey = $companyId . ':' . $fiscalYear;

        if (!isset($this->bracketsCache[$cacheKey])) {
            $this->bracketsCache[$cacheKey] = TaxBracket::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('company_id', $companyId)
                ->where('fiscal_year', 'like', "%{$fiscalYear}%")
                ->orderBy('sort_order')
                ->get();
        }

        return $this->bracketsCache[$cacheKey];
    }
}
