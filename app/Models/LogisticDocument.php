<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticDocument extends Model
{
    protected $table = 'logistic_documents';

    protected $fillable = ['name', 'path', 'size', 'mime_type', 'folder_id'];

    public function folder()
    {
        return $this->belongsTo(LogisticFolder::class);
    }
}
