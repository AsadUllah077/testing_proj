<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class UserLoginLogs extends Model
{

    protected $table = 'user_login_logs';


    protected $guarded = [];
}
