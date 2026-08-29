<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmsSopStep extends Model
{
    protected $table = 'qms_sop_steps';

    protected $fillable = [
        'sop_id', 'order', 'title', 'description', 'media_path', 'has_checklist',
    ];

    protected $casts = [
        'has_checklist' => 'boolean',
    ];

    public function sop()
    {
        return $this->belongsTo(QmsSop::class, 'sop_id');
    }
}
