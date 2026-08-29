<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayRun extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable;
    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'unique_no',
        'created_by',
        'frequency',
        'run_type',
        'original_pay_run_id',
        'approval_status',
        'preflight_status',
        'preflight_checked_at',
        'preflight_checked_by',
        'preflight_override_reason',
        'approved_by',
        'approved_at',
        'approval_notes',
        'accounting_notification_status',
        'accounting_notified_at',
        'accounting_notified_by',
        'accounting_posted_at',
        'accounting_posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_unique_no',
    ];
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'accounting_notified_at' => 'datetime',
        'accounting_posted_at' => 'datetime',
        'preflight_checked_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];
    public $defaultSort = 'id';

    public $sortable = ['id', 'unique_no', 'period_start', 'period_end', 'payment_date', 'status', 'approval_status'];

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function accountingNotifiedBy()
    {
        return $this->belongsTo(User::class, 'accounting_notified_by');
    }

    public function accountingPostedBy()
    {
        return $this->belongsTo(User::class, 'accounting_posted_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function originalPayRun() { return $this->belongsTo(PayRun::class, 'original_pay_run_id'); }
    public function supplementaryRuns() { return $this->hasMany(PayRun::class, 'original_pay_run_id'); }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
