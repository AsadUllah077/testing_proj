<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'type', 'email', 'phone',
        'address', 'city', 'state', 'country', 'postal_code',
        'website', 'industry', 'contact_person', 'contact_email',
        'contact_phone', 'notes', 'status', 'created_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
