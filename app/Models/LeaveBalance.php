<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use  SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'total_leaves' => 'integer',
        'used_leaves' => 'decimal:2',
        'remaining_leaves' => 'decimal:2',
        'year' => 'integer',
    ];

    /**
     * Get the employee associated with the leave balance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the company associated with the leave balance.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the leave type associated with the leave balance.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Scope for current year balance.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope for specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Check if the balance is for the current year.
     */
    public function isCurrentYear()
    {
        return $this->year == now()->year;
    }

    /**
     * Get formatted leave balance.
     */
    public function getFormattedBalanceAttribute()
    {
        return "{$this->remaining_leaves} / {$this->total_leaves}";
    }

    /**
     * Get used leaves percentage.
     */
    public function getUsedPercentageAttribute()
    {
        if ($this->total_leaves <= 0) {
            return 0;
        }

        return round(($this->used_leaves / $this->total_leaves) * 100, 2);
    }

    /**
     * Increment used leaves.
     */
    public function incrementUsedLeaves($value)
    {
        $this->used_leaves += $value;
        $this->remaining_leaves = $this->total_leaves - $this->used_leaves;
        $this->save();
    }

    /**
     * Decrement used leaves.
     */
    public function decrementUsedLeaves($value)
    {
        $this->used_leaves = max(0, $this->used_leaves - $value);
        $this->remaining_leaves = $this->total_leaves - $this->used_leaves;
        $this->save();
    }
}
