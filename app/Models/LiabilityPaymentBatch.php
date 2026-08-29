<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LiabilityPaymentBatch extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['payment_date' => 'date', 'reversed_at' => 'datetime'];

    public function allocations() { return $this->hasMany(LiabilityPaymentAllocation::class); }
    public function bankAccount() { return $this->belongsTo(ChartOfAccount::class, 'bank_account_id'); }
}
