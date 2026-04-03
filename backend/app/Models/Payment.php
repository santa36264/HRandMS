<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'booking_id', 'user_id', 'gateway',
        'amount', 'currency', 'method', 'status',
        'gateway_response', 'notes', 'paid_at',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'gateway_response'  => 'array',
        'paid_at'           => 'datetime',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopePending($query)   { return $query->where('status', 'pending'); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isRefunded(): bool  { return $this->status === 'refunded'; }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
