<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPaymentRunItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount_to_pay'  => 'decimal:2',
        'bill_total'     => 'decimal:2',
        'bill_remaining' => 'decimal:2',
    ];

    public function vendorPaymentRun()
    {
        return $this->belongsTo(VendorPaymentRun::class, 'vendor_payment_run_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
