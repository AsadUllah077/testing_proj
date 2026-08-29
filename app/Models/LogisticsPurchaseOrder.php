<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsPurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'currency',
        'payment_terms',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier()
    {
        return $this->belongsTo(LogisticsSupplierV2::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(LogisticsPurchaseOrderItem::class, 'logistics_purchase_order_id');
    }

    public function attachments()
    {
        return $this->hasMany(LogisticsPurchaseOrderAttachment::class, 'logistics_purchase_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
