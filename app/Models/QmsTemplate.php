<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category', 'name', 'file_path', 'file_name', 'file_size', 'mime_type',
        'company_id', 'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
