<?php
// app/Models/GatePassItem.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatePassItem extends Model
{
    use LogsActivity, BelongsToCompany;

    protected $guarded =[];

    protected $casts = [
        'product_details' => 'array',
        'quantity' => 'integer',
        'returned_at' => 'datetime',
    ];

    public function gatePass()
    {
        return $this->belongsTo(GatePass::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
