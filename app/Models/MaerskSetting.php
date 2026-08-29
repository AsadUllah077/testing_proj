<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaerskSetting extends Model
{
    protected $fillable = [
        'company_id',
        'consumer_key',
        'consumer_secret',
        'base_url',
        'access_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];
}
