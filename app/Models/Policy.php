<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'file_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
