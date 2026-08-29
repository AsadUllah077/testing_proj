<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BelongsToCompany;
use App\Traits\Sortable;

class JournalEntry extends Model
{
    use SoftDeletes, LogsActivity, BelongsToCompany, Sortable;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $entry) {
            if ($entry->getOriginal('posting_status') === 'posted') {
                throw new \LogicException('Posted journals are immutable; use a controlled reversal.');
            }
        });
        static::deleting(function (self $entry) {
            if ($entry->posting_status === 'posted') {
                throw new \LogicException('Posted journals cannot be deleted; use a controlled reversal.');
            }
        });
    }

    public $defaultSort = 'date';

    public $sortable = ['date', 'unique_no', 'narration', 'item', 'description', 'account_id', 'debit', 'credit'];

    /* -----------------------------------------
     | Relationships
     |------------------------------------------*/

    // Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Accounting Period
    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    // Account (chart of accounts)
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    // Created By
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Updated By
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs()
    {
        return $this->hasMany(JournalEntryLog::class);
    }
    public function logChange($event, $old = null, $new = null)
    {
        JournalEntryLog::create([
            'journal_entry_id' => $this->unique_no,
            'company_id'  => $this->company_id,
            'user_id'     => auth('web')->id(),
            'event'       => $event,
            'old_values'  => $old,
            'new_values'  => $new,
        ]);
    }
}
