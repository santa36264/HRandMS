<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\BookingStatus;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_reference', 'user_id', 'room_id',
        'check_in_date', 'check_out_date', 'guests_count',
        'total_amount', 'discount_amount', 'final_amount',
        'status', 'payment_status', 'special_requests',
        'cancellation_reason', 'cancelled_at',
    ];

    protected $casts = [
        'check_in_date'     => 'date',
        'check_out_date'    => 'date',
        'total_amount'      => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'final_amount'      => 'decimal:2',
        'cancelled_at'      => 'datetime',
    ];

    // -----------------------------------------------
    // Boot — auto-generate booking reference
    // -----------------------------------------------
    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if ($booking->booking_reference) return;

            // Generate a unique reference with retry loop
            do {
                $ref = 'BK-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            } while (static::where('booking_reference', $ref)->exists());

            $booking->booking_reference = $ref;
        });
    }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopePending($query)    { return $query->where('status', BookingStatus::Pending->value); }
    public function scopeConfirmed($query)  { return $query->where('status', BookingStatus::Confirmed->value); }
    public function scopeActive($query)     { return $query->whereIn('status', ['confirmed', 'checked_in']); }
    public function scopeUpcoming($query)   { return $query->where('check_in_date', '>=', today()); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function nights(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending_payment', 'confirmed']);
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
