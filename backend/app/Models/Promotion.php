<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'type', 'discount_percentage',
        'discount_amount', 'code', 'valid_from', 'valid_until',
        'is_active', 'a_b_test_variant', 'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeExpired($query) { return $query->where('valid_until', '<', now()); }
    public function scopeUpcoming($query) { return $query->where('valid_from', '>', now()); }
    public function scopeOngoing($query) { return $query->where('valid_from', '<=', now())->where('valid_until', '>=', now()); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function broadcasts()
    {
        return $this->hasMany(PromotionBroadcast::class);
    }

    public function analytics()
    {
        return $this->hasMany(PromotionAnalytic::class);
    }

    public function subscriptions()
    {
        return $this->belongsToMany(User::class, 'promotion_subscriptions', 'promotion_id', 'user_id');
    }
}
