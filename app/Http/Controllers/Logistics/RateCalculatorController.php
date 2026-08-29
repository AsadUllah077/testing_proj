<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsAirRateCard;
use App\Models\LogisticsInlandRateCard;
use App\Models\LogisticsOceanRateCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateCalculatorController extends Controller
{
    // ─── Index Page ───────────────────────────────────────────────

    public function index(): View
    {
        return view('pages.products.logistics.carriers.rate-card.rate-calculator.index');
    }

    // ─── AJAX: fetch inland zones ─────────────────────────────────

    public function zones(): JsonResponse
    {
        $zones = LogisticsInlandRateCard::where('status', 'active')
            ->orderBy('carrier_mou_party')
            ->orderBy('zone_route_description')
            ->get(['id', 'carrier_mou_party', 'zone_route_description'])
            ->map(fn ($z) => [
                'id' => $z->id,
                'zone_route_description' => $z->zone_route_description,
                'carrier_label' => $z->carrier_label,
            ]);

        return response()->json($zones);
    }

    // ─── AJAX: calculate ──────────────────────────────────────────

    public function calculate(Request $request): JsonResponse
    {
        $mode = $request->input('mode');

        return match ($mode) {
            'ocean' => $this->calcOcean($request),
            'air' => $this->calcAir($request),
            'inland' => $this->calcInland($request),
            default => response()->json(['error' => 'Invalid transport mode selected.'], 422),
        };
    }

    // ─── Ocean ────────────────────────────────────────────────────

    private function calcOcean(Request $request): JsonResponse
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $equipment = $request->input('equipment');

        if (! $origin || ! $destination) {
            return response()->json(['error' => 'Origin and destination are required for ocean rates.'], 422);
        }

        $query = LogisticsOceanRateCard::with('carrier')->where('status', 'live')
            ->where('origin_port', $origin)
            ->where('destination_port', $destination);

        if ($equipment) {
            $query->where('equipment', $equipment);
        }

        $records = $query->orderBy('all_in_total')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'error' => "No live ocean rate cards found for {$origin} → {$destination}".
                    ($equipment ? " with equipment: {$equipment}" : '').
                    '. Please add a rate card first or try different filters.',
            ], 404);
        }

        $rates = $records->map(fn ($r) => [
            'id' => $r->id,
            'ref_code' => $r->ref_code,
            'carrier' => $r->carrier,
            'carrier_label' => $r->carrier_label,
            'carrier_color' => $r->carrier_color,
            'rate_type' => $r->rate_type,
            'equipment' => $r->equipment,
            'equipment_label' => $r->equipment_label,
            'origin_port' => $r->origin_port,
            'destination_port' => $r->destination_port,
            'transit_time_days' => $r->transit_time_days,
            'ocean_freight' => (float) $r->ocean_freight,
            'baf' => (float) $r->baf,
            'caf' => (float) $r->caf,
            'eis_isps_other' => (float) $r->eis_isps_other,
            'thc_origin' => (float) $r->thc_origin,
            'thc_destination' => (float) $r->thc_destination,
            'all_in_total' => (float) $r->all_in_total,
            'free_days_demurrage' => $r->free_days_demurrage,
            'valid_from' => $r->valid_from?->format('d M Y'),
            'valid_to' => $r->valid_to?->format('d M Y'),
            'nac_quote_reference' => $r->nac_quote_reference,
        ]);

        return response()->json([
            'best' => $rates->first(),
            'all' => $rates->values(),
        ]);
    }

    // ─── Air ──────────────────────────────────────────────────────

    private function calcAir(Request $request): JsonResponse
    {
        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $weightBreakpoint = $request->input('weight_breakpoint');
        $weightKg = (float) ($request->input('weight_kg') ?: 1);

        if (! $origin || ! $destination) {
            return response()->json(['error' => 'Origin and destination are required for air rates.'], 422);
        }

        $query = LogisticsAirRateCard::where('status', 'live')
            ->where('origin_port', $origin)
            ->where('destination_port', $destination);

        if ($weightBreakpoint) {
            $query->where('weight_breakpoint', $weightBreakpoint);
        }

        $records = $query->orderBy('all_in_per_kg')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'error' => "No live air rate cards found for {$origin} → {$destination}".
                    ($weightBreakpoint ? " ({$weightBreakpoint})" : '').
                    '. Please add a rate card first or try different filters.',
            ], 404);
        }

        $rates = $records->map(function ($r) use ($weightKg) {
            $allInPerKg = (float) $r->all_in_per_kg;
            $rawTotal = $allInPerKg * $weightKg;
            $minCharge = (float) ($r->minimum_charge_usd ?: 0);
            $totalCharge = $minCharge > 0 && $rawTotal < $minCharge ? $minCharge : $rawTotal;

            return [
                'id' => $r->id,
                'ref_code' => $r->ref_code,
                'carrier' => $r->carrier,
                'carrier_label' => $r->carrier_label,
                'carrier_color' => $r->carrier_color,
                'origin_port' => $r->origin_port,
                'destination_port' => $r->destination_port,
                'weight_breakpoint' => $r->weight_breakpoint,
                'weight_breakpoint_label' => $r->weight_breakpoint_label,
                'charge_rate_usd_kg' => (float) $r->charge_rate_usd_kg,
                'fuel_surcharge_fsc' => (float) $r->fuel_surcharge_fsc,
                'security_xray' => (float) $r->security_xray,
                'origin_handling' => (float) $r->origin_handling,
                'all_in_per_kg' => $allInPerKg,
                'minimum_charge_usd' => $minCharge,
                'total_for_weight' => $totalCharge,
                'minimum_applied' => $minCharge > 0 && $rawTotal < $minCharge,
                'valid_from' => $r->rate_valid_from?->format('d M Y'),
                'valid_to' => $r->rate_valid_to?->format('d M Y'),
            ];
        });

        return response()->json([
            'best' => $rates->first(),
            'all' => $rates->values(),
            'weight_kg' => $weightKg,
        ]);
    }

    // ─── Inland ───────────────────────────────────────────────────

    private function calcInland(Request $request): JsonResponse
    {
        $zoneId = $request->input('origin');

        if (! $zoneId) {
            return response()->json(['error' => 'Please select a zone.'], 422);
        }

        $zone = LogisticsInlandRateCard::where('status', 'active')->find($zoneId);

        if (! $zone) {
            return response()->json(['error' => 'Selected zone not found or is no longer active.'], 404);
        }

        $mapZone = fn ($r) => [
            'id' => $r->id,
            'ref_code' => $r->ref_code,
            'carrier_mou_party' => $r->carrier_mou_party,
            'carrier_label' => $r->carrier_label,
            'carrier_color' => $r->carrier_color,
            'zone_route_description' => $r->zone_route_description,
            'example_routes' => $r->example_routes,
            'transit_time' => $r->transit_time,
            'rate_up_to_500kg' => $r->rate_up_to_500kg !== null ? (float) $r->rate_up_to_500kg : null,
            'rate_501_1000kg' => $r->rate_501_1000kg !== null ? (float) $r->rate_501_1000kg : null,
            'rate_1001_5000kg' => $r->rate_1001_5000kg !== null ? (float) $r->rate_1001_5000kg : null,
            'rate_20ft_container' => $r->rate_20ft_container !== null ? (float) $r->rate_20ft_container : null,
            'rate_40ft_container' => $r->rate_40ft_container !== null ? (float) $r->rate_40ft_container : null,
            'fuel_levy_label' => $r->fuel_levy_label,
            'mou_expiry_date' => $r->mou_expiry_date?->format('d M Y'),
        ];

        $all = LogisticsInlandRateCard::where('status', 'active')
            ->where('carrier_mou_party', $zone->carrier_mou_party)
            ->orderBy('zone_route_description')
            ->get()
            ->map($mapZone);

        return response()->json([
            'best' => $mapZone($zone),
            'all' => $all->values(),
        ]);
    }
}
