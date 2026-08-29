<?php

namespace App\Http\Controllers\Product\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ProgressSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProgressSheetController extends Controller
{
    /**
     * Display a listing of progress sheets.
     */
    public function index(Request $request)
    {
        try {
            $search  = $request->get('search');
            $date    = $request->get('date');
            $name    = $request->get('name');
            $perPage = $request->get('per_page', 20);

            $query = ProgressSheet::with(['createdBy'])->latest();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('working', 'LIKE', "%{$search}%")
                        ->orWhere('remarks', 'LIKE', "%{$search}%");
                });
            }

            if ($date) {
                $query->whereDate('date', $date);
            }

            if ($name) {
                $query->where('name', $name);
            }

            $sheets    = $query->paginate($perPage);
            $employees = Employee::whereHas('department', fn ($q) => $q->where('department', 'ILIKE', '%technician%'))
                ->orderBy('full_name')
                ->get(['id', 'full_name']);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html'    => view('pages.products.warehouse.progress-sheet.partials.table', compact('sheets'))->render(),
                ]);
            }

            return view('pages.products.warehouse.progress-sheet.index', compact('sheets', 'employees'));

        } catch (\Exception $e) {
            Log::error('ProgressSheetController@index: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error loading progress sheets.'], 500);
            }

            return redirect()->back()->with('error', 'Error loading progress sheets: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new progress sheet entry.
     */
    public function create()
    {
        $employees =   Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%operations department%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%lab%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%repair%']);
            })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']);

        return view('pages.products.warehouse.progress-sheet.create', compact('employees'));
    }

    /**
     * Store a newly created progress sheet entry.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'     => 'required|date',
            'name'     => 'required|string|max:255',
            'working'  => 'required|string',
            'quantity' => 'nullable|string|max:100',
            'remarks'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sheet = ProgressSheet::create([
                'date'       => $request->date,
                'name'       => $request->name,
                'working'    => $request->working,
                'quantity'   => $request->quantity,
                'remarks'    => $request->remarks,
                'created_by' => auth('web')->id(),
                'company_id' => auth('web')->user()->company_id ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Progress sheet entry created successfully.',
                'redirect' => route('warehouse.progress-sheet.index'),
                'data'     => $sheet,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProgressSheetController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified entry.
     */
    public function edit($id)
    {
        try {
            $sheet     = ProgressSheet::findOrFail($id);
            $employees =   Employee::whereHas('department', function ($q) {
                $q->whereRaw('LOWER(department) LIKE ?', ['%technician%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%operations department%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%lab%'])
                    ->orWhereRaw('LOWER(department) LIKE ?', ['%repair%']);
            })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email']);

            return view('pages.products.warehouse.progress-sheet.edit', compact('sheet', 'employees'));

        } catch (\Exception $e) {
            Log::error('ProgressSheetController@edit: ' . $e->getMessage());
            return redirect()->route('warehouse.progress-sheet.index')
                ->with('error', 'Entry not found.');
        }
    }

    /**
     * Update the specified entry.
     */
    public function update(Request $request, $id)
    {
        $sheet = ProgressSheet::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'date'     => 'required|date',
            'name'     => 'required|string|max:255',
            'working'  => 'required|string',
            'quantity' => 'nullable|string|max:100',
            'remarks'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sheet->update([
                'date'     => $request->date,
                'name'     => $request->name,
                'working'  => $request->working,
                'quantity' => $request->quantity,
                'remarks'  => $request->remarks,
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Entry updated successfully.',
                'redirect' => route('warehouse.progress-sheet.index'),
                'data'     => $sheet,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProgressSheetController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a single entry (AJAX).
     */
    public function destroy($id)
    {
        try {
            $sheet = ProgressSheet::findOrFail($id);
            $sheet->delete();

            return response()->json(['success' => true, 'message' => 'Entry deleted successfully.']);

        } catch (\Exception $e) {
            Log::error('ProgressSheetController@destroy: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete entry.'], 500);
        }
    }

    /**
     * Export progress sheets as PDF.
     */
    public function exportPdf(Request $request)
    {
        try {
            $search = $request->get('search');
            $date   = $request->get('date');

            $query = ProgressSheet::latest();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('working', 'LIKE', "%{$search}%")
                        ->orWhere('remarks', 'LIKE', "%{$search}%");
                });
            }
            if ($date) {
                $query->whereDate('date', $date);
            }

            $sheets  = $query->get();
            $filters = ['search' => $search, 'date' => $date];

            $pdf = Pdf::loadView('pdf.progress-sheet-export', compact('sheets', 'filters'));
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('progress-sheet-' . now()->format('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            Log::error('ProgressSheetController@exportPdf: ' . $e->getMessage());
            return redirect()->back()->with('error', 'PDF export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export progress sheets as CSV.
     */
    public function exportCsv(Request $request)
    {
        try {
            $search = $request->get('search');
            $date   = $request->get('date');

            $query = ProgressSheet::latest();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('working', 'LIKE', "%{$search}%")
                        ->orWhere('remarks', 'LIKE', "%{$search}%");
                });
            }
            if ($date) {
                $query->whereDate('date', $date);
            }

            $sheets   = $query->get();
            $filename = 'progress-sheet-' . now()->format('Y-m-d') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($sheets) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['S.No', 'Date', 'Name', 'Working', 'Quantity', 'Remarks']);

                foreach ($sheets as $i => $sheet) {
                    fputcsv($file, [
                        $i + 1,
                        $sheet->date->format('d-m-Y'),
                        $sheet->name,
                        $sheet->working,
                        $sheet->quantity ?? '',
                        $sheet->remarks ?? '',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('ProgressSheetController@exportCsv: ' . $e->getMessage());
            return redirect()->back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete entries (AJAX).
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:progress_sheets,id',
        ]);

        try {
            DB::beginTransaction();

            $count = ProgressSheet::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} entry(s).",
                'count'   => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProgressSheetController@bulkDelete: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete entries.'], 500);
        }
    }
}
