<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTypeRating extends Model
{
    protected $fillable = [
        'room_id',
        'average_rating',
        'total_reviews',
        'rating_1',
        'rating_2',
        'rating_3',
        'rating_4',
        'rating_5',
    ];

    protected $casts = [
        'average_rating' => 'decimal:2',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
