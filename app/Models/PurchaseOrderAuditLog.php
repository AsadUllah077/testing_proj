<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderAuditLog extends Model
{


    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'action',
        'old_data',
        'new_data',
         'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changes' => 'array',

    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class,'purchase_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
