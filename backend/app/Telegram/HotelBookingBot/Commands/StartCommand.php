<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\User;
use App\Models\Promotion;
use Illuminate\Support\Facades\Log;

class StartCommand
{
    /**
     * User ID from Telegram
     */
    private int $userId;

    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * User's first name
     */
    private string $firstName;

    /**
     * User's username
     */
    private ?string $username;

    /**
     * Constructor
     */
    public function __construct(int $userId, int $chatId, string $firstName, ?string $username = null)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
        $this->firstName = $firstName;
        $this->username = $username;
    }

    /**
     * Execute the start command
     */
    public function execute(): array
    {
        try {
            // Link or create user with Telegram chat ID
            $user = $this->linkUserWithTelegram();

            // Build welcome message
            $message = $this->buildWelcomeMessage($user);

            // Build keyboard
            $keyboard = $this->buildMainMenuKeyboard($user);

            // Get promotional message if available
            $promoMessage = $this->getPromotionalMessage();

            return [
                'success' => true,
                'message' => $message,
                'keyboard' => $keyboard,
                'promo' => $promoMessage,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Error executing start command', [
                'error' => $e->getMessage(),
                'telegram_user_id' => $this->userId,
            ]);

            return [
                'success' => false,
                'message' => "❌ Error initializing bot. Please try again later.",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Link user with Telegram chat ID
     */
    private function linkUserWithTelegram(): ?User
    {
        // Try to find user by Telegram ID
        $user = User::where('telegram_user_id', $this->userId)->first();

        if ($user) {
            // Update chat ID if changed
            if ($user->telegram_chat_id !== $this->chatId) {
                $user->update(['telegram_chat_id' => $this->chatId]);
            }
            return $user;
        }

        // Try to find user by username
        if ($this->username) {
            $user = User::where('username', $this->username)->first();
            if ($user) {
                $user->update([
                    'telegram_user_id' => $this->userId,
                    'telegram_chat_id' => $this->chatId,
                ]);
                return $user;
            }
        }

        // User not found - return null (will show register button)
        return null;
    }

    /**
     * Build welcome message
     */
    private function buildWelcomeMessage(?User $user): string
    {
        $hotelName = config('app.name', 'SATAAB Hotel');
        
        if ($user) {
            return "🏨 <b>Welcome back to {$hotelName}!</b>\n\n"
                . "Hello <b>{$user->name}</b>,\n\n"
                . "We're excited to help you with your hotel reservations.\n\n"
                . "<b>What would you like to do?</b>";
        } else {
            return "🏨 <b>Welcome to {$hotelName}!</b>\n\n"
                . "Hello <b>{$this->firstName}</b>,\n\n"
                . "We're excited to help you with your hotel reservations.\n\n"
                . "Please register to get started and enjoy exclusive benefits!\n\n"
                . "<b>What would you like to do?</b>";
        }
    }

    /**
     * Build main menu keyboard
     */
    private function buildMainMenuKeyboard(?User $user): array
    {
        $buttons = [];

        // First row - Search and Bookings
        $buttons[] = [
            ['text' => '🔍 Search Rooms', 'callback_data' => 'menu_search_rooms'],
            ['text' => '📅 My Bookings', 'callback_data' => 'menu_my_bookings'],
        ];

        // Second row - Profile and Contact
        $buttons[] = [
            ['text' => '👤 My Profile', 'callback_data' => 'menu_my_profile'],
            ['text' => '📞 Contact Hotel', 'callback_data' => 'menu_contact'],
        ];

        // Third row - Help and Register (if not logged in)
        if ($user) {
            $buttons[] = [
                ['text' => '❓ Help', 'callback_data' => 'menu_help'],
                ['text' => '⚙️ Settings', 'callback_data' => 'menu_settings'],
            ];
        } else {
            $buttons[] = [
                ['text' => '❓ Help', 'callback_data' => 'menu_help'],
            ];
            $buttons[] = [
                ['text' => '✅ Register', 'callback_data' => 'menu_register'],
            ];
        }

        return $buttons;
    }

    /**
     * Get promotional message
     */
    private function getPromotionalMessage(): ?string
    {
        try {
            $promotion = Promotion::where('active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if ($promotion) {
                return "🎉 <b>Special Offer!</b>\n"
                    . "{$promotion->title}\n"
                    . "{$promotion->description}\n"
                    . "Use code: <code>{$promotion->code}</code>";
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching promotion', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get user registration URL
     */
    public function getRegistrationUrl(): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        return "{$frontendUrl}/register?telegram_id={$this->userId}";
    }
}
