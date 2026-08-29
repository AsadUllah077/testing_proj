<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;

class PayslipItem extends Model
{
    use Sortable;
    protected $guarded = [];

    public $defaultSort = 'pay_item_id';

    public $sortable = ['id', 'pay_item_id', 'amount', 'payslip.notes', 'payslip.payRun.period_end', 'payslip.payRun.status'];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
    public function payItem()
    {
        return $this->belongsTo(PayItem::class);
    }

    /* ──────────────────────────────────────────────────────────────────────
     * Statutory remittance-deadline accessors
     *
     * Default global grace period: 15 days after pay-period end.
     * Configure via `config('accounting.payroll_statutory_grace_days')`.
     * Already-paid items are never overdue.
     * ────────────────────────────────────────────────────────────────────── */

    public function getStatutoryDueDateAttribute()
    {
        $periodEnd = optional($this->payslip?->payRun)->period_end;
        if (! $periodEnd) {
            return null;
        }

        // Use statutory body's grace days if available, otherwise fall back to config
        $statutoryBody = $this->payItem?->statutoryBody;
        $graceDays = $statutoryBody ? $statutoryBody->default_grace_days : config('accounting.payroll_statutory_grace_days', 15);
        $due = $periodEnd->copy()->addDays($graceDays);
        while ($due->isWeekend()) $due->addDay();
        return $due;
    }

    public function getIsPaidAttribute(): bool
    {
        return (float) $this->paid_amount >= (float) $this->amount - 0.0001;
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 4));
    }

    public function remittanceAllocations()
    {
        return $this->hasMany(PayrollRemittanceAllocation::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_paid) {
            return false;
        }
        $due = $this->statutory_due_date;

        return $due && now()->startOfDay()->gt($due);
    }

    public function getIsDueSoonAttribute(): bool
    {
        if ($this->is_paid || $this->is_overdue) {
            return false;
        }
        $due = $this->statutory_due_date;
        if (! $due) {
            return false;
        }
        $warningDays = (int) ($this->payItem?->statutoryBody?->default_warning_days ?? config('accounting.payroll_statutory_warning_days', 5));

        return now()->startOfDay()->diffInDays($due, false) <= $warningDays;
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->statutory_due_date, false) * -1;
    }
}
