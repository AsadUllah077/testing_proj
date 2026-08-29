<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAction extends Model
{
    protected $fillable = [
        'company_id',
        'transaction_id',
        'action',
        'status',
        'performed_by',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)
            ->withoutGlobalScopes(['transactionStatus', 'uniqueNoNotNull'])
            ->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
