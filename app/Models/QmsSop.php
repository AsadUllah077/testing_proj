<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsSop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qms_sops';

    protected $fillable = [
        'sop_number', 'title', 'description', 'department', 'linked_product',
        'version', 'status', 'owner_id', 'reviewer_id', 'company_id',
        'created_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(QmsSopStep::class, 'sop_id')->orderBy('order');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($sop) {
            if (empty($sop->sop_number)) {
                $count = static::withTrashed()->count() + 1;
                $sop->sop_number = 'SOP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
