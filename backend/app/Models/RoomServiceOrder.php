<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'user_id', 'room_id', 'total_amount',
        'status', 'special_requests', 'ordered_at', 'delivered_at',
        'estimated_delivery_time',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery_time' => 'datetime',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopePreparing($query) { return $query->where('status', 'preparing'); }
    public function scopeDelivered($query) { return $query->where('status', 'delivered'); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function items()
    {
        return $this->hasMany(RoomServiceOrderItem::class);
    }
}
