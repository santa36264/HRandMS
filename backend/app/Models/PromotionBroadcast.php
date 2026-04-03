<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id', 'user_id', 'variant', 'sent_at',
        'clicked_at', 'booked_at', 'status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeSent($query) { return $query->whereNotNull('sent_at'); }
    public function scopeClicked($query) { return $query->whereNotNull('clicked_at'); }
    public function scopeConverted($query) { return $query->whereNotNull('booked_at'); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
