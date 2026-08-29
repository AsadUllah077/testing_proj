<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryBenchmark;
use Illuminate\Http\Request;

class SalaryBenchmarkController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryBenchmark::where('company_id', auth('web')->user()->company_id);

        if ($request->filled('designation')) {
            $query->where('designation', 'ILIKE', '%' . $request->designation . '%');
        }

        $benchmarks = $query->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $designations = Employee::where('company_id', auth('web')->user()->company_id)
            ->distinct()
            ->pluck('designation');
            
        return view('pages.payroll.salary-benchmarks.index', compact('benchmarks', 'designations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'min_salary' => 'required|numeric|min:0',
            'median_salary' => 'required|numeric|min:0',
            'max_salary' => 'required|numeric|min:0',
        ]);

        SalaryBenchmark::updateOrCreate(
            [
                'company_id' => auth('web')->user()->company_id,
                'designation' => $validated['designation']
            ],
            $validated
        );

        return response()->json(['success' => true, 'message' => 'Benchmark saved successfully.']);
    }

    public function destroy($id)
    {
        $benchmark = SalaryBenchmark::where('company_id', auth('web')->user()->company_id)
            ->findOrFail($id);
            
        $benchmark->delete();

        return response()->json(['success' => true, 'message' => 'Benchmark deleted successfully.']);
    }
}
