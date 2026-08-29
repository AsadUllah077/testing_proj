<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsPurchaseOrderItem extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'logistics_purchase_order_id',
        'sku',
        'description',
        'hs_code',
        'quantity',
        'weight_kg',
        'price',
        'tax_percentage',
        'total',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(LogisticsPurchaseOrder::class, 'logistics_purchase_order_id');
    }
}
