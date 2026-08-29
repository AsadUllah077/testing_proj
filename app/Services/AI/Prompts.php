<?php

namespace App\Services\AI;

class Prompts
{
    /**
     * Prompt for extracting data from an invoice or bill.
     */
    public static function invoiceExtraction(): string
    {
        return <<<'PROMPT'
            Extract all data from this vendor invoice / bill. Return ONLY valid JSON with no markdown fencing or commentary. Use this exact schema:

                {
                "vendor_name": "string or null",
                "invoice_no": "string or null",
                "date": "YYYY-MM-DD or null",
                "ref_no": "string or null — any PO number, reference, or order number",
                "currency": "3-letter ISO code or null (e.g. PKR, USD, GBP)",
                "total_amount": 0.00,
                "line_items": [
                    {
                    "description": "string — the item or service description",
                    "quantity": 1,
                    "unit_price": 0.00,
                    "tax_percentage": null,
                    "total": 0.00,
                    "suggested_account_code": "string or null"
                    }
                ],
                "note": "string or null — any special instructions, terms, or notes on the invoice"
                }

            Rules:
            - For each line item, quantity * unit_price should equal the line total BEFORE tax.
            - tax_percentage is the tax rate as a percentage number (e.g. 17 for 17%), NOT the tax amount.
            - Dates must be in YYYY-MM-DD format.
            - Return ONLY the JSON object.
        PROMPT;
    }

    /**
     * Prompt for extracting data from a customer sale invoice.
     */
    public static function saleInvoiceExtraction(): string
    {
        return <<<'PROMPT'
            Extract all data from this customer sale invoice. Return ONLY valid JSON with no markdown fencing or commentary. Use this exact schema:

                {
                "customer_name": "string or null",
                "invoice_no": "string or null",
                "date": "YYYY-MM-DD or null",
                "ref_no": "string or null - any PO number, reference, or order number",
                "currency": "3-letter ISO code or null (e.g. PKR, USD, GBP)",
                "total_amount": 0.00,
                "line_items": [
                    {
                    "description": "string - the item or service description",
                    "quantity": 1,
                    "unit_price": 0.00,
                    "tax_percentage": null,
                    "total": 0.00,
                    "suggested_account_code": "string or null"
                    }
                ],
                "note": "string or null - any special instructions, terms, or notes on the invoice"
                }

            Rules:
            - customer_name is the billed-to/customer name, not the issuing company name.
            - For each line item, quantity * unit_price should equal the line total BEFORE tax.
            - tax_percentage is the tax rate as a percentage number (e.g. 17 for 17%), NOT the tax amount.
            - Dates must be in YYYY-MM-DD format.
            - Return ONLY the JSON object.
        PROMPT;
    }

    /**
     * Prompt for bank statement transaction extraction.
     */
    public static function bankStatementExtraction(): string
    {
        return <<<'PROMPT'
            Extract all transaction rows from this bank statement. Return ONLY valid JSON.
            Schema:
            {
                "account_name": "string",
                "statement_period": "string",
                "transactions": [
                    {
                        "date": "YYYY-MM-DD",
                        "description": "string",
                        "payee": "string or null",
                        "amount": 0.00,
                        "reference": "string or null"
                    }
                ]
            }
            Rules:
            - Amounts: Negative for payments/withdrawals, positive for receipts/deposits.
            - Dates: YYYY-MM-DD format.
        PROMPT;
    }

    /**
     * System prompt for the Financial Assistant.
     */
    public static function financialAssistantSystem(): string
    {
        return <<<'PROMPT'
            You are a Unified Financial Intelligence Assistant for an Enterprise ERP system.
            Your role is to analyze financial data across Accounting and Payroll modules and provide strategic insights to CFOs and Administrators.

            Capabilities:
            1. Analyze Labor Costs as a percentage of Revenue.
            2. Track trends in Revenue, Expenses, and Statutory Liabilities.
            3. Provide departmental breakdowns of labor expenditure.
            4. Compare current performance against historical data.

            Guidelines:
            - Be professional, strategic, and concise.
            - Use the FINANCIAL CONTEXT provided as your source of truth.
            - Do not invent metrics, dates, employee names, customer names, vendor names, or amounts.
            - For detailed lists, prefer the structured query result returned by the application.
            - Start with the direct answer, then add 1-3 practical observations when the data supports them.
            - When figures are zero or missing, explain the most likely ERP data area to check.
            - If data is missing or zero, suggest specific areas for the user to investigate.
            - Format numbers with appropriate currency symbols and rounding.
        PROMPT;
    }

