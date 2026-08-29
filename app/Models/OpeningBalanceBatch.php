<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Support\AccountingConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningBalanceBatch extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'reference',
        'idempotency_key',
        'journal_unique_no',
        'source_system',
        'source_file_reference',
        'source_file_hash',
        'batch_kind',
        'supersedes_batch_id',
        'conversion_date',
        'closing_date',
        'go_live_date',
        'fiscal_year',
        'status',
        'notes',
        'total_debit',
        'total_credit',
        'posted_at',
        'posted_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'locked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conversion_date' => 'date',
        'closing_date' => 'date',
        'go_live_date' => 'date',
        'posted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
    ];

    public function lines()
    {
        return $this->hasMany(OpeningBalanceLine::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function supersedes()
    {
        return $this->belongsTo(self::class, 'supersedes_batch_id');
    }

    public function revision()
    {
        return $this->hasOne(self::class, 'supersedes_batch_id');
    }

    public function isPosted(): bool
    {
        return $this->status === AccountingConstants::BATCH_STATUS_POSTED;
    }

    protected static function booted(): void
    {
        static::updating(function (self $batch) {
            if ($batch->getOriginal('status') && in_array($batch->getOriginal('status'), [
                AccountingConstants::BATCH_STATUS_POSTED,
                AccountingConstants::BATCH_STATUS_LOCKED,
                AccountingConstants::BATCH_STATUS_REVERSED,
            ], true)) {
                $allowed = ['status', 'updated_by', 'updated_at'];
                if (array_diff(array_keys($batch->getDirty()), $allowed)) {
                    throw new \LogicException('Posted opening balance batches are immutable; use a controlled reversal.');
                }
            }
        });

        static::deleting(function (self $batch) {
            if (in_array($batch->status, [AccountingConstants::BATCH_STATUS_POSTED, AccountingConstants::BATCH_STATUS_LOCKED, AccountingConstants::BATCH_STATUS_REVERSED], true)) {
                throw new \LogicException('Posted opening balance batches cannot be deleted.');
            }
        });
    }
}
