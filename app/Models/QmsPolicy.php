<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\LogsActivity;

class QmsPolicy extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'qms_policies';

    protected $fillable = [
        'policy_number',
        'title',
        'content',
        'version',
        'category',
        'status',
        'priority',
        'department',
        'classification',
        'company_id',
        'created_by',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'reviewer_id',
        'approved_at',
        'approved_by',
        'rejection_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'approved_at'  => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(QmsPolicyAttachment::class, 'policy_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approvalHistory()
    {
        return $this->hasMany(QmsPolicyApproval::class, 'policy_id')->orderBy('approved_at');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($policy) {
            if (empty($policy->policy_number)) {
                $count = static::withTrashed()->count() + 1;
                $policy->policy_number = 'POL-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
