<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'rating'              => $this->rating,
            'cleanliness_rating'  => $this->cleanliness_rating,
            'service_rating'      => $this->service_rating,
            'location_rating'     => $this->location_rating,
            'title'               => $this->title,
            'comment'             => $this->comment,
            'is_approved'         => $this->is_approved,
            'approved_at'         => $this->approved_at?->toDateTimeString(),
            'average_sub_rating'  => $this->averageSubRating(),
            'room'                => new RoomResource($this->whenLoaded('room')),
            'user'                => new UserResource($this->whenLoaded('user')),
            'booking'             => $this->whenLoaded('booking', fn() => $this->booking ? [
                'id'                => $this->booking->id,
                'booking_reference' => $this->booking->booking_reference,
                'check_in_date'     => $this->booking->check_in_date?->toDateString(),
                'check_out_date'    => $this->booking->check_out_date?->toDateString(),
            ] : null),
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }
}
