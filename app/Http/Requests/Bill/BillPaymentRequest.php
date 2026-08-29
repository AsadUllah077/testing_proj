<?php

namespace App\Http\Requests\Bill;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillPaymentRequest extends FormRequest
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
    public function rules()
    {
        return [
            'bill_id' => ['required', Rule::exists('bills', 'id')->where('company_id', auth('web')->user()?->company_id)],
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($q) => $q->where('company_id', auth('web')->user()?->company_id)->where('type', 'Account')->where('sub_type', 'BANK')),
            ],
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'bill_id.required' => 'bill ID is required',
            'bill_id.exists' => 'Invalid bill selected',
            'payment_date.required' => 'Payment date is required',
            'payment_date.date' => 'Invalid payment date format',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Amount must be greater than 0',
            'account_id.required' => 'Payment account is required',
            'account_id.exists' => 'Invalid account selected',
            'description.max' => 'Description cannot exceed 500 characters',
        ];
    }
}
