<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsDocumentRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'requested_by', 'status',
        'due_date', 'assigned_to', 'company_id', 'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
