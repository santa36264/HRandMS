<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\BookingStatus;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role',
        'avatar', 'google_id', 'address', 'nationality', 'id_number',
        'email_verified_at', 'language_preference', 'telegram_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // -----------------------------------------------
    // Password Reset — point to frontend URL
    // -----------------------------------------------
    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        ResetPasswordNotification::createUrlUsing(function ($notifiable, $token) use ($frontendUrl) {
            return $frontendUrl . '/auth/reset-password?token=' . $token . '&email=' . urlencode($notifiable->email);
        });

        $this->notify(new ResetPasswordNotification($token));
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isStaff(): bool  { return $this->role === 'staff'; }
    public function isGuest(): bool  { return $this->role === 'guest'; }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function emailVerificationOtps()
    {
        return $this->hasMany(\App\Models\EmailVerificationOtp::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function maintenanceAssignments()
    {
        return $this->hasMany(MaintenanceSchedule::class, 'assigned_to');
    }

    public function activeBookings()
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::CheckedIn->value]);
    }

    public function favorites()
    {
        return $this->belongsToMany(Room::class, 'user_favorites');
    }

    public function promotionSubscriptions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_subscriptions', 'user_id', 'promotion_id')
            ->withTimestamps();
    }

    public function promotionBroadcasts()
    {
        return $this->hasMany(PromotionBroadcast::class);
    }
}
