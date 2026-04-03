<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.exists'   => 'No account found with this email address.',
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'email address'];
    }
}
