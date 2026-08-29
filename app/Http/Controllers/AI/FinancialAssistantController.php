<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\FinancialIntelligenceService;
use App\Services\AI\QueryIntentParser;
use App\Services\AI\FinancialAssistantService;
use App\Services\AI\FinancialQueryService;
use Illuminate\Http\Request;

class FinancialAssistantController extends Controller
{
    protected $intelligence;
    protected $parser;
    protected $aiService;
    protected $queryService;

    public function __construct(
        FinancialIntelligenceService $intelligence,
        QueryIntentParser $parser,
        FinancialAssistantService $aiService,
        FinancialQueryService $queryService
    ) {
        $this->intelligence = $intelligence;
        $this->parser = $parser;
        $this->aiService = $aiService;
        $this->queryService = $queryService;
    }

    public function index()
    {
        return view('pages.ai.assistant');
    }

    public function getContext()
    {
        $companyId = auth('web')->user()->company_id;
        $suggestions = $this->intelligence->generateSuggestions($companyId);

        return response()->json([
            'suggestions' => $suggestions,
            'summary' => [
                'revenue_ytd' => $this->intelligence->getRevenue(now()->startOfYear(), now(), $companyId),
                'labor_ytd' => $this->intelligence->getLaborCost(now()->startOfYear(), now(), $companyId),
            ]
        ]);
    }

