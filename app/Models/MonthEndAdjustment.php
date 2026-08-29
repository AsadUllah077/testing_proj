<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthEndAdjustment extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'fixed_asset_id',
        'unique_no',
        'type',
        'date',
        'narration',
        'lines',
        'total_amount',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'lines' => 'array',
        'total_amount' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
