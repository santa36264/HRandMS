<?php

namespace App\Telegram\HotelBookingBot;

use App\Services\RoomService;
use App\Services\BookingService;
use App\Telegram\HotelBookingBot\Commands\StartCommand;
use App\Telegram\HotelBookingBot\Commands\HelpCommand;
use App\Telegram\HotelBookingBot\Commands\RoomSearchCommand;
use App\Telegram\HotelBookingBot\Commands\SearchStateManager;
use App\Telegram\HotelBookingBot\Commands\SearchCallbackHandler;
use App\Telegram\HotelBookingBot\Commands\RoomDetailsCallbackHandler;
use App\Telegram\HotelBookingBot\Commands\BookingCommand;
use App\Telegram\HotelBookingBot\Commands\BookingStateManager;
use App\Telegram\HotelBookingBot\Commands\BookingCallbackHandler;
use App\Telegram\HotelBookingBot\Commands\PaymentCommand;
use App\Telegram\HotelBookingBot\Commands\PaymentCallbackHandler;
use Illuminate\Support\Facades\Log;

class HotelBookingBot extends BaseBot
{
    /**
     * Bot name
     */
    protected string $botName = 'default';

    /**
     * Room service
     */
    private RoomService $roomService;

    /**
     * Booking service
     */
    private BookingService $bookingService;

    /**
     * Constructor
     */
    public function __construct(RoomService $roomService, BookingService $bookingService)
    {
        $this->roomService = $roomService;
        $this->bookingService = $bookingService;
    }

    /**
     * Handle command
     */
    protected function handleCommand(string $command): void
    {
        $parts = explode(' ', $command);
        $cmd = strtolower($parts[0]);

        Log::info('Telegram command received', [
            'command' => $cmd,
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
        ]);

        match ($cmd) {
            '/start' => $this->handleStartCommand(),
            '/help' => $this->handleHelpCommand(),
            '/rooms' => $this->handleRoomsCommand(),
            '/search' => $this->handleSearchCommand(),
            '/bookings' => $this->handleBookingsCommand(),
            '/mybookings' => $this->handleMyBookingsCommand(),
            '/cancel' => $this->handleCancelCommand(),
            '/register' => $this->handleRegisterCommand(),
            '/service' => $this->handleServiceCommand(),
            '/concierge' => $this->handleConciergeCommand(),
            '/airport' => $this->handleAirportCommand(),
            '/taxi' => $this->handleTaxiCommand(),
            '/tour' => $this->handleTourCommand(),
            '/food' => $this->handleFoodCommand(),
            '/spa' => $this->handleSpaCommand(),
            '/broadcast' => $this->handleAdminBroadcast($parts),
            '/stats' => $this->handleAdminStats(),
            '/checkin' => $this->handleAdminCheckIn($parts),
            '/checkout' => $this->handleAdminCheckOut($parts),
            '/assign' => $this->handleAdminAssign($parts),
            '/maintenance' => $this->handleAdminMaintenance($parts),
            default => $this->handleUnknownCommand($cmd),
        };
    }

    /**
     * Handle message
     */
    protected function handleMessage(string $text): void
    {
        // Check if user is in registration flow
        $registrationStateManager = new \App\Telegram\HotelBookingBot\Commands\RegistrationStateManager($this->userId);
        $registrationState = $registrationStateManager->getState();

        if ($registrationState === 'awaiting_email') {
            $this->handleRegistrationEmail($text, $registrationStateManager);
        } elseif ($registrationState === 'awaiting_verification_code') {
            $this->handleRegistrationVerificationCode($text, $registrationStateManager);
        } elseif ($registrationState === 'awaiting_name') {
            $this->handleRegistrationName($text, $registrationStateManager);
        } elseif ($registrationState === 'awaiting_phone') {
            $this->handleRegistrationPhone($text, $registrationStateManager);
        } else {
            // Check if user is in booking flow
            $bookingStateManager = new \App\Telegram\HotelBookingBot\Commands\BookingStateManager($this->userId);
            $bookingState = $bookingStateManager->getState();

            if ($bookingState === 'awaiting_guest_count') {
                $this->handleBookingGuestCount($text, $bookingStateManager);
            } elseif ($bookingState === 'awaiting_guest_names') {
                $this->handleBookingGuestName($text, $bookingStateManager);
            } elseif ($bookingState === 'awaiting_special_requests') {
                $this->handleBookingSpecialRequests($text, $bookingStateManager);
            } else {
                // Normal message handling
                Log::info('Telegram message received', [
                    'text' => $text,
                    'chat_id' => $this->chatId,
                    'user_id' => $this->userId,
                ]);

                $this->sendMessage(
                    "Thanks for your message! 🏨\n\n"
                    . "Type /help to see available commands."
                );
            }
        }
    }

