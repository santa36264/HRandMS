<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'    => 'Reset token is missing.',
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'email.exists'      => 'No account found with this email address.',
            'password.required' => 'A new password is required.',
            'password.confirmed'=> 'Password confirmation does not match.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.letters'  => 'Password must contain at least one letter.',
            'password.numbers'  => 'Password must contain at least one number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token'    => 'reset token',
            'email'    => 'email address',
            'password' => 'new password',
        ];
    }
}
