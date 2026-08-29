<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = LogisticsSupplier::query();
        $search = strtolower($request->search);

        // Search
        if ($request->has('search') && $request->search != '') {
            $suppliers->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_company_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(primary_contact_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(official_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(country_of_registration) LIKE ?', ['%' . $search . '%']);
            });
        }

        // Date filters
        if ($request->has('from_date') && $request->from_date != '') {
            $suppliers->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $suppliers->whereDate('created_at', '<=', $request->to_date);
        }

        $suppliers = $suppliers->latest()->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.suppliers.partials.table', compact('suppliers'))->render(),
                'pagination' => $suppliers->links()->toHtml(),
            ]);
        }

        return view('pages.products.logistics.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('pages.products.logistics.suppliers.create');
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'legal_company_name' => 'required|string|max:255',
                'tax_id' => 'required|string|max:50',
                'business_type' => 'required|string|max:100',
                'country_of_registration' => 'required|string|max:100',
                'primary_contact_name' => 'nullable|string|max:255',
                'official_email' => 'nullable|email|max:255',
                'registered_office_address' => 'nullable|string',
                'default_incoterms' => 'nullable|string|max:50',
                'pref_port_of_loading' => 'nullable|string|max:255',
                'lead_time_days' => 'nullable|integer',
                'iban' => 'nullable|string|max:50',
                'swift_bic' => 'nullable|string|max:50',
                'payment_currency' => 'nullable|string|max:10',
                'business_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip,exe|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            $input = $request->except(['business_license', '_token']);

            // Handle file upload
            if ($request->hasFile('business_license')) {
                $file = $request->file('business_license');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('logistics/suppliers/documents', $filename, 'public');
                $input['business_license_path'] = $path;
            }

            LogisticsSupplier::create($input);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier registered successfully!',
                'redirect_url' => route('logistics.suppliers.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Supplier registration error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $supplier = LogisticsSupplier::findOrFail($id);
        return view('pages.products.logistics.suppliers.show', compact('supplier'));
    }

    public function edit($id)
    {
        $supplier = LogisticsSupplier::findOrFail($id);
        return view('pages.products.logistics.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'legal_company_name' => 'required|string|max:255',
                'tax_id' => 'required|string|max:50',
                'business_type' => 'required|string|max:100',
                'country_of_registration' => 'required|string|max:100',
                'primary_contact_name' => 'nullable|string|max:255',
                'official_email' => 'nullable|email|max:255',
                'registered_office_address' => 'nullable|string',
                'default_incoterms' => 'nullable|string|max:50',
                'pref_port_of_loading' => 'nullable|string|max:255',
                'lead_time_days' => 'nullable|integer',
                'iban' => 'nullable|string|max:50',
                'swift_bic' => 'nullable|string|max:50',
                'payment_currency' => 'nullable|string|max:10',
                'business_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip,exe|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            $supplier = LogisticsSupplier::findOrFail($id);
            $input = $request->except(['business_license', '_token', '_method']);

            // Handle file upload
            if ($request->hasFile('business_license')) {
                // Delete old file if exists
                if ($supplier->business_license_path && Storage::disk('public')->exists($supplier->business_license_path)) {
                    Storage::disk('public')->delete($supplier->business_license_path);
                }

                $file = $request->file('business_license');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('logistics/suppliers/documents', $filename, 'public');
                $input['business_license_path'] = $path;
            }

            $supplier->update($input);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully!',
                'redirect_url' => route('logistics.suppliers.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Supplier update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = LogisticsSupplier::findOrFail($id);

            // Delete file if exists
            if ($supplier->business_license_path && Storage::disk('public')->exists($supplier->business_license_path)) {
                Storage::disk('public')->delete($supplier->business_license_path);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting supplier: ' . $e->getMessage()
            ], 500);
        }
    }
}
