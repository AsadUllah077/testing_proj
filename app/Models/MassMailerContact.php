<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MassMailerContact extends Model
{
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'address',
        'phone',
        'company',
        'position',
        'city',
        'state',
        'country',
        'zipcode',
        'note'
    ];

    public function tags()
    {
        return $this->belongsToMany(MassMailerTag::class, 'mass_mailer_contact_tag', 'contact_id', 'tag_id');
    }
}
