<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'state_id',
        'currency_id',
        'name',
        'phone',
        'mobile',
        'email',
        'logo',
        'signature',
        'favicon',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
    public function taxRates()
    {
        return $this->hasMany(TaxRate::class, 'company_id');
    }

    public function statutoryBodies()
    {
        return $this->hasMany(StatutoryBody::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
