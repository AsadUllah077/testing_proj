<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsSupplierV2;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class SuppliersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip completely empty rows
        if (empty($row['company_name'])) {
            return null;
        }

        // Normalise status — only allow known values
        $allowedStatuses = ['Approved', 'Pending', 'Suspended', 'Under Review'];
        $rawStatus = isset($row['status']) ? trim($row['status']) : 'Pending';
        $status = in_array($rawStatus, $allowedStatuses) ? $rawStatus : 'Pending';

        return new LogisticsSupplierV2([
            'supplier_code'       => LogisticsSupplierV2::generateSupplierId(),

            // Company details
            'company_name'        => trim($row['company_name']),
            'trade_name'          => $row['trade_name'] ?? null,
            'business_type'       => $row['business_type'] ?? 'Manufacturer',
            'category'            => $row['category'] ?? 'General',
            'country'             => $row['country'] ?? null,
            'city'                => $row['city'] ?? null,
            'registration_no'     => $row['registration_no_ntn'] ?? ($row['registration_no'] ?? null),
            'strn_no'             => $row['strn_no'] ?? null,
            'year_established'    => isset($row['year_established']) && is_numeric($row['year_established'])
                                        ? (int) $row['year_established'] : null,
            'annual_value'        => isset($row['annual_value_usd']) && is_numeric($row['annual_value_usd'])
                                        ? (float) $row['annual_value_usd'] : null,

            // Key contacts
            'primary_contact'     => $row['primary_contact'] ?? null,
            'designation'         => $row['designation'] ?? null,
            'email'               => $row['email'] ?? null,
            'phone'               => $row['phone'] ?? null,
            'finance_contact'     => $row['finance_contact'] ?? null,
            'logistics_contact'   => $row['logistics_contact'] ?? null,

            // Commercial terms
            'incoterms'           => $row['incoterms'] ?? 'FOB',
            'payment_terms'       => $row['payment_terms'] ?? null,
            'currency'            => $row['currency'] ?? 'USD',
            'credit_limit'        => isset($row['credit_limit_usd']) && is_numeric($row['credit_limit_usd'])
                                        ? (float) $row['credit_limit_usd'] : null,
            'moq'                 => $row['moq'] ?? null,
            'lead_time'           => isset($row['lead_time_days']) && is_numeric($row['lead_time_days'])
                                        ? (int) $row['lead_time_days'] : null,

            // Compliance
            'chamber_membership'  => $row['chamber_membership'] ?? null,
            'export_license'      => $row['export_license'] ?? null,
            'quality_certs'       => $row['quality_certs'] ?? null,
            'bank_account'        => $row['bank_account'] ?? null,
            'bank_reference'      => $row['bank_reference'] ?? null,
            'aml_status'          => $row['aml_status'] ?? 'Pending Screening',
            'internal_notes'      => $row['internal_notes'] ?? null,

            // Status — validated against allowed values
            'status'              => $status,

            'company_id'          => auth()->check() ? (int) auth()->user()->company_id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name'   => 'required|string',
            'business_type'  => 'required|string',
            'category'       => 'required|string',
            'country'        => 'required|string',
            'primary_contact'=> 'required|string',
            'email'          => 'required|email',
            'phone'          => 'required|string',
            'incoterms'      => 'required|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'company_name.required'    => 'Row :row: company_name is required.',
            'email.email'              => 'Row :row: email must be a valid email address.',
            'incoterms.required'       => 'Row :row: incoterms is required.',
        ];
    }
}
