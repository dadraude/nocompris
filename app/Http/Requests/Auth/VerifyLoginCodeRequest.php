<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyLoginCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('Enter the 6-digit code we emailed you.'),
            'code.digits' => __('The verification code must contain 6 digits.'),
        ];
    }

    /**
     * Prepare the request data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => preg_replace('/\D+/', '', (string) $this->input('code')),
        ]);
    }
}
