<?php

namespace App\Http\Requests\Account;

use App\Support\AccountingDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningBalanceBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    protected function prepareForValidation(): void
    {
        foreach (['closing_date', 'conversion_date', 'go_live_date'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => AccountingDate::fromIso($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        $companyId = (int) loggedCompanyId()['company_id'];
        $batchId = $this->route('openingBalanceBatch')?->id;

        return [
            'reference' => ['required', 'string', 'max:100', Rule::unique('opening_balance_batches')->where('company_id', $companyId)->ignore($batchId)],
            'source_system' => ['required', 'string', 'max:50'],
            'closing_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:conversion_date'],
            'conversion_date' => ['required', 'date_format:Y-m-d'],
            'go_live_date' => ['nullable', 'date_format:Y-m-d', 'after:closing_date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'distinct', Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)],
            'lines.*.debit' => ['nullable', 'decimal:0,4', 'min:0'],
            'lines.*.credit' => ['nullable', 'decimal:0,4', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.source_account_code' => ['nullable', 'string', 'max:100'],
            'lines.*.source_account_name' => ['nullable', 'string', 'max:255'],
            'lines.*.source_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
