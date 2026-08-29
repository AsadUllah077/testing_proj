<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsPurchaseOrderAttachment extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $table = 'logistics_po_attachments';

    protected $fillable = [
        'company_id',
        'logistics_purchase_order_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
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
