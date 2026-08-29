<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollFinalSettlement extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'settlement_date' => 'date', 'last_working_date' => 'date', 'lines' => 'array',
        'gross_amount' => 'decimal:4', 'deduction_amount' => 'decimal:4', 'net_amount' => 'decimal:4',
        'hr_approved_at' => 'datetime', 'payroll_approved_at' => 'datetime',
        'approved_at' => 'datetime', 'posted_at' => 'datetime',
    ];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
