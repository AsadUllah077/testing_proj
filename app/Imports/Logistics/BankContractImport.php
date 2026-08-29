<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsLetterOfCredit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankContractImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new LogisticsLetterOfCredit([
            'lc_number'            => LogisticsLetterOfCredit::generateBcNumber(),
            'record_type'          => 'bank_contract',
            'lc_type'              => $row['contract_type'],
            'issuing_bank'         => $row['issuing_bank'],
            'advising_bank'        => $row['advising_bank'],
            'reimbursing_bank'     => $row['reimbursing_bank'],
            'applicant'            => $row['applicant'],
            'beneficiary'          => $row['beneficiary'],
            'currency'             => $row['currency'],
            'amount'               => $row['amount'],
            'tolerance_pct'        => $row['tolerance'],
            'payment_terms'        => $row['payment_terms'],
            'issue_date'           => $row['issue_date_yyyy_mm_dd'],
            'expiry_date'          => $row['expiry_date_yyyy_mm_dd'],
            'place_of_expiry'      => $row['place_of_expiry'],
            'latest_shipment_date' => $row['latest_shipment_date_yyyy_mm_dd'],
            'incoterms'            => $row['incoterms'],
            'port_of_loading'      => $row['port_of_loading'],
            'port_of_discharge'    => $row['port_of_discharge'],
            'goods_description'    => $row['goods_description'],
            'documents_required'   => $row['documents_required_comma_separated'],
            'special_conditions'   => $row['special_conditions'],
            'status'               => 'Active',
            'company_id'           => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);
    }
}
