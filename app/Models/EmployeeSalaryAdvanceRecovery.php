<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryAdvanceRecovery extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:4', 'recovered_at' => 'datetime'];

    public function advance() { return $this->belongsTo(EmployeeSalaryAdvance::class, 'employee_salary_advance_id'); }
    public function payRun() { return $this->belongsTo(PayRun::class); }
    public function payslip() { return $this->belongsTo(Payslip::class); }
}
