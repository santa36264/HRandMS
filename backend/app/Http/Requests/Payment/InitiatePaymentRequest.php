<?php

namespace App\Http\Requests\Payment;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'gateway'    => ['required', 'string', 'in:chapa'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'A booking is required to initiate payment.',
            'booking_id.integer'  => 'Invalid booking reference.',
            'booking_id.exists'   => 'The selected booking does not exist.',
            'gateway.required'    => 'Please select a payment gateway.',
            'gateway.in'          => 'Only Chapa is supported as a payment gateway.',
        ];
    }

    public function attributes(): array
    {
        return [
            'booking_id' => 'booking',
            'gateway'    => 'payment gateway',
        ];
    }
}
