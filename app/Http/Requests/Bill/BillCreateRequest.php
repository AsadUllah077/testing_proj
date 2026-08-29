<?php

namespace App\Http\Requests\Bill;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BillCreateRequest extends FormRequest
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
        return [

            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('company_id', auth('web')->user()?->company_id)],
            'invoice_no' => [
                'required',
                'string',
                'max:255',
                // Duplicate detection: same invoice_no from the same vendor is a duplicate payment risk
                Rule::unique('bills', 'invoice_no')
                    ->where('company_id', auth('web')->user()?->company_id)
                    ->where('vendor_id', $this->vendor_id),
            ],
            'date' => 'required|date',
            'vendor_invoice_date' => 'nullable|date',
            'posting_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'ref_no' => 'nullable|string|max:255',
            'purchase_order_no' => 'nullable|string|max:255',
            'goods_receipt_no' => 'nullable|string|max:255',
            'project' => 'nullable|string|max:255',
            'cost_centre' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'warehouse' => 'nullable|string|max:255',
            'vendor_tax_invoice_reference' => 'nullable|string|max:255',
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'note' => 'nullable|string',
            'rows' => 'required|array|min:1',
            'rows.*.item' => 'nullable|string|max:255',
            'rows.*.manual_item' => 'nullable|string|max:255',
            'rows.*.description' => 'nullable|string',
            'rows.*.hs_code' => 'nullable|string|max:50',
            'rows.*.quantity' => 'nullable|numeric|min:0',
            'rows.*.kg' => 'nullable|numeric|min:0',
            'rows.*.price' => 'required|numeric|min:0',
            'rows.*.unit_of_measure' => 'nullable|string|max:30',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'rows.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', auth('web')->user()?->company_id),
            ],
            'rows.*.tax_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', auth('web')->user()?->company_id)],
            'rows.*.total' => 'nullable|numeric|min:0',
            'payment_type' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where(fn ($q) => $q->where('company_id', auth('web')->user()?->company_id)->where('type', 'Account')->where('sub_type', 'BANK'))],
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|gt:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'withholding_tax' => 'nullable|numeric|min:0',
            'withholding_account_id' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where('company_id', auth('web')->user()?->company_id)],
            'send_email' => 'sometimes|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ((float) $this->input('paid_amount', 0) > 0 && ! $this->filled('payment_type')) {
                $validator->errors()->add('payment_type', 'A bank/cash account is required when an initial payment is entered.');
            }
            if ((float) $this->input('withholding_tax', 0) > 0 && ! $this->filled('withholding_account_id')) {
                $validator->errors()->add('withholding_account_id', 'A withholding tax payable account is required.');
            }
            foreach ((array) $this->input('rows', []) as $index => $row) {
                $qty = (float) ($row['quantity'] ?? 0);
                $kg = (float) ($row['kg'] ?? 0);
                if ($qty <= 0 && $kg <= 0) $validator->errors()->add("rows.$index.quantity", 'Enter a positive quantity or weight.');
                if ($qty > 0 && $kg > 0) $validator->errors()->add("rows.$index.kg", 'Use quantity or KG on a line, not both.');
                if (blank($row['item'] ?? null) && blank($row['manual_item'] ?? null) && blank($row['description'] ?? null)) {
                    $validator->errors()->add("rows.$index.item", 'Each line requires an item or description.');
                }
            }
        });
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
            ])
        );
    }
}
