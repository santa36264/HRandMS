<?php

namespace App\Http\Requests\Room;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Works for both /admin/rooms/{room} (model binding) and raw ID routes
        $roomId = $this->route('room')?->id ?? $this->route('room');

        return [
            'room_number'     => ['sometimes', 'string', 'max:10', "unique:rooms,room_number,{$roomId}"],
            'name'            => ['sometimes', 'string', 'max:100'],
            'type'            => ['sometimes', 'in:single,double,suite,deluxe,penthouse'],
            'status'          => ['sometimes', 'in:available,occupied,maintenance'],
            'floor'           => ['sometimes', 'integer', 'min:1', 'max:200'],
            'capacity'        => ['sometimes', 'integer', 'min:1', 'max:10'],
            'price_per_night' => ['sometimes', 'numeric', 'min:1', 'max:999999'],
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
            'room_number.unique'        => 'This room number is already used by another room.',
            'room_number.max'           => 'Room number may not exceed 10 characters.',
            'name.max'                  => 'Room name may not exceed 100 characters.',
            'type.in'                   => 'Room type must be one of: single, double, suite, deluxe, penthouse.',
            'status.in'                 => 'Status must be one of: available, occupied, maintenance.',
            'floor.integer'             => 'Floor must be a whole number.',
            'floor.min'                 => 'Floor must be at least 1.',
            'capacity.min'              => 'Capacity must be at least 1 guest.',
            'capacity.max'              => 'Capacity may not exceed 10 guests.',
            'price_per_night.numeric'   => 'Price must be a number.',
            'price_per_night.min'       => 'Price per night must be at least ETB 1.',
            'description.max'           => 'Description may not exceed 2000 characters.',
            'amenities.max'             => 'You may not add more than 30 amenities.',
            'amenities.*.max'           => 'Each amenity name may not exceed 50 characters.',
            'images.max'                => 'You may not add more than 20 images.',
            'images.*.url'              => 'Each image must be a valid URL.',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_number'     => 'room number',
            'name'            => 'room name',
            'type'            => 'room type',
            'status'          => 'room status',
            'floor'           => 'floor number',
            'capacity'        => 'guest capacity',
            'price_per_night' => 'price per night',
        ];
    }
}
