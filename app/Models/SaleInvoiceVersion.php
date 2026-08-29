<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceVersion extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = ['snapshot' => 'array'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
