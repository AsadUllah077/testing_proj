<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderQuotationItem extends Model
{
    use SoftDeletes, LogsActivity, BelongsToCompany;
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'kg' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the company that owns the purchase order item.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the purchase order item.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the purchase order that owns the purchase order item.
     */
    public function purchaseOrderQuotation(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderQuotation::class, 'purchase_order_quotation_id');
    }

    /**
     * Get the account that owns the purchase order item.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class,'account_id');
    }

    /**
     * Get the tax that owns the purchase order item.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }
}
