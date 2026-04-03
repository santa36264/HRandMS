<?php

namespace App\Http\Requests\Booking;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show']),
            ],
            'cancellation_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === 'cancelled'),
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'              => 'A booking status is required.',
            'status.in'                    => 'Invalid status. Allowed: pending, confirmed, checked_in, checked_out, cancelled, no_show.',
            'cancellation_reason.required' => 'A cancellation reason is required when cancelling a booking.',
            'cancellation_reason.max'      => 'Cancellation reason may not exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status'              => 'booking status',
            'cancellation_reason' => 'cancellation reason',
        ];
    }
}
