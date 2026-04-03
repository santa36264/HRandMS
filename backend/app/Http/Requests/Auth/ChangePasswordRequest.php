<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'Current password is required.',
            'current_password.current_password'  => 'Current password is incorrect.',
            'password.required'                  => 'New password is required.',
            'password.confirmed'                 => 'Password confirmation does not match.',
            'password.min'                       => 'New password must be at least 8 characters.',
            'password.letters'                   => 'New password must contain at least one letter.',
            'password.numbers'                   => 'New password must contain at least one number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'current password',
            'password'         => 'new password',
        ];
    }
}
