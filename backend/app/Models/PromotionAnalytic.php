<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id', 'variant', 'total_sent', 'total_clicked',
        'total_converted', 'conversion_rate', 'click_rate',
    ];

    protected $casts = [
        'total_sent' => 'integer',
        'total_clicked' => 'integer',
        'total_converted' => 'integer',
        'conversion_rate' => 'decimal:4',
        'click_rate' => 'decimal:4',
    ];

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
