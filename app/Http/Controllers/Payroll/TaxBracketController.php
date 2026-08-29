<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Exports\TaxBracketTemplateExport;
use App\Imports\TaxBracketImport;
use App\Models\AccountingPeriod;
use App\Models\TaxBracket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TaxBracketController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = AccountingPeriod::where('company_id', auth()->user()->company_id)->orderBy('start_date', 'desc')->get();
        $fiscalYear = $request->fiscal_year ?? AccountingPeriod::findPeriodForDate(now())?->name ?? $this->currentFiscalYear();

        $this->recalculateBaseTaxes(auth()->user()->company_id, $fiscalYear);

        $brackets   = TaxBracket::where('fiscal_year', $fiscalYear)->orderBy('sort_order')->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.tax-brackets.partials.table', compact('brackets'))->render(),
                'pagination' => $brackets->hasPages()
                    ? (string) $brackets->withQueryString()->links()
                    : null,
            ]);
        }

        return view('pages.payroll.tax-brackets.index', compact('brackets', 'fiscalYear', 'fiscalYears'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|string|max:50',
            'min_income'  => 'required|numeric|min:0',
            'max_income'  => 'nullable|numeric|gt:min_income',
            'rate'        => 'required|numeric|min:0|max:100',
            'sort_order'  => 'required|integer|min:0',
        ]);

        TaxBracket::create([
            ...$request->only(['fiscal_year', 'min_income', 'max_income', 'rate', 'sort_order']),
            'base_tax' => 0,
        ]);

        $this->recalculateBaseTaxes(Auth::user()->company_id, $request->fiscal_year);

        return response()->json(['success' => true, 'message' => 'Tax bracket created.']);
    }

    public function update(Request $request, TaxBracket $taxBracket)
    {
        $request->validate([
            'fiscal_year' => 'required|string|max:50',
            'min_income'  => 'required|numeric|min:0',
            'max_income'  => 'nullable|numeric',
            'rate'        => 'required|numeric|min:0|max:100',
            'sort_order'  => 'required|integer|min:0',
        ]);

        $oldFiscalYear = $taxBracket->fiscal_year;

        $taxBracket->update($request->only(['fiscal_year', 'min_income', 'max_income', 'rate', 'sort_order']));

        $companyId = Auth::user()->company_id;
        $this->recalculateBaseTaxes($companyId, $oldFiscalYear);
        $this->recalculateBaseTaxes($companyId, $request->fiscal_year);

        return response()->json(['success' => true, 'message' => 'Tax bracket updated.']);
    }

    public function destroy(TaxBracket $taxBracket)
    {
        $companyId = Auth::user()->company_id;
        $fiscalYear = $taxBracket->fiscal_year;

        $taxBracket->delete();
        $this->recalculateBaseTaxes($companyId, $fiscalYear);

        return response()->json(['success' => true, 'message' => 'Tax bracket deleted.']);
    }

    /**
     * Seed Pakistan FBR 2024-2025 salaried tax slabs.
     */
    public function seedFBR(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $fiscalYear = $request->fiscal_year ?? $this->currentFiscalYear();

        $slabs = [
            [
                'min_income' => 0,
                'max_income' => 600000,
                'rate'       => 0,
                'sort_order' => 1,
            ],
            [
                'min_income' => 600000,
                'max_income' => 1200000,
                'rate'       => 1,
                'sort_order' => 2,
            ],
            [
                'min_income' => 1200000,
                'max_income' => 2200000,
                'rate'       => 11,
                'sort_order' => 3,
            ],
            [
                'min_income' => 2200000,
                'max_income' => 3200000,
                'rate'       => 23,
                'sort_order' => 4,
            ],
            [
                'min_income' => 3200000,
                'max_income' => 4100000,
                'rate'       => 30,
                'sort_order' => 5,
            ],
            [
                'min_income' => 4100000,
                'max_income' => null,
                'rate'       => 35,
                'sort_order' => 6,
            ],
        ];

        DB::transaction(function () use ($companyId, $fiscalYear, $slabs) {

            TaxBracket::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('fiscal_year', $fiscalYear)
                ->forceDelete();

            foreach ($slabs as $slab) {
                TaxBracket::withoutGlobalScopes()->create([
                    'company_id'  => $companyId,
                    'fiscal_year' => $fiscalYear,
                    'min_income'  => $slab['min_income'],
                    'max_income'  => $slab['max_income'],
                    'base_tax'    => 0,
                    'rate'        => $slab['rate'],
                    'sort_order'  => $slab['sort_order'],
                ]);
            }
        });

        $this->recalculateBaseTaxes($companyId, $fiscalYear);

        return response()->json([
            'success' => true,
            'message' => "FBR {$fiscalYear} tax slabs loaded successfully."
        ]);
    }

    public function downloadTemplate(Request $request)
    {
        $fiscalYear = $request->fiscal_year ?? AccountingPeriod::findPeriodForDate(now())?->name ?? $this->currentFiscalYear();

        return Excel::download(new TaxBracketTemplateExport($fiscalYear), 'tax_brackets_import_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx',
            'fiscal_year' => 'required|string|max:50',
        ]);

        try {
            $import = new TaxBracketImport(
                Auth::user()->company_id,
                $request->input('fiscal_year'),
                $request->boolean('replace_existing')
            );

            Excel::import($import, $request->file('import_file'));

            $this->recalculateBaseTaxes(Auth::user()->company_id, $request->input('fiscal_year'));

            $errors = $import->getErrors();
            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();

            if ($imported === 0 && empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tax bracket rows were found in the file.',
                    'errors' => [],
                ], 422);
            }

            if (! empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => "Imported {$imported} row(s), skipped {$skipped} row(s). Please review the errors below.",
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} tax bracket row(s) successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function recalculateBaseTaxes(int $companyId, string $fiscalYear): void
    {
        $baseTax = 0;

        $brackets = TaxBracket::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $companyId)
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('sort_order')
            ->orderBy('min_income')
            ->get();

        foreach ($brackets as $bracket) {
            $bracket->base_tax = round($baseTax, 2);
            $bracket->save();

            if ($bracket->max_income === null) {
                continue;
            }

            $range = max(0, (float) $bracket->max_income - (float) $bracket->min_income);
            $baseTax += $range * ((float) $bracket->rate / 100);
        }
    }

    private function currentFiscalYear(): string
    {
        $year = now()->year;
        return ($year - 1) . '-' . $year;
    }
}
