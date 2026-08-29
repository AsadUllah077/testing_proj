<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'currencies';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
