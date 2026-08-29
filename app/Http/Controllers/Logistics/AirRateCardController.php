<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsAirRateCard;
use App\Models\LogisticsV2Carriers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AirRateCardController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = LogisticsAirRateCard::with('createdBy')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ref_code', 'like', "%{$s}%")
                    ->orWhere('origin_port', 'like', "%{$s}%")
                    ->orWhere('destination_port', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('carrier')) {
            $query->where('carrier', $request->carrier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('weight_breakpoint')) {
            $query->where('weight_breakpoint', $request->weight_breakpoint);
        }

        $rates = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.carriers.rate-card.air.index', compact('rates'));
    }

    // ─── Create ───────────────────────────────────────────────────

    public function create(): View
    {
        $carriers = LogisticsV2Carriers::where('transport_modes', 'air_freight')->latest()->get();

        return view('pages.products.logistics.carriers.rate-card.air.create', compact('carriers'));
    }

    // ─── Store ────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $allIn = $this->computeAllIn($validated);
        $status = $request->input('action') === 'draft' ? 'draft' : 'live';

        $carrier = LogisticsV2Carriers::where('id', $request->carrier_id)->first();
        $rate = LogisticsAirRateCard::create(array_merge($validated, [
            'ref_code' => LogisticsAirRateCard::generateRefCode($carrier->carrier_company_name),
            'all_in_per_kg' => $allIn,
            'status' => $status,
            'created_by' => Auth::id(),
            'carrier_id' => $carrier->id,
            'carrier' => ' ',
        ]));

        return redirect()
            ->route('logistics.carriers.rate-card.air.show', $rate)
            ->with('success', 'Air rate card saved successfully.');
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show(LogisticsAirRateCard $air): View
    {
        $air->load('createdBy');

        return view('pages.products.logistics.carriers.rate-card.air.show', compact('air'));
    }

    // ─── Edit ─────────────────────────────────────────────────────

    public function edit(LogisticsAirRateCard $air): View
    {
        return view('pages.products.logistics.carriers.rate-card.air.edit', compact('air'));
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, LogisticsAirRateCard $air): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $allIn = $this->computeAllIn($validated);
        $status = $request->input('action') === 'draft' ? 'draft' : 'live';

        $air->update(array_merge($validated, [
            'all_in_per_kg' => $allIn,
            'status' => $status,
            'carrier_id' => $request->carrier_id,
        ]));

        return redirect()
            ->route('logistics.carriers.rate-card.air.show', $air)
            ->with('success', 'Air rate card updated successfully.');
    }

    // ─── Destroy ──────────────────────────────────────────────────

    public function destroy(LogisticsAirRateCard $air): RedirectResponse
    {
        $air->delete();

        return redirect()
            ->route('logistics.carriers.rate-card.air.index')
            ->with('success', 'Air rate card deleted.');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function computeAllIn(array $data): float
    {
        return array_sum([
            $data['charge_rate_usd_kg'] ?? 0,
            $data['fuel_surcharge_fsc'] ?? 0,
            $data['security_xray'] ?? 0,
            $data['origin_handling'] ?? 0,
        ]);
    }

    private function rules(): array
    {
        return [
            'carrier_id' => ['required', 'string', 'exists:logistics_v2_carriers,id'],
            'origin_port' => ['required', 'string', Rule::in(array_keys(LogisticsAirRateCard::ORIGIN_PORTS))],
            'destination_port' => ['required', 'string', Rule::in(array_keys(LogisticsAirRateCard::DESTINATION_PORTS))],
            'via_hub' => ['nullable', 'string', 'max:10'],
            'weight_breakpoint' => ['required', 'string', Rule::in(array_keys(LogisticsAirRateCard::WEIGHT_BREAKPOINTS))],
            'charge_rate_usd_kg' => ['required', 'numeric', 'min:0'],
            'fuel_surcharge_fsc' => ['nullable', 'numeric', 'min:0'],
            'security_xray' => ['nullable', 'numeric', 'min:0'],
            'origin_handling' => ['nullable', 'numeric', 'min:0'],
            'minimum_charge_usd' => ['nullable', 'numeric', 'min:0'],
            'source_document' => ['nullable', 'string', Rule::in(array_keys(LogisticsAirRateCard::SOURCE_DOCUMENTS))],
            'notes' => ['nullable', 'string'],
            'rate_valid_from' => ['nullable', 'date'],
            'rate_valid_to' => ['required', 'date', 'after_or_equal:rate_valid_from'],
        ];
    }
}
