<?php

namespace App\Http\Requests\OrganizationSetting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ChartOfAccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        $companyId = loggedCompanyId()['company_id'] ?? null;

        return [
            'account_number' => [
                'required',
                'string',
                Rule::unique('chart_of_accounts', 'account_number')
                    ->ignore($id)
                    ->when($companyId && Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($rule) => $rule->where('company_id', $companyId)),
            ],
            'acc_no' => [
                'nullable',
                'string',
                Rule::unique('chart_of_accounts', 'acc_no')
                    ->ignore($id)
                    ->when($companyId && Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($rule) => $rule->where('company_id', $companyId)),
            ],
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')
                    ->when($companyId && Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($rule) => $rule->where('company_id', $companyId)),
                Rule::notIn([$id]),   // Prevent selecting itself as parent
            ],
            'category' => ['required', Rule::in(['Assets', 'Liabilities', 'Equity', 'Expenses', 'Revenue', 'COGS'])],
            'type' => ['required', Rule::in(['Group', 'Account'])],
            'initial_balance' => 'nullable|numeric|min:0',
            'opening_balance_amount' => 'nullable|numeric|min:0',
            'opening_balance_side' => ['nullable', Rule::in(['debit', 'credit'])],
            'opening_date' => 'nullable|date',
            'tax_code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'system_account_type' => 'nullable|string|max:100',
            'sub_type' => 'nullable|string|max:100'
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $amount = (float) ($this->input('opening_balance_amount') ?? $this->input('initial_balance') ?? 0);
            $side = $this->input('opening_balance_side');

            if ($amount <= 0 && $side) {
                $validator->errors()->add('opening_balance_amount', 'Opening balance amount must be greater than zero when a side is selected.');
            }

            if ($amount > 0 && ! $side) {
                $validator->errors()->add('opening_balance_side', 'Choose whether the opening balance is Debit or Credit.');
            }

            if ($amount > 0 && $this->input('type') === 'Group') {
                $validator->errors()->add('type', 'Opening balances cannot be posted to group accounts.');
            }

            if ($amount > 0 && in_array($this->input('category'), ['Revenue', 'Expenses', 'COGS'], true)) {
                $validator->errors()->add('category', 'Opening balances for Revenue, Expenses and COGS are not allowed at the start of a new fiscal year. Bring prior-year profit or loss forward through Retained Earnings.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'opening_balance_side.in' => 'Opening balance side must be Debit or Credit.',
        ];
    }
}
