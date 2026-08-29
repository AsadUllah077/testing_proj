<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaxSettlementBatch extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['settlement_date' => 'date', 'snapshot' => 'array', 'reversed_at' => 'datetime'];

    public function taxPeriod() { return $this->belongsTo(TaxPeriod::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
