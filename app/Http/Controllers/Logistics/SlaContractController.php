<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\SlaContract\SlaContractRequest;
use App\Models\LogisticsV2Carriers;
use App\Models\SlaContract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SlaContractController extends Controller
{
    /**
     * Shared view data for create / edit forms.
     */
    private function formData(): array
    {
        return [
            'carriers' => LogisticsV2Carriers::forSelect(),            // [id => name] from DB
            'transportModes' => SlaContract::transportModes(),
            'contractTerms' => SlaContract::contractTerms(),
            'rateLockPeriods' => SlaContract::rateLockPeriods(),
            'penaltyClauses' => SlaContract::penaltyClauses(),
            'reviewFrequencies' => SlaContract::reviewFrequencies(),
            'statuses' => SlaContract::statuses(),
            'performanceStatuses' => SlaContract::performanceStatuses(),
        ];
    }

    // ──────────────────────────────────────────────
    //  INDEX   GET /carrier/rate-card/sla-contract
    // ──────────────────────────────────────────────
    public function index(Request $request): View
    {
        $contracts = SlaContract::with('carrier')
            ->search($request->input('search'))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('mode'), fn ($q, $v) => $q->where('transport_mode', $v))
            ->when($request->input('carrier_id'), fn ($q, $v) => $q->where('carrier_id', $v))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('pages.products.logistics.carriers.sla-contract.index', array_merge(
            compact('contracts'),
            ['carriers' => LogisticsV2Carriers::forSelect()]   // for filter dropdown
        ));
    }

    // ──────────────────────────────────────────────
    //  CREATE  GET /carrier/rate-card/sla-contract/create
    // ──────────────────────────────────────────────
    public function create(): View
    {
        return view(
            'pages.products.logistics.carriers.sla-contract.create',
            $this->formData()
        );
    }

    // ──────────────────────────────────────────────
    //  STORE   POST /carrier/rate-card/sla-contract
    // ──────────────────────────────────────────────
    public function store(SlaContractRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['status'] = $data['status'] ?? 'active';
            $data['performance_status'] = $data['performance_status'] ?? 'pending';

            $contract = SlaContract::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Sla Contract create successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────
    //  SHOW    GET /carrier/rate-card/sla-contract/{slaContract}
    // ──────────────────────────────────────────────
    public function show(SlaContract $slaContract): View
    {
        $slaContract->load('carrier');

        return view(
            'pages.products.logistics.carriers.sla-contract.show',
            ['contract' => $slaContract]
        );
    }

    // ──────────────────────────────────────────────
    //  EDIT    GET /carrier/rate-card/sla-contract/{slaContract}/edit
    // ──────────────────────────────────────────────
    public function edit(SlaContract $slaContract): View
    {
        return view(
            'pages.products.logistics.carriers.sla-contract.edit',
            array_merge($this->formData(), ['contract' => $slaContract])
        );
    }

    // ──────────────────────────────────────────────
    //  UPDATE  PUT /carrier/rate-card/sla-contract/{slaContract}
    // ──────────────────────────────────────────────
    public function update(SlaContractRequest $request, SlaContract $slaContract): RedirectResponse
    {
        $slaContract->update($request->validated());

        return redirect()
            ->route('logistics.carriers.sla-contract.show', $slaContract)
            ->with('success', "SLA Contract {$slaContract->contract_id} updated successfully.");
    }

    // ──────────────────────────────────────────────
    //  DESTROY DELETE /carrier/rate-card/sla-contract/{slaContract}
    // ──────────────────────────────────────────────
    public function destroy(SlaContract $slaContract): RedirectResponse
    {
        $id = $slaContract->contract_id;
        $slaContract->delete($id);

        return redirect()
            ->route('logistics.carriers.sla-contract.index')
            ->with('success', "SLA Contract {$id} has been deleted.");
    }
}
