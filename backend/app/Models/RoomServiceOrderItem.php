<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomServiceOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_service_order_id', 'menu_item_id', 'quantity',
        'unit_price', 'total_price', 'special_instructions',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function order()
    {
        return $this->belongsTo(RoomServiceOrder::class, 'room_service_order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
