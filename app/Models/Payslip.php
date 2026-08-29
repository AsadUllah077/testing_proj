<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'gross_earnings' => 'decimal:4',
        'total_deductions' => 'decimal:4',
        'net_pay' => 'decimal:4',
        'ytd_gross' => 'decimal:4',
        'ytd_deductions' => 'decimal:4',
        'ytd_net' => 'decimal:4',
        'tax_calculation_snapshot' => 'array',
    ];
    public function payRun()
    {
        return $this->belongsTo(PayRun::class);
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function items()
    {
        return $this->hasMany(PayslipItem::class);
    }
}
