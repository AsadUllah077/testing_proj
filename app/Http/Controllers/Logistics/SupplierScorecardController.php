<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsSupplierV2;
use App\Models\LogisticsSupplierScorecard;
use Illuminate\Http\Request;

class SupplierScorecardController extends Controller
{
    public function index(Request $request)
    {
        $scorecards = LogisticsSupplierScorecard::with('supplier')->latest()->get();

        // Calculate YoY change mock logic or real if multiple years exist
        $scorecards->map(function($item) {
            $previous = LogisticsSupplierScorecard::where('supplier_id', $item->supplier_id)
                ->where('id', '<', $item->id)
                ->latest()
                ->first();
            
            if ($previous) {
                $diff = $item->overall_score - $previous->overall_score;
                $item->yoy_change = ($diff >= 0 ? '+' : '') . $diff;
                $item->yoy_status = $diff > 0 ? 'up' : ($diff < 0 ? 'dn' : 'neut');
            } else {
                $item->yoy_change = '0';
                $item->yoy_status = 'neut';
            }
            return $item;
        });

        return view('pages.products.logistics.suppliers.scorecard.index', compact('scorecards'));
    }

    public function create()
    {
        $suppliers = LogisticsSupplierV2::all();
        return view('pages.products.logistics.suppliers.scorecard.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:logistics_suppliers_v2,id',
            'evaluation_period' => 'required|string',
            'quality_score' => 'required|integer|min:0|max:100',
            'delivery_otd' => 'required|integer|min:0|max:100',
            'doc_compliance' => 'required|integer|min:0|max:100',
            'communication' => 'required|integer|min:1|max:5',
            'price_competitiveness' => 'required|integer|min:1|max:5',
            'remarks' => 'nullable|string',
        ]);

        LogisticsSupplierScorecard::create($validated);

        return redirect()->route('logistics.suppliers.scorecard.index')->with('success', 'Scorecard created successfully!');
    }

    public function edit($id)
    {
        $scorecard = LogisticsSupplierScorecard::findOrFail($id);
        $suppliers = LogisticsSupplierV2::all();
        return view('pages.products.logistics.suppliers.scorecard.edit', compact('scorecard', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $scorecard = LogisticsSupplierScorecard::findOrFail($id);
        
        $validated = $request->validate([
            'supplier_id' => 'required|exists:logistics_suppliers_v2,id',
            'evaluation_period' => 'required|string',
            'quality_score' => 'required|integer|min:0|max:100',
            'delivery_otd' => 'required|integer|min:0|max:100',
            'doc_compliance' => 'required|integer|min:0|max:100',
            'communication' => 'required|integer|min:1|max:5',
            'price_competitiveness' => 'required|integer|min:1|max:5',
            'remarks' => 'nullable|string',
        ]);

        $scorecard->update($validated);

        return redirect()->route('logistics.suppliers.scorecard.index')->with('success', 'Scorecard updated successfully!');
    }
}
