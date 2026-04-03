<?php

namespace App\Telegram\HotelBookingBot\Middleware;

use Illuminate\Support\Facades\Log;

class AdminAuthMiddleware
{
    /**
     * Check if user is admin
     */
    public static function isAdmin(int $userId): bool
    {
        $adminIds = config('telegram.admin_ids', []);
        return in_array($userId, $adminIds);
    }

    /**
     * Get admin error message
     */
    public static function getUnauthorizedMessage(): string
    {
        return "❌ <b>Access Denied</b>\n\n"
            . "This command is only available to administrators.\n"
            . "If you believe this is an error, contact the hotel management.";
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess(int $userId, string $command): void
    {
        Log::warning('Unauthorized admin command attempt', [
            'user_id' => $userId,
            'command' => $command,
            'timestamp' => now(),
        ]);
    }
}
