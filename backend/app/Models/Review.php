<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'room_id', 'booking_id',
        'rating', 'cleanliness_rating', 'service_rating', 'location_rating',
        'title', 'comment', 'is_approved', 'approved_at',
    ];

    protected $casts = [
        'is_approved'  => 'boolean',
        'approved_at'  => 'datetime',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeApproved($query)  { return $query->where('is_approved', true); }
    public function scopePending($query)   { return $query->where('is_approved', false); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function averageSubRating(): float
    {
        $ratings = array_filter([
            $this->cleanliness_rating,
            $this->service_rating,
            $this->location_rating,
        ]);

        return count($ratings) ? round(array_sum($ratings) / count($ratings), 1) : $this->rating;
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
