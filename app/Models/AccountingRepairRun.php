<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AccountingRepairRun extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'summary' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(AccountingRepairItem::class, 'repair_run_id');
    }
}