    /**
     * Handle registration email
     */
    private function handleRegistrationEmail(string $email, $stateManager): void
    {
        try {
            $registerCommand = new \App\Telegram\HotelBookingBot\Commands\RegisterCommand(
                $this->userId,
                $this->chatId
            );

            $result = $registerCommand->handleEmailSubmission($email);

            if ($result['success']) {
                if ($result['action'] === 'awaiting_verification_code') {
                    $stateManager->setState('awaiting_verification_code');
                    $stateManager->setStateData(['user_id' => $result['user_id'] ?? null]);
                } elseif ($result['action'] === 'awaiting_name') {
                    $stateManager->setState('awaiting_name');
                    $stateManager->setStateData(['email' => $result['email']]);
                }

                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling registration email', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing email. Please try again.");
        }
    }

    /**
     * Handle registration verification code
     */
    private function handleRegistrationVerificationCode(string $code, $stateManager): void
    {
        try {
            $registerCommand = new \App\Telegram\HotelBookingBot\Commands\RegisterCommand(
                $this->userId,
                $this->chatId
            );

            $result = $registerCommand->verifyCode($code);

            if ($result['success']) {
                $stateManager->clearState();
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling verification code', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error verifying code. Please try again.");
        }
    }

    /**
     * Handle registration name
     */
    private function handleRegistrationName(string $name, $stateManager): void
    {
        $stateManager->updateStateData(['name' => $name]);
        $stateManager->setState('awaiting_phone');

        $this->sendMessage(
            "Thanks, <b>{$name}</b>! 👤\n\n"
            . "Now, please enter your phone number:\n"
            . "(or type 'skip' to continue without it)"
        );
    }

    /**
     * Handle registration phone
     */
    private function handleRegistrationPhone(string $phone, $stateManager): void
    {
        $data = $stateManager->getStateData();

        if (strtolower($phone) !== 'skip') {
            $data['phone'] = $phone;
        }

        try {
            $registerCommand = new \App\Telegram\HotelBookingBot\Commands\RegisterCommand(
                $this->userId,
                $this->chatId
            );

            $result = $registerCommand->createNewAccount($data);

            if ($result['success']) {
                $stateManager->clearState();
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error creating account', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error creating account. Please try again.");
        }
    }

    /**
     * Handle booking guest count
     */
    private function handleBookingGuestCount(string $text, $stateManager): void
    {
        try {
            $guestCount = (int)$text;

            $bookingCommand = new \App\Telegram\HotelBookingBot\Commands\BookingCommand(
                $this->roomService,
                $this->chatId,
                $this->userId
            );

            $result = $bookingCommand->handleGuestCount($guestCount);

            if ($result['success']) {
                $stateManager->setState($result['action']);
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling guest count', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Invalid input. Please enter a number.");
        }
    }

    /**
     * Handle booking guest name
     */
    private function handleBookingGuestName(string $name, $stateManager): void
    {
        try {
            $bookingCommand = new \App\Telegram\HotelBookingBot\Commands\BookingCommand(
                $this->roomService,
                $this->chatId,
                $this->userId
            );

            $result = $bookingCommand->handleGuestName($name);

            if ($result['success']) {
                $stateManager->setState($result['action']);
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling guest name', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing guest name. Please try again.");
        }
    }

    /**
     * Handle booking special requests
     */
    private function handleBookingSpecialRequests(string $requests, $stateManager): void
    {
        try {
            $bookingCommand = new \App\Telegram\HotelBookingBot\Commands\BookingCommand(
                $this->roomService,
                $this->chatId,
                $this->userId
            );

            $result = $bookingCommand->handleSpecialRequests($requests);

            if ($result['success']) {
                $stateManager->setState($result['action']);
                $this->sendMessageWithKeyboard($result['message'], $bookingCommand->getConfirmationButtons());
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling special requests', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing special requests. Please try again.");
        }
    }

    /**
     * Handle callback query
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';

        Log::info('Telegram callback query received', [
            'data' => $data,
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
        ]);

        if (str_starts_with($data, 'menu_')) {
            $this->handleMenuCallback($callbackId, $data);
        } elseif (str_starts_with($data, 'help_')) {
            $this->handleHelpCallback($callbackId, $data);
        } elseif (str_starts_with($data, 'search_')) {
            $this->handleSearchCallback($callbackId, $data);
        } elseif (str_starts_with($data, 'room_')) {
            $this->handleRoomCallback($callbackId, $data);
        } elseif (str_starts_with($data, 'favorite_')) {
            $this->handleFavoriteCallback($callbackId, $data);
        } elseif (str_starts_with($data, 'payment_')) {
            $this->handlePaymentCallbackQuery($callbackId, $data);
        } elseif (str_starts_with($data, 'checkout_')) {
            $this->handleCheckOutCallbackQuery($callbackId, $data);
        } elseif (str_starts_with($data, 'mybooking')) {
            $this->handleMyBookingsCallbackQuery($callbackId, $data);
        } elseif (str_starts_with($data, 'booking_')) {
            $this->handleBookingCallbackQuery($callbackId, $data);
        } elseif (str_starts_with($data, 'service_')) {
            $this->handleRoomServiceCallbackQuery($callbackId, $data);
        } elseif (str_starts_with($data, 'concierge_')) {
            $this->handleConciergeCallbackQuery($callbackId, $data);
        }

        $this->answerCallbackQuery($callbackId, 'Processing...');
    }

    /**
     * Handle group command
     */
    protected function handleGroupCommand(string $command): void
    {
        // Only respond to specific commands in groups
        if ($command === '/help') {
            $this->handleHelpCommand();
        }
    }

    /**
     * Handle /start command
     */
    private function handleStartCommand(): void
    {
        try {
            $firstName = $this->message['from']['first_name'] ?? 'Guest';
            $username = $this->message['from']['username'] ?? null;

            $startCommand = new \App\Telegram\HotelBookingBot\Commands\StartCommand(
                $this->userId,
                $this->chatId,
                $firstName,
                $username
            );

            $result = $startCommand->execute();

            if (!$result['success']) {
                $this->sendMessage($result['message']);
                return;
            }

            // Send welcome message with keyboard
            $this->sendMessageWithKeyboard($result['message'], $result['keyboard']);

            // Send promotional message if available
            if ($result['promo']) {
                $this->sendMessage($result['promo']);
            }

            // If user not registered, send registration info
            if (!$result['user']) {
                $registrationUrl = $startCommand->getRegistrationUrl();
                $this->sendMessage(
                    "📝 <b>Register Now</b>\n\n"
                    . "Click the Register button above or visit:\n"
                    . "<a href=\"{$registrationUrl}\">Register Here</a>\n\n"
                    . "Registration takes less than 2 minutes!"
                );
            }
        } catch (\Exception $e) {
            Log::error('Error handling start command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error initializing bot. Please try again later.");
        }
    }

    /**
     * Handle /help command
     */
    private function handleHelpCommand(): void
    {
        $message = HelpCommand::getMainHelpMessage();
        $keyboard = HelpCommand::getHelpKeyboard();

        $this->sendMessageWithKeyboard($message, $keyboard);
    }

    /**
     * Handle /search command
     */
    private function handleSearchCommand(): void
    {
        try {
            $searchCommand = new RoomSearchCommand($this->roomService);
            $result = $searchCommand->startSearch();

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['keyboard']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling search command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error starting room search. Please try again later.");
        }
    }

    /**
     * Handle /rooms command
     */
    private function handleRoomsCommand(): void
    {
        try {
            // Get available rooms from service
            $rooms = $this->roomService->getAvailableRooms();

            if (empty($rooms)) {
                $this->sendMessage("❌ No rooms available at the moment.");
                return;
            }

            $message = "<b>Available Rooms:</b>\n\n";
            $buttons = [];

            foreach ($rooms as $room) {
                $message .= "🏠 <b>{$room['name']}</b>\n"
                    . "   Price: {$room['price']} ETB/night\n"
                    . "   Capacity: {$room['capacity']} guests\n\n";

                $buttons[] = [
                    ['text' => "View {$room['id']}", 'callback_data' => "room_{$room['id']}"],
                ];
            }

            $this->sendMessageWithKeyboard($message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error fetching rooms', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error fetching rooms. Please try again later.");
        }
    }

    /**
     * Handle /bookings command
     */
    private function handleBookingsCommand(): void
    {
        try {
            // Get user bookings from service
            $bookings = $this->bookingService->getUserBookings($this->userId);

            if (empty($bookings)) {
                $this->sendMessage("📋 You have no bookings yet.");
                return;
            }

            $message = "<b>Your Bookings:</b>\n\n";
            $buttons = [];

            foreach ($bookings as $booking) {
                $message .= "📅 <b>Booking #{$booking['id']}</b>\n"
                    . "   Room: {$booking['room_name']}\n"
                    . "   Check-in: {$booking['check_in']}\n"
                    . "   Check-out: {$booking['check_out']}\n"
                    . "   Status: {$booking['status']}\n\n";

                $buttons[] = [
                    ['text' => "Details", 'callback_data' => "booking_{$booking['id']}_view"],
                    ['text' => "Cancel", 'callback_data' => "booking_{$booking['id']}_cancel"],
                ];
            }

            $this->sendMessageWithKeyboard($message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error fetching bookings', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error fetching bookings. Please try again later.");
        }
    }

    /**
     * Handle /cancel command
     */
    private function handleCancelCommand(): void
    {
        $this->sendMessage(
            "To cancel a booking, use the /mybookings command and select the booking you want to cancel."
        );
    }

    private function handleMyBookingsCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\MyBookingsCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showBookings();

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling my bookings command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading bookings. Please try again later.");
        }
    }

    /**
     * Handle /register command
     */
    private function handleRegisterCommand(): void
    {
        try {
            $registerCommand = new \App\Telegram\HotelBookingBot\Commands\RegisterCommand(
                $this->userId,
                $this->chatId
            );

            $result = $registerCommand->startRegistration();

            if ($result['success']) {
                // Set registration state
                $stateManager = new \App\Telegram\HotelBookingBot\Commands\RegistrationStateManager($this->userId);
                $stateManager->setState('awaiting_email');

                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling register command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error starting registration. Please try again later.");
        }
    }

    /**
     * Handle unknown command
     */
    private function handleUnknownCommand(string $command): void
    {
        $this->sendMessage(
            "❓ Unknown command: {$command}\n\n"
            . "Type /help to see available commands."
        );
    }

    /**
     * Handle room callback
     */
    private function handleRoomCallback(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\RoomDetailsCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId,
                $this->roomService
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                // Send photos if available
                if (isset($response['has_photos']) && $response['has_photos'] && !empty($response['photos'])) {
                    $this->sendMediaGroup($response['photos']);
                }

                // Send details message
                if (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling room callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading room details. Please try again later.");
        }
    }

    /**
     * Handle favorite callback
     */
    private function handleFavoriteCallback(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\RoomDetailsCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId,
                $this->roomService
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                $this->sendMessage($response['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling favorite callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error updating favorites. Please try again later.");
        }
    }

    /**
     * Handle booking callback query
     */
    private function handleBookingCallbackQuery(string $callbackId, string $data): void
    {
        try {
            // Check if it's a room booking start (booking_room_{roomId})
            if (str_contains($data, 'booking_room_')) {
                $parts = explode('_', $data);
                if (count($parts) >= 3) {
                    $roomId = (int)$parts[2];
                    $this->handleStartBooking($roomId);
                    $this->answerCallbackQuery($callbackId, 'Starting booking...');
                    return;
                }
            }

            // Handle booking confirmation/edit/cancel
            $handler = new \App\Telegram\HotelBookingBot\Commands\BookingCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId,
                $this->roomService
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['action']) && $response['action'] === 'awaiting_guest_count') {
                    $this->sendMessage($response['message']);
                } elseif (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling booking callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing booking. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    /**
     * Handle start booking
     */
    private function handleStartBooking(int $roomId): void
    {
        try {
            $bookingCommand = new \App\Telegram\HotelBookingBot\Commands\BookingCommand(
                $this->roomService,
                $this->chatId,
                $this->userId
            );

            $result = $bookingCommand->startBooking($roomId);

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error starting booking', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error starting booking. Please try again later.");
        }
    }

    /**
     * View booking details
     */
    private function viewBookingDetails(int $bookingId): void
    {
        $booking = $this->bookingService->getBookingById($bookingId);

        if (!$booking) {
            $this->sendMessage("❌ Booking not found.");
            return;
        }

        $message = "<b>Booking Details</b>\n\n"
            . "📅 Booking ID: #{$booking['id']}\n"
            . "🏠 Room: {$booking['room_name']}\n"
            . "📍 Check-in: {$booking['check_in']}\n"
            . "📍 Check-out: {$booking['check_out']}\n"
            . "💰 Total: {$booking['total']} ETB\n"
            . "📊 Status: {$booking['status']}\n";

        $this->sendMessage($message);
    }

    /**
     * Cancel booking
     */
    private function cancelBooking(int $bookingId): void
    {
        $result = $this->bookingService->cancelBooking($bookingId);

        if ($result) {
            $this->sendMessage("✅ Booking cancelled successfully.");
        } else {
            $this->sendMessage("❌ Failed to cancel booking. Please try again later.");
        }
    }

    /**
     * Handle menu callback
     */
    private function handleMenuCallback(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\MenuCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['keyboard'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['keyboard']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling menu callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing your request. Please try again later.");
        }
    }

    /**
     * Handle help callback
     */
    private function handleHelpCallback(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\HelpCallbackHandler(
                $this->chatId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['keyboard'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['keyboard']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling help callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing your request. Please try again later.");
        }
    }

    /**
     * Handle search callback
     */
    private function handleSearchCallback(string $callbackId, string $data): void
    {
        try {
            $handler = new SearchCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId,
                $this->roomService
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['keyboard'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['keyboard']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling search callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing your request. Please try again later.");
        }
    }

    /**
     * Handle payment callback query
     */
    private function handlePaymentCallbackQuery(string $callbackId, string $data): void
    {
        try {
            $chapaGateway = app('App\Payments\Gateways\ChapaGateway');
            
            $handler = new \App\Telegram\HotelBookingBot\Commands\PaymentCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId,
                $chapaGateway
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['checkout_url'])) {
                    // Send payment link
                    $this->sendMessageWithKeyboard(
                        $response['message'],
                        [[
                            ['text' => '💳 Complete Payment', 'url' => $response['checkout_url']],
                        ]]
                    );
                } elseif (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling payment callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing payment. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    private function handleMyBookingsCallbackQuery(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\MyBookingsCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['action'])) {
                    $this->sendMessage($response['message']);
                } elseif (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling my bookings callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing request. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    private function handleCheckOutCallbackQuery(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\CheckOutCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['action'])) {
                    $this->sendMessage($response['message']);
                } elseif (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling check-out callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing request. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    /**
     * Handle /service command
     */
    private function handleServiceCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\RoomServiceCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showMenuCategories();

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling service command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading room service menu. Please try again later.");
        }
    }

    /**
     * Handle room service callback query
     */
    private function handleRoomServiceCallbackQuery(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\RoomServiceCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling room service callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing request. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    /**
     * Handle /concierge command
     */
    private function handleConciergeCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showMainMenu();

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling concierge command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading concierge services. Please try again later.");
        }
    }

    /**
     * Handle /airport command
     */
    private function handleAirportCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showServicesByType('airport');

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling airport command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading airport services. Please try again later.");
        }
    }

    /**
     * Handle /taxi command
     */
    private function handleTaxiCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showServicesByType('taxi');

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling taxi command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading taxi services. Please try again later.");
        }
    }

    /**
     * Handle /tour command
     */
    private function handleTourCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showServicesByType('tour');

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling tour command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading tour services. Please try again later.");
        }
    }

    /**
     * Handle /food command
     */
    private function handleFoodCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showServicesByType('food');

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling food command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading restaurant services. Please try again later.");
        }
    }

    /**
     * Handle /spa command
     */
    private function handleSpaCommand(): void
    {
        try {
            $command = new \App\Telegram\HotelBookingBot\Commands\ConciergeCommand(
                $this->chatId,
                $this->userId
            );

            $result = $command->showServicesByType('spa');

            if ($result['success']) {
                $this->sendMessageWithKeyboard($result['message'], $result['buttons']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling spa command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error loading spa services. Please try again later.");
        }
    }

    /**
     * Handle concierge callback query
     */
    private function handleConciergeCallbackQuery(string $callbackId, string $data): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\ConciergeCallbackHandler(
                $this->chatId,
                $this->userId,
                $data,
                $callbackId
            );

            $result = $handler->handle();

            if ($result['success']) {
                $response = $result['response'];
                
                if (isset($response['buttons'])) {
                    $this->sendMessageWithKeyboard($response['message'], $response['buttons']);
                } else {
                    $this->sendMessage($response['message']);
                }
            } else {
                $this->sendMessage($result['message']);
            }

            $this->answerCallbackQuery($callbackId, 'Processing...');
        } catch (\Exception $e) {
            Log::error('Error handling concierge callback', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing request. Please try again later.");
            $this->answerCallbackQuery($callbackId, 'Error');
        }
    }

    /**
     * Handle /broadcast admin command
     */
    private function handleAdminBroadcast(array $parts): void
    {
        try {
            $params = array_slice($parts, 1);
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'broadcast',
                $params
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);

                // Send broadcast to all users
                if (isset($result['broadcast_data'])) {
                    $this->broadcastToUsers($result['broadcast_data']);
                }
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling broadcast command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing broadcast.");
        }
    }

    /**
     * Handle /stats admin command
     */
    private function handleAdminStats(): void
    {
        try {
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'stats'
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling stats command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error retrieving statistics.");
        }
    }

    /**
     * Handle /checkin admin command
     */
    private function handleAdminCheckIn(array $parts): void
    {
        try {
            $params = array_slice($parts, 1);
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'checkin',
                $params
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling checkin command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing check-in.");
        }
    }

    /**
     * Handle /checkout admin command
     */
    private function handleAdminCheckOut(array $parts): void
    {
        try {
            $params = array_slice($parts, 1);
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'checkout',
                $params
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling checkout command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error processing check-out.");
        }
    }

    /**
     * Handle /assign admin command
     */
    private function handleAdminAssign(array $parts): void
    {
        try {
            $params = array_slice($parts, 1);
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'assign',
                $params
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling assign command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error assigning room.");
        }
    }

    /**
     * Handle /maintenance admin command
     */
    private function handleAdminMaintenance(array $parts): void
    {
        try {
            $params = array_slice($parts, 1);
            $handler = new \App\Telegram\HotelBookingBot\Commands\AdminCommandHandler(
                $this->chatId,
                $this->userId,
                'maintenance',
                $params
            );

            $result = $handler->handle();

            if ($result['success']) {
                $this->sendMessage($result['message']);
            } else {
                $this->sendMessage($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling maintenance command', ['error' => $e->getMessage()]);
            $this->sendMessage("❌ Error updating room status.");
        }
    }

    /**
     * Broadcast message to all users
     */
    private function broadcastToUsers(array $broadcastData): void
    {
        try {
            Log::info('Broadcasting message to users', [
                'user_count' => $broadcastData['count'],
                'message_preview' => substr($broadcastData['message'], 0, 100),
            ]);

            // In production, this would send to each user via Telegram API
            // For now, we log it for the system to process asynchronously
        } catch (\Exception $e) {
            Log::error('Error broadcasting to users', ['error' => $e->getMessage()]);
        }
    }
}