<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'category', 'price',
        'emoji', 'is_available', 'preparation_time',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'preparation_time' => 'integer',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeAvailable($query) { return $query->where('is_available', true); }
    public function scopeByCategory($query, $category) { return $query->where('category', $category); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function orderItems()
    {
        return $this->hasMany(RoomServiceOrderItem::class);
    }
}
