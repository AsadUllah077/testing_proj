<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingRepairItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'related_entity_ids' => 'array',
        'before_snapshot' => 'array',
        'authoritative_target' => 'array',
        'after_snapshot' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(AccountingRepairRun::class, 'repair_run_id');
    }
}
