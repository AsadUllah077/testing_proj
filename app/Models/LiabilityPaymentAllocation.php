<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LiabilityPaymentAllocation extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['due_date' => 'date', 'amount' => 'decimal:4'];

    public function batch() { return $this->belongsTo(LiabilityPaymentBatch::class, 'liability_payment_batch_id'); }
    public function account() { return $this->belongsTo(ChartOfAccount::class); }
}
