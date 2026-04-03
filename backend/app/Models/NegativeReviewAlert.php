<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NegativeReviewAlert extends Model
{
    protected $fillable = [
        'review_id',
        'rating',
        'comment',
        'status',
        'admin_notes',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
