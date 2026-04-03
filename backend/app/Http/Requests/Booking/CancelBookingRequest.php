<?php

namespace App\Http\Requests\Booking;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Cancellation reason may not exceed 300 characters.',
        ];
    }

    public function attributes(): array
    {
        return ['reason' => 'cancellation reason'];
    }
}
