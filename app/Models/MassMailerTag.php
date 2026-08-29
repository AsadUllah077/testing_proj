<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MassMailerTag extends Model
{
    protected $fillable = ['title'];

    public function contacts()
    {
        return $this->belongsToMany(MassMailerContact::class, 'mass_mailer_contact_tag', 'tag_id', 'contact_id');
    }
}
