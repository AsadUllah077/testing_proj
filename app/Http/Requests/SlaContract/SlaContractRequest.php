<?php

namespace App\Http\Requests\SlaContract;

use Illuminate\Foundation\Http\FormRequest;

class SlaContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_id' => ['required', 'integer', 'exists:logistics_v2_carriers,id'],
            'transport_mode' => ['required', 'string', 'max:100'],
            'contract_term' => ['required', 'string', 'max:100'],
            'min_otd_guarantee' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_claim_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'volume_commitment' => ['nullable', 'string', 'max:255'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_expiry_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'rate_lock_period' => ['nullable', 'string', 'max:100'],
            'penalty_clause' => ['nullable', 'string', 'max:100'],
            'penalty_rate_amount' => ['nullable', 'string', 'max:255'],
            'review_frequency' => ['nullable', 'string', 'max:100'],
            'special_conditions' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,active,breach,expired,terminated'],
            'performance_status' => ['nullable', 'in:met,marginal,breach,pending'],
        ];
    }

    public function messages(): array
    {
        return [
            'carrier_id.required' => 'Please select a carrier.',
            'carrier_id.exists' => 'Selected carrier does not exist.',
            'min_otd_guarantee.required' => 'Minimum OTD guarantee is required.',
            'max_claim_rate.required' => 'Maximum claim rate is required.',
            'contract_expiry_date.after_or_equal' => 'Expiry date must be on or after the start date.',
        ];
    }
}
