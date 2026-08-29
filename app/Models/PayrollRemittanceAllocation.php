<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollRemittanceAllocation extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['original_amount' => 'decimal:4', 'amount' => 'decimal:4'];

    public function batch() { return $this->belongsTo(PayrollRemittanceBatch::class, 'payroll_remittance_batch_id'); }
    public function payslipItem() { return $this->belongsTo(PayslipItem::class); }
    public function payItem() { return $this->belongsTo(PayItem::class); }
    public function liabilityAccount() { return $this->belongsTo(ChartOfAccount::class, 'liability_account_id'); }
}
