<?php

namespace App\Exports;

use App\Models\LogisticsBooking;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LogisticsBookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected string $mode;

    public function __construct(string $mode = 'Inland')
    {
        $this->mode = $mode;
    }

    public function collection()
    {
        $query = LogisticsBooking::with(['carrier', 'events'])->orderBy('created_at', 'desc');

        if ($this->mode === 'Inland') {
            $query->whereIn('service_type', ['Inland', 'LTL', 'FTL']);
        } else {
            $query->where('service_type', $this->mode);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Shipment ID',
            'Carrier',
            'Service Type',
            'Status',
            'Origin',
            'Destination',
            'Pick Date',
            'ETA',
            'Booking Date',
            'Carrier Rate',
            'Second Party',
            'Third Party',
            'Created At',
        ];
    }

    public function map($booking): array
    {
        $today    = now()->startOfDay();
        $pickDate = $booking->pick_date ? Carbon::parse($booking->pick_date)->startOfDay() : null;
        $etaDate  = $booking->eta       ? Carbon::parse($booking->eta)->startOfDay()       : null;

        if ($booking->is_draft) {
            $status = 'Draft';
        } elseif (!$pickDate || $today->lt($pickDate)) {
            $status = 'Booked';
        } elseif ($today->eq($pickDate)) {
            $status = 'Picked Up';
        } elseif (!$etaDate || $today->lt($etaDate)) {
            $status = 'In Transit';
        } else {
            $status = 'Delivered';
        }

        return [
            'SHP-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            $booking->carrier?->legal_carrier_name ?? 'TBD',
            $booking->service_type,
            $status,
            $booking->origin_company ?: ($booking->origin_country . ' ' . $booking->origin_postal),
            $booking->dest_company   ?: ($booking->dest_country   . ' ' . $booking->dest_postal),
            $booking->pick_date    ? $booking->pick_date->format('M d, Y')    : '—',
            $booking->eta          ? $booking->eta->format('M d, Y')          : '—',
            $booking->loading_date ? $booking->loading_date->format('M d, Y') : '—',
            $booking->carrier_rate ? '$' . number_format($booking->carrier_rate, 2) : '—',
            $booking->second_party ?? '—',
            $booking->third_party  ?? '—',
            $booking->created_at->format('M d, Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF0078C8']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
