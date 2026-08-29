<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsCarrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CarrierController extends Controller
{
    public function index(Request $request)
    {
        $carriers = LogisticsCarrier::query();
        $search = strtolower($request->search);

        if ($request->has('search') && $request->search != '') {
            $carriers->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_carrier_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(logistics_mode) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(scac_iata_code) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(dot_number) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(mc_authority) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->has('logistics_mode') && $request->logistics_mode != '') {
            $carriers->where('logistics_mode', $request->logistics_mode);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $carriers->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $carriers->whereDate('created_at', '<=', $request->to_date);
        }

        $carriers = $carriers->latest()->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.carriers.partials.table', compact('carriers'))->render(),
                'pagination' => $carriers->links()->toHtml(),
            ]);
        }

        return view('pages.products.logistics.carriers.index', compact('carriers'));
    }

    public function create()
    {
        return view('pages.products.logistics.carriers.create');
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'legal_carrier_name' => 'required|string|max:255',
                'logistics_mode' => 'required|string|max:100',
                'scac_iata_code' => 'nullable|string|max:50',
                'dot_number' => 'nullable|string|max:50',
                'mc_authority' => 'nullable|string|max:50',
                'safety_rating' => 'nullable|string|max:50',
                'liability_insurance' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip,exe|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            $input = $request->except(['liability_insurance', '_token']);

            if ($request->hasFile('liability_insurance')) {
                $file = $request->file('liability_insurance');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('logistics/carriers/documents', $filename, 'public');
                $input['liability_insurance_path'] = $path;
            }

            $input['company_id'] = $input['company_id'] ?? session('company_id');
            $carrier = LogisticsCarrier::create($input);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carrier registered successfully!',
                'carrier' => [
                    'id'   => $carrier->id,
                    'name' => $carrier->legal_carrier_name,
                    'time' => 'Standard Transit',
                ],
                'redirect_url' => route('logistics.carriers.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Carrier registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $carrier = LogisticsCarrier::findOrFail((int) $id);
        return view('pages.products.logistics.carriers.show', compact('carrier'));
    }

    public function edit($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $carrier = LogisticsCarrier::findOrFail((int) $id);
        return view('pages.products.logistics.carriers.edit', compact('carrier'));
    }

    public function update(Request $request, $id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $id = (int) $id;
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'legal_carrier_name' => 'required|string|max:255',
                'logistics_mode' => 'required|string|max:100',
                'scac_iata_code' => 'nullable|string|max:50',
                'dot_number' => 'nullable|string|max:50',
                'mc_authority' => 'nullable|string|max:50',
                'safety_rating' => 'nullable|string|max:50',
                'liability_insurance' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip,exe|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            $carrier = LogisticsCarrier::findOrFail($id);
            $input = $request->except(['liability_insurance', '_token', '_method']);

            if ($request->hasFile('liability_insurance')) {
                if ($carrier->liability_insurance_path && Storage::disk('public')->exists($carrier->liability_insurance_path)) {
                    Storage::disk('public')->delete($carrier->liability_insurance_path);
                }

                $file = $request->file('liability_insurance');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('logistics/carriers/documents', $filename, 'public');
                $input['liability_insurance_path'] = $path;
            }

            $carrier->update($input);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carrier updated successfully!',
                'redirect_url' => route('logistics.carriers.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Carrier update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        try {
            $carrier = LogisticsCarrier::findOrFail((int) $id);

            if ($carrier->liability_insurance_path && Storage::disk('public')->exists($carrier->liability_insurance_path)) {
                Storage::disk('public')->delete($carrier->liability_insurance_path);
            }

            $carrier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Carrier deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting carrier: ' . $e->getMessage()
            ], 500);
        }
    }
}
