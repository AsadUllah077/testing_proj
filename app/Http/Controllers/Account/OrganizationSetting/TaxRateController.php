<?php

namespace App\Http\Controllers\Account\OrganizationSetting;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRate\TaxRateCreateRequest;
use App\Models\ActivityLog;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 20;

        $taxRates = TaxRate::when($search, function ($query) use ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('tax_rate', 'LIKE', "%{$search}%")
                    ->orWhere('tax_type', 'LIKE', "%{$search}%");
            });
        })
            ->sortable()
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $data = [
            'taxRates' => $taxRates,
            'perPage' => $perPage,
            'search' => $search,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.organization-settings.tax-rate.table', $data)->render(),
                'pagination' => $taxRates->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.organization-settings.tax-rate.index', $data);
    }


    public function store(TaxRateCreateRequest $request)
    {
        try {
            $validate = $request->validated();
            $taxRate = TaxRate::create([
                'company_id' => $validate['company_id'],
                'name' => $validate['name'],
                'code' => $validate['code'],
                'tax_rate' => $validate['tax_rate'],
                'tax_type' => $validate['tax_type'],
            ]);

            $taxRate->logChange(
                event: 'created',
                old: null,
                new: $taxRate->toArray()
            );

            return response()->json([
                'message' => ['Tax Rate created successfully'],
                'success' => true,
                'tax_rate' => $taxRate,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => [$th->getMessage()]]);
        }
    }


    public function edit($id)
    {
        try {
            return response()->json([
                'success' => true,
                'tax_rate' => TaxRate::findOrFail($id)
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => [$th->getMessage()]]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $taxRate = TaxRate::findOrFail($id);

            $old = $taxRate->toArray();

            $taxRate->update($request->all());

            $taxRate->logChange(
                event: 'updated',
                old: $old,
                new: $taxRate->toArray()
            );

            return response()->json(['success' => true, 'message' => ['Tax Rate updated successfully']]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => [$th->getMessage()]]);
        }
    }


    public function destroy($id)
    {
        try {
            $taxRate = TaxRate::findOrFail($id);

            $old = $taxRate->toArray();

            $taxRate->logChange(
                event: 'deleted',
                old: $old,
                new: null
            );

            $taxRate->delete();

            return response()->json(['success' => true, 'message' => ['Tax Rate deleted successfully']]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => [$th->getMessage()]]);
        }
    }

    public function logs($id)
    {
        try {
            $taxRate = TaxRate::findOrFail($id);

            $logs = $taxRate->logs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.organization-settings.tax-rate.logs', compact('taxRate', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function allLogs()
    {
        try {
            $logs = ActivityLog::where('loggable_type', 'App\Models\TaxRate')->latest()->paginate(20);
            return view('pages.activity_logs.index', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
