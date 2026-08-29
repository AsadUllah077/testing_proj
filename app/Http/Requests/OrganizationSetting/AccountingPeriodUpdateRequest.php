<?php

namespace App\Http\Requests\OrganizationSetting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountingPeriodUpdateRequest extends FormRequest
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
        $companyId = loggedCompanyId()['company_id'];
        return [
            'tax_rate_id' => 'nullable',
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounting_periods', 'name')->where('company_id', $companyId)->ignore($this->route('id')),
            ],
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'tax_period'  => 'nullable|string|max:50',
            'default_sale_tax_rate_id'  => 'nullable',
            'default_purchase_tax_rate_id'  => 'nullable',
            'is_active'   => 'boolean',
            'is_locked'   => 'boolean',
        ];
    }
}
