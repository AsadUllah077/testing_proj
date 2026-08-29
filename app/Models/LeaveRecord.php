<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRecord extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes,Sortable;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'early_leave_time' => 'datetime:H:i',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'created_by'];

    // Half day options
    const HALF_NONE = null;

    const HALF_FIRST = 'first_half';

    const HALF_SECOND = 'second_half';

    /**
     * Get the employee associated with the leave record.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the company associated with the leave record.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the leave type associated with the leave record.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who created this leave record.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total days for this leave record.
     * Handles half days (0.5 per half day) and full days.
     */
    public function calculateTotalDays()
    {
        $start = $this->start_date;
        $end = $this->end_date;

        // Ensure we have employee working days
        $employee = $this->employee;

        // If same day
        if ($start->eq($end)) {
            if ($this->isWorkingDay($start, $employee)) {
                return $this->isHalfDay() ? 0.5 : 1;
            }

            return 0;
        }

        $totalDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($this->isWorkingDay($current, $employee)) {
                if ($current->eq($start) && $this->half === self::HALF_FIRST) {
                    $totalDays += 0.5; // First half on start date
                } elseif ($current->eq($end) && $this->half === self::HALF_SECOND) {
                    $totalDays += 0.5; // Second half on end date
                } else {
                    $totalDays += 1; // Full day
                }
            }
            $current->addDay();
        }

        return $totalDays;
    }

    /**
     * Check if the leave record is for a half day.
     */
    public function isHalfDay()
    {
        return ! is_null($this->half);
    }

    /**
     * Check if it's first half.
     */
    public function isFirstHalf()
    {
        return $this->half === self::HALF_FIRST;
    }

    /**
     * Check if it's second half.
     */
    public function isSecondHalf()
    {
        return $this->half === self::HALF_SECOND;
    }

    /**
     * Check if it's an early leave.
     */
    public function isEarlyLeave()
    {
        return ! is_null($this->early_leave_time);
    }

    /**
     * Get formatted early leave time.
     */
    public function getFormattedEarlyLeaveTimeAttribute()
    {
        return $this->early_leave_time ? date('h:i A', strtotime($this->early_leave_time)) : null;
    }

    /**
     * Get leave duration description.
     */
    public function getDurationDescriptionAttribute()
    {
        if ($this->isHalfDay()) {
            return $this->half === self::HALF_FIRST ? 'First Half Day' : 'Second Half Day';
        }

        if ($this->isEarlyLeave()) {
            return "Early Leave at {$this->formatted_early_leave_time}";
        }

        if ($this->start_date->eq($this->end_date)) {
            return 'Full Day';
        }

        return $this->start_date->format('M d').' - '.$this->end_date->format('M d');
    }

    /**
     * Scope for manual adjustments.
     */
    public function scopeManualAdjustments($query)
    {
        return $query->whereNotNull('adjustment_reason');
    }

    /**
     * Scope for regular leave requests.
     */
    public function scopeRegularRequests($query)
    {
        return $query->whereNull('adjustment_reason');
    }

    /**
     * Scope for leaves in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Get all half day options.
     */
    public static function getHalfDayOptions()
    {
        return [
            self::HALF_NONE => 'Full Day',
            self::HALF_FIRST => 'First Half',
            self::HALF_SECOND => 'Second Half',
        ];
    }

    /**
     * Helper method to check if a date is working day.
     */
    public function isWorkingDay($date, $employee = null)
    {
        $employee = $employee ?: $this->employee;

        if (! $employee || ! $employee->working_day_from || ! $employee->working_day_to) {
            return $date->dayOfWeek !== \Carbon\Carbon::SUNDAY; // Default to Monday-Friday
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $fromIndex = array_search($employee->working_day_from, $days);
        $toIndex = array_search($employee->working_day_to, $days);

        if ($fromIndex === false || $toIndex === false) {
            return $date->dayOfWeek !== \Carbon\Carbon::SUNDAY;
        }

        // format('N') returns 1 (Monday) through 7 (Sunday)
        $currentDayIndex = (int) $date->format('N') - 1;

        if ($fromIndex <= $toIndex) {
            return $currentDayIndex >= $fromIndex && $currentDayIndex <= $toIndex;
        } else {
            // Handles cases like Saturday to Tuesday
            return $currentDayIndex >= $fromIndex || $currentDayIndex <= $toIndex;
        }
    }
}
