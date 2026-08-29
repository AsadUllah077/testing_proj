<?php

namespace App\Exports;

use App\Models\LogisticsCarrier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogisticsCarriersExport implements FromCollection, WithHeadings, WithMapping
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
        $query = LogisticsCarrier::query();

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_carrier_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(logistics_mode) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(scac_iata_code) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(dot_number) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(mc_authority) LIKE ?', ['%' . $search . '%']);
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cannot Name',
            'Logistics Mode',
            'SCAC / IATA',
            'DOT Number',
            'MC Authority',
            'Safety Rating',
        ];
    }

    public function map($carrier): array
    {
        return [
            $carrier->id,
            $carrier->legal_carrier_name,
            $carrier->logistics_mode,
            $carrier->scac_iata_code,
            $carrier->dot_number,
            $carrier->mc_authority,
            $carrier->safety_rating,
        ];
    }
}
