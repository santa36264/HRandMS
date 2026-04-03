<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\RoomStatus;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_number', 'name', 'type', 'status', 'floor',
        'capacity', 'price_per_night', 'description',
        'amenities', 'images', 'is_active',
    ];

    protected $casts = [
        'amenities'       => 'array',
        'images'          => 'array',
        'is_active'       => 'boolean',
        'price_per_night' => 'decimal:2',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeAvailable($query)
    {
        return $query->where('status', RoomStatus::Available->value)->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAvailableBetween($query, string $checkIn, string $checkOut)
    {
        return $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
            $q->whereNotIn('status', ['cancelled', 'no_show'])
              ->where('check_in_date', '<', $checkOut)
              ->where('check_out_date', '>', $checkIn);
        });
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function isAvailable(): bool
    {
        return $this->status === RoomStatus::Available->value;
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function activeBooking()
    {
        return $this->hasOne(Booking::class)->where('status', 'checked_in');
    }

    public function photos()
    {
        return $this->hasMany(RoomPhoto::class)->orderBy('order');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorites');
    }
}
