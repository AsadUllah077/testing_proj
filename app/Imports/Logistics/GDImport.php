<?php

namespace App\Imports\Logistics;

use App\Models\LogisticsGoodsDeclaration;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GDImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $n = fn($v) => is_numeric($v) ? $v : null;

        return new LogisticsGoodsDeclaration([
            'gd_number'         => LogisticsGoodsDeclaration::generateGdNumber(),
            'declaration_type'  => $row['declaration_type'],
            'collectorate'      => $row['collectorate'],
            'bl_awb_number'     => $row['bl_awb_number'],
            'importer'          => $row['importer'],
            'ntn_cnic'          => $row['ntn_cnic'],
            'exporter'          => $row['exporter'],
            'country_of_origin' => $row['country_of_origin'],
            'hs_code'           => $row['hs_code'],
            'description'       => $row['description'],
            'quantity'          => $n($row['quantity']),
            'unit'              => $row['unit'],
            'gross_weight'      => $n($row['gross_weight']),
            'net_weight'        => $n($row['net_weight']),
            'customs_value'     => $row['customs_value'],
            'currency'          => $row['currency'] ?? 'USD',
            'exchange_rate'     => $row['exchange_rate'],
            'customs_duty'      => $n($row['customs_duty']),
            'additional_duty'   => $n($row['additional_duty']),
            'sales_tax'         => $n($row['sales_tax']),
            'income_tax'        => $n($row['income_tax']),
            'total_duty'        => $n($row['total_duty']),
            'duty_payment_mode' => $row['duty_payment_mode'],
            'landed_cost'       => $n($row['landed_cost']),
            'status'            => 'Draft',
            'exam_status'       => 'no_exam',
            'filed_date'        => $row['filed_date_yyyy_mm_dd'],
            'remarks'           => $row['remarks'],
            'company_id'        => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);
    }
}
