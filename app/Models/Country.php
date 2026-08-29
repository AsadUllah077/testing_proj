<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'countries';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id');
    }
}
