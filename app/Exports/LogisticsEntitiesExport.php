<?php

namespace App\Exports;

use App\Models\LogisticsEntity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogisticsEntitiesExport implements FromCollection, WithHeadings, WithMapping
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
        $query = LogisticsEntity::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(business_legal_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(entity_type) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_full_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(tax_id_vat_number) LIKE ?', ['%' . $search . '%']);
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Business Legal Name',
            'Entity Type',
            'Tax ID / VAT',
            'Registration Country',
            'Contact Name',
            'Contact Email',
            'Contact Phone',
            'Registered City',
            'Registered State',
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->business_legal_name,
            $client->entity_type,
            $client->tax_id_vat_number,
            $client->registration_country,
            $client->contact_full_name,
            $client->contact_email,
            $client->contact_phone_number,
            $client->registered_city,
            $client->registered_state_province,
        ];
    }
}
