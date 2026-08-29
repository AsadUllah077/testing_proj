<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MassMailerCampaign extends Model
{
    protected $fillable = [
        'template_id',
        'subject',
        'body',
        'status',
        'total_contacts',
        'sent_contacts',
        'failed_contacts'
    ];

    public function template()
    {
        return $this->belongsTo(MassMailerTemplate::class, 'template_id');
    }
}
