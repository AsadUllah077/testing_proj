<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index(Request $request)
    {
        $query = Company::with(['country', 'state', 'currency']);

        // 🔍 Searching
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            Log::info($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('mobile', 'ILIKE', "%{$search}%")
                    ->orWhereHas('country', fn($sub) => $sub->where('name', 'ILIKE', "%{$search}%"))
                    ->orWhereHas('state', fn($sub) => $sub->where('name', 'ILIKE', "%{$search}%"))
                    ->orWhereHas(
                        'currency',
                        fn($sub) =>
                        $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])->orWhere('symbol', 'ILIKE', "%{$search}%")
                    );
            });
        }

        if (filled($request->from_date) && filled($request->to_date)) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // 🔽 Sorting
        $sortable = ['name', 'email', 'phone', 'mobile', 'created_at'];
        $orderBy = in_array($request->get('order_by'), $sortable) ? $request->get('order_by') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $direction);

        // 🔢 Pagination
        $perPage = $request->get('per_page', 20);
        $companies = $query->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.home.companies.table', compact('companies'))->render(),
                'pagination' => $companies->appends($request->only('search', 'per_page', 'order_by', 'direction'))->links()->toHtml(),
            ]);
        }

        return view('pages.home.companies.index', compact('companies', 'orderBy', 'direction'));
    }



    /**
     * Show the form for creating a new company.
     */
    public function create()
    {
        $countries = Country::latest()->get();
        $currencies = Currency::latest()->get();
        return view('pages.home.companies.create', compact('countries', 'currencies'));
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'currency_id' => 'required|exists:currencies,id',
            'name' => 'required|string|max:255|unique:companies,name',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'signature' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'favicon' => 'nullable|image|mimes:jpg,jpeg,png,ico|max:1024',
        ]);

        // dd($request->all());
        DB::beginTransaction();

        try {
            // Handle file uploads
            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store('logos', 'public');
            }

            if ($request->hasFile('signature')) {
                $validated['signature'] = $request->file('signature')->store('signatures', 'public');
            }

            if ($request->hasFile('favicon')) {
                $validated['favicon'] = $request->file('favicon')->store('favicons', 'public');
            }

            Company::create($validated);

            DB::commit();

            return redirect()->route('home.companies.index')->with('success', 'Company created successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log the error for debugging
            Log::error('Company creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while creating the company. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company)
    {
        return view('home.companies.show', compact('company'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit($id)
    {
        // Fetch company record or fail if not found
        $company = Company::findOrFail($id);

        // Fetch all countries
        $countries = Country::all();

        // (Optional) Fetch all currencies if your edit view includes currency dropdown
        $currencies = Currency::all();

        // Return the edit view
        return view('pages.home.companies.edit', compact('company', 'countries', 'currencies'));
    }


    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'currency_id' => 'required|exists:currencies,id',
            'name' => 'required|string|max:255|unique:companies,name,' . $id,
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'signature' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'favicon' => 'nullable|image|mimes:jpg,jpeg,png,ico|max:1024',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 Find the company record
            $company = Company::findOrFail($id);

            // 🔹 Handle logo replacement
            if ($request->hasFile('logo')) {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
                $validated['logo'] = $request->file('logo')->store('logos', 'public');
            } else {
                $validated['logo'] = $company->logo;
            }

            // 🔹 Handle signature replacement
            if ($request->hasFile('signature')) {
                if ($company->signature && Storage::disk('public')->exists($company->signature)) {
                    Storage::disk('public')->delete($company->signature);
                }
                $validated['signature'] = $request->file('signature')->store('signatures', 'public');
            } else {
                $validated['signature'] = $company->signature;
            }

            // 🔹 Handle favicon replacement
            if ($request->hasFile('favicon')) {
                if ($company->favicon && Storage::disk('public')->exists($company->favicon)) {
                    Storage::disk('public')->delete($company->favicon);
                }
                $validated['favicon'] = $request->file('favicon')->store('favicons', 'public');
            } else {
                $validated['favicon'] = $company->favicon;
            }

            // 🔹 Update the company record
            $company->update($validated);

            DB::commit();

            $condition = $request->input('from_organization');

            if ($condition && $condition == 1) {
                return redirect()
                    ->route('account.organization-settings.company', $company->id)
                    ->with('success', 'Company updated successfully!');
            }

            return redirect()
                ->route('home.companies.index')
                ->with('success', 'Company updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Company update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while updating the company. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Remove the specified company from storage (soft delete).
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Find company or throw 404 if not found
            $company = Company::findOrFail($id);

            // Perform soft delete
            $company->delete();

            DB::commit();

            return redirect()
                ->route('companies.index')
                ->with('success', 'Company deleted successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Company deletion failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'company_id' => $id,
            ]);

            return back()->withErrors([
                'error' => 'An error occurred while deleting the company. Please try again.',
            ]);
        }
    }

    public function getStates($country_id)
    {
        try {
            $states = State::where('country_id', $country_id)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json(['success' => true, 'states' => $states]);
        } catch (\Throwable $e) {
            Log::error('Error fetching states: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Unable to load states.']);
        }
    }
}
