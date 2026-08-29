<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'states';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function county(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