    public function getSuggestions()
    {
        $companyId = auth('web')->user()->company_id;
        return response()->json([
            'suggestions' => $this->intelligence->generateSuggestions($companyId)
        ]);
    }

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'history' => 'nullable|array'
        ]);

        $query = $validated['query'];
        $history = $validated['history'] ?? [];
        $companyId = auth('web')->user()->company_id;

        // 0. Parse Intent
        $parsed = $this->parser->parse($query);

        // 1. Detect if this is a detailed data query (Text-to-SQL)
        $isDetailed = $this->shouldUseDetailedQuery($query, $parsed['intent']);

        if ($isDetailed) {
            $deterministic = $this->intelligence->answerDetailedQuery($query, $companyId);
            if ($deterministic) {
                $deterministic['suggestions'] = $this->intelligence->generateSuggestions($companyId);

                return response()->json($deterministic);
            }

            $sqlResult = $this->queryService->generateSql($query, $companyId);

            if (isset($sqlResult['sql'])) {
                $execution = $this->queryService->executeQuery($sqlResult['sql'], $companyId);
                if (isset($execution['success'])) {
                    $message = $sqlResult['explanation'];

                    if (($execution['count'] ?? 0) === 0) {
                        $message .= "\n\nNo matching records were found for this request.";
                    } else {
                        $summary = $this->aiService->summarizeQueryResult(
                            $query,
                            $sqlResult['explanation'],
                            $execution['data'],
                            $sqlResult['columns'] ?? []
                        );

                        if ($summary) {
                            $message = $summary;
                        }
                    }

                    return response()->json([
                        'message' => $message,
                        'type' => 'query_result',
                        'visualization' => $sqlResult['visualization'] ?? 'table',
                        'data' => $execution['data'],
                        'columns' => $sqlResult['columns'] ?? [],
                        'suggestions' => $this->intelligence->generateSuggestions($companyId)
                    ]);
                }
                return response()->json([
                    'message' => "I found the right data but had trouble executing the query: " . ($execution['error'] ?? 'Unknown error'),
                    'type' => 'error'
                ]);
            }

            return response()->json([
                'message' => "I understand you're looking for specific data, but I'm having trouble connecting to the AI analysis engine right now. Please try again in a moment.",
                'type' => 'error'
            ]);
        }

        // 2. Standard KPI Intent Processing
        $response = $this->processIntent($parsed, $companyId);

        if (($response['type'] ?? null) !== 'error') {
            $response['suggestions'] = $this->intelligence->generateSuggestions($companyId);

            return response()->json($response);
        }

        // 3. Build Context and call AI for conversational response
        $context = $this->intelligence->buildFullContext($companyId);
        $prompt = $this->aiService->buildGeminiPrompt($query, $context, $history);
        $aiMessage = $this->aiService->getAIResponse($prompt);

        if ($aiMessage) {
            $response['message'] = $aiMessage;
        }

        // Add fresh suggestions for the next turn
        $response['suggestions'] = $this->intelligence->generateSuggestions($companyId);

        return response()->json($response);
    }

    private function shouldUseDetailedQuery($query, $intent)
    {
        $detailedKeywords = [
            'show',
            'list',
            'filter',
            'over',
            'under',
            'unpaid',
            'paid',
            'specific',
            'details',
            'who',
            'how many',
            'count',
            'department',
            'pay run',
            'receivable',
            'payable',
            'vendor',
            'customer',
            'headcount',
            'trend',
            'compare',
            'over time',
            'transaction',
            'journal',
            'ledger',
            'account balance',
            'trial balance',
            'cash',
            'bank',
            'cogs',
            'cost of goods',
            'asset',
            'liability',
            'liabilities',
            'equity',
            'financial summary',
            'financial activity',
            'financial activities',
            'profit',
            'p&l',
            'profit and loss',
        ];
        $queryLower = strtolower($query);

        foreach ($detailedKeywords as $word) {
            if (str_contains($queryLower, $word)) return true;
        }

        return $intent === 'unknown';
    }

    private function getSystemPrompt()
    {
        return <<<PROMPT
            You are a Unified Financial Intelligence Assistant for an ERP system. 
            You have access to cross-module data combining Accounting (Revenue, Expenses) and Payroll (Labor Costs, Statutory Liabilities).
            
            Your goals:
            1. Provide deep strategic insights based on the context provided.
            2. Explain trends and ratios (like Labor % of Revenue) professionally.
            3. Use the currency symbols and formatting appropriate for financial reporting.
            4. If the user asks about growth or comparison, highlight the key drivers.
            5. Be concise but insightful. 
            
            Always use the CONTEXT data provided. Do not invent numbers.
            If data is missing, suggest what the user should check in the ERP.
        PROMPT;
    }

    private function processIntent($parsed, $companyId)
    {
        $start = $parsed['start_date'];
        $end = $parsed['end_date'];
        $label = $parsed['date_range_label'] ?? $parsed['date_label'];

        switch ($parsed['intent']) {
            case 'labor_cost_percentage_revenue':
                $labor = $this->intelligence->getLaborCost($start, $end, $companyId);
                $revenue = $this->intelligence->getRevenue($start, $end, $companyId);
                $percentage = $revenue > 0 ? ($labor / $revenue) * 100 : 0;

                return [
                    'message' => "For {$label}, our total labor cost was " . currency()?->symbol . " " . number_format($labor) . ", which represents **" . round($percentage, 1) . "%** of our " . currency()?->symbol . " " . number_format($revenue) . " revenue.",
                    'type' => 'ratio',
                    'data' => ['percentage' => $percentage, 'labor' => $labor, 'revenue' => $revenue]
                ];

            case 'labor_cost':
                if ($parsed['is_trend']) {
                    $trend = $this->intelligence->getGrowthTrend('labor_cost', $companyId);
                    return [
                        'message' => "Here is the labor cost trend over the past 12 months.",
                        'type' => 'chart',
                        'chart_type' => 'line',
                        'data' => $trend
                    ];
                }
                $cost = $this->intelligence->getLaborCost($start, $end, $companyId);
                return [
                    'message' => "Total labor cost for {$label} was " . currency()?->symbol . " " . number_format($cost) . ".",
                    'type' => 'stat',
                    'value' => $cost
                ];

            case 'revenue':
                if ($parsed['is_trend']) {
                    $trend = $this->intelligence->getGrowthTrend('revenue', $companyId);
                    return [
                        'message' => "Revenue growth trend over the past year.",
                        'type' => 'chart',
                        'chart_type' => 'bar',
                        'data' => $trend
                    ];
                }
                $rev = $this->intelligence->getRevenue($start, $end, $companyId);
                return [
                    'message' => "Total revenue for {$label} was " . currency()?->symbol . " " . number_format($rev) . ".",
                    'type' => 'stat',
                    'value' => $rev
                ];

            case 'statutory_liabilities':
                $stat = $this->intelligence->getStatutoryLiabilities($start, $end, $companyId);
                return [
                    'message' => "Total statutory deductions for {$label} amounted to " . currency()?->symbol . " " . number_format($stat) . ".",
                    'type' => 'stat',
                    'value' => $stat
                ];

            case 'expenses':
                if ($parsed['is_trend']) {
                    $trend = $this->intelligence->getGrowthTrend('expenses', $companyId);
                    return [
                        'message' => "Expense trend over the past year.",
                        'type' => 'chart',
                        'chart_type' => 'bar',
                        'data' => $trend
                    ];
                }
                $expenses = $this->intelligence->getExpenses($start, $end, $companyId);
                return [
                    'message' => "Total approved expenses for {$label} were " . currency()?->symbol . " " . number_format($expenses) . ".",
                    'type' => 'stat',
                    'value' => $expenses
                ];

            case 'cogs':
                $cogs = $this->intelligence->getCOGS($start, $end, $companyId);
                return [
                    'message' => "COGS for {$label} was " . currency()?->symbol . " " . number_format($cogs) . ".",
                    'type' => 'stat',
                    'value' => $cogs
                ];

            case 'receivables':
                $receivables = $this->intelligence->getReceivablesOutstanding($end, $companyId);
                return [
                    'message' => "Accounts receivable outstanding as of {$end} was " . currency()?->symbol . " " . number_format($receivables) . ".",
                    'type' => 'stat',
                    'value' => $receivables
                ];

            case 'payables':
                $payables = $this->intelligence->getPayablesOutstanding($end, $companyId);
                return [
                    'message' => "Accounts payable outstanding as of {$end} was " . currency()?->symbol . " " . number_format($payables) . ".",
                    'type' => 'stat',
                    'value' => $payables
                ];

            case 'cash':
                $cash = $this->intelligence->getCashBalance($end, $companyId);
                return [
                    'message' => "Cash/bank balance as of {$end} was " . currency()?->symbol . " " . number_format($cash) . ".",
                    'type' => 'stat',
                    'value' => $cash
                ];

            case 'assets':
                $assets = $this->intelligence->getAssetsBalance($end, $companyId);
                return [
                    'message' => "Total assets as of {$end} were " . currency()?->symbol . " " . number_format($assets) . ".",
                    'type' => 'stat',
                    'value' => $assets
                ];

            case 'liabilities':
                $liabilities = $this->intelligence->getLiabilitiesBalance($end, $companyId);
                return [
                    'message' => "Total liabilities as of {$end} were " . currency()?->symbol . " " . number_format($liabilities) . ".",
                    'type' => 'stat',
                    'value' => $liabilities
                ];

            case 'equity':
                $equity = $this->intelligence->getEquityBalance($end, $companyId);
                return [
                    'message' => "Total equity as of {$end} was " . currency()?->symbol . " " . number_format($equity) . ".",
                    'type' => 'stat',
                    'value' => $equity
                ];

            case 'financial_summary':
                return [
                    'message' => "Financial activity summary for {$label}.",
                    'type' => 'summary_card',
                    'data' => $this->intelligence->getFinancialActivitySummary($start, $end, $companyId)
                ];

            default:
                return [
                    'message' => "I'm sorry, I couldn't find a direct answer for that. Try asking about labor costs, revenue, or statutory deductions.",
                    'type' => 'error'
                ];
        }
    }
}
