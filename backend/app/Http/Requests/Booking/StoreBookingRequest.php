<?php

namespace App\Http\Requests\Booking;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'room_id'          => ['required', 'integer', 'exists:rooms,id'],
            'check_in_date'    => ['required', 'date', 'after_or_equal:today'],
            'check_out_date'   => ['required', 'date', 'after:check_in_date'],
            'guests_count'     => ['required', 'integer', 'min:1', 'max:10'],
            'special_requests' => ['nullable', 'string', 'max:500'],
            'discount_amount'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'          => 'Please select a room.',
            'room_id.integer'           => 'Invalid room selection.',
            'room_id.exists'            => 'The selected room does not exist.',
            'check_in_date.required'    => 'Check-in date is required.',
            'check_in_date.date'        => 'Check-in date must be a valid date.',
            'check_in_date.after_or_equal' => 'Check-in date cannot be in the past.',
            'check_out_date.required'   => 'Check-out date is required.',
            'check_out_date.date'       => 'Check-out date must be a valid date.',
            'check_out_date.after'      => 'Check-out must be at least one day after check-in.',
            'guests_count.required'     => 'Number of guests is required.',
            'guests_count.integer'      => 'Number of guests must be a whole number.',
            'guests_count.min'          => 'At least 1 guest is required.',
            'guests_count.max'          => 'Maximum 10 guests allowed per booking.',
            'special_requests.max'      => 'Special requests may not exceed 500 characters.',
            'discount_amount.numeric'   => 'Discount amount must be a number.',
            'discount_amount.min'       => 'Discount amount cannot be negative.',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id'          => 'room',
            'check_in_date'    => 'check-in date',
            'check_out_date'   => 'check-out date',
            'guests_count'     => 'number of guests',
            'special_requests' => 'special requests',
            'discount_amount'  => 'discount amount',
        ];
    }
}
