<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollPreflightOverride extends Model
{
    use BelongsToCompany;
    protected $guarded = [];
    public function payRun() { return $this->belongsTo(PayRun::class); }
    public function overriddenBy() { return $this->belongsTo(User::class, 'overridden_by'); }
}
