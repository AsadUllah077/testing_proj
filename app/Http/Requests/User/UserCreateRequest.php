<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserCreateRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'username' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'regex:/^[0-9+\-\s()]+$/', 'min:10', 'max:20'],
            'password' => ['required',  'min:5'],
            'profile_pic' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'designation' => ['required', 'string', 'max:100'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'companies' => ['required', 'array'],
            'companies.*' => ['integer', 'exists:companies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.regex' => 'First name should only contain letters',
            'last_name.required' => 'Last name is required',
            'last_name.regex' => 'Last name should only contain letters',
            'username.required' => 'Username is required',
            'username.regex' => 'Username can only contain letters, numbers and underscores',
            'username.unique' => 'This username is already taken',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'phone_number.required' => 'Phone number is required',
            'phone_number.regex' => 'Please provide a valid phone number',
            'profile_pic.image' => 'File must be an image',
            'profile_pic.mimes' => 'Image must be jpeg, png, jpg or gif',
            'profile_pic.max' => 'Image size should not exceed 2MB',
            'designation.required' => 'Designation is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 5 characters',
            'company_id.required' => 'Company is required',
            'company_id.integer' => 'Please provide a valid company ID',
            'company_id.exists' => 'Company does not exist',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ])
        );
    }
}
