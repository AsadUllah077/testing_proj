<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AccountingPeriod extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity, Sortable;

    protected $fillable = [
        'company_id',
        'tax_rate_id',
        'default_sale_tax_rate_id',
        'default_purchase_tax_rate_id',
        'name',
        'start_date',
        'end_date',
        'tax_period',
        'is_active',
        'is_locked',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',

        'is_active'  => 'boolean',
        'is_locked'  => 'boolean',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $sortable = ['id', 'start_date', 'end_date', 'is_active', 'is_locked'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnlocked(Builder $query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeForCompany(Builder $query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }


    public static function findPeriodForDate($date, $companyId = null)
    {
        // Resolve company ID: use provided value, then logged-in user, then no filter
        if ($companyId === null) {
            try {
                $companyId = loggedCompanyId()['company_id'] ?? null;
            } catch (\Throwable $e) {
                $companyId = null;
            }
        }

        $query = self::query()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('is_locked', true);
            })
            ->orderBy('is_locked')
            ->orderByDesc('start_date');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $period = $query->first();

        if (!$period) {
            return null;
        }

        if ($period->is_locked) {
            // Check if current user is in the allowed users list (Advisors/Exception Users)
            // In Xero, only users with Advisor status can edit locked periods
            $userId = auth('web')->id();

            if (!$userId) {
                abort(403, 'Unauthorized.');
            }

            /* 
            $isBlocked = $period->lockUsers()->where('user_id', $userId)->exists();

            if ($isBlocked) {
                abort(403, 'This period is locked. You are not allowed to perform any action.');
            } 
            */

            // Check if user is in the allowed users list
            $isAllowedUser = $period->allowedUsers()->where('user_id', $userId)->exists();

            // If period is locked and user is NOT in allowed list, block them
            if (!$isAllowedUser) {
                abort(403, 'This period is locked. Only authorized users (Advisors) can perform actions on locked periods.');
            }
        }

        return $period;
    }

    public function contains($date)
    {
        return $date->between($this->start_date, $this->end_date);
    }

    public function isOpen()
    {
        return $this->is_active && !$this->is_locked;
    }

    public function lock()
    {
        $this->update(['is_locked' => true]);
    }

    public function unlock()
    {
        $this->update(['is_locked' => false]);
    }

    public function accountingPeriodLogs()
    {
        return $this->hasMany(AccountingPeriodLogs::class);
    }

    /**
     * Users who are ALLOWED to edit this period when it's locked
     * When a period is locked, only users in this list can create/edit transactions
     */
    public function allowedUsers()
    {
        return $this->hasMany(AccountingPeriodLockUser::class);
    }

    /**
     * @deprecated Use allowedUsers() instead. Kept for backward compatibility.
     */
    public function lockUsers()
    {
        return $this->allowedUsers();
    }
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
