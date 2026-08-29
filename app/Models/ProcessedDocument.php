<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'file_name',
        'file_path',
        'document_type',
        'extracted_data',
        'status',
        'reference_id',
        'reference_type',
        'created_by',
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
