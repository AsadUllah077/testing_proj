<?php

namespace App\Http\Controllers\Account\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\ExportVendorsJob;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 20;

        $vendors = Vendor::when($search, function ($query) use ($search) {
            $searchFilter = strtolower(trim($search));

            $query->where(function ($q) use ($searchFilter) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
            });
        })
            ->withSum('bills', 'remaining_amount')
            ->sortable()
            ->latest()
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'per_page' => $perPage,
            ]);
        $data = compact('vendors', 'perPage');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.bill.vendor.partials.table', $data)->render(),
                'pagination' => $vendors->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.bill.vendor.index', $data);
    }



    public function store(Request $request)
    {
        try {
            $companyId = loggedCompanyId()['company_id'];
            $validated = $request->validate([
                'company_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    Rule::unique('vendors', 'email')
                        ->where(fn($q) => $q->where('company_id', $companyId)),
                ],
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'payment_terms_days' => 'nullable|integer|min:0|max:3650',
            ]);
            $validated['company_id'] = $companyId;

            $vendor =  Vendor::create($validated);
            $vendor->logChange(
                event: 'created',
                old: null,
                new: $vendor->toArray()
            );

            return response()->json(['message' => 'Vendor created successfully!', 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function edit($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            return response()->json([
                'success' => true,
                'vendors' => $vendor
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $companyId = loggedCompanyId()['company_id'];
            $validated = $request->validate([
                'company_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('vendors', 'email')
                        ->where(fn($q) => $q->where('company_id', $companyId))
                        ->ignore($id, 'id'),
                ],
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'payment_terms_days' => 'nullable|integer|min:0|max:3650',
            ]);
            $validated['company_id'] = $companyId;
            $vendor = Vendor::findOrFail($id);
            $old = $vendor->toArray();

            $vendor->update($validated);
            $vendor->logChange(
                event: 'updated',
                old: $old,
                new: $vendor->toArray()
            );

            return response()->json(['message' => 'Vendor updated successfully!', 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $old = $vendor->toArray();

            $vendor->logChange(
                event: 'deleted',
                old: $old,
                new: null
            );
            $vendor->delete();
            return response()->json(['message' => 'Vendor deleted successfully!'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function logs($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);

            $logs = $vendor->logs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.bill.vendor.log', compact('vendor', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }


    /**
     * Soft delete or permanently delete all vendors.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);

            if (empty($ids)) {
                return back()->with('error', 'No vendors selected for deletion.');
            }

            foreach ($ids as $id) {
                $vendor = Vendor::find($id);
                if ($vendor) {
                    $vendor->logChange(
                        event: 'deleted',
                        old: $vendor->toArray(),
                        new: null
                    );
                    $vendor->delete();
                }
            }

            return back()->with('success', 'Selected vendors deleted successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Check if email exists and whether it's soft deleted
     */
    public function checkEmail(Request $request)
    {
        try {
            $email = $request->email;

            // Check in normal vendors first
            $vendor = Vendor::where('email', $email)->first();

            if ($vendor) {
                // Email exists and vendor is active
                return response()->json([
                    'exists' => true,
                    'deleted' => false,
                    'message' => 'Email already exists'
                ]);
            }

            // Check in soft deleted vendors
            $deletedVendor = Vendor::onlyTrashed()
                ->where('email', $email)
                ->first();

            if ($deletedVendor) {
                // Email exists but vendor is soft deleted
                return response()->json([
                    'exists' => true,
                    'deleted' => true,
                    'vendor_id' => $deletedVendor->id,
                    'message' => 'This email belongs to a deleted vendor'
                ]);
            }

            // Email doesn't exist
            return response()->json([
                'exists' => false,
                'deleted' => false
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted vendor
     */
    public function restore($id)
    {
        try {
            $vendor = Vendor::onlyTrashed()->findOrFail($id);

            $vendor->restore();

            $vendor->logChange(
                event: 'restored',
                old: null,
                new: $vendor->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Vendor restored successfully!',
                'vendor' => $vendor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate export job
     */
    public function export(Request $request)
    {
        try {
            $format = $request->input('format'); // 'xlsx' or 'pdf'
            $search = $request->input('search');

            // Validate format
            if (!in_array($format, ['xlsx', 'pdf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid export format'
                ], 400);
            }

            // Generate unique job ID
            $jobId = Str::uuid()->toString();

            // Dispatch export job
            ExportVendorsJob::dispatch(
                $search,
                $format,
                auth('web')->id(),
                $jobId
            );

            return response()->json([
                'success' => true,
                'message' => 'Export started. Please wait...',
                'job_id' => $jobId
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Check export progress
     */
    public function exportProgress(Request $request)
    {
        try {
            $jobId = $request->job_id;

            $progress = Cache::get("export_progress_{$jobId}", [
                'percentage' => 0,
                'message' => 'Waiting to start...'
            ]);

            $result = Cache::get("export_result_{$jobId}");

            return response()->json([
                'success' => true,
                'progress' => $progress,
                'result' => $result
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function storeInline(Request $request)
    {
        try {

            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'nullable|email|unique:customers,email',
                'phone' => 'nullable|string|max:20',
            ]);

            $validated['company_id'] = loggedCompanyId()['company_id'];
            $vendor = Vendor::create($validated);

            return response()->json([
                'success'  => true,
                'vendor' => $vendor
            ], 200);
        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    public function paginate(Request $request)
    {
        $search = $request->query('q', '');
        $page   = $request->query('page', 1);

        $query = Vendor::query()
            ->where('company_id', loggedCompanyId()['company_id']);
        if ($search !== '') {
            $searchLower = strtolower($search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
        }
        $vendors = $query->orderBy('name')
            ->paginate(20, ['*'], 'page', $page);
        return response()->json([
            'data'       => $vendors->items(),
            'next_page'  => $vendors->nextPageUrl() ? $page + 1 : null,
        ]);
    }

    public function exportExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\VendorsExport($request->search), 'vendors_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $vendors = Vendor::when($request->search, function ($query) use ($request) {
            $searchFilter = strtolower(trim($request->search));
            return $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
        })->get();

        $companyName = auth('web')->user()->company->name ?? 'Company';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.account.bill.vendor.pdf.export', compact('vendors', 'companyName'));
        return $pdf->download('vendors_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Display the specified vendor details.
     */
    public function show($id)
    {
        try {
            $vendor = Vendor::with(['bills' => function ($query) {
                $query->latest();
            }])->findOrFail($id);

            $logs = $vendor->logs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->take(10)
                ->get();

            return view('pages.account.bill.vendor.details', compact('vendor', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', 'Vendor not found.');
        }
    }
}
