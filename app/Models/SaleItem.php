<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany, Sortable;

    protected $guarded = [];

    protected $casts = [
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public $defaultSort = 'unique_no';
    public $sortable = ['unique_no', 'item', 'description', 'kg', 'quantity', 'price', 'total', 'account_id', 'created_by', 'updated_by'];

    /**
     * Get the company that owns the sale.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the sale.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the account that owns the sale.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Get the user who created the sale.
     */
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

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
