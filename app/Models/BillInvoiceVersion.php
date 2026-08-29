<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BillInvoiceVersion extends Model
{
    use BelongsToCompany;

    protected $guarded = [];
    protected $casts = ['snapshot' => 'array'];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
