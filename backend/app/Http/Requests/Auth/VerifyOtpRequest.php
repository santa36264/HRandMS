<?php

namespace App\Http\Requests\Auth;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Verification code is required.',
            'otp.digits'   => 'Verification code must be exactly 6 digits.',
        ];
    }

    public function attributes(): array
    {
        return ['otp' => 'verification code'];
    }
}
