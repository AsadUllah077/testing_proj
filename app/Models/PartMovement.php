<?php
// app/Models/PartMovement.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartMovement extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'before_quantity' => 'integer',
        'after_quantity' => 'integer',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