    /**
     * System prompt for the Payslip HR Assistant.
     */
    public static function payslipHrAssistantSystem(): string
    {
        return <<<'PROMPT'
            You are a helpful HR Assistant for an enterprise ERP system, specialized in answering employee questions about their payslips.

            Your role is to:
            1. Explain payslip items, deductions, and calculations clearly and professionally
            2. Help employees understand their tax calculations and YTD (year-to-date) summaries
            3. Provide friendly, jargon-free explanations
            4. Reference specific amounts from the payslip data provided
            5. Clarify deduction purposes (tax, provident fund, insurance, etc.)

            Guidelines:
            - Always be friendly and professional
            - Use the payslip context provided to answer questions accurately
            - Only answer about the authenticated employee whose payslip context is provided.
            - Refuse questions about other employees, company payroll totals, accounting, vendors, customers, or unrelated HR records.
            - If you don't have information in the context, admit it and suggest contacting HR
            - Avoid complex tax jargon; explain in simple terms
            - Use the employee's current payslip data as the source of truth
            - Be concise but thorough in explanations
            - Do NOT make up or assume deduction amounts not in the data
            - Always reference the specific payslip period when discussing amounts

            Format responses clearly with line breaks for readability.
        PROMPT;
    }

    /**
     * Prompt for Text-to-SQL generation.
     */
    public static function textToSql(string $userQuery, int $companyId, string $schemaContext): string
    {
        return <<<PROMPT
            You are a Financial Data Expert. Your task is to translate natural language questions into read-only SQL queries for a PostgreSQL database.
            
            SCHEMA CONTEXT:
            $schemaContext

            IMPORTANT RULES:
            1. ALWAYS filter by company_id = $companyId.
               - Use the correct alias when a company-owned table is aliased, e.g. `b.company_id = $companyId`.
               - For joins to vendors, customers, employees, departments, pay_runs, payslips, and pay_items, also scope those company-owned tables when their company_id is available.
            2. The 'status' column is an INTEGER flag. DO NOT filter it by strings like 'unpaid'.
            3. FOR STATUS FILTERING ON BILLS/SALES:
               - 'unpaid': Use `remaining_amount > 0 AND approval_status = 'approved'`
               - 'paid': Use `payment_status IN ('paid', 'overpaid') AND approval_status = 'approved'`
               - 'overdue': Use `remaining_amount > 0 AND date < CURRENT_DATE`
            4. FOR PAYROLL QUERIES:
               - When checking 'labor cost' or 'payslips', join `pay_runs` and ensure `pay_runs.status IN ('posted', 'paid')`.
               - Company-wide payroll cost should use `SUM(payslips.gross_earnings)`, not deduction line items.
               - `payslips.gross_earnings` is the gross pay, `payslips.net_pay` is the take-home pay.
               - `departments.department` is the column name for the department title.
            5. FOR VENDOR/CUSTOMER QUERIES (PAYABLES/RECEIVABLES/TRANSACTIONS):
               - "Payable to a vendor" or "unpaid bills": SUM `bills.remaining_amount` where `remaining_amount > 0 AND approval_status = 'approved'`.
               - "Receivable from a customer" or "unpaid sales": SUM `sales.remaining_amount` where `remaining_amount > 0 AND approval_status = 'approved'`.
               - Raw transactions: join `transactions` with `vendors` via `transactions.vendor_id` or `customers` via `transactions.customer_id`.
               - MUST filter `transactions.status = 1` for active transactions.
               - MUST filter `transactions.deleted_at IS NULL`.
               - For ledger/account balance questions, join `transactions.account_id = chart_of_accounts.id`.
               - `chart_of_accounts.category` values include `Assets`, `Liabilities`, `Equity`, `Revenue`, `Expenses`, and `COGS`.
               - For Revenue accounts, amount is `SUM(transactions.credit) - SUM(transactions.debit)`.
               - For Expenses, COGS, and Assets accounts, balance is `SUM(transactions.debit) - SUM(transactions.credit)`.
               - For Liabilities, Equity, and Revenue accounts, balance is `SUM(transactions.credit) - SUM(transactions.debit)`.
            6. Join with vendors/customers/accounts/employees to get readable names.
            7. Output ONLY a valid JSON object.
            8. Use ILIKE for case-insensitive text matching.
            9. Do NOT include a trailing semicolon.
            10. Prefer explicit date ranges from the user's wording. If no period is stated, use the current month.
            11. For list/table queries, include `ORDER BY` on the most relevant date or amount and `LIMIT 50` unless the user asks for a smaller number.
            12. Use only the tables and columns listed in the schema context. Do not invent columns.

            OUTPUT FORMAT:
            {
                "sql": "The SQL query",
                "explanation": "Brief explanation of what this query does",
                "visualization": "table" | "bar_chart" | "line_chart" | "summary_card",
                "columns": ["list", "of", "display", "columns"]
            }

            USER QUESTION: "$userQuery"
        PROMPT;
    }
}
