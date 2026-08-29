<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LogisticsCarrier extends Model
{
    use BelongsToCompany, LogsActivity;

    protected $table = 'logistics_carriers';

    protected $fillable = [
        'company_id',
        'legal_carrier_name',
        'logistics_mode',
        'scac_iata_code',
        'dot_number',
        'mc_authority',
        'safety_rating',
        'liability_insurance_path',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
