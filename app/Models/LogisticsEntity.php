<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsEntity extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected $table = 'logistics_entities';

    protected $fillable = [
        'company_id',
        'business_legal_name',
        'entity_type',
        'tax_id_vat_number',
        'registration_country',
        'contact_full_name',
        'contact_email',
        'contact_phone_number',
        'registered_street_address',
        'registered_city',
        'registered_state_province',
        'registered_postal_code',
        'mailing_street_address',
        'mailing_city',
        'mailing_state_province',
        'mailing_postal_code',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
