<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class QmsDocument extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'doc_no',
        'title',
        'summary',
        'type',
        'status',
        'revision_cycle',
        'version',
        'purpose',
        'retention',
        'company_id',
        'owner_id',
        'created_by',
        'last_reviewed_at',
        'approved_at',
        'approved_by',
        'controlled_distribution',
        'is_confidential',
        'require_acknowledgment',
    ];

    protected $casts = [
        'controlled_distribution' => 'array',
        'last_reviewed_at'        => 'datetime',
        'approved_at'             => 'datetime',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
        'deleted_at'              => 'datetime',
        'is_confidential'         => 'boolean',
        'require_acknowledgment'  => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments()
    {
        return $this->hasMany(QmsDocumentAttachment::class, 'document_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
