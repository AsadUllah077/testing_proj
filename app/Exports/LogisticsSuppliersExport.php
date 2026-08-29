<?php

namespace App\Exports;

use App\Models\LogisticsSupplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogisticsSuppliersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = LogisticsSupplier::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_company_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(primary_contact_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(official_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(country_of_registration) LIKE ?', ['%' . $search . '%']);
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company Name',
            'Tax ID',
            'Business Type',
            'Country',
            'Contact Name',
            'Email',
            'Address',
            'Business Type',
            'Incoterms',
            'Port',
            'Lead Time',
            'Currency',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->id,
            $supplier->legal_company_name,
            $supplier->tax_id,
            $supplier->business_type,
            $supplier->country_of_registration,
            $supplier->primary_contact_name,
            $supplier->official_email,
            $supplier->registered_office_address,
            $supplier->business_type,
            $supplier->default_incoterms,
            $supplier->pref_port_of_loading,
            $supplier->lead_time_days,
            $supplier->payment_currency,
        ];
    }
}
