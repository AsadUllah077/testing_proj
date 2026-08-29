<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LogisticsSupplier extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $table = 'logistics_suppliers';

    protected $fillable = [
        'company_id',
        'legal_company_name',
        'tax_id',
        'business_type',
        'country_of_registration',
        'primary_contact_name',
        'official_email',
        'registered_office_address',
        'default_incoterms',
        'pref_port_of_loading',
        'lead_time_days',
        'iban',
        'swift_bic',
        'payment_currency',
        'business_license_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
