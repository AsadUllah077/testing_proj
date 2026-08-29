<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaxRateLog extends Model
{
    protected $fillable = [
        'tax_rate_id',
        'company_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
