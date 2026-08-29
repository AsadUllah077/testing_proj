<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MassMailerTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body'];
}
