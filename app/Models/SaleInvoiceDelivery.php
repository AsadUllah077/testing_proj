<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceDelivery extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
