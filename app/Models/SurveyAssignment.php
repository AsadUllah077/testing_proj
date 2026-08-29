<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyAssignment extends Model
{
    protected $guarded = [];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function assignable()
    {
        // Dynamic relation helper
        if ($this->assignable_type === 'Employee') {
            return $this->belongsTo(Employee::class, 'assignable_id');
        } elseif ($this->assignable_type === 'Department') {
            return $this->belongsTo(Department::class, 'assignable_id');
        } elseif ($this->assignable_type === 'Team') {
            return $this->belongsTo(Team::class, 'assignable_id');
        }
        return null;
    }
}
