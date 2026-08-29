<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmsPolicyApproval extends Model
{
    protected $table = 'qms_policy_approvals';

    protected $fillable = ['policy_id', 'version', 'approved_by', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime'];

    public function policy()
    {
        return $this->belongsTo(QmsPolicy::class, 'policy_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
