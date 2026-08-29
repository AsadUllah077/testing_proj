<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteAllocation extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['allocation_date' => 'date', 'amount' => 'decimal:2'];

    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
