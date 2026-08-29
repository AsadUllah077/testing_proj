<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmCandidate extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'applied_date' => 'date',
        'interview_date' => 'date',
        'answers' => 'array',
        'rating' => 'decimal:1',
    ];
    protected $appends = ['full_name', 'stage_color'];

    // Source constants
    const SOURCE_LINKEDIN = 'linkedin';

    const SOURCE_WEBSITE = 'website';

    const SOURCE_REFERRAL = 'referral';

    const SOURCE_DIRECT = 'direct';

    const SOURCE_OTHER = 'other';

    // Stage constants
    const STAGE_NEW = 'new';

    const STAGE_SCREENING = 'screening';

    const STAGE_INTERVIEWING = 'interviewing';

    const STAGE_OFFER = 'offer';

    const STAGE_REJECTED = 'rejected';

    const STAGE_HIRED = 'hired';

    /**
     * Get the full name of the candidate.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the job associated with the candidate.
     */
    public function job()
    {
        return $this->belongsTo(HrmJob::class, 'hrm_job_id');
    }

    /**
     * Get the company associated with the candidate.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who added this candidate.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all source options.
     */
    public static function getSourceOptions()
    {
        return [
            self::SOURCE_LINKEDIN => 'LinkedIn',
            self::SOURCE_WEBSITE => 'Company Website',
            self::SOURCE_REFERRAL => 'Employee Referral',
            self::SOURCE_DIRECT => 'Direct Application',
            self::SOURCE_OTHER => 'Other',
        ];
    }

    /**
     * Get all stage options.
     */
    public static function getStageOptions()
    {
        return [
            self::STAGE_NEW => 'New',
            self::STAGE_SCREENING => 'Screening',
            self::STAGE_INTERVIEWING => 'Interviewing',
            self::STAGE_OFFER => 'Offer',
            self::STAGE_REJECTED => 'Rejected',
            self::STAGE_HIRED => 'Hired',
        ];
    }

    /**
     * Get stage color for display.
     */
    public function getStageColorAttribute()
    {
        return match ($this->stage) {
            'new' => 'bg-blue-100 text-blue-800',
            'screening' => 'bg-yellow-100 text-yellow-800',
            'interviewing' => 'bg-purple-100 text-purple-800',
            'offer' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'hired' => 'bg-emerald-100 text-emerald-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get rating stars for display.
     */
    public function getRatingStarsAttribute()
    {
        if (! $this->rating) {
            return null;
        }

        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

        return [
            'full' => $fullStars,
            'half' => $halfStar,
            'empty' => $emptyStars,
        ];
    }

    /**
     * Scope for candidates by job.
     */
    public function scopeForJob($query, $jobId)
    {
        return $query->where('hrm_job_id', $jobId);
    }

    /**
     * Scope for candidates by stage.
     */
    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Scope for active candidates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the file extension of the resume.
     */
    public function getResumeExtensionAttribute()
    {
        if (! $this->resume_path) {
            return null;
        }

        return pathinfo($this->resume_original_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the file size of the resume in human readable format.
     */
    public function getResumeSizeAttribute()
    {
        if (! $this->resume_path || ! file_exists(storage_path('app/'.$this->resume_path))) {
            return null;
        }

        $size = filesize(storage_path('app/'.$this->resume_path));

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }


    // ... existing methods ...

    // Add getInterviewDateTimeAttribute
    public function getInterviewDateTimeAttribute()
    {
        if ($this->interview_date && $this->interview_time) {
            return $this->interview_date->format('Y-m-d').' '.$this->interview_time;
        }

        return null;
    }

    // Add formatted interview time
    public function getFormattedInterviewTimeAttribute()
    {
        if ($this->interview_time) {
            return \Carbon\Carbon::createFromFormat('H:i:s', $this->interview_time)->format('h:i A');
        }

    }
}
