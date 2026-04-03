<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConciergeBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'concierge_service_id',
        'type',
        'status',
        'scheduled_time',
        'special_requests',
        'total_amount',
        'confirmation_code',
        'provider_contact',
        'notes',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(ConciergeService::class, 'concierge_service_id');
    }
}
