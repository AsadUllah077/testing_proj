<?php

namespace App\Http\Requests\Sale;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SaleCreateRequest extends FormRequest
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
        $companyId = auth('web')->user()?->company_id;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'invoice_no' => ['required', 'string', 'max:255', Rule::unique('sales', 'invoice_no')->where('company_id', $companyId)],
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'ref_no' => 'nullable|string|max:255',
            'customer_po_no' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:2000',
            'salesperson_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'cost_centre' => 'nullable|string|max:255',
            'project' => 'nullable|string|max:255',
            'warehouse' => 'nullable|string|max:255',
            'tax_invoice_reference' => 'nullable|string|max:255',
            'electronic_invoice_qr_payload' => 'nullable|string|max:4000',
            'note' => 'nullable|string',
            'rows' => 'required|array|min:1',
            'rows.*.item' => 'nullable|string|max:255',
            'rows.*.manual_item' => 'nullable|string|max:255',
            'rows.*.description' => 'nullable|string',
            'rows.*.hs_code' => 'nullable|string|max:50',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.kg' => 'nullable|numeric|min:0',
            'rows.*.unit_of_measure' => 'nullable|string|max:30',
            'rows.*.price' => 'required|numeric|min:0',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'rows.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', auth('web')->user()?->company_id),
            ],
            'rows.*.tax_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', $companyId)],
            'rows.*.total' => 'required|numeric|min:0',
            'payment_type' => [Rule::requiredIf(fn () => (float) $this->input('paid_amount', 0) > 0), 'nullable', Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('type', 'Account')->where('sub_type', 'BANK'))],
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|gt:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'send_email' => 'nullable|boolean',
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('rows', []) as $index => $row) {
                if (blank($row['item'] ?? null) && blank($row['manual_item'] ?? null) && blank($row['description'] ?? null)) {
                    $validator->errors()->add("rows.$index.item", 'Each line requires an item or description.');
                }
                if ((float) ($row['quantity'] ?? 0) <= 0 && (float) ($row['kg'] ?? 0) <= 0) {
                    $validator->errors()->add("rows.$index.quantity", 'Each line requires a quantity or weight greater than zero.');
                }
                if ((float) ($row['quantity'] ?? 0) > 0 && (float) ($row['kg'] ?? 0) > 0) {
                    $validator->errors()->add("rows.$index.kg", 'Use either quantity or weight on a line, not both.');
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
