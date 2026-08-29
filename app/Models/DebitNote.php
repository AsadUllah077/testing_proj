<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'      => 'date',
        'sub_total' => 'decimal:2',
        'tax'       => 'decimal:2',
        'discount'  => 'decimal:2',
        'total'     => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
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

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
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
        return $this->hasMany(DebitNoteItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->whereIn('type', ['DebitNote', 'DebitNoteRefund']);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DebitNoteAllocation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(DebitNoteRefund::class);
    }

    public function getUsageStatusAttribute(): string
    {
        if ((float) $this->remaining_amount <= 0) {
            if ((float) $this->refunded_amount > 0 && (float) $this->allocated_amount > 0) {
                return 'Fully Used';
            }

            return (float) $this->allocated_amount > 0 ? 'Fully Allocated' : 'Fully Refunded';
        }

        if ((float) $this->allocated_amount > 0 || (float) $this->refunded_amount > 0) {
            return 'Partially Used';
        }

        return 'Available Credit';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function generateUniqueNo(): string
    {
        $prefix = 'DRN';
        $ym     = now()->format('Ym');

        $last = static::where('unique_no', 'like', "{$prefix}{$ym}%")
            ->orderByDesc('id')
            ->value('unique_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . $ym . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 1 ? 'Confirmed' : 'Draft';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status === 1 ? 'emerald' : 'gray';
    }
}
