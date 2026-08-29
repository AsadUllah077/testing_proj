<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use  LogsActivity, SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/'.$this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        $size = $this->file_size;
        if ($size >= 1073741824) {
            $size = number_format($size / 1073741824, 2).' GB';
        } elseif ($size >= 1048576) {
            $size = number_format($size / 1048576, 2).' MB';
        } elseif ($size >= 1024) {
            $size = number_format($size / 1024, 2).' KB';
        } else {
            $size = $size.' bytes';
        }

        return $size;
    }
}
