<?php

namespace App\Http\Requests\Room;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'room_number'     => ['required', 'string', 'max:10', 'unique:rooms,room_number'],
            'name'            => ['required', 'string', 'max:100'],
            'type'            => ['required', 'in:single,double,suite,deluxe,penthouse'],
            'floor'           => ['required', 'integer', 'min:1', 'max:200'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:10'],
            'price_per_night' => ['required', 'numeric', 'min:1', 'max:999999'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'amenities'       => ['nullable', 'array', 'max:30'],
            'amenities.*'     => ['string', 'max:50'],
            'images'          => ['nullable', 'array', 'max:20'],
            'images.*'        => ['string', 'url', 'max:500'],
            'is_active'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_number.required'      => 'Room number is required.',
            'room_number.unique'        => 'This room number is already in use.',
            'room_number.max'           => 'Room number may not exceed 10 characters.',
            'name.required'             => 'Room name is required.',
            'name.max'                  => 'Room name may not exceed 100 characters.',
            'type.required'             => 'Room type is required.',
            'type.in'                   => 'Room type must be one of: single, double, suite, deluxe, penthouse.',
            'floor.required'            => 'Floor number is required.',
            'floor.integer'             => 'Floor must be a whole number.',
            'floor.min'                 => 'Floor must be at least 1.',
            'floor.max'                 => 'Floor number seems unrealistically high.',
            'capacity.required'         => 'Room capacity is required.',
            'capacity.min'              => 'Capacity must be at least 1 guest.',
            'capacity.max'              => 'Capacity may not exceed 10 guests.',
            'price_per_night.required'  => 'Price per night is required.',
            'price_per_night.numeric'   => 'Price must be a number.',
            'price_per_night.min'       => 'Price per night must be at least ETB 1.',
            'price_per_night.max'       => 'Price per night exceeds the allowed maximum.',
            'description.max'           => 'Description may not exceed 2000 characters.',
            'amenities.array'           => 'Amenities must be a list.',
            'amenities.max'             => 'You may not add more than 30 amenities.',
            'amenities.*.max'           => 'Each amenity name may not exceed 50 characters.',
            'images.array'              => 'Images must be a list.',
            'images.max'                => 'You may not add more than 20 images.',
            'images.*.url'              => 'Each image must be a valid URL.',
            'images.*.max'              => 'Image URL may not exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_number'     => 'room number',
            'name'            => 'room name',
            'type'            => 'room type',
            'floor'           => 'floor number',
            'capacity'        => 'guest capacity',
            'price_per_night' => 'price per night',
            'description'     => 'description',
            'amenities'       => 'amenities',
            'images'          => 'images',
            'is_active'       => 'active status',
        ];
    }
}
