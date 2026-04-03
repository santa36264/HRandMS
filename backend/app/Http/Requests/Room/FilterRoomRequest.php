<?php

namespace App\Http\Requests\Room;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class FilterRoomRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'check_in'    => ['nullable', 'date', 'after_or_equal:today'],
            'check_out'   => ['nullable', 'date', 'after:check_in', 'required_with:check_in'],
            'type'        => ['nullable', 'in:single,double,suite,deluxe,penthouse'],
            'status'      => ['nullable', 'in:available,occupied,maintenance'],
            'capacity'    => ['nullable', 'integer', 'min:1', 'max:10'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'gt:min_price'],
            'floor'       => ['nullable', 'integer', 'min:1'],
            'amenities'   => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:50'],
            'search'      => ['nullable', 'string', 'max:100'],
            'sort_by'     => ['nullable', 'in:price_per_night,capacity,floor,room_number,name'],
            'sort_dir'    => ['nullable', 'in:asc,desc'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'        => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.date'             => 'Check-in must be a valid date.',
            'check_in.after_or_equal'   => 'Check-in date cannot be in the past.',
            'check_out.date'            => 'Check-out must be a valid date.',
            'check_out.after'           => 'Check-out must be after check-in.',
            'check_out.required_with'   => 'Check-out date is required when check-in is provided.',
            'type.in'                   => 'Room type must be one of: single, double, suite, deluxe, penthouse.',
            'status.in'                 => 'Status must be one of: available, occupied, maintenance.',
            'capacity.integer'          => 'Capacity must be a whole number.',
            'capacity.min'              => 'Capacity must be at least 1.',
            'capacity.max'              => 'Capacity may not exceed 10.',
            'min_price.numeric'         => 'Minimum price must be a number.',
            'min_price.min'             => 'Minimum price cannot be negative.',
            'max_price.numeric'         => 'Maximum price must be a number.',
            'max_price.gt'              => 'Maximum price must be greater than minimum price.',
            'floor.integer'             => 'Floor must be a whole number.',
            'floor.min'                 => 'Floor must be at least 1.',
            'search.max'                => 'Search term may not exceed 100 characters.',
            'sort_by.in'                => 'Sort field must be one of: price_per_night, capacity, floor, room_number, name.',
            'sort_dir.in'               => 'Sort direction must be asc or desc.',
            'per_page.integer'          => 'Per page must be a whole number.',
            'per_page.min'              => 'Per page must be at least 1.',
            'per_page.max'              => 'Per page may not exceed 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'check_in'  => 'check-in date',
            'check_out' => 'check-out date',
            'type'      => 'room type',
            'status'    => 'room status',
            'capacity'  => 'guest capacity',
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
            'sort_by'   => 'sort field',
            'sort_dir'  => 'sort direction',
            'per_page'  => 'results per page',
        ];
    }
}
