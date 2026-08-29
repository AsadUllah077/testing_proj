<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BillInvoiceDelivery extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['sent_at' => 'datetime'];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
