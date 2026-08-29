<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    use LogsActivity;

    protected $table = 'hs_codes';

    protected $fillable = [
        'company_id',
        'hs_code',
        'description',
        'cd',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }


}
