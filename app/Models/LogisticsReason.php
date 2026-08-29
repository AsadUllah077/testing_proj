<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsReason extends Model
{
    protected $fillable = [
        'type',
        'reason_text',
        'is_system'
    ];
}
