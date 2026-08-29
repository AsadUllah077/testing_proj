<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayItem;
use App\Models\ChartOfAccount;
use App\Models\StatutoryBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PayItemController extends Controller
{
    public function index(Request $request)
    {
        $searchLower = strtolower($request->search);
        $payItems = PayItem::with(['account', 'baseItem'])
            ->when($request->search, fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->sortable()
            ->paginate($request->per_page ?? 20);
        $accounts = ChartOfAccount::forCompany(Auth::user()->company_id)->where('type', 'Account')->get();

        $data = [
            'payItems' => $payItems,
            'accounts' => $accounts,
            'statutoryBodies' => StatutoryBody::where('company_id', loggedCompanyId()['company_id'])
                ->get(['id', 'name', 'code']),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.partials.pay-item-table', $data)->render(),
                'pagination' => $payItems->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.payroll.pay-item.index', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction,reimbursement',
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'calculation_type' => 'nullable|in:fixed,percentage',
            'percentage_value' => 'nullable|numeric|min:0|max:100',
            'base_item_id' => ['nullable', Rule::exists('pay_items', 'id')->where('company_id', Auth::user()->company_id)],
            'statutory_body_id' => [
                'nullable',
                Rule::exists('statutory_bodies', 'id')->where('company_id', loggedCompanyId()['company_id']),
            ],
            'is_taxable' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $payItem = PayItem::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'type' => $request->type,
            'account_id' => $request->account_id,
            'calculation_type' => $request->calculation_type ?? 'fixed',
            'percentage_value' => $request->percentage_value,
            'base_item_id' => $request->base_item_id,
            'statutory_body_id' => $request->statutory_body_id,
            'is_taxable' => $request->boolean('is_taxable'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pay Item created successfully']);
    }

    public function update(Request $request, $id)
    {
        $payItem = PayItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction,reimbursement',
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'calculation_type' => 'nullable|in:fixed,percentage',
            'percentage_value' => 'nullable|numeric|min:0|max:100',
            'base_item_id' => ['nullable', Rule::exists('pay_items', 'id')->where('company_id', Auth::user()->company_id)],
            'statutory_body_id' => [
                'nullable',
                Rule::exists('statutory_bodies', 'id')->where('company_id', loggedCompanyId()['company_id']),
            ],
            'is_taxable' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $payItem->update([
            'name' => $request->name,
            'type' => $request->type,
            'account_id' => $request->account_id,
            'calculation_type' => $request->calculation_type ?? 'fixed',
            'percentage_value' => $request->percentage_value,
            'base_item_id' => $request->base_item_id,
            'statutory_body_id' => $request->statutory_body_id,
            'is_taxable' => $request->boolean('is_taxable'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pay Item updated successfully']);
    }

    public function destroy($id)
    {
        $payItem = PayItem::findOrFail($id);
        $payItem->delete();
        return response()->json(['success' => true, 'message' => 'Pay Item deleted successfully']);
    }
}
