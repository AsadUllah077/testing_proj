<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity'            => 'decimal:2',
        'price'               => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'sub_total'           => 'decimal:2',
        'tax_amount'          => 'decimal:2',
        'total'               => 'decimal:2',
    ];

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }
}
