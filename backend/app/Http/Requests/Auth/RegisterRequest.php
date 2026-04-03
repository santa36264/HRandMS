<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email:rfc,dns', 'max:180', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Your full name is required.',
            'name.min'          => 'Name must be at least 2 characters.',
            'name.max'          => 'Name may not exceed 100 characters.',
            'email.required'    => 'An email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'email.unique'      => 'This email address is already registered.',
            'email.max'         => 'Email address is too long.',
            'phone.regex'       => 'Phone number format is invalid (e.g. +251 912 345 678).',
            'password.required' => 'A password is required.',
            'password.confirmed'=> 'Password confirmation does not match.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.letters'  => 'Password must contain at least one letter.',
            'password.numbers'  => 'Password must contain at least one number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'full name',
            'email'    => 'email address',
            'phone'    => 'phone number',
            'password' => 'password',
        ];
    }
}
