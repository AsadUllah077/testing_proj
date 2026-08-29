<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount_to_apply'   => 'decimal:2',
        'invoice_total'     => 'decimal:2',
        'invoice_remaining' => 'decimal:2',
    ];

    public function customerPayment()
    {
        return $this->belongsTo(CustomerPayment::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
