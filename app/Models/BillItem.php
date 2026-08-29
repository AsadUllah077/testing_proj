<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillItem extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany, Sortable;

    protected $guarded = [];
    protected $casts = [
        'quantity' => 'decimal:4',
        'kg' => 'decimal:4',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate_snapshot' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total' => 'decimal:2',
    ];

    public $defaultSort = 'unique_no';
    public $sortable = ['unique_no', 'item', 'description', 'kg', 'quantity', 'price', 'total', 'account_id', 'created_by', 'updated_by'];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function tax()
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
