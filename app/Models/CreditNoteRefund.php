<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteRefund extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['refund_date' => 'date', 'amount' => 'decimal:2'];

    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
    public function account(): BelongsTo { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
    public function accountingPeriod(): BelongsTo { return $this->belongsTo(AccountingPeriod::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
