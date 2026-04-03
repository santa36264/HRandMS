<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id', 'booking_id', 'user_id',
        'amount', 'percentage', 'reason',
        'cancellation_reference', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'integer',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeProcessed($query) { return $query->where('status', 'processed'); }
    public function scopeFailed($query) { return $query->where('status', 'failed'); }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
