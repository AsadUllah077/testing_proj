<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementLine extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'accounting_period_id',
        'bank_account_id',
        'bank_statement_import_id',
        'date',
        'amount',
        'remaining_amount',
        'payee',
        'description',
        'reference',
        'transaction_id',
        'status',
        'note',
        'unique_no',
        'row_hash',
        'reconciled_by',
        'reconciled_at',
        'unreconciled_by',
        'unreconciled_at',
        'unreconcile_reason',
        'match_confidence',
        'is_locked',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'unreconciled_at' => 'datetime',
        'match_confidence' => 'integer',
        'is_locked' => 'boolean',
    ];

    protected $table = 'bank_statement_lines';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bank_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'bank_statement_line_id');
    }

    public function statementImport()
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function matches()
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    public function events()
    {
        return $this->hasMany(BankReconciliationEvent::class);
    }
}
