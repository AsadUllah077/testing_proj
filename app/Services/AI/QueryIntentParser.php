<?php

namespace App\Services\AI;

use Carbon\Carbon;

class QueryIntentParser
{
    /**
     * Parse natural language into structured intent and date range.
     */
    public function parse($query)
    {
        $query = strtolower(trim($query));
        $intent = 'unknown';
        $params = [];
        
        // 1. Detect Intent
        if (str_contains($query, 'transaction') || str_contains($query, 'journal') || str_contains($query, 'ledger') || str_contains($query, 'profit') || str_contains($query, 'p&l')) {
            $intent = 'transactions';
        } elseif (str_contains($query, 'financial summary') || str_contains($query, 'financial activity') || str_contains($query, 'financial activities')) {
            $intent = 'financial_summary';
        } elseif (str_contains($query, 'labor cost') && str_contains($query, 'revenue')) {
            $intent = 'labor_cost_percentage_revenue';
        } elseif (str_contains($query, 'labor cost') || str_contains($query, 'payroll cost') || str_contains($query, 'payroll expense') || str_contains($query, 'payroll')) {
            $intent = 'labor_cost';
        } elseif (str_contains($query, 'revenue') || str_contains($query, 'income')) {
            $intent = 'revenue';
        } elseif (str_contains($query, 'cogs') || str_contains($query, 'cost of goods')) {
            $intent = 'cogs';
        } elseif (str_contains($query, 'receivable')) {
            $intent = 'receivables';
        } elseif (str_contains($query, 'payable')) {
            $intent = 'payables';
        } elseif (str_contains($query, 'cash') || str_contains($query, 'bank')) {
            $intent = 'cash';
        } elseif (str_contains($query, 'asset')) {
            $intent = 'assets';
        } elseif (str_contains($query, 'statutory') || str_contains($query, 'deduction')) {
            $intent = 'statutory_liabilities';
        } elseif (str_contains($query, 'liabilit')) {
            $intent = 'liabilities';
        } elseif (str_contains($query, 'equity')) {
            $intent = 'equity';
        } elseif (str_contains($query, 'expense') || str_contains($query, 'bill')) {
            $intent = 'expenses';
        }

        // 2. Detect Date Range
        $now = now();
        $startDate = $now->copy()->startOfMonth()->toDateString();
        $endDate = $now->copy()->endOfMonth()->toDateString();
        $label = "This Month";

        if (preg_match('/\b(today)\b/', $query)) {
            $startDate = $now->copy()->toDateString();
            $endDate = $now->copy()->toDateString();
            $label = "Today";
        } elseif (preg_match('/\b(yesterday)\b/', $query)) {
            $startDate = $now->copy()->subDay()->toDateString();
            $endDate = $now->copy()->subDay()->toDateString();
            $label = "Yesterday";
        } elseif (preg_match('/\b(this|current)\s+week\b/', $query)) {
            $startDate = $now->copy()->startOfWeek()->toDateString();
            $endDate = $now->copy()->endOfWeek()->toDateString();
            $label = "This Week";
        } elseif (preg_match('/\b(last|previous)\s+week\b/', $query)) {
            $startDate = $now->copy()->subWeek()->startOfWeek()->toDateString();
            $endDate = $now->copy()->subWeek()->endOfWeek()->toDateString();
            $label = "Last Week";
        } elseif (preg_match('/\b(this|current)\s+month\b/', $query)) {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
            $label = "This Month";
        } elseif (preg_match('/\b(last|past)\s+(\d+)\s+months?\b/', $query, $matches)) {
            $months = max(1, min((int) $matches[2], 60));
            $startDate = $now->copy()->subMonths($months - 1)->startOfMonth()->toDateString();
            $endDate = $now->copy()->toDateString();
            $label = "Last {$months} Months";
        } elseif (preg_match('/\b(previous)\s+(\d+)\s+months?\b/', $query, $matches)) {
            $months = max(1, min((int) $matches[2], 60));
            $startDate = $now->copy()->subMonths($months)->startOfMonth()->toDateString();
            $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
            $label = "Previous {$months} Complete Months";
        } elseif (str_contains($query, 'last month')) {
            $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
            $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
            $label = "Last Month";
        } elseif (preg_match('/\bq([1-4])(?:\s+(\d{4}))?\b/', $query, $matches)) {
            $quarter = (int) $matches[1];
            $year = isset($matches[2]) ? (int) $matches[2] : (int) $now->format('Y');
            $startDate = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfQuarter()->toDateString();
            $endDate = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->endOfQuarter()->toDateString();
            $label = "Q{$quarter} {$year}";
        } elseif (str_contains($query, 'this quarter') || str_contains($query, 'current quarter')) {
            $startDate = $now->copy()->startOfQuarter()->toDateString();
            $endDate = $now->copy()->endOfQuarter()->toDateString();
            $label = "This Quarter";
        } elseif (str_contains($query, 'last quarter') || str_contains($query, 'previous quarter')) {
            $startDate = $now->copy()->subQuarter()->startOfQuarter()->toDateString();
            $endDate = $now->copy()->subQuarter()->endOfQuarter()->toDateString();
            $label = "Last Quarter";
        } elseif (str_contains($query, 'this year') || str_contains($query, 'ytd') || str_contains($query, 'year to date') || str_contains($query, 'year-to-date')) {
            $startDate = $now->copy()->startOfYear()->toDateString();
            $endDate = $now->copy()->toDateString();
            $label = "Year to Date";
        } elseif (str_contains($query, 'last year')) {
            $startDate = $now->copy()->subYear()->startOfYear()->toDateString();
            $endDate = $now->copy()->subYear()->endOfYear()->toDateString();
            $label = "Last Year";
        } elseif (preg_match('/\b(20\d{2})\b/', $query, $matches)) {
            $year = (int) $matches[1];
            $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
            $endDate = Carbon::create($year, 12, 31)->endOfYear()->toDateString();
            $label = (string) $year;
        }

        // 3. Detect Trend Request
        $isTrend = str_contains($query, 'trend')
            || str_contains($query, 'compare')
            || str_contains($query, 'comparison')
            || str_contains($query, 'growth')
            || str_contains($query, 'change')
            || str_contains($query, 'over the past')
            || str_contains($query, 'month over month')
            || str_contains($query, 'mom');

        return [
            'intent' => $intent,
            'is_trend' => $isTrend,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date_label' => $label,
            'date_range_label' => "{$label} ({$startDate} to {$endDate})",
            'original_query' => $query
        ];
    }
}
