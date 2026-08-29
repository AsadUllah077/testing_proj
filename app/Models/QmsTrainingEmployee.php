<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmsTrainingEmployee extends Model
{
    protected $table = 'qms_training_employees';

    protected $fillable = [
        'training_id', 'employee_id', 'completion_status', 'certified', 'completed_at',
    ];

    protected $casts = [
        'certified'    => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(QmsTraining::class, 'training_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
