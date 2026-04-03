<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConciergeService extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'price',
        'duration_minutes',
        'time_slots',
        'provider_name',
        'provider_phone',
        'is_available',
    ];

    protected $casts = [
        'time_slots' => 'array',
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function bookings()
    {
        return $this->hasMany(ConciergeBooking::class);
    }
}
