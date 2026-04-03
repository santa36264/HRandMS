<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'min:2', 'max:100'],
            'phone'       => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:60'],
            'address'     => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min'        => 'Name must be at least 2 characters.',
            'name.max'        => 'Name may not exceed 100 characters.',
            'phone.regex'     => 'Phone number format is invalid (e.g. +251 912 345 678).',
            'nationality.max' => 'Nationality may not exceed 60 characters.',
            'address.max'     => 'Address may not exceed 255 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'full name',
            'phone'       => 'phone number',
            'nationality' => 'nationality',
            'address'     => 'address',
        ];
    }
}
