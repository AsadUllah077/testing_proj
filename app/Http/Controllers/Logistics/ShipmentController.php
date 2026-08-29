<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsBookingV2;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticsBookingV2::query()->with('hsCode', 'carrier')->latest();
        $this->applyFilters($query, $request);
        $shipments = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => LogisticsBookingV2::count(),
            'imports' => LogisticsBookingV2::where('booking_type', 'import')->count(),
            'exports' => LogisticsBookingV2::where('booking_type', 'export')->count(),
            'delayed' => LogisticsBookingV2::where('status', 'Doc Missing')->count(),
        ];

        return view('pages.products.logistics.shipment.index', compact('shipments', 'stats'));
    }

    public function imports(Request $request)
    {
        $query = LogisticsBookingV2::where('booking_type', 'import')->latest();
        $this->applyFilters($query, $request);
        $shipments = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => LogisticsBookingV2::where('booking_type', 'import')->count(),
            'in_transit' => LogisticsBookingV2::where('booking_type', 'import')->where('status', 'In-Transit')->count(),
            'confirmed' => LogisticsBookingV2::where('booking_type', 'import')->where('status', 'Confirmed')->count(),
            'pending' => LogisticsBookingV2::where('booking_type', 'import')->where('status', 'Pending Confirm')->count(),
        ];

        return view('pages.products.logistics.shipment.imports', compact('shipments', 'stats'));
    }

    public function exports(Request $request)
    {
        $query = LogisticsBookingV2::where('booking_type', 'export')->latest();
        $this->applyFilters($query, $request);
        $shipments = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => LogisticsBookingV2::where('booking_type', 'export')->count(),
            'in_transit' => LogisticsBookingV2::where('booking_type', 'export')->where('status', 'In-Transit')->count(),
            'confirmed' => LogisticsBookingV2::where('booking_type', 'export')->where('status', 'Confirmed')->count(),
            'pending' => LogisticsBookingV2::where('booking_type', 'export')->where('status', 'Pending Confirm')->count(),
        ];

        return view('pages.products.logistics.shipment.exports', compact('shipments', 'stats'));
    }

    public function delayed(Request $request)
    {
        $query = LogisticsBookingV2::where('status', 'Doc Missing')->latest();
        $this->applyFilters($query, $request);
        $shipments = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.shipment.delayed', compact('shipments'));
    }

    public function pickup(Request $request)
    {
        return view('pages.products.logistics.shipment.pickup');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('freight_mode')) {
            $query->where('freight_mode', $request->freight_mode);
        }
        if ($request->filled('etd_from')) {
            $query->whereDate('etd', '>=', $request->etd_from);
        }
        if ($request->filled('etd_to')) {
            $query->whereDate('etd', '<=', $request->etd_to);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'ilike', "%{$search}%")
                    ->orWhere('shipper', 'ilike', "%{$search}%")
                    ->orWhere('consignee', 'ilike', "%{$search}%")
                    ->orWhere('carrier', 'ilike', "%{$search}%")
                    ->orWhere('origin_port', 'ilike', "%{$search}%")
                    ->orWhere('dest_port', 'ilike', "%{$search}%");
            });
        }
    }
}
