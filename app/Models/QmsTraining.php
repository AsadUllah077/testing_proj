<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QmsTraining extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qms_trainings';

    protected $fillable = [
        'title', 'description', 'linked_sop_id', 'linked_policy_id',
        'status', 'certification_enabled', 'certification_criteria',
        'has_assessment', 'company_id', 'created_by',
    ];

    protected $casts = [
        'certification_enabled' => 'boolean',
        'has_assessment'        => 'boolean',
    ];

    public function sop()
    {
        return $this->belongsTo(QmsSop::class, 'linked_sop_id');
    }

    public function policy()
    {
        return $this->belongsTo(QmsPolicy::class, 'linked_policy_id');
    }

    public function employees()
    {
        return $this->hasMany(QmsTrainingEmployee::class, 'training_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
