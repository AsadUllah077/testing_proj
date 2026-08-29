<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents users who are ALLOWED to edit locked accounting periods (like Xero Advisors)
 * When a period is locked, only users in this table can create/edit transactions
 * All other users are blocked from making changes to locked periods
 */
class AccountingPeriodLockUser extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'user_id',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
