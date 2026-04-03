<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\User;
use App\Services\RoomService;
use Illuminate\Support\Facades\Log;

class MenuCallbackHandler
{
    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * User ID
     */
    private int $userId;

    /**
     * Callback data
     */
    private string $callbackData;

    /**
     * Callback ID
     */
    private string $callbackId;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId, string $callbackData, string $callbackId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
    }

    /**
     * Handle menu callback
     */
    public function handle(): array
    {
        try {
            $action = str_replace('menu_', '', $this->callbackData);

            $response = match ($action) {
                'search_rooms' => $this->handleSearchRooms(),
                'my_bookings' => $this->handleMyBookings(),
                'my_profile' => $this->handleMyProfile(),
                'contact' => $this->handleContact(),
                'help' => $this->handleHelp(),
                'settings' => $this->handleSettings(),
                'register' => $this->handleRegister(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling menu callback', [
                'error' => $e->getMessage(),
                'callback_data' => $this->callbackData,
            ]);

            return [
                'success' => false,
                'callback_id' => $this->callbackId,
                'message' => '❌ Error processing request',
            ];
        }
    }

    /**
     * Handle search rooms
     */
    private function handleSearchRooms(): array
    {
        $searchCommand = new RoomSearchCommand(app('App\Services\RoomService'));
        return $searchCommand->startSearch();
    }

    /**
     * Handle my bookings
     */
    private function handleMyBookings(): array
    {
        $command = new MyBookingsCommand($this->chatId, $this->userId);
        $result = $command->showBookings();

        if ($result['success']) {
            return [
                'message' => $result['message'],
                'keyboard' => $result['buttons'],
            ];
        }

        return [
            'message' => $result['message'],
        ];
    }

    /**
     * Handle my profile
     */
    private function handleMyProfile(): array
    {
        $user = User::where('telegram_user_id', $this->userId)->first();

        if (!$user) {
            return [
                'message' => "❌ Please register first to view your profile.\n\n"
                    . "Use the Register button to get started.",
            ];
        }

        return [
            'message' => "<b>👤 My Profile</b>\n\n"
                . "Name: {$user->name}\n"
                . "Email: {$user->email}\n"
                . "Phone: " . ($user->phone ?? 'Not provided') . "\n"
                . "Member Since: {$user->created_at->format('M d, Y')}\n\n"
                . "Total Bookings: {$user->bookings()->count()}\n"
                . "Total Spent: {$user->bookings()->sum('total_price')} ETB",
            'keyboard' => [
                [
                    ['text' => '✏️ Edit Profile', 'callback_data' => 'profile_edit'],
                    ['text' => '🔐 Change Password', 'callback_data' => 'profile_password'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                ]
            ],
        ];
    }

    /**
     * Handle contact
     */
    private function handleContact(): array
    {
        return [
            'message' => "<b>📞 Contact Hotel</b>\n\n"
                . "📱 Phone: +251 911 234 567\n"
                . "📧 Email: info@sataabhotel.com\n"
                . "🌐 Website: www.sataabhotel.com\n"
                . "📍 Address: Addis Ababa, Ethiopia\n\n"
                . "⏰ Working Hours:\n"
                . "Monday - Friday: 8:00 AM - 6:00 PM\n"
                . "Saturday - Sunday: 9:00 AM - 5:00 PM",
            'keyboard' => [
                [
                    ['text' => '💬 Send Message', 'url' => 'https://wa.me/251911234567'],
                    ['text' => '📧 Email Us', 'url' => 'mailto:info@sataabhotel.com'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                ]
            ],
        ];
    }

    /**
     * Handle help
     */
    private function handleHelp(): array
    {
        return [
            'message' => "<b>❓ Help & FAQ</b>\n\n"
                . "<b>How to book a room?</b>\n"
                . "1. Click 'Search Rooms'\n"
                . "2. Select your dates\n"
                . "3. Choose a room\n"
                . "4. Complete payment\n\n"
                . "<b>How to cancel a booking?</b>\n"
                . "Go to 'My Bookings' and select the booking you want to cancel.\n\n"
                . "<b>What's your cancellation policy?</b>\n"
                . "Free cancellation up to 24 hours before check-in.\n\n"
                . "<b>Need more help?</b>\n"
                . "Contact us using the Contact Hotel option.",
            'keyboard' => [
                [
                    ['text' => '📞 Contact Support', 'callback_data' => 'menu_contact'],
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                ]
            ],
        ];
    }

    /**
     * Handle settings
     */
    private function handleSettings(): array
    {
        $user = User::where('telegram_user_id', $this->userId)->first();
        $notificationsEnabled = $user?->telegram_notifications_enabled ?? true;

        return [
            'message' => "<b>⚙️ Settings</b>\n\n"
                . "Notifications: " . ($notificationsEnabled ? '✅ Enabled' : '❌ Disabled'),
            'keyboard' => [
                [
                    ['text' => ($notificationsEnabled ? '🔕 Disable' : '🔔 Enable') . ' Notifications', 
                     'callback_data' => 'settings_toggle_notifications'],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                ]
            ],
        ];
    }

    /**
     * Handle register
     */
    private function handleRegister(): array
    {
        $registrationUrl = config('app.frontend_url', 'http://localhost:5173') 
            . "/register?telegram_id={$this->userId}";

        return [
            'message' => "<b>✅ Register Now</b>\n\n"
                . "Click the button below to complete your registration:\n\n"
                . "Registration takes less than 2 minutes!",
            'keyboard' => [
                [
                    ['text' => '📝 Register', 'url' => $registrationUrl],
                ],
                [
                    ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                ]
            ],
        ];
    }
}
