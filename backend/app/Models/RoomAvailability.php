<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'date', 'is_available', 'price',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeAvailable($query) { return $query->where('is_available', true); }
    public function scopeUnavailable($query) { return $query->where('is_available', false); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
