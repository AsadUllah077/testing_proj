<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningBalanceLine extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'opening_balance_batch_id',
        'company_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'source_account_code',
        'source_account_name',
        'source_reference',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(OpeningBalanceBatch::class, 'opening_balance_batch_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    protected static function booted(): void
    {
        $guard = function (self $line): void {
            $status = $line->batch()->withoutGlobalScopes()->value('status');
            if (in_array($status, ['Posted', 'Locked', 'Reversed'], true)) {
                throw new \LogicException('Lines belonging to a posted opening balance batch are immutable.');
            }
        };
        static::updating($guard);
        static::deleting($guard);
    }
}
