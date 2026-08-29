<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementImport extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'bank_account_id', 'statement_number', 'start_date', 'end_date',
        'opening_balance', 'closing_balance', 'currency', 'source_filename', 'file_hash',
        'line_count', 'status', 'review_note', 'imported_by', 'imported_at', 'reviewed_by',
        'reviewed_at', 'finalized_by', 'finalized_at', 'reopened_by', 'reopened_at', 'reopen_reason',
    ];

    protected $casts = [
        'start_date' => 'date', 'end_date' => 'date', 'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4', 'imported_at' => 'datetime', 'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime', 'reopened_at' => 'datetime',
    ];

    public function bankAccount() { return $this->belongsTo(ChartOfAccount::class, 'bank_account_id'); }
    public function lines() { return $this->hasMany(BankStatementLine::class); }
    public function importer() { return $this->belongsTo(User::class, 'imported_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function finalizer() { return $this->belongsTo(User::class, 'finalized_by'); }
}
