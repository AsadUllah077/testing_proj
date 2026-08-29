<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'count' => 'integer',
    ];

    /**
     * Get the company that owns the employee type.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getPerLabelAttribute()
    {
        return match ($this->per) {
            'per_week' => '/ Week',
            'per_month' => '/ Month',
            'per_year' => '/ Year',
            default => '',
        };
    }

    /**
     * Get all leave balances for this employee.
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get all leave records for this employee.
     */
    public function leaveRecords()
    {
        return $this->hasMany(LeaveRecord::class);
    }

    /**
     * Get current year leave balance for a specific type.
     */
    public function getCurrentYearLeaveBalance($leaveTypeId)
    {
        return $this->leaveBalances()
            ->where('leave_type_id', $leaveTypeId)
            ->first();
    }

    /**
     * Get available leaves for a specific type in current year.
     */
    public function getAvailableLeaves($leaveTypeId)
    {
        $balance = $this->getCurrentYearLeaveBalance($leaveTypeId);

        return $balance ? $balance->remaining_leaves : 0;
    }

    /**
     * Check if employee has sufficient leave balance.
     */
    public function hasSufficientLeaveBalance($leaveTypeId, $requestedDays)
    {
        $balance = $this->getCurrentYearLeaveBalance($leaveTypeId);

        if (! $balance) {
            return false;
        }

        return $balance->remaining_leaves >= $requestedDays;
    }
}
