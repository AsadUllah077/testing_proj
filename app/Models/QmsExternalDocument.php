<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsExternalDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ref_no', 'title', 'source', 'status', 'expiry_date',
        'owner_id', 'file_path', 'file_name', 'company_id', 'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
