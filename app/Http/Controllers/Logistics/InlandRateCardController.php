<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsInlandRateCard;
use App\Models\LogisticsV2Carriers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InlandRateCardController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = LogisticsInlandRateCard::with('createdBy')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ref_code', 'like', "%{$s}%")
                    ->orWhere('zone_route_description', 'like', "%{$s}%")
                    ->orWhere('example_routes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('carrier')) {
            $query->where('carrier_mou_party', $request->carrier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rates = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.carriers.rate-card.inland.index', compact('rates'));
    }

    // ─── Create ───────────────────────────────────────────────────

    public function create(): View
    {
        $carriers = LogisticsV2Carriers::where('transport_modes', 'inland_trucking')->latest()->get();

        return view('pages.products.logistics.carriers.rate-card.inland.create', compact('carriers'));
    }

    // ─── Store ────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $status = 'active';

        $carrier = LogisticsV2Carriers::where('id', $request->carrier_id)->first();
        $rate = LogisticsInlandRateCard::create(array_merge($validated, [
            'ref_code' => LogisticsInlandRateCard::generateRefCode($carrier->carrier_company_name),
            'status' => $status,
            'created_by' => Auth::id(),
            'carrier_id' => $request->carrier_id,
            'carrier_mou_party' => ' ',
        ]));

        return redirect()
            ->route('logistics.carriers.rate-card.inland.show', $rate)
            ->with('success', 'Inland zone rate saved successfully.');
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show(LogisticsInlandRateCard $inland): View
    {
        $inland->load('createdBy');

        return view('pages.products.logistics.carriers.rate-card.inland.show', compact('inland'));
    }

    // ─── Edit ─────────────────────────────────────────────────────

    public function edit(LogisticsInlandRateCard $inland): View
    {
        $carriers = LogisticsV2Carriers::where('transport_modes', 'inland_trucking')->latest()->get();

        return view('pages.products.logistics.carriers.rate-card.inland.edit', compact('inland', 'carriers'));
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, LogisticsInlandRateCard $inland): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $status = 'active';

        $inland->update(array_merge($validated, ['status' => $status,   'carrier_id' => $request->carrier_id]));

        return redirect()
            ->route('logistics.carriers.rate-card.inland.show', $inland)
            ->with('success', 'Inland zone rate updated successfully.');
    }

    // ─── Destroy ──────────────────────────────────────────────────

    public function destroy(LogisticsInlandRateCard $inland): RedirectResponse
    {
        $inland->delete();

        return redirect()
            ->route('logistics.carriers.rate-card.inland.index')
            ->with('success', 'Rate card deleted.');
    }

    // ─── Validation Rules ─────────────────────────────────────────

    private function rules(): array
    {
        return [
            'carrier_id' => ['required', 'string', 'exists:logistics_v2_carriers,id'],
            'zone_route_description' => ['required', 'string', 'max:255'],
            'example_routes' => ['nullable', 'string', 'max:255'],
            'transit_time' => ['nullable', 'string', 'max:100'],
            'rate_up_to_500kg' => ['nullable', 'numeric', 'min:0'],
            'rate_501_1000kg' => ['nullable', 'numeric', 'min:0'],
            'rate_1001_5000kg' => ['nullable', 'numeric', 'min:0'],
            'rate_20ft_container' => ['nullable', 'numeric', 'min:0'],
            'rate_40ft_container' => ['nullable', 'numeric', 'min:0'],
            'mou_effective_date' => ['nullable', 'date'],
            'mou_expiry_date' => ['nullable', 'date', 'after_or_equal:mou_effective_date'],
            'fuel_levy_clause' => ['nullable', 'string', Rule::in(array_keys(LogisticsInlandRateCard::FUEL_LEVY_CLAUSES))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
