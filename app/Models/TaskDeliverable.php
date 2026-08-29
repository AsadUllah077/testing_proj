<?php
// app/Models/TaskDeliverable.php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TaskDeliverable extends Model
{
    use SoftDeletes,LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the task associated with the deliverable.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who submitted this deliverable.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get file URL if exists.
     */
    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return null;

        $bytes = (int)$this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if deliverable is a file.
     */
    public function isFile()
    {
        return $this->type === 'file';
    }

    /**
     * Check if deliverable is text.
     */
    public function isText()
    {
        return $this->type === 'text';
    }
}
