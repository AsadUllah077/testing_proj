<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsLetterOfCredit;
use Illuminate\Http\Request;
use App\Exports\Logistics\BankContractTemplateExport;
use App\Imports\Logistics\BankContractImport;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class BankContractController extends Controller
{
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('contract_number')) {
            $query->where('contract_number', $request->contract_number);
        }
        if ($request->filled('start_date')) {
            $query->where('issue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('expiry_date', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('lc_number', 'ilike', "%{$search}%")
                  ->orWhere('applicant', 'ilike', "%{$search}%")
                  ->orWhere('beneficiary', 'ilike', "%{$search}%")
                  ->orWhere('issuing_bank', 'ilike', "%{$search}%");
            });
        }
    }

    public function index(Request $request)
    {
        $query = LogisticsLetterOfCredit::where('record_type', 'bank_contract')->latest();
        $this->applyFilters($query, $request);
        $contracts = $query->paginate(20)->withQueryString();

        $stats = [
            'total'         => LogisticsLetterOfCredit::where('record_type', 'bank_contract')->count(),
            'active'        => LogisticsLetterOfCredit::where('record_type', 'bank_contract')->where('status', 'Active')->count(),
            'expiring_soon' => LogisticsLetterOfCredit::where('record_type', 'bank_contract')->where('status', 'Expiring Soon')->count(),
            'settled'       => LogisticsLetterOfCredit::where('record_type', 'bank_contract')->where('status', 'Settled')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.lc.partials.table', ['records' => $contracts, 'type' => 'bank_contract'])->render(),
                'summary' => $stats
            ]);
        }

        return view('pages.products.logistics.lc.bank-contracts.index', compact('contracts', 'stats'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new BankContractTemplateExport, 'bank_contract_import_template.xlsx');
    }

    public function importContracts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        try {
            Excel::import(new BankContractImport, $request->file('file'));
            return redirect()->back()->with('success', 'Bank Contract records imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing Bank Contracts: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('pages.products.logistics.lc.bank-contracts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'contract_number' => 'required|string',
            'issuing_bank' => 'required|string',
            'applicant'    => 'required|string',
            'beneficiary'  => 'required|string',
            'currency'     => 'required|string',
            'amount'       => 'required|numeric|min:0',
            'issue_date'   => 'required|date',
            'expiry_date'  => 'required|date|after:issue_date',
            'incoterms'    => ['nullable', Rule::in(array_keys(config('logistics.bank_contract_incoterms', [])))],
        ]);

        LogisticsLetterOfCredit::create([
            'lc_number'            => LogisticsLetterOfCredit::generateBcNumber(),
            'record_type'          => 'bank_contract',
            'contract_number'      => $request->contract_number,
            'issuing_bank'         => $request->issuing_bank,
            'advising_bank'        => $request->advising_bank,
            'reimbursing_bank'     => $request->reimbursing_bank,
            'applicant'            => $request->applicant,
            'beneficiary'          => $request->beneficiary,
            'currency'             => $request->currency,
            'amount'               => $request->amount,
            'tolerance_pct'        => $request->tolerance_pct ?: null,
            'payment_terms'        => $request->payment_terms,
            'issue_date'           => $request->issue_date,
            'expiry_date'          => $request->expiry_date,
            'po_pi_indented_no'    => $request->po_pi_indented_no,
            'date_in_document'     => $request->date_in_document,
            'booking_number'       => $request->booking_number,
            'booking_date'         => $request->booking_date,
            'latest_shipment_date' => $request->latest_shipment_date ?: null,
            'incoterms'            => $request->incoterms,
            'port_of_loading'      => $request->port_of_loading,
            'port_of_discharge'    => $request->port_of_discharge,
            'goods_description'    => $request->goods_description,
            'documents_required'   => $request->documents_required ? implode(',', (array) $request->documents_required) : null,
            'special_conditions'   => $request->special_conditions,
            'status'               => 'Draft',
            'company_id'           => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Bank Contract created successfully.']);
    }

    public function show($id)
    {
        $contract = LogisticsLetterOfCredit::where('record_type', 'bank_contract')->findOrFail($id);
        return view('pages.products.logistics.lc.bank-contracts.show', compact('contract'));
    }

    public function edit($id)
    {
        $contract = LogisticsLetterOfCredit::where('record_type', 'bank_contract')->findOrFail($id);
        return view('pages.products.logistics.lc.bank-contracts.edit', compact('contract'));
    }

    public function update(Request $request, $id)
    {
        $contract = LogisticsLetterOfCredit::where('record_type', 'bank_contract')->findOrFail($id);

        $request->validate([
            'contract_number' => 'required|string',
            'issuing_bank' => 'required|string',
            'applicant'    => 'required|string',
            'beneficiary'  => 'required|string',
            'currency'     => 'required|string',
            'amount'       => 'required|numeric|min:0',
            'issue_date'   => 'required|date',
            'expiry_date'  => 'required|date|after:issue_date',
            'incoterms'    => ['nullable', Rule::in(array_keys(config('logistics.bank_contract_incoterms', [])))],
        ]);

        $contract->update([
            'contract_number'      => $request->contract_number,
            'issuing_bank'         => $request->issuing_bank,
            'advising_bank'        => $request->advising_bank,
            'reimbursing_bank'     => $request->reimbursing_bank,
            'applicant'            => $request->applicant,
            'beneficiary'          => $request->beneficiary,
            'currency'             => $request->currency,
            'amount'               => $request->amount,
            'tolerance_pct'        => $request->tolerance_pct ?: null,
            'payment_terms'        => $request->payment_terms,
            'issue_date'           => $request->issue_date,
            'expiry_date'          => $request->expiry_date,
            'po_pi_indented_no'    => $request->po_pi_indented_no,
            'date_in_document'     => $request->date_in_document,
            'booking_number'       => $request->booking_number,
            'booking_date'         => $request->booking_date,
            'latest_shipment_date' => $request->latest_shipment_date ?: null,
            'incoterms'            => $request->incoterms,
            'port_of_loading'      => $request->port_of_loading,
            'port_of_discharge'    => $request->port_of_discharge,
            'goods_description'    => $request->goods_description,
            'documents_required'   => $request->documents_required ? implode(',', (array) $request->documents_required) : null,
            'special_conditions'   => $request->special_conditions,
            'status'               => $request->status ?? $contract->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Bank Contract updated successfully.']);
    }

    public function destroy($id)
    {
        LogisticsLetterOfCredit::where('record_type', 'bank_contract')->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
