<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsPolicyAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'policy_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'company_id',
        'created_by',
    ];

    public function policy()
    {
        return $this->belongsTo(QmsPolicy::class, 'policy_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
