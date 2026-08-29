<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsV2Carriers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\Auth;

class CarriersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    /** Valid enum keys from model constants */
    private array $validModes     = ['ocean_liner', 'air_freight', 'inland_trucking', 'rail_freight', 'multimodal'];
    private array $validServices  = ['fcl_sea', 'lcl_sea', 'air_express', 'air_standard', 'road_ftl', 'road_ltl', 'rail'];
    private array $validTracking  = ['yes_web_portal', 'yes_api', 'yes_both', 'no'];
    private array $validFiata     = ['fiata_member', 'iata_member', 'both', 'none'];
    private array $validInsurance = ['yes_coi', 'yes_no_coi', 'no'];
    private array $validPayment   = ['net_30', 'net_45', 'net_60', 'advance', 'cod'];
    private array $validStatuses  = ['draft', 'pending_approval', 'approved', 'rejected'];

    public function model(array $row)
    {
        if (empty($row['carrier_company_name'])) {
            return null;
        }

        $raw = fn(string $key) => isset($row[$key]) ? trim((string) $row[$key]) : null;

        // Normalise enum fields — fallback to safe default
        $mode     = in_array($raw('transport_modes'), $this->validModes)     ? $raw('transport_modes') : 'ocean_liner';
        $service  = in_array($raw('services_offered'), $this->validServices)  ? $raw('services_offered') : null;
        $tracking = in_array($raw('tracking_portal_api'), $this->validTracking) ? $raw('tracking_portal_api') : 'no';
        $fiata    = in_array($raw('fiata_iata_membership'), $this->validFiata)  ? $raw('fiata_iata_membership') : 'none';
        $insur    = in_array($raw('insurance_marine_liability'), $this->validInsurance) ? $raw('insurance_marine_liability') : 'no';
        $payment  = in_array($raw('payment_terms'), $this->validPayment)       ? $raw('payment_terms') : 'net_30';
        $status   = in_array($raw('status'), $this->validStatuses)             ? $raw('status') : 'pending_approval';

        return new LogisticsV2Carriers([
            // Company Information
            'carrier_company_name'     => $raw('carrier_company_name'),
            'trading_brand_name'       => $raw('trading_brand_name'),
            'transport_modes'          => $mode,
            'country_of_incorporation' => $raw('country_of_incorporation'),
            'pakistan_liaison_office'  => $raw('pakistan_liaison_office'),
            'ntn_scac_code'            => $raw('ntn_scac_code'),
            'contact_person'           => $raw('contact_person'),
            'contact_email'            => $raw('contact_email'),
            'phone_pakistan_office'    => $raw('phone_pakistan_office'),
            'website'                  => $raw('website'),

            // Service Coverage
            'key_origin_ports_airports'  => $raw('key_origin_ports_airports'),
            'key_destination_markets'    => $raw('key_destination_markets'),
            'services_offered'           => $service,
            'tracking_portal_api'        => $tracking,

            // Compliance & Documents
            'fiata_iata_membership'      => $fiata,
            'insurance_marine_liability' => $insur,
            'aeo_ctpat_iso_certification'=> $raw('aeo_ctpat_iso_certification'),
            'payment_terms'              => $payment,
            'credit_limit_usd'           => isset($row['credit_limit_usd']) && is_numeric($row['credit_limit_usd'])
                                               ? (float) $row['credit_limit_usd'] : null,
            'account_manager_internal'   => $raw('account_manager_internal'),
            'notes_onboarding_remarks'   => $raw('notes_onboarding_remarks'),

            // Status & Audit
            'status'     => $status,
            'created_by' => Auth::id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'carrier_company_name'     => 'required|string',
            'transport_modes'          => 'required|string',
            'country_of_incorporation' => 'required|string',
            'contact_person'           => 'required|string',
            'contact_email'            => 'required|email',
            'payment_terms'            => 'required|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'carrier_company_name.required' => 'Row :row: carrier_company_name is required.',
            'contact_email.email'           => 'Row :row: contact_email must be a valid email.',
            'payment_terms.required'        => 'Row :row: payment_terms is required.',
        ];
    }
}
