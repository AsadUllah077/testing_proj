<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany, Sortable;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'sub_total' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_after_discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public $defaultSort = 'id';

    public $sortable = ['date', 'ref_no', 'invoice_no', 'customer_id', 'account_id', 'sub_total', 'total', 'paid_amount', 'allocated_amount', 'remaining_amount', 'created_by', 'updated_by'];


    /**
     * Get the company that owns the credit note.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the credit note.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the customer that owns the credit note.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the account that owns the credit note.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Get the currency that owns the credit note.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the sale that owns the credit note.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the user who created the credit note.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the credit note.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the credit note items for the credit note.
     */
    public function creditNoteItems(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public static function generateUniqueNo(): string
    {
        $prefix = 'CRN';
        $year = date('Y');
        $month = date('m');

        $lastNote = static::where('unique_no', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastNote) {
            $lastSequence = (int) substr($lastNote->unique_no, -4);
            $sequence = $lastSequence + 1;
        }

        return "{$prefix}{$year}{$month}" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function auditLogs()
    {
        return $this->hasMany(CreditNoteAuditLog::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CreditNoteAllocation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(CreditNoteRefund::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id', 'id')
            ->whereIn('type', ['Credit Note', 'CreditNoteRefund']);
    }
}
