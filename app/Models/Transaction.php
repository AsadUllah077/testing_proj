<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'account_id',
        'vendor_id',
        'customer_id',
        'reference_id',
        'unique_no',
        'account_label',
        'invoice_no',
        'date',
        'type',
        'source_system',
        'source_reference',
        'posting_idempotency_key',
        'is_opening_balance',
        'reversal_of_transaction_id',
        'reversal_of_unique_no',
        'correction_type',
        'accounting_repair_run_id',
        'description',
        'debit',
        'credit',
        'created_by',
        'updated_by',
        'posted_by',
        'posted_at',
        'status',
        'credit_note_status',
        'bank_statement_line_id',
        'opening_balance_batch_id',
        'payable_id',
        'payable_type'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date:Y-m-d',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'status' => 'integer',
        'credit_note_status' => 'integer',
        'is_opening_balance' => 'boolean',
        'posted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the transaction.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the transaction.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the account that owns the transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function openingBalanceBatch(): BelongsTo
    {
        return $this->belongsTo(OpeningBalanceBatch::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversal_of_transaction_id');
    }

    /**
     * Get the vendor that owns the transaction.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the customer that owns the transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sale associated with the transaction.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'reference_id');
    }

    /**
     * Get the bill associated with the transaction.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'reference_id');
    }


    /**
     * Get the latest audit action for the transaction.
     */
    public function auditAction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AuditAction::class)->latestOfMany();
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the transaction.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    protected static function booted()
    {
        static::addGlobalScope('transactionStatus', function (Builder $builder) {
            $builder->where('status', true);
        });

        static::addGlobalScope('uniqueNoNotNull', function (Builder $builder) {
            $builder->whereNotNull('unique_no');
        });

        static::updating(function (self $transaction) {
            if ($transaction->getOriginal('is_opening_balance') && (int) $transaction->getOriginal('status') === 1) {
                throw new \LogicException('Posted opening journal lines are immutable; create a controlled correction.');
            }
        });

        static::deleting(function (self $transaction) {
            if ($transaction->is_opening_balance && (int) $transaction->status === 1) {
                throw new \LogicException('Posted opening journal lines cannot be deleted.');
            }
        });

        // :::::::::: it is used for when period locked is false then show record else nothing showing :::::::::: //
        /*static::addGlobalScope('accountingPeriodLocked', function (Builder $builder) {
            $builder->whereHas('accountingPeriod', function ($query) {
                $query->where('is_locked', false);
            });
        });*/
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function getAmountAttribute()
    {
        return abs((float) $this->debit) > 0 ? $this->debit : ($this->credit ?? 0);
    }

    public function isDebit()
    {
        return $this->debit > 0;
    }

    public function isCredit()
    {
        return $this->credit > 0;
    }

    /**
     * Get the bank statement line associated with the transaction.
     */
    public function bankStatementLine()
    {
        return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id');
    }

    public function payable()
    {
        return $this->morphTo(null, 'payable_type', 'payable_id');
    }

    /**
     * Used by Journal to show who this line belongs to
     */
    public function getCounterpartyAttribute(): ?string
    {
        if ($this->payable) {
            return ucfirst($this->payable_type) . ': ' . $this->payable->display_name;
        }

        if ($this->customer) {
            return 'Customer: ' . $this->customer->name;
        }

        return null;
    }
}
