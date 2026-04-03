<?php

namespace App\Services;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    private const OTP_EXPIRY_MINUTES = 2;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function send(User $user): void
    {
        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['This email is already verified.'],
            ]);
        }

        $this->enforceResendCooldown($user);

        // Invalidate any previous unused OTPs
        EmailVerificationOtp::where('user_id', $user->id)
            ->where('is_used', false)
            ->delete();

        $otp = $this->generateOtp();

        EmailVerificationOtp::create([
            'user_id'    => $user->id,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        $user->notify(new EmailVerificationOtpNotification($otp));
    }

    public function verify(User $user, string $otp): void
    {
        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'otp' => ['This email is already verified.'],
            ]);
        }

        $record = EmailVerificationOtp::where('user_id', $user->id)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid verification code.'],
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        // Mark OTP as used and verify the user
        $record->update(['is_used' => true]);
        $user->update(['email_verified_at' => now()]);
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function enforceResendCooldown(User $user): void
    {
        $latest = EmailVerificationOtp::where('user_id', $user->id)
            ->where('is_used', false)
            ->latest()
            ->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            $wait = self::RESEND_COOLDOWN_SECONDS - $latest->created_at->diffInSeconds(now());
            throw ValidationException::withMessages([
                'otp' => ["Please wait {$wait} seconds before requesting a new code."],
            ]);
        }
    }
}
