<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleAuditLog extends Model
{

    protected $fillable = [
        'sale_id',
        'user_id',
        'action',
        'old_data',
        'new_data',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changes' => 'array',
    ];

    public function sale(): BelongsTo

    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
