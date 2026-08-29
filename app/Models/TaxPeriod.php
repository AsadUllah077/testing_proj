<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxPeriod extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity, SoftDeletes, Sortable;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'status',
        'total_payable',
        'total_receivable',
        'net_amount',
        'carried_forward_amount',
        'paid_amount',
        'unique_no',
        'created_by',
        'updated_by',
        'approval_status', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'approval_note',
        'locked_at', 'locked_by', 'settlement_reference', 'tax_authority', 'jurisdiction',
        'registration_number', 'filing_type', 'filing_reference', 'filed_at', 'filing_status', 'filing_attachment',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_payable' => 'decimal:4',
        'total_receivable' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'carried_forward_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'locked_at' => 'datetime', 'filed_at' => 'date',
    ];

    public $defaultSort = 'start_date';

    public $sortable = ['id', 'start_date', 'end_date', 'status', 'total_payable', 'total_receivable', 'net_amount', 'carried_forward_amount', 'paid_amount', 'name', 'type'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'unique_no', 'unique_no');
    }

    public function settlementBatches()
    {
        return $this->hasMany(TaxSettlementBatch::class);
    }

    public function paymentBatches()
    {
        return $this->hasMany(TaxPaymentBatch::class);
    }

    public function isLockedForPosting(): bool
    {
        return $this->locked_at !== null || in_array($this->status, ['settled', 'paid', 'partial_paid'], true);
    }

    public static function isDateLocked($companyId, $date)
    {
        return self::where('company_id', $companyId)
            ->whereIn('status', ['settled', 'paid', 'partial_paid'])
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /* ──────────────────────────────────────────────────────────────────────
     * Statutory filing-deadline accessors (global default: 30 days after period end)
     *
     * Override `config('accounting.tax_filing_grace_days')` per jurisdiction:
     *   Malaysia GST/SST: 30 days
     *   UK VAT:           37 days (1 month + 7 days)
     *   EU VAT:           ~30 days (varies by country)
     *   India GST:        20 days (GSTR-3B)
     * ────────────────────────────────────────────────────────────────────── */

    public function getDueDateAttribute()
    {
        if (! $this->end_date) {
            return null;
        }
        $graceDays = (int) config('accounting.tax_filing_grace_days', 30);

        return $this->end_date->copy()->addDays($graceDays);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['paid'])) {
            return false;
        }
        $due = $this->due_date;

        return $due && now()->startOfDay()->gt($due);
    }

    public function getIsDueSoonAttribute(): bool
    {
        if (in_array($this->status, ['paid']) || $this->is_overdue) {
            return false;
        }
        $due = $this->due_date;
        if (! $due) {
            return false;
        }
        $warningDays = (int) config('accounting.tax_filing_warning_days', 7);

        return now()->startOfDay()->diffInDays($due, false) <= $warningDays;
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date, false) * -1;
    }
}
