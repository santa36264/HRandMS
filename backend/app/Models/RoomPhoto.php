<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'photo_url',
        'order',
        'alt_text',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the room that owns this photo
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
