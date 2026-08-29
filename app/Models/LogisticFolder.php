<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticFolder extends Model
{
    protected $table = 'logistic_folders';

    protected $fillable = ['name', 'parent_id'];

    public function documents()
    {
        return $this->hasMany(LogisticDocument::class, 'folder_id');
    }

    public function parent()
    {
        return $this->belongsTo(LogisticFolder::class, 'parent_id');
    }

    public function subfolders()
    {
        return $this->hasMany(LogisticFolder::class, 'parent_id');
    }
}
