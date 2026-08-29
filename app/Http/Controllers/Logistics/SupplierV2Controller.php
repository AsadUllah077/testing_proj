<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Imports\Logistics\SuppliersImport;
use App\Exports\Logistics\SuppliersTemplateExport;
use App\Models\LogisticsSupplierV2;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class SupplierV2Controller extends Controller
{
    public function index(Request $request)
    {
        $suppliers = LogisticsSupplierV2::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('registration_no', 'like', "%{$request->search}%");
            })
            ->when($request->category && $request->category !== 'All Categories', function ($q) use ($request) {
                $q->where('category', $request->category);
            })
            ->when($request->country && $request->country !== 'All Countries', function ($q) use ($request) {
                $q->where('country', $request->country);
            })
            ->when($request->status && $request->status !== 'All Statuses', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => LogisticsSupplierV2::count(),
            'approved' => LogisticsSupplierV2::where('status', 'Approved')->count(),
            'review' => LogisticsSupplierV2::where('status', 'Under Review')->count(),
            'suspended' => LogisticsSupplierV2::where('status', 'Suspended')->count(),
            'new' => LogisticsSupplierV2::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('pages.products.logistics.suppliers.v2.index', compact('suppliers', 'stats'));
    }

    public function create()
    {
        return view('pages.products.logistics.suppliers.v2.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'business_type' => 'required',
            'category' => 'required',
            'country' => 'required',
            'registration_no' => 'required',
            'primary_contact' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'incoterms' => 'required',
        ]);

        $data = $request->all();
        $data['supplier_code'] = LogisticsSupplierV2::generateSupplierId();

        $supplier = LogisticsSupplierV2::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Supplier Create successfully'
        ]);
    }

    public function show($id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        return view('pages.products.logistics.suppliers.v2.show', compact('supplier', 'id'));
    }

    public function edit($id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        return view('pages.products.logistics.suppliers.v2.edit', compact('supplier', 'id'));
    }

    public function update(Request $request, $id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        $supplier->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Supplier Update successfully'
        ]);
    }

    public function destroy($id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        $supplier->delete();

        return response()->json(['success' => true]);
    }

    public function approve($id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        $supplier->update(['status' => 'Approved']);

        return back()->with('success', 'Supplier approved successfully!');
    }

    public function reject($id)
    {
        $supplier = LogisticsSupplierV2::findOrFail($id);
        $supplier->update(['status' => 'Suspended']);

        return back()->with('success', 'Supplier suspended successfully!');
    }

    /**
     * Download the Excel import template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new SuppliersTemplateExport, 'suppliers_import_template.xlsx');
    }

    /**
     * Import suppliers from an uploaded Excel / CSV file.
     */
    public function importSuppliers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:5120',
        ]);

        try {
            $import = new SuppliersImport;
            Excel::import($import, $request->file('file'));

            // Collect row-level validation failures (SkipsOnError)
            $failures = $import->errors();

            if (count($failures) > 0) {
                $msgs = collect($failures)->map(fn ($e) => $e->getMessage())->unique()->implode(' | ');
                return redirect()->back()
                    ->with('warning', 'Import completed with some errors: ' . $msgs);
            }

            return redirect()->back()->with('success', 'Suppliers imported successfully!');

        } catch (ValidationException $e) {
            $failures = collect($e->failures())
                ->map(fn ($f) => 'Row ' . $f->row() . ': ' . implode(', ', $f->errors()))
                ->implode(' | ');
            return redirect()->back()->with('error', 'Validation failed — ' . $failures);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import error: ' . $e->getMessage());
        }
    }
}
