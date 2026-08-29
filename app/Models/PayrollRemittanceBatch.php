<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollRemittanceBatch extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:4',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function allocations() { return $this->hasMany(PayrollRemittanceAllocation::class); }
    public function statutoryBody() { return $this->belongsTo(StatutoryBody::class); }
    public function bankAccount() { return $this->belongsTo(ChartOfAccount::class, 'bank_account_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
