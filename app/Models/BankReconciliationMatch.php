<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationMatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'bank_statement_line_id', 'transaction_id', 'matchable_type', 'matchable_id',
        'amount', 'origin', 'posting_unique_no', 'confidence', 'matched_by', 'matched_at',
        'reversed_by', 'reversed_at', 'reversal_reason', 'reversal_unique_no',
    ];

    protected $casts = ['amount' => 'decimal:4', 'matched_at' => 'datetime', 'reversed_at' => 'datetime'];

    public function statementLine() { return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id'); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function matchable() { return $this->morphTo(); }
    public function scopeActive($query) { return $query->whereNull('reversed_at'); }
}
