<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
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

            'company_id' => ['required', 'integer', Rule::in([$companyId])],
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('customers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('id')),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0|max:3650',

        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->all()
            ])
        );
    }
}
