<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }
}
