<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsOceanRateCard;
use App\Models\LogisticsV2Carriers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OceanRateCardController extends Controller
{
    public function index(Request $request): View
    {
        $query = LogisticsOceanRateCard::with('createdBy')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ref_code', 'like', "%{$s}%")
                    ->orWhere('nac_quote_reference', 'like', "%{$s}%")
                    ->orWhere('origin_port', 'like', "%{$s}%")
                    ->orWhere('destination_port', 'like', "%{$s}%");
            });
        }

        if ($request->filled('carrier')) {
            $query->where('carrier', $request->carrier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rate_type')) {
            $query->where('rate_type', $request->rate_type);
        }

        $rates = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.carriers.rate-card.index', compact('rates'));
    }

    // ─── Create ───────────────────────────────────────────────────

    public function create(): View
    {
        $carriers = LogisticsV2Carriers::where('transport_modes', 'ocean_liner')->latest()->get();

        return view('pages.products.logistics.carriers.rate-card.create', compact('carriers'));
    }

    // ─── Store ────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $allIn = $this->computeAllIn($validated);

        $status = $request->input('action') === 'draft' ? 'draft' : 'live';

        $rate = LogisticsOceanRateCard::create(array_merge($validated, [
            'ref_code' => LogisticsOceanRateCard::generateRefCode(),
            'all_in_total' => $allIn,
            'status' => $status,
            'created_by' => Auth::id(),
            'carrier_id' => $request->carrier_id,
        ]));

        return redirect()
            ->route('logistics.carriers.rate-card.ocean.show', $rate)
            ->with('success', 'Ocean rate card saved successfully.');
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show(LogisticsOceanRateCard $ocean): View
    {
        $ocean->load('createdBy');

        return view('pages.products.logistics.carriers.rate-card.show', compact('ocean'));
    }

    // ─── Edit ─────────────────────────────────────────────────────

    public function edit(LogisticsOceanRateCard $ocean): View
    {
        return view('pages.products.logistics.carriers.rate-card.edit', compact('ocean'));
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, LogisticsOceanRateCard $ocean): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $allIn = $this->computeAllIn($validated);
        $status = $request->input('action') === 'draft' ? 'draft' : 'live';

        $ocean->update(array_merge($validated, [
            'all_in_total' => $allIn,
            'status' => $status,
            'carrier_id' => $request->carrier_id,
        ]));

        return redirect()
            ->route('logistics.carriers.rate-card.ocean.show', $ocean)
            ->with('success', 'Ocean rate card updated successfully.');
    }

    // ─── Destroy ──────────────────────────────────────────────────

    public function destroy(LogisticsOceanRateCard $ocean): RedirectResponse
    {
        $ocean->delete();

        return redirect()
            ->route('logistics.carriers.rate-card.ocean.index')
            ->with('success', 'Rate card deleted.');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function computeAllIn(array $data): float
    {
        return array_sum([
            $data['ocean_freight'] ?? 0,
            $data['baf'] ?? 0,
            $data['caf'] ?? 0,
            $data['eis_isps_other'] ?? 0,
            $data['thc_origin'] ?? 0,
            $data['thc_destination'] ?? 0,
        ]);
    }

    private function rules(): array
    {
        return [
            // Source & Carrier
            'carrier_id' => ['required', 'string', 'exists:logistics_v2_carriers,id'],
            'rate_type' => ['required', 'string', Rule::in(array_keys(LogisticsOceanRateCard::RATE_TYPES))],
            'nac_quote_reference' => ['nullable', 'string', 'max:100'],
            'source_document' => ['nullable', 'string', Rule::in(array_keys(LogisticsOceanRateCard::SOURCE_DOCUMENTS))],

            // Route & Equipment
            'origin_port' => ['required', 'string', Rule::in(array_keys(LogisticsOceanRateCard::ORIGIN_PORTS))],
            'destination_port' => ['required', 'string', Rule::in(array_keys(LogisticsOceanRateCard::DESTINATION_PORTS))],
            'equipment' => ['required', 'string', Rule::in(array_keys(LogisticsOceanRateCard::EQUIPMENT))],
            'transit_time_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Rate Components
            'ocean_freight' => ['required', 'numeric', 'min:0'],
            'baf' => ['nullable', 'numeric', 'min:0'],
            'caf' => ['nullable', 'numeric', 'min:0'],
            'eis_isps_other' => ['nullable', 'numeric', 'min:0'],
            'thc_origin' => ['nullable', 'numeric', 'min:0'],
            'thc_destination' => ['nullable', 'numeric', 'min:0'],
            'free_days_demurrage' => ['nullable', 'integer', 'min:0'],
            'demurrage_rate_after_free' => ['nullable', 'numeric', 'min:0'],
            'free_days_detention' => ['nullable', 'integer', 'min:0'],
            'detention_rate_after_free' => ['nullable', 'numeric', 'min:0'],

            // Validity
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'agreed_volume_teu_month' => ['nullable', 'integer', 'min:0'],
            'remarks_gri_pss_notes' => ['nullable', 'string'],
        ];
    }
}
