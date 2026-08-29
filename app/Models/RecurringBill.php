<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringBill extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'next_run_date'  => 'date',
        'last_run_date'  => 'date',
        'sub_total'      => 'decimal:2',
        'tax'            => 'decimal:2',
        'discount'       => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringBillItem::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Calculate the next run date based on frequency and interval. */
    public function calculateNextRunDate(Carbon $from): Carbon
    {
        return match ($this->frequency) {
            'weekly'    => $from->copy()->addWeeks($this->interval),
            'quarterly' => $from->copy()->addMonths(3 * $this->interval),
            'yearly'    => $from->copy()->addYears($this->interval),
            default     => $from->copy()->addMonths($this->interval), // monthly
        };
    }

    public function isExpired(): bool
    {
        if ($this->end_date && $this->next_run_date->gt($this->end_date)) {
            return true;
        }

        if ($this->max_occurrences && $this->bills_generated >= $this->max_occurrences) {
            return true;
        }

        return false;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'weekly'    => $this->interval === 1 ? 'Weekly'    : "Every {$this->interval} weeks",
            'monthly'   => $this->interval === 1 ? 'Monthly'   : "Every {$this->interval} months",
            'quarterly' => $this->interval === 1 ? 'Quarterly' : "Every {$this->interval} quarters",
            'yearly'    => $this->interval === 1 ? 'Yearly'    : "Every {$this->interval} years",
            default     => ucfirst($this->frequency),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'emerald',
            'paused'    => 'amber',
            'completed' => 'blue',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueToday($query)
    {
        return $query->where('status', 'active')
            ->whereDate('next_run_date', '<=', today());
    }
}
