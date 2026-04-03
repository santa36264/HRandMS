<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewCollectionSession extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'status',
        'rating',
        'what_liked',
        'improvement_suggestions',
        'would_recommend',
        'permission_to_display',
        'admin_reply',
        'rating_given_at',
        'completed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'would_recommend' => 'boolean',
        'permission_to_display' => 'boolean',
        'rating_given_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'booking_id', 'booking_id');
    }
}
