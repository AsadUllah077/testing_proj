<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CreditNoteAuditLog extends Model
{

    protected $fillable = [
        'credit_note_id',
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

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class,'credit_note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
