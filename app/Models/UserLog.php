<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    // use BelongsToCompany;
    protected $fillable = [
        'user_id', 'performed_by', 'action', 'changed_fields'
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public $timestamps = false;

    // Optional relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
