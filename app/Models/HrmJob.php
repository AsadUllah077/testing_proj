<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmJob extends Model
{
    use LogsActivity, SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'skills' => 'array',
        'posted_date' => 'date',
        'closing_date' => 'date',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_OPEN = 'open';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_CLOSED = 'closed';

    const STATUS_CANCELLED = 'cancelled';

    // Employment type constants
    const TYPE_FULL_TIME = 'full_time';

    const TYPE_PART_TIME = 'part_time';

    const TYPE_CONTRACT = 'contract';

    const TYPE_INTERNSHIP = 'internship';

    const TYPE_REMOTE = 'remote';

    // Experience level constants
    const LEVEL_ENTRY = 'entry';

    const LEVEL_MID = 'mid';

    const LEVEL_SENIOR = 'senior';

    const LEVEL_LEAD = 'lead';

    const LEVEL_EXECUTIVE = 'executive';

    /**
     * Get the department associated with the job.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the hiring manager associated with the job.
     */
    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    /**
     * Get the company associated with the job.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this job.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all applications for this job.
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Scope for active jobs (open).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for closed jobs.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for draft jobs.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Check if job is active (open).
     */
    public function isActive()
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if job is closed.
     */
    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Get formatted employment type.
     */
    public function getFormattedEmploymentTypeAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->employment_type));
    }

    /**
     * Get formatted experience level.
     */
    public function getFormattedExperienceLevelAttribute()
    {
        return ucfirst($this->experience_level);
    }

    /**
     * Get status color for display.
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'open' => 'bg-green-100 text-green-800',
            'on_hold' => 'bg-yellow-100 text-yellow-800',
            'closed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get all status options.
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_OPEN => 'Open',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get all employment type options.
     */
    public static function getEmploymentTypeOptions()
    {
        return [
            self::TYPE_FULL_TIME => 'Full Time',
            self::TYPE_PART_TIME => 'Part Time',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_INTERNSHIP => 'Internship',
            self::TYPE_REMOTE => 'Remote',
        ];
    }

    /**
     * Get all experience level options.
     */
    public static function getExperienceLevelOptions()
    {
        return [
            self::LEVEL_ENTRY => 'Entry Level',
            self::LEVEL_MID => 'Mid Level',
            self::LEVEL_SENIOR => 'Senior Level',
            self::LEVEL_LEAD => 'Lead',
            self::LEVEL_EXECUTIVE => 'Executive',
        ];
    }

    /**
     * Generate job ID if not exists.
     */
    public static function generateJobId()
    {
        $year = date('Y');
        $lastJob = self::where('job_id', 'like', "JB-{$year}-%")
            ->orderBy('job_id', 'desc')
            ->first();

        if ($lastJob) {
            $lastNumber = intval(substr($lastJob->job_id, -3));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "JB-{$year}-{$newNumber}";
    }

    /**
     * Get all candidates for this job.
     */
    public function candidates()
    {
        return $this->hasMany(HrmCandidate::class, 'hrm_job_id');
    }

    /**
     * Get the number of candidates for this job.
     */
    public function getCandidatesCountAttribute()
    {
        return $this->candidates()->count();
    }
}
