<?php

namespace App\Services\AI;

use App\Facades\Ai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialQueryService
{
    /**
     * Generate read-only SQL from natural language query.
     */
    public function generateSql(string $query, int $companyId): array
    {
        $schemaContext = $this->getSchemaContext();
        $prompt = Prompts::textToSql($query, $companyId, $schemaContext);
        
        try {
            $data = Ai::prompt($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 1024,
                'response_format' => ['type' => 'json_object'],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            // If it returns string (fallback for drivers that don't support JSON response type natively)
            if (is_string($data)) {
                $data = preg_replace('/```json\s*/', '', $data);
                $data = preg_replace('/```\s*/', '', $data);
                $data = json_decode(trim($data), true);
            }
            
            if (!$data || !isset($data['sql'])) {
                return ['error' => 'AI failed to generate a valid query.'];
            }

            $data['sql'] = $this->normalizeGeneratedSql($data['sql']);

            // Final safety check: ensure the SQL is scoped to the active company.
            if (!$this->passesSqlSafetyChecks($data['sql'], $companyId)) {
                Log::warning("AI generated SQL failed safety checks: " . $data['sql']);
                return ['error' => 'Security check failed: Generated query is not safely company-scoped.'];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Financial Query AI Error: ' . $e->getMessage());
        }

        return ['error' => 'Failed to generate query'];
    }

    /**
     * Execute the generated SQL safely.
     */
    public function executeQuery(string $sql, int $companyId): array
    {
        $sql = $this->normalizeGeneratedSql($sql);

        if (!$this->passesSqlSafetyChecks($sql, $companyId)) {
            return ['error' => 'Only safe, read-only, company-scoped SELECT queries are allowed.'];
        }

        try {
            $results = DB::connection()->select($sql);
            return [
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ];
        } catch (\Exception $e) {
            Log::error('SQL Execution Error: ' . $e->getMessage());
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get Schema Context for the Prompt.
     */
    protected function getSchemaContext(): string
    {
        return <<<'CONTEXT'
            - Table: bills (id, company_id, vendor_id, account_id, invoice_no, date, sub_total, tax, total, paid_amount, remaining_amount, status, approval_status)
            - Table: sales (id, company_id, customer_id, account_id, invoice_no, date, sub_total, tax, total, paid_amount, remaining_amount, status, approval_status)
            - Table: transactions (id, company_id, accounting_period_id, account_id, vendor_id, customer_id, reference_id, unique_no, account_label, invoice_no, date, type, description, debit, credit, payable_type, payable_id, status, deleted_at)
            - Table: chart_of_accounts (id, account_number, name, category, system_account_type, sub_type, type)
            - Table: vendors (id, company_id, name)
            - Table: customers (id, company_id, name)
            - Table: employees (id, company_id, full_name, designation, salary, department_id)
            - Table: departments (id, company_id, department)
            - Table: pay_runs (id, company_id, period_start, period_end, payment_date, status, approval_status)
            - Table: payslips (id, company_id, pay_run_id, employee_id, gross_earnings, total_deductions, net_pay, status)
            - Table: payslip_items (id, payslip_id, pay_item_id, amount, description)
            - Table: pay_items (id, company_id, name, type, account_id, statutory_body_id)
        CONTEXT;
    }

    protected function normalizeGeneratedSql(string $sql): string
    {
        return preg_replace('/;\s*\z/', '', trim($sql));
    }

    protected function passesSqlSafetyChecks(string $sql, int $companyId): bool
    {
        $trimmed = trim($sql);

        if (!preg_match('/\Aselect\b/i', $trimmed)) {
            return false;
        }

        if (preg_match('/(;|--|\/\*|\*\/)/', $trimmed)) {
            return false;
        }

        $forbidden = ['insert', 'update', 'delete', 'drop', 'truncate', 'alter', 'create', 'grant', 'revoke', 'replace', 'call', 'exec', 'union'];
        foreach ($forbidden as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $trimmed)) {
                return false;
            }
        }

        $companyPattern = '/(?:[`"\[]?\w+[`"\]]?\.)?[`"\[]?company_id[`"\]]?\s*=\s*[\'"]?' . preg_quote((string) $companyId, '/') . '[\'"]?/i';

        return (bool) preg_match($companyPattern, $trimmed);
    }
}
