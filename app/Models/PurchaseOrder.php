<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'order_date' => 'date',
        'departure_date' => 'date',
        'sub_total' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Get the company that owns the purchase order.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the purchase order.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the vendor that owns the purchase order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the account that owns the purchase order.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Get the currency that owns the purchase order.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

     public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Get the user who last updated the sale.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function purchaseOrderItems(){
        return $this->hasMany(PurchaseOrderItem::class);
    }
     public function auditLogs()
    {
        return $this->hasMany(PurchaseOrderAuditLog::class)->with('user')->orderBy('created_at', 'desc');
    }
}
