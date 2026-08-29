<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriodLogs extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'user_id',
        'event',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
