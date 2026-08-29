<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteItem extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany;
    protected $guarded = [];
    protected $table = 'credit_note_items';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'kg' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the company that owns the credit note item.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the credit note item.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the credit note that owns the item.
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    /**
     * Get the account that owns the credit note item.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Get the tax that owns the credit note item.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }

    /**
     * Get the user who created the credit note item.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the credit note item.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
