<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'booking_reference'  => $this->booking_reference,
            'status'             => $this->status,
            'payment_status'     => $this->payment_status,

            // Dates
            'check_in_date'      => $this->check_in_date?->toDateString(),
            'check_out_date'     => $this->check_out_date?->toDateString(),
            'nights'             => $this->nights(),

            // Guests
            'guests_count'       => $this->guests_count,
            'special_requests'   => $this->special_requests,

            // Pricing
            'price_per_night'    => (float) $this->room?->price_per_night,
            'total_amount'       => (float) $this->total_amount,
            'discount_amount'    => (float) $this->discount_amount,
            'final_amount'       => (float) $this->final_amount,

            // Relations
            'room'               => new RoomResource($this->whenLoaded('room')),
            'user'               => new UserResource($this->whenLoaded('user')),

            // Cancellation
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at'        => $this->cancelled_at?->toDateTimeString(),

            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
