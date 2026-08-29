<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryAdvance extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'amount',
        'remaining_amount',
        'recovery_method',
        'recovery_frequency',
        'installment_amount',
        'recovery_percentage',
        'max_net_deduction_percentage',
        'recovery_start_date',
        'installments_total',
        'installments_recovered',
        'recovery_paused_at',
        'advance_date',
        'reason',
        'status',
        'payment_reference',
        'bank_account_id',
        'issued_at',
        'issued_by',
        'recovered_pay_run_id',
        'recovered_at',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installment_amount' => 'decimal:4',
        'recovery_percentage' => 'decimal:4',
        'max_net_deduction_percentage' => 'decimal:4',
        'recovery_start_date' => 'date',
        'recovery_paused_at' => 'datetime',
        'advance_date' => 'date',
        'issued_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public $defaultSort = 'advance_date';

    public $sortable = ['advance_date', 'amount', 'remaining_amount'];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }

    public function recoveredPayRun()
    {
        return $this->belongsTo(PayRun::class, 'recovered_pay_run_id');
    }

    public function recoveries()
    {
        return $this->hasMany(EmployeeSalaryAdvanceRecovery::class);
    }
}
