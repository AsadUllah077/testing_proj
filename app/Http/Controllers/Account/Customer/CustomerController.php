<?php

namespace App\Http\Controllers\Account\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Jobs\ExportCustomersJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with search & pagination.
     */
    public function index(Request $request)
    {
        // Extract filters
        $search = $request->search;
        $perPage = $request->per_page ?? 20;

        // Query customers with dynamic search conditions
        $customers = Customer::when($search, function ($query) use ($search) {
            $searchFilter = strtolower(trim($search));

            $query->where(function ($q) use ($searchFilter) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
            });
        })
            ->withSum('sales', 'remaining_amount') // Add this line to get sum of remaining_amount
            ->sortable()
            ->latest() // Sort by latest created
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'per_page' => $perPage,
            ]);

        $data = compact('customers', 'perPage');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.sale.customer.partials.table', $data)->render(),
                'pagination' => $customers->links()->toHtml(),
            ]);
        }

        // Load index view
        return view('pages.account.sale.customer.index', $data);
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerCreateRequest $request)
    {
        try {
            // Validate incoming payload
            $validated = $request->validated();
            // Create customer
            $customer = Customer::create([
                'company_id' => loggedCompanyId()['company_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'credit_limit' => $validated['credit_limit'] ?? null,
                'payment_terms_days' => $validated['payment_terms_days'] ?? 30,
            ]);

            // Log creation event
            $customer->logChange(
                event: 'created',
                old: null,
                new: $customer->toArray()
            );

            return response()->json(['message' => 'Customer created successfully!', 'success' => true], 201);
        } catch (\Throwable $th) {

            // Return backend error as JSON
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Fetch a single customer for editing (AJAX).
     */
    public function edit($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            return response()->json(['customer' => $customer, 'success' => true]);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'success' => false]);
        }
    }

    /**
     * Update a customer's details.
     */
    public function update(CustomerUpdateRequest $request, $id)
    {
        try {
            // Validate input
            $validated = $request->validated();
            // Fetch old customer record
            $customer = Customer::findOrFail($id);
            $old = $customer->toArray();

            // Update customer
            $customer->update([
                'company_id' => loggedCompanyId()['company_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'credit_limit' => $validated['credit_limit'] ?? null,
                'payment_terms_days' => $validated['payment_terms_days'] ?? 30,
            ]);

            // Log update event
            $customer->logChange(
                event: 'updated',
                old: $old,
                new: $customer->toArray()
            );

            return response()->json(['message' => 'Customer updated successfully!', 'success' => true], 200);
        } catch (\Throwable $th) {

            // Redirect with error flashed to session
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Soft delete or permanently delete a customer.
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $old = $customer->toArray();

            // Log deletion
            $customer->logChange(
                event: 'deleted',
                old: $old,
                new: null
            );

            $customer->delete();

            return redirect()->back()->with('success', 'Customer deleted successfully!');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Show logs of customer activity (Created/Updated/Deleted).
     */
    public function logs($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            // Fetch logs with user relations
            $logs = $customer->logs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.sale.customer.log', compact('customer', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Show all logs of all customers.
     */
    public function allLogs()
    {
        try {
            $logs = ActivityLog::where('loggable_type', 'App\Models\Customer')->latest()->paginate(20);

            return view('pages.activity-logs.index', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Soft delete or permanently delete all customer.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);

            if (empty($ids)) {
                return back()->with('error', 'No customers selected for deletion.');
            }

            foreach ($ids as $id) {
                $customer = Customer::find($id);
                if ($customer) {
                    $customer->logChange(
                        event: 'deleted',
                        old: $customer->toArray(),
                        new: null
                    );
                    $customer->delete();
                }
            }

            return back()->with('success', 'Selected customers deleted successfully.');
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

            // Check in active customers first
            $customer = Customer::where('email', $email)->first();

            if ($customer) {
                // Email exists and customer is active
                return response()->json([
                    'exists' => true,
                    'deleted' => false,
                    'message' => 'Email already exists',
                ]);
            }

            // Check in soft deleted customers
            $deletedCustomer = Customer::onlyTrashed()
                ->where('email', $email)
                ->first();

            if ($deletedCustomer) {
                // Email exists but customer is soft deleted
                return response()->json([
                    'exists' => true,
                    'deleted' => true,
                    'customer_id' => $deletedCustomer->id,
                    'message' => 'This email belongs to a deleted customer',
                ]);
            }

            // Email doesn't exist
            return response()->json([
                'exists' => false,
                'deleted' => false,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft deleted customer
     */
    public function restore($id)
    {
        try {
            $customer = Customer::onlyTrashed()->findOrFail($id);

            $customer->restore();

            $customer->logChange(
                event: 'restored',
                old: null,
                new: $customer->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Customer restored successfully!',
                'customer' => $customer,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function storeInline(Request $request)
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:customers,email',
                'phone' => 'nullable|string|max:20',
            ]);

            $validated['company_id'] = loggedCompanyId()['company_id'];
            $customer = Customer::create($validated);

            return response()->json([
                'success' => true,
                'customer' => $customer,
            ], 200);
        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function paginate(Request $request)
    {
        $search = $request->query('q', '');
        $page = $request->query('page', 1);

        $query = Customer::query()
            ->where('company_id', loggedCompanyId()['company_id']);
        if ($search !== '') {
            $searchLower = strtolower($search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
        }
        $customers = $query->orderBy('name')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'data' => $customers->items(),
            'next_page' => $customers->nextPageUrl() ? $page + 1 : null,
        ]);
    }

    public function exportExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CustomersExport($request->search), 'customers_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $customers = Customer::when($request->search, function ($query) use ($request) {
            $searchFilter = strtolower(trim($request->search));
            return $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchFilter}%"])
                ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchFilter}%"]);
        })->get();

        $companyName = auth('web')->user()->company->name ?? 'Company';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.account.sale.customer.pdf.export', compact('customers', 'companyName'));
        return $pdf->download('customers_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Display the specified customer details.
     */
    public function show($id)
    {
        try {
            $customer = Customer::with(['sales' => function ($query) {
                $query->latest();
            }])->findOrFail($id);

            $logs = $customer->logs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->take(10)
                ->get();

            return view('pages.account.sale.customer.details', compact('customer', 'logs'));
        } catch (\Throwable $th) {
            return back()->with('error', 'Customer not found.');
        }
    }
}
