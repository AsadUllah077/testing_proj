<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bank Rule — auto-learned payee → account mapping.
 *
 * When a user creates a transaction from a bank statement line and selects
 * a contra account, the system remembers that mapping so future lines
 * with the same payee auto-fill the account (Xero-style).
 */
class BankRule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'name',
        'payee',
        'match_type',
        'direction',
        'min_amount',
        'max_amount',
        'priority',
        'is_active',
        'contra_account_id',
        'usage_count',
        'accepted_count',
        'rejected_count',
        'last_used_at',
        'last_rejected_at',
        'tax_code',
        'tracking_category',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'min_amount' => 'decimal:4',
        'max_amount' => 'decimal:4',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'accepted_count' => 'integer',
        'rejected_count' => 'integer',
        'last_used_at' => 'datetime',
        'last_rejected_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * The contra account that was selected.
     */
    public function contraAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'contra_account_id');
    }

    /**
     * The bank account this rule applies to.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }

    /**
     * Scope: rules for a specific bank account.
     */
    public function scopeForBank($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    /**
     * Normalize payee text for consistent matching.
     * Lowercase, trim whitespace, collapse multiple spaces.
     */
    public static function normalizePayee(?string $payee): string
    {
        if (empty($payee)) {
            return '';
        }

        $payee = mb_strtolower(trim($payee));
        $payee = preg_replace('/\s+/', ' ', $payee);

        return $payee;
    }

    /**
     * Find a matching rule for the given payee and bank account.
     */
    public static function findRule(int $companyId, int $bankAccountId, ?string $payee): ?self
    {
        $normalized = self::normalizePayee($payee);

        if (empty($normalized)) {
            return null;
        }

        return static::where('company_id', $companyId)
            ->where('bank_account_id', $bankAccountId)
            ->where('payee', $normalized)
            ->first();
    }

    public function matchesStatementLine(BankStatementLine $line): bool
    {
        if (! $this->is_active || (int) $this->bank_account_id !== (int) $line->bank_account_id) {
            return false;
        }

        $amount = abs((float) $line->amount);
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) {
            return false;
        }
        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            return false;
        }
        if ($this->direction === 'money_in' && (float) $line->amount <= 0) {
            return false;
        }
        if ($this->direction === 'money_out' && (float) $line->amount >= 0) {
            return false;
        }

        $payee = self::normalizePayee($line->payee);
        $pattern = self::normalizePayee($this->payee);

        return match ($this->match_type) {
            'contains' => str_contains($payee, $pattern),
            'starts_with' => str_starts_with($payee, $pattern),
            default => $payee === $pattern,
        };
    }

    /**
     * Save or update a rule after a transaction is created.
     */
    public static function learnRule(int $companyId, int $bankAccountId, ?string $payee, int $contraAccountId): ?self
    {
        $normalized = self::normalizePayee($payee);

        if (empty($normalized)) {
            return null;
        }

        $rule = static::updateOrCreate(
            [
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'payee' => $normalized,
            ],
            [
                'contra_account_id' => $contraAccountId,
            ]
        );

        // Increment usage count (for confidence / sorting)
        $rule->increment('usage_count');
        $rule->increment('accepted_count');
        $rule->update(['last_used_at' => now()]);

        return $rule;
    }
}
