<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\BankRule;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankRuleController extends Controller
{
    public function index()
    {
        $rules = BankRule::with(['bankAccount', 'contraAccount'])
            ->where('company_id', $this->companyId())
            ->orderBy('priority')
            ->orderByDesc('usage_count')
            ->paginate(25);
        $accounts = ChartOfAccount::forCompany($this->companyId())->orderBy('name')->get();

        return view('pages.account.bank-rules.index', compact('rules', 'accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRule($request);
        $data['company_id'] = $this->companyId();
        $data['payee'] = BankRule::normalizePayee($data['payee']);
        $data['is_active'] = $request->boolean('is_active', true);
        BankRule::create($data);

        return back()->with('success', 'Bank rule created. It suggests an account but never posts automatically.');
    }

    public function update(Request $request, BankRule $bankRule)
    {
        abort_if((int) $bankRule->company_id !== $this->companyId(), 404);
        $data = $this->validateRule($request, $bankRule->id);
        $data['payee'] = BankRule::normalizePayee($data['payee']);
        $data['is_active'] = $request->boolean('is_active');
        $bankRule->update($data);

        return back()->with('success', 'Bank rule updated.');
    }

    public function feedback(Request $request, BankRule $bankRule)
    {
        abort_if((int) $bankRule->company_id !== $this->companyId(), 404);
        $data = $request->validate(['outcome' => ['required', Rule::in(['accepted', 'rejected'])]]);
        if ($data['outcome'] === 'accepted') {
            $bankRule->increment('accepted_count');
            $bankRule->update(['last_used_at' => now(), 'approved_by' => auth('web')->id(), 'approved_at' => now()]);
        } else {
            $bankRule->increment('rejected_count');
            $bankRule->update(['last_rejected_at' => now()]);
        }

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Rule feedback recorded.');
    }

    public function destroy(BankRule $bankRule)
    {
        abort_if((int) $bankRule->company_id !== $this->companyId(), 404);
        $bankRule->delete();

        return back()->with('success', 'Bank rule removed.');
    }

    private function validateRule(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'bank_account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', $this->companyId())],
            'payee' => [
                'required', 'string', 'max:255',
                Rule::unique('bank_rules')->where(fn ($query) => $query
                    ->where('company_id', $this->companyId())
                    ->where('bank_account_id', $request->bank_account_id))->ignore($ignoreId),
            ],
            'match_type' => ['required', Rule::in(['exact', 'contains', 'starts_with'])],
            'direction' => ['required', Rule::in(['both', 'money_in', 'money_out'])],
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|gte:min_amount',
            'priority' => 'required|integer|min:1|max:9999',
            'contra_account_id' => ['required', 'different:bank_account_id', Rule::exists('chart_of_accounts', 'id')->where('company_id', $this->companyId())],
            'is_active' => 'nullable|boolean',
            'tax_code' => 'nullable|string|max:100',
            'tracking_category' => 'nullable|string|max:255',
        ]);
    }

    private function companyId(): int
    {
        return (int) (loggedCompanyId()['company_id'] ?? auth('web')->user()->company_id);
    }
}
