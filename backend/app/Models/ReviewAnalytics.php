<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewAnalytics extends Model
{
    protected $fillable = [
        'date',
        'average_rating',
        'total_reviews',
        'rating_1',
        'rating_2',
        'rating_3',
        'rating_4',
        'rating_5',
        'completion_rate',
        'response_time_hours',
        'keywords',
    ];

    protected $casts = [
        'date' => 'date',
        'average_rating' => 'decimal:2',
        'keywords' => 'array',
    ];
}
