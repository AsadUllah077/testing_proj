<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsCapa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qms_capas';

    protected $fillable = [
        'capa_number', 'title', 'description', 'root_cause',
        'linked_product', 'linked_sop_id', 'assigned_to',
        'status', 'deadline', 'resolution_notes',
        'company_id', 'created_by', 'closed_at',
    ];

    protected $casts = [
        'deadline'  => 'date',
        'closed_at' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function sop()
    {
        return $this->belongsTo(QmsSop::class, 'linked_sop_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($capa) {
            if (empty($capa->capa_number)) {
                $count = static::withTrashed()->count() + 1;
                $capa->capa_number = 'CAPA-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
