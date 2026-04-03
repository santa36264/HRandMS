<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'status',
        'booking_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
