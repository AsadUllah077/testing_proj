<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'applied_at' => 'datetime',
    ];

    // Status constants
    const STATUS_APPLIED = 'applied';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_INTERVIEWED = 'interviewed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_HIRED = 'hired';

    /**
     * Get the job associated with the application.
     */
    public function job()
    {
        return $this->belongsTo(HrmJob::class,'hrm_job_id');
    }

    /**
     * Get the candidate associated with the application.
     */
    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Get the recruiter assigned to this application.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get full name of applicant.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get status color for display.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'applied' => 'bg-blue-100 text-blue-800',
            'reviewed' => 'bg-yellow-100 text-yellow-800',
            'shortlisted' => 'bg-purple-100 text-purple-800',
            'interviewed' => 'bg-indigo-100 text-indigo-800',
            'rejected' => 'bg-red-100 text-red-800',
            'hired' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get all status options.
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_SHORTLISTED => 'Shortlisted',
            self::STATUS_INTERVIEWED => 'Interviewed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_HIRED => 'Hired',
        ];
    }
}
