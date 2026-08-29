<?php

namespace Tests\Unit;

use App\Models\BankRule;
use App\Models\BankStatementLine;
use PHPUnit\Framework\TestCase;

class BankRuleMatchingTest extends TestCase
{
    public function test_contains_rule_respects_direction_and_amount_range(): void
    {
        $rule = new BankRule([
            'bank_account_id' => 10,
            'payee' => 'internet provider',
            'match_type' => 'contains',
            'direction' => 'money_out',
            'min_amount' => 5000,
            'max_amount' => 30000,
            'is_active' => true,
        ]);

        $matchingLine = new BankStatementLine([
            'bank_account_id' => 10,
            'payee' => 'Monthly INTERNET PROVIDER payment',
            'amount' => -12500,
        ]);
        $moneyInLine = new BankStatementLine([
            'bank_account_id' => 10,
            'payee' => 'Internet Provider refund',
            'amount' => 12500,
        ]);
        $largeLine = new BankStatementLine([
            'bank_account_id' => 10,
            'payee' => 'Internet Provider',
            'amount' => -45000,
        ]);

        $this->assertTrue($rule->matchesStatementLine($matchingLine));
        $this->assertFalse($rule->matchesStatementLine($moneyInLine));
        $this->assertFalse($rule->matchesStatementLine($largeLine));
    }

    public function test_exact_and_starts_with_rules_normalize_payee_text(): void
    {
        $exact = new BankRule([
            'bank_account_id' => 20,
            'payee' => 'acme limited',
            'match_type' => 'exact',
            'direction' => 'both',
            'is_active' => true,
        ]);
        $startsWith = new BankRule([
            'bank_account_id' => 20,
            'payee' => 'salary batch',
            'match_type' => 'starts_with',
            'direction' => 'both',
            'is_active' => true,
        ]);

        $this->assertTrue($exact->matchesStatementLine(new BankStatementLine([
            'bank_account_id' => 20,
            'payee' => '  ACME   LIMITED ',
            'amount' => 100,
        ])));
        $this->assertTrue($startsWith->matchesStatementLine(new BankStatementLine([
            'bank_account_id' => 20,
            'payee' => 'Salary Batch July 2026',
            'amount' => -100,
        ])));
    }
}
