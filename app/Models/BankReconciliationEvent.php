<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationEvent extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'bank_statement_line_id', 'bank_statement_import_id',
        'event', 'metadata', 'user_id',
    ];

    protected $casts = ['metadata' => 'array'];

    public function statementLine() { return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id'); }
    public function statementImport() { return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
