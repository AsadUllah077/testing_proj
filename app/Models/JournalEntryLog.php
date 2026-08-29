<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLog extends Model
{

    protected $guarded = [];
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
