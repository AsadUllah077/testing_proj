<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendance extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes,Sortable;

    protected $guarded = [];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'total_work_hours' => 'decimal:2',
    ];

    public $defaultSort = 'id';

    public $sortable = ['id', 'attendance_date', 'employee_id', 'check_in', 'check_out', 'total_work_hours'];

    /* ==========================
    | Relationships
    ==========================*/
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /* ==========================
     | Mutators & Helpers
     ==========================*/

    // Auto calculate work hours when check-out is set
    public function calculateWorkHours()
    {
        if ($this->check_in && $this->check_out) {
            $checkIn = \Carbon\Carbon::parse($this->check_in);
            $checkOut = \Carbon\Carbon::parse($this->check_out);

            $minutes = $checkOut->diffInMinutes($checkIn);

            $this->total_work_hours = round($minutes / 60, 2);
        }
    }

    public function calculateWorkHours2()
    {
        if ($this->check_in && $this->check_out) {
            $checkIn = \Carbon\Carbon::parse($this->check_in);
            $checkOut = \Carbon\Carbon::parse($this->check_out);
            $this->total_work_hours = $checkOut->diffInHours($checkIn, true);
            $this->save();
        }
    }

    protected static function booted()
    {
        static::saving(function ($attendance) {
            $attendance->calculateWorkHours();
        });
    }
    // Add these methods to your EmployeeAttendance model

    /**
     * Get attendance status attribute
     */
    public function getStatusAttribute()
    {
        if (! $this->check_in) {
            return 'absent';
        }

        if ($this->working_type === 'remote') {
            return 'remote';
        }

        $shiftStartTime = $this->start_time ?? '09:00:00';
        $shiftEndTime = $this->end_time ?? $shiftStartTime;

        $checkIn = \Carbon\Carbon::parse($this->check_in);
        $checkInMinutes = $checkIn->hour * 60 + $checkIn->minute;

        $sParts = explode(':', $shiftStartTime);
        $eParts = explode(':', $shiftEndTime);

        $startMinutes = (isset($sParts[0]) ? (int) $sParts[0] : 0) * 60 + (isset($sParts[1]) ? (int) $sParts[1] : 0);
        $endMinutes = (isset($eParts[0]) ? (int) $eParts[0] : 0) * 60 + (isset($eParts[1]) ? (int) $eParts[1] : 0);

        $overnight = $endMinutes < $startMinutes;
        if ($overnight && $checkInMinutes < $startMinutes) {
            $checkInMinutes += 1440;
        }

        $graceMinutes = $startMinutes + 15;
        if ($checkInMinutes > $graceMinutes) {
            return 'late';
        }

        return 'present';
    }

    /**
     * Get formatted check in time
     */
    public function getFormattedCheckInAttribute()
    {
        return $this->check_in ? \Carbon\Carbon::parse($this->check_in)->format('h:i A') : '---';
    }

    /**
     * Get formatted check out time
     */
    public function getFormattedCheckOutAttribute()
    {
        return $this->check_out ? \Carbon\Carbon::parse($this->check_out)->format('h:i A') : '---';
    }

    /**
     * Get formatted work hours
     */
    public function getFormattedWorkHoursAttribute()
    {
        if (! $this->total_work_hours) {
            return ' ';
        }

        $hoursDecimal = abs($this->total_work_hours ?? 0);
        $hours = floor($hoursDecimal);
        $minutes = round(($hoursDecimal - $hours) * 60);

        return $hours.'h '.str_pad($minutes, 2, '0', STR_PAD_LEFT).'m';
    }

    /**
     * Get overtime in minutes (only if checkout is >= 60 min after end_time)
     */
    public function getOvertimeMinutesAttribute(): ?int
    {
        if (! $this->check_out || ! $this->end_time) {
            return null;
        }

        $checkOut = \Carbon\Carbon::parse($this->check_out);
        $endTime = \Carbon\Carbon::parse($this->end_time);

        if (! $checkOut->gt($endTime)) {
            return null;
        }

        $overtimeMinutes = $endTime->diffInMinutes($checkOut);

        return $overtimeMinutes >= 60 ? $overtimeMinutes : null;
    }

    /**
     * Get formatted overtime (e.g. "1h 30m"), null when under threshold
     */
    public function getFormattedOvertimeAttribute(): ?string
    {
        $minutes = $this->overtime_minutes;

        if ($minutes === null) {
            return null;
        }

        $hours = (int) floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours.'h '.str_pad($mins, 2, '0', STR_PAD_LEFT).'m';
    }
}
