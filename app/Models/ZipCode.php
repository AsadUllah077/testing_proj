<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    protected $table = 'zip_codes';

    protected $fillable = [
        'country_code',
        'zipcode',
        'city',
        'state',
    ];
}
