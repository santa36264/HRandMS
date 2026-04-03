<?php

namespace App\Http\Requests\Room;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'check_in'  => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests'    => ['nullable', 'integer', 'min:1', 'max:10'],
            'type'      => ['nullable', 'string', 'in:single,double,suite,deluxe,penthouse'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gt:min_price'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required'         => 'Check-in date is required.',
            'check_in.date'             => 'Check-in must be a valid date.',
            'check_in.after_or_equal'   => 'Check-in date cannot be in the past.',
            'check_out.required'        => 'Check-out date is required.',
            'check_out.date'            => 'Check-out must be a valid date.',
            'check_out.after'           => 'Check-out must be at least one day after check-in.',
            'guests.integer'            => 'Number of guests must be a whole number.',
            'guests.min'                => 'At least 1 guest is required.',
            'guests.max'                => 'Maximum 10 guests allowed.',
            'type.in'                   => 'Room type must be one of: single, double, suite, deluxe, penthouse.',
            'min_price.numeric'         => 'Minimum price must be a number.',
            'min_price.min'             => 'Minimum price cannot be negative.',
            'max_price.numeric'         => 'Maximum price must be a number.',
            'max_price.gt'              => 'Maximum price must be greater than minimum price.',
        ];
    }

    public function attributes(): array
    {
        return [
            'check_in'  => 'check-in date',
            'check_out' => 'check-out date',
            'guests'    => 'number of guests',
            'type'      => 'room type',
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
        ];
    }
}
