<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AccountingCloseTask extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'task_key',
        'is_completed',
        'notes',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
