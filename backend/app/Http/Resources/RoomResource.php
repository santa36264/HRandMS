<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'room_number'     => $this->room_number,
            'name'            => $this->name,
            'type'            => $this->type,
            'status'          => $this->status,
            'floor'           => $this->floor,
            'capacity'        => $this->capacity,
            'price_per_night' => (float) $this->price_per_night,
            'description'     => $this->description,
            'amenities'       => $this->amenities ?? [],
            'images'          => $this->images ?? [],
            'average_rating'  => $this->reviews_avg_rating
                                    ? round((float) $this->reviews_avg_rating, 1)
                                    : ($this->relationLoaded('reviews') ? $this->averageRating() : null),
            // Injected by AvailabilityService when present
            'nights'          => $this->when(isset($this->nights), $this->nights ?? null),
            'total_price'     => $this->when(isset($this->total_price), $this->total_price ?? null),
        ];
    }
}
