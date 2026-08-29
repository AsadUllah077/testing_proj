<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTermCondition;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceTermConditionController extends Controller
{
    public function index()
    {
        $companyId = auth('web')->user()?->company_id;

        $terms = InvoiceTermCondition::where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(10);

        $globalDefault = InvoiceTermCondition::whereNull('company_id')
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->first();

        return view('pages.account.organization-settings.invoice-term-condition.index', compact('terms', 'globalDefault'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $companyId = auth('web')->user()?->company_id;

        $data['company_id'] = $companyId;
        $data['is_active'] = $request->has('is_active');
        $data['is_default'] = $request->has('is_default');
        $data['created_by'] = auth('web')->id();
        $data['updated_by'] = auth('web')->id();

        if ($data['is_default']) {
            $data['is_active'] = true;
            InvoiceTermCondition::where('company_id', $companyId)->update(['is_default' => false]);
        }

        InvoiceTermCondition::create($data);

        return to_route('account.organization-settings.invoice-term-conditions.index')
            ->with('success', 'Invoice terms created successfully.');
    }

    public function update(Request $request, InvoiceTermCondition $invoiceTermCondition)
    {
        $this->authorizeCompanyTerm($invoiceTermCondition);

        $data = $this->validatedData($request);
        $companyId = auth('web')->user()?->company_id;

        $data['is_active'] = $request->has('is_active');
        $data['is_default'] = $request->has('is_default');
        $data['updated_by'] = auth('web')->id();

        if ($data['is_default']) {
            $data['is_active'] = true;
            InvoiceTermCondition::where('company_id', $companyId)
                ->where('id', '!=', $invoiceTermCondition->id)
                ->update(['is_default' => false]);
        }

        $invoiceTermCondition->update($data);

        return to_route('account.organization-settings.invoice-term-conditions.index')
            ->with('success', 'Invoice terms updated successfully.');
    }

    public function destroy(InvoiceTermCondition $invoiceTermCondition)
    {
        $this->authorizeCompanyTerm($invoiceTermCondition);
        $invoiceTermCondition->delete();

        return to_route('account.organization-settings.invoice-term-conditions.index')
            ->with('success', 'Invoice terms deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['content'] = InvoiceTermCondition::sanitizeContent($data['content']);

        if ($data['content'] === '') {
            throw ValidationException::withMessages([
                'content' => 'The terms content field is required.',
            ]);
        }

        return $data;
    }

    private function authorizeCompanyTerm(InvoiceTermCondition $term): void
    {
        abort_unless((int) $term->company_id === (int) auth('web')->user()?->company_id, 404);
    }
}
