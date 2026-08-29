<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderPayment extends Model
{
     use LogsActivity,SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
     public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
      public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

}
